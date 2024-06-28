<?php

namespace Survos\KeyValueBundle\Tests;


// this is in APP, waiting for https://github.com/symfony/symfony/discussions/57587 to move to bundle

use League\Csv\Exception;
use PHPUnit\Framework\Attributes\Test;
use Survos\KeyValueBundle\Service\KeyValueService;
use Survos\KeyValueBundle\StorageBox;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class KeyValueTest extends KernelTestCase
{
    public function testCreateTables(): void
    {
        $kernel = self::bootKernel([
            'environment' => 'test',
        ]);

        $this->assertSame('test', $kernel->getEnvironment());
        // $routerService = static::getContainer()->get('router');
        $filename = 'test.pixie.db';
        $kvService = static::getContainer()->get(KeyValueService::class);
        $movieTableName = 'movie';
        $kv = $kvService->getStorageBox($filename, [
            $movieTableName => 'imdb_id|int,name'
        ]);
        $this->assertEquals(1, count($kv->getTables()), "bad table count");
        $kv->select($movieTableName);
        $this->assertEquals(0, $kv->count(), "$movieTableName should be empty");
        $this->expectException(\LogicException::class);
        $kv->select('badTable');


    }

    #[Test]
    public function parseIndex(): void
    {
        $indexArray = StorageBox::getIndexDefinitions('id,name|text');
        foreach ($indexArray as $index) {
            $indexes[$index->propertyName] = $index;
        }
        $this->assertTrue($indexes['id']->isPrimary);
        $this->assertNotTrue($indexes['name']->isPrimary, json_encode($indexes['name']));
        $this->assertSame('TEXT', $indexes['name']->type, json_encode($indexes['name']));
        $this->assertSame('TEXT', $indexes['id']->type);


    }


}
