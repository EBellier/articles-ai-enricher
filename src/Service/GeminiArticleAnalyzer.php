<?php

namespace App\Service;

use App\DTO\ArticleAnalysisResult;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final readonly class GeminiArticleAnalyzer implements ArticleAnalyzerInterface
{
    public function __construct(
        private HttpClientInterface $geminiClient,
        private string $model = 'gemini-1.5-flash',
    ) {
    }

    public function analyze(string $title, string $content): ArticleAnalysisResult
    {
        $prompt = <<<PROMPT
Tu es un assistant éditorial pour la presse régionale.
Analyse le titre et le contenu suivants et renvoie STRICTEMENT un objet JSON respectant cette structure exacte :
{
  "summary": "Résumé journalistique percutant en une à deux phrases",
  "locality": "Commune ou localité principale bretonne/française identifiée (ou null si aucune n'est citée)",
  "tags": ["mot-cle-1", "mot-cle-2", "mot-cle-3"]
}

Titre : {$title}
Contenu : {$content}
PROMPT;

        $response = $this->geminiClient->request(
            'POST', 
            "models/{$this->model}:generateContent", 
            [

                'json' => [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $prompt],
                            ],
                        ],
                    ],
                    'generationConfig' => [
                        'responseMimeType' => 'application/json',
                        'temperature' => 0.2,
                    ],
                ],
        ]);

        $data = $response->toArray();
        $rawContent = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;

        if ($rawContent === null) {
            throw new \RuntimeException('Réponse invalide reçue de l\'API Gemini.');
        }

        $payload = json_decode($rawContent, true);

        if (!is_array($payload) || !isset($payload['summary'], $payload['tags'])) {
            throw new \RuntimeException('Le format JSON retourné par Gemini est invalide ou incomplet.');
        }

        return new ArticleAnalysisResult(
            locality: $payload['locality'] ?? null,
            summary: (string) $payload['summary'],
            tags: (array) $payload['tags'],
        );
    }
}