<?php

namespace App\Controller;


use App\Entity\Article;
use App\Enum\AnalysisMode;
use App\Enum\ArticleStatus;
use App\Message\EnrichArticleMessage;
use App\Repository\ArticleRepository;
use App\Service\MessengerWorkerStatus;
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
    public function createDemo(EntityManagerInterface $entityManager, MessageBusInterface $messageBus, Request $request): JsonResponse
    {
        $rawContent = $request->getContent();
        $payload = [];

        if ($rawContent !== '') {
            $decoded = json_decode($rawContent, true, 512, JSON_THROW_ON_ERROR);
            if (is_array($decoded)) {
                $payload = $decoded;
            }
        }

        $content = trim((string) ($payload['content'] ?? ''));
        if ($content === '') {
            $content = 'Ce lundi, la métropole brestoise a inauguré un nouvel espace dédié à l\'innovation technologique et aux startups locales. Plusieurs acteurs du territoire étaient présents pour célébrer cette étape majeure.';
        }

        $mode = AnalysisMode::fromValue(isset($payload['mode']) ? (string) $payload['mode'] : 'fake');

        $article = new Article();
        $article->setTitle('Article soumis depuis l\'interface de démonstration');
        $article->setContent($content);
        $article->setStatus(ArticleStatus::PENDING);

        $entityManager->persist($article);
        $entityManager->flush();

        $messageBus->dispatch(new EnrichArticleMessage($article->getId(), $mode));

        return new JsonResponse([
            'message' => 'Article créé et mis en attente d\'enrichissement IA.',
            'id' => $article->getId(),
            'status' => $article->getStatus()->value,
        ], Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'app_article_show', methods: ['GET'])]
    public function show(Article $article, MessengerWorkerStatus $workerStatus): JsonResponse
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
            'workerRunning' => $workerStatus->isRunning(),
        
        ]);
    }
}
