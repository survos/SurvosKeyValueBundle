<?php declare(strict_types = 1);

namespace Survos\KeyValueBundle\Utils;

use Survos\KeyValueBundle\Type\TypeInterface;

interface TypeExtractorInterface
{
    public function extract(): array;
    public function extractDefault(): TypeInterface;
    public function extractSupported(string $type): array;
}
