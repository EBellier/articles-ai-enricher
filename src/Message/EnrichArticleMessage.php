<?php

namespace App\Message;

use App\Enum\AnalysisMode;

final readonly class EnrichArticleMessage
{
    public function __construct(
        public int $articleId,
        public AnalysisMode $mode = AnalysisMode::FAKE,
    ) {
    }
}