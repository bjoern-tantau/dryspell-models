<?php
namespace Dryspell\Console\Commands;

use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\Tools\Console\Command\DiffCommand as DoctrineDiffCommand;
use Doctrine\Migrations\Tools\Console\Command\DoctrineCommand;
use Dryspell\Migrations\SchemaProvider;
use Dryspell\Models\ObjectInterface;
use hanneskod\classtools\Iterator\ClassIterator;
use Psr\Container\ContainerInterface;
use ReflectionClass;
use Symfony\Component\Console\Input\InputArgument;
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
class DiffCommand extends DoctrineCommand
{

    /** @var string */
    protected static $defaultName = 'migrations:diff';

    /**
     * Forcing Dryspell's SchemaProvider
     *
     * @param SchemaProvider $schemaProvider
     * @param Finder $finder
     */
    public function __construct(private Finder $finder, private ContainerInterface $container,
                                private DoctrineDiffCommand $doctrineDiff, ?DependencyFactory $dependencyFactory = null,
                                ?string $name = null)
    {
        parent::__construct($dependencyFactory, $name);
    }

    protected function configure(): void
    {
        parent::configure();

        $this->setAliases($this->doctrineDiff->getAliases())
            ->setDescription($this->doctrineDiff->getDescription())
            ->setHelp($this->doctrineDiff->getHelp())
        ;
        $this->getDefinition()->addArguments($this->doctrineDiff->getDefinition()->getArguments());
        $this->getDefinition()->addOptions($this->doctrineDiff->getDefinition()->getOptions());

        $this
            ->addOption('models-path', null, InputOption::VALUE_REQUIRED, 'Directory, containing all models to generate migrations for.', 'src')
            ->addOption('models-namespace', null, InputOption::VALUE_REQUIRED, 'Namespace, containing all models to generate migrations for.')
            ->addOption('models-interface', null, InputOption::VALUE_REQUIRED, 'Interface, all models implement that should be migrations generated for.', ObjectInterface::class)
        ;

        $this->doctrineDiff
            ->addArgument('migrations:diff')
            ->addOption('models-namespace')
            ->addOption('models-interface')
            ->addOption('models-path')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $classes = new ClassIterator($this->finder->in($input->getOption('models-path')));
        if ($input->hasOption('models-namespace')) {
            $classes = $classes->inNamespace($input->getOption('models-namespace'));
        }
        foreach ($classes->type($input->getOption('models-interface')) as $class) {
            /* @var $class ReflectionClass */
            if ($class->isInstantiable()) {
                $object = $this->container->get($class->getName());
                $this->getDependencyFactory()->getSchemaProvider()->addObject($object);
            }
        }

        $reflector     = new \ReflectionObject($this->doctrineDiff);
        $ioProperty    = $reflector->getProperty('io');
        $ioProperty->setAccessible(true);
        $ioProperty->setValue($this->doctrineDiff, $this->io);
        $executeMethod = $reflector->getMethod('execute');
        $executeMethod->setAccessible(true);
        return $executeMethod->invoke($this->doctrineDiff, $input, $output);
    }
}
