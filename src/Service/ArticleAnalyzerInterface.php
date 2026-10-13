<?php

namespace App\Service;

use App\DTO\ArticleAnalysisResult;

interface ArticleAnalyzerInterface
{
    /**
     * Analyse le texte d'un article pour en extraire localité, résumé et mots-clés.
     */
    public function analyze(string $title, string $content): ArticleAnalysisResult;
}