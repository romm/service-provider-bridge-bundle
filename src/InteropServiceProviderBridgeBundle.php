<?php

namespace TheCodingMachine\Interop\ServiceProviderBridgeBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use TheCodingMachine\ServiceProvider\Registry;
use TheCodingMachine\ServiceProvider\RegistryInterface;

class InteropServiceProviderBridgeBundle extends Bundle implements RegistryProviderInterface
{
    private $serviceProviders;
    private $useDiscovery;
    private $id;

    private static $count = 0;

    /**
     * @param array $serviceProviders An array of service providers, in the format specified in thecodingmachine/service-provider-registry: https://github.com/thecodingmachine/service-provider-registry#how-does-it-work
     * @param bool $useDiscovery
     */
    public function __construct(array $serviceProviders = [], $useDiscovery = true)
    {
        $this->serviceProviders = $serviceProviders;
        $this->useDiscovery = $useDiscovery;
        $this->id = self::$count;
        $this->name = $this->getName() . $this->id;

        self::$count++;
    }

    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new ServiceProviderCompilationPass($this->id, $this));
    }

    /**
     * At boot time, let's fill the container with the registry.
     */
    public function boot()
    {
        $registryServiceName = 'service_provider_registry_'.$this->id;
        $this->container->set($registryServiceName, $this->getRegistry($this->container));
    }

    /**
     * When the Kernel shuts down, this bundle's static identifier must be
     * reset, otherwise it may lead to incorrect identifier binding for the
     * compiled container.
     */
    public function shutdown()
    {
        self::$count = 0;
    }

    /**
     * @param ContainerInterface $container
     * @return RegistryInterface
     */
    public function getRegistry(ContainerInterface $container)
    {
        $discovery = null;
        if ($this->useDiscovery) {
            $discovery = \TheCodingMachine\Discovery\Discovery::getInstance();
        }

        // In parallel, let's merge the registry:
        $registry = new Registry($this->serviceProviders, $discovery);
        return $registry;
    }
}
