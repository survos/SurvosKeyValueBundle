<?php

namespace Survos\KeyValueBundle\Service;

// see https://github.com/bungle/web.php/blob/master/sqlite.php for a wrapper without PDO

use League\Csv\Info;
use League\Csv\Reader;
use Psr\Log\LoggerInterface;
use Survos\KeyValueBundle\Event\CsvHeaderEvent;
use Survos\KeyValueBundle\StorageBox;
use Symfony\Component\Finder\Finder;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use function Symfony\Component\String\u;
use \JsonMachine\Items;


class PixyImportService
{
    public function __construct(
        private string                            $dataDir,
        private KeyValueService                   $keyValueService,
        private LoggerInterface                   $logger,
        private readonly EventDispatcherInterface $eventDispatcher)
    {
    }

    public function import(array $configData, string $pixyDbName, ?string $dirOrFilename = null, int $limit = 0): StorageBox
    {
        $dirOrFilename = $dirOrFilename ?? $configData["source"]["dir"];
        $finder = new Finder();
        if (!file_exists($dirOrFilename)) {
            $dirOrFilename = $this->dataDir . $dirOrFilename;
        }
        assert(file_exists($dirOrFilename), $dirOrFilename);

        $files = $finder->in($dirOrFilename)->name(['*.json', '*.csv', '*.txt', '*.tsv']);
        if ($ignore = $configData["source"]["ignore"] ?? false) {
            $files->notName($ignore);
        }
        assert($files->count(), "Nofiles in $dirOrFilename");
        foreach ($files as $splFile) {
            $map[$splFile->getRealPath()] = u($splFile->getFilenameWithoutExtension())->snake()->toString();
            foreach ($configData['files'] ?? [] as $rule => $tableName) {
                if (preg_match($rule, $splFile->getFilename(), $mm)) {
//                    dd($mm, $splFile->getFilename(), $tableName);
                    $map[$splFile->getRealPath()] = $tableName;
                    break;
                }
            }
            $fileMap[$splFile->getRealPath()] = $map[$splFile->getRealPath()] ?? null;
        }

        // only create the tables that match the filenames
        foreach ($fileMap as $fn => $tableName) {
            $tables = $configData['tables'];
            foreach ($tables as $tableName => $tableData) {
                $tablesToCreate[$tableName] = $tableData['indexes'];
            }
        }
        if (file_exists($pixyDbName)) unlink($pixyDbName);
        $kv = $this->keyValueService->getStorageBox($pixyDbName, $tablesToCreate);
//        dd($fileMap, $tablesToCreate);


        foreach ($fileMap as $fn => $tableName) {
            if (empty($tableName)) {
                $this->logger && $this->logger->warning("Skipping $fn, no map to tables");
                continue;
            }
//            dd($tableName, $tablesToCreate);
            if (!array_key_exists($tableName, $tables)) {
                $this->logger && $this->logger->warning("Skipping $tableName, not defined in tables");
                continue;
            }
            $tableData = $tables[$tableName];
//        }

            $kv->map($tableData['rules'], [$tableName]);
            $kv->select($tableName);

            $ext = $splFile->getExtension();
            if ($ext == 'json') {
                $iterator = Items::fromFile($splFile->getRealPath())->getIterator();
                $firstRow = $iterator->current();
                // @todo: handle nested properties
                $headers = array_keys(get_object_vars($firstRow));
                $iterator->rewind();

                $mappedHeader = $kv->mapHeader($headers);
                dump($headers, mapped: $mappedHeader);
                $this->eventDispatcher->dispatch(
                    $headerEvent = new CsvHeaderEvent($mappedHeader, $fn));
                dump($headerEvent->header);
                $headers = $headerEvent->header;



//                $this->readJson($splFile->getRealPath());
            } elseif (in_array($ext, ['tsv', 'csv', 'txt'])) {
                $csvReader = Reader::createFromPath($fn, 'r');
                $result = Info::getDelimiterStats($csvReader, ["\t", ','], 3);
                // pick the highest one
                arsort($result);
                $csvReader->setDelimiter(array_key_first($result));
                $csvReader->setHeaderOffset(0); //set the CSV header offset

                $originalHeaders = $csvReader->getHeader();
                $headers = $kv->mapHeader($originalHeaders);
                $this->eventDispatcher->dispatch(
                    $headerEvent = new CsvHeaderEvent($headers, $fn));
                $headers = $headerEvent->header;
                // this could also be handled by a bundle event listener.

                if (count($headers) != count(array_unique($headers))) {
                    dd($headers, array_unique($headers));
                }
//                dd($originalHeaders, $headers);
                $iterator = $csvReader->getRecords($headers);
            }

            // takes a function that will iterate through an object
//            $kv->addFormatter(function());

            $kv->beginTransaction();
            if (isset($headers))
                assert(count($headers) == count(array_unique($headers)), json_encode($headers));
            // don't parse the header match each time, store them
            $regexRules = $configData['tables'][$tableName]['formatter'] ?? [];
            foreach ($headers as $header) {
                foreach ($regexRules as $variableRegexRule => $dataRegexRules) {
                    if (preg_match($variableRegexRule, $header)) {
                        $dataRules[$header] = $dataRegexRules;
                    }
                }
            }
            foreach ($iterator as $idx => $row) {
                // if it's json, remap the keys
                if ($ext === 'json') {
                    $row = array_combine($headers, array_values((array)$row));
//                    dd($x, $idx, $row, $headers); return $kv;
                }
                foreach ($row as $k => $v) {
                    foreach ($dataRules[$k] ?? [] as $dataRegexRule => $substitution) {
                        $match = preg_match($dataRegexRule, $v, $mm);
                        if ($match) {
                            // @todo: a preg_replace?
                            $row[$k] = $substitution === '' ? null : $substitution;
//                                if ($v == '\N') {
//                                    dd($row, $k, header: $header, sub: $substitution);
//                                }
//                                if ($dataRegexRule == '/\\N/')
//                                dd($dataRegexRule, $v, $substitution, $k, $mm);
                        }
                    }
                }
//                        foreach ([
//                                     function (array $row) {
//                                         foreach ($row as $k => $v) {
//                                             if ($v == '\N') {
//                                                 $row[$k] = null;
//                                             }
//                                             if ($k == 'is_adult') {
//                                                 $row[$k] = boolval($v);
//                                             }
//                                         }
//                                         return $row;
//                                     }
//                                 ] as $callable) {
//                            $row = $callable($row);
//                        }

                $kv->set($row);
                if ($idx == 1) dump($tableName, $row);
                if ($limit && ($idx > $limit)) break;
//            dd($kv->get($row['id']));
//            dump($row); break;
            }
            $kv->commit();
        }
        return $kv;
//        dd($fileMap);

    }

    public
    function readJson(string $realPath): \Generator
    {

// this usually takes few kB of memory no matter the file size
        $rows = Items::fromFile($realPath);
        foreach ($rows->getIterator() as $id => $row) {
            yield $id => $row;
            dd($realPath, $id, $row);
            if (count($user->Artist) > 1) {
                dd($id, $user);
                return;
            }
        }

    }

}
