<?php

namespace Survos\KeyValueBundle\Service;

// see https://github.com/bungle/web.php/blob/master/sqlite.php for a wrapper without PDO

use Psr\Log\LoggerInterface;
use Survos\KeyValueBundle\Debug\TraceableStorageBox;
use Survos\KeyValueBundle\StorageBox;
use Symfony\Component\Stopwatch\Stopwatch;


class KeyValueService
{
    // cache, indexed by filename
    private array $storageBoxes = [];

    public function __construct(
        private bool $isDebug,
        private array $data=[],
        private string $dataDir='./',
        private ?LoggerInterface $logger=null,
        private ?Stopwatch $stopwatch=null,
    )
    {
    }

    function getStorageBox(string $filename, array $tables=[], bool $destroy=false): StorageBox
    {
        if (!file_exists($filename)) {
            $filename = $this->dataDir . "/$filename";
        }
        $destroy && $this->destroy($filename);
        if (!$kv = $this->storageBoxes[$filename]??false) {
            $class = $this->isDebug ? TraceableStorageBox::class : StorageBox::class;
            $kv =  new $class($filename,
                $this->data, // for debug
                $tables,
                logger: $this->logger, stopwatch: $this->stopwatch);
            $this->storageBoxes[$filename] = $kv;
        }
        return $kv;
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

    /** @internal used in the DataCollector class */
    public function getData(): array
    {
        return $this->data;
        foreach ($this->storageBoxes as $filename => $storageBox) {
            $this->data[$filename] = $storageBox->getData();
        }
        return $this->data;
    }



}
