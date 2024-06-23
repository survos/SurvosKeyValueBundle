<?php


namespace Survos\KeyValueBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

class CsvHeaderEvent extends Event
{

    public function __construct(
        public array $header,
        public string $filename
    )
    {
    }
}
