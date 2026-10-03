<?php
declare(strict_types=1);
namespace App\Services;

/**
 * Gemini AI Service
 * Generates fitness plans using Google Gemini API
 */
class GeminiService
{
    private const DEFAULT_MODEL = 'gemini-1.5-flash';
    private const BASE_URL      = 'https://generativelanguage.googleapis.com/v1beta/models/';
    private const TIMEOUT       = 120;

    private string $apiKey;
    private string $model;

    public function __construct()
    {
        $config = [];
        $configFile = dirname(__DIR__) . '/config/config.php';
        if (file_exists($configFile)) {
            $loaded = require $configFile;
            if (is_array($loaded)) {
                $config = $loaded;
            }
        }

        $this->apiKey = (string)($config['gemini']['api_key'] ?? getenv('GEMINI_API_KEY') ?: '');
        $this->model  = (string)($config['gemini']['model'] ?? self::DEFAULT_MODEL);
    }

    /**
     * Generate complete fitness plan from client profile
     */
    public function generateFitnessPlan(array $clientProfile): array
    {
        $startTime = microtime(true);

        try {
            if (empty($this->apiKey)) {
                throw new \Exception("Gemini API key is not configured. Please set your API key in app/config/config.php under ['gemini']['api_key'].");
            }

            $prompt   = $this->buildPrompt($clientProfile);
            $response = $this->callGemini($prompt);
            $plan     = $this->parseResponse($response);

            return [
                'success'        => true,
                'plan'           => $plan,
                'generationTime' => (int)((microtime(true) - $startTime) * 1000),
                'model'          => $this->model,
            ];
        } catch (\Exception $e) {
            return [
                'success'        => false,
                'error'          => $e->getMessage(),
                'generationTime' => (int)((microtime(true) - $startTime) * 1000),
                'model'          => $this->model,
            ];
        }
    }

    /**
     * Build prompt for Gemini
     */
    private function buildPrompt(array $profile): string
    {
        $goalsStr = is_array($profile['fitness_goals'] ?? null)
            ? implode(', ', $profile['fitness_goals'])
            : ($profile['fitness_goals'] ?? 'General Fitness');

        $name = (string)($profile['name'] ?? 'Client');
        $age = (string)($profile['age'] ?? '25');
        $activityLevel = (string)($profile['activity_level'] ?? 'Moderate');
        $medConditions = !empty($profile['medical_conditions']) ? (string)$profile['medical_conditions'] : 'None';
        $dietPrefs = !empty($profile['dietary_preferences']) ? (string)$profile['dietary_preferences'] : 'None';
        $sessions = (string)($profile['sessions_per_week'] ?? '3-4');

        return <<<PROMPT
You are an expert certified fitness trainer AI creating a personalized weekly workout plan.

CLIENT PROFILE:
- Name: {$name}
- Age: {$age}
- Fitness Goals: {$goalsStr}
- Current Activity Level: {$activityLevel}
- Medical Conditions: {$medConditions}
- Dietary Preferences: {$dietPrefs}
- Sessions Per Week: {$sessions}

Generate a complete fitness plan. You MUST respond ONLY with valid JSON — no markdown, no code fences, no explanations outside the JSON.

Required JSON format:
{
  "workoutRoutine": {
    "MON": [{"name": "Push-ups", "sets": 3, "reps": 12, "restSeconds": 60}],
    "TUE": [],
    "WED": [{"name": "Squats", "sets": 4, "reps": 15, "restSeconds": 90}],
    "THU": [],
    "FRI": [{"name": "Pull-ups", "sets": 3, "reps": 8, "restSeconds": 120}],
    "SAT": [],
    "SUN": []
  },
  "mealPlan": {
    "macros": {
      "calories": 2200,
      "protein": 165,
      "carbs": 220,
      "fats": 70
    },
    "meals": [
      {
        "type": "Breakfast",
        "time": "07:00 AM",
        "foodName": "Oatmeal with banana and protein powder",
        "amount": 250,
        "unit": "grams"
      }
    ],
    "quickSuggestions": {
      "Breakfast": ["Scrambled eggs with toast", "Greek yogurt with berries", "Protein pancakes"],
      "Lunch": ["Grilled chicken salad", "Turkey sandwich", "Quinoa bowl"],
      "Dinner": ["Salmon with vegetables", "Chicken stir-fry", "Lean beef with rice"],
      "Snack": ["Protein shake", "Mixed nuts", "Apple with peanut butter"]
    }
  },
  "aiNotes": "Brief explanation of why this plan suits the client, any warnings, tips for success."
}

Rules:
- Match workout intensity to the client's activity level
- Rest days must have an empty array []
- Focus exercises on stated fitness goals
- Respect dietary preferences and medical conditions
- Include 3-5 exercises on active days for beginners, 5-7 for intermediate/advanced
PROMPT;
    }

    /**
     * Call Gemini REST API
     */
    private function callGemini(string $prompt): string
    {
        $modelsToTry = array_unique([$this->model, 'gemini-1.5-flash', 'gemini-2.0-flash', 'gemini-1.5-pro']);
        $lastError = '';

        foreach ($modelsToTry as $currentModel) {
            $url  = self::BASE_URL . $currentModel . ':generateContent?key=' . $this->apiKey;
            $body = json_encode([
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt]
                        ]
                    ]
                ],
                'generationConfig' => [
                    'temperature'     => 0.7,
                    'topP'            => 0.9,
                    'maxOutputTokens' => 8192,
                    'responseMimeType'=> 'application/json',
                ],
            ]);

            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => $body,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => self::TIMEOUT,
                CURLOPT_HTTPHEADER     => [
                    'Content-Type: application/json',
                    'x-goog-api-key: ' . $this->apiKey,
                ],
                CURLOPT_SSL_VERIFYPEER => true,
            ]);

            $response  = curl_exec($ch);
            $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            if ($curlError) {
                $lastError = "Gemini connection error: {$curlError}";
                continue;
            }

            if ($httpCode === 200) {
                $this->model = $currentModel;
                return (string)$response;
            }

            $decoded = json_decode((string)$response, true);
            $msg     = $decoded['error']['message'] ?? "HTTP {$httpCode}";
            $lastError = "Gemini API error (HTTP {$httpCode} on {$currentModel}): {$msg}";

            // If it's a 400 invalid API key error, don't keep trying other models since key is bad
            if ($httpCode === 400 && str_contains(strtolower($msg), 'api key')) {
                throw new \Exception($lastError);
            }
        }

        throw new \Exception($lastError ?: "Failed to get valid response from Gemini API");
    }

    /**
     * Parse Gemini response and extract the JSON plan
     */
    private function parseResponse(string $rawResponse): array
    {
        $decoded = json_decode($rawResponse, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception("Failed to parse Gemini outer response: " . json_last_error_msg());
        }

        // Extract text content from Gemini response structure
        $text = $decoded['candidates'][0]['content']['parts'][0]['text'] ?? null;

        if (!$text) {
            $finishReason = $decoded['candidates'][0]['finishReason'] ?? 'UNKNOWN';
            throw new \Exception("Gemini returned no content. Finish reason: {$finishReason}");
        }

        // Strip markdown code fences if present
        $text = preg_replace('/^```(?:json)?\s*/i', '', trim($text));
        $text = preg_replace('/\s*```$/', '', $text);

        $plan = json_decode($text, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception("Failed to parse AI plan JSON: " . json_last_error_msg());
        }

        if (!isset($plan['workoutRoutine'])) {
            throw new \Exception("AI response missing 'workoutRoutine' field");
        }

        return $plan;
    }

    /**
     * Quick connectivity check — returns available: true always
     * since Gemini is a cloud API (no local server needed)
     */
    public function checkOllamaStatus(): array
    {
        return [
            'available' => true,
            'models'    => [$this->model],
            'error'     => null,
        ];
    }
}
