<?php

declare(strict_types=1);

namespace MethorZ\OpenApi\Generator;

use ReflectionProperty;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Extracts OpenAPI validation constraints from Symfony Validator attributes
 *
 * Converts Symfony Validator constraints to their OpenAPI schema equivalents.
 */
final readonly class ConstraintExtractor
{
    /**
     * Apply Symfony Validator constraints to schema
     *
     * @param array<string, mixed> $schema
     * @phpcsSuppress SlevomatCodingStandard.PHP.DisallowReference.DisallowedPassingByReference
     */
    public function applyConstraints(ReflectionProperty $property, array &$schema, bool &$required): void
    {
        foreach ($property->getAttributes() as $attribute) {
            $attrName = $attribute->getName();

            if ($attrName === Assert\NotBlank::class) {
                $required = true;
            } elseif ($attrName === Assert\Uuid::class) {
                $schema['format'] = 'uuid';
            } elseif ($attrName === Assert\Length::class) {
                /** @var Assert\Length $instance */
                $instance = $attribute->newInstance();

                if ($instance->min !== null) {
                    $schema['minLength'] = $instance->min;
                }

                if ($instance->max !== null) {
                    $schema['maxLength'] = $instance->max;
                }
            } elseif ($attrName === Assert\Range::class) {
                /** @var Assert\Range $instance */
                $instance = $attribute->newInstance();

                if ($instance->min !== null) {
                    $schema['minimum'] = $instance->min;
                }

                if ($instance->max !== null) {
                    $schema['maximum'] = $instance->max;
                }
            } elseif ($attrName === Assert\Email::class) {
                $schema['format'] = 'email';
            } elseif ($attrName === Assert\Url::class) {
                $schema['format'] = 'uri';
            }
        }
    }

    /**
     * Check if property has UUID constraint
     */
    public function hasUuidConstraint(ReflectionProperty $property): bool
    {
        foreach ($property->getAttributes() as $attribute) {
            if ($attribute->getName() === Assert\Uuid::class) {
                return true;
            }
        }

        return false;
    }
}
