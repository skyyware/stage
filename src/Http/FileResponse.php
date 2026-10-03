<?php
declare(strict_types=1);

namespace Stage\Http;

use InvalidArgumentException;
use RuntimeException;
use SplFileObject;

final readonly class FileResponse extends Response
{
    private SplFileObject $file;
    public int $size;

    /** @param array<string, string> $headers */
    public function __construct(string $path, string $downloadName, array $headers = [])
    {
        if ($downloadName === '' || $downloadName === '.' || $downloadName === '..'
            || preg_match('//u', $downloadName) !== 1 || preg_match('/[\x00-\x1f\x7f\/\\\\]/', $downloadName)) {
            throw new InvalidArgumentException('The download name must be a UTF-8 filename.');
        }
        foreach ($headers as $name => $value) {
            if (in_array(strtolower($name), ['content-type', 'content-disposition', 'x-content-type-options'], true)) {
                throw new InvalidArgumentException('File response download headers cannot be replaced.');
            }
        }
        $fallback = str_replace(['\\', '"'], ['\\\\', '\\"'], preg_replace('/[^\x20-\x7e]/', '_', $downloadName) ?? 'download');
        parent::__construct('', 200, array_merge($headers, [
            'content-type' => 'application/octet-stream',
            'content-disposition' => 'attachment; filename="' . $fallback . '"; filename*=UTF-8\'\'' . rawurlencode($downloadName),
            'x-content-type-options' => 'nosniff',
        ]));
        if (!is_file($path)) {
            throw new HttpError(404);
        }
        try {
            $file = new SplFileObject($path, 'rb');
            $stat = $file->fstat();
        } catch (RuntimeException) {
            throw new HttpError(500);
        }
        if (($stat['mode'] & 0170000) !== 0100000 || $stat['size'] < 0) {
            throw new HttpError(500);
        }
        $this->file = $file;
        $this->size = $stat['size'];
    }

    public function send(bool $head = false): void
    {
        if (!$head) {
            $this->file->rewind();
        }
        parent::send($head);
    }

    protected function bodyLength(): int
    {
        return $this->size;
    }

    protected function sendBody(): void
    {
        $remaining = $this->size;
        while ($remaining > 0) {
            $chunk = $this->file->fread(min(65536, $remaining));
            if ($chunk === false || $chunk === '') {
                throw new RuntimeException('The file response could not be completed.');
            }
            echo $chunk;
            $remaining -= strlen($chunk);
        }
    }
}
