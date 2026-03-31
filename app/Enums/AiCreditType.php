<?php

declare(strict_types=1);

namespace App\Enums;

enum AiCreditType: string
{
    case Chat = 'chat';
    case Summary = 'summary';
    case Embedding = 'embedding';
}
