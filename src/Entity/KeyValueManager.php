<?php declare(strict_types=1);

namespace Survos\KeyValueBundle\Entity;

use Doctrine\ORM\EntityManagerInterface;
use Survos\KeyValueBundle\Repository\KeyValueRepository;

class KeyValueManager implements KeyValueManagerInterface
{
    var KeyValueRepository $keyValueRepository;

    public function __construct(
        private readonly EntityManagerInterface $em,
        private array                           $config = [],
        private ?string                         $defaultList = null
    )
    {
        $this->keyValueRepository = $this->em->getRepository(KeyValue::class);
    }

    public function getDefaultList(bool $throwErrorIfMissing=true): ?string
    {
        // this is usually called if type isn't set, so throw error if missing.
        if ($throwErrorIfMissing && !$this->defaultList) {
            throw new \LogicException("either set default_list of explicitly set the list name");
        }
        return $this->defaultList;
    }

    public function setDefaultList(string $defaultList): self
    {
        $this->defaultList = $defaultList;
        return $this;
    }


    public function has(string $value, ?string $type = null, bool $isCaseSensitive = true): bool
    {
        $type ??= $this->getDefaultList();
        return $this->keyValueRepository->matchValue($value, $type, $isCaseSensitive);
    }

    private function isStrict(): bool
    {
        return $this->config["strict"] ?? false;
    }

    public function remove(string $value, ?string $type = null, bool $flush = true): void
    {
        if (!$this->has($value, $type)) {
            throw new \LogicException("Value '$value' does not exist");
        }
        if ($entity = $this->keyValueRepository->findOneBy([
            'value' => $value,
            'type' => $type,
        ])) {
            $this->em->remove($entity);
            if ($flush) {
                $this->em->flush();
            }
        }

    }
    public function add(string $value, ?string $type = null, bool $flush = true): void
    {
        $type ??= $this->getDefaultList();
        if ($this->isStrict()) {
            $validLists = array_map(fn($config) => $config['name'], $this->config["lists"]);
            if (!in_array($type, $validLists, true)) {
                throw new \LogicException("Type '$type' is not allowed: " . implode(', ', $validLists));
            }
        }
        if ($this->has($value, $type)) {
            throw new \LogicException("Value '$value' already exists");
        }
        $this->persist($value, $type);

        if ($flush) {
            $this->em->flush();
        }
    }

    /** {@inheritDoc} */
    public function getList(?string $type = null): array
    {
        $type ??= $this->getDefaultList();
        return $this->keyValueRepository->createQueryBuilder('kv')->select(['kv.value'])
            ->andWhere('kv.type = :type')
            ->setParameter('type', $type)
            ->getQuery()
            ->getSingleColumnResult();
    }

    public function getTypes(): array
    {
        return $this->keyValueRepository->createQueryBuilder('kv')
            ->select('kv.type, count(kv.value) as count')
            ->groupBy('kv.type')
            ->getQuery()
            ->getResult();
    }


    private function persist(string $value, string $type): void
    {
        $this->em->persist(new KeyValue($value, $type));
    }
}
