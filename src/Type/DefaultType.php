<?php declare(strict_types = 1);

namespace Survos\KeyValueBundle\Type;

use Survos\KeyValueBundle\Entity\KeyValueManagerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * Default type used to be a fallback type for validator
 *
 * Class DefaultType
 *
 * @package Survos\KeyValueBundle\Type
 */
class DefaultType implements TypeInterface
{
    public function satisfies(string $value): bool
    {
        return true;
    }

    public function supports(string $type): bool
    {
        return true;
    }

    public function validate(
        string $value,
        Constraint $constraint,
        ExecutionContextInterface &$context,
        KeyValueManagerInterface $manager
    ): void {
        if ($manager->has($value, $constraint->type, $constraint->caseSensitive)) {
            $context->buildViolation($constraint->message)->addViolation();
        }
    }
}
