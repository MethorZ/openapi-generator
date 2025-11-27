<?php

declare(strict_types=1);

namespace MethorZ\OpenApi\Tests\Fixtures;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Complex DTO for testing nested DTOs, collections, and enums
 */
final readonly class ComplexDto
{
    /**
     * @param array<int, AddressDto> $addresses
     * @param array<string> $tags
     */
    public function __construct(
        #[Assert\NotBlank]
        public string $id,

        #[Assert\NotBlank]
        public string $name,

        public StatusEnum $status,

        public AddressDto $primaryAddress,

        public array $addresses,

        public array $tags,

        public ?AddressDto $billingAddress = null,
    ) {
    }
}

