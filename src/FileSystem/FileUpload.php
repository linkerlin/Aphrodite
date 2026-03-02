<?php

declare(strict_types=1);

namespace Aphrodite\FileSystem;

/**
 * Uploaded file representation.
 */
class UploadedFile
{
    protected string $name;
    protected string $type;
    protected string $tmpName;
    protected int $error;
    protected int $size;

    public function __construct(
        string $name,
        string $type,
        string $tmpName,
        int $error,
        int $size
    ) {
        $this->name = $name;
        $this->type = $type;
        $this->tmpName = $tmpName;
        $this->error = $error;
        $this->size = $size;
    }

    /**
     * Create from $_FILES array.
     */
    public static function createFromArray(array $file): ?self
    {
        if (empty($file['name']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        return new self(
            $file['name'],
            $file['type'] ?? '',
            $file['tmp_name'],
            $file['error'],
            $file['size']
        );
    }

    /**
     * Get original name.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get client extension.
     */
    public function getClientExtension(): string
    {
        return pathinfo($this->name, PATHINFO_EXTENSION);
    }

    /**
     * Get MIME type.
     */
    public function getMimeType(): string
    {
        if ($this->type) {
            return $this->type;
        }

        if (function_exists('finfo_file')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $this->tmpName);
            finfo_close($finfo);
            return $mime;
        }

        return 'application/octet-stream';
    }

    /**
     * Get file size.
     */
    public function getSize(): int
    {
        return $this->size;
    }

    /**
     * Get temp path.
     */
    public function getTempPath(): string
    {
        return $this->tmpName;
    }

    /**
     * Get error code.
     */
    public function getError(): int
    {
        return $this->error;
    }

    /**
     * Check if upload has error.
     */
    public function hasError(): bool
    {
        return $this->error !== UPLOAD_ERR_OK;
    }

    /**
     * Get error message.
     */
    public function getErrorMessage(): string
    {
        return match ($this->error) {
            UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE',
            UPLOAD_ERR_PARTIAL => 'File only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temp folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write to disk',
            UPLOAD_ERR_EXTENSION => 'Upload stopped by extension',
            default => 'Unknown error',
        };
    }

    /**
     * Move to new location.
     */
    public function move(string $to, ?string $name = null): bool
    {
        $name = $name ?? $this->name;
        $path = rtrim($to, '/') . '/' . $name;

        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        return move_uploaded_file($this->tmpName, $path);
    }

    /**
     * Copy to new location (non-uploaded files).
     */
    public function copy(string $to, ?string $name = null): bool
    {
        $name = $name ?? $this->name;
        $path = rtrim($to, '/') . '/' . $name;

        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        return copy($this->tmpName, $path);
    }

    /**
     * Get contents.
     */
    public function getContents(): string|false
    {
        return file_get_contents($this->tmpName);
    }

    /**
     * Check if file is image.
     */
    public function isImage(): bool
    {
        $mime = $this->getMimeType();
        return in_array($mime, [
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp',
            'image/svg+xml',
            'image/bmp',
        ]);
    }
}

/**
 * File upload handler.
 */
class FileUpload
{
    protected string $uploadPath;
    protected array $allowedTypes = [];
    protected int $maxSize = 0;
    protected bool $renameDuplicates = true;

    public function __construct(
        string $uploadPath,
        array $allowedTypes = [],
        int $maxSize = 0
    ) {
        $this->uploadPath = rtrim($uploadPath, '/');
        $this->allowedTypes = $allowedTypes;
        $this->maxSize = $maxSize;

        if (!is_dir($this->uploadPath)) {
            mkdir($this->uploadPath, 0755, true);
        }
    }

    /**
     * Handle uploaded files from request.
     */
    public function handle(array $files): array
    {
        $uploaded = [];

        foreach ($files as $key => $file) {
            if (is_array($file['name'])) {
                // Multiple files
                $uploaded[$key] = $this->handleMultiple($file);
            } else {
                // Single file
                $uploadedFile = $this->process($file);
                if ($uploadedFile) {
                    $uploaded[$key] = $uploadedFile;
                }
            }
        }

        return $uploaded;
    }

    /**
     * Handle multiple files.
     */
    protected function handleMultiple(array $file): array
    {
        $results = [];
        $count = count($file['name']);

        for ($i = 0; $i < $count; $i++) {
            $singleFile = [
                'name' => $file['name'][$i] ?? '',
                'type' => $file['type'][$i] ?? '',
                'tmp_name' => $file['tmp_name'][$i] ?? '',
                'error' => $file['error'][$i] ?? UPLOAD_ERR_NO_FILE,
                'size' => $file['size'][$i] ?? 0,
            ];

            $uploadedFile = $this->process($singleFile);
            if ($uploadedFile) {
                $results[] = $uploadedFile;
            }
        }

        return $results;
    }

    /**
     * Process single uploaded file.
     */
    protected function process(array $file): ?UploadedFile
    {
        $uploadedFile = UploadedFile::createFromArray($file);

        if (!$uploadedFile) {
            return null;
        }

        // Check for errors
        if ($uploadedFile->hasError()) {
            throw new \RuntimeException(
                "Upload error for {$uploadedFile->getName()}: " 
                . $uploadedFile->getErrorMessage()
            );
        }

        // Check file size
        if ($this->maxSize > 0 && $uploadedFile->getSize() > $this->maxSize) {
            throw new \RuntimeException(
                "File {$uploadedFile->getName()} exceeds maximum size of {$this->maxSize} bytes"
            );
        }

        // Check allowed types
        if (!empty($this->allowedTypes)) {
            $mime = $uploadedFile->getMimeType();
            if (!in_array($mime, $this->allowedTypes)) {
                throw new \RuntimeException(
                    "File type {$mime} is not allowed"
                );
            }
        }

        // Generate unique name if needed
        $name = $uploadedFile->getName();
        if ($this->renameDuplicates) {
            $name = $this->generateUniqueName($name);
        }

        // Move file
        $uploadedFile->move($this->uploadPath, $name);

        return $uploadedFile;
    }

    /**
     * Generate unique filename.
     */
    protected function generateUniqueName(string $name): string
    {
        $info = pathinfo($name);
        $base = $info['filename'];
        $ext = $info['extension'] ?? '';

        $counter = 1;
        $newName = $name;

        while (file_exists($this->uploadPath . '/' . $newName)) {
            $newName = $base . '_' . $counter . ($ext ? ".{$ext}" : '');
            $counter++;
        }

        return $newName;
    }

    /**
     * Set configuration.
     */
    public function setConfig(array $config): self
    {
        foreach ($config as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
        return $this;
    }
}
