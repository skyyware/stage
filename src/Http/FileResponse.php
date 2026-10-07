<?php
declare(strict_types=1);

namespace Stage\Http;

use InvalidArgumentException;
use RuntimeException;
use SplFileObject;

final readonly class FileResponse extends Response
{
    private SplFileObject $file;
    private int $offset;
    private int $length;
    public int $size;

    /** @param array<string, string> $headers */
    public function __construct(string $path, string $downloadName, array $headers = [], ?string $contentType = null, ?Request $request = null)
    {
        if (($contentType === null) !== ($request === null)) {
            throw new InvalidArgumentException('Inline delivery requires a content type and request.');
        }
        if ($contentType !== null && !preg_match('~^[a-z0-9][a-z0-9!#$&^_.+\-]*/[a-z0-9][a-z0-9!#$&^_.+\-]*$~iD', $contentType)) {
            throw new InvalidArgumentException('The content type must be a type/subtype without parameters.');
        }
        if ($downloadName === '' || $downloadName === '.' || $downloadName === '..'
            || preg_match('//u', $downloadName) !== 1 || preg_match('/[\x00-\x1f\x7f\/\\\\]/', $downloadName)) {
            throw new InvalidArgumentException('The download name must be a UTF-8 filename.');
        }
        foreach ($headers as $name => $value) {
            if (in_array(strtolower($name), ['content-type', 'content-disposition', 'x-content-type-options'], true)) {
                throw new InvalidArgumentException('File response download headers cannot be replaced.');
            }
            if ($request !== null && in_array(strtolower($name), ['accept-ranges', 'content-range'], true)) {
                throw new InvalidArgumentException('File response range headers cannot be replaced.');
            }
        }
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
        [$status, $this->offset, $this->length] = self::selection($request, $this->size);
        if ($request !== null) {
            $headers['accept-ranges'] = 'bytes';
        }
        if ($status === 206) {
            $headers['content-range'] = 'bytes ' . $this->offset . '-' . ($this->offset + $this->length - 1) . '/' . $this->size;
        } elseif ($status === 416) {
            $headers['content-range'] = 'bytes */' . $this->size;
        }
        $fallback = str_replace(['\\', '"'], ['\\\\', '\\"'], preg_replace('/[^\x20-\x7e]/', '_', $downloadName) ?? 'download');
        parent::__construct('', $status, array_merge($headers, [
            'content-type' => $contentType ?? 'application/octet-stream',
            'content-disposition' => ($request === null ? 'attachment' : 'inline') . '; filename="' . $fallback . '"; filename*=UTF-8\'\'' . rawurlencode($downloadName),
            'x-content-type-options' => 'nosniff',
        ]));
    }

    /** @param array<string, string> $headers */
    public static function inline(string $path, string $downloadName, string $contentType, Request $request, array $headers = []): self
    {
        return new self($path, $downloadName, $headers, $contentType, $request);
    }

    /** @return array{int, int, int} */
    private static function selection(?Request $request, int $size): array
    {
        $full = [200, 0, $size];
        if ($request === null || $request->method !== 'GET') {
            return $full;
        }
        $ranges = [];
        foreach ($request->headers as $name => $value) {
            if (strtolower($name) === 'if-range') {
                return $full;
            }
            if (strtolower($name) === 'range') {
                $ranges[] = $value;
            }
        }
        if (count($ranges) !== 1 || !preg_match('/^bytes=([0-9]*)-([0-9]*)$/iD', trim($ranges[0], " \t"), $match)
            || ($match[1] === '' && $match[2] === '')) {
            return $full;
        }
        $first = ltrim($match[1], '0') ?: '0';
        $last = ltrim($match[2], '0') ?: '0';
        if ($match[1] !== '' && $match[2] !== '' && self::compare($first, $last) > 0) {
            return $full;
        }
        if ($size === 0 || ($match[1] === '' && $last === '0')
            || ($match[1] !== '' && self::compare($first, (string) $size) >= 0)) {
            return [416, 0, 0];
        }
        if ($match[1] === '') {
            $length = self::compare($last, (string) $size) >= 0 ? $size : (int) $last;
            return [206, $size - $length, $length];
        }
        $start = (int) $first;
        $end = $match[2] === '' || self::compare($last, (string) $size) >= 0 ? $size - 1 : (int) $last;
        return [206, $start, $end - $start + 1];
    }

    private static function compare(string $left, string $right): int
    {
        return strlen($left) <=> strlen($right) ?: strcmp($left, $right);
    }

    public function send(bool $head = false): void
    {
        if (!$head && $this->length > 0 && $this->file->fseek($this->offset) !== 0) {
            throw new RuntimeException('The file response could not seek to its start.');
        }
        parent::send($head);
    }

    protected function bodyLength(): int
    {
        return $this->length;
    }

    protected function sendBody(): void
    {
        $remaining = $this->length;
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
