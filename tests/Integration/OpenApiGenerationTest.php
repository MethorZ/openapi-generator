<?php

declare(strict_types=1);

namespace MethorZ\OpenApi\Tests\Integration;

use MethorZ\OpenApi\Analyzer\HandlerAnalyzer;
use MethorZ\OpenApi\Config\OpenApiConfig;
use MethorZ\OpenApi\Generator\DtoSchemaGenerator;
use MethorZ\OpenApi\Generator\RouteScanner;
use MethorZ\OpenApi\Generator\SecuritySchemeGenerator;
use MethorZ\OpenApi\Tests\Fixtures\ExampleDto;
use MethorZ\OpenApi\Tests\Fixtures\TestHandler;
use PHPUnit\Framework\TestCase;

/**
 * Integration tests for complete OpenAPI generation workflow
 */
final class OpenApiGenerationTest extends TestCase
{
    public function testGeneratesCompleteOpenApiSpec(): void
    {
        // Setup config with routes
        $config = [
            'routes' => [
                [
                    'path' => '/api/examples',
                    'allowed_methods' => ['GET', 'POST'],
                    'middleware' => [TestHandler::class],
                ],
                [
                    'path' => '/api/examples/{id}',
                    'allowed_methods' => ['GET', 'PUT', 'DELETE'],
                    'middleware' => [TestHandler::class],
                ],
            ],
        ];

        // Create components
        $handlerAnalyzer = new HandlerAnalyzer();
        $schemaGenerator = new DtoSchemaGenerator();
        $routeScanner = new RouteScanner($config, $handlerAnalyzer, $schemaGenerator);

        // Generate paths
        $paths = $routeScanner->scanRoutes();

        // Verify paths generated
        $this->assertNotEmpty($paths);
        $this->assertArrayHasKey('/api/examples', $paths);
        $this->assertArrayHasKey('/api/examples/{id}', $paths);

        // Verify operations
        $examplesPath = $paths['/api/examples'];
        $this->assertArrayHasKey('get', $examplesPath);
        $this->assertArrayHasKey('post', $examplesPath);

        // Generate schemas
        $schemaGenerator->generate(ExampleDto::class);
        $schemas = $schemaGenerator->getAllSchemas();

        $this->assertNotEmpty($schemas);
        $this->assertArrayHasKey('ExampleDto', $schemas);
    }

    public function testGeneratesOpenApiConfigFromArray(): void
    {
        $configArray = [
            'info' => [
                'title' => 'Test API',
                'version' => '1.0.0',
                'description' => 'API for testing',
            ],
            'servers' => [
                ['url' => 'https://api.example.com', 'description' => 'Production'],
                ['url' => 'http://localhost:8080', 'description' => 'Development'],
            ],
            'securitySchemes' => SecuritySchemeGenerator::bearerToken(),
            'tags' => [
                ['name' => 'Examples', 'description' => 'Example endpoints'],
            ],
        ];

        $config = OpenApiConfig::fromArray($configArray);

        $this->assertSame('Test API', $config->info['title']);
        $this->assertSame('1.0.0', $config->info['version']);
        $this->assertCount(2, $config->servers);
        $this->assertNotEmpty($config->securitySchemes);
    }

    public function testGeneratesSecuritySchemes(): void
    {
        $bearer = SecuritySchemeGenerator::bearerToken();
        $apiKey = SecuritySchemeGenerator::apiKey();
        $basic = SecuritySchemeGenerator::basicAuth();

        // Combine security schemes
        $securitySchemes = array_merge($bearer, $apiKey, $basic);

        $this->assertArrayHasKey('bearerAuth', $securitySchemes);
        $this->assertArrayHasKey('apiKey', $securitySchemes);
        $this->assertArrayHasKey('basicAuth', $securitySchemes);

        // Verify structure
        $this->assertSame('http', $securitySchemes['bearerAuth']['type']);
        $this->assertSame('apiKey', $securitySchemes['apiKey']['type']);
        $this->assertSame('http', $securitySchemes['basicAuth']['type']);
    }

    public function testHandlesEmptyRouteConfiguration(): void
    {
        $config = ['routes' => []];
        $routeScanner = new RouteScanner(
            $config,
            new HandlerAnalyzer(),
            new DtoSchemaGenerator(),
        );

        $paths = $routeScanner->scanRoutes();

        $this->assertIsArray($paths);
        $this->assertEmpty($paths);
    }

    public function testHandlesMultipleMethodsOnSamePath(): void
    {
        $config = [
            'routes' => [
                [
                    'path' => '/api/resource',
                    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'],
                    'middleware' => [TestHandler::class],
                ],
            ],
        ];

        $routeScanner = new RouteScanner(
            $config,
            new HandlerAnalyzer(),
            new DtoSchemaGenerator(),
        );

        $paths = $routeScanner->scanRoutes();

        $this->assertArrayHasKey('/api/resource', $paths);

        $operations = $paths['/api/resource'];
        $this->assertArrayHasKey('get', $operations);
        $this->assertArrayHasKey('post', $operations);
        $this->assertArrayHasKey('put', $operations);
        $this->assertArrayHasKey('patch', $operations);
        $this->assertArrayHasKey('delete', $operations);
    }
}
