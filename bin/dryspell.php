<?php

use Doctrine\DBAL\Migrations\Tools\Console\Command\ExecuteCommand;
use Doctrine\DBAL\Migrations\Tools\Console\Command\GenerateCommand;
use Doctrine\DBAL\Migrations\Tools\Console\Command\MigrateCommand;
use Doctrine\DBAL\Migrations\Tools\Console\Command\StatusCommand;
use Doctrine\DBAL\Migrations\Tools\Console\Command\VersionCommand;
use Dryspell\Console\Application;
use Dryspell\Console\Commands\DiffCommand;
use Dryspell\Migrations\Exception;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\CommandLoader\ContainerCommandLoader;
use Symfony\Component\Console\Helper\HelperSet;

require_once __DIR__ . '/../vendor/autoload.php';

$options   = getopt('b:', ['bootstrap:']);
$bootstrap = $options['bootstrap'] ?? $options['b'] ?? 'src/bootstrap.php';
$container = include $bootstrap;

if (!($container instanceof ContainerInterface)) {
    var_dump($options);
    throw new Exception('No Container found. Add a file returning a ContainerInterface with the --bootstrap option.');
}

$commandLoader = new ContainerCommandLoader($container, [
    'migrations:diff'     => DiffCommand::class,
    'migrations:execute'  => ExecuteCommand::class,
    'migrations:generate' => GenerateCommand::class,
    'migrations:migrate'  => MigrateCommand::class,
    'migrations:status'   => StatusCommand::class,
    'migrations:version'  => VersionCommand::class,
    ]);

$helperSet = $container->get(HelperSet::class);

$project = json_decode(file_get_contents(__DIR__ . '/../composer.json'));
$app     = new Application($project->name);
$app->setHelperSet($helperSet);
$app->setCommandLoader($commandLoader);
$app->run();