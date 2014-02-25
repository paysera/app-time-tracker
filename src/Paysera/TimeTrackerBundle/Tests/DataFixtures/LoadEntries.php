<?php
/**
 * Created by: Gediminas Samulis
 * Date: 2013-10-08
 */

namespace Paysera\TimeTrackerBundle\Tests\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Evp\Component\Money\Money;
use Paysera\TimeTrackerBundle\Entity\Entry;
use Paysera\TimeTrackerBundle\Entity\Price;

class LoadEntries extends AbstractFixture implements OrderedFixtureInterface
{
    function load(ObjectManager $manager)
    {
        $entry1 = new Entry();
        $entry1->setTransactionKey('abcdef1');
        $entry1->setNumber('ABC666');
        $entry1->setStatus(Entry::STATUS_ACTIVE);
        $entry1->setPrice(new Money('13', 'EUR'));
        $entry1->setNextReservationAt(new \DateTime('+7 days'));
        $entry1->setLocation($this->getReference('location1'));

        $manager->persist($entry1);
        $manager->flush();
    }

    /**
     * Get the order of this fixture
     *
     * @return integer
     */
    function getOrder()
    {
        return 3;
    }


}