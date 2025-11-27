<?php

declare(strict_types=1);

namespace MethorZ\OpenApi\Tests\Unit\Generator;

use MethorZ\OpenApi\Generator\SecuritySchemeGenerator;
use PHPUnit\Framework\TestCase;

final class SecuritySchemeGeneratorTest extends TestCase
{
    public function testBearerTokenGeneratesCorrectScheme(): void
    {
        $scheme = SecuritySchemeGenerator::bearerToken();

        $this->assertIsArray($scheme);
        $this->assertArrayHasKey('bearerAuth', $scheme);

        /** @var array<string, mixed> $bearerAuth */
        $bearerAuth = $scheme['bearerAuth'];
        $this->assertSame('http', $bearerAuth['type']);
        $this->assertSame('bearer', $bearerAuth['scheme']);
        $this->assertSame('JWT', $bearerAuth['bearerFormat']);
    }

    public function testBearerTokenWithCustomName(): void
    {
        $scheme = SecuritySchemeGenerator::bearerToken('customBearer');

        $this->assertArrayHasKey('customBearer', $scheme);
        $this->assertArrayNotHasKey('bearerAuth', $scheme);
    }

    public function testApiKeyGeneratesCorrectScheme(): void
    {
        $scheme = SecuritySchemeGenerator::apiKey();

        $this->assertIsArray($scheme);
        $this->assertArrayHasKey('apiKey', $scheme);

        /** @var array<string, mixed> $apiKey */
        $apiKey = $scheme['apiKey'];
        $this->assertSame('apiKey', $apiKey['type']);
        $this->assertSame('X-API-Key', $apiKey['name']);
        $this->assertSame('header', $apiKey['in']);
    }

    public function testApiKeyWithCustomParameters(): void
    {
        $scheme = SecuritySchemeGenerator::apiKey('customApi', 'api_key', 'query');

        $this->assertArrayHasKey('customApi', $scheme);

        /** @var array<string, mixed> $customApi */
        $customApi = $scheme['customApi'];
        $this->assertSame('apiKey', $customApi['type']);
        $this->assertSame('api_key', $customApi['name']);
        $this->assertSame('query', $customApi['in']);
    }

    public function testApiKeyInCookie(): void
    {
        $scheme = SecuritySchemeGenerator::apiKey('sessionKey', 'session_id', 'cookie');

        /** @var array<string, mixed> $sessionKey */
        $sessionKey = $scheme['sessionKey'];
        $this->assertSame('cookie', $sessionKey['in']);
    }

    public function testBasicAuthGeneratesCorrectScheme(): void
    {
        $scheme = SecuritySchemeGenerator::basicAuth();

        $this->assertIsArray($scheme);
        $this->assertArrayHasKey('basicAuth', $scheme);

        /** @var array<string, mixed> $basicAuth */
        $basicAuth = $scheme['basicAuth'];
        $this->assertSame('http', $basicAuth['type']);
        $this->assertSame('basic', $basicAuth['scheme']);
    }

    public function testBasicAuthWithCustomName(): void
    {
        $scheme = SecuritySchemeGenerator::basicAuth('customBasic');

        $this->assertArrayHasKey('customBasic', $scheme);
        $this->assertArrayNotHasKey('basicAuth', $scheme);
    }

    public function testOauth2GeneratesCorrectScheme(): void
    {
        $flows = [
            'authorizationCode' => [
                'authorizationUrl' => 'https://example.com/oauth/authorize',
                'tokenUrl' => 'https://example.com/oauth/token',
                'scopes' => ['read' => 'Read access', 'write' => 'Write access'],
            ],
        ];

        $scheme = SecuritySchemeGenerator::oauth2('oauth', $flows);

        $this->assertIsArray($scheme);
        $this->assertArrayHasKey('oauth', $scheme);

        /** @var array<string, mixed> $oauth */
        $oauth = $scheme['oauth'];
        $this->assertSame('oauth2', $oauth['type']);
        $this->assertArrayHasKey('flows', $oauth);
        $this->assertSame($flows, $oauth['flows']);
    }

    public function testOauth2WithEmptyFlows(): void
    {
        $scheme = SecuritySchemeGenerator::oauth2();

        /** @var array<string, mixed> $oauth */
        $oauth = $scheme['oauth2'];
        $this->assertArrayHasKey('flows', $oauth);
        $this->assertEmpty($oauth['flows']);
    }

    public function testOauth2AuthorizationCodeFlowGeneration(): void
    {
        $flow = SecuritySchemeGenerator::oauth2AuthorizationCode(
            'https://example.com/oauth/authorize',
            'https://example.com/oauth/token',
            ['read' => 'Read access', 'write' => 'Write access'],
        );

        $this->assertIsArray($flow);
        $this->assertArrayHasKey('authorizationCode', $flow);

        /** @var array<string, mixed> $authCode */
        $authCode = $flow['authorizationCode'];
        $this->assertSame('https://example.com/oauth/authorize', $authCode['authorizationUrl']);
        $this->assertSame('https://example.com/oauth/token', $authCode['tokenUrl']);
        $this->assertArrayHasKey('scopes', $authCode);

        /** @var array<string, string> $scopes */
        $scopes = $authCode['scopes'];
        $this->assertCount(2, $scopes);
        $this->assertArrayHasKey('read', $scopes);
        $this->assertArrayHasKey('write', $scopes);
    }

    public function testOauth2AuthorizationCodeFlowWithEmptyScopes(): void
    {
        $flow = SecuritySchemeGenerator::oauth2AuthorizationCode(
            'https://example.com/oauth/authorize',
            'https://example.com/oauth/token',
        );

        /** @var array<string, mixed> $authCode */
        $authCode = $flow['authorizationCode'];
        $this->assertArrayHasKey('scopes', $authCode);
        $this->assertEmpty($authCode['scopes']);
    }

    public function testOauth2ClientCredentialsFlowGeneration(): void
    {
        $flow = SecuritySchemeGenerator::oauth2ClientCredentials(
            'https://example.com/oauth/token',
            ['api.read' => 'Read API', 'api.write' => 'Write API'],
        );

        $this->assertIsArray($flow);
        $this->assertArrayHasKey('clientCredentials', $flow);

        /** @var array<string, mixed> $clientCreds */
        $clientCreds = $flow['clientCredentials'];
        $this->assertSame('https://example.com/oauth/token', $clientCreds['tokenUrl']);
        $this->assertArrayHasKey('scopes', $clientCreds);

        /** @var array<string, string> $scopes */
        $scopes = $clientCreds['scopes'];
        $this->assertCount(2, $scopes);
    }

    public function testOauth2ClientCredentialsFlowWithEmptyScopes(): void
    {
        $flow = SecuritySchemeGenerator::oauth2ClientCredentials('https://example.com/oauth/token');

        /** @var array<string, mixed> $clientCreds */
        $clientCreds = $flow['clientCredentials'];
        $this->assertArrayHasKey('scopes', $clientCreds);
        $this->assertEmpty($clientCreds['scopes']);
    }
}
