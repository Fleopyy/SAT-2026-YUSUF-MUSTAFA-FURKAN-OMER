<?php
// BİTİRAS — E-Posta Kuyruk ve Randevu Bildirim CLI İşçisi
// Bu betik sadece CLI (Komut Satırı) üzerinden çalışacak şekilde kilitlenmiştir.
// Windows Görev Zamanlayıcı (Task Scheduler) ile periyodik olarak çalıştırılabilir.

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    echo "Hata: Bu betik yalnızca komut satırından (CLI) çalıştırılabilir.\n";
    exit(1);
}

// Zaman limiti ve bellek ayarları
@set_time_limit(300);
@ini_set('memory_limit', '128M');
if (!ini_get('date.timezone')) {
    date_default_timezone_set('Europe/Istanbul');
}

require_once __DIR__ . '/../data/database.php';
require_once __DIR__ . '/../data/mail_config.php';
require_once __DIR__ . '/../data/mail_service.php';
require_once __DIR__ . '/../data/appointment_notification_service.php';

$options = getopt('', ['reminders-only', 'queue-only', 'limit:', 'quiet', 'help']);

if (isset($options['help'])) {
    echo "====================================================\n";
    echo " BİTİRAS E-Posta & Randevu Bildirim CLI İşçisi\n";
    echo "====================================================\n";
    echo "Kullanım:\n";
    echo "  php cron/mail_worker.php [seçenekler]\n\n";
    echo "Seçenekler:\n";
    echo "  --reminders-only   Yalnızca randevu hatırlatmalarını denetler ve kuyruğa ekler.\n";
    echo "  --queue-only       Yalnızca kuyruktaki bekleyen e-postaları gönderir.\n";
    echo "  --limit=N          Kuyruktan tek seferde işlenecek azami e-posta adedi (varsayılan: 50).\n";
    echo "  --quiet            Detaylı çıktı üretmez, sadece özet döner.\n";
    echo "  --help             Bu yardım iletisini gösterir.\n";
    exit(0);
}

$quiet = isset($options['quiet']);
$limit = isset($options['limit']) ? max(1, (int)$options['limit']) : 50;
$remindersOnly = isset($options['reminders-only']);
$queueOnly = isset($options['queue-only']);

$startTime = microtime(true);
$timestamp = date('Y-m-d H:i:s');

if (!$quiet) {
    echo "[{$timestamp}] BİTİRAS Mail Worker başlatıldı.\n";
}

$results = [
    'reminders' => null,
    'queue' => null,
];

try {
    // 1. Randevu Hatırlatma Denetimi (24 saat, 2 saat, işletme yaklaşan randevu)
    if (!$queueOnly) {
        if (!$quiet) {
            echo "[{$timestamp}] Randevu hatırlatmaları taranıyor...\n";
        }
        $reminderResults = processAppointmentReminders();
        $results['reminders'] = $reminderResults;

        if (!$quiet) {
            echo sprintf(
                "  -> 24 Saat Hatırlatması: %d eklendi\n  -> 2 Saat Müşteri Hatırlatması: %d eklendi\n  -> İşletme Yaklaşan Bildirimi: %d eklendi\n",
                $reminderResults['reminders_24h'] ?? 0,
                $reminderResults['reminders_2h'] ?? 0,
                $reminderResults['business_reminders'] ?? 0
            );
        }
    }

    // 2. Kuyruktaki E-Postaların Gönderilmesi
    if (!$remindersOnly) {
        if (!$quiet) {
            echo "[{$timestamp}] Kuyruktaki e-postalar işleniyor (azami: {$limit})...\n";
        }
        $queueResults = processMailQueue($limit);
        $results['queue'] = $queueResults;

        if (!$quiet) {
            echo sprintf(
                "  -> İşlenen: %d | Başarılı: %d | Başarısız: %d\n",
                $queueResults['processed'] ?? 0,
                $queueResults['sent'] ?? 0,
                $queueResults['failed'] ?? 0
            );
        }
    }

    $elapsed = round(microtime(true) - $startTime, 3);
    if (!$quiet) {
        echo "[{$timestamp}] Görev tamamlandı ({$elapsed} saniye).\n";
    }

    exit(0);
} catch (Throwable $e) {
    $errTime = date('Y-m-d H:i:s');
    fwrite(STDERR, "[{$errTime}] HATA: " . $e->getMessage() . "\n");
    error_log("BİTİRAS Mail Worker CLI error: " . $e->getMessage());
    exit(1);
}
