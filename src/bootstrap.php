<?php

use DI\ContainerBuilder;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Driver\PDOSqlite\Driver as SqliteDriver;
use Doctrine\DBAL\Migrations\Configuration\Configuration;
use Doctrine\DBAL\Migrations\Tools\Console\Helper\ConfigurationHelper;
use Doctrine\DBAL\Migrations\Tools\Console\Helper\ConfigurationHelperInterface;
use Doctrine\DBAL\Tools\Console\Helper\ConnectionHelper;
use Dryspell\Models\BackendInterface;
use Dryspell\Models\Backends\Doctrine;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Helper\QuestionHelper;
use function DI\autowire;
use function DI\get;

require_once __DIR__ . '/../vendor/autoload.php';

$builder                            = new ContainerBuilder();
$builder->useAutowiring(true);
$builder->addDefinitions([
    BackendInterface::class => autowire(Doctrine::class),
    Driver::class           => autowire(SqliteDriver::class),
    HelperSet::class        => function(ContainerInterface $container) {
        return new HelperSet([
            'db'            => $container->get(ConnectionHelper::class),
            'dialog'        => $container->get(QuestionHelper::class),
            'configuration' => $container->get(ConfigurationHelperInterface::class),
        ]);
    },
    ConfigurationHelperInterface::class => autowire(ConfigurationHelper::class)
        ->constructorParameter('configuration', get(Configuration::class))
    ,
    Configuration::class                => autowire(Configuration::class)
        ->methodParameter('setMigrationsNamespace', 'migrationsNamespace', 'Dryspell\\Migrations')
        ->methodParameter('setMigrationsDirectory', 'migrationsDirectory', __DIR__ . '/../migrations/')
    ,
    Connection::class                   => autowire(Connection::class)
        ->constructorParameter('params', [
            'path' => 'db.sqlite',
        ])
    ,
]);
return $builder->build();
