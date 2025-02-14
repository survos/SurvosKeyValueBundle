<?php declare(strict_types = 1);

namespace Survos\KeyValueBundle\Type;

use Survos\KeyValueBundle\Entity\KeyValueManagerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

interface TypeInterface
{
    /**
     * Describes if type supported by current class
     *
     * @param string $type
     * @return bool
     */
    public function supports(string $type): bool;

    /**
     * Checks if value is satisfied by current type
     *
     * @param string $value
     * @return bool
     */
    public function satisfies(string $value): bool;

    /**
     * Validates current value.
     * Used to implement custom storage validator
     *
     * @param string $value
     * @param Constraint $constraint
     * @param ExecutionContextInterface $context
     * @param KeyValueManagerInterface $manager
     */
    public function validate(
        string $value,
        Constraint $constraint,
        ExecutionContextInterface &$context,
        KeyValueManagerInterface $manager
    ): void;
}
