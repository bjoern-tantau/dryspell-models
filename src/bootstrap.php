<?php

use DI\ContainerBuilder;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Driver\PDOSqlite\Driver as SqliteDriver;
use Doctrine\DBAL\Tools\Console\Helper\ConnectionHelper;
use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\Configuration\Connection\ConnectionLoader;
use Doctrine\Migrations\Configuration\Connection\ExistingConnection;
use Doctrine\Migrations\Configuration\Migration\ConfigurationLoader;
use Doctrine\Migrations\Configuration\Migration\ExistingConfiguration;
use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\Provider\SchemaProvider;
use Doctrine\Migrations\Tools\Console\Command\DiffCommand as DoctrineDiffCommand;
use Dryspell\Console\Commands\DiffCommand;
use Dryspell\Migrations\SchemaProvider as SchemaProvider2;
use Dryspell\Models\BackendInterface;
use Dryspell\Models\Backends\Doctrine;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Helper\QuestionHelper;
use function DI\autowire;
use function DI\get;

require_once __DIR__ . '/../vendor/autoload.php';

$builder                   = new ContainerBuilder();
$builder->useAutowiring(true);
$builder->addDefinitions([
    BackendInterface::class => autowire(Doctrine::class),
    Driver::class           => autowire(SqliteDriver::class),
    HelperSet::class        => function (ContainerInterface $container) {
        return new HelperSet([
        'db'     => $container->get(ConnectionHelper::class),
        'dialog' => $container->get(QuestionHelper::class),
        ]);
    },
    Configuration::class       => autowire(Configuration::class)
        ->method('addMigrationsDirectory', 'Dryspell\\Migrations', __DIR__ . '/../migrations/')
    ,
    Connection::class          => autowire(Connection::class)
        ->constructorParameter('params', [
            'path' => 'db.sqlite',
        ])
    ,
    ConnectionLoader::class    => autowire(ExistingConnection::class),
    ConfigurationLoader::class => autowire(ExistingConfiguration::class),
    DependencyFactory::class   => function (ConfigurationLoader $configurationLoader,
                                            ConnectionLoader $connectionLoader, ContainerInterface $container) {
        $factory = DependencyFactory::fromConnection($configurationLoader, $connectionLoader);
        $factory->setDefinition(SchemaProvider::class, fn() => $container->get(SchemaProvider::class));
        return $factory;
    },
    SchemaProvider::class      => autowire(SchemaProvider2::class),
    DiffCommand::class         => autowire()
        ->constructorParameter('dependencyFactory', get(DependencyFactory::class)),
    DoctrineDiffCommand::class => autowire()
        ->constructorParameter('dependencyFactory', get(DependencyFactory::class)),
]);
return $builder->build();
