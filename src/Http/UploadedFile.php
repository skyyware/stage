<?php
declare(strict_types=1);

namespace Stage\Http;

use InvalidArgumentException;

final readonly class UploadedFile
{
    private function __construct(public string $path, public string $name, public int $size) {}

    /** @param array<string, mixed> $upload */
    public static function fromPhp(array $upload, int $maxBytes): self
    {
        if ($maxBytes < 0) {
            throw new InvalidArgumentException('The upload limit cannot be negative.');
        }
        $error = $upload['error'] ?? null;
        if (!is_int($error)) {
            throw new HttpError(400);
        }
        if ($error !== UPLOAD_ERR_OK) {
            throw new HttpError(match ($error) {
                UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 413,
                UPLOAD_ERR_NO_TMP_DIR, UPLOAD_ERR_CANT_WRITE, UPLOAD_ERR_EXTENSION => 500,
                default => 400,
            });
        }
        $path = $upload['tmp_name'] ?? null;
        $name = $upload['name'] ?? null;
        $reportedSize = $upload['size'] ?? null;
        if (!is_string($path) || !is_string($name) || !is_int($reportedSize) || $reportedSize < 0
            || preg_match('//u', $name) !== 1 || preg_match('/[\x00-\x1f\x7f]/', $name)) {
            throw new HttpError(400);
        }
        $name = basename(str_replace('\\', '/', $name));
        if ($name === '' || $name === '.' || $name === '..' || !is_uploaded_file($path)) {
            throw new HttpError(400);
        }
        clearstatcache(true, $path);
        $size = @filesize($path);
        if ($size === false) {
            throw new HttpError(500);
        }
        if ($size > $maxBytes) {
            throw new HttpError(413);
        }
        if ($size !== $reportedSize) {
            throw new HttpError(400);
        }
        return new self($path, $name, $size);
    }
}
