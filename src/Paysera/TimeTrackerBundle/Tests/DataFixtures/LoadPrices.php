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
use Paysera\TimeTrackerBundle\Entity\Price;

class LoadPrices extends AbstractFixture implements OrderedFixtureInterface
{
    function load(ObjectManager $manager)
    {
        $price1 = new Price();
        $price1->setActive(true);
        $price1->setPrice(new Money(6, 'EUR'));
        $price1->setPeriod(3600);
        $price1->setPeriodFrom(1);
        $price1->setPeriodTo(36000);
        $price1->setLocation($this->getReference('location1'));

        $price2 = new Price();
        $price2->setActive(true);
        $price2->setPrice(new Money(60, 'EUR'));
        $price2->setPeriod(3600 * 24);
        $price2->setPeriodFrom(3600 * 10 + 1);
        $price2->setPeriodTo(3600 * 24 * 31);
        $price2->setLocation($this->getReference('location1'));

        $manager->persist($price1);
        $manager->persist($price2);
        $manager->flush();
    }

    /**
     * Get the order of this fixture
     *
     * @return integer
     */
    function getOrder()
    {
        return 2;
    }


}