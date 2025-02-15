<?php declare(strict_types=1);

// good candidate for php 8.4!

namespace Survos\KeyValueBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Survos\KeyValueBundle\Repository\KeyValueRepository;
use Symfony\Component\Validator\Constraints as Assert;


#[ORM\Entity(repositoryClass: KeyValueRepository::class)]
#[ORM\Table]
#[ORM\UniqueConstraint(name: 'kv_type_value', columns: ['type', 'value'])]
// should values be indexed, for faster lookup
class KeyValue implements \Stringable
{
    /**
     *
     * @todo: hash string for integration with service
     *
     * @var int
     */
    #[ORM\Id]
    #[ORM\GeneratedValue()]
    #[ORM\Column(type: 'integer')]
    protected int $id;

    public function __construct(
        /**
         * @var string|null
         */
        #[ORM\Column(type: 'string', nullable: false)]
        #[Assert\NotBlank]
        protected        $value,

        /**
         * @var string|null
         */
        #[ORM\Column(type: 'string', length: 255, nullable: false)]
        #[Assert\NotBlank]
        protected string $type,

    )
    {
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
