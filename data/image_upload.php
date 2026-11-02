<?php
// No database or session side effects. Only decoded and re-encoded raster images are stored.
function validatedUploadImage(array $file, int $maxBytes): array {
    if ($maxBytes < 1 || $maxBytes > 5 * 1024 * 1024) throw new RuntimeException('Gecersiz dosya boyutu siniri.');
    $tmp = $file['tmp_name'] ?? null;
    $name = $file['name'] ?? null;
    if (($file['error'] ?? null) !== UPLOAD_ERR_OK || !is_string($tmp) || !is_uploaded_file($tmp)) {
        throw new RuntimeException('Fotoğraf yüklenemedi.');
    }
    // Exactly one extension; reject path syntax, hidden names and double extensions.
    if (!is_string($name) || !preg_match('/\A[^.\/\\\\\x00-\x1f:]+\.(jpe?g|png|webp)\z/iD', $name, $match)) {
        throw new RuntimeException('Geçersiz fotoğraf dosya adı veya uzantısı.');
    }
    $size = filesize($tmp);
    if ($size === false || $size < 1 || $size > $maxBytes) {
        throw new RuntimeException('Fotoğraf izin verilen dosya boyutunu aşıyor.');
    }
    $types = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($tmp);
    $info = @getimagesize($tmp);
    $extension = strtolower($match[1]);
    if ($extension === 'jpeg') $extension = 'jpg';
    if (!isset($types[$mime]) || $types[$mime] !== $extension || !$info || ($info['mime'] ?? '') !== $mime) {
        throw new RuntimeException('Yalnızca geçerli JPG, PNG veya WEBP fotoğraf yükleyebilirsiniz.');
    }
    // Bound decoder memory usage before decoding untrusted pixels.
    if ($info[0] < 1 || $info[1] < 1 || $info[0] > 8000 || $info[1] > 8000 || $info[0] * $info[1] > 16000000) {
        throw new RuntimeException('Fotoğraf boyutları çok büyük.');
    }
    assertUploadImageResources((int)$info[0], (int)$info[1], $size);
    if (!function_exists('imagecreatefromstring')) throw new RuntimeException('Görsel işleme kullanılamıyor.');
    $contents = file_get_contents($tmp);
    if ($contents === false || strlen($contents) !== $size) throw new RuntimeException('Fotograf okunamadi.');
    $image = @imagecreatefromstring($contents);
    if ($image === false) throw new RuntimeException('Fotoğraf içeriği çözümlenemedi.');
    return [$image, $extension];
}

function storeUploadImageWithinQuota(array $file, int $maxBytes, string $relativeDirectory): string {
    [$image, $extension] = validatedUploadImage($file, $maxBytes);
    $directory = dirname(__DIR__);
    $path = null;
    $handle = null;
    try {
        // Never follow symlinks or Windows junctions into another destination.
        foreach (explode('/', $relativeDirectory) as $part) {
            if (!preg_match('/\A[a-z0-9_-]+\z/D', $part)) throw new RuntimeException('Geçersiz yükleme dizini.');
            $directory .= '/' . $part;
            if (!is_dir($directory) && !mkdir($directory, 0750) && !is_dir($directory)) throw new RuntimeException('Fotoğraf klasörü oluşturulamadı.');
            $actual = str_replace('\\', '/', (string)realpath($directory));
            $expected = str_replace('\\', '/', $directory);
            if (is_link($directory) || (PHP_OS_FAMILY === 'Windows' ? strcasecmp($actual, $expected) !== 0 : $actual !== $expected)) {
                throw new RuntimeException('Güvensiz yükleme dizini.');
            }
        }
        $filename = bin2hex(random_bytes(20)) . '.' . $extension;
        $path = $directory . '/' . $filename;
        $handle = @fopen($path, 'x+b');
        if (!$handle) throw new RuntimeException('Fotoğraf diske kaydedilemedi.');
        imagealphablending($image, false);
        imagesavealpha($image, true);
        $ok = match ($extension) {
            'jpg' => imagejpeg($image, $handle, 90),
            'png' => imagepng($image, $handle),
            'webp' => imagewebp($image, $handle, 90),
        };
        if (!$ok || !fflush($handle) || fstat($handle)['size'] < 1 || fstat($handle)['size'] > $maxBytes) throw new RuntimeException('Fotoğraf kaydedilemedi.');
        return $relativeDirectory . '/' . $filename;
    } catch (Throwable $e) {
        if (is_resource($handle)) { fclose($handle); $handle = null; if ($path !== null) unlink($path); }
        throw $e;
    } finally {
        if (is_resource($handle)) fclose($handle);
        imagedestroy($image);
    }
}

// Conservative working-set estimate includes decoder, raster, encoder and input buffers.
function assertUploadImageResources(int $width, int $height, int $bytes): void {
    if ($width < 1 || $height < 1 || $width > 8000 || $height > 8000
        || $width * $height > 16000000 || $bytes < 1 || $bytes > 5 * 1024 * 1024) {
        throw new RuntimeException('Fotograf kaynak sinirini asiyor.');
    }
    $required = $width * $height * 12 + $bytes * 2 + 16 * 1024 * 1024;
    $setting = trim((string)ini_get('memory_limit'));
    $limit = 256 * 1024 * 1024; // Also bound work when PHP memory_limit is unlimited.
    if ($setting !== '-1') {
        if (!preg_match('/\A([0-9]+)\s*([KMG]?)\z/i', $setting, $parts)) {
            throw new RuntimeException('Bellek siniri belirlenemedi.');
        }
        $multiplier = match (strtoupper($parts[2])) { 'K' => 1024, 'M' => 1048576, 'G' => 1073741824, default => 1 };
        $limit = min($limit, (float)$parts[1] * $multiplier);
    }
    if ($required > $limit - memory_get_usage(true)) throw new RuntimeException('Fotograf icin yeterli bellek yok.');
}

// Count all files (including historical/orphan files); never traverse redirected paths.
function uploadQuotaUsage(string $directory, int $maxFiles, int $maxBytes): array {
    $count = 0; $bytes = 0; $visited = 0;
    $walk = function (string $path, int $depth) use (&$walk, &$count, &$bytes, &$visited, $maxFiles, $maxBytes): void {
        if ($depth > 8) throw new RuntimeException('Yukleme dizini siniri asildi.');
        foreach (new DirectoryIterator($path) as $entry) {
            if ($entry->isDot()) continue;
            if (++$visited > 4096) throw new RuntimeException('Yukleme dizini siniri asildi.');
            $name = $entry->getPathname();
            $actual = str_replace('\\', '/', (string)realpath($name));
            $expected = str_replace('\\', '/', $name);
            if ($entry->isLink() || (PHP_OS_FAMILY === 'Windows' ? strcasecmp($actual, $expected) !== 0 : $actual !== $expected)) {
                throw new RuntimeException('Guvensiz kota dizini.');
            }
            if ($entry->isDir()) { $walk($name, $depth + 1); continue; }
            if (!$entry->isFile() || $entry->getSize() < 0) throw new RuntimeException('Kota hesaplanamadi.');
            $count++; $bytes += $entry->getSize();
            if ($count >= $maxFiles || $bytes > $maxBytes) throw new RuntimeException('Fotograf kotasi dolu.');
        }
    };
    $walk($directory, 0);
    return [$count, $bytes];
}

function storeUploadImage(array $file, int $maxBytes, string $relativeDirectory): string {
    if (!preg_match('~\Auploads/(isletme|musteri)/([1-9][0-9]*)(?:/(gallery|employees))?\z~D', $relativeDirectory, $owner)
        || ($owner[1] === 'isletme') !== isset($owner[3])) throw new RuntimeException('Gecersiz yukleme dizini.');
    $business = $owner[1] === 'isletme';
    if ($maxBytes < 1 || $maxBytes > ($business ? 2 : 5) * 1024 * 1024) throw new RuntimeException('Gecersiz dosya siniri.');
    $directory = dirname(__DIR__);
    foreach (['uploads', $owner[1], $owner[2]] as $part) {
        $directory .= '/' . $part;
        if (!is_dir($directory) && !@mkdir($directory, 0750) && !is_dir($directory)) throw new RuntimeException('Kota dizini olusturulamadi.');
        $actual = str_replace('\\', '/', (string)realpath($directory));
        $expected = str_replace('\\', '/', $directory);
        if (is_link($directory) || (PHP_OS_FAMILY === 'Windows' ? strcasecmp($actual, $expected) !== 0 : $actual !== $expected)) throw new RuntimeException('Guvensiz kota dizini.');
    }
    // A persistent lock outside the counted owner tree serializes gallery and employee uploads.
    $lockPath = dirname($directory) . '/.quota-' . $owner[2] . '.lock';
    if (is_link($lockPath)) throw new RuntimeException('Guvensiz kota kilidi.');
    $lock = @fopen($lockPath, 'c+b');
    if (!$lock) throw new RuntimeException('Kota kilidi acilamadi.');
    try {
        $lockStat = fstat($lock);
        $actualLock = str_replace('\\', '/', (string)realpath($lockPath));
        $expectedLock = str_replace('\\', '/', $lockPath);
        if (!$lockStat || $lockStat['nlink'] !== 1 || is_link($lockPath)
            || (PHP_OS_FAMILY === 'Windows' ? strcasecmp($actualLock, $expectedLock) !== 0 : $actualLock !== $expectedLock)) {
            throw new RuntimeException('Guvensiz kota kilidi.');
        }
        if (!flock($lock, LOCK_EX | LOCK_NB)) throw new RuntimeException('Baska bir fotograf yukleniyor. Tekrar deneyin.');
        $quota = ($business ? 100 : 20) * 1024 * 1024;
        [, $used] = uploadQuotaUsage($directory, $business ? 200 : 20, $quota);
        // Reserve the maximum encoded size before allocating any image raster.
        if ($maxBytes > $quota - $used) throw new RuntimeException('Fotograf kotasi dolu.');
        return storeUploadImageWithinQuota($file, $maxBytes, $relativeDirectory);
    } finally {
        flock($lock, LOCK_UN);
        fclose($lock);
    }
}
