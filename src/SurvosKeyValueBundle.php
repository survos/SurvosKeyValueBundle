<?php

/** generated from /home/tac/ca/survos/packages/maker-bundle/templates/skeleton/bundle/src/Bundle.tpl.php */

namespace Survos\KeyValueBundle;

use App\Controller\PixyController;
use Survos\ApiGrid\Controller\GridController;
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

        $builder->autowire(PixyController::class)
            ->addTag('container.service_subscriber')
            ->addTag('controller.service_arguments')
//            ->setArgument('$chartBuilder', new Reference('chartjs.builder', ContainerInterface::NULL_ON_INVALID_REFERENCE))
//            ->setAutoconfigured(true)
//            ->setAutowired(true)
//            ->setPublic(true)
        ;

        // storageBoxService, right?  Then get an instance of the storageBox? PixyService?
        $builder->register(StorageBox::class)
            ->setAutowired(true)
            ->setArgument('$logger', new Reference('logger'))
            ;

        $x = $builder->register(KeyValueService::class)
            ->setAutowired(true)
            ->setArgument('$logger', new Reference('logger'))
        ;

        $x = $builder->register(PixyImportService::class)
            ->setAutowired(true)
            ->setArgument('$dataDir', $config['directory'])
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
            ->scalarNode('directory')->defaultValue('./')->end()
            ->end();
    }
}
