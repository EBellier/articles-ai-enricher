<?php

namespace App\Tests\Controller;

use App\Entity\Article;
use App\Enum\ArticleStatus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

final class ArticleControllerTest extends WebTestCase
{
    public function testCreateDemoArticleDispatchesMessage(): void
    {
        $client = static::createClient();

        // 1. Appel du point d'entrée
        $client->request('POST', '/articles/demo');

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('id', $data);
        $this->assertSame('pending', $data['status']);


    }

    public function testShowArticleReturnsArticleDetails(): void
    {
        $client = static::createClient();
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
    }
}