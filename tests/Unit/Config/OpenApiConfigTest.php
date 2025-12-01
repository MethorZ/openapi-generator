<?php

declare(strict_types=1);

namespace MethorZ\OpenApi\Tests\Unit\Config;

use MethorZ\OpenApi\Config\OpenApiConfig;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class OpenApiConfigTest extends TestCase
{
    public function testConstructorWithDefaultValues(): void
    {
        $config = new OpenApiConfig();

        $this->assertIsArray($config->info);
        $this->assertEmpty($config->info);
        $this->assertIsArray($config->servers);
        $this->assertEmpty($config->servers);
        $this->assertSame('docs/openapi.yaml', $config->outputPath);
        $this->assertTrue($config->generateJson);
    }

    public function testConstructorWithCustomValues(): void
    {
        $info = ['title' => 'Test API', 'version' => '1.0.0'];
        $servers = [['url' => 'https://api.example.com']];
        $securitySchemes = ['bearerAuth' => ['type' => 'http', 'scheme' => 'bearer']];
        $tags = [['name' => 'Users', 'description' => 'User management']];
        $security = ['bearerAuth'];

        $config = new OpenApiConfig(
            info: $info,
            servers: $servers,
            securitySchemes: $securitySchemes,
            tags: $tags,
            security: $security,
            outputPath: 'custom/path.yaml',
            generateJson: false,
        );

        $this->assertSame($info, $config->info);
        $this->assertSame($servers, $config->servers);
        $this->assertSame($securitySchemes, $config->securitySchemes);
        $this->assertSame($tags, $config->tags);
        $this->assertSame($security, $config->security);
        $this->assertSame('custom/path.yaml', $config->outputPath);
        $this->assertFalse($config->generateJson);
    }

    public function testFromArrayWithCompleteConfig(): void
    {
        $array = [
            'info' => ['title' => 'API', 'version' => '2.0.0'],
            'servers' => [['url' => 'https://api.test.com']],
            'securitySchemes' => ['apiKey' => ['type' => 'apiKey']],
            'tags' => [['name' => 'Products']],
            'security' => ['apiKey'],
            'outputPath' => 'output/api.yaml',
            'generateJson' => false,
        ];

        $config = OpenApiConfig::fromArray($array);

        $this->assertSame($array['info'], $config->info);
        $this->assertSame($array['servers'], $config->servers);
        $this->assertSame($array['securitySchemes'], $config->securitySchemes);
        $this->assertSame($array['tags'], $config->tags);
        $this->assertSame($array['security'], $config->security);
        $this->assertSame('output/api.yaml', $config->outputPath);
        $this->assertFalse($config->generateJson);
    }

    public function testFromArrayWithPartialConfigUsesDefaults(): void
    {
        $array = [
            'info' => ['title' => 'Minimal API'],
        ];

        $config = OpenApiConfig::fromArray($array);

        $this->assertSame(['title' => 'Minimal API'], $config->info);
        $this->assertIsArray($config->servers);
        $this->assertEmpty($config->servers);
        $this->assertSame('docs/openapi.yaml', $config->outputPath);
        $this->assertTrue($config->generateJson);
    }

    public function testFromArrayWithEmptyArray(): void
    {
        $config = OpenApiConfig::fromArray([]);

        $this->assertIsArray($config->info);
        $this->assertEmpty($config->info);
        $this->assertIsArray($config->servers);
        $this->assertEmpty($config->servers);
        $this->assertSame('docs/openapi.yaml', $config->outputPath);
        $this->assertTrue($config->generateJson);
    }

    public function testDefaultReturnsConfigWithDefaultValues(): void
    {
        $config = OpenApiConfig::default();

        $this->assertArrayHasKey('title', $config->info);
        $this->assertSame('API Documentation', $config->info['title']);
        $this->assertArrayHasKey('version', $config->info);
        $this->assertSame('1.0.0', $config->info['version']);
        $this->assertArrayHasKey('description', $config->info);

        $this->assertNotEmpty($config->servers);
        $this->assertCount(1, $config->servers);

        /** @var array<string, string> $server */
        $server = $config->servers[0];
        $this->assertArrayHasKey('url', $server);
        $this->assertSame('http://localhost:8080', $server['url']);
    }

    public function testToArrayConvertsConfigToArray(): void
    {
        $info = ['title' => 'Test', 'version' => '1.0'];
        $servers = [['url' => 'https://test.com']];

        $config = new OpenApiConfig(
            info: $info,
            servers: $servers,
            outputPath: 'test/output.yaml',
            generateJson: false,
        );

        $array = $config->toArray();

        $this->assertArrayHasKey('info', $array);
        $this->assertSame($info, $array['info']);
        $this->assertArrayHasKey('servers', $array);
        $this->assertSame($servers, $array['servers']);
        $this->assertArrayHasKey('outputPath', $array);
        $this->assertSame('test/output.yaml', $array['outputPath']);
        $this->assertArrayHasKey('generateJson', $array);
        $this->assertFalse($array['generateJson']);
    }

    public function testToArrayIncludesAllProperties(): void
    {
        $config = new OpenApiConfig();
        $array = $config->toArray();

        $this->assertArrayHasKey('info', $array);
        $this->assertArrayHasKey('servers', $array);
        $this->assertArrayHasKey('securitySchemes', $array);
        $this->assertArrayHasKey('tags', $array);
        $this->assertArrayHasKey('security', $array);
        $this->assertArrayHasKey('outputPath', $array);
        $this->assertArrayHasKey('generateJson', $array);
    }

    public function testFromYamlFileThrowsExceptionForNonExistentFile(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Config file not found:');

        OpenApiConfig::fromYamlFile('/nonexistent/file.yaml');
    }
}
