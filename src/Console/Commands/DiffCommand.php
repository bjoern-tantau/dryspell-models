<?php
namespace Dryspell\Console\Commands;

use Doctrine\DBAL\Migrations\Tools\Console\Command\DiffCommand as DoctrineDiffCommand;
use Dryspell\Migrations\SchemaProvider;
use Dryspell\Models\ObjectInterface;
use hanneskod\classtools\Iterator\ClassIterator;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;

/**
 * Command for generate migration classes by comparing your current database schema
 * to your mapping information.
 *
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link    www.doctrine-project.org
 * @since   2.0
 * @author  Jonathan Wage <jonwage@gmail.com>
 */
class DiffCommand extends DoctrineDiffCommand
{

    /**
     *
     * @var Finder
     */
    private $finder;

    /**
     * Container to construct objects with
     *
     * @var ContainerInterface
     */
    private $container;

    /**
     * Forcing Dryspell's SchemaProvider
     *
     * @param SchemaProvider $schemaProvider
     * @param Finder $finder
     */
    public function __construct(SchemaProvider $schemaProvider, Finder $finder, ContainerInterface $container)
    {
        $this->finder    = $finder;
        $this->container = $container;
        parent::__construct($schemaProvider);
    }

    protected function configure()
    {
        parent::configure();

        $this
            ->addOption('models-path', null, InputOption::VALUE_REQUIRED, 'Directory, containing all models to generate migrations for.', getcwd())
            ->addOption('models-namespace', null, InputOption::VALUE_REQUIRED, 'Namespace, containing all models to generate migrations for.')
            ->addOption('models-interface', null, InputOption::VALUE_REQUIRED, 'Interface, all models implement that should be migrations generated for.', ObjectInterface::class)
        ;
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $classes = new ClassIterator($this->finder->in($input->getOption('models-path')));
        if ($input->hasOption('models-namespace')) {
            $classes = $classes->inNamespace($input->getOption('models-namespace'));
        }
        foreach ($classes->type($input->getOption('models-interface')) as $class) {
            /* @var $class \ReflectionClass */
            if ($class->isInstantiable()) {
                $object = $this->container->get($class->getName());
                $this->schemaProvider->addObject($object);
            }
        }

        return parent::execute($input, $output);
    }
}
