<?php

declare(strict_types=1);

namespace MethorZ\OpenApi\Tests\Unit\Generator;

use MethorZ\OpenApi\Generator\ConstraintExtractor;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use Symfony\Component\Validator\Constraints as Assert;

final class ConstraintExtractorTest extends TestCase
{
    private ConstraintExtractor $extractor;

    protected function setUp(): void
    {
        $this->extractor = new ConstraintExtractor();
    }

    public function testApplyConstraintsWithNotBlankSetsRequired(): void
    {
        $testClass = new class {
            #[Assert\NotBlank]
            public string $name;
        };

        $property = new ReflectionProperty($testClass, 'name');
        $schema = [];
        $required = false;

        $this->extractor->applyConstraints($property, $schema, $required);

        $this->assertTrue($required);
    }

    public function testApplyConstraintsWithUuidSetsFormat(): void
    {
        $testClass = new class {
            #[Assert\Uuid]
            public string $id;
        };

        $property = new ReflectionProperty($testClass, 'id');
        $schema = [];
        $required = false;

        $this->extractor->applyConstraints($property, $schema, $required);

        $this->assertArrayHasKey('format', $schema);
        $this->assertSame('uuid', $schema['format']);
    }

    public function testApplyConstraintsWithLengthSetsMinAndMax(): void
    {
        $testClass = new class {
            #[Assert\Length(min: 3, max: 100)]
            public string $username;
        };

        $property = new ReflectionProperty($testClass, 'username');
        $schema = [];
        $required = false;

        $this->extractor->applyConstraints($property, $schema, $required);

        $this->assertArrayHasKey('minLength', $schema);
        $this->assertArrayHasKey('maxLength', $schema);
        $this->assertSame(3, $schema['minLength']);
        $this->assertSame(100, $schema['maxLength']);
    }

    public function testApplyConstraintsWithLengthOnlyMin(): void
    {
        $testClass = new class {
            #[Assert\Length(min: 5)]
            public string $code;
        };

        $property = new ReflectionProperty($testClass, 'code');
        $schema = [];
        $required = false;

        $this->extractor->applyConstraints($property, $schema, $required);

        $this->assertArrayHasKey('minLength', $schema);
        $this->assertArrayNotHasKey('maxLength', $schema);
        $this->assertSame(5, $schema['minLength']);
    }

    public function testApplyConstraintsWithRangeSetsMinAndMax(): void
    {
        $testClass = new class {
            #[Assert\Range(min: 1, max: 100)]
            public int $age;
        };

        $property = new ReflectionProperty($testClass, 'age');
        $schema = [];
        $required = false;

        $this->extractor->applyConstraints($property, $schema, $required);

        $this->assertArrayHasKey('minimum', $schema);
        $this->assertArrayHasKey('maximum', $schema);
        $this->assertSame(1, $schema['minimum']);
        $this->assertSame(100, $schema['maximum']);
    }

    public function testApplyConstraintsWithEmailSetsFormat(): void
    {
        $testClass = new class {
            #[Assert\Email]
            public string $email;
        };

        $property = new ReflectionProperty($testClass, 'email');
        $schema = [];
        $required = false;

        $this->extractor->applyConstraints($property, $schema, $required);

        $this->assertArrayHasKey('format', $schema);
        $this->assertSame('email', $schema['format']);
    }

    public function testApplyConstraintsWithUrlSetsFormat(): void
    {
        $testClass = new class {
            #[Assert\Url]
            public string $website;
        };

        $property = new ReflectionProperty($testClass, 'website');
        $schema = [];
        $required = false;

        $this->extractor->applyConstraints($property, $schema, $required);

        $this->assertArrayHasKey('format', $schema);
        $this->assertSame('uri', $schema['format']);
    }

    public function testApplyConstraintsWithMultipleConstraints(): void
    {
        $testClass = new class {
            #[Assert\Email]
            #[Assert\Length(min: 3, max: 50)]
            #[Assert\NotBlank]
            public string $email;
        };

        $property = new ReflectionProperty($testClass, 'email');
        $schema = [];
        $required = false;

        $this->extractor->applyConstraints($property, $schema, $required);

        $this->assertTrue($required);
        $this->assertArrayHasKey('minLength', $schema);
        $this->assertArrayHasKey('maxLength', $schema);
        $this->assertArrayHasKey('format', $schema);
        $this->assertSame(3, $schema['minLength']);
        $this->assertSame(50, $schema['maxLength']);
        $this->assertSame('email', $schema['format']);
    }

    public function testApplyConstraintsWithNoConstraints(): void
    {
        $testClass = new class {
            public string $plainProperty;
        };

        $property = new ReflectionProperty($testClass, 'plainProperty');
        $schema = [];
        $required = false;

        $this->extractor->applyConstraints($property, $schema, $required);

        $this->assertFalse($required);
        $this->assertEmpty($schema);
    }

    public function testHasUuidConstraintReturnsTrueWhenPresent(): void
    {
        $testClass = new class {
            #[Assert\Uuid]
            public string $id;
        };

        $property = new ReflectionProperty($testClass, 'id');
        $result = $this->extractor->hasUuidConstraint($property);

        $this->assertTrue($result);
    }

    public function testHasUuidConstraintReturnsFalseWhenAbsent(): void
    {
        $testClass = new class {
            public string $name;
        };

        $property = new ReflectionProperty($testClass, 'name');
        $result = $this->extractor->hasUuidConstraint($property);

        $this->assertFalse($result);
    }

    public function testHasUuidConstraintReturnsFalseWithOtherConstraints(): void
    {
        $testClass = new class {
            #[Assert\Email]
            #[Assert\NotBlank]
            public string $email;
        };

        $property = new ReflectionProperty($testClass, 'email');
        $result = $this->extractor->hasUuidConstraint($property);

        $this->assertFalse($result);
    }
}
