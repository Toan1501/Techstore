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

        return $fileName;
    }

    public function getTargetDirectory(): string
    {
        return $this->targetDirectory;
    }
}
