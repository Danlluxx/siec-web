<?php

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

require_once __DIR__ . '/PHPMailer/src/Exception.php';
require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/src/SMTP.php';

function siteRawPostValue(string $key): string
{
    return trim((string)($_POST[$key] ?? ''));
}

function sitePostValue(string $key): string
{
    return htmlspecialchars(siteRawPostValue($key), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function siteMailDebugLog(string $message): void
{
    $line = '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL;
    @file_put_contents(__DIR__ . '/mail_debug.log', $line, FILE_APPEND);
}

function siteEnvValues(): array
{
    static $values = null;

    if (is_array($values)) {
        return $values;
    }

    $values = [];
    $envPath = __DIR__ . '/.env';

    if (!is_file($envPath)) {
        return $values;
    }

    $lines = @file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (!is_array($lines)) {
        return $values;
    }

    foreach ($lines as $line) {
        $line = trim($line);

        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }

        if (str_starts_with($line, 'export ')) {
            $line = trim(substr($line, 7));
        }

        $delimiterPos = strpos($line, '=');
        if ($delimiterPos === false) {
            continue;
        }

        $key = trim(substr($line, 0, $delimiterPos));
        $value = trim(substr($line, $delimiterPos + 1));

        if ($key === '') {
            continue;
        }

        if (strlen($value) >= 2) {
            $firstChar = $value[0];
            $lastChar = $value[strlen($value) - 1];

            if (($firstChar === '"' && $lastChar === '"') || ($firstChar === "'" && $lastChar === "'")) {
                $value = substr($value, 1, -1);
            }
        }

        $values[$key] = $value;
    }

    return $values;
}

function siteEnvValue(string $key, string $default = ''): string
{
    $values = siteEnvValues();

    if (array_key_exists($key, $values)) {
        return (string)$values[$key];
    }

    return $default;
}

function siteEnvBool(string $key, bool $default = false): bool
{
    $rawValue = strtolower(trim(siteEnvValue($key, $default ? 'true' : 'false')));

    if (in_array($rawValue, ['1', 'true', 'yes', 'on'], true)) {
        return true;
    }

    if (in_array($rawValue, ['0', 'false', 'no', 'off'], true)) {
        return false;
    }

    return $default;
}

function siteValidateEmailOrDefault(string $value, string $default): string
{
    $value = trim($value);

    if (filter_var($value, FILTER_VALIDATE_EMAIL)) {
        return strtolower($value);
    }

    return $default;
}

function siteRequireEnvValue(string $key, string $errorMessage): string
{
    $value = trim(siteEnvValue($key));

    if ($value === '') {
        throw new RuntimeException($errorMessage);
    }

    return $value;
}

function siteRequireEnvInt(string $key, string $errorMessage): int
{
    $value = (int)trim(siteEnvValue($key));

    if ($value <= 0) {
        throw new RuntimeException($errorMessage);
    }

    return $value;
}

function siteMailRecipient(): string
{
    return siteValidateEmailOrDefault(siteEnvValue('MAIL_TO_ADDRESS', 'fanatbmw04@mail.ru'), 'fanatbmw04@mail.ru');
}

function siteGenerateQuestionnaireId(string $prefix): string
{
    $normalizedPrefix = trim($prefix);
    $normalizedPrefix = preg_replace('/[^\p{L}\p{N}]+/u', '', $normalizedPrefix) ?: 'ID';

    try {
        $number = random_int(100000, 999999);
    } catch (Throwable $e) {
        $number = mt_rand(100000, 999999);
    }

    return $normalizedPrefix . '-' . $number;
}

function siteSendMail(string $subject, string $htmlBody, string $replyToEmail = '', string $replyToName = '', array $attachments = []): array
{
    $mail = new PHPMailer(true);

    try {
        $fromAddress = siteValidateEmailOrDefault(siteEnvValue('MAIL_FROM_ADDRESS', 'siec@siec-brn.ru'), 'siec@siec-brn.ru');
        $fromName = trim(siteEnvValue('MAIL_FROM_NAME', 'Сибирская энергетическая компания'));

        if (!filter_var($fromAddress, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Некорректный MAIL_FROM_ADDRESS в .env.');
        }

        $attachmentNames = [];
        foreach ($attachments as $attachment) {
            $attachmentNames[] = (string)($attachment['name'] ?? basename((string)($attachment['path'] ?? '')));
        }

        $plainBody = preg_replace('/<br\s*\/?>/i', PHP_EOL, $htmlBody);
        $plainBody = html_entity_decode(strip_tags((string)$plainBody), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $plainBody = trim((string)preg_replace("/\n{3,}/", "\n\n", $plainBody));

        $applyCommonConfig = static function (PHPMailer $message) use (
            $fromAddress,
            $fromName,
            $replyToEmail,
            $replyToName,
            $attachments,
            $subject,
            $htmlBody,
            $plainBody
        ): void {
            $message->CharSet = 'UTF-8';
            $message->Encoding = PHPMailer::ENCODING_BASE64;
            $message->isHTML(true);
            $message->setFrom($fromAddress, $fromName);
            $message->Sender = $fromAddress;
            $message->addAddress(siteMailRecipient());

            if ($replyToEmail !== '' && filter_var($replyToEmail, FILTER_VALIDATE_EMAIL)) {
                $message->addReplyTo($replyToEmail, $replyToName);
            }

            foreach ($attachments as $attachment) {
                $path = (string)($attachment['path'] ?? '');
                $name = (string)($attachment['name'] ?? basename($path));
                $mime = (string)($attachment['mime'] ?? 'application/octet-stream');

                if ($path === '' || !is_file($path)) {
                    throw new RuntimeException('Файл вложения не найден.');
                }

                $message->addAttachment($path, $name, PHPMailer::ENCODING_BASE64, $mime);
            }

            $message->Subject = $subject;
            $message->Body = $htmlBody;
            $message->AltBody = $plainBody !== '' ? $plainBody : 'Письмо с сайта.';
        };

        $applySmtpTransport = static function (
            PHPMailer $message,
            string $host,
            int $port,
            string $username,
            string $password,
            bool $auth,
            string $encryption
        ): void {
            $message->isSMTP();
            $message->Host = $host;
            $message->Port = $port;
            $message->Username = $username;
            $message->Password = $password;
            $message->SMTPAuth = $auth;
            $message->Timeout = 10;
            $message->SMTPKeepAlive = false;
            $message->SMTPOptions = [
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true,
                ],
            ];

            if ($encryption === 'ssl' || $encryption === 'smtps') {
                $message->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                return;
            }

            if ($encryption === 'tls' || $encryption === 'starttls') {
                $message->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                return;
            }

            if ($encryption === '' || $encryption === 'none') {
                $message->SMTPSecure = '';
                $message->SMTPAutoTLS = false;
                return;
            }

            throw new RuntimeException('Некорректный MAIL_ENCRYPTION в .env.');
        };

        $smtpHost = trim(siteEnvValue('MAIL_HOST'));
        $smtpPortRaw = trim(siteEnvValue('MAIL_PORT'));
        $smtpUsername = trim(siteEnvValue('MAIL_USERNAME'));
        $smtpPassword = trim(siteEnvValue('MAIL_PASSWORD'));
        $smtpEncryption = strtolower(trim(siteEnvValue('MAIL_ENCRYPTION', 'ssl')));
        $smtpAuth = siteEnvBool('MAIL_SMTP_AUTH', true);

        $smtpConfigured = $smtpHost !== '' && $smtpPortRaw !== '' && $smtpUsername !== '' && $smtpPassword !== '';
        $applyCommonConfig($mail);

        if ($smtpConfigured) {
            $smtpPort = (int)$smtpPortRaw;
            if ($smtpPort <= 0) {
                throw new RuntimeException('Некорректный MAIL_PORT в .env.');
            }

            $transport = 'smtp:' . $smtpHost;
            $applySmtpTransport($mail, $smtpHost, $smtpPort, $smtpUsername, $smtpPassword, (bool)$smtpAuth, $smtpEncryption);
        } else {
            $transport = 'php-mail';
        }

        $mail->send();
        siteMailDebugLog(
            'OK'
            . ' | to=' . siteMailRecipient()
            . ' | from=' . $fromAddress
            . ' | transport=' . $transport
            . ' | subject=' . $subject
            . ' | reply_to=' . ($replyToEmail !== '' ? $replyToEmail : '-')
            . ' | attachments=' . ($attachmentNames !== [] ? implode(', ', $attachmentNames) : '-')
        );

        return ['ok' => true, 'error' => ''];
    } catch (Throwable $e) {
        $primaryError = $mail->ErrorInfo ?: $e->getMessage();

        if (isset($transport) && str_starts_with((string)$transport, 'smtp:')) {
            try {
                $smtpFallbacks = [];

                if (
                    isset($smtpHost, $smtpPort)
                    && $smtpHost === 'smtp.spaceweb.ru'
                    && (int)$smtpPort === 465
                ) {
                    $smtpFallbacks[] = ['port' => 2525, 'encryption' => 'none', 'label' => 'smtp:' . $smtpHost . ':2525-fallback'];
                }

                foreach ($smtpFallbacks as $smtpFallback) {
                    try {
                        $altMail = new PHPMailer(true);
                        $applyCommonConfig($altMail);
                        $applySmtpTransport(
                            $altMail,
                            $smtpHost,
                            (int)$smtpFallback['port'],
                            $smtpUsername,
                            $smtpPassword,
                            (bool)$smtpAuth,
                            (string)$smtpFallback['encryption']
                        );
                        $altMail->send();

                        siteMailDebugLog(
                            'OK'
                            . ' | to=' . siteMailRecipient()
                            . ' | from=' . $fromAddress
                            . ' | transport=' . $smtpFallback['label']
                            . ' | subject=' . $subject
                            . ' | reply_to=' . ($replyToEmail !== '' ? $replyToEmail : '-')
                            . ' | attachments=' . (!empty($attachmentNames) ? implode(', ', $attachmentNames) : '-')
                            . ' | smtp_error=' . $primaryError
                        );

                        return ['ok' => true, 'error' => ''];
                    } catch (Throwable $altError) {
                        $primaryError .= ' | ' . $smtpFallback['label'] . '=' . ($altMail->ErrorInfo ?: $altError->getMessage());
                    }
                }

                $fallbackMail = new PHPMailer(true);
                $applyCommonConfig($fallbackMail);
                $fallbackMail->send();

                siteMailDebugLog(
                    'OK'
                    . ' | to=' . siteMailRecipient()
                    . ' | from=' . $fromAddress
                    . ' | transport=php-mail-fallback'
                    . ' | subject=' . $subject
                    . ' | reply_to=' . ($replyToEmail !== '' ? $replyToEmail : '-')
                    . ' | attachments=' . (!empty($attachmentNames) ? implode(', ', $attachmentNames) : '-')
                    . ' | smtp_error=' . $primaryError
                );

                return ['ok' => true, 'error' => ''];
            } catch (Throwable $fallbackError) {
                $combinedError = $primaryError . ' | fallback=' . ($fallbackMail->ErrorInfo ?: $fallbackError->getMessage());
                error_log('Mail send failed: ' . $combinedError);
                siteMailDebugLog(
                    'FAIL'
                    . ' | to=' . siteMailRecipient()
                    . ' | from=' . (isset($fromAddress) ? $fromAddress : '-')
                    . ' | transport=' . (isset($transport) ? $transport : '-')
                    . ' | subject=' . $subject
                    . ' | reply_to=' . ($replyToEmail !== '' ? $replyToEmail : '-')
                    . ' | error=' . $combinedError
                );

                return ['ok' => false, 'error' => $fallbackMail->ErrorInfo ?: $primaryError];
            }
        }

        error_log('Mail send failed: ' . $primaryError);
        siteMailDebugLog(
            'FAIL'
            . ' | to=' . siteMailRecipient()
            . ' | from=' . (isset($fromAddress) ? $fromAddress : '-')
            . ' | transport=' . (isset($transport) ? $transport : '-')
            . ' | subject=' . $subject
            . ' | reply_to=' . ($replyToEmail !== '' ? $replyToEmail : '-')
            . ' | error=' . $primaryError
        );

        return ['ok' => false, 'error' => $primaryError];
    } finally {
        foreach ($attachments as $attachment) {
            $path = (string)($attachment['path'] ?? '');
            $deleteAfterSend = !empty($attachment['delete_after_send']);

            if ($deleteAfterSend && $path !== '' && is_file($path)) {
                @unlink($path);
            }
        }
    }
}
