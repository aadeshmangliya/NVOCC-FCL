<?php
class MailConfig
{
    public static function send($to, $subject, $message, $isHtml = true)
    {
        // Validate email
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        $fromName = env('MAIL_FROM_NAME', 'Indus Magna Shipping');
        $fromEmail = env('MAIL_FROM_ADDRESS', 'noreply@indusmagnashipping.com');

        try {
            $mailDriver = env('MAIL_DRIVER', 'smtp');
            $mailHost = env('MAIL_HOST', '');
            $mailPort = env('MAIL_PORT', 587);
            if ($mailDriver === 'smtp' && !empty($mailHost)) {
                $result = self::sendViaSMTP($to, $subject, $message, $fromEmail, $fromName, $isHtml, $mailHost, $mailPort);
            } else {
                $result = self::sendViaLocalMail($to, $subject, $message, $fromEmail, $fromName, $isHtml);
            }
            return $result;
        } catch (Exception $e) {
            return false;
        }
    }

    private static function sendViaLocalMail($to, $subject, $message, $fromEmail, $fromName, $isHtml)
    {
        $headers = "MIME-Version: 1.0\r\n";
        if ($isHtml) {
            $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        } else {
            $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
        }
        $headers .= "From: " . $fromName . " <" . $fromEmail . ">\r\n";
        $headers .= "Reply-To: " . $fromEmail . "\r\n";
        // Anti-spam headers
        $headers .= "Return-Path: <" . $fromEmail . ">\r\n";
        $headers .= "X-Mailer: Indus Magna Shipping\r\n";
        $headers .= "X-Priority: 3\r\n";
        $headers .= "Importance: Normal\r\n";
        $headers .= "X-MSMail-Priority: Normal\r\n";

        $result = @mail($to, $subject, $message, $headers);
        return $result;
    }

    private static function sendViaSMTP($to, $subject, $message, $fromEmail, $fromName, $isHtml, $smtpHost, $smtpPort)
    {
        $smtpUsername = env('MAIL_USERNAME', '');
        $smtpPassword = env('MAIL_PASSWORD', '');
        $mailEncryption = env('MAIL_ENCRYPTION', 'tls');

        // Use stream_socket_client for better TLS support
        $errorNo = 0;
        $errorStr = '';


        $socket = @stream_socket_client(
            "tcp://$smtpHost:$smtpPort",
            $errorNo,
            $errorStr,
            10,
            STREAM_CLIENT_CONNECT
        );

        if (!$socket) {
            return false;
        }

        stream_set_timeout($socket, 10);

        try {
            // Read initial greeting
            $response = fgets($socket, 1024);
            if (strpos($response, '220') === false) {
                fclose($socket);
                return false;
            }

            // Send EHLO
            fwrite($socket, "EHLO localhost\r\n");
            $response = fgets($socket, 1024);
            while ($response && strlen($response) > 3 && $response[3] === '-') {
                $response = fgets($socket, 1024);
            }

            if (strpos($response, '250') === false) {
                fclose($socket);
                return false;
            }

            // STARTTLS
            if ($mailEncryption === 'tls' || $mailEncryption === 'TLS') {
                fwrite($socket, "STARTTLS\r\n");
                $response = fgets($socket, 1024);

                if (strpos($response, '220') === false) {
                } else {
                    if (!@stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                        fclose($socket);
                        return false;
                    }
                    // Send EHLO again after TLS
                    fwrite($socket, "EHLO localhost\r\n");
                    $response = fgets($socket, 1024);
                    while ($response && strlen($response) > 3 && $response[3] === '-') {
                        $response = fgets($socket, 1024);
                    }
                }
            }

            // AUTH LOGIN
            if (!empty($smtpUsername) && !empty($smtpPassword)) {
                fwrite($socket, "AUTH LOGIN\r\n");
                $response = fgets($socket, 1024);

                if (strpos($response, '334') === false) {
                    fclose($socket);
                    return false;
                }

                // Send username
                fwrite($socket, base64_encode($smtpUsername) . "\r\n");
                $response = fgets($socket, 1024);

                // Send password
                fwrite($socket, base64_encode($smtpPassword) . "\r\n");
                $response = fgets($socket, 1024);

                if (strpos($response, '235') === false && strpos($response, '250') === false) {
                    fclose($socket);
                    return false;
                }
            }

            // Mail from
            fwrite($socket, "MAIL FROM:<$fromEmail>\r\n");
            $response = fgets($socket, 1024);
            if (strpos($response, '250') === false) {
                fclose($socket);
                return false;
            }

            // Recipient
            fwrite($socket, "RCPT TO:<$to>\r\n");
            $response = fgets($socket, 1024);
            if (strpos($response, '250') === false) {
                fclose($socket);
                return false;
            }

            // Data
            fwrite($socket, "DATA\r\n");
            $response = fgets($socket, 1024);
            if (strpos($response, '354') === false) {
                fclose($socket);
                return false;
            }

            // Compose email with anti-spam headers
            $headers = "From: $fromName <$fromEmail>\r\n";
            $headers .= "To: $to\r\n";
            $headers .= "Subject: $subject\r\n";
            $headers .= "MIME-Version: 1.0\r\n";
            if ($isHtml) {
                $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
            } else {
                $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
            }
            // Anti-spam headers
            $headers .= "Return-Path: <$fromEmail>\r\n";
            $headers .= "Reply-To: $fromEmail\r\n";
            $headers .= "X-Mailer: Indus Magna Shipping\r\n";
            $headers .= "X-Priority: 3\r\n";
            $headers .= "Importance: Normal\r\n";
            $headers .= "X-MSMail-Priority: Normal\r\n";

            // Send email
            fwrite($socket, $headers . "\r\n");
            fwrite($socket, $message . "\r\n");
            fwrite($socket, ".\r\n");

            $response = fgets($socket, 1024);
            if (strpos($response, '250') === false) {
                fclose($socket);
                return false;
            }
            // Quit
            fwrite($socket, "QUIT\r\n");
            fclose($socket);
            return true;
        } catch (Exception $e) {
            @fclose($socket);
            return false;
        }
    }
}
