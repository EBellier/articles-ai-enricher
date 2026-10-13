<?php

namespace App\Message;

final readonly class EnrichArticleMessage
{
    public function __construct(
        public int $articleId,
    ) {
    }
}