<?php

namespace Survos\KeyValueBundle\Service;

// see https://github.com/bungle/web.php/blob/master/sqlite.php for a wrapper without PDO

use Psr\Log\LoggerInterface;
use Survos\KeyValueBundle\Debug\TraceableStorageBox;
use Survos\KeyValueBundle\StorageBox;
use Symfony\Component\Stopwatch\Stopwatch;


class KeyValueService
{
    public function __construct(
        private bool $isDebug,
        private ?LoggerInterface $logger=null,
        private ?Stopwatch $stopwatch=null,
    )
    {
    }

    function getStorageBox(string $filename, array $tables=[]): StorageBox
    {
        $class = $this->isDebug ? TraceableStorageBox::class : StorageBox::class;
        return new $class($filename, $tables, logger: $this->logger, stopwatch: $this->stopwatch);
    }

    function getStringBox(string $filename, array $tables=[]): StorageBox
    {
        return new StorageBox($filename, $tables, valueType: 'string', logger: $this->logger);
    }

    function destroy(string $filename): void
    {
        if (file_exists($filename)) {
            unlink($filename);
        }
    }
}
