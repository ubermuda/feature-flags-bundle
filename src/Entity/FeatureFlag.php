<?php

namespace Ubermuda\FeatureFlagsBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Ubermuda\FeatureFlagsBundle\Enum\FeatureFlagType;
use Ubermuda\FeatureFlagsBundle\Repository\FeatureFlagRepository;

#[ORM\Entity(repositoryClass: FeatureFlagRepository::class)]
class FeatureFlag
{
    #[ORM\Column]
    #[ORM\GeneratedValue]
    #[ORM\Id]
    public private(set) ?int $id = null;

    /** @var list<string> */
    #[ORM\Column(type: Types::JSON, options: ['jsonb' => true])]
    public array $tags = [];

    /**
     * Allowed values for Select-typed flags. Null for non-Select types.
     *
     * @var list<string>|null
     */
    #[ORM\Column(type: Types::JSON, nullable: true, options: ['jsonb' => true])]
    public ?array $options = null;

    public function __construct(
        #[ORM\Column(length: 255, unique: true)]
        public string $name,

        #[ORM\Column(enumType: FeatureFlagType::class)]
        public FeatureFlagType $type = FeatureFlagType::Bool,

        #[ORM\Column(type: Types::JSON, nullable: true)]
        public mixed $value = null,
    ) {
    }
}
