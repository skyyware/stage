<?php
declare(strict_types=1);

namespace Stage\Tests;

use PHPUnit\Framework\TestCase;

final class FileHttpTest extends TestCase
{
    private string $directory;
    private string $address;
    private mixed $server = null;

    protected function setUp(): void
    {
        $this->directory = dirname(__DIR__) . '/.runtime/files-' . bin2hex(random_bytes(8));
        mkdir($this->directory, 0700, true);
        $file = fopen($this->directory . '/payload.bin', 'wb');
        self::assertIsResource($file);
        self::assertTrue(ftruncate($file, 64 * 1024 * 1024));
        fclose($file);
        $socket = stream_socket_server('tcp://127.0.0.1:0');
        self::assertIsResource($socket);
        $this->address = stream_socket_get_name($socket, false);
        fclose($socket);
        $environment = getenv();
        $environment['STAGE_TEST_FILE'] = $this->directory . '/payload.bin';
        unset($environment['PHP_CLI_SERVER_WORKERS']);
        $this->server = proc_open([
            PHP_BINARY, '-d', 'memory_limit=16M', '-d', 'output_buffering=0', '-d', 'zlib.output_compression=0',
            '-d', 'upload_max_filesize=65M', '-d', 'post_max_size=66M', '-d', 'display_errors=0',
            '-d', 'upload_tmp_dir=' . $this->directory, '-S', $this->address, __DIR__ . '/files.php',
        ], [0 => ['pipe', 'r'], 1 => ['file', $this->directory . '/server.log', 'a'],
            2 => ['file', $this->directory . '/server.log', 'a']], $pipes, dirname(__DIR__), $environment);
        self::assertIsResource($this->server);
        fclose($pipes[0]);
        for ($attempt = 0; $attempt < 100; $attempt++) {
            $connection = @stream_socket_client('tcp://' . $this->address, timeout: 0.05);
            if ($connection !== false) {
                fclose($connection);
                self::assertSame(200, $this->request('/health'));
                self::assertSame('Stage file fixture', file_get_contents($this->directory . '/body'));
                return;
            }
            usleep(20000);
        }
        self::fail('The PHP file fixture did not start: ' . file_get_contents($this->directory . '/server.log'));
    }

    protected function tearDown(): void
    {
        if (is_resource($this->server)) {
            proc_terminate($this->server);
            proc_close($this->server);
        }
        foreach (glob($this->directory . '/*') as $path) {
            unlink($path);
        }
        rmdir($this->directory);
    }

    public function testRealTransferExceedsPhpMemoryWithoutRaisingTheRawBodyLimit(): void
    {
        self::assertSame(200, $this->request('/download'));
        self::assertSame(64 * 1024 * 1024, filesize($this->directory . '/body'));
        $digest = hash_file('sha256', $this->directory . '/payload.bin');
        self::assertSame($digest, hash_file('sha256', $this->directory . '/body'));
        $headers = strtolower(file_get_contents($this->directory . '/headers'));
        foreach (['content-length: 67108864', 'content-type: application/octet-stream', 'cache-control: no-store',
            'x-content-type-options: nosniff', 'x-robots-tag: noindex', "content-security-policy: default-src 'none'",
            'content-disposition: attachment;', "filename*=utf-8''report%20%22%c3%a4%22.bin"] as $header) {
            self::assertStringContainsString($header, $headers);
        }
        $connection = stream_socket_client('tcp://' . $this->address, timeout: 5);
        self::assertIsResource($connection);
        stream_set_timeout($connection, 5);
        fwrite($connection, "HEAD /download HTTP/1.1\r\nHost: " . $this->address . "\r\nConnection: close\r\n\r\n");
        $head = stream_get_contents($connection);
        fclose($connection);
        [$headHeaders, $headBody] = explode("\r\n\r\n", $head, 2);
        self::assertStringStartsWith('HTTP/1.1 200', $headHeaders);
        self::assertStringContainsString('Content-Length: 67108864', $headHeaders);
        self::assertSame('', $headBody);
        self::assertSame(200, $this->request('/upload', [
            '-F', 'field=parsed multipart field', '-F', 'file=@' . $this->directory . '/payload.bin;filename=report.txt',
        ]));
        $upload = json_decode(file_get_contents($this->directory . '/body'), true, flags: JSON_THROW_ON_ERROR);
        self::assertSame('report.txt', $upload['name']);
        self::assertSame(64 * 1024 * 1024, $upload['size']);
        self::assertSame($digest, $upload['sha256']);
        self::assertSame('parsed multipart field', $upload['field']);
        self::assertLessThanOrEqual(16 * 1024 * 1024, $upload['peak']);
    }

    public function testNativeUploadBoundariesAndBodylessFiles(): void
    {
        file_put_contents($this->directory . '/small.bin', '12345678');
        $file = 'file=@' . $this->directory . '/small.bin;filename=small.bin';
        self::assertSame(200, $this->request('/small-upload', ['-F', $file]));
        file_put_contents($this->directory . '/small.bin', '123456789');
        self::assertSame(413, $this->request('/small-upload', ['-F', $file]));
        self::assertSame('{"error":413}', file_get_contents($this->directory . '/body'));
        self::assertSame(400, $this->request('/upload', ['-F', 'field=no file']));
        self::assertSame(400, $this->request('/upload', ['-F', 'file[]=@' . $this->directory . '/small.bin']));
        self::assertSame(413, $this->request('/upload', [
            '-H', 'Content-Type: application/octet-stream', '--data-raw', str_repeat('x', 33),
        ]));
        self::assertSame(404, $this->request('/missing'));
        self::assertSame('{"error":404}', file_get_contents($this->directory . '/body'));
        file_put_contents($this->directory . '/small.bin', '');
        self::assertSame(200, $this->request('/small-upload', ['-F', $file]));
        $upload = json_decode(file_get_contents($this->directory . '/body'), true, flags: JSON_THROW_ON_ERROR);
        self::assertSame(0, $upload['size']);
        file_put_contents($this->directory . '/payload.bin', '');
        self::assertSame(200, $this->request('/download'));
        self::assertSame('', file_get_contents($this->directory . '/body'));
        self::assertStringContainsString('Content-Length: 0', file_get_contents($this->directory . '/headers'));
    }

    private function request(string $path, array $arguments = []): int
    {
        $process = proc_open(array_merge([
            'curl', '--disable', '--noproxy', '*', '--silent', '--show-error', '--max-time', '15',
            '--output', $this->directory . '/body', '--dump-header', $this->directory . '/headers',
            '--write-out', '%{http_code}',
        ], $arguments, ['http://' . $this->address . $path]), [0 => ['pipe', 'r'], 1 => ['pipe', 'w'],
            2 => ['file', $this->directory . '/curl.log', 'a']], $pipes);
        self::assertIsResource($process);
        fclose($pipes[0]);
        $status = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        self::assertSame(0, proc_close($process), file_get_contents($this->directory . '/curl.log'));
        return (int) $status;
    }
}
