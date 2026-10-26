<?php

namespace App\Support;

use RuntimeException;

final class WebpImage
{
    public static function encode(string $binary, int $maxEdge, int $quality = 75): string
    {
        if (! function_exists('imagewebp') || ! function_exists('imagecreatefromstring')) {
            throw new RuntimeException('Server belum bisa menyimpan gambar WebP.');
        }

        $source = @imagecreatefromstring($binary);
        if ($source === false) {
            throw new RuntimeException('Gambar tidak dapat dibaca.');
        }

        $width = imagesx($source);
        $height = imagesy($source);
        $longest = max($width, $height);
        $target = $source;

        if ($longest > $maxEdge) {
            $scale = $maxEdge / $longest;
            $nextWidth = max(1, (int) round($width * $scale));
            $nextHeight = max(1, (int) round($height * $scale));
            $resized = imagecreatetruecolor($nextWidth, $nextHeight);
            imagealphablending($resized, false);
            imagesavealpha($resized, true);
            $transparent = imagecolorallocatealpha($resized, 0, 0, 0, 127);
            imagefilledrectangle($resized, 0, 0, $nextWidth, $nextHeight, $transparent);
            imagecopyresampled($resized, $source, 0, 0, 0, 0, $nextWidth, $nextHeight, $width, $height);
            imagedestroy($source);
            $target = $resized;
        }

        ob_start();
        $saved = imagewebp($target, null, $quality);
        $webp = ob_get_clean();
        imagedestroy($target);

        if ($saved !== true || ! is_string($webp) || $webp === '') {
            throw new RuntimeException('Gambar gagal disimpan sebagai WebP.');
        }

        return $webp;
    }
}
