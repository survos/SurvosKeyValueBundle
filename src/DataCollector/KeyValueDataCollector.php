<?php

declare(strict_types=1);

namespace Survos\KeyValueBundle\DataCollector;

use Psr\Log\LoggerInterface;
use Survos\KeyValueBundle\Debug\TraceableStorageBox;
use Survos\KeyValueBundle\Service\KeyValueService;
use Symfony\Bundle\FrameworkBundle\DataCollector\AbstractDataCollector;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @author Antoine Makdessi <amakdessi@me.com>
 */
final class KeyValueDataCollector extends AbstractDataCollector
{

    public function __construct(
        private KeyValueService $keyValueService,
    )
    {
    }
    public function collect(Request $request, Response $response, \Throwable $exception = null): void
    {
        $data = $this->keyValueService->getData();

        $this->data[$this->getName()] = !empty($data) ? $this->cloneVar($data) : null;
//        dd($this->data);
    }

    public function getName(): string
    {
        return self::class;
    }


    /** @internal used in the DataCollector view template */
    public function getCollectedData(): mixed
    {
        $data = $this->data[$this->getName()] ?? null;
        return $data;
    }
}
