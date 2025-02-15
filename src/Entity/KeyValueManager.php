<?php declare(strict_types=1);

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

    public function has(string $value, string $type, bool $isCaseSensitive = true): bool
    {
        return $this->repository->matchValue($value, $type, $isCaseSensitive);
    }

    public function add(string $value, string $type, bool $flush = true): void
    {
        $this->persist($value, $type);

        if ($flush) {
            $this->em->flush();
        }
    }

    /** {@inheritDoc} */
    public function getList(string $type = null): array
    {
        return $this->repository->createQueryBuilder('kv')->select(['value'])
            ->andWhere('kv.type = :type')
            ->setParameter('type', $type)
            ->getQuery()
            ->getResult();
    }

    private function persist(string $value, string $type): void
    {
        $this->em->persist(new KeyValue($value, $type));
    }
}
