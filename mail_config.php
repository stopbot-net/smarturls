<?php
/*
     _              _           _                _
    | |            | |         | |              | |
 ___| |_ ___  _ __ | |__   ___ | |_ _ __   ___| |_
/ __| __/ _ \| '_ \| '_ \ / _ \| __| '_ \ / _ \ __|
\__ \ || (_) | |_) | |_) | (_) | |_ | | | |  __/ |_
|___/\__\___/| .__/|_.__/ \___/ \__|_| |_|\___|\__|
             | |
             |_|
                      [Stopbot SmartUrls - Mailer]

Guide   : https://docs.stopbot.net
Website : stopbot.net
contact : t.me @stopbotnet
*/

return [

    /* ─────────────────────────────────────────
     |  SMTP CONNECTION
     ───────────────────────────────────────── */
    'smtp' => [
        'host'       => 'smtp.example.com',   // SMTP server hostname
        'port'       => 587,                   // 587 (TLS) | 465 (SSL) | 25 (plain)
        'encryption' => 'tls',                 // 'tls' | 'ssl' | '' (none)
        'auth'       => true,                  // true = require SMTP authentication
        'username'   => 'user@example.com',    // SMTP login username
        'password'   => 'your_smtp_password',  // SMTP login password
        'timeout'    => 30,                    // Connection timeout (seconds)
        'debug'      => 0,                     // 0=off | 1=client | 2=full | 3=verbose
    ],

    /* ─────────────────────────────────────────
     |  FROM LIST
     |  Pengirim dipilih secara berurutan (round-robin).
     |  Set 'enabled' => false untuk menonaktifkan entri.
     ───────────────────────────────────────── */
    'from_list' => [
        [
            'name'    => 'Support Team',
            'email'   => 'support@example.com',
            'enabled' => true,
        ],
        [
            'name'    => 'No Reply',
            'email'   => 'noreply@example.com',
            'enabled' => true,
        ],
        [
            'name'    => 'Admin',
            'email'   => 'admin@example.com',
            'enabled' => false,  // dinonaktifkan, tidak akan dipakai
        ],
    ],

    /* ─────────────────────────────────────────
     |  RECIPIENT LIST (TO)
     |  Daftar penerima utama email.
     ───────────────────────────────────────── */
    'to_list' => [
        ['email' => 'recipient1@example.com', 'name' => 'Recipient One'],
        ['email' => 'recipient2@example.com', 'name' => 'Recipient Two'],
        ['email' => 'recipient3@example.com', 'name' => 'Recipient Three'],
    ],

    /* ─────────────────────────────────────────
     |  CC LIST
     ───────────────────────────────────────── */
    'cc_list' => [
        // ['email' => 'cc@example.com', 'name' => 'CC Person'],
    ],

    /* ─────────────────────────────────────────
     |  BCC LIST
     ───────────────────────────────────────── */
    'bcc_list' => [
        // ['email' => 'bcc@example.com', 'name' => 'BCC Person'],
    ],

    /* ─────────────────────────────────────────
     |  SUBJECT LIST
     |  Subject diputar secara berurutan per pengiriman.
     ───────────────────────────────────────── */
    'subject_list' => [
        'Selamat datang di layanan kami!',
        'Informasi penting untuk Anda',
        'Update terbaru dari kami',
        'Penawaran eksklusif khusus Anda',
    ],

    /* ─────────────────────────────────────────
     |  EMAIL BODY
     ───────────────────────────────────────── */
    'body' => [
        'html'      => '',              // HTML body (kosongkan jika pakai template)
        'plain'     => '',              // Plain-text fallback
        'template'  => '',              // Path ke file .html template (opsional)
        'variables' => [],              // Variabel untuk di-replace di template: ['{{name}}' => 'John']
    ],

    /* ─────────────────────────────────────────
     |  FILE ATTACHMENTS
     |  Daftar file yang dilampirkan ke setiap email.
     ───────────────────────────────────────── */
    'attachments' => [
        // [
        //     'path'     => '/absolute/path/to/file.pdf',
        //     'name'     => 'Document.pdf',   // nama tampil di email (opsional)
        //     'encoding' => 'base64',         // 'base64' | 'quoted-printable' | '8bit' | '7bit' | 'binary'
        //     'type'     => 'application/pdf',// MIME type (opsional, auto-detect jika kosong)
        //     'inline'   => false,            // true = inline (CID embed), false = attachment
        // ],
    ],

    /* ─────────────────────────────────────────
     |  ENCODING
     ───────────────────────────────────────── */
    'encoding' => [
        'charset'       => 'UTF-8',              // Charset email
        'body_encoding' => 'base64',             // 'base64' | 'quoted-printable' | '8bit' | '7bit'
        'content_type'  => 'text/html',          // 'text/html' | 'text/plain'
    ],

    /* ─────────────────────────────────────────
     |  THREADING
     |  Aktifkan untuk mengelompokkan email dalam satu thread.
     ───────────────────────────────────────── */
    'thread' => [
        'enabled'        => false,     // true = aktifkan threading
        'message_id'     => '',        // Message-ID dari email pertama (untuk reply)
        'thread_subject' => '',        // Subject thread tetap (pakai ini jika reply)
    ],

    /* ─────────────────────────────────────────
     |  RETRY
     ───────────────────────────────────────── */
    'retry' => [
        'max_attempts' => 3,     // Jumlah maksimum percobaan kirim
        'delay_seconds' => 5,    // Jeda antar percobaan (detik)
    ],

    /* ─────────────────────────────────────────
     |  BATCH SEND
     ───────────────────────────────────────── */
    'batch' => [
        'delay_between_emails' => 1,   // Jeda antar email dalam satu batch (detik)
        'keep_alive'           => true, // Gunakan koneksi SMTP persisten (hemat sumber daya)
    ],

    /* ─────────────────────────────────────────
     |  REPLY-TO
     ───────────────────────────────────────── */
    'reply_to' => [
        // ['email' => 'replyto@example.com', 'name' => 'Reply Here'],
    ],

    /* ─────────────────────────────────────────
     |  LOGGING
     ───────────────────────────────────────── */
    'log' => [
        'enabled'  => true,
        'path'     => __DIR__ . '/lib/mailer/logs/mailer.log',
        'max_size' => 5 * 1024 * 1024,  // 5 MB, lalu di-rotate
    ],

];
