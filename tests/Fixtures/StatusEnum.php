<?php

declare(strict_types=1);

namespace MethorZ\OpenApi\Tests\Fixtures;

/**
 * Example enum for testing enum type support
 */
enum StatusEnum: string
{
    case DRAFT = 'draft';
    case ACTIVE = 'active';
    case ARCHIVED = 'archived';
}

