<?php declare(strict_types = 1);

namespace Survos\KeyValueBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Survos\KeyValueBundle\Repository\KeyValueRepository;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @codeCoverageIgnore
 */
#[ORM\Entity(repositoryClass: KeyValueRepository::class)]
#[ORM\Table]
#[ORM\UniqueConstraint(name: 'assignment_unique', columns: ['type', 'value'])]
// should values be indexed, for faster lookup
class KeyValue implements \Stringable
{
    /**
     * @var int
     */
    #[ORM\Id]
    #[ORM\GeneratedValue()]
    #[ORM\Column(type: 'integer')]
    protected $id;

    /**
     * @var string|null
     */
    #[ORM\Column(type: 'string', length: 255, nullable: false)]
    #[Assert\NotBlank]
    protected string $type;

    /**
     * @var string|null
     */
    #[ORM\Column(type: 'string', nullable: false)]
    #[Assert\NotBlank]
    protected $value;

    public function __construct(string $value, string $type)
    {
        $this->value = $value;
        $this->type = $type;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getValue(): string
    {
        return $this->value ?? '';
    }

    public function getType(): string
    {
        return $this->type ?? '';
    }

    public function __toString(): string
    {
        return sprintf("%s/%s", $this->type, $this->getValue());
    }
}
