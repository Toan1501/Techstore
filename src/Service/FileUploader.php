<?php
// src/Service/FileUploader.php
namespace App\Service;

use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

class FileUploader
{
    public function __construct(
        private $targetDirectory,
        private SluggerInterface $slugger,
    ) {
    }

    /** Allowed image types. SVG is deliberately excluded (can carry <script> → stored XSS). */
    private const ALLOWED_MIME = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    private const ALLOWED_EXT = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
    private const MAX_BYTES = 4 * 1024 * 1024; // 4 MB

    public function upload(UploadedFile $file): string
    {
        if ($file->getSize() > self::MAX_BYTES) {
            throw new FileException('Ảnh vượt quá dung lượng cho phép (tối đa 4MB).');
        }

        $mime = (string) $file->getMimeType();
        $ext = strtolower((string) $file->guessExtension());
        if (!in_array($mime, self::ALLOWED_MIME, true) || !in_array($ext, self::ALLOWED_EXT, true)) {
            throw new FileException('Định dạng ảnh không hợp lệ. Chỉ chấp nhận JPG, PNG, WEBP, GIF.');
        }

        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $this->slugger->slug($originalFilename);
        $fileName = $safeFilename.'-'.uniqid().'.'.$ext;

        // Let a genuine move failure surface instead of returning a dangling filename.
        $file->move($this->getTargetDirectory(), $fileName);

        // Downscale oversized images to keep storefront pages light.
        $this->downscale($this->getTargetDirectory().'/'.$fileName, $ext);

        return $fileName;
    }

    /** Largest edge (px) kept for stored images. */
    private const MAX_EDGE = 1200;

    /** Resize an image in place so its longest edge is <= MAX_EDGE. No-op if GD is unavailable or already small. */
    private function downscale(string $path, string $ext): void
    {
        if (!function_exists('imagecreatetruecolor') || $ext === 'gif') {
            return; // skip animated gifs (GD would flatten them)
        }

        $info = @getimagesize($path);
        if ($info === false) {
            return;
        }
        [$w, $h] = $info;
        if ($w <= self::MAX_EDGE && $h <= self::MAX_EDGE) {
            return;
        }

        $src = match ($ext) {
            'jpg', 'jpeg' => @imagecreatefromjpeg($path),
            'png' => @imagecreatefrompng($path),
            'webp' => @imagecreatefromwebp($path),
            default => false,
        };
        if (!$src) {
            return;
        }

        $scale = self::MAX_EDGE / max($w, $h);
        $nw = (int) round($w * $scale);
        $nh = (int) round($h * $scale);
        $dst = imagecreatetruecolor($nw, $nh);

        if ($ext === 'png' || $ext === 'webp') {
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
        }
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);

        match ($ext) {
            'jpg', 'jpeg' => imagejpeg($dst, $path, 82),
            'png' => imagepng($dst, $path, 6),
            'webp' => imagewebp($dst, $path, 82),
            default => null,
        };

        imagedestroy($src);
        imagedestroy($dst);
    }

    public function getTargetDirectory(): string
    {
        return $this->targetDirectory;
    }
}
