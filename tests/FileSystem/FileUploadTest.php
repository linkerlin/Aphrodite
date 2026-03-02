<?php

declare(strict_types=1);

namespace Aphrodite\Tests\FileSystem;

require_once __DIR__ . '/../../src/FileSystem/FileUpload.php';

use Aphrodite\FileSystem\FileUpload;
use Aphrodite\FileSystem\UploadedFile;
use PHPUnit\Framework\TestCase;

class UploadedFileTest extends TestCase
{
    public function testCreateFromArray(): void
    {
        $file = [
            'name' => 'test.jpg',
            'type' => 'image/jpeg',
            'tmp_name' => '/tmp/php123',
            'error' => UPLOAD_ERR_OK,
            'size' => 1024,
        ];

        $uploaded = UploadedFile::createFromArray($file);

        $this->assertInstanceOf(UploadedFile::class, $uploaded);
        $this->assertEquals('test.jpg', $uploaded->getName());
        $this->assertEquals('image/jpeg', $uploaded->getType());
        $this->assertEquals(1024, $uploaded->getSize());
    }

    public function testCreateFromArrayWithNoFile(): void
    {
        $file = [
            'name' => '',
            'tmp_name' => '',
            'error' => UPLOAD_ERR_NO_FILE,
        ];

        $uploaded = UploadedFile::createFromArray($file);

        $this->assertNull($uploaded);
    }

    public function testGetClientExtension(): void
    {
        $file = new UploadedFile('test.jpg', 'image/jpeg', '/tmp/test', UPLOAD_ERR_OK, 1024);

        $this->assertEquals('jpg', $file->getClientExtension());
    }

    public function testHasError(): void
    {
        $fileOk = new UploadedFile('test.jpg', 'image/jpeg', '/tmp/test', UPLOAD_ERR_OK, 1024);
        $fileError = new UploadedFile('test.jpg', 'image/jpeg', '/tmp/test', UPLOAD_ERR_INI_SIZE, 1024);

        $this->assertFalse($fileOk->hasError());
        $this->assertTrue($fileError->hasError());
    }

    public function testGetErrorMessage(): void
    {
        $file = new UploadedFile('test.jpg', 'image/jpeg', '/tmp/test', UPLOAD_ERR_INI_SIZE, 1024);

        $this->assertEquals('File exceeds upload_max_filesize', $file->getErrorMessage());
    }

    public function testIsImage(): void
    {
        $imageFile = new UploadedFile('test.jpg', 'image/jpeg', '/tmp/test', UPLOAD_ERR_OK, 1024);
        $textFile = new UploadedFile('test.txt', 'text/plain', '/tmp/test', UPLOAD_ERR_OK, 1024);

        $this->assertTrue($imageFile->isImage());
        $this->assertFalse($textFile->isImage());
    }

    public function testGetMimeTypeFromProperty(): void
    {
        $file = new UploadedFile('test.jpg', 'image/jpeg', '/tmp/test', UPLOAD_ERR_OK, 1024);

        $this->assertEquals('image/jpeg', $file->getMimeType());
    }

    public function testGetContents(): void
    {
        $tempFile = sys_get_temp_dir() . '/test_file_' . uniqid() . '.txt';
        file_put_contents($tempFile, 'test content');

        $file = new UploadedFile('test.txt', 'text/plain', $tempFile, UPLOAD_ERR_OK, 12);

        $this->assertEquals('test content', $file->getContents());

        unlink($tempFile);
    }
}

class FileUploadTest extends TestCase
{
    private string $uploadPath;

    protected function setUp(): void
    {
        $this->uploadPath = sys_get_temp_dir() . '/aphrodite_upload_' . uniqid();
    }

    protected function tearDown(): void
    {
        if (is_dir($this->uploadPath)) {
            $files = glob($this->uploadPath . '/*');
            foreach ($files as $file) {
                unlink($file);
            }
            rmdir($this->uploadPath);
        }
    }

    public function testHandleSingleFile(): void
    {
        $handler = new FileUpload($this->uploadPath);

        $files = [
            'avatar' => [
                'name' => 'avatar.jpg',
                'type' => 'image/jpeg',
                'tmp_name' => sys_get_temp_dir() . '/php_test',
                'error' => UPLOAD_ERR_OK,
                'size' => 1024,
            ],
        ];

        // Create temp file for testing
        $tempFile = sys_get_temp_dir() . '/php_test';
        file_put_contents($tempFile, 'test');

        $result = $handler->handle($files);

        $this->assertArrayHasKey('avatar', $result);
        $this->assertInstanceOf(UploadedFile::class, $result['avatar']);

        unlink($tempFile);
    }

    public function testHandleNoFiles(): void
    {
        $handler = new FileUpload($this->uploadPath);

        $result = $handler->handle([]);

        $this->assertEmpty($result);
    }

    public function testMaxSizeValidation(): void
    {
        $handler = new FileUpload($this->uploadPath, [], 100);

        $tempFile = sys_get_temp_dir() . '/php_test_small';
        file_put_contents($tempFile, 'test content');

        $files = [
            'file' => [
                'name' => 'large.jpg',
                'type' => 'image/jpeg',
                'tmp_name' => $tempFile,
                'error' => UPLOAD_ERR_OK,
                'size' => 1024, // Larger than maxSize
            ],
        ];

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('exceeds maximum size');

        $handler->handle($files);

        unlink($tempFile);
    }

    public function testAllowedTypesValidation(): void
    {
        $handler = new FileUpload($this->uploadPath, ['image/png'], 0);

        $tempFile = sys_get_temp_dir() . '/php_test_png';
        file_put_contents($tempFile, 'test');

        $files = [
            'file' => [
                'name' => 'image.jpg',
                'type' => 'image/jpeg',
                'tmp_name' => $tempFile,
                'error' => UPLOAD_ERR_OK,
                'size' => 100,
            ],
        ];

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('is not allowed');

        $handler->handle($files);

        unlink($tempFile);
    }

    public function testCreatesUploadDirectory(): void
    {
        $path = sys_get_temp_dir() . '/aphrodite_nested/test/' . uniqid();
        
        $handler = new FileUpload($path);

        $this->assertDirectoryExists($path);

        // Cleanup
        rmdir($path);
    }
}
