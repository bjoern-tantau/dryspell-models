<?php
namespace Dryspell\Console;

/**
 * Application with added option
 *
 * @author Björn Tantau <bjoern@bjoern-tantau.de>
 */
class Application extends \Symfony\Component\Console\Application
{

    protected function getDefaultInputDefinition(): \Symfony\Component\Console\Input\InputDefinition
    {
        $definition = parent::getDefaultInputDefinition();
        $definition->addOption(new \Symfony\Component\Console\Input\InputOption('bootstrap', 'b', \Symfony\Component\Console\Input\InputOption::VALUE_REQUIRED, 'Bootstrap-File, returning a ContainerInterface.'));
        return $definition;
    }
}
