<?php

declare(strict_types=1);

namespace MethorZ\OpenApi\Tests\Unit\Generator;

use MethorZ\OpenApi\Generator\TypeResolver;
use MethorZ\OpenApi\Tests\Fixtures\AddressDto;
use MethorZ\OpenApi\Tests\Fixtures\StatusEnum;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionUnionType;

final class TypeResolverTest extends TestCase
{
    private TypeResolver $resolver;

    protected function setUp(): void
    {
        $this->resolver = new TypeResolver();
    }

    public function testMapPhpTypeToOpenApiForInteger(): void
    {
        $result = $this->resolver->mapPhpTypeToOpenApi('int');
        $this->assertSame('integer', $result);
    }

    public function testMapPhpTypeToOpenApiForFloat(): void
    {
        $result = $this->resolver->mapPhpTypeToOpenApi('float');
        $this->assertSame('number', $result);
    }

    public function testMapPhpTypeToOpenApiForBoolean(): void
    {
        $result = $this->resolver->mapPhpTypeToOpenApi('bool');
        $this->assertSame('boolean', $result);
    }

    public function testMapPhpTypeToOpenApiForArray(): void
    {
        $result = $this->resolver->mapPhpTypeToOpenApi('array');
        $this->assertSame('array', $result);
    }

    public function testMapPhpTypeToOpenApiForString(): void
    {
        $result = $this->resolver->mapPhpTypeToOpenApi('string');
        $this->assertSame('string', $result);
    }

    public function testMapPhpTypeToOpenApiForUnknownDefaultsToString(): void
    {
        $result = $this->resolver->mapPhpTypeToOpenApi('SomeCustomClass');
        $this->assertSame('string', $result);
    }

    public function testGenerateEnumSchemaForBackedEnum(): void
    {
        $schema = $this->resolver->generateEnumSchema(StatusEnum::class);

        $this->assertIsArray($schema);
        $this->assertArrayHasKey('type', $schema);
        $this->assertArrayHasKey('enum', $schema);
        $this->assertSame('string', $schema['type']);

        /** @var array<string> $enum */
        $enum = $schema['enum'];
        $this->assertContains('draft', $enum);
        $this->assertContains('active', $enum);
        $this->assertContains('archived', $enum);
    }

    public function testGenerateEnumSchemaForNonExistentClassReturnsStringType(): void
    {
        $schema = $this->resolver->generateEnumSchema('NonExistentEnum');

        $this->assertIsArray($schema);
        $this->assertArrayHasKey('type', $schema);
        $this->assertSame('string', $schema['type']);
        $this->assertArrayNotHasKey('enum', $schema);
    }

    public function testGenerateArraySchemaForSimpleArray(): void
    {
        $reflection = new ReflectionClass(AddressDto::class);
        $property = $reflection->getProperty('street');

        $schema = $this->resolver->generateArraySchema(
            $property,
            static fn (string $class) => 'TestSchema',
        );

        $this->assertIsArray($schema);
        $this->assertSame('array', $schema['type']);
    }

    public function testGenerateUnionTypeSchemaWithMultipleTypes(): void
    {
        // Create a test class with union type property
        $testClass = new class {
            public string|int $value;
        };

        $reflection = new ReflectionClass($testClass);
        $property = $reflection->getProperty('value');
        $type = $property->getType();

        $this->assertInstanceOf(ReflectionUnionType::class, $type);

        /** @var ReflectionUnionType $type */
        $schema = $this->resolver->generateUnionTypeSchema(
            $type,
            static fn (string $class) => 'TestSchema',
        );

        $this->assertIsArray($schema);
        $this->assertArrayHasKey('oneOf', $schema);

        /** @var array<int, array<string, string>> $oneOf */
        $oneOf = $schema['oneOf'];
        $this->assertCount(2, $oneOf);
    }

    public function testGenerateUnionTypeSchemaWithClassTypes(): void
    {
        // Create a test class with union of classes
        $testClass = new class {
            public AddressDto|StatusEnum $value;
        };

        $reflection = new ReflectionClass($testClass);
        $property = $reflection->getProperty('value');
        $type = $property->getType();

        $this->assertInstanceOf(ReflectionUnionType::class, $type);

        /** @var ReflectionUnionType $type */
        $schema = $this->resolver->generateUnionTypeSchema(
            $type,
            static fn (string $class) => basename(str_replace('\\', '/', $class)),
        );

        $this->assertIsArray($schema);
        $this->assertArrayHasKey('oneOf', $schema);

        /** @var array<int, array<string, mixed>> $oneOf */
        $oneOf = $schema['oneOf'];
        $this->assertCount(2, $oneOf);

        // Should have references to schemas
        $hasRef = false;

        foreach ($oneOf as $item) {
            if (isset($item['$ref'])) {
                $hasRef = true;

                break;
            }
        }

        $this->assertTrue($hasRef, 'Union type with classes should generate $ref');
    }
}
