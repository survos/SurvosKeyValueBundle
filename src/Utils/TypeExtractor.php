<?php declare(strict_types = 1);

namespace Survos\KeyValueBundle\Utils;

use Survos\KeyValueBundle\Type\TypeInterface;

class TypeExtractor implements TypeExtractorInterface
{
    public function __construct(
        /** @var TypeInterface[] */
        private readonly array $types,
        private readonly TypeInterface $defaultType
    )
    {
    }

    public function extract(): array
    {
        return $this->types;
    }

    public function extractDefault(): TypeInterface
    {
        return $this->defaultType;
    }

    public function extractSupported(string $type): array
    {
        return array_filter($this->types, fn(TypeInterface $typeObject) => $typeObject->supports($type));
    }
}
