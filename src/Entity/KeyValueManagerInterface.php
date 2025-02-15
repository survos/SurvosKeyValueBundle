<?php declare(strict_types = 1);

namespace Survos\KeyValueBundle\Entity;

interface KeyValueManagerInterface
{
    public function has(string $value, string $type, bool $isCaseSensitive = true): bool;
    public function add(string $value, string $type, bool $flush = true): void;

    /** @return array<KeyValue> */
    public function getList(string $type): array;
    public function getTypes(): array;
//    public function setConfig(array $config): self;

    public function getDefaultList(): ?string;

    public function setDefaultList(string $defaultList): self;

}
