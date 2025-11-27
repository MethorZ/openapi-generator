<?php

declare(strict_types=1);

namespace MethorZ\OpenApi\Generator;

use ReflectionClass;
use ReflectionEnum;
use ReflectionNamedType;
use ReflectionProperty;
use ReflectionUnionType;

/**
 * Resolves PHP types to OpenAPI schema types
 *
 * Handles conversion of PHP native types, enums, arrays, and union types
 * to their OpenAPI schema equivalents.
 */
final readonly class TypeResolver
{
    /**
     * Map PHP type to OpenAPI type
     */
    public function mapPhpTypeToOpenApi(string $phpType): string
    {
        return match ($phpType) {
            'int' => 'integer',
            'float' => 'number',
            'bool' => 'boolean',
            'array' => 'array',
            default => 'string',
        };
    }

    /**
     * Generate schema for enum type
     *
     * @param class-string $enumClass
     * @return array<string, mixed>
     */
    public function generateEnumSchema(string $enumClass): array
    {
        if (!enum_exists($enumClass)) {
            return ['type' => 'string'];
        }

        /** @var class-string<\UnitEnum> $enumClass */
        $reflection = new ReflectionEnum($enumClass);

        if (!$reflection->isBacked()) {
            // Unit enum - just list the names
            return [
                'type' => 'string',
                'enum' => array_map(static fn ($case) => $case->name, $reflection->getCases()),
            ];
        }

        // Backed enum - get the backing values
        $values = [];
        $backingType = $reflection->getBackingType();

        foreach ($reflection->getCases() as $case) {
            /** @var \BackedEnum $enumInstance */
            $enumInstance = $case->getValue();
            $values[] = $enumInstance->value;
        }

        // Default
        $openApiType = 'string';

        if ($backingType instanceof ReflectionNamedType) {
            $openApiType = $this->mapPhpTypeToOpenApi($backingType->getName());
        }

        return [
            'type' => $openApiType,
            'enum' => $values,
        ];
    }

    /**
     * Generate schema for array with optional item type from PHPDoc
     *
     * @param callable(string): string $getSchemaName Callback to get schema name for DTO
     * @return array<string, mixed>
     */
    public function generateArraySchema(ReflectionProperty $property, callable $getSchemaName): array
    {
        $schema = ['type' => 'array'];

        // Try to extract item type from PHPDoc
        $itemType = $this->extractArrayItemType($property);

        if ($itemType !== null) {
            if (enum_exists($itemType)) {
                $schema['items'] = $this->generateEnumSchema($itemType);
            } elseif (class_exists($itemType)) {
                $schema['items'] = [
                    '$ref' => '#/components/schemas/' . $getSchemaName($itemType),
                ];
            } else {
                $schema['items'] = ['type' => $this->mapPhpTypeToOpenApi($itemType)];
            }
        }

        return $schema;
    }

    /**
     * Generate schema for union type
     *
     * @param callable(string): string $getSchemaName Callback to get schema name for DTO
     * @return array<string, mixed>
     */
    public function generateUnionTypeSchema(ReflectionUnionType $unionType, callable $getSchemaName): array
    {
        $types = [];

        foreach ($unionType->getTypes() as $type) {
            if (!$type instanceof ReflectionNamedType) {
                continue;
            }

            $typeName = $type->getName();

            // Skip null type (handled by nullable flag)
            if ($typeName === 'null') {
                continue;
            }

            if (enum_exists($typeName)) {
                $types[] = $this->generateEnumSchema($typeName);
            } elseif (class_exists($typeName)) {
                $types[] = [
                    '$ref' => '#/components/schemas/' . $getSchemaName($typeName),
                ];
            } else {
                $types[] = ['type' => $this->mapPhpTypeToOpenApi($typeName)];
            }
        }

        if (count($types) === 1) {
            return $types[0];
        }

        return ['oneOf' => $types];
    }

    /**
     * Extract array item type from PHPDoc
     *
     * Supports: @param array<Type> and @param array<int, Type>
     */
    private function extractArrayItemType(ReflectionProperty $property): ?string
    {
        $class = $property->getDeclaringClass();

        // Try constructor PHPDoc first (for property promotion)
        $constructor = $class->getConstructor();

        if ($constructor !== null) {
            $docComment = $constructor->getDocComment();

            if ($docComment !== false) {
                $itemType = $this->parseArrayItemTypeFromDoc($docComment, $property->getName(), $class);

                if ($itemType !== null) {
                    return $itemType;
                }
            }
        }

        // Try class-level PHPDoc
        $docComment = $class->getDocComment();

        if ($docComment !== false) {
            return $this->parseArrayItemTypeFromDoc($docComment, $property->getName(), $class);
        }

        return null;
    }

    /**
     * Parse array item type from PHPDoc comment
     *
     * @param ReflectionClass<object> $class
     */
    private function parseArrayItemTypeFromDoc(
        string $docComment,
        string $propertyName,
        ReflectionClass $class,
    ): ?string {
        // Match: @param array<Type> or @param array<int, Type> $propertyName
        $pattern = '/@param\s+array<(?:(?:int|string),\s*)?\\\\?([a-zA-Z_][a-zA-Z0-9_\\\\]*?)>\s+\$' . preg_quote(
            $propertyName,
            '/',
        ) . '/';

        if (preg_match($pattern, $docComment, $matches) === 1) {
            // Clean up the type (remove leading backslash if present)
            $itemType = ltrim($matches[1], '\\');

            // If it's a short class name, try to resolve it with the current namespace
            if (
                !class_exists($itemType) && !enum_exists($itemType) && !in_array(
                    $itemType,
                    ['string', 'int', 'float', 'bool', 'mixed'],
                    true,
                )
            ) {
                $namespace = $class->getNamespaceName();
                $fullType = $namespace . '\\' . $itemType;

                if (class_exists($fullType) || enum_exists($fullType)) {
                    return $fullType;
                }
            }

            return $itemType;
        }

        return null;
    }
}
