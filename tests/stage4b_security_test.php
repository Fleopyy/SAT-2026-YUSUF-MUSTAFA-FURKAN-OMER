<?php
// CLI only. No application bootstrap, database or live media access.
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
require_once __DIR__ . '/../data/image_upload.php';
$n = 0;
function verify4b(bool $ok, string $label): void {
    global $n; $n++;
    if (!$ok) throw new RuntimeException($label);
}
function rejects4b(callable $fn, string $label): void {
    $failed = false;
    try { $fn(); } catch (RuntimeException $e) { $failed = true; }
    verify4b($failed, $label);
}
$source = file_get_contents(__DIR__ . '/../data/image_upload.php');
verify4b(strpos($source, 'assertUploadImageResources((int)$info[0]') < strpos($source, '$image = @imagecreatefromstring'), 'resource check precedes decode');
verify4b(str_contains($source, "if (!function_exists('imagecreatefromstring')) throw new RuntimeException"), 'GD missing fails closed before decode');
verify4b(str_contains($source, "fstat(\$handle)['size'] > \$maxBytes"), 'encoded output capped');
$oldLimit = ini_get('memory_limit');
ini_set('memory_limit', '128M');
assertUploadImageResources(1000, 1000, 2097152);
verify4b(true, 'normal raster budget');
foreach ([[8001,1,1], [1,8001,1], [4001,4000,1], [0,1,1], [1,1,5242881], [8000,2000,2097152]] as $args) {
    rejects4b(fn() => assertUploadImageResources(...$args), 'dimensions, pixels, input or memory bound');
}
ini_set('memory_limit', '-1');
assertUploadImageResources(8000,2000,5242880);
verify4b(true, 'unlimited PHP uses bounded application budget');

ini_set('memory_limit', $oldLimit);
rejects4b(fn() => validatedUploadImage([], 5242881), 'absolute input cap');
rejects4b(fn() => validatedUploadImage([], 2097152), 'non HTTP upload fails closed');
$dir = __DIR__ . '/.stage4b-' . bin2hex(random_bytes(8));
mkdir($dir);
try {
    mkdir($dir . '/gallery'); mkdir($dir . '/employees');
    file_put_contents($dir . '/gallery/old.jpg', '12345');
    file_put_contents($dir . '/employees/orphan.png', '1234567');
    verify4b(uploadQuotaUsage($dir, 3, 12) === [2,12], 'shared count includes orphan and both folders');
    rejects4b(fn() => uploadQuotaUsage($dir, 2, 100), 'file quota');
    rejects4b(fn() => uploadQuotaUsage($dir, 3, 11), 'byte quota');
    verify4b(file_get_contents($dir . '/gallery/old.jpg') === '12345', 'existing file preserved');
} finally {
    unlink($dir . '/gallery/old.jpg'); unlink($dir . '/employees/orphan.png');
    rmdir($dir . '/gallery'); rmdir($dir . '/employees'); rmdir($dir);
}
if (!function_exists('imagecreatefromstring')) {
    echo "SKIP GD metadata round trip: GD disabled.\n";
} else {
    // JPEG APP1 EXIF plus an appended payload must not survive the existing GD round trip.
    $im = imagecreatetruecolor(2,2);
    ob_start(); imagejpeg($im, null, 90); $jpeg = ob_get_clean(); imagedestroy($im);
    $marker = "Exif\0\0STAGE4B_PRIVATE_GPS";
    $input = substr($jpeg,0,2) . "\xff\xe1" . pack('n',strlen($marker)+2) . $marker . substr($jpeg,2) . 'STAGE4B_TRAILER';
    $im = imagecreatefromstring($input);
    verify4b($im !== false, 'metadata fixture decoded');
    ob_start(); imagejpeg($im, null, 90); $clean = ob_get_clean(); imagedestroy($im);
    verify4b(!str_contains($clean, 'STAGE4B_PRIVATE_GPS') && !str_contains($clean, 'STAGE4B_TRAILER') && !str_contains($clean, "Exif\0\0"), 'GD strips JPEG EXIF and trailer');
    foreach (['png','webp'] as $format) {
        $encoder = 'image' . $format;
        if (!function_exists($encoder)) { echo "SKIP $format encoder unavailable.\n"; continue; }
        $im = imagecreatetruecolor(2,2);
        ob_start(); $encoder($im); $encoded = ob_get_clean(); imagedestroy($im);
        $im = imagecreatefromstring($encoded . 'STAGE4B_PRIVATE_METADATA');
        ob_start(); $encoder($im); $clean = ob_get_clean(); imagedestroy($im);
        verify4b(!str_contains($clean, 'STAGE4B_PRIVATE_METADATA'), "$format appended data stripped");
    }
}
echo "Stage 4B: $n checks passed.\n";
