<?php

namespace Survos\KeyValueBundle\Model;

class Index
{
    public function __construct(
        public string $propertyName,
        public string $type = 'TEXT',
        public bool $isPrimary = false,
        public bool $isUnique = false
    )
    {

    }

}
