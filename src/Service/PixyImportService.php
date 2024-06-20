<?php

namespace Survos\KeyValueBundle\Service;

// see https://github.com/bungle/web.php/blob/master/sqlite.php for a wrapper without PDO

use League\Csv\Reader;
use Psr\Log\LoggerInterface;
use Survos\KeyValueBundle\StorageBox;
use Symfony\Component\Finder\Finder;
use function Symfony\Component\String\u;


class PixyImportService
{
    public function __construct(
        private string $dataDir,
        private KeyValueService $keyValueService,
        private LoggerInterface $logger)
    {
    }

    public function import(array $configData, string $dirOrFilename, string $pixyDbName): StorageBox
    {
        $finder = new Finder();

        foreach ($finder->in($this->dataDir . '/' . $dirOrFilename)->name('*.csv') as $splFile) {
//            $fn =
            $map[$splFile->getRealPath()] = u($splFile->getFilenameWithoutExtension())->snake()->toString();
            foreach ($configData['files']??[] as $rule=>$tableName) {
                if (preg_match($rule, $splFile->getFilename(), $mm)) {
//                    dd($mm, $splFile->getFilename(), $tableName);
                    $map[$splFile->getRealPath()] = $tableName;
                    break;
                }
            }
            $fileMap[$splFile->getRealPath()] = $map[$splFile->getRealPath()]??null;
        }


        // only create the tables that match the filenames
        foreach ($fileMap as $fn => $tableName) {
            $tables = $configData['tables'];
            foreach ($tables as $tableName => $tableData) {
                $tablesToCreate[$tableName] = $tableData['indexes'];
            }
        }
        $kv = $this->keyValueService->getStorageBox($pixyDbName, $tablesToCreate);


        foreach ($fileMap as $fn => $tableName) {
            if (empty($tableName)) {
                $this->logger && $this->logger->warning("Skipping $fn, no map to tables");
                continue;
            }
            if (!array_key_exists($tableName, $tables)) {
                $this->logger && $this->logger->warning("Skipping $tableName, not defined in tables");
                continue;
            }
            $tableData = $tables[$tableName];
//        }
//        foreach ($tables as $tableName => $tableData) {
            $kv->map($tableData['rules'], [$tableName]);
            $kv->select($tableName);

            $csv = Reader::createFromPath($fn, 'r');
            $csv->setHeaderOffset(0); //set the CSV header offset

            $headers = $kv->mapHeader($csv->getHeader());
            if (count($headers) != count(array_unique($headers))) {
                dd($headers, array_unique($headers));
            }
            $kv->beginTransaction();
            assert(count($headers) == count(array_unique($headers)), json_encode($headers));
            foreach ($csv->getRecords($headers) as $idx => $row) {
                $kv->set($row);
                if ($idx==1) dump($tableName, $row);
//                if ($idx > 100) break;
//            dd($kv->get($row['id']));
//            dump($row); break;
            }
            $kv->commit();
        }
        dd($fileMap);

    }

}
