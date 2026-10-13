<?php

namespace App\Controller;


use App\Entity\Article;
use App\Enum\ArticleStatus;
use App\Message\EnrichArticleMessage;
use App\Repository\ArticleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;


#[Route('/articles')]
final class ArticleController extends AbstractController
{
    #[Route('/demo', name: 'app_article_demo', methods: ['POST','GET'])]
    public function createDemo(EntityManagerInterface $entityManager, MessageBusInterface $messageBus): JsonResponse
    {
        // 1. Enregistrement d'un article brut
        $article = new Article();
        $article->setTitle('Inauguration du nouveau pôle numérique à Brest');
        $article->setContent(
            'Ce lundi, la métropole brestoise a inauguré un nouvel espace dédié ' .
            'à l\'innovation technologique et aux startups locales. Plusieurs acteurs ' .
            'du territoire étaient présents pour célébrer cette étape majeure.'
        );
        $article->setStatus(ArticleStatus::PENDING);

        $entityManager->persist($article);
        $entityManager->flush();

        // 2. Dispatch du message asynchrone vers Messenger
        $messageBus->dispatch(new EnrichArticleMessage($article->getId()));

        return new JsonResponse([
            'message' => 'Article créé et mis en attente d\'enrichissement IA.', 
            'id' => $article->getId(),
            'status' => $article->getStatus()->value
            ], Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'app_article_show', methods: ['GET'])]
    public function show(Article $article): JsonResponse
    {
        return new JsonResponse([
            'id' => $article->getId(),
            'title' => $article->getTitle(),
            'status' => $article->getStatus()->value,
            'locality' => $article->getLocality(),
            'summary' => $article->getSummary(),
            'tags' => $article->getTags(),
            'createdAt' => $article->getCreatedAt()->format(\DateTimeInterface::ATOM),
            'updatedAt' => $article->getUpdatedAt()?->format(\DateTimeInterface::ATOM),
            'content' => $article->getContent(),
        
        ]);
    }
}
