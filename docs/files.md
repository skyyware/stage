# Transfer files

Requires `skyyware/stage` 0.1.3 or later. PHP receives multipart uploads into
temporary files before Stage dispatches the request. `UploadedFile` validates
one upload; `FileResponse` streams a regular file as an attachment. Both leave
storage and access decisions in your application.

## Run a local round trip

Create a new application:

```sh
mkdir stage-files
cd stage-files
composer require skyyware/stage:^0.1.3
mkdir public
```

Create `public/index.php`:

```php
<?php
declare(strict_types=1);

use Stage\Http\Application;
use Stage\Http\FileResponse;
use Stage\Http\Route;
use Stage\Http\UploadedFile;

require dirname(__DIR__) . '/vendor/autoload.php';

(new Application(
	new Route('POST', '/files', function (): FileResponse {
		$file = UploadedFile::fromPhp($_FILES['file'] ?? [], maxBytes: 10_000_000);
		return new FileResponse($file->path, $file->name, [
			'cache-control' => 'no-store',
			'content-security-policy' => "default-src 'none'; sandbox",
		]);
	}),
))->run();
```

Start a local server with PHP upload limits above the application's 10 MB limit.
PHP's `M` settings use powers of 1024; the application's limit is decimal bytes.

```sh
php -d upload_max_filesize=10M -d post_max_size=11M \
  -d output_buffering=0 -d zlib.output_compression=0 \
  -S 127.0.0.1:8080 -t public public/index.php
```

In a second terminal, from the application directory:

```sh
printf 'hello from Stage\n' > example.txt
curl --fail --form file=@example.txt \
  --dump-header headers.txt --output returned.txt http://127.0.0.1:8080/files
cmp example.txt returned.txt
curl --include --request POST http://127.0.0.1:8080/files
```

`cmp` succeeds without output. The download has an attachment disposition,
`application/octet-stream`, `nosniff`, and the file's length. The request without
a file returns 400 with `{"error":400}`. Stop the server with Ctrl+C.

This local example returns the temporary upload immediately and retains
nothing. PHP removes the temporary file when the request ends. To keep a file,
validate the caller and application rules, then use `move_uploaded_file` with
an application-generated destination outside `public/`. Check the move's result
before recording success. Never use the client name as a storage path.

## Serve a stored file

After the application has authorized access and resolved its own storage path,
return a `FileResponse` from a GET handler:

```php
return new FileResponse($authorizedPath, $displayName, [
	'cache-control' => 'no-store',
	'x-robots-tag' => 'noindex',
	'content-security-policy' => "default-src 'none'; sandbox",
]);
```

Its optional headers survive file delivery. A HEAD request uses the GET route
and receives the same length without a body. GET handlers must account for
that fallback before recording download counts or other side effects. Do not
rebuild this response from `$response->body`; that property is empty because
the file is not buffered. The [HTTP reference](http.md#file-downloads) lists
validation, errors, and current limitations.

## Set limits at each boundary

Stage's raw request limit remains 1 MiB. It does not limit PHP-parsed multipart
data. Keep `enable_post_data_reading` enabled and read parsed fields from
`$_POST`. Configure `upload_max_filesize`, a larger `post_max_size`, a writable
upload temporary directory, and server request limits and timeouts. PHP's
[upload documentation](https://www.php.net/manual/en/features.file-upload.post-method.php)
describes this lifecycle.

If the whole POST exceeds `post_max_size`, PHP leaves both input arrays empty.
`UploadedFile::fromPhp` then reports a missing file with 400. To report 413 for
that case, enforce a total request limit in the server or application adapter
before dispatch. Application validation cannot prevent PHP receiving a request
before the script runs. See PHP's
[core configuration](https://www.php.net/manual/en/ini.core.php#ini.post-max-size).

Stage reads downloads in 64 KiB chunks. Disable application output buffers and
compression that would accumulate or transform the body. Keep stored files
unchanged during delivery. Disk failure or concurrent truncation can end a
response early after headers have been sent. File access rules, content checks,
storage quotas, concurrent use, and retention need application-level tests.
