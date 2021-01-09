<?php

declare(strict_types=1);

namespace App;

use Exception;
use LogicException;
use Psr\Container\ContainerInterface;
use ReflectionObject;
use RuntimeException;
use Symfony\Component\Config\ConfigCache;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Loader\DelegatingLoader;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Config\Loader\LoaderResolver;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\DependencyInjection\AddConsoleCommandPass;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\DependencyInjection\Compiler\MergeExtensionConfigurationPass;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Dumper\PhpDumper;
use Symfony\Component\DependencyInjection\Loader\ClosureLoader;
use Symfony\Component\DependencyInjection\Loader\DirectoryLoader;
use Symfony\Component\DependencyInjection\Loader\GlobFileLoader;
use Symfony\Component\DependencyInjection\Loader\IniFileLoader;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\EventDispatcher\DependencyInjection\RegisterListenersPass;

use function dirname;
use function is_dir;
use function is_file;
use function is_writable;
use function mkdir;
use function preg_grep;
use function realpath;
use function scandir;
use function sprintf;
use function str_replace;
use function ucfirst;

final class Kernel
{
    /**
     * @var ContainerInterface
     */
    private ContainerInterface $container;

    /**
     * @var string
     */
    private string $projectDir;

    /**
     * @var array
     */
    private array $compilerPasses = [
        [RegisterListenersPass::class, PassConfig::TYPE_BEFORE_OPTIMIZATION, 0],
        [AddConsoleCommandPass::class, PassConfig::TYPE_BEFORE_OPTIMIZATION, 0],
    ];

    /**
     * Kernel constructor.
     * @param string $name
     * @param string $version
     * @param string $environment
     * @param bool $debug
     */
    public function __construct(
        private string $name,
        private string $version,
        private string $environment,
        private bool $debug
    ) {
        foreach (['cache' => $this->getCacheDir(), 'logs' => $this->getLogsDir()] as $name => $dir) {
            if (!is_dir($dir)) {
                if (false === mkdir($dir, 0777, true) && !is_dir($dir)) {
                    throw new RuntimeException(sprintf('Unable to create the "%s" directory (%s).', $name, $dir));
                }
            } elseif (!is_writable($dir)) {
                throw new RuntimeException(sprintf('Unable to write in the "%s" directory (%s).', $name, $dir));
            }
        }
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getVersion(): string
    {
        return $this->version;
    }

    /**
     * @return string
     */
    public function getEnvironment(): string
    {
        return $this->environment;
    }

    /**
     * @return bool
     */
    public function isDebug(): bool
    {
        return $this->debug;
    }

    /**
     * Returns the parameters.
     *
     * @return array An array of parameters
     */
    public function getParameters(): array
    {
        return [
            'kernel.name' => $this->getName(),
            'kernel.version' => $this->getVersion(),
            'kernel.environment' => $this->getEnvironment(),
            'kernel.debug' => $this->isDebug(),
            'kernel.project_dir' => realpath($this->getProjectDir()) ?: $this->getProjectDir(),
            'kernel.resources_dir' => realpath($this->getResourcesDir()) ?: $this->getResourcesDir(),
            'kernel.config_dir' => realpath($this->getConfigDir()) ?: $this->getConfigDir(),
            'kernel.cache_dir' => realpath($this->getCacheDir()) ?: $this->getCacheDir(),
            'kernel.logs_dir' => realpath($this->getLogsDir()) ?: $this->getLogsDir(),
            'kernel.sessions_dir' => realpath($this->getSessionsDir()) ?: $this->getSessionsDir(),
            'kernel.wiring_dir' => realpath($this->getWiringDir()) ?: $this->getWiringDir(),
        ];
    }

    /**
     * Gets the application root dir (path of the project's composer file).
     *
     * @return string The project root dir
     */
    public function getProjectDir(): string
    {
        if (false === isset($this->projectDir)) {
            $r = new ReflectionObject($this);

            if (!is_file($dir = $r->getFileName())) {
                throw new LogicException(
                    sprintf('Cannot auto-detect project dir for kernel of class "%s".', $r->name)
                );
            }

            $dir = $rootDir = dirname($dir);
            while (!is_file($dir.'/composer.json')) {
                if ($dir === dirname($dir)) {
                    return $this->projectDir = $rootDir;
                }
                $dir = dirname($dir);
            }
            $this->projectDir = $dir;
        }

        return $this->projectDir;
    }

    /**
     * @return string
     */
    public function getResourcesDir(): string
    {
        return $this->getProjectDir().'/resources';
    }

    /**
     * @return string
     */
    public function getConfigDir(): string
    {
        return $this->getResourcesDir().'/config';
    }

    /**
     * @return string
     */
    public function getCacheDir(): string
    {
        return $this->getProjectDir().'/var/cache/'.$this->getEnvironment();
    }

    /**
     * @return string
     */
    public function getLogsDir(): string
    {
        return $this->getProjectDir().'/var/logs/'.$this->getEnvironment();
    }

    /**
     * @return string
     */
    public function getSessionsDir(): string
    {
        return $this->getProjectDir().'/var/sessions';
    }

    /**
     * @return string
     */
    public function getWiringDir(): string
    {
        return $this->getResourcesDir().'/wiring';
    }

    /**
     * @return Application
     * @throws Exception
     */
    public function getCli(): Application
    {
        /** @var Application $cli */
        $cli = $this->getContainer()->get(Application::class);

        $definition = $cli->getDefinition();

        $definition->addOption(
            new InputOption('--env', '-e', InputOption::VALUE_REQUIRED, 'The Environment name.', $this->getEnvironment())
        );

        $definition->addOption(
            new InputOption('--no-debug', null, InputOption::VALUE_NONE, 'Switches off debug mode.')
        );

        return $cli;
    }

    /**
     * @return ContainerInterface
     * @throws Exception
     */
    public function getContainer(): ContainerInterface
    {
        if (isset($this->container)) {
            return $this->container;
        }

        $class = $this->getContainerClass();
        $cache = new ConfigCache($this->getCacheDir().'/'.$class.'.php', $this->isDebug());
        $cachePath = $cache->getPath();

        if ($this->isDebug() || !$cache->isFresh()) {
            $containerBuilder = $this->getContainerBuilder();
            $containerBuilder->compile();

            $dumper = new PhpDumper($containerBuilder);

            $content = $dumper->dump(
                [
                    'class' => $class,
                    'file' => $cachePath,
                    'debug' => $this->isDebug(),
                ]
            );

            $cache->write($content, $containerBuilder->getResources());
        }

        require $cachePath;
        $container = new $class();
        $container->set('kernel', $this);

        return $this->container = $container;
    }

    /**
     * Gets a new ContainerBuilder instance used to build the service container.
     *
     * @return ContainerBuilder
     * @throws Exception
     */
    private function getContainerBuilder(): ContainerBuilder
    {
        $container = new ContainerBuilder();
        $container->getParameterBag()->add($this->getParameters());

        self::registerSynthetic($container, 'kernel', $this::class);

        // ensure these extensions are implicitly loaded
        $container->getCompilerPassConfig()->setMergePass(new MergeExtensionConfigurationPass());

        foreach ($this->compilerPasses as [$compilerPass, $type, $priority]) {
            $container->addCompilerPass(new $compilerPass(), $type, $priority);
        }

        $loader = self::getContainerLoader($container);

        $this->loadServices($loader, $this->getWiringDir());
        $this->loadServices($loader, $this->getWiringDir().'/'.$this->getEnvironment());

        return $container;
    }

    /**
     * @param ContainerBuilder $container
     * @param string $id
     * @param string $class
     */
    private static function registerSynthetic(ContainerBuilder $container, string $id, string $class): void
    {
        $container
            ->register($id, $class)
            ->setAutoconfigured(true)
            ->setSynthetic(true)
            ->setPublic(true);

        $container
            ->setAlias($class, $id)
            ->setPublic(true);
    }

    /**
     * Returns a loader for the container.
     *
     * @param ContainerBuilder $container
     * @return DelegatingLoader
     */
    private static function getContainerLoader(ContainerBuilder $container): DelegatingLoader
    {
        $locator = new FileLocator();
        $resolver = new LoaderResolver(
            [
                new XmlFileLoader($container, $locator),
                new YamlFileLoader($container, $locator),
                new IniFileLoader($container, $locator),
                new PhpFileLoader($container, $locator),
                new GlobFileLoader($container, $locator),
                new DirectoryLoader($container, $locator),
                new ClosureLoader($container),
            ]
        );

        return new DelegatingLoader($resolver);
    }

    /**
     * Gets the container class.
     *
     * @return string The container class
     *
     */
    private function getContainerClass(): string
    {
        $class = str_replace('\\', '_', $this::class);
        $class .= ucfirst($this->environment);
        $class .= $this->debug ? 'Debug' : '';
        $class .= 'Container';

        return $class;
    }

    /**
     * @param LoaderInterface $loader
     * @param string $path
     * @throws Exception
     */
    private static function loadServices(LoaderInterface $loader, string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        foreach (preg_grep('/^.*\.(xml|yaml|yml|ini|php)$/i', scandir($path)) as $file) {
            $loader->load($path.'/'.$file);
        }
    }
}