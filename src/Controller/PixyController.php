<?php

namespace Survos\KeyValueBundle\Controller;

use League\Csv\Reader;
use Survos\KeyValueBundle\Service\KeyValueService;
use Survos\KeyValueBundle\Service\PixyImportService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Yaml\Yaml;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

#[Route('/pixy')]
class PixyController extends AbstractController
{

    public function __construct(
        private ParameterBagInterface $bag,
        private KeyValueService $keyValueService
    ) {

    }

    private function getPixyConf(string $pixyName, bool $throwIfMissing=true): ?string
    {
        $dirs = [
            $this->bag->get('data_dir'),
            $this->bag->get('kernel.project_dir') . "/config/packages/pixy/",
        ];
        $pixyName = str_replace('.pixy', '', $pixyName); // hack!
        foreach ($dirs as $dir) {
            $fn = $dir . "/$pixyName.yaml";
            if (file_exists($fn)) {
                return $fn;
            }
        }
        assert(false, "$fn not found");
        return null;
    }

    #[Route('/show/{pixyName}/{tableName}/{key}', name: 'pixy_show_record')]
    public function show_record(
        string $pixyName,
        string $tableName,
        string $key,
        #[MapQueryParameter] ?string $index=null,
        #[MapQueryParameter] ?string $value=null,
        #[MapQueryParameter] int $limit = 5
    ): Response
    {
        $kv = $this->keyValueService->getStorageBox($pixyName);
        $kv->select($tableName);
        $row = $kv->get($key, $tableName);
        if (!$row) {
            throw new NotFoundHttpException("No key $key in $tableName / $pixyName");
        }
        $row = (array)$row;
        return $this->render('@SurvosKeyValue/pixy/show.html.twig', [
            'row' => $row,
            'columns' => array_keys($row),
            ]);

    }

    #[Route('/browse/{pixyName}/{tableName}', name: 'pixy_browse')]
    public function browse(
                         string $pixyName,
                         string $tableName,
                         #[MapQueryParameter] ?string $index=null,
                         #[MapQueryParameter] ?string $value=null,
                         #[MapQueryParameter] int $limit = 5
    ): Response
    {
        // need to handle extension
        $kv = $this->keyValueService->getStorageBox($pixyName);
        $where = [];
        if ($index) {
            $where[$index] = $value?:null;
        }
        $kv->select($tableName);
        $iterator = $kv->iterate($tableName, $where);

        if ($firstRow = $iterator->current()) {
            $columns = array_keys($firstRow);
        } else {
            $columns = ['value'];
        }
        array_unshift($columns, 'key');
        $iterator->rewind();
//        foreach ($kv->iterate($tableName, $where) as $row) {
//            dd($row);
//        }
        // see kaggle for inspiration, https://www.kaggle.com/datasets/shivamb/real-or-fake-fake-jobposting-prediction/data
        return $this->render('@SurvosKeyValue/pixy/browse.html.twig', [
            'pixyName' => $pixyName,
'tableName' => $tableName,
//            'kv' => $kv, // avoidable?/
            'iterator' => $firstRow ? $iterator : [],
            'keyName' => $kv->getPrimaryKey(),
            'columns' => $columns,
            'filename' => $kv->getFilename(),
        ]);

    }

    #[Route('/{pixyName}', name: 'pixy_homepage')]
    public function home(ChartBuilderInterface $chartBuilder,
                         string $pixyName,
    #[MapQueryParameter] int $limit = 5
    ): Response
    {
        $firstRecords = [];
        $charts = [];
        $tables = [];

        $kv = $this->keyValueService->getStorageBox($pixyName);
        foreach ($kv->getTables() as $tableName) {
            $count = $kv->count($tableName);
//            dd($tableName, $count);
            $tables[$tableName] = [
                'first' => $kv->iterate($tableName)->current()
            ];

            $charts = [];
            foreach ($kv->getIndexes($tableName) as $indexName) {
                $labels =  [];
                $values = [];
                $counts = $kv->getCounts($indexName, $tableName, $limit);
                if (count($counts) === 0) {
                    continue;
                }
                foreach ($counts as $count) {
                    $labels[] = $count['value']; // the property name
                    $values[] = $count['count'];
                    // @todo: composer require phpcolor/bootstrap-colors
                    $colors[] = sprintf('rgb(%d, %d, %d)',
                        rand(0,255),
                        rand(0,255),
                        rand(0,255)
                    );
                }
                $chart = $chartBuilder->createChart(
                    str_contains($indexName, 'year') ? Chart::TYPE_LINE :
                    Chart::TYPE_PIE
                );

                $chart->setData([
                    'labels' => $labels,
                    'datasets' => [
                        [
                            'label' => $indexName . "/$tableName",
                            'backgroundColor' => $colors,
                            'borderColor' => 'rgb(255, 99, 132)',
                            'data' => $values,
                        ],
                    ],
                ]);

                $charts[$indexName] = [
                    'chart'=> $chart,
                    'counts' => $counts
                    ];
            }
            $tables[$tableName]['charts'] = $charts;
        }

        return $this->render('@SurvosKeyValue/pixy/graphs.html.twig', [
            'pixyName' => $pixyName,
//            'kv' => $kv, // avoidable?/
        'tables' => $tables,
            'filename' => $kv->getFilename(),
            'firstRecords' => $firstRecords
        ]);

    }
    #[Route('/import/{pixyName}', name: 'pixy_import')]
    public function import(KeyValueService $keyValueService,
                           PixyImportService $pixyImportService,
                           string $pixyName,
                           #[MapQueryParameter] int $limit = 0,
    ): Response
    {
        // get the conf file from the configured directories (from the bundle)

        // cache wget "https://github.com/MuseumofModernArt/collection/raw/main/Artists.csv"   ?
        if ($configFilename = $this->getPixyConf($pixyName)) {
            $config = Yaml::parseFile($configFilename);
        } else {
            $config = [
                'dir' => "$pixyName"
            ];
        }
        $pixyImportService->import($config, $pixyName, limit: $limit);
        return $this->redirectToRoute('pixy_homepage', [
            'pixyName' => $pixyName
        ]);

        dd();




        $tables = ['tables'];
        foreach ($tables as $tableName => $tableData) {
            $tablesToCreate[$tableName] = $tableData['indexes'];
        }
        $kv = $keyValueService->getStorageBox($pixyDbName, $tablesToCreate);
//        dd($pixyDbName, $configFilename, $tablesToCreate);

        foreach ($tables as $tableName => $tableData) {
        $kv->map($tableData['rules'], [$tableName]);
        $kv->select($tableName);

            $fn = $this->dataDir . '/moma/' . ucfirst($tableName) . 's.csv';
            assert(file_exists($fn), $fn);
            // pixydb? phixy.db?
            $csv = Reader::createFromPath($fn, 'r');
            $csv->setHeaderOffset(0); //set the CSV header offset

            $headers = $kv->mapHeader($csv->getHeader());
            $kv->beginTransaction();
            assert(count($headers) == count(array_unique($headers)), json_encode($headers));
            foreach ($csv->getRecords($headers) as $idx => $row) {
                $kv->set($row);
//                if ($idx > 100) break;
//            dd($kv->get($row['id']));
//            dump($row); break;
            }
            $kv->commit();
            foreach ($kv->iterate() as $key => $row) {
                dump($key, $row); break;
            }
        }

        return $this->redirectToRoute('pixy_homepage');
    }
}
