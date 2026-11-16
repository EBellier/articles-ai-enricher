<?php

namespace App\Tests\Controller;

use App\Entity\Article;
use App\Entity\ArticleProcessingLog;
use App\Enum\AnalysisMode;
use App\Enum\ArticleStatus;
use App\Message\EnrichArticleMessage;
use App\MessageHandler\EnrichArticleMessageHandler;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

final class ArticleControllerTest extends WebTestCase
{
    private function resetDatabase(): void
    {
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $connection = $entityManager->getConnection();

        $connection->executeStatement('DELETE FROM article_processing_logs');
        $connection->executeStatement('DELETE FROM article');
    }

    public function testCreateDemoArticleDispatchesMessage(): void
    {
        $client = static::createClient();
        $this->resetDatabase();

        // 1. Appel du point d'entrée
        $client->request('POST', '/articles/demo');

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('id', $data);
        $this->assertSame('pending', $data['status']);


    }

    public function testCreateDemoArticleDispatchesSelectedAnalysisMode(): void
    {
        $client = static::createClient();
        $this->resetDatabase();

        $client->request(
            'POST',
            '/articles/demo',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode([
                'content' => 'Article de test',
                'mode' => 'gemini',
            ], JSON_THROW_ON_ERROR),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $envelopes = static::getContainer()->get('messenger.transport.async')->getSent();
        $this->assertCount(1, $envelopes);
        $this->assertInstanceOf(EnrichArticleMessage::class, $envelopes[0]->getMessage());
        $this->assertSame(AnalysisMode::GEMINI, $envelopes[0]->getMessage()->mode);
    }

    public function testShowArticleReturnsArticleDetails(): void
    {
        $client = static::createClient();
        $this->resetDatabase();
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);

        $article = new Article();
        $article->setTitle('Article de test');
        $article->setContent('Contenu de test');
        $article->setStatus(ArticleStatus::PENDING);
        $article->setLocality('Brest');
        $article->setSummary('Résumé de test');
        $article->setTags(['symfony', 'php']);

        $entityManager->persist($article);
        $entityManager->flush();

        $client->request('GET', '/articles/' . $article->getId());

        $this->assertResponseIsSuccessful();

        $data = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertSame('Article de test', $data['title']);
        $this->assertSame('Brest', $data['locality']);
        $this->assertSame(['symfony', 'php'], $data['tags']);
        $this->assertIsBool($data['workerRunning']);
    }

    public function testFullFlowCreatesArticleThenEnrichesIt(): void
    {
        $client = static::createClient();
        $this->resetDatabase();
        $container = static::getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);
        $handler = $container->get(EnrichArticleMessageHandler::class);

        $client->request('POST', '/articles/demo');

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $payload = json_decode((string) $client->getResponse()->getContent(), true);
        $articleId = $payload['id'];

        $this->assertNotNull($articleId);

        $entityManager->clear();
        $handler->__invoke(new EnrichArticleMessage($articleId));

        $entityManager->clear();
        $article = $entityManager->find(Article::class, $articleId);

/*         var_dump([
            'status' => $article?->getStatus()?->value,
            'locality' => $article?->getLocality(),
            'summary' => $article?->getSummary(),
            'tags' => $article?->getTags(),
        ]); */

        $this->assertNotNull($article);
        $this->assertSame(ArticleStatus::ENRICHED, $article->getStatus());
        $this->assertSame('Plouguerneau', $article->getLocality());
        $this->assertSame('Ceci est un résumé synthétique généré automatiquement pour tester le flux asynchrone.', $article->getSummary());
        $this->assertSame(['ia', 'symfony', 'actualité'], $article->getTags());

        $logs = $entityManager->getRepository(ArticleProcessingLog::class)->findBy(['article' => $article]);
        $this->assertCount(2, $logs);
        $this->assertSame(ArticleStatus::PROCESSING, $logs[0]->getStatus());
        $this->assertSame(ArticleStatus::ENRICHED, $logs[1]->getStatus());
    }

    public function testHomePageShowsEnrichAction(): void
    {
        $client = static::createClient();

        $client->request('GET', '/');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('.toolbar button', 'Enrichir l\'article');
        $this->assertSelectorExists('.mode-button[data-mode="fake"]');
        $this->assertSelectorExists('.mode-button[data-mode="gemini"]');
        $this->assertSelectorTextContains('body', 'Prêt');
    }
}