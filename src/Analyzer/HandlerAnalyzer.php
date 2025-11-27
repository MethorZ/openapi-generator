<?php

declare(strict_types=1);

namespace MethorZ\OpenApi\Analyzer;

use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;

/**
 * Analyzes handler classes to extract request and response DTOs
 */
final class HandlerAnalyzer
{
    /**
     * Analyze handler and extract DTO information
     *
     * @return array{requestDto: string|null, responseDto: string|null}
     */
    public function analyze(string $handlerClass): array
    {
        if (!class_exists($handlerClass)) {
            return ['requestDto' => null, 'responseDto' => null];
        }

        $reflection = new ReflectionClass($handlerClass);

        if (!$reflection->hasMethod('__invoke')) {
            return ['requestDto' => null, 'responseDto' => null];
        }

        $method = $reflection->getMethod('__invoke');

        return [
            'requestDto' => $this->extractRequestDto($method),
            'responseDto' => $this->extractResponseDto($method),
        ];
    }

    /**
     * Extract request DTO from method parameters
     * Looks for any parameter that's not ServerRequestInterface
     */
    private function extractRequestDto(ReflectionMethod $method): ?string
    {
        foreach ($method->getParameters() as $parameter) {
            $type = $parameter->getType();

            if (!$type instanceof ReflectionNamedType) {
                continue;
            }

            $typeName = $type->getName();

            // Skip standard PSR-7 request
            if ($typeName === 'Psr\Http\Message\ServerRequestInterface') {
                continue;
            }

            // Skip primitive types
            if (in_array($typeName, ['string', 'int', 'float', 'bool', 'array'], true)) {
                continue;
            }

            // This must be a DTO parameter!
            if (class_exists($typeName)) {
                return $typeName;
            }
        }

        return null;
    }

    /**
     * Extract response DTO from return type
     */
    private function extractResponseDto(ReflectionMethod $method): ?string
    {
        $returnType = $method->getReturnType();

        if (!$returnType instanceof ReflectionNamedType) {
            return null;
        }

        $typeName = $returnType->getName();

        // Check if it's a class (likely a DTO)
        if (class_exists($typeName)) {
            return $typeName;
        }

        return null;
    }
}
