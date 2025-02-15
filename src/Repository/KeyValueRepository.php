<?php declare(strict_types = 1);

namespace Survos\KeyValueBundle\Repository;

use Doctrine\ORM\EntityRepository;

class KeyValueRepository extends EntityRepository
{
    /** @codeCoverageIgnore */
    public function matchValue(string $value, string $type, bool $isCaseSensitive = true): bool
    {
        $valCondition = $isCaseSensitive ?
            "UPPER(t.value) = UPPER('{$value}')" :
            "t.value = '{$value}'";


        // count() might be faster
        return (bool) $this->createQueryBuilder('t')
            ->where($valCondition)
            ->andWhere("t.type = :type")
                ->setParameter("type", $type)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
