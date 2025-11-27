<?php

declare(strict_types=1);

namespace MethorZ\OpenApi\Config;

use function file_exists;
use function file_get_contents;
use function is_array;
use function is_file;

/**
 * OpenAPI generator configuration
 *
 * Supports:
 * - Info section (title, version, description)
 * - Server definitions
 * - Security schemes
 * - Global tags
 * - Output paths
 *
 * Can be loaded from array or YAML file.
 */
final readonly class OpenApiConfig
{
    /**
     * @param array<string, mixed> $info
     * @param array<int, array<string, mixed>> $servers
     * @param array<string, mixed> $securitySchemes
     * @param array<int, array<string, string>> $tags
     * @param array<string> $security
     */
    public function __construct(
        public array $info = [],
        public array $servers = [],
        public array $securitySchemes = [],
        public array $tags = [],
        public array $security = [],
        public string $outputPath = 'docs/openapi.yaml',
        public bool $generateJson = true,
    ) {
    }

    /**
     * Create from array configuration
     *
     * @param array<string, mixed> $config
     */
    public static function fromArray(array $config): self
    {
        /** @var array<string, mixed> $info */
        $info = is_array($config['info'] ?? null) ? $config['info'] : [];

        /** @var array<int, array<string, mixed>> $servers */
        $servers = is_array($config['servers'] ?? null) ? $config['servers'] : [];

        /** @var array<string, mixed> $securitySchemes */
        $securitySchemes = is_array($config['securitySchemes'] ?? null) ? $config['securitySchemes'] : [];

        /** @var array<int, array<string, string>> $tags */
        $tags = is_array($config['tags'] ?? null) ? $config['tags'] : [];

        /** @var array<string> $security */
        $security = is_array($config['security'] ?? null) ? $config['security'] : [];

        /** @var string $outputPath */
        $outputPath = is_string($config['outputPath'] ?? null) ? $config['outputPath'] : 'docs/openapi.yaml';

        /** @var bool $generateJson */
        $generateJson = is_bool($config['generateJson'] ?? null) ? $config['generateJson'] : true;

        return new self(
            info: $info,
            servers: $servers,
            securitySchemes: $securitySchemes,
            tags: $tags,
            security: $security,
            outputPath: $outputPath,
            generateJson: $generateJson,
        );
    }

    /**
     * Create from YAML file
     */
    public static function fromYamlFile(string $path): self
    {
        if (! file_exists($path) || ! is_file($path)) {
            throw new \RuntimeException("Config file not found: {$path}");
        }

        $content = file_get_contents($path);

        if ($content === false) {
            throw new \RuntimeException("Failed to read config file: {$path}");
        }

        $config = \Symfony\Component\Yaml\Yaml::parse($content);

        if (! is_array($config)) {
            throw new \RuntimeException("Invalid config file format: {$path}");
        }

        return self::fromArray($config);
    }

    /**
     * Get default configuration
     */
    public static function default(): self
    {
        return new self(
            info: [
                'title' => 'API Documentation',
                'version' => '1.0.0',
                'description' => 'Generated API documentation',
            ],
            servers: [
                ['url' => 'http://localhost:8080', 'description' => 'Local development'],
            ],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'info' => $this->info,
            'servers' => $this->servers,
            'securitySchemes' => $this->securitySchemes,
            'tags' => $this->tags,
            'security' => $this->security,
            'outputPath' => $this->outputPath,
            'generateJson' => $this->generateJson,
        ];
    }
}

