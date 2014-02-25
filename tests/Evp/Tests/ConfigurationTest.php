<?php

namespace Evp\Tests;

/**
 * Base test case
 */
class ConfigurationTest extends BaseTestCase
{
    /**
     * Bundle services prefix
     */
    const BUNDLE_SERVICE_PREFIX = 'paysera_';

    /**
     * Service ids to ignore loading
     *
     * @var array
     */
    protected $ignoreList = array();

    /**
     * @var \Symfony\Component\DependencyInjection\Container
     */
    protected $container;

    /**
     * Test initialization
     */
    public function setUp()
    {
        require_once __DIR__ . '/../../../app/AppKernel.php';

        $kernel = new \AppKernel('dev', true);
        $kernel->boot();

        $this->container = $kernel->getContainer();
    }

    /**
     * Test if service configuration is correct
     */
    public function testServicesConfiguration()
    {
        foreach ($this->getBundleServices() as $serviceId) {
            if (in_array($serviceId, $this->ignoreList)) {
                continue;
            }

            $service = $this->container->get($serviceId);
            $this->assertNotEmpty($service);
        }
    }

    /**
     * Get service ids of current bundle
     *
     * @return array
     */
    protected function getBundleServices()
    {
        return array_filter($this->container->getServiceIds(), array($this, 'isThisBundleService'));
    }

    /**
     * Check if service id is from this bundle
     *
     * @param string $name
     *
     * @return integer
     */
    protected function isThisBundleService($name)
    {
        return preg_match('/^' . static::BUNDLE_SERVICE_PREFIX . '/i', $name);
    }
}