<?php declare(strict_types = 1);

namespace Survos\KeyValueBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
/** @Annotation */
class IsNotKeyValueed extends Constraint
{
    /** @var string */
    public string $type;

    /** @var bool */
    public bool $caseSensitive = true;

    /** @var string */
    public string $message = 'This value is key-valueed';
}
