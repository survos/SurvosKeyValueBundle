<?php declare(strict_types = 1);

namespace Survos\KeyValueBundle\Repository;

use Doctrine\ORM\EntityRepository;

class KeyValueRepository extends EntityRepository
{
    /** @codeCoverageIgnore */
    public function matchValue(string $value, string $type, bool $isSensetive = true): bool
    {
        $valCondition = $isSensetive ?
            "UPPER(t.value) = UPPER('{$value}')" :
            "t.value = '{$value}'";

        return isset($this->createQueryBuilder('t')
            ->where($valCondition)
            ->andWhere("t.type = '{$type}'")
            ->getQuery()
            ->getResult()[0]);
    }
}
