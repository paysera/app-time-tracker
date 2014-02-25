<?php
/**
 * Created by: Gediminas Samulis
 * Date: 2013-10-09
 */

namespace Paysera\TimeTrackerBundle\DataFixtures\ORM;


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

        $location4 = new Location();
        $location4->setName('P4');

        $location5 = new Location();
        $location5->setName('P5');

        $location6 = new Location();
        $location6->setName('P6');

        $location7 = new Location();
        $location7->setName('Pc');

        $location8 = new Location();
        $location8->setName('KUN');

        $location9 = new Location();
        $location9->setName('DOM');

        $manager->persist($location1);
        $manager->persist($location2);
        $manager->persist($location3);
        $manager->persist($location4);
        $manager->persist($location5);
        $manager->persist($location6);
        $manager->persist($location7);
        $manager->persist($location8);
        $manager->persist($location9);
        $manager->flush();

        $this->setReference('P1', $location1);
        $this->setReference('P2', $location2);
        $this->setReference('P3', $location3);
        $this->setReference('P4', $location4);
        $this->setReference('P5', $location5);
        $this->setReference('P6', $location6);
        $this->setReference('Pc', $location7);
        $this->setReference('KUN', $location8);
        $this->setReference('DOM', $location9);
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