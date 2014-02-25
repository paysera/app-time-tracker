<?php

namespace Evp\Tests;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMException;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Dumper\PhpDumper;
use Symfony\Component\DependencyInjection\Scope;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver as AnnotationDriverORM;
use Symfony\Bridge\Doctrine\Mapping\Driver\XmlDriver;

/**
 * The general ideas on mock implementation is used
 * from EVP tests
 */
abstract class BaseTestCase extends \PHPUnit_Framework_TestCase
{
    protected $requestHeaders;
    protected $container;
    protected $kernel;

    /**
     * Should return namespace of entities in that bundle, ie 'Evp\Bundle\CurrencyBundle\Entity'
     *
     * @return string
     */
    protected function getEntityNamespace()
    {
        $reflection = new \ReflectionClass($this);
        return preg_replace('/\\\\Tests.*$/', '\Entity', $reflection->getNamespaceName());
    }

    protected function getCurrentDir()
    {
        $reflection = new \ReflectionClass($this);
        return preg_replace('#Tests/.*$#', 'Tests', $reflection->getFileName());
    }

    protected function getTempDir()
    {
        return '/tmp';
    }

    static public function assertSaneContainer(Container $container, $message = 'Some of container services are invalid')
    {
        $errors = array();
        foreach ($container->getServiceIds() as $id) {
            try {
                $container->get($id);
            } catch (\Exception $e) {
                $errors[$id] = $e->getMessage();
            }
        }

        self::assertEquals(array(), $errors, $message);
    }

    protected function getContainerBuilder()
    {
        $tmpDir = $this->getTempDir();
        // \sys_get_temp_dir()
        $container = new ContainerBuilder();
        $container->addScope(new Scope('request'));
        $container->register('request', 'Symfony\\Component\\HttpFoundation\\Request')->setScope('request');
        $container->setParameter('kernel.debug', false);
        $container->setParameter('kernel.root_dir', $tmpDir);
        $container->setParameter('kernel.cache_dir', $tmpDir);
        $container->setParameter('kernel.bundles', array());
        return $container;
    }

    protected function getBaseKernelMock()
    {
        return $this->getMockBuilder('Symfony\\Component\\HttpKernel\\Kernel')
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * Get mock entity manager with fixtures schema
     * @param array $fixtures
     *
     * @return EntityManager
     */
    protected function getMockEntityManager($fixtures = array())
    {
        $em = $this->getMockSqliteEntityManager();

        $schemaTool = new \Doctrine\ORM\Tools\SchemaTool($em);
        $schemaTool->dropSchema(array());

        $mappings = array();
        foreach($fixtures as $fixture) {
            $mappings[] = $em->getClassMetadata($fixture);
        }
        $schemaTool->createSchema($mappings);

        return $em;
    }

    /**
     * Get mappings dir related to namespaces
     * Used to locate entities and mapping xml files
     *
     * @return array
     */
    protected function getMappingsNamespaces() {
        $dir = $this->getCurrentDir();
        $mappingsDir = $dir . '/../Resources/config/doctrine';
        return array(
            $mappingsDir => $this->getEntityNamespace()
        );
    }

    /**
     * EntityManager mock object together with
     * annotation mapping driver and pdo_sqlite
     * database in memory
     *
     * @return EntityManager
     */
    protected function getMockSqliteEntityManager()
    {
        $conn = array(
            'driver' => 'pdo_sqlite',
            'memory' => true,
        );
        $dir = $this->getCurrentDir();
        $tmpDir = $this->getTempDir();

        $config = $this->getMock('Doctrine\ORM\Configuration');
        $config->expects($this->once())
            ->method('getProxyDir')
            ->will($this->returnValue($tmpDir));

        $config->expects($this->once())
            ->method('getProxyNamespace')
            ->will($this->returnValue('EntityProxy'));

        $config->expects($this->once())
            ->method('getAutoGenerateProxyClasses')
            ->will($this->returnValue(true));

        $config->expects($this->once())
            ->method('getClassMetadataFactoryName')
            ->will($this->returnValue('Doctrine\\ORM\\Mapping\\ClassMetadataFactory'));

        $config->expects($this->any())
            ->method('getEntityNamespace')
            ->will($this->returnCallback(array($this, 'getNamespace')));

        $mappingsNamespaces = $this->getMappingsNamespaces();
        $driver = new XmlDriver(array_keys($mappingsNamespaces));
        $driver->setNamespacePrefixes($mappingsNamespaces);

        $config->expects($this->any())
            ->method('getMetadataDriverImpl')
            ->will($this->returnValue($driver));

        $evm = $this->getMock('Doctrine\Common\EventManager');
        $em = \Doctrine\ORM\EntityManager::create($conn, $config, $evm);

        return $em;
    }

    public function getNamespace($entityNamespaceAlias)
    {
        if (substr($entityNamespaceAlias, 0, 3) == 'Evp') {
            return 'Evp\\Bundle\\' . substr($entityNamespaceAlias, 3) . '\\Entity';
        } else {
            throw ORMException::unknownEntityNamespace($entityNamespaceAlias);
        }
    }

    protected function forcePropertyChange($object, $property, $value)
    {
        $class = new \ReflectionClass($object);
        $propertyReflection = $class->getProperty($property);
        $propertyReflection->setAccessible(true);
        $propertyReflection->setValue($object, $value);
    }

}