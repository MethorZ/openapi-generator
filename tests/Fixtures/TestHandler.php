<?php

declare(strict_types=1);

namespace MethorZ\OpenApi\Tests\Fixtures;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Test handler with request and response DTOs
 */
final class TestHandler implements RequestHandlerInterface
{
    public function __invoke(ServerRequestInterface $request, ExampleDto $dto): ExampleDto
    {
        return $dto;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        throw new \RuntimeException('Not implemented');
    }
}

