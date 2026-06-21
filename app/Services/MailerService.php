<?php

declare(strict_types=1);

namespace App\Services;

final class MailerService
{
    public function send(string $to, string $subject, string $body, array $attachments = []): bool
    {
        $to = filter_var($to, FILTER_VALIDATE_EMAIL) ? $to : '';
        if ($to === '') {
            return false;
        }

        $subject = $this->headerText($subject);
        $mail = $this->settings();
        $headers = [
            'From: ' . $this->mailbox($mail['from_email'] ?? '', $mail['from_name'] ?? ''),
            'MIME-Version: 1.0',
        ];

        if ($attachments === []) {
            $headers[] = 'Content-Type: text/plain; charset=UTF-8';
            if (($mail['transport'] ?? 'mail') === 'smtp' && ($mail['host'] ?? '') !== '') {
                return $this->smtp($mail, $to, $subject, $body, $headers);
            }
            return mail($to, $subject, $body, implode("\r\n", $headers));
        }

        $boundary = 'lf_' . bin2hex(random_bytes(12));
        $headers[] = 'Content-Type: multipart/mixed; boundary="' . $boundary . '"';
        $message = "--{$boundary}\r\nContent-Type: text/plain; charset=UTF-8\r\n\r\n{$body}\r\n";

        foreach ($attachments as $attachment) {
            $message .= "--{$boundary}\r\n";
            $message .= 'Content-Type: ' . ($attachment['mime'] ?? 'application/octet-stream') . '; name="' . addslashes($attachment['name']) . '"' . "\r\n";
            $message .= "Content-Transfer-Encoding: base64\r\n";
            $message .= 'Content-Disposition: attachment; filename="' . addslashes($attachment['name']) . '"' . "\r\n\r\n";
            $message .= chunk_split(base64_encode($attachment['content'])) . "\r\n";
        }

        $message .= "--{$boundary}--";
        if (($mail['transport'] ?? 'mail') === 'smtp' && ($mail['host'] ?? '') !== '') {
            return $this->smtp($mail, $to, $subject, $message, $headers);
        }

        return mail($to, $subject, $message, implode("\r\n", $headers));
    }

    public function template(string $key, array $vars): array
    {
        $row = app()->db()->fetch('SELECT subject, body FROM email_templates WHERE template_key = ?', [$key]);
        $subject = $row['subject'] ?? '';
        $body = $row['body'] ?? '';

        foreach ($vars as $name => $value) {
            $subject = str_replace('{{' . $name . '}}', (string) $value, $subject);
            $body = str_replace('{{' . $name . '}}', (string) $value, $body);
        }

        return [$subject, $body];
    }

    private function mailbox(string $email, string $name): string
    {
        $email = filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : 'no-reply@example.com';
        $name = $this->headerText($name);

        return $name !== '' ? sprintf('"%s" <%s>', addslashes($name), $email) : $email;
    }

    private function headerText(string $value): string
    {
        return trim(preg_replace('/[\r\n]+/', ' ', $value));
    }

    private function settings(): array
    {
        if (!app()->isInstalled()) {
            return config('mail', []);
        }

        $settings = new SettingsService();
        return [
            'transport' => $settings->get('mail_transport', config('mail.transport', 'mail')),
            'host' => $settings->get('mail_host', config('mail.host', '')),
            'port' => (int) $settings->get('mail_port', (string) config('mail.port', 587)),
            'username' => $settings->get('mail_username', config('mail.username', '')),
            'password' => $settings->get('mail_password', config('mail.password', '')),
            'encryption' => $settings->get('mail_encryption', config('mail.encryption', 'tls')),
            'from_email' => $settings->get('mail_from_email', config('mail.from_email', '')),
            'from_name' => $settings->get('mail_from_name', config('mail.from_name', '')),
        ];
    }

    private function smtp(array $mail, string $to, string $subject, string $body, array $headers): bool
    {
        $host = (string) $mail['host'];
        $port = (int) ($mail['port'] ?? 587);
        $scheme = ($mail['encryption'] ?? '') === 'ssl' ? 'ssl://' : '';
        $socket = @stream_socket_client($scheme . $host . ':' . $port, $errno, $error, 15);
        if (!$socket) {
            return false;
        }

        $read = static function () use ($socket): string {
            $response = '';
            while (($line = fgets($socket, 515)) !== false) {
                $response .= $line;
                if (strlen($line) >= 4 && $line[3] === ' ') {
                    break;
                }
            }
            return $response;
        };
        $write = static fn (string $line) => fwrite($socket, $line . "\r\n");
        $read();
        $write('EHLO ' . ($_SERVER['SERVER_NAME'] ?? 'localhost'));
        $read();

        if (($mail['encryption'] ?? '') === 'tls') {
            $write('STARTTLS');
            $read();
            if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                fclose($socket);
                return false;
            }
            $write('EHLO ' . ($_SERVER['SERVER_NAME'] ?? 'localhost'));
            $read();
        }

        if (($mail['username'] ?? '') !== '') {
            $write('AUTH LOGIN');
            $read();
            $write(base64_encode((string) $mail['username']));
            $read();
            $write(base64_encode((string) ($mail['password'] ?? '')));
            $read();
        }

        $from = filter_var((string) ($mail['from_email'] ?? ''), FILTER_VALIDATE_EMAIL) ?: 'no-reply@example.com';
        $write('MAIL FROM:<' . $from . '>');
        $read();
        $write('RCPT TO:<' . $to . '>');
        $read();
        $write('DATA');
        $read();
        $write('To: <' . $to . '>');
        $write('Subject: ' . $subject);
        foreach ($headers as $header) {
            $write($header);
        }
        $write('');
        fwrite($socket, str_replace("\n.", "\n..", $body) . "\r\n.\r\n");
        $read();
        $write('QUIT');
        fclose($socket);

        return true;
    }
}
