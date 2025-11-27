<?php

declare(strict_types=1);

namespace MethorZ\OpenApi\Tests\Fixtures;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Example DTO for testing schema generation
 */
final readonly class ExampleDto
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(min: 3, max: 100)]
        public string $name,

        #[Assert\Email]
        public string $email,

        #[Assert\Range(min: 18, max: 120)]
        public int $age,

        public ?string $optional = null,
    ) {
    }
}

