<?php

namespace App\Tests\MessageHandler;

use App\DTO\ArticleAnalysisResult;
use App\Entity\Article;
use App\Entity\ArticleProcessingLog;
use App\Enum\ArticleStatus;
use App\Message\EnrichArticleMessage;
use App\MessageHandler\EnrichArticleMessageHandler;
use App\Repository\ArticleRepository;
use App\Service\ArticleAnalyzerInterface;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class EnrichArticleMessageHandlerTest extends TestCase
{
    /** @var ArticleRepository&MockObject */
    private ArticleRepository $articleRepository;

    /** @var ArticleAnalyzerInterface&MockObject */
    private ArticleAnalyzerInterface $analyzer;

    /** @var EntityManagerInterface&MockObject */
    private EntityManagerInterface $entityManager;

    private EnrichArticleMessageHandler $handler;

    protected function setUp(): void
    {
        $this->articleRepository = $this->createMock(ArticleRepository::class);
        $this->analyzer = $this->createMock(ArticleAnalyzerInterface::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);

        $this->handler = new EnrichArticleMessageHandler(
            $this->articleRepository,
            $this->analyzer,
            $this->entityManager,
            new NullLogger()
        );
    }

    public function testHandlesSuccessfulEnrichment(): void
    {
        $article = new Article();
        $article->setTitle('Titre test');
        $article->setContent('Contenu test');
        $article->setStatus(ArticleStatus::PENDING);

        $this->articleRepository
            ->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($article);

        $this->analyzer
            ->expects($this->once())
            ->method('analyze')
            ->with('Titre test', 'Contenu test')
            ->willReturn(new ArticleAnalysisResult('Brest', 'Résumé test', ['tech', 'brest']));

        // On vérifie que les deux logs (PROCESSING puis ENRICHED) sont persistés
        $this->entityManager
            ->expects($this->exactly(2))
            ->method('persist')
            ->with($this->isInstanceOf(ArticleProcessingLog::class));

        $this->entityManager
            ->expects($this->exactly(2))
            ->method('flush');

        $this->handler->__invoke(new EnrichArticleMessage(1));

        $this->assertSame(ArticleStatus::ENRICHED, $article->getStatus());
        $this->assertSame('Brest', $article->getLocality());
        $this->assertSame('Résumé test', $article->getSummary());
        $this->assertSame(['tech', 'brest'], $article->getTags());
    }

    public function testHandlesFailureAndRethrowsException(): void
    {
        $article = new Article();
        $article->setTitle('Titre');
        $article->setContent('Contenu');

        $this->articleRepository
            ->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($article);

        $this->analyzer
            ->expects($this->once())
            ->method('analyze')
            ->with('Titre', 'Contenu')
            ->willThrowException(new \RuntimeException('Service LLM hors ligne'));

        $this->entityManager
            ->expects($this->exactly(2))
            ->method('persist')
            ->with($this->isInstanceOf(ArticleProcessingLog::class));

        $this->entityManager
            ->expects($this->exactly(2))
            ->method('flush');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Service LLM hors ligne');

        try {
            $this->handler->__invoke(new EnrichArticleMessage(1));
        } finally {
            $this->assertSame(ArticleStatus::FAILED, $article->getStatus());
        }
    }
}