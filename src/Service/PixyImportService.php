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
        if (!file_exists($dirOrFilename)) {
            throw new \LogicException("$dirOrFilename does not exist");
        }
        assert(file_exists($dirOrFilename), $dirOrFilename);

        $files = $finder->in($dirOrFilename)->name(['*.json', '*.csv', '*.txt', '*.tsv']);
        if ($ignore = $configData["source"]["ignore"] ?? false) {
            $files->notName($ignore);
        }
        assert($files->count(), "No files in $dirOrFilename");

        foreach ($files as $splFile) {
            $map[$splFile->getRealPath()] = u($splFile->getFilenameWithoutExtension())->snake()->toString();
            foreach ($configData['files'] ?? [] as $rule => $tableNameRule) {
                if (preg_match($rule, $splFile->getFilename(), $mm)) {
//                    dd($mm, $splFile->getFilename(), $tableName);
                    $map[$splFile->getRealPath()] = $tableNameRule;
                    break;
                }
            }
            $fileMap[$splFile->getRealPath()] = $map[$splFile->getRealPath()] ?? null;
        }
        unset($splFile);

//        list($splFile, $tableName, $mm, $fileMap, $fn, $tables, $tableData, $kv) =
        $kv = $this->createKv($fileMap, $configData, $pixyDbName);
        $tables = $kv->inspectSchema();

        foreach ($fileMap as $fn => $tableName) {
            if (empty($tableName)) {
                $this->logger && $this->logger->warning("Skipping $fn, no map to tables");
                continue;
            }
//            dd($tableName, $tablesToCreate);
            $table = $kv->inspectSchema()[$tableName]??null;

            if (!$table) {
                throw new \LogicException("$tableName is not defined in tables ");
                $this->logger && $this->logger->warning("Skipping $tableName, not defined in tables");
                continue;
            }
//            $tableData = (array)$table; // $tables[$tableName];

            $rules = $configData['tables'][$tableName]['rules'];
            $kv->map($rules, [$tableName]);
            $kv->select($tableName);

            list($ext, $iterator, $headers, $mappedHeader) =
                $this->setupHeader($configData, $tableName, $kv, $fn);
//            dd($mappedHeader, $headers, $tableName, $configData);

            // takes a function that will iterate through an object
//            $kv->addFormatter(function());

            $kv->beginTransaction();
            if (isset($headers))
                assert(count($headers) == count(array_unique($headers)), json_encode($headers));
            // don't parse the header match each time, store them
            $regexRules = $configData['tables'][$tableName]['formatter'] ?? [];
            // why not mapped headers?
            assert($headers == $mappedHeader, "headers?");
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
                    $origRow = $row;
//                    dd($row, $headers, $mappedHeader);
                    $row = array_combine($mappedHeader, array_values((array)$row));
//                    dump(table: $tableName, orig: $origRow, mapped: $mappedHeader, new_row: $row);
//                    dd($idx, $row, $headers); return $kv;
                }
//                dump($ext, $mappedHeader, $row);
                assert(array_key_exists('id', $row),
                    $tableName . " " .
                    json_encode($row, JSON_PRETTY_PRINT));

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
//                if ($idx == 1) dump($tableName, $row);
                if ($limit && ($idx > $limit)) break;
//            dd($kv->get($row['id']));
//            dump($row); break;
            }
            $kv->commit();
        }
        return $kv;
//        dd($fileMap);

    }

    public function createKv(array $fileMap,  array $configData, string $pixyDbName): StorageBox
    {


        // only create the tables that match the filenames
        foreach ($fileMap as $fn => $tableName) {
            $tables = $configData['tables'];
            foreach ($tables as $tableName => $tableData) {
                $tablesToCreate[$tableName] = $tableData['indexes'];
            }
        }
        if (file_exists($pixyDbName)) unlink($pixyDbName);
        $kv = $this->keyValueService->getStorageBox($pixyDbName, $tablesToCreate);
        return $kv;
        return array($splFile, $tableName, $mm, $fileMap, $fn, $tables, $tableData, $kv);
//        dd($fileMap, $tablesToCreate);
    }

    /**
     * @param mixed $splFile
     * @param array $configData
     * @param mixed $tableName
     * @param StorageBox $kv
     * @param int|string $fn
     * @return array
     * @throws \JsonMachine\Exception\InvalidArgumentException
     * @throws \JsonMachine\Exception\PathNotFoundException
     * @throws \League\Csv\Exception
     * @throws \League\Csv\InvalidArgument
     * @throws \League\Csv\SyntaxError
     * @throws \League\Csv\UnavailableStream
     */
    public function setupHeader(array $configData, string $tableName, StorageBox $kv, int|string $fn): array
    {
        $ext = pathinfo($fn, PATHINFO_EXTENSION);
        if ($ext == 'json') {
            $iterator = Items::fromFile($fn)->getIterator();
            $firstRow = $iterator->current();
            // @todo: handle nested properties
            $headers = array_keys(get_object_vars($firstRow));
            $iterator->rewind();
            $rules = $configData['tables'][$tableName]['rules'];
            $mappedHeader = $kv->mapHeader($headers, regexRules: $rules);
//            dd($rules, $configData, $tableName, filename: $splFile->getFilename(), mappedHeader: $mappedHeader);
            // keep for replacing the key names later
//                dd($headers, mapped: $mappedHeader);
            $this->eventDispatcher->dispatch(
                $headerEvent = new CsvHeaderEvent($mappedHeader, $fn));
//
//                dump($headerEvent->header);
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
//                assert(array_key_exists($tableName, $configData), json_encode($configData));
            $headers = $kv->mapHeader($originalHeaders, regexRules: $configData['tables'][$tableName]['rules']);
//                dd($originalHeaders, $headers, $configData);
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
        return [$ext, $iterator, $headers, $mappedHeader];
    }

}
