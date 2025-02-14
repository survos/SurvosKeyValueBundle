<?php declare(strict_types = 1);

namespace Survos\KeyValueBundle\Entity;

interface KeyValueManagerInterface
{
    public function isKeyValueed(string $value, string $type, bool $sensetive = true): bool;
    public function addToKeyValue(string $value, string $type, bool $flush = true): void;

    /** @return array<KeyValue> */
    public function getList(?string $type = null): array;
}
