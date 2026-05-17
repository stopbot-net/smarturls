<?php
/**
 * Contoh penggunaan Stopbot Mailer
 * Jalankan: php lib/mailer/example.php
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use Stopbot\Mailer\Mailer;

// ── 1. Inisialisasi (pakai mail_config.php) ─────────────────
$mailer = new Mailer(__DIR__ . '/../../mail_config.php');

// ── 2. Override config secara runtime (opsional) ─────────────

// Ganti daftar penerima
$mailer->setToList([
    ['email' => 'alice@example.com', 'name' => 'Alice'],
    ['email' => 'bob@example.com',   'name' => 'Bob'],
]);

// Ganti daftar subject (diputar berurutan per email)
$mailer->setSubjectList([
    'Halo Alice & Bob! 🎉',
    'Update Mingguan #42',
]);

// Set body langsung
$mailer->setBody(
    '<h1>Halo {{name}}!</h1><p>Ini pesan dari Stopbot Mailer.</p>',
    'Halo {{name}}! Ini pesan dari Stopbot Mailer.'
);

// Tambah attachment runtime
// $mailer->addAttachment('/path/ke/file.pdf', 'Laporan.pdf', 'base64');
// $mailer->addAttachment('/path/ke/gambar.jpg', 'foto.jpg', 'base64', 'image/jpeg', true); // inline

// ── 3. Kirim batch (semua penerima di to_list) ───────────────
echo "=== Memulai batch send ===\n";

$results = $mailer->sendBatch([
    '{{name}}' => 'Teman',  // variabel global untuk semua email batch ini
]);

// ── 4. Tampilkan hasil ───────────────────────────────────────
echo "\n--- BERHASIL ---\n";
foreach ($results['success'] as $r) {
    echo "  ✓ {$r['recipient']} | Subject: {$r['subject']} | From: {$r['from']} | Attempt: {$r['attempt']}\n";
    echo "    Message-ID: {$r['message_id']}\n";
}

echo "\n--- GAGAL ---\n";
foreach ($results['failed'] as $r) {
    echo "  ✗ {$r['recipient']} | {$r['error']}\n";
}

// ── 5. Kirim reply/thread ────────────────────────────────────
// $threadId = '<abc123@mail.example.com>';
// $mailer->setBody('<p>Terima kasih atas pesan Anda.</p>');
// $replyResult = $mailer->sendReply(
//     ['email' => 'alice@example.com', 'name' => 'Alice'],
//     $threadId,
//     'Pertanyaan anda kemarin'
// );
// print_r($replyResult);

// ── 6. Log sesi ─────────────────────────────────────────────
echo "\n--- LOG SESI ---\n";
foreach ($mailer->getSendLog() as $entry) {
    echo $entry . "\n";
}
