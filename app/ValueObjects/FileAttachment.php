<?php

namespace App\ValueObjects;

use Carbon\CarbonInterface;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class FileAttachment implements Arrayable
{
    public function __construct(
        public ?string $fileName = null,
        public ?string $filePath = null,
        public ?string $fileThumbnail = null,
        public ?string $directory = null,
        public ?string $createdBy = null,
        public ?CarbonInterface $createdAt = null
    ) {}

    public static function fromArray(array|Collection $data): self
    {
        return new self(
            fileName: $data['file_name'] ?? null,
            filePath: $data['file_path'] ?? null,
            directory: $data['directory'] ?? null,
            createdBy: $data['created_by'] ?? '',
            createdAt: Carbon::parse($data['created_at'] ?? null)
        );
    }

    public function toArray(): array
    {
        return [
            'file_name' => $this->fileName,
            'file_path' => $this->filePath,
            'file_thumbnail' => $this->fileThumbnail,
            'file_thumbnail_url' => generate_thumbnail($this->filePath),
            'directory' => $this->directory,
            'created_by' => $this->createdBy,
            'created_at' => $this->createdAt,
        ];
    }
}
