<?php
namespace App\Service;

use App\DTO\ArticleAnalysisResult;

final class FakeArticleAnalyzer implements ArticleAnalyzerInterface
{
    public function analyze(string $title, string $content): ArticleAnalysisResult
    {
        //Pour test erreur
        //throw new \RuntimeException('Erreur 503 : Service LLM momentanément indisponible.');
        
        sleep(1); // Simule un délai de traitement pour l'analyse de l'article.

        // Simule l'analyse d'un article en générant des données fictives.
        $locality = 'Plouguerneau';
        $summary = 'Ceci est un résumé synthétique généré automatiquement pour tester le flux asynchrone.';
        $tags = ['ia', 'symfony', 'actualité'];

        return new ArticleAnalysisResult($locality, $summary, $tags);
    }
}