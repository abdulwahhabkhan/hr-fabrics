<?php

namespace App\Enums;

use InvalidArgumentException;

enum FileType: string
{
    case Image = 'image';
    case PDF = 'pdf';
    case Word = 'word';
    case Excel = 'excel';

    public static function getFileType(string $mimeType): self
    {
        return match (true) {
            str_starts_with($mimeType, 'image/') => self::Image,
            $mimeType === 'application/pdf' => self::PDF,
            in_array($mimeType, ['application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document']) => self::Word,
            in_array($mimeType, ['application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']) => self::Excel,
            default => throw new InvalidArgumentException('Invalid file type'),
        };
    }

    public function icon(): ?string
    {
        return match ($this) {
            self::PDF => 'pdf',
            self::Word => 'word',
            self::Excel => 'excel',
            default => null,
        };
    }
}
