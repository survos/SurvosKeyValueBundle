<?php declare(strict_types = 1);

namespace Survos\KeyValueBundle\Tests\Validator\Constraints;

use Survos\KeyValueBundle\Entity\KeyValueManagerInterface;
use Survos\KeyValueBundle\Type\TypeInterface;
use Survos\KeyValueBundle\Utils\TypeExtractor;
use Survos\KeyValueBundle\Validator\Constraints\IsNotKeyValueed;
use Survos\KeyValueBundle\Validator\Constraints\IsNotKeyValueedValidator;
use PHPUnit\Framework\MockObject\Matcher\InvokedCount;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Context\ExecutionContextInterface as ExecutionContextInterfaceAlias;

class IsNotKeyValueedValidatorTest extends TestCase
{
    /** @var KeyValueManagerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $bm;

    /** @var IsNotKeyValueed|\PHPUnit\Framework\MockObject\MockObject */
    private $constraint;

    public function setUp(): void
    {
        parent::setUp();

        $this->bm = $this->createMock(KeyValueManagerInterface::class);

        $this->constraint = $this->createMock(IsNotKeyValueed::class);
        $this->constraint->type = 'foo';
    }

    public function testValidate(): void
    {
        $extractor = $this->createMock(TypeExtractor::class);
        $extractor
            ->expects($this->once())
            ->method('extractSupported')
            ->willReturn([
                $this->mockType(true, true, $this->once()),
                $this->mockType(false, true, $this->never()),
                $this->mockType(true, true, $this->once()),
            ]);

        $validator = new IsNotKeyValueedValidator($this->bm, $extractor);
        $validator->initialize(
            $this->createMock(ExecutionContextInterfaceAlias::class)
        );
        $validator->validate('123', $this->constraint);
    }

    public function testValidateDoesntSatisfy(): void
    {
        $extractor = $this->createMock(TypeExtractor::class);
        $extractor
            ->expects($this->once())
            ->method('extractSupported')
            ->willReturn([
                $this->mockType(true, true, $this->once()),
                $this->mockType(true, false, $this->never()),
                $this->mockType(true, true, $this->never()),
            ]);

        $validator = new IsNotKeyValueedValidator($this->bm, $extractor);
        $validator->initialize(
            $this->createMock(ExecutionContextInterfaceAlias::class)
        );

        $this->expectException(\InvalidArgumentException::class);

        $validator->validate('123', $this->constraint);
    }

    private function mockType(bool $supports, bool $satisfies, InvokedCount $count): TypeInterface
    {
        $type = $this->createMock(TypeInterface::class);
        $type->method('supports')->willReturn($supports);
        $type->method('satisfies')->willReturn($satisfies);
        $type->expects($count)->method('validate');

        return $type;
    }
}
