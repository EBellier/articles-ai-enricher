<?php

namespace App\Entity;

use App\Enum\ArticleStatus;
use App\Repository\ArticleProcessingLogRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ArticleProcessingLogRepository::class)]
#[ORM\Table(name: 'article_processing_logs')]
class ArticleProcessingLog
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Article::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Article $article = null;

    #[ORM\Column(length: 30, enumType: ArticleStatus::class)]
    private ArticleStatus $status;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $errorMessage = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;


    public function __construct(Article $article, ArticleStatus $status, ?string $errorMessage = null)
    {
        $this->article = $article;
        $this->status = $status;
        $this->errorMessage = $errorMessage;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getArticle(): ?Article
    {
        return $this->article;
    }
    public function getStatus(): ArticleStatus
    {
        return $this->status;
    }
    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }
    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
