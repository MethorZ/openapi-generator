<?php

declare(strict_types=1);

namespace MethorZ\OpenApi\Tests\Unit\Generator;

use MethorZ\OpenApi\Generator\DtoSchemaGenerator;
use MethorZ\OpenApi\Tests\Fixtures\ExampleDto;
use PHPUnit\Framework\TestCase;

final class DtoSchemaGeneratorTest extends TestCase
{
    private DtoSchemaGenerator $generator;

    protected function setUp(): void
    {
        $this->generator = new DtoSchemaGenerator();
    }

    public function testGeneratesSchemaForDto(): void
    {
        $schema = $this->generator->generate(ExampleDto::class);

        $this->assertIsArray($schema);
        $this->assertArrayHasKey('type', $schema);
        $this->assertEquals('object', $schema['type']);
        $this->assertArrayHasKey('properties', $schema);
        $this->assertArrayHasKey('required', $schema);
    }

    public function testExtractsProperties(): void
    {
        $schema = $this->generator->generate(ExampleDto::class);

        $this->assertIsArray($schema['properties']);
        /** @var array<string, array<string, mixed>> $properties */
        $properties = $schema['properties'];
        $this->assertArrayHasKey('name', $properties);
        $this->assertArrayHasKey('email', $properties);
        $this->assertArrayHasKey('age', $properties);
        $this->assertArrayHasKey('optional', $properties);
    }

    public function testExtractsPropertyTypes(): void
    {
        $schema = $this->generator->generate(ExampleDto::class);

        $this->assertIsArray($schema['properties']);
        /** @var array<string, array<string, mixed>> $properties */
        $properties = $schema['properties'];
        $this->assertIsArray($properties['name']);
        $this->assertIsArray($properties['email']);
        $this->assertIsArray($properties['age']);
        $this->assertEquals('string', $properties['name']['type']);
        $this->assertEquals('string', $properties['email']['type']);
        $this->assertEquals('integer', $properties['age']['type']);
    }

    public function testExtractsLengthConstraints(): void
    {
        $schema = $this->generator->generate(ExampleDto::class);

        $this->assertIsArray($schema['properties']);
        /** @var array<string, array<string, mixed>> $properties */
        $properties = $schema['properties'];
        $this->assertIsArray($properties['name']);
        /** @var array<string, mixed> $nameProperty */
        $nameProperty = $properties['name'];
        $this->assertArrayHasKey('minLength', $nameProperty);
        $this->assertEquals(3, $nameProperty['minLength']);
        $this->assertArrayHasKey('maxLength', $nameProperty);
        $this->assertEquals(100, $nameProperty['maxLength']);
    }

    public function testExtractsRangeConstraints(): void
    {
        $schema = $this->generator->generate(ExampleDto::class);

        $this->assertIsArray($schema['properties']);
        /** @var array<string, array<string, mixed>> $properties */
        $properties = $schema['properties'];
        $this->assertIsArray($properties['age']);
        /** @var array<string, mixed> $ageProperty */
        $ageProperty = $properties['age'];
        $this->assertArrayHasKey('minimum', $ageProperty);
        $this->assertEquals(18, $ageProperty['minimum']);
        $this->assertArrayHasKey('maximum', $ageProperty);
        $this->assertEquals(120, $ageProperty['maximum']);
    }

    public function testExtractsEmailFormat(): void
    {
        $schema = $this->generator->generate(ExampleDto::class);

        $this->assertIsArray($schema['properties']);
        /** @var array<string, array<string, mixed>> $properties */
        $properties = $schema['properties'];
        $this->assertIsArray($properties['email']);
        /** @var array<string, mixed> $emailProperty */
        $emailProperty = $properties['email'];
        $this->assertArrayHasKey('format', $emailProperty);
        $this->assertEquals('email', $emailProperty['format']);
    }

    public function testMarksNullableProperties(): void
    {
        $schema = $this->generator->generate(ExampleDto::class);

        $this->assertIsArray($schema['properties']);
        /** @var array<string, array<string, mixed>> $properties */
        $properties = $schema['properties'];
        $this->assertIsArray($properties['optional']);
        /** @var array<string, mixed> $optionalProperty */
        $optionalProperty = $properties['optional'];
        $this->assertArrayHasKey('nullable', $optionalProperty);
        $this->assertTrue($optionalProperty['nullable']);
    }

    public function testIdentifiesRequiredProperties(): void
    {
        $schema = $this->generator->generate(ExampleDto::class);

        $this->assertIsArray($schema['required']);
        /** @var array<int, string> $required */
        $required = $schema['required'];
        // Has @Assert\NotBlank
        $this->assertContains('name', $required);
        // No @Assert\NotBlank, just @Assert\Email
        $this->assertNotContains('email', $required);
        // No @Assert\NotBlank, just @Assert\Range
        $this->assertNotContains('age', $required);
        // Nullable
        $this->assertNotContains('optional', $required);
    }

    public function testGetSchemaName(): void
    {
        $name = $this->generator->getSchemaName(ExampleDto::class);

        $this->assertEquals('ExampleDto', $name);
    }

    public function testReturnsEmptyArrayForNonExistentClass(): void
    {
        $schema = $this->generator->generate('NonExistentClass');

        $this->assertIsArray($schema);
        $this->assertEmpty($schema);
    }
}
