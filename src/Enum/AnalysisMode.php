<?php

namespace App\Enum;

enum AnalysisMode: string
{
    case FAKE = 'fake';
    case GEMINI = 'gemini';

    public static function fromValue(?string $value): self
    {
        return match (strtolower((string) $value)) {
            'gemini', 'withgemini' => self::GEMINI,
            default => self::FAKE,
        };
    }
}
