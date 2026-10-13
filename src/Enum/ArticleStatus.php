<?php 
namespace App\Enum;
enum ArticleStatus: string
{
    case PENDING = 'pending';
    case PROCESSING = 'processing';
    case ENRICHED = 'enriched';
    case FAILED = 'failed';
}