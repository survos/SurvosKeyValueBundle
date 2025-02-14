<?php declare(strict_types = 1);

namespace Survos\KeyValueBundle\Type;

class IpType extends DefaultType implements TypeInterface
{
    public function satisfies(string $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_IP);
    }

    public function supports(string $type): bool
    {
        return 'ip' === $type;
    }
}
