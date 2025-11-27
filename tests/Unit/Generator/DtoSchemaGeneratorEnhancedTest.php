<?php

declare(strict_types=1);

namespace MethorZ\OpenApi\Tests\Unit\Generator;

use MethorZ\OpenApi\Generator\DtoSchemaGenerator;
use MethorZ\OpenApi\Tests\Fixtures\AddressDto;
use MethorZ\OpenApi\Tests\Fixtures\ComplexDto;
use MethorZ\OpenApi\Tests\Fixtures\StatusEnum;
use PHPUnit\Framework\TestCase;

final class DtoSchemaGeneratorEnhancedTest extends TestCase
{
    private DtoSchemaGenerator $generator;

    protected function setUp(): void
    {
        $this->generator = new DtoSchemaGenerator();
    }

    public function testGeneratesSchemaForNestedDto(): void
    {
        $schema = $this->generator->generate(AddressDto::class);

        $this->assertIsArray($schema);
        $this->assertSame('object', $schema['type']);
        $this->assertArrayHasKey('properties', $schema);

        /** @var array<string, array<string, mixed>> $properties */
        $properties = $schema['properties'];

        $this->assertArrayHasKey('street', $properties);
        $this->assertArrayHasKey('city', $properties);
        $this->assertArrayHasKey('zipCode', $properties);
        $this->assertArrayHasKey('country', $properties);

        // Check required fields
        $this->assertArrayHasKey('required', $schema);
        /** @var array<int, string> $required */
        $required = $schema['required'];
        $this->assertContains('street', $required);
        $this->assertContains('city', $required);
        $this->assertContains('zipCode', $required);
        $this->assertNotContains('country', $required); // Nullable
    }

    public function testGeneratesSchemaWithEnumType(): void
    {
        $schema = $this->generator->generate(ComplexDto::class);

        $this->assertIsArray($schema);
        /** @var array<string, array<string, mixed>> $properties */
        $properties = $schema['properties'];

        $this->assertArrayHasKey('status', $properties);
        /** @var array<string, mixed> $statusProperty */
        $statusProperty = $properties['status'];

        $this->assertSame('string', $statusProperty['type']);
        $this->assertArrayHasKey('enum', $statusProperty);
        /** @var array<int, string> $enumValues */
        $enumValues = $statusProperty['enum'];
        $this->assertContains('draft', $enumValues);
        $this->assertContains('active', $enumValues);
        $this->assertContains('archived', $enumValues);
    }

    public function testGeneratesSchemaWithNestedDtoReference(): void
    {
        $schema = $this->generator->generate(ComplexDto::class);

        /** @var array<string, array<string, mixed>> $properties */
        $properties = $schema['properties'];

        // Check primaryAddress (required nested DTO)
        $this->assertArrayHasKey('primaryAddress', $properties);
        /** @var array<string, mixed> $primaryAddress */
        $primaryAddress = $properties['primaryAddress'];
        $this->assertArrayHasKey('$ref', $primaryAddress);
        $this->assertSame('#/components/schemas/AddressDto', $primaryAddress['$ref']);

        // Check billingAddress (nullable nested DTO)
        $this->assertArrayHasKey('billingAddress', $properties);
        /** @var array<string, mixed> $billingAddress */
        $billingAddress = $properties['billingAddress'];
        $this->assertArrayHasKey('$ref', $billingAddress);
        $this->assertTrue($billingAddress['nullable'] ?? false);
    }

    public function testGeneratesSchemaWithTypedArrayFromPhpDoc(): void
    {
        $schema = $this->generator->generate(ComplexDto::class);

        /** @var array<string, array<string, mixed>> $properties */
        $properties = $schema['properties'];

        // Check addresses array (array<AddressDto>)
        $this->assertArrayHasKey('addresses', $properties);
        /** @var array<string, mixed> $addresses */
        $addresses = $properties['addresses'];
        $this->assertSame('array', $addresses['type']);
        $this->assertArrayHasKey('items', $addresses);
        /** @var array<string, mixed> $items */
        $items = $addresses['items'];
        $this->assertArrayHasKey('$ref', $items);
        $this->assertSame('#/components/schemas/AddressDto', $items['$ref']);

        // Check tags array (array<string>)
        $this->assertArrayHasKey('tags', $properties);
        /** @var array<string, mixed> $tags */
        $tags = $properties['tags'];
        $this->assertSame('array', $tags['type']);
        $this->assertArrayHasKey('items', $tags);
        /** @var array<string, mixed> $tagItems */
        $tagItems = $tags['items'];
        $this->assertSame('string', $tagItems['type']);
    }

    public function testCachesGeneratedSchemas(): void
    {
        $schema1 = $this->generator->generate(AddressDto::class);
        $schema2 = $this->generator->generate(AddressDto::class);

        // Should return same instance (cached)
        $this->assertSame($schema1, $schema2);
    }

    public function testGetAllSchemasReturnsCache(): void
    {
        $this->generator->generate(AddressDto::class);
        $this->generator->generate(ComplexDto::class);

        $allSchemas = $this->generator->getAllSchemas();

        // getAllSchemas() returns schemas with short names (for OpenAPI components/schemas)
        $this->assertArrayHasKey('AddressDto', $allSchemas);
        $this->assertArrayHasKey('ComplexDto', $allSchemas);
    }

    public function testClearCacheResetsSchemas(): void
    {
        $this->generator->generate(AddressDto::class);
        $allSchemas = $this->generator->getAllSchemas();
        $this->assertNotEmpty($allSchemas);

        $this->generator->clearCache();
        $allSchemasAfter = $this->generator->getAllSchemas();
        $this->assertEmpty($allSchemasAfter);
    }

    public function testGetSchemaNameExtractsClassName(): void
    {
        $name = $this->generator->getSchemaName(AddressDto::class);
        $this->assertSame('AddressDto', $name);

        $name2 = $this->generator->getSchemaName(ComplexDto::class);
        $this->assertSame('ComplexDto', $name2);
    }
}

