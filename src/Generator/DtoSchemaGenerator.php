<?php

declare(strict_types=1);

namespace MethorZ\OpenApi\Generator;

use ReflectionClass;
use ReflectionNamedType;
use ReflectionProperty;
use ReflectionUnionType;

/**
 * Generates OpenAPI schemas from DTO classes
 *
 * Coordinates schema generation by delegating responsibilities to:
 * - TypeResolver: Converts PHP types to OpenAPI types
 * - ConstraintExtractor: Extracts validation constraints
 *
 * Supports:
 * - Basic PHP types (string, int, float, bool, array)
 * - Nested DTOs (references other DTO classes)
 * - Collections with typed items (array<Type>)
 * - Enums (backed enums with string/int values)
 * - Nullable types (?Type)
 * - Union types (Type1|Type2)
 * - Symfony Validator constraints
 */
final class DtoSchemaGenerator
{
    private readonly TypeResolver $typeResolver;
    private readonly ConstraintExtractor $constraintExtractor;

    /**
     * @var array<string, array<string, mixed>> Schema cache
     */
    private array $schemaCache = [];

    /**
     * @var array<int, string> Processing stack for circular reference detection
     */
    private array $processingStack = [];

    public function __construct(?TypeResolver $typeResolver = null, ?ConstraintExtractor $constraintExtractor = null)
    {
        $this->typeResolver = $typeResolver ?? new TypeResolver();
        $this->constraintExtractor = $constraintExtractor ?? new ConstraintExtractor();
    }

    /**
     * Generate OpenAPI schema from DTO class
     *
     * @return array<string, mixed>
     */
    public function generate(string $dtoClass): array
    {
        if (!class_exists($dtoClass)) {
            return [];
        }

        // Check cache first
        if (isset($this->schemaCache[$dtoClass])) {
            return $this->schemaCache[$dtoClass];
        }

        // Detect circular references
        if (in_array($dtoClass, $this->processingStack, true)) {
            // Return a reference instead of the full schema
            return ['$ref' => '#/components/schemas/' . $this->getSchemaName($dtoClass)];
        }

        // Add to processing stack
        $this->processingStack[] = $dtoClass;

        $reflection = new ReflectionClass($dtoClass);
        $properties = [];
        $required = [];

        // Get properties from class properties
        foreach ($reflection->getProperties() as $property) {
            if ($property->isStatic()) {
                continue;
            }

            $propertySchema = $this->extractPropertySchema($property);
            $properties[$property->getName()] = $propertySchema['schema'];

            if ($propertySchema['required']) {
                $required[] = $property->getName();
            }
        }

        $schema = [
            'type' => 'object',
            'properties' => $properties,
        ];

        if (!empty($required)) {
            $schema['required'] = $required;
        }

        // Remove from processing stack
        array_pop($this->processingStack);

        // Cache the result
        $this->schemaCache[$dtoClass] = $schema;

        return $schema;
    }

    /**
     * Get schema name from DTO class
     */
    public function getSchemaName(string $dtoClass): string
    {
        $parts = explode('\\', $dtoClass);

        return end($parts);
    }

    /**
     * Extract schema for a single property
     *
     * @return array{schema: array<string, mixed>, required: bool}
     */
    private function extractPropertySchema(ReflectionProperty $property): array
    {
        $required = false;
        $nullable = false;
        $schema = [];

        // Extract from PHP type
        $type = $property->getType();

        if ($type instanceof ReflectionNamedType) {
            $phpType = $type->getName();
            $nullable = $type->allowsNull();

            // Handle different type categories
            if (enum_exists($phpType)) {
                // Enum type
                $schema = $this->typeResolver->generateEnumSchema($phpType);
            } elseif (class_exists($phpType)) {
                // Nested DTO or other class
                $schema = $this->generateNestedDtoSchema($phpType);
            } elseif ($phpType === 'array') {
                // Array - check PHPDoc for item type
                $schema = $this->typeResolver->generateArraySchema(
                    $property,
                    fn (string $class) => $this->getSchemaName($class)
                );
            } else {
                // Scalar type
                $schema['type'] = $this->typeResolver->mapPhpTypeToOpenApi($phpType);

                // Check for UUID format
                if ($phpType === 'string' && $this->constraintExtractor->hasUuidConstraint($property)) {
                    $schema['format'] = 'uuid';
                }
            }
        } elseif ($type instanceof ReflectionUnionType) {
            // Union type - generate oneOf
            $schema = $this->typeResolver->generateUnionTypeSchema(
                $type,
                fn (string $class) => $this->getSchemaName($class)
            );
            $nullable = $type->allowsNull();
        }

        // Extract from Symfony Validator attributes
        $this->constraintExtractor->applyConstraints($property, $schema, $required);

        // Set nullable flag (for all types including refs)
        if ($nullable) {
            $schema['nullable'] = true;
        }

        return [
            'schema' => $schema,
            'required' => $required && !$nullable,
        ];
    }

    /**
     * Generate schema for nested DTO
     *
     * @param class-string $dtoClass
     * @return array<string, mixed>
     */
    private function generateNestedDtoSchema(string $dtoClass): array
    {
        // Generate reference to the nested DTO
        return [
            '$ref' => '#/components/schemas/' . $this->getSchemaName($dtoClass),
        ];
    }

    /**
     * Get all generated schemas (for components/schemas section)
     *
     * @return array<string, array<string, mixed>>
     */
    public function getAllSchemas(): array
    {
        $result = [];

        foreach ($this->schemaCache as $dtoClass => $schema) {
            $schemaName = $this->getSchemaName($dtoClass);
            $result[$schemaName] = $schema;
        }

        return $result;
    }

    /**
     * Reset schema cache (useful for testing)
     */
    public function clearCache(): void
    {
        $this->schemaCache = [];
        $this->processingStack = [];
    }
}
