<?php

namespace App\Service;

use App\Enum\AnalysisMode;

readonly class ArticleAnalyzerResolver
{
    public function __construct(
        private FakeArticleAnalyzer $fakeAnalyzer,
        private GeminiArticleAnalyzer $geminiAnalyzer,
    ) {
    }

    public function resolve(AnalysisMode $mode): ArticleAnalyzerInterface
    {
        return match ($mode) {
            AnalysisMode::FAKE => $this->fakeAnalyzer,
            AnalysisMode::GEMINI => $this->geminiAnalyzer,
        };
    }
}
