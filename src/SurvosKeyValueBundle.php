<?php declare(strict_types=1);

namespace Survos\KeyValueBundle;

use Survos\KeyValueBundle\Command\KeyValueAdd;
use Survos\KeyValueBundle\Command\KeyValueMultipleCommandsOne;
use Survos\KeyValueBundle\Command\KeyValueMultipleCommandsTwo;
use Survos\KeyValueBundle\Command\KeyValueRemove;
use Survos\KeyValueBundle\Command\KeyValueShow;
use Survos\KeyValueBundle\Entity\KeyValueManager;
use Survos\KeyValueBundle\Entity\KeyValueManagerInterface;
use Survos\KeyValueBundle\Repository\KeyValueRepository;
use Survos\KeyValueBundle\Type\DefaultType;
use Survos\KeyValueBundle\Type\EmailType;
use Survos\KeyValueBundle\Type\IpType;
use Survos\KeyValueBundle\Utils\TypeExtractorInterface;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

class SurvosKeyValueBundle extends AbstractBundle implements CompilerPassInterface
{
    const SERVICE_TAG = 'survos.key-value.type';

    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {

        foreach ([
                     KeyValueAdd::class,
                     KeyValueRemove::class,
                     KeyValueShow::class] as $commandName) {
            $builder->autowire($commandName)
                ->setAutoconfigured(true)
                ->addTag('console.command');
        }

//        $builder->autowire(KeyValueRepository::class)
//            ->setPublic(true)
//            ->setAutoconfigured(true)
//            ->setAutowired(true);

        $builder->autowire(
            KeyValueManagerInterface::class, KeyValueManager::class)
            ->setPublic(true)
            ->setAutoconfigured(true)
            ->setAutowired(true)
            ->setArgument('$config', $config)
            ->setArgument('$defaultList', $config['default_list']);

        // @todo: UserAgent type?
        foreach ([
                     EmailType::class,
                     IPType::class,
                 ] as $class) {
            $builder->autowire($class)
                ->setPublic(true)
                ->setAutoconfigured(true)
                ->setAutowired(true)
                ->addTag(self::SERVICE_TAG, [
                    'class' => $class,
                ]);
        }


    }

    public function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass($this);
    }


    public function process(ContainerBuilder $container): void
    {

        $types = $container->findTaggedServiceIds(self::SERVICE_TAG);
//        dump($types);
//        $extractor = $container->getDefinition(TypeExtractorInterface::class);
//        $extractor->setArgument('$types', array_map(fn(string $service) => new Reference($service), array_keys($types)));
    }

    private function addListsSection(ArrayNodeDefinition $rootNode): void
    {
        $rootNode
            ->children()
            ->arrayNode('lists')
            ->arrayPrototype()
            ->children()
            ->scalarNode('name')->info('the list key, e.g. banned_ip')->isRequired()->end()
            ->booleanNode('case')->info("if lookups are case-sensitive")->defaultTrue()->end()
            ->scalarNode('regex')->info("validate via regex when adding values")->end()
            ->scalarNode('type')->info("defined validation types, url, email, ip")->end()
            ->end()
            ->end()
            ->end();

    }

    public function configure(DefinitionConfigurator $definition): void
    {
        $rootNode = $definition->rootNode();
        $rootNode
            ->children()
            ->scalarNode('default_type')->cannotBeEmpty()->defaultValue(DefaultType::class)->end()
            ->scalarNode('api_key')->info('@todo: integrate with BotPathServer')->defaultNull()->end()
            ->scalarNode('default_list')->defaultValue(null)->end()
            ->booleanNode('strict')->defaultValue(true)->end()
            ->end();

        $this->addListsSection($rootNode);
    }

}
