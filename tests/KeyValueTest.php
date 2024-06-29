<?php

namespace Survos\KeyValueBundle\Tests;


// this is in APP, waiting for https://github.com/symfony/symfony/discussions/57587 to move to bundle

use League\Csv\Exception;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\Attributes\Test;
use Survos\KeyValueBundle\Model\Table;
use Survos\KeyValueBundle\Service\KeyValueService;
use Survos\KeyValueBundle\StorageBox;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class KeyValueTest extends KernelTestCase
{
    const FILENAME='test.pixie.db';
    const MOVIE_TABLE_NAME='movie';
    #[Test]
    public function createTables(): void
    {
        $kernel = self::bootKernel([
            'environment' => 'test',
        ]);
        $movieTableName = self::MOVIE_TABLE_NAME;

        $this->assertSame('test', $kernel->getEnvironment());
        // $routerService = static::getContainer()->get('router');
         $kvService = static::getContainer()->get(KeyValueService::class);


         $kv = $kvService->getStorageBox(self::FILENAME, [
             self::MOVIE_TABLE_NAME => 'imdb_id|int,name'
         ]);
        $this->assertEquals(1, count($kv->getTables()), "bad table count");
         $kv->select(self::MOVIE_TABLE_NAME);
         $this->assertEquals(0, $kv->count(), self::MOVIE_TABLE_NAME . " should be empty");

        $tables = $kv->inspectSchema();
        $this->assertContains($movieTableName, array_keys($tables));
        /** @var Table $movieTable */
        $movieTable = $tables[$movieTableName];
        $this->assertEquals(Table::class, get_class($movieTable));
        $this->assertSame('imdb_id', $kv->getPrimaryKey());
        $this->assertSame($kv->getPrimaryKey(), $movieTable->getPkName());

        // @todo: end the exception expectation
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

    #[Test]
    #[Depends('createTables')]
    public function addData(): void
    {
        $kernel = self::bootKernel([
//            'environment' => 'test',
        ]);
        $movieTableName = self::MOVIE_TABLE_NAME;
        $kvService = static::getContainer()->get(KeyValueService::class);
        $kv = $kvService->getStorageBox(self::FILENAME);
        $this->assertEquals(1, count($kv->getTables()), "bad table count");
        $tables = $kv->inspectSchema();

        $this->assertContains($movieTableName, array_keys($tables));
        $movieTable = $tables[$movieTableName];
        $this->assertEquals(Table::class, get_class($movieTable));


    }


}
