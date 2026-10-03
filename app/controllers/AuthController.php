<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Container;
use App\Core\Mailer;
use App\Models\EmailVerification;
use App\Models\OtpCode;
use App\Models\User;
use App\Models\LoginActivity;
use DateTimeImmutable;

final class AuthController extends Controller
{
    public function loginAction(): void
    {
        if (isset($_SESSION['user_id'])) {
            $this->redirect('home/index');
        }

        $error = '';
        $success = '';

        if (isset($_SESSION['flash_success'])) {
            $success = (string)$_SESSION['flash_success'];
            unset($_SESSION['flash_success']);
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = trim((string)($_POST['email'] ?? ''));
            $password = (string)($_POST['password'] ?? '');

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = 'Invalid email address.';
            } elseif ($password === '') {
                $error = 'Password is required.';
            } else {
                $userModel = new User();
                $user = $userModel->findByEmail($email);
                $loginActivityModel = new LoginActivity();

                if (!$user) {
                    $loginActivityModel->logLoginFailed($email, 'Invalid email');
                    $error = 'Invalid email or password.';
                } elseif ((int)$user['is_verified'] !== 1) {
                    $loginActivityModel->logLoginFailed($email, 'Email not verified');
                    $token = bin2hex(random_bytes(32));
                    $verificationModel = new EmailVerification();
                    $verificationModel->deleteByUserId((int)$user['id']);
                    $verificationModel->create((int)$user['id'], $token);

                    $config = Container::get('config');
                    $baseUrl = rtrim((string)($config['app']['base_url'] ?? ''), '/');
                    $verifyLink = $baseUrl . '/index.php?r=auth/verify&token=' . urlencode($token);

                    $mailBody = '<p>You must verify your email before logging in.</p>' .
                        '<p><a href="' . htmlspecialchars($verifyLink) . '">Verify Email</a></p>';
                    $sent = Mailer::send((string)$user['email'], 'Verify your email', $mailBody);

                    if ($sent) {
                        $error = 'Your email is not verified. We re-sent the verification link to your email.';
                    } else {
                        $error = 'Your email is not verified, and we failed to send the verification email. ' . Mailer::lastError();
                    }
                } elseif (!password_verify($password, (string)$user['password'])) {
                    $loginActivityModel->logLoginFailed($email, 'Invalid password');
                    $error = 'Invalid email or password.';
                } else {
                    $otp = (string)random_int(100000, 999999);
                    $expiresAt = (new DateTimeImmutable())->modify('+5 minutes');

                    $otpModel = new OtpCode();
                    $otpModel->deleteAllForUser((int)$user['id']);
                    $otpModel->create((int)$user['id'], $otp, $expiresAt);

                    $mailBody = '<p>Your OTP code is: <strong>' . htmlspecialchars($otp) . '</strong></p>' .
                        '<p>This code expires in 5 minutes.</p>';
                    $sent = Mailer::send((string)$user['email'], 'Your OTP Code', $mailBody);
                    if (!$sent) {
                        $loginActivityModel->logLoginFailed($email, 'Failed to send OTP');
                        $error = 'Failed to send OTP to your email. Check SMTP settings. ' . Mailer::lastError();
                    } else {
                        $loginActivityModel->logOtpSent((int)$user['id'], $email);
                        $_SESSION['pending_otp_user_id'] = (int)$user['id'];
                        $this->redirect('auth/otp');
                    }
                }
            }
        }

        $this->view('auth/login', ['error' => $error, 'success' => $success]);
    }

    public function registerAction(): void
    {
        if (isset($_SESSION['user_id'])) {
            $this->redirect('home/index');
        }

        $error = '';
        $success = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $firstname = trim((string)($_POST['firstname'] ?? ''));
            $lastname = trim((string)($_POST['lastname'] ?? ''));
            $middleInitial = trim((string)($_POST['middle_initial'] ?? ''));
            $email = trim((string)($_POST['email'] ?? ''));
            $password = (string)($_POST['password'] ?? '');
            $passwordConfirm = (string)($_POST['password_confirm'] ?? '');
            $birthDateRaw = trim((string)($_POST['birth_date'] ?? ''));
            $heightRaw = $_POST['height_cm'] ?? '';
            $weightRaw = $_POST['weight_kg'] ?? '';

            if ($firstname === '' || mb_strlen($firstname) > 50) {
                $error = 'First name is required (max 50 characters).';
            } elseif ($lastname === '' || mb_strlen($lastname) > 50) {
                $error = 'Last name is required (max 50 characters).';
            } elseif (mb_strlen($middleInitial) > 5) {
                $error = 'Middle initial must be at most 5 characters.';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = 'Invalid email address.';
            } elseif (mb_strlen($password) < 8 || !hash_equals($password, $passwordConfirm)) {
                $error = 'Please enter a valid password. It must be at least 8 characters long and match the confirmation password.';
            } else {
                // Validate birth date
                $birthDate = $birthDateRaw;
                $birthDt = \DateTime::createFromFormat('Y-m-d', $birthDate);
                if (!$birthDt || $birthDt->format('Y-m-d') !== $birthDate) {
                    $error = 'Please enter a valid birth date.';
                } else {
                    $age = User::computeAge($birthDate);
                    if ($age < 1 || $age > 120) {
                        $error = 'Birth date results in an invalid age (must be 1–120 years).';
                    } else {
                        $heightCm = (float)$heightRaw;
                        if ($heightCm < 50 || $heightCm > 272) {
                            $error = 'Please enter height in centimeters (50–272).';
                        } else {
                            $weightKg = (float)$weightRaw;
                            if ($weightKg < 20 || $weightKg > 400) {
                                $error = 'Please enter weight in kilograms (20–400).';
                            }
                        }
                    }
                }
            }

            if ($error === '') {
                $userModel = new User();
                $existing = $userModel->findByEmail($email);

                if ($existing) {
                    if ((int)$existing['is_verified'] === 1) {
                        $error = 'Email is already registered.';
                    } else {
                        $token = bin2hex(random_bytes(32));
                        $verificationModel = new EmailVerification();
                        $verificationModel->deleteByUserId((int)$existing['id']);
                        $verificationModel->create((int)$existing['id'], $token);

                        $config = Container::get('config');
                        $baseUrl = rtrim((string)($config['app']['base_url'] ?? ''), '/');
                        $verifyLink = $baseUrl . '/index.php?r=auth/verify&token=' . urlencode($token);

                        $mailBody = '<p>Your account exists but is not verified yet.</p>' .
                            '<p><a href="' . htmlspecialchars($verifyLink) . '">Verify Email</a></p>';
                        $sent = Mailer::send($email, 'Verify your email', $mailBody);
                        if ($sent) {
                            $success = 'Account already exists but not verified. We re-sent the verification link.';
                        } else {
                            $error = 'Account exists but not verified, and we failed to send the verification email. ' . Mailer::lastError();
                        }
                    }
                } else {
                    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                    $userId = $userModel->create(
                        $firstname,
                        $lastname,
                        $middleInitial,
                        $birthDate,
                        (float)$heightCm,
                        (float)$weightKg,
                        $email,
                        $passwordHash
                    );

                    $token = bin2hex(random_bytes(32));
                    $verificationModel = new EmailVerification();
                    $verificationModel->deleteByUserId($userId);
                    $verificationModel->create($userId, $token);

                    $config = Container::get('config');
                    $baseUrl = rtrim((string)($config['app']['base_url'] ?? ''), '/');
                    $verifyLink = $baseUrl . '/index.php?r=auth/verify&token=' . urlencode($token);

                    $mailBody = '<p>Click the link below to verify your email:</p>' .
                        '<p><a href="' . htmlspecialchars($verifyLink) . '">Verify Email</a></p>';
                    $sent = Mailer::send($email, 'Verify your email', $mailBody);
                    if (!$sent) {
                        $error = 'Registration created, but failed to send verification email. Check SMTP settings. ' . Mailer::lastError();
                    } else {
                        $success = 'Registration successful. Please check your email to verify your account.';
                    }
                }
            }
        }

        $this->view('auth/register', ['error' => $error, 'success' => $success]);
    }

    public function verifyAction(): void
    {
        $token = trim((string)($_GET['token'] ?? ''));
        $error = '';
        $success = '';

        if ($token === '') {
            $error = 'Invalid verification token.';
        } else {
            $verificationModel = new EmailVerification();
            $record = $verificationModel->findByToken($token);

            if (!$record) {
                $error = 'Verification link is invalid or already used.';
            } else {
                $userModel = new User();
                $userModel->setVerified((int)$record['user_id']);
                $verificationModel->deleteByUserId((int)$record['user_id']);
                $success = 'Email verified successfully. You can now log in.';
                $_SESSION['flash_success'] = $success;
            }
        }

        $this->view('auth/verify_email', ['error' => $error, 'success' => $success]);
    }

    public function otpAction(): void
    {
        if (!isset($_SESSION['pending_otp_user_id'])) {
            $this->redirect('auth/login');
        }

        $error = '';
        $success = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $otp = trim((string)($_POST['otp'] ?? ''));
            $userId = (int)$_SESSION['pending_otp_user_id'];
            
            // Track attempts in session to prevent brute force
            $_SESSION['otp_attempts'] = (int)($_SESSION['otp_attempts'] ?? 0) + 1;

            if ($_SESSION['otp_attempts'] > 5) {
                $otpModel = new OtpCode();
                $otpModel->deleteAllForUser($userId);
                unset($_SESSION['pending_otp_user_id'], $_SESSION['otp_attempts']);
                $_SESSION['login_error'] = 'Too many failed OTP attempts. Please sign in again.';
                $this->redirect('auth/login');
            }

            if (!preg_match('/^[0-9]{6}$/', $otp)) {
                $error = 'OTP must be 6 digits.';
            } else {
                $otpModel = new OtpCode();
                $match = $otpModel->latestValidForUser($userId, $otp);

                $userModel = new User();
                $user = $userModel->findById($userId);
                $email = $user ? (string)$user['email'] : '';

                $loginActivityModel = new LoginActivity();

                if (!$match) {
                    $remaining = 5 - (int)$_SESSION['otp_attempts'];
                    $loginActivityModel->logOtpFailed($userId, $email, 'Invalid or expired OTP');
                    $error = 'Invalid or expired OTP. (' . max(0, $remaining) . ' attempts remaining)';
                } else {
                    $otpModel->deleteAllForUser($userId);
                    $loginActivityModel->logLoginSuccess($userId, $email);
                    
                    // Prevent session fixation
                    session_regenerate_id(true);
                    
                    $_SESSION['user_id'] = $userId;
                    unset($_SESSION['pending_otp_user_id'], $_SESSION['otp_attempts']);
                    $this->redirect('home/index');
                }
            }
        }

        $this->view('auth/otp', ['error' => $error, 'success' => $success]);
    }

    public function googleAction(): void
    {
        if (isset($_SESSION['user_id'])) {
            $this->redirect('home/index');
        }

        $config = Container::get('config');
        $oauth = $config['google_oauth'] ?? [];

        $clientId = (string)($oauth['client_id'] ?? '');
        $redirectUri = (string)($oauth['redirect_uri'] ?? '');

        if ($clientId === '' || $redirectUri === '') {
            http_response_code(500);
            echo 'Google OAuth is not configured. Please set google_oauth.client_id and google_oauth.redirect_uri.';
            return;
        }

        $state = bin2hex(random_bytes(16));
        $_SESSION['google_oauth_state'] = $state;

        $params = [
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'state' => $state,
            'access_type' => 'online',
            'include_granted_scopes' => 'true',
            'prompt' => 'select_account',
        ];

        $url = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);
        header('Location: ' . $url);
        exit;
    }

    public function googlecallbackAction(): void
    {
        if (isset($_SESSION['user_id'])) {
            $this->redirect('home/index');
        }

        $config = Container::get('config');
        $oauth = $config['google_oauth'] ?? [];

        $clientId = (string)($oauth['client_id'] ?? '');
        $clientSecret = (string)($oauth['client_secret'] ?? '');
        $redirectUri = (string)($oauth['redirect_uri'] ?? '');

        $error = '';
        $success = '';

        $code = trim((string)($_GET['code'] ?? ''));
        $state = trim((string)($_GET['state'] ?? ''));
        $expectedState = (string)($_SESSION['google_oauth_state'] ?? '');
        unset($_SESSION['google_oauth_state']);

        if ($clientId === '' || $clientSecret === '' || $redirectUri === '') {
            $error = 'Google OAuth is not configured.';
            $this->view('auth/login', ['error' => $error, 'success' => $success]);
            return;
        }

        if ($code === '') {
            $error = 'Google login failed: missing code.';
            $this->view('auth/login', ['error' => $error, 'success' => $success]);
            return;
        }

        if ($expectedState === '' || !hash_equals($expectedState, $state)) {
            $error = 'Google login failed: invalid state.';
            $this->view('auth/login', ['error' => $error, 'success' => $success]);
            return;
        }

        $tokenResponse = $this->postFormJson('https://oauth2.googleapis.com/token', [
            'code' => $code,
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'redirect_uri' => $redirectUri,
            'grant_type' => 'authorization_code',
        ]);

        if (!$tokenResponse || empty($tokenResponse['access_token'])) {
            $error = 'Google login failed: unable to get access token.';
            $this->view('auth/login', ['error' => $error, 'success' => $success]);
            return;
        }

        $accessToken = (string)$tokenResponse['access_token'];
        $userInfo = $this->getJson('https://www.googleapis.com/oauth2/v3/userinfo', [
            'Authorization: Bearer ' . $accessToken,
        ]);

        $email = trim((string)($userInfo['email'] ?? ''));
        $name = trim((string)($userInfo['name'] ?? ''));
        $googleEmailVerified = (bool)($userInfo['email_verified'] ?? false);

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Google login failed: email not available.';
            $this->view('auth/login', ['error' => $error, 'success' => $success]);
            return;
        }

        if ($name === '') {
            $name = $email;
        }

        $userModel = new User();
        $user = $userModel->findByEmail($email);

        if (!$user) {
            [$oauthFirst, $oauthLast] = User::splitDisplayName($name);
            if ($googleEmailVerified) {
                $userId = $userModel->createOauthVerified($name, $email, $oauthFirst, $oauthLast);
            } else {
                $userId = $userModel->createOauthUnverified($name, $email, $oauthFirst, $oauthLast);
            }
        } else {
            $userId = (int)$user['id'];
            if ($googleEmailVerified && (int)$user['is_verified'] !== 1) {
                $userModel->setVerified($userId);
            }
        }

        $token = bin2hex(random_bytes(32));
        $verificationModel = new EmailVerification();
        $verificationModel->deleteByUserId($userId);

        // If Google did not verify the email, send verification link without blocking login
        if (!$googleEmailVerified) {
            $verificationModel->create($userId, $token);
            $baseUrl = rtrim((string)($config['app']['base_url'] ?? ''), '/');
            $verifyLink = $baseUrl . '/index.php?r=auth/verify&token=' . urlencode($token);
            $mailBody = '<p>Click the link below to verify your email:</p>' .
                '<p><a href="' . htmlspecialchars($verifyLink) . '">Verify Email</a></p>';
            try {
                Mailer::send($email, 'Verify your email', $mailBody);
            } catch (\Throwable $e) {
                // Silently ignore mail failure during OAuth
            }
        }

        // Prevent session fixation
        session_regenerate_id(true);

        $_SESSION['user_id'] = $userId;
        $this->redirect('home/index');
    }

    private function postFormJson(string $url, array $fields): ?array
    {
        $payload = http_build_query($fields);

        // Try cURL first with strict timeouts & SSL tolerance for free hosting
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => $payload,
                CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
                CURLOPT_CONNECTTIMEOUT => 8,
                CURLOPT_TIMEOUT        => 12,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => 0,
                CURLOPT_USERAGENT      => 'NutrifyApp/1.0',
            ]);
            $response = curl_exec($ch);
            $status   = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($response !== false && $status >= 200 && $status < 400) {
                $decoded = json_decode((string)$response, true);
                if (is_array($decoded)) {
                    return $decoded;
                }
            }
        }

        // Fallback to file_get_contents with stream context
        $opts = [
            'http' => [
                'method'  => 'POST',
                'header'  => "Content-Type: application/x-www-form-urlencoded\r\nUser-Agent: NutrifyApp/1.0\r\n",
                'content' => $payload,
                'timeout' => 12,
                'ignore_errors' => true,
            ],
            'ssl' => [
                'verify_peer'      => false,
                'verify_peer_name' => false,
            ],
        ];
        $ctx = stream_context_create($opts);
        $streamRes = @file_get_contents($url, false, $ctx);
        if ($streamRes !== false) {
            $decoded = json_decode($streamRes, true);
            return is_array($decoded) ? $decoded : null;
        }

        return null;
    }

    private function getJson(string $url, array $headers = []): ?array
    {
        // Try cURL first
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            $opts = [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CONNECTTIMEOUT => 8,
                CURLOPT_TIMEOUT        => 12,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => 0,
                CURLOPT_USERAGENT      => 'NutrifyApp/1.0',
            ];
            if (!empty($headers)) {
                $opts[CURLOPT_HTTPHEADER] = $headers;
            }
            curl_setopt_array($ch, $opts);
            $response = curl_exec($ch);
            $status   = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($response !== false && $status >= 200 && $status < 400) {
                $decoded = json_decode((string)$response, true);
                if (is_array($decoded)) {
                    return $decoded;
                }
            }
        }

        // Fallback to file_get_contents
        $headerStr = "User-Agent: NutrifyApp/1.0\r\n";
        if (!empty($headers)) {
            $headerStr .= implode("\r\n", $headers) . "\r\n";
        }
        $streamOpts = [
            'http' => [
                'method'        => 'GET',
                'header'        => $headerStr,
                'timeout'       => 12,
                'ignore_errors' => true,
            ],
            'ssl' => [
                'verify_peer'      => false,
                'verify_peer_name' => false,
            ],
        ];
        $ctx = stream_context_create($streamOpts);
        $streamRes = @file_get_contents($url, false, $ctx);
        if ($streamRes !== false) {
            $decoded = json_decode($streamRes, true);
            return is_array($decoded) ? $decoded : null;
        }

        return null;
    }
}
