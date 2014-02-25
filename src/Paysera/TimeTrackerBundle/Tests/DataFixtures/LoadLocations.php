<?php
/**
 * Created by: Gediminas Samulis
 * Date: 2013-10-08
 */

namespace Paysera\TimeTrackerBundle\Tests\DataFixtures;


use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Paysera\TimeTrackerBundle\Entity\Location;

class LoadLocations extends AbstractFixture implements OrderedFixtureInterface
{
    function load(ObjectManager $manager)
    {
        $location1 = new Location();
        $location1->setName('P1');

        $location2 = new Location();
        $location2->setName('P2');

        $location3 = new Location();
        $location3->setName('P3');

        $manager->persist($location1);
        $manager->persist($location2);
        $manager->persist($location3);
        $manager->flush();
        $this->setReference('location1', $location1);
        $this->setReference('location2', $location2);
        $this->setReference('location3', $location3);
    }

    /**
     * Get the order of this fixture
     *
     * @return integer
     */
    function getOrder()
    {
        return 1;
    }


}