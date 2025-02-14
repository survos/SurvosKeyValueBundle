<?php declare(strict_types = 1);

namespace Survos\KeyValueBundle\Entity;

use Doctrine\ORM\EntityManagerInterface;
use Survos\KeyValueBundle\Repository\KeyValueRepository;

class KeyValueManager implements KeyValueManagerInterface
{
    /** @var KeyValueRepository */
    private $repository;

    public function __construct(private readonly EntityManagerInterface $em)
    {
        $this->repository = $this->em->getRepository(KeyValue::class);
    }

    public function isKeyValueed(string $value, string $type, bool $sensetive = true): bool
    {
        return $this->repository->matchValue($value, $type, $sensetive);
    }

    public function addToKeyValue(string $value, string $type, bool $flush = true): void
    {
        $this->add($value, $type);

        if ($flush) {
            $this->em->flush();
        }
    }

    /** {@inheritDoc} */
    public function getList(?string $type = null): array
    {
        if (!$type) {
            return $this->repository->findAll();
        }

        return $this->repository->findBy([
            'type' => $type,
        ]);
    }

    private function add(string $value, string $type): void
    {
        $this->em->persist(new KeyValue($value, $type));
    }
}
