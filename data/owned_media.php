<?php
function ownedMediaPath(string $path, int $businessId, ?string $root = null): string {
    $root = $root ?? dirname(__DIR__);
    $path = str_replace('\\', '/', $path);
    if ($businessId < 1 || preg_match('/[\x00-\x1f:]/', $path) || str_starts_with($path, '//')) {
        throw new RuntimeException('Geçersiz dosya yolu.');
    }
    $path = ltrim($path, '/'); // Historical root-relative URLs are supported.
    $parts = explode('/', $path);
    if (array_intersect($parts, ['', '.', '..']) || !str_starts_with($path, 'uploads/isletme/' . $businessId . '/')) {
        throw new RuntimeException('Dosya işletmenin yükleme dizininde değil.');
    }
    $walk = $root;
    foreach ($parts as $part) {
        $walk .= DIRECTORY_SEPARATOR . $part;
        clearstatcache(true, $walk);
        if (is_link($walk)) throw new RuntimeException('Sembolik bağlantı silinemez.');
        $resolved = realpath($walk);
        $expected = str_replace('\\', '/', $walk);
        $actual = str_replace('\\', '/', (string)$resolved);
        if (PHP_OS_FAMILY === 'Windows') { $expected = strtolower($expected); $actual = strtolower($actual); }
        if ($resolved === false || $actual !== $expected) throw new RuntimeException('Dosya yolu çözümlenemedi veya yönlendirilmiş.');
    }
    $base = realpath($root . '/uploads/isletme/' . $businessId);
    $candidate = realpath($walk);
    $prefix = str_replace('\\', '/', $base ?: '') . '/';
    $normalized = str_replace('\\', '/', $candidate ?: '');
    if (PHP_OS_FAMILY === 'Windows') { $prefix = strtolower($prefix); $normalized = strtolower($normalized); }
    if (!$base || !$candidate || !str_starts_with($normalized, $prefix) || !is_file($candidate)) {
        throw new RuntimeException('Silinecek dosya bulunamadı.');
    }
    $stat = stat($candidate);
    if (!$stat || $stat['nlink'] > 1) throw new RuntimeException('Paylaşılan dosya bağlantısı silinemez.');
    return $candidate;
}

function deleteOwnedMedia(string $path, int $businessId, $db = null, ?callable $change = null, ?string $root = null): void {
    $file = ownedMediaPath($path, $businessId, $root);
    $backup = dirname($file) . '/.delete-' . bin2hex(random_bytes(20));
    $staged = false;
    try {
        if ($db && $db->beginTransaction() === false) throw new RuntimeException('İşlem başlatılamadı.');
        if (ownedMediaPath($path, $businessId, $root) !== $file) throw new RuntimeException('Dosya yolu değişti.');
        if (!rename($file, $backup)) throw new RuntimeException('Dosya silmeye hazırlanamadı.');
        $staged = true;
        if ($change) $change();
        if (!copy($backup, $file)) throw new RuntimeException('Dosya kurtarma kopyası oluşturulamadı.');
        if (!unlink($file)) throw new RuntimeException('Dosya silinemedi.');
        if ($db && $db->commit() === false) throw new RuntimeException('İşlem kaydedilemedi.');
    } catch (Throwable $e) {
        try {
            if ($db && $db->inTransaction() && $db->rollBack() === false) throw new RuntimeException('Rollback failed');
        } catch (Throwable $rollbackError) {
            error_log('Media database recovery required: ' . $rollbackError->getMessage());
        }
        $restored = !$staged || (file_exists($file)
            ? (copy($backup, $file) && unlink($backup))
            : rename($backup, $file));
        if (!$restored) {
            error_log('Media recovery required: ' . $backup . ' -> ' . $file);
            throw new RuntimeException('Dosya geri yüklenemedi; yönetici müdahalesi gerekli.', 0, $e);
        }
        throw $e;
    }
    if (!unlink($backup)) {
        error_log('Media cleanup required: ' . $backup);
        throw new RuntimeException('Kayıt silindi fakat geçici dosya temizlenemedi; yönetici müdahalesi gerekli.');
    }
}
