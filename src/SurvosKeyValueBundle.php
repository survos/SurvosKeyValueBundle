<?php

/** generated from /home/tac/ca/survos/packages/maker-bundle/templates/skeleton/bundle/src/Bundle.tpl.php */

namespace Survos\KeyValueBundle;

use Survos\ApiGrid\Controller\GridController;
use Survos\KeyValueBundle\Controller\PixyController;
use Survos\KeyValueBundle\DataCollector\KeyValueDataCollector;
use Survos\KeyValueBundle\Debug\TraceableStorageBox;
use Survos\KeyValueBundle\Event\CsvHeaderEvent;
use Survos\KeyValueBundle\EventListener\CsvHeaderEventListener;
use Survos\KeyValueBundle\Service\KeyValueService;
use Survos\KeyValueBundle\Service\PixyImportService;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

class SurvosKeyValueBundle extends AbstractBundle
{
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {

        $x = $builder->register(PixyImportService::class)
            ->setAutowired(true)
            ->setArgument('$dataDir', $config['directory'])
        ;

        $builder->autowire(PixyController::class)
            ->addTag('container.service_subscriber')
            ->addTag('controller.service_arguments')
//            ->setArgument('$chartBuilder', new Reference('chartjs.builder', ContainerInterface::NULL_ON_INVALID_REFERENCE))
//            ->setAutoconfigured(true)
//            ->setAutowired(true)
//            ->setPublic(true)
        ;

        $builder->autowire(KeyValueDataCollector::class)
            ->setArgument('$keyValueService', new Reference(KeyValueService::class))
//            ->setArgument('$logger', new Reference('logger'))
            ->addTag('data_collector', [
                'template' => '@SurvosKeyValue/DataCollector/pixy_debug_profile.html.twig'
            ]);


        // storageBoxService, right?  Then get an instance of the storageBox? PixyService?
        foreach ([StorageBox::class, TraceableStorageBox::class] as $storageBoxClass) {
            $builder->register($storageBoxClass)
                ->setAutowired(true)
                ->setArgument('$logger', new Reference('logger'))
            ;

        }

        $x = $builder->register(KeyValueService::class)
            ->setAutowired(true)
            ->setArgument('$isDebug', $builder->getParameter('kernel.debug'))
            ->setArgument('$dataDir', $config['directory'])
            ->setArgument('$stopwatch', new Reference('debug.stopwatch'))
            ->setArgument('$logger', new Reference('logger'))
        ;

        // register our listener.  We could disable or set priority in the config
        $builder->register(CsvHeaderEventListener::class)
            ->addTag('kernel.event_listener', [
                'method' => 'onCsvHeaderEvent',
                'event' => CsvHeaderEvent::class])
            ->setAutowired(true)
        ;

    }

    public function configure(DefinitionConfigurator $definition): void
    {
        $definition->rootNode()
            ->children()
            ->scalarNode('directory')->info("where to store the pixy db files")->defaultValue('./data')->end()
            ->scalarNode('extension')->info("the pixy db extension")->defaultValue('.pixy.db')->end()
            ->scalarNode('config_directory')->info("location of .pixy.yaml config files")->defaultValue('./pixy')->end()
            ->end();
    }
}
