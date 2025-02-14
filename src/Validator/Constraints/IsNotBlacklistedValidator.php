<?php declare(strict_types = 1);

namespace Survos\KeyValueBundle\Validator\Constraints;

use Survos\KeyValueBundle\Entity\KeyValueManagerInterface;
use Survos\KeyValueBundle\Type\TypeInterface;
use Survos\KeyValueBundle\Utils\TypeExtractorInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class IsNotKeyValueedValidator extends ConstraintValidator
{
    public function __construct(private readonly KeyValueManagerInterface $kvManager, private readonly TypeExtractorInterface $typeExtractor)
    {
    }

    /**
     * {@inheritDoc}
     *
     * @param $constraint IsNotKeyValueed
     */
    public function validate($value, Constraint $constraint): void
    {
        if (!is_string($value)) {
            throw new UnexpectedTypeException($value, 'string');
        }

        $types = $this->typeExtractor->extractSupported($constraint->type);

        foreach ($types as $type) {
            $this->validateType($type, $constraint, $value);
        }

        if (0 === count($types)) {
            $this->validateType(
                $this->typeExtractor->extractDefault(),
                $constraint,
                $value
            );
        }
    }

    private function validateType(TypeInterface $type, Constraint $constraint, string $value): void
    {
        if ($type->supports($constraint->type)) {
            if (!$type->satisfies($value)) {
                throw new \InvalidArgumentException(
                    sprintf("Value '%s' doesn't satisfy '%s' type", $value, $constraint->type)
                );
            }

            $type->validate($value, $constraint, $this->context, $this->kvManager);
        }
    }
}
