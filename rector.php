<?php

declare(strict_types=1);

use Rector\Core\Configuration\Option;
use Rector\Php80\Rector\Class_\ClassPropertyAssignToConstructorPromotionRector;
use Rector\Php80\Rector\FunctionLike\UnionTypesRector;
use Rector\Set\ValueObject\LevelSetList;
use Rector\TypeDeclaration\Rector\FunctionLike\ReturnTypeDeclarationRector;
use Rector\TypeDeclaration\Rector\Property\PropertyTypeDeclarationRector;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $parameters = $containerConfigurator->parameters();
    $parameters->set(Option::PATHS, [
        __DIR__ . '/src'
    ]);

    $containerConfigurator->import(LevelSetList::UP_TO_PHP_80);

    $services = $containerConfigurator->services();
    $services->remove(ClassPropertyAssignToConstructorPromotionRector::class);
    $services->set(ReturnTypeDeclarationRector::class);
    $services->set(PropertyTypeDeclarationRector::class);
    $services->set(UnionTypesRector::class);
};
