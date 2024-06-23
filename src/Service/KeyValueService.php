<?php

namespace Survos\KeyValueBundle\Service;

// see https://github.com/bungle/web.php/blob/master/sqlite.php for a wrapper without PDO

use Psr\Log\LoggerInterface;
use Survos\KeyValueBundle\StorageBox;


class KeyValueService
{
    public function __construct(private LoggerInterface $logger)
    {
    }

    function getStorageBox(string $filename, array $tables=[]): StorageBox
    {
        return new StorageBox($filename, $tables, logger: $this->logger);
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
