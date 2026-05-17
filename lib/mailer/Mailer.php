<?php
namespace Stopbot\Mailer;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception as MailerException;

/**
 * Stopbot Mailer - PHPMailer wrapper dengan fitur:
 * threading, retry, list email/subject/from, attachment, encoding
 */
class Mailer
{
    private array $config;

    /** Index berputar untuk from_list, subject_list */
    private int $fromIndex    = 0;
    private int $subjectIndex = 0;

    /** Log buffer */
    private array $sendLog = [];

    public function __construct(string $configPath = '')
    {
        if ($configPath === '') {
            $configPath = dirname(__DIR__, 2) . '/mail_config.php';
        }

        if (!file_exists($configPath)) {
            throw new \RuntimeException("Config file not found: {$configPath}");
        }

        $this->config = require $configPath;
        $this->normalizeConfig();
    }

    /* ═══════════════════════════════════════════════════════════
     |  PUBLIC API
     ═══════════════════════════════════════════════════════════ */

    /**
     * Kirim ke semua penerima di to_list secara berurutan (batch).
     * Subject & From di-rotate tiap email.
     *
     * @param  array $extraData  Variabel tambahan untuk body template per email
     * @return array             Hasil pengiriman ['success'=>[], 'failed'=>[]]
     */
    public function sendBatch(array $extraData = []): array
    {
        $results = ['success' => [], 'failed' => []];
        $toList  = $this->config['to_list'];

        if (empty($toList)) {
            $this->log('ERROR', 'to_list kosong, tidak ada yang dikirim.');
            return $results;
        }

        $delay = (int) ($this->config['batch']['delay_between_emails'] ?? 1);

        foreach ($toList as $index => $recipient) {
            $result = $this->sendToOne($recipient, $extraData);

            if ($result['success']) {
                $results['success'][] = $result;
                $this->log('OK', "Berhasil ke {$recipient['email']} | Subject: {$result['subject']} | From: {$result['from']}");
            } else {
                $results['failed'][] = $result;
                $this->log('FAIL', "Gagal ke {$recipient['email']} | {$result['error']}");
            }

            // Jeda antar email kecuali email terakhir
            if ($index < count($toList) - 1 && $delay > 0) {
                sleep($delay);
            }
        }

        $this->flushLog();
        return $results;
    }

    /**
     * Kirim ke satu penerima tertentu (dengan retry).
     *
     * @param  array  $recipient  ['email' => '', 'name' => '']
     * @param  array  $extraData  Variabel tambahan untuk body template
     * @return array
     */
    public function sendToOne(array $recipient, array $extraData = []): array
    {
        $from    = $this->nextFrom();
        $subject = $this->nextSubject();
        $body    = $this->buildBody($extraData);

        $maxAttempts = (int) ($this->config['retry']['max_attempts'] ?? 3);
        $delay       = (int) ($this->config['retry']['delay_seconds'] ?? 5);

        $lastError = '';

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                $mail = $this->buildMailer($from, $recipient, $subject, $body);
                $mail->send();

                return [
                    'success'   => true,
                    'recipient' => $recipient['email'],
                    'subject'   => $subject,
                    'from'      => "{$from['name']} <{$from['email']}>",
                    'attempt'   => $attempt,
                    'message_id'=> $mail->getLastMessageID(),
                ];
            } catch (MailerException $e) {
                $lastError = $e->getMessage();
                $this->log('RETRY', "Attempt {$attempt}/{$maxAttempts} ke {$recipient['email']} gagal: {$lastError}");

                if ($attempt < $maxAttempts && $delay > 0) {
                    sleep($delay);
                }
            }
        }

        return [
            'success'   => false,
            'recipient' => $recipient['email'],
            'subject'   => $subject,
            'from'      => "{$from['name']} <{$from['email']}>",
            'attempt'   => $maxAttempts,
            'error'     => $lastError,
        ];
    }

    /**
     * Kirim email reply (untuk threading).
     * $threadMessageId = Message-ID dari email yang di-reply.
     */
    public function sendReply(array $recipient, string $threadMessageId, string $subject = '', array $extraData = []): array
    {
        $originalConfig = $this->config['thread'];

        $this->config['thread']['enabled']    = true;
        $this->config['thread']['message_id'] = $threadMessageId;

        if ($subject !== '') {
            $this->config['thread']['thread_subject'] = $subject;
        }

        $result = $this->sendToOne($recipient, $extraData);

        // Restore
        $this->config['thread'] = $originalConfig;

        return $result;
    }

    /**
     * Akses langsung ke log pengiriman sesi ini.
     */
    public function getSendLog(): array
    {
        return $this->sendLog;
    }

    /**
     * Override daftar penerima secara runtime.
     */
    public function setToList(array $toList): self
    {
        $this->config['to_list'] = $toList;
        return $this;
    }

    /**
     * Override daftar subject secara runtime.
     */
    public function setSubjectList(array $subjects): self
    {
        $this->config['subject_list'] = $subjects;
        $this->subjectIndex = 0;
        return $this;
    }

    /**
     * Override body HTML secara runtime.
     */
    public function setBody(string $html, string $plain = ''): self
    {
        $this->config['body']['html']  = $html;
        $this->config['body']['plain'] = $plain;
        return $this;
    }

    /**
     * Tambah lampiran secara runtime.
     *
     * @param string $path     Path absolut ke file
     * @param string $name     Nama tampil (opsional)
     * @param string $encoding 'base64' | 'quoted-printable' | '8bit'
     * @param string $type     MIME type (opsional)
     * @param bool   $inline   true = embed sebagai inline image
     */
    public function addAttachment(string $path, string $name = '', string $encoding = 'base64', string $type = '', bool $inline = false): self
    {
        $this->config['attachments'][] = compact('path', 'name', 'encoding', 'type', 'inline');
        return $this;
    }

    /* ═══════════════════════════════════════════════════════════
     |  PRIVATE: BUILD PHPMAILER INSTANCE
     ═══════════════════════════════════════════════════════════ */

    private function buildMailer(array $from, array $recipient, string $subject, array $body): PHPMailer
    {
        $mail = new PHPMailer(true);

        // ── SMTP ─────────────────────────────────────
        $mail->isSMTP();
        $mail->Host       = $this->config['smtp']['host'];
        $mail->Port       = (int) $this->config['smtp']['port'];
        $mail->SMTPAuth   = (bool) $this->config['smtp']['auth'];
        $mail->Username   = $this->config['smtp']['username'];
        $mail->Password   = $this->config['smtp']['password'];
        $mail->SMTPDebug  = (int) $this->config['smtp']['debug'];
        $mail->Timeout    = (int) ($this->config['smtp']['timeout'] ?? 30);
        $mail->SMTPKeepAlive = (bool) ($this->config['batch']['keep_alive'] ?? true);

        $enc = strtolower($this->config['smtp']['encryption'] ?? 'tls');
        if ($enc === 'ssl') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        } elseif ($enc === 'tls') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        } else {
            $mail->SMTPSecure = '';
            $mail->SMTPAutoTLS = false;
        }

        // ── ENCODING ─────────────────────────────────
        $charset  = $this->config['encoding']['charset'] ?? 'UTF-8';
        $bodyEnc  = $this->resolveEncoding($this->config['encoding']['body_encoding'] ?? 'base64');
        $mail->CharSet  = $charset;
        $mail->Encoding = $bodyEnc;

        // ── FROM ─────────────────────────────────────
        $mail->setFrom($from['email'], $from['name']);

        // ── REPLY-TO ─────────────────────────────────
        foreach ($this->config['reply_to'] as $rt) {
            $mail->addReplyTo($rt['email'], $rt['name'] ?? '');
        }

        // ── TO ───────────────────────────────────────
        $mail->addAddress($recipient['email'], $recipient['name'] ?? '');

        // ── CC / BCC ─────────────────────────────────
        foreach ($this->config['cc_list'] as $cc) {
            $mail->addCC($cc['email'], $cc['name'] ?? '');
        }
        foreach ($this->config['bcc_list'] as $bcc) {
            $mail->addBCC($bcc['email'], $bcc['name'] ?? '');
        }

        // ── SUBJECT ──────────────────────────────────
        $mail->Subject = $subject;

        // ── BODY ─────────────────────────────────────
        $contentType = strtolower($this->config['encoding']['content_type'] ?? 'text/html');
        if ($contentType === 'text/html') {
            $mail->isHTML(true);
            $mail->Body    = $body['html'];
            $mail->AltBody = $body['plain'] ?: strip_tags($body['html']);
        } else {
            $mail->isHTML(false);
            $mail->Body = $body['plain'] ?: strip_tags($body['html']);
        }

        // ── THREADING ────────────────────────────────
        if (!empty($this->config['thread']['enabled']) && !empty($this->config['thread']['message_id'])) {
            $mid = $this->config['thread']['message_id'];
            $mail->addCustomHeader('In-Reply-To', $mid);
            $mail->addCustomHeader('References', $mid);

            if (!empty($this->config['thread']['thread_subject'])) {
                $ts = $this->config['thread']['thread_subject'];
                $mail->Subject = (str_starts_with(strtolower($ts), 're:') ? '' : 'Re: ') . $ts;
            }
        }

        // ── ATTACHMENTS ──────────────────────────────
        foreach ($this->config['attachments'] as $att) {
            if (empty($att['path']) || !file_exists($att['path'])) {
                $this->log('WARN', "Attachment tidak ditemukan: " . ($att['path'] ?? '(kosong)'));
                continue;
            }

            $attName     = $att['name']     ?: basename($att['path']);
            $attEncoding = $this->resolveEncoding($att['encoding'] ?? 'base64');
            $attType     = $att['type']     ?: $this->detectMime($att['path']);
            $attInline   = !empty($att['inline']);

            if ($attInline) {
                $cid = md5($att['path']);
                $mail->addEmbeddedImage($att['path'], $cid, $attName, $attEncoding, $attType);
            } else {
                $mail->addAttachment($att['path'], $attName, $attEncoding, $attType);
            }
        }

        return $mail;
    }

    /* ═══════════════════════════════════════════════════════════
     |  PRIVATE: HELPERS
     ═══════════════════════════════════════════════════════════ */

    /** Ambil from berikutnya dari from_list (round-robin, hanya yang enabled). */
    private function nextFrom(): array
    {
        $list = array_values(array_filter(
            $this->config['from_list'],
            fn($f) => !empty($f['enabled'])
        ));

        if (empty($list)) {
            throw new \RuntimeException('Tidak ada from_list yang aktif (enabled = true).');
        }

        $item = $list[$this->fromIndex % count($list)];
        $this->fromIndex++;
        return $item;
    }

    /** Ambil subject berikutnya dari subject_list (round-robin). */
    private function nextSubject(): string
    {
        $list = $this->config['subject_list'];

        if (empty($list)) {
            return '(No Subject)';
        }

        $subject = $list[$this->subjectIndex % count($list)];
        $this->subjectIndex++;
        return $subject;
    }

    /** Bangun body dari config (template / html / plain). */
    private function buildBody(array $extraData = []): array
    {
        $bodyCfg = $this->config['body'];
        $html    = $bodyCfg['html']  ?? '';
        $plain   = $bodyCfg['plain'] ?? '';

        // Pakai template file jika ada
        if (!empty($bodyCfg['template']) && file_exists($bodyCfg['template'])) {
            $html = file_get_contents($bodyCfg['template']);
        }

        // Replace variabel dari config
        $vars = array_merge($bodyCfg['variables'] ?? [], $extraData);
        foreach ($vars as $placeholder => $value) {
            $html  = str_replace($placeholder, $value, $html);
            $plain = str_replace($placeholder, $value, $plain);
        }

        return compact('html', 'plain');
    }

    /**
     * Normalisasi nama encoding ke konstanta PHPMailer.
     * PHPMailer menerima: 'base64', 'quoted-printable', '8bit', '7bit', 'binary'
     */
    private function resolveEncoding(string $enc): string
    {
        $map = [
            'base64'           => PHPMailer::ENCODING_BASE64,
            'quoted-printable' => PHPMailer::ENCODING_QUOTED_PRINTABLE,
            'qp'               => PHPMailer::ENCODING_QUOTED_PRINTABLE,
            '8bit'             => PHPMailer::ENCODING_8BIT,
            '7bit'             => PHPMailer::ENCODING_7BIT,
            'binary'           => PHPMailer::ENCODING_BINARY,
        ];
        return $map[strtolower($enc)] ?? PHPMailer::ENCODING_BASE64;
    }

    /** Auto-detect MIME type dari extension file. */
    private function detectMime(string $path): string
    {
        if (function_exists('mime_content_type')) {
            $mime = mime_content_type($path);
            if ($mime && $mime !== 'application/x-empty') {
                return $mime;
            }
        }

        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $map = [
            'pdf'  => 'application/pdf',
            'jpg'  => 'image/jpeg', 'jpeg' => 'image/jpeg',
            'png'  => 'image/png',  'gif'  => 'image/gif',
            'webp' => 'image/webp', 'svg'  => 'image/svg+xml',
            'doc'  => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls'  => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'zip'  => 'application/zip', 'rar' => 'application/x-rar-compressed',
            'txt'  => 'text/plain',      'csv' => 'text/csv',
            'html' => 'text/html',       'xml' => 'application/xml',
            'mp3'  => 'audio/mpeg',      'mp4' => 'video/mp4',
        ];

        return $map[$ext] ?? 'application/octet-stream';
    }

    /** Pastikan semua key konfigurasi ada. */
    private function normalizeConfig(): void
    {
        $defaults = [
            'from_list'    => [],
            'to_list'      => [],
            'cc_list'      => [],
            'bcc_list'     => [],
            'reply_to'     => [],
            'subject_list' => [],
            'attachments'  => [],
            'body'         => ['html' => '', 'plain' => '', 'template' => '', 'variables' => []],
            'encoding'     => ['charset' => 'UTF-8', 'body_encoding' => 'base64', 'content_type' => 'text/html'],
            'thread'       => ['enabled' => false, 'message_id' => '', 'thread_subject' => ''],
            'retry'        => ['max_attempts' => 3, 'delay_seconds' => 5],
            'batch'        => ['delay_between_emails' => 1, 'keep_alive' => true],
            'log'          => ['enabled' => true, 'path' => __DIR__ . '/logs/mailer.log', 'max_size' => 5242880],
        ];

        foreach ($defaults as $key => $default) {
            if (!isset($this->config[$key])) {
                $this->config[$key] = $default;
            }
        }
    }

    /** Tulis ke log buffer. */
    private function log(string $level, string $message): void
    {
        $entry = sprintf('[%s] [%s] %s', date('Y-m-d H:i:s'), $level, $message);
        $this->sendLog[] = $entry;
    }

    /** Tulis buffer log ke file. */
    private function flushLog(): void
    {
        if (empty($this->config['log']['enabled']) || empty($this->sendLog)) {
            return;
        }

        $logPath = $this->config['log']['path'];
        $logDir  = dirname($logPath);

        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        // Rotate jika file terlalu besar
        $maxSize = (int) ($this->config['log']['max_size'] ?? 5242880);
        if (file_exists($logPath) && filesize($logPath) >= $maxSize) {
            rename($logPath, $logPath . '.' . date('YmdHis') . '.bak');
        }

        $content = implode(PHP_EOL, $this->sendLog) . PHP_EOL;
        file_put_contents($logPath, $content, FILE_APPEND | LOCK_EX);
    }
}
