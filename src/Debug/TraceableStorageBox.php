<?php

declare(strict_types=1);

namespace Survos\KeyValueBundle\Debug;

use Doctrine\Persistence\ObjectManager;
use Meilisearch\Bundle\Collection;
use Meilisearch\Bundle\SearchService;
use Psr\Log\LoggerInterface;
use Survos\KeyValueBundle\StorageBox;
use Symfony\Component\Stopwatch\Stopwatch;

final class TraceableStorageBox extends StorageBox
{
//    private Stopwatch $stopwatch;
    private array $data = [];

    #[NoReturn] function __construct(private string                    $filename,
                                     private array                     $tablesToCreate = [],
                                     private array                     $regexRules = [],
                                     private ?string                   $currentTable = null,
                                     private ?int                      $version = 1,
                                     private string                    $valueType = 'json', // eventually jsonb
                                     private bool                      $temporary = false, // nyi
                                     private readonly ?LoggerInterface $logger = null,
                                     private array $formatters = [],
                                     private readonly ?Stopwatch $stopwatch = null,
    )
    {

        parent::__construct(...func_get_args());
//        $this->stopwatch = $stopwatch;
    }

    public function map(array $tableRegexRules, array $tables = ['__all']) {
        return $this->innerSearchService(__FUNCTION__, \func_get_args());
    }

    public function get(string $key, string $table = null): string|object|array|null
    {
        return $this->innerSearchService(__FUNCTION__, \func_get_args());
    }

    public function innerSearchService(string $function, array $args): mixed
    {
        $this->stopwatch->start($function);

        $result = parent::{$function}(...$args);

        $event = $this->stopwatch->stop($function);

        $this->data[$function] = [
            '_params' => $args,
            '_results' => $result,
            '_duration' => $event->getDuration(),
            '_memory' => $event->getMemory(),
        ];

        return $result;
    }


    public function index(ObjectManager $objectManager, $searchable): array
    {
        return $this->innerSearchService(__FUNCTION__, \func_get_args());
    }

    public function remove(ObjectManager $objectManager, $searchable): array
    {
        return $this->innerSearchService(__FUNCTION__, \func_get_args());
    }

    public function clear(): void
    {
        $this->innerSearchService(__FUNCTION__, \func_get_args());
    }

    public function deleteByIndexName(string $indexName): ?array
    {
        return $this->innerSearchService(__FUNCTION__, \func_get_args());
    }

    public function delete(string $key, string $table = null): bool
    {
        return $this->innerSearchService(__FUNCTION__, \func_get_args());
    }

    public function search(ObjectManager $objectManager, string $className, string $query = '', array $searchParams = []): array
    {
        return $this->innerSearchService(__FUNCTION__, \func_get_args());
    }

    public function rawSearch(string $className, string $query = '', array $searchParams = []): array
    {
        return $this->innerSearchService(__FUNCTION__, \func_get_args());
    }

    public function count(string $table = null): int
    {
        return $this->innerSearchService(__FUNCTION__, \func_get_args());
    }

    public function isSearchable($className): bool
    {
        return $this->searchService->isSearchable($className);
    }

    public function getSearchable(): array
    {
        return $this->searchService->getSearchable();
    }

    public function getConfiguration(): Collection
    {
        return $this->searchService->getConfiguration();
    }

    public function searchableAs(string $className): string
    {
        return $this->searchService->searchableAs($className);
    }

    /** @internal used in the DataCollector class */
    public function getData(): array
    {
        return $this->data;
    }
}
