<?php

declare(strict_types=1);

namespace MethorZ\OpenApi\Tests\Fixtures;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Example nested DTO for testing nested DTO support
 */
final readonly class AddressDto
{
    public function __construct(
        #[Assert\NotBlank]
        public string $street,

        #[Assert\NotBlank]
        public string $city,

        #[Assert\NotBlank]
        #[Assert\Length(min: 5, max: 10)]
        public string $zipCode,

        public ?string $country = null,
    ) {
    }
}

