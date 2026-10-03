<?php

declare(strict_types=1);

namespace App\Core;

use PHPMailer\PHPMailer\PHPMailer;
final class Mailer
{
    private static string $lastError = '';

    public static function send(string $toEmail, string $subject, string $htmlBody): bool
    {
        self::$lastError = '';
        $config = Container::get('config');
        $mail = $config['mail'] ?? [];

        $baseUrl = (string)($config['app']['base_url'] ?? '');
        $host = (string)($_SERVER['HTTP_HOST'] ?? ($_SERVER['SERVER_NAME'] ?? ''));
        $isLocal = (
            ($baseUrl !== '' && (stripos($baseUrl, 'localhost') !== false || stripos($baseUrl, '127.0.0.1') !== false))
            || ($host !== '' && (stripos($host, 'localhost') !== false || stripos($host, '127.0.0.1') !== false))
        );

        $fromEmail = $mail['from_email'] ?? 'no-reply@example.com';
        $fromName = $mail['from_name'] ?? 'Auth MVC';

        $driver = (string)($mail['driver'] ?? 'phpmail');
        self::log('Send attempt via ' . $driver . ' to=' . $toEmail . ' subject=' . $subject);
        if ($driver === 'smtp' && class_exists(PHPMailer::class)) {
            $smtp = $mail['smtp'] ?? [];

            $host = (string)($smtp['host'] ?? '');
            $port = (int)($smtp['port'] ?? 587);
            $username = (string)($smtp['username'] ?? '');
            $password = (string)($smtp['password'] ?? '');
            $encryption = (string)($smtp['encryption'] ?? 'tls');
            $debug = (int)($smtp['debug'] ?? 0);

            $phpMailer = new PHPMailer(true);
            $phpMailer->isSMTP();
            $phpMailer->Timeout = 8;
            $phpMailer->SMTPAutoTLS = true;
            if ($debug > 0) {
                $phpMailer->SMTPDebug = $debug;
                $phpMailer->Debugoutput = function (string $str, int $level) {
                    self::log('SMTP DEBUG [' . $level . ']: ' . $str);
                };
            }
            $phpMailer->Host = $host;
            $phpMailer->SMTPAuth = true;
            $phpMailer->Username = $username;
            $phpMailer->Password = $password;
            $phpMailer->Port = $port;
            if ($encryption !== '') {
                $enc = strtolower($encryption);
                if ($enc === 'tls' || $enc === 'starttls') {
                    $phpMailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                } elseif ($enc === 'ssl') {
                    $phpMailer->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                } else {
                    $phpMailer->SMTPSecure = $encryption;
                }
            }

            if ($isLocal) {
                $phpMailer->SMTPOptions = [
                    'ssl' => [
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                        'allow_self_signed' => true,
                    ],
                ];
            }

            $phpMailer->setFrom($fromEmail, $fromName);
            $phpMailer->addAddress($toEmail);
            $phpMailer->isHTML(true);
            $phpMailer->Subject = $subject;
            $phpMailer->Body = $htmlBody;

            try {
                return $phpMailer->send();
            } catch (\Throwable $e) {
                self::$lastError = $e->getMessage();
                self::log('SMTP send failed: ' . self::$lastError);
                return false;
            }
        }

        $headers = [];
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Content-type:text/html;charset=UTF-8';
        $headers[] = 'From: ' . $fromName . ' <' . $fromEmail . '>';

        $ok = mail($toEmail, $subject, $htmlBody, implode("\r\n", $headers));
        if (!$ok) {
            self::$lastError = 'PHP mail() returned false.';
            self::log('mail() send failed');
        }
        return $ok;
    }

    public static function lastError(): string
    {
        return self::$lastError;
    }

    private static function log(string $message): void
    {
        $dir = BASE_PATH . '/app/logs';
        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }

        $file = $dir . '/mail.log';
        $line = '[' . date('Y-m-d H:i:s') . '] ' . $message . "\n";
        @file_put_contents($file, $line, FILE_APPEND);
    }
}
