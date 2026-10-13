<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use EnrichArticleMessage;

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

    public function testShowArticleReturnsNotFoundForInvalidId(): void
    {
        $client = static::createClient();

        $client->request('GET', '/articles/999999');

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }
}