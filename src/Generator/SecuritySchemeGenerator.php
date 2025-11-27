<?php

declare(strict_types=1);

namespace MethorZ\OpenApi\Generator;

/**
 * Generates OpenAPI security schemes
 *
 * Supports:
 * - Bearer tokens (JWT)
 * - API Keys
 * - Basic Authentication
 * - OAuth2
 */
final class SecuritySchemeGenerator
{
    /**
     * Generate Bearer Token (JWT) security scheme
     *
     * @return array<string, mixed>
     */
    public static function bearerToken(string $name = 'bearerAuth'): array
    {
        return [
            $name => [
                'type' => 'http',
                'scheme' => 'bearer',
                'bearerFormat' => 'JWT',
            ],
        ];
    }

    /**
     * Generate API Key security scheme
     *
     * @param string $in Location: header, query, or cookie
     * @return array<string, mixed>
     */
    public static function apiKey(string $name = 'apiKey', string $paramName = 'X-API-Key', string $in = 'header'): array
    {
        return [
            $name => [
                'type' => 'apiKey',
                'name' => $paramName,
                'in' => $in,
            ],
        ];
    }

    /**
     * Generate Basic Authentication security scheme
     *
     * @return array<string, mixed>
     */
    public static function basicAuth(string $name = 'basicAuth'): array
    {
        return [
            $name => [
                'type' => 'http',
                'scheme' => 'basic',
            ],
        ];
    }

    /**
     * Generate OAuth2 security scheme
     *
     * @param array<string, mixed> $flows
     * @return array<string, mixed>
     */
    public static function oauth2(string $name = 'oauth2', array $flows = []): array
    {
        return [
            $name => [
                'type' => 'oauth2',
                'flows' => $flows,
            ],
        ];
    }

    /**
     * Generate OAuth2 Authorization Code flow
     *
     * @param array<string, string> $scopes
     * @return array<string, mixed>
     */
    public static function oauth2AuthorizationCode(
        string $authorizationUrl,
        string $tokenUrl,
        array $scopes = [],
    ): array {
        return [
            'authorizationCode' => [
                'authorizationUrl' => $authorizationUrl,
                'tokenUrl' => $tokenUrl,
                'scopes' => $scopes,
            ],
        ];
    }

    /**
     * Generate OAuth2 Client Credentials flow
     *
     * @param array<string, string> $scopes
     * @return array<string, mixed>
     */
    public static function oauth2ClientCredentials(string $tokenUrl, array $scopes = []): array
    {
        return [
            'clientCredentials' => [
                'tokenUrl' => $tokenUrl,
                'scopes' => $scopes,
            ],
        ];
    }
}
