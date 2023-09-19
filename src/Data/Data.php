<?php

declare(strict_types = 1);

namespace DigitalCreative\Filepond\Data;

use Illuminate\Support\Facades\Storage;

class Data
{
    public function __construct(
        public string $path,
        public string $filename,
        public string $disk,
    )
    {
    }

    public static function make(string $path, string $filename, string $disk): static
    {
        return new static($path, $filename, $disk);
    }

    public static function fromEncrypted(string $serverId): static
    {
        return decrypt($serverId);
    }

    public function encrypt(): string
    {
        return encrypt($this);
    }

    public function absolutePath(): string
    {
        return Storage::disk($this->disk)->path($this->path);
    }

    public function deleteDirectory(): bool
    {
        return Storage::disk($this->disk)->deleteDirectory(dirname($this->path));
    }

    public function deleteFile(): bool
    {
        return Storage::disk($this->disk)->delete($this->path);
    }
}
