<?php

namespace App\MessageHandler;

use App\Entity\ArticleProcessingLog;
use App\Enum\ArticleStatus;
use App\Message\EnrichArticleMessage;
use App\Repository\ArticleRepository;
use App\Service\ArticleAnalyzerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class EnrichArticleMessageHandler
{
    public function __construct(
        private ArticleRepository $articleRepository,
        private ArticleAnalyzerInterface $articleAnalyzer,
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(EnrichArticleMessage $message): void
    {
        $article = $this->articleRepository->find($message->articleId);

        if ($article === null) {
            $this->logger->warning('EnrichArticleMessage ignoré : article introuvable.', [
                'article_id' => $message->articleId,
            ]);
            return;
        }

        // 1. Début de traitement : passage en PROCESSING 
        $article->setStatus(ArticleStatus::PROCESSING);
        $this->entityManager->persist(new ArticleProcessingLog($article, ArticleStatus::PROCESSING));
        $this->entityManager->flush();

        try {
            $this->logger->info('Démarrage de l\'enrichissement IA pour l\'article {id}.', [
                'id' => $article->getId(),
            ]);

            $analysis = $this->articleAnalyzer->analyze($article->getTitle(), $article->getContent());

            $article->setLocality($analysis->locality);
            $article->setSummary($analysis->summary);
            $article->setTags($analysis->tags);
            $article->setStatus(ArticleStatus::ENRICHED);

            
            $this->entityManager->persist(new ArticleProcessingLog($article, ArticleStatus::ENRICHED));
            $this->entityManager->flush();

            $this->logger->info('Article {id} enrichi avec succès.', [
                'id' => $article->getId(),
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Échec de l\'analyse pour l\'article {id} : {message}', [
                'id' => $article->getId(),
                'message' => $e->getMessage(),
                'exception' => $e,
            ]);

            $article->setStatus(ArticleStatus::FAILED);
            $this->entityManager->persist(
                new ArticleProcessingLog($article, ArticleStatus::FAILED, $e->getMessage())
            );
            $this->entityManager->flush();

            // Relance de l'erreur indispensable pour déclencher les retries Messenger
            throw $e;
        }
    }
}