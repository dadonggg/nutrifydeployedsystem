<?php
declare(strict_types=1);
namespace App\Models;

use App\Core\Model;
use PDO;

final class FitnessProgressTracking extends Model
{
    public function calculateAndSave(int $memberId, int $serviceRequestId): int
    {
        try {
            // Get all logged dates (workout, nutrition, and weight logs)
            $workoutDates = $this->getWorkoutDates($serviceRequestId, $memberId);
            $nutritionDates = $this->getNutritionDates($serviceRequestId, $memberId);
            $weightDates = $this->getWeightDates($memberId);
            $allDates = array_unique(array_merge($workoutDates, $nutritionDates, $weightDates));
            sort($allDates);
            
            if (empty($allDates)) {
                return 0;
            }
            
            // Get client's active weekdays if any
            $activeWeekdays = [];
            $stmt = $this->db()->prepare('SELECT list_of_weekdays FROM fitness_programs WHERE member_id = :mid LIMIT 1');
            $stmt->execute([':mid' => $memberId]);
            $rawWeekdays = $stmt->fetchColumn();
            if ($rawWeekdays) {
                $activeWeekdays = array_map('trim', explode(',', $rawWeekdays));
            }

            // Calculate consistency score using formula: Sc = Σ(B + (si · w))
            $B = 10; // Base points per log
            $w = 2;  // Streak bonus weight
            $totalScore = 0;
            $currentStreak = 0;
            $maxStreak = 0;
            $lastDate = null;
            
            foreach ($allDates as $date) {
                if ($lastDate !== null) {
                    if (!$this->hasMissedScheduledDays($lastDate, $date, $activeWeekdays)) {
                        $currentStreak++;
                    } else {
                        $currentStreak = 0;
                    }
                }
                
                $totalScore += $B + ($currentStreak * $w);
                $maxStreak = max($maxStreak, $currentStreak);
                $lastDate = $date;
            }
            
            // Calculate workout frequency per week
            $totalDays = count($allDates);
            $firstDate = new \DateTime($allDates[0]);
            $lastDateObj = new \DateTime($allDates[count($allDates) - 1]);
            $daysDiff = $firstDate->diff($lastDateObj)->days + 1;
            $weeks = max(1, $daysDiff / 7);
            $workoutFrequency = $totalDays / $weeks;
            
            // Save progress snapshot
            $stmt = $this->db()->prepare(
                'INSERT INTO fitness_progress_tracking 
                (member_id, service_request_id, snapshot_date, consistency_score, current_streak, 
                 total_logged_days, total_workouts, total_nutrition_logs, workout_frequency_per_week)
                VALUES (:mid, :req_id, CURDATE(), :score, :streak, :days, :workouts, :nutrition, :frequency)'
            );
            
            $stmt->execute([
                ':mid' => $memberId,
                ':req_id' => $serviceRequestId,
                ':score' => round($totalScore, 2),
                ':streak' => $currentStreak,
                ':days' => $totalDays,
                ':workouts' => count($workoutDates),
                ':nutrition' => count($nutritionDates),
                ':frequency' => round($workoutFrequency, 2)
            ]);
            
            return (int)$this->db()->lastInsertId();
        } catch (\Exception $e) {
            $this->logError("Failed to calculate progress: " . $e->getMessage());
            return 0;
        }
    }

    public function getCurrentProgressByMemberId(int $memberId, int $serviceRequestId = 0, int $userId = 0): array
    {
        if ($memberId <= 0 && $serviceRequestId > 0) {
            $stmt = $this->db()->prepare('SELECT member_id FROM fitness_service_requests WHERE id = :req_id LIMIT 1');
            $stmt->execute([':req_id' => $serviceRequestId]);
            $memberId = (int)($stmt->fetchColumn() ?: 0);
        }

        // Get all logged dates (workouts, nutrition, and weight logs)
        $workoutDates = $this->getWorkoutDates($serviceRequestId, $memberId);
        $nutritionDates = $this->getNutritionDates($serviceRequestId, $memberId);
        $weightDates = $this->getWeightDates($memberId, $userId);
        $allDates = array_unique(array_merge($workoutDates, $nutritionDates, $weightDates));
        sort($allDates);
        
        if (empty($allDates)) {
            return [
                'consistency_score' => 0,
                'current_streak' => 0,
                'total_logged_days' => 0,
                'total_workouts' => count($workoutDates),
                'total_nutrition_logs' => count($nutritionDates),
                'workout_frequency_per_week' => 0
            ];
        }
        
        // Get client's active weekdays if any
        $activeWeekdays = [];
        if ($memberId > 0) {
            $stmt = $this->db()->prepare('SELECT list_of_weekdays FROM fitness_programs WHERE member_id = :mid LIMIT 1');
            $stmt->execute([':mid' => $memberId]);
            $rawWeekdays = $stmt->fetchColumn();
            if ($rawWeekdays) {
                $activeWeekdays = array_map('trim', explode(',', $rawWeekdays));
            }
        }

        // Calculate consistency score
        $B = 10;
        $w = 2;
        $totalScore = 0;
        $currentStreak = 0;
        $lastDate = null;
        
        foreach ($allDates as $date) {
            if ($lastDate !== null) {
                if (!$this->hasMissedScheduledDays($lastDate, $date, $activeWeekdays)) {
                    $currentStreak++;
                } else {
                    $currentStreak = 0;
                }
            }
            
            $totalScore += $B + ($currentStreak * $w);
            $lastDate = $date;
        }
        
        // Calculate workout frequency
        $totalDays = count($allDates);
        $firstDate = new \DateTime($allDates[0]);
        $lastDateObj = new \DateTime($allDates[count($allDates) - 1]);
        $daysDiff = $firstDate->diff($lastDateObj)->days + 1;
        $weeks = max(1, $daysDiff / 7);
        $workoutFrequency = $totalDays / $weeks;
        
        return [
            'consistency_score' => round($totalScore, 2),
            'current_streak' => $currentStreak,
            'total_logged_days' => $totalDays,
            'total_workouts' => count($workoutDates),
            'total_nutrition_logs' => count($nutritionDates),
            'workout_frequency_per_week' => round($workoutFrequency, 2)
        ];
    }

    public function getCurrentProgress(int $serviceRequestId): array
    {
        return $this->getCurrentProgressByMemberId(0, $serviceRequestId);
    }

    public function sendToTrainer(int $progressId): bool
    {
        try {
            $stmt = $this->db()->prepare(
                'UPDATE fitness_progress_tracking SET sent_to_trainer = 1, sent_at = NOW() WHERE id = :id'
            );
            return $stmt->execute([':id' => $progressId]);
        } catch (\Exception $e) {
            $this->logError("Failed to send progress: " . $e->getMessage());
            return false;
        }
    }

    public function findByServiceRequestId(int $serviceRequestId): array
    {
        $stmt = $this->db()->prepare(
            'SELECT * FROM fitness_progress_tracking 
             WHERE service_request_id = :req_id 
             ORDER BY snapshot_date DESC'
        );
        $stmt->execute([':req_id' => $serviceRequestId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findSentToTrainer(int $trainerId): array
    {
        $stmt = $this->db()->prepare(
            'SELECT fpt.*, 
                    COALESCE(fsr.full_name, u.fullname, "Client") as client_name, 
                    u.fullname as member_fullname,
                    u.id as user_id,
                    gm.id as member_id,
                    gm.fitness_goal as member_goal,
                    (SELECT COUNT(*) FROM fitness_trainer_feedback ftf 
                     WHERE ftf.progress_tracking_id = fpt.id 
                        OR (ftf.member_id = fpt.member_id AND ftf.trainer_id = :tid2 AND ftf.created_at >= fpt.sent_at)) as feedback_given
             FROM fitness_progress_tracking fpt
             LEFT JOIN fitness_service_requests fsr ON fsr.id = fpt.service_request_id
             JOIN gym_members gm ON gm.id = fpt.member_id
             JOIN users u ON u.id = gm.user_id
             WHERE (fsr.assigned_trainer_id = :tid OR gm.assigned_trainer_id = :tid3) 
               AND fpt.sent_to_trainer = 1
             ORDER BY fpt.sent_at DESC'
        );
        $stmt->execute([':tid' => $trainerId, ':tid2' => $trainerId, ':tid3' => $trainerId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findPendingByServiceRequestId(int $serviceRequestId): ?array
    {
        $stmt = $this->db()->prepare(
            'SELECT * FROM fitness_progress_tracking 
             WHERE service_request_id = :req_id 
             AND sent_to_trainer = 1
             ORDER BY sent_at DESC
             LIMIT 1'
        );
        $stmt->execute([':req_id' => $serviceRequestId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    private function getWeightDates(int $memberId, int $userId = 0): array
    {
        if ($memberId <= 0 && $userId <= 0) return [];
        try {
            if ($memberId > 0 && $userId > 0) {
                $stmt = $this->db()->prepare(
                    'SELECT DISTINCT date_logged FROM member_weight_logs WHERE member_id = :mid OR user_id = :uid'
                );
                $stmt->execute([':mid' => $memberId, ':uid' => $userId]);
            } elseif ($memberId > 0) {
                $stmt = $this->db()->prepare(
                    'SELECT DISTINCT date_logged FROM member_weight_logs WHERE member_id = :mid OR user_id = (SELECT user_id FROM gym_members WHERE id = :mid2 LIMIT 1)'
                );
                $stmt->execute([':mid' => $memberId, ':mid2' => $memberId]);
            } else {
                $stmt = $this->db()->prepare(
                    'SELECT DISTINCT date_logged FROM member_weight_logs WHERE user_id = :uid'
                );
                $stmt->execute([':uid' => $userId]);
            }
            return array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'date_logged');
        } catch (\Exception $e) {
            return [];
        }
    }

    private function getWorkoutDates(int $serviceRequestId, int $memberId = 0): array
    {
        if ($serviceRequestId > 0 && $memberId > 0) {
            $stmt = $this->db()->prepare(
                'SELECT DISTINCT log_date FROM fitness_workout_logs WHERE service_request_id = :req_id OR member_id = :mid'
            );
            $stmt->execute([':req_id' => $serviceRequestId, ':mid' => $memberId]);
        } elseif ($serviceRequestId > 0) {
            $stmt = $this->db()->prepare(
                'SELECT DISTINCT log_date FROM fitness_workout_logs WHERE service_request_id = :req_id'
            );
            $stmt->execute([':req_id' => $serviceRequestId]);
        } elseif ($memberId > 0) {
            $stmt = $this->db()->prepare(
                'SELECT DISTINCT log_date FROM fitness_workout_logs WHERE member_id = :mid'
            );
            $stmt->execute([':mid' => $memberId]);
        } else {
            return [];
        }
        return array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'log_date');
    }

    private function getNutritionDates(int $serviceRequestId, int $memberId = 0): array
    {
        if ($serviceRequestId > 0 && $memberId > 0) {
            $stmt = $this->db()->prepare(
                'SELECT DISTINCT log_date FROM fitness_nutrition_logs WHERE service_request_id = :req_id OR member_id = :mid'
            );
            $stmt->execute([':req_id' => $serviceRequestId, ':mid' => $memberId]);
        } elseif ($serviceRequestId > 0) {
            $stmt = $this->db()->prepare(
                'SELECT DISTINCT log_date FROM fitness_nutrition_logs WHERE service_request_id = :req_id'
            );
            $stmt->execute([':req_id' => $serviceRequestId]);
        } elseif ($memberId > 0) {
            $stmt = $this->db()->prepare(
                'SELECT DISTINCT log_date FROM fitness_nutrition_logs WHERE member_id = :mid'
            );
            $stmt->execute([':mid' => $memberId]);
        } else {
            return [];
        }
        return array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'log_date');
    }

    private function hasMissedScheduledDays(string $lastDate, string $currentDate, array $activeWeekdays): bool
    {
        if (empty($activeWeekdays)) {
            $lastDateObj = new \DateTime($lastDate);
            $currentDateObj = new \DateTime($currentDate);
            return $lastDateObj->diff($currentDateObj)->days > 1;
        }

        $lastDateObj = new \DateTime($lastDate);
        $currentDateObj = new \DateTime($currentDate);
        
        $interval = new \DateInterval('P1D');
        $period = new \DatePeriod(
            (clone $lastDateObj)->modify('+1 day'),
            $interval,
            $currentDateObj
        );

        foreach ($period as $date) {
            $dayOfWeek = $date->format('l');
            if (in_array($dayOfWeek, $activeWeekdays, true)) {
                return true;
            }
        }

        return false;
    }

    private function logError(string $message): void
    {
        $logDir = __DIR__ . '/../logs';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }
        $logFile = $logDir . '/fitness_service.log';
        $logMessage = sprintf("[%s] FitnessProgressTracking: %s\n", date('Y-m-d H:i:s'), $message);
        @error_log($logMessage, 3, $logFile);
    }
}
