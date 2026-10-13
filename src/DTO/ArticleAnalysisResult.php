<?php

namespace App\DTO;

final readonly class ArticleAnalysisResult
{
    /**
     * @param string[] $tags
     */
    public function __construct(
        public ?string $locality,
        public string $summary,
        public array $tags
    ) {
    }
}