<?php

declare(strict_types=1);

use Rector\CodeQuality\Rector\Class_\InlineConstructorDefaultToPropertyRector;
use Rector\Config\RectorConfig;
use Rector\Doctrine\Set\DoctrineSetList;
use Rector\Symfony\Set\SymfonySetList;
use Rector\Symfony\Set\SensiolabsSetList;
use Rector\Set\ValueObject\LevelSetList;
use Rector\CodeQuality\Rector\ClassMethod\ReturnTypeFromStrictScalarReturnExprRector;
use Rector\TypeDeclaration\Rector\ClassMethod\ReturnTypeFromStrictBoolReturnExprRector;
use Rector\TypeDeclaration\Rector\ClassMethod\ReturnTypeFromStrictNewArrayRector;
use Rector\TypeDeclaration\Rector\ClassMethod\AddReturnTypeDeclarationRector;
use Rector\Symfony\Rector\MethodCall\GetHelperControllerToServiceRector;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths([
//        __DIR__ . '/src/EventListener',
//        __DIR__ . '/src/EventSubscriber',
        __DIR__ . '/src',
    ]);

    $rectorConfig->rules([
        \Rector\Symfony\Symfony61\Rector\Class_\CommandPropertyToAttributeRector::class,
//        ReturnTypeFromStrictBoolReturnExprRector::class,
        ReturnTypeFromStrictNewArrayRector::class,
//        ReturnTypeFromStrictScalarReturnExprRector::class,
        AddReturnTypeDeclarationRector::class,
//        GetHelperControllerToServiceRector::class
    ]);

    // register a single rule
    $rectorConfig->ruleWithConfiguration(
        \Rector\Php80\Rector\Class_\AnnotationToAttributeRector::class,
        [
            new \Rector\Php80\ValueObject\AnnotationToAttribute('ApiPlatform\Core\Annotation\ApiFilter'),
            new \Rector\Php80\ValueObject\AnnotationToAttribute('ApiPlatform\Core\Annotation\ApiResource'),
            new \Rector\Php80\ValueObject\AnnotationToAttribute('ApiPlatform\Core\Annotation\ApiProperty'),
            new \Rector\Php80\ValueObject\AnnotationToAttribute('ApiPlatform\Core\Annotation\ApiSubresource'),
            new \Rector\Php80\ValueObject\AnnotationToAttribute('Gedmo\Mapping\Annotation\Tree'),
            new \Rector\Php80\ValueObject\AnnotationToAttribute('Gedmo\Mapping\Annotation\TreeRoot'),
            new \Rector\Php80\ValueObject\AnnotationToAttribute('Gedmo\Mapping\Annotation\TreeParent'),
            new \Rector\Php80\ValueObject\AnnotationToAttribute('Gedmo\Mapping\Annotation\TreeLeft'),
            new \Rector\Php80\ValueObject\AnnotationToAttribute('Gedmo\Mapping\Annotation\TreeLevel'),
            new \Rector\Php80\ValueObject\AnnotationToAttribute('Gedmo\Mapping\Annotation\TreeRight'),
            new \Rector\Php80\ValueObject\AnnotationToAttribute('Gedmo\Locale'),

            new \Rector\Php80\ValueObject\AnnotationToAttribute('Gedmo\Mapping\Annotation\Timestampable'),
            new \Rector\Php80\ValueObject\AnnotationToAttribute('Gedmo\Mapping\Annotation\Slug'),




        ]
    );

//    $rectorConfig->withAs(symfony: true)


//    $rectorConfig->rule(InlineConstructorDefaultToPropertyRector::class);
    $rectorConfig->sets([
        LevelSetList::UP_TO_PHP_83,
        DoctrineSetList::ANNOTATIONS_TO_ATTRIBUTES,
        SymfonySetList::SYMFONY_64,
        SymfonySetList::ANNOTATIONS_TO_ATTRIBUTES,
    ]);
};

