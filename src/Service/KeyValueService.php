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
    private array $data=[
        'x' => 'y',
        'dummy' => 'value'];

    public function __construct(
        private bool $isDebug,
        private ?LoggerInterface $logger=null,
        private ?Stopwatch $stopwatch=null,
    )
    {
        $this->innerSearchService(self::class, ['isDebug' => $this->isDebug]);
    }

    private function innerSearchService(string $function, array $args): mixed
    {
        $this->stopwatch->start($function);

        $result = 'dummy-result'; //  $this->searchService->{$function}(...$args);

        $event = $this->stopwatch->stop($function);

        $this->data[$function] = [
            '_params' => $args,
            '_results' => $result,
            '_duration' => $event->getDuration(),
            '_memory' => $event->getMemory(),
        ];

        return $result;
    }


    function getStorageBox(string $filename, array $tables=[]): StorageBox
    {
        if (!$kv = $this->storageBoxes[$filename]??false) {
            $class = $this->isDebug ? TraceableStorageBox::class : StorageBox::class;
            $kv =  new $class($filename, $tables, logger: $this->logger, stopwatch: $this->stopwatch);
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
