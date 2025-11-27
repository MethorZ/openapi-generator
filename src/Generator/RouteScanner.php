<?php

declare(strict_types=1);

namespace MethorZ\OpenApi\Generator;

use MethorZ\OpenApi\Analyzer\HandlerAnalyzer;

/**
 * Scans application routes and generates OpenAPI paths
 */
final class RouteScanner
{
    /**
     * @param array<string, mixed> $config
     */
    public function __construct(
        private readonly array $config,
        private readonly HandlerAnalyzer $handlerAnalyzer,
        private readonly DtoSchemaGenerator $schemaGenerator,
    ) {
    }

    /**
     * Scan all routes and generate OpenAPI paths
     *
     * @return array<string, array<string, mixed>>
     */
    public function scanRoutes(): array
    {
        /** @var array<int, array<string, mixed>> $routes */
        $routes = $this->config['routes'] ?? [];
        $paths = [];

        /** @var array<string, mixed> $route */
        foreach ($routes as $route) {
            $path = $route['path'] ?? null;

            if (!$path) {
                continue;
            }

            $operations = $this->generateOperations($route);

            if (!empty($operations)) {
                if (!isset($paths[$path])) {
                    $paths[$path] = [];
                }

                $paths[$path] = array_merge($paths[$path], $operations);
            }
        }

        return $paths;
    }

    /**
     * @param array<string, mixed> $route
     * @return array<string, array<string, mixed>>
     */
    private function generateOperations(array $route): array
    {
        $handlerClass = $this->extractHandlerClass($route);

        if (!$handlerClass || !class_exists($handlerClass)) {
            return [];
        }

        // Analyze handler to extract DTOs
        $handlerInfo = $this->handlerAnalyzer->analyze($handlerClass);

        $operations = [];
        /** @var array<int, string> $methods */
        $methods = $route['allowed_methods'] ?? [];

        foreach ($methods as $httpMethod) {
            $operation = $this->generateOperation($handlerClass, $httpMethod, $route, $handlerInfo);

            if ($operation) {
                $methodName = strtolower($httpMethod);
                $operations[$methodName] = $operation;
            }
        }

        return $operations;
    }

    /**
     * @param array<string, mixed> $route
     */
    private function extractHandlerClass(array $route): ?string
    {
        $middleware = $route['middleware'] ?? [];

        // Handler is typically the last middleware (or only item)
        if (empty($middleware)) {
            return null;
        }

        $handler = is_array($middleware) ? end($middleware) : $middleware;

        if (is_string($handler) && class_exists($handler)) {
            return $handler;
        }

        return null;
    }

    /**
     * @param array<string, mixed> $route
     * @param array{requestDto: string|null, responseDto: string|null} $handlerInfo
     * @return array<string, mixed>
     */
    private function generateOperation(
        string $handlerClass,
        string $httpMethod,
        array $route,
        array $handlerInfo,
    ): array {
        $tag = $this->extractTag($handlerClass);
        $summary = $this->generateSummary($handlerClass, $httpMethod);
        $operationId = $this->generateOperationId($handlerClass);

        $pathValue = $route['path'] ?? '';
        $path = is_string($pathValue) ? $pathValue : '';
        $parameters = $this->extractPathParameters($path);

        $operation = [
            'operationId' => $operationId,
            'summary' => $summary,
            'tags' => [$tag],
        ];

        // Add parameters
        if (!empty($parameters)) {
            $operation['parameters'] = $parameters;
        }

        // Add request body (for POST, PUT, PATCH)
        if ($handlerInfo['requestDto'] && in_array($httpMethod, ['POST', 'PUT', 'PATCH'], true)) {
            $operation['requestBody'] = $this->generateRequestBody($handlerInfo['requestDto']);
        }

        // Always add responses
        if ($handlerInfo['responseDto']) {
            $operation['responses'] = $this->generateResponses($handlerInfo['responseDto'], $httpMethod);
        } else {
            // Default responses if no DTO
            $operation['responses'] = $this->generateDefaultResponses($httpMethod);
        }

        return $operation;
    }

    private function extractTag(string $handlerClass): string
    {
        // Extract module from namespace: Item\Application\Handler\GetItemHandler → Items
        $parts = explode('\\', $handlerClass);

        foreach ($parts as $part) {
            if (!in_array($part, ['Application', 'Handler', 'Command'], true)) {
                // Found module name - pluralize it
                return $part . 's';
            }
        }

        return 'API';
    }

    private function generateSummary(string $handlerClass, string $httpMethod): string
    {
        $className = class_basename($handlerClass);
        $className = str_replace('Handler', '', $className);

        // Split camel case: GetItem → ["Get", "Item"]
        $words = preg_split('/(?=[A-Z])/', $className, -1, PREG_SPLIT_NO_EMPTY);

        if ($words === false) {
            return 'Operation';
        }

        return implode(' ', array_map('strtolower', $words));
    }

    private function generateOperationId(string $handlerClass): string
    {
        $className = class_basename($handlerClass);
        $className = str_replace('Handler', '', $className);

        return lcfirst($className);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function extractPathParameters(string $path): array
    {
        // Extract path parameters like {id} or {id:[regex]}
        preg_match_all('/\{([^:}]+)(?::[^}]+)?\}/', $path, $matches);

        $parameters = [];

        foreach ($matches[1] as $paramName) {
            $type = 'string';
            $format = null;

            // Infer type from name
            if (in_array($paramName, ['id', 'uuid'], true)) {
                $format = 'uuid';
            } elseif (str_contains($paramName, '_id') || str_ends_with($paramName, 'Id')) {
                $type = 'integer';
            }

            $param = [
                'name' => $paramName,
                'in' => 'path',
                'required' => true,
                'schema' => ['type' => $type],
            ];

            if ($format) {
                $param['schema']['format'] = $format;
            }

            $parameters[] = $param;
        }

        return $parameters;
    }

    /**
     * @return array<string, mixed>
     */
    private function generateRequestBody(string $requestDtoClass): array
    {
        $schemaName = $this->schemaGenerator->getSchemaName($requestDtoClass);

        return [
            'required' => true,
            'content' => [
                'application/json' => [
                    'schema' => [
                        '$ref' => "#/components/schemas/{$schemaName}",
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array<int|string, array<string, mixed>>
     */
    private function generateResponses(string $responseDtoClass, string $httpMethod): array
    {
        $schemaName = $this->schemaGenerator->getSchemaName($responseDtoClass);

        // Determine success status code based on method
        $successCode = match ($httpMethod) {
            'POST' => 201,
            'DELETE' => 204,
            default => 200,
        };

        $responses = [];

        // Success response
        if ($successCode === 204) {
            $responses[$successCode] = [
                'description' => 'No Content',
            ];
        } else {
            $responses[$successCode] = [
                'description' => 'Success',
                'content' => [
                    'application/json' => [
                        'schema' => [
                            '$ref' => "#/components/schemas/{$schemaName}",
                        ],
                    ],
                ],
            ];
        }

        // Common error responses
        $responses[400] = [
            'description' => 'Bad Request',
        ];

        $responses[404] = [
            'description' => 'Not Found',
        ];

        return $responses;
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function generateDefaultResponses(string $httpMethod): array
    {
        $successCode = match ($httpMethod) {
            'POST' => 201,
            'DELETE' => 204,
            default => 200,
        };

        return [
            $successCode => ['description' => 'Success'],
            400 => ['description' => 'Bad Request'],
            404 => ['description' => 'Not Found'],
        ];
    }
}

/**
 * Helper function to get class basename
 */
if (!function_exists('class_basename')) {
    function class_basename(string $class): string
    {
        return basename(str_replace('\\', '/', $class));
    }
}
