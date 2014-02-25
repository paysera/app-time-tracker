<?php

namespace Evp\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase as BaseWebTestCase;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Component\Console\Output\Output;
use Symfony\Component\Console\Output\ConsoleOutput;

class WebTestCase extends BaseWebTestCase
{
    /**
     * Creates client and in-memory (if it is the case for test env) database.
     * WARNING! Clears all data and creates database schema. test env MUST have other doctrine config than prod env.
     *
     * @param null $dataFixturesDir
     * @return \Symfony\Bundle\FrameworkBundle\Client
     * @throws \Exception
     */
    protected function createClientWithNewDatabase($dataFixturesDir = null)
    {
        $client = static::createClient();
        $container = $client->getContainer();
        $doctrine = $container->get('doctrine');
        $entityManager = $doctrine->getManager();
        $params = $entityManager->getConnection()->getParams();
        if (!isset($params['memory']) || !$params['memory']) {
            throw new \Exception('Using not in-memory database in functional test!');
        }

        $schemaTool = new SchemaTool($entityManager);

        $mdf = $entityManager->getMetadataFactory();
        $classes = $mdf->getAllMetadata();

        $schemaTool->dropDatabase();
        $schemaTool->createSchema($classes);

        $application = new \Symfony\Bundle\FrameworkBundle\Console\Application($client->getKernel());
        $application->setAutoExit(false);
        $application->setCatchExceptions(false);
        $statusCode = $application->run(new \Symfony\Component\Console\Input\ArrayInput(array(
            'command' => 'doctrine:fixtures:load',
            '--fixtures' => $dataFixturesDir,
            '-e' => 'test',
            '-q' => null,
        )));
        if ($statusCode !== 0) {
            throw new \Exception('Load doctrine fixtures command returned status code > 0: ' . $output->output);
        }

        return $client;
    }
}