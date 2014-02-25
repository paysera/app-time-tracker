<?php
/**
 * Created by: Gediminas Samulis
 * Date: 2013-10-08
 */

namespace Paysera\TimeTrackerBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Evp\Component\Money\Money;
use Paysera\TimeTrackerBundle\Entity\Price;

class LoadPrices extends AbstractFixture implements OrderedFixtureInterface
{
    function load(ObjectManager $manager)
    {
        //P1
        $price1 = new Price();
        $price1->setActive(true);
        $price1->setPrice(new Money(6, 'LTL')); 
        $price1->setPeriod(3600);
        $price1->setPeriodFrom(null);
        $price1->setPeriodTo(3600);
        $price1->setLocation($this->getReference('P1'));
        $manager->persist($price1);

        $price2 = new Price();
        $price2->setActive(true);
        $price2->setPrice(new Money(30, 'LTL'));
        $price2->setPeriod(3600 * 24);
        $price2->setPeriodFrom(null);
        $price2->setPeriodTo(null);
        $price2->setLocation($this->getReference('P1'));
        $manager->persist($price2);

        //P2
        $price1 = new Price();
        $price1->setActive(true);
        $price1->setPrice(new Money(6, 'LTL'));
        $price1->setPeriod(3600);
        $price1->setPeriodFrom(null);
        $price1->setPeriodTo(3600 * 24);
        $price1->setLocation($this->getReference('P2'));
        $manager->persist($price1);

        $price2 = new Price();
        $price2->setActive(true);
        $price2->setPrice(new Money(60, 'LTL'));
        $price2->setPeriod(3600 * 24);
        $price2->setPeriodFrom(null);
        $price2->setPeriodTo(null);
        $price2->setLocation($this->getReference('P2'));
        $manager->persist($price2);

        //P3
        $price1 = new Price();
        $price1->setActive(true);
        $price1->setPrice(new Money(6, 'LTL'));
        $price1->setPeriod(3600);
        $price1->setPeriodFrom(null);
        $price1->setPeriodTo(3600 * 24);
        $price1->setLocation($this->getReference('P3'));
        $manager->persist($price1);

        $price2 = new Price();
        $price2->setActive(true);
        $price2->setPrice(new Money(60, 'LTL'));
        $price2->setPeriod(3600 * 24);
        $price2->setPeriodFrom(null);
        $price2->setPeriodTo(null);
        $price2->setLocation($this->getReference('P3'));
        $manager->persist($price2);

        //P4
        $price1 = new Price();
        $price1->setActive(true);
        $price1->setPrice(new Money(6, 'LTL'));
        $price1->setPeriod(3600);
        $price1->setPeriodFrom(null);
        $price1->setPeriodTo(3600);
        $price1->setLocation($this->getReference('P4'));
        $manager->persist($price1);

        $price2 = new Price();
        $price2->setActive(true);
        $price2->setPrice(new Money(25, 'LTL'));
        $price2->setPeriod(3600 * 24);
        $price2->setPeriodFrom(null);
        $price2->setPeriodTo(null);
        $price2->setLocation($this->getReference('P4'));
        $manager->persist($price2);

        //P5
        $price1 = new Price();
        $price1->setActive(true);
        $price1->setPrice(new Money(6, 'LTL'));
        $price1->setPeriod(3600);
        $price1->setPeriodFrom(null);
        $price1->setPeriodTo(3600);
        $price1->setLocation($this->getReference('P5'));
        $manager->persist($price1);

        $price2 = new Price();
        $price2->setActive(true);
        $price2->setPrice(new Money(20, 'LTL'));
        $price2->setPeriod(3600 * 24);
        $price2->setPeriodFrom(null);
        $price2->setPeriodTo(null);
        $price2->setLocation($this->getReference('P5'));
        $manager->persist($price2);

        //P6
        $price1 = new Price();
        $price1->setActive(true);
        $price1->setPrice(new Money(6, 'LTL'));
        $price1->setPeriod(3600);
        $price1->setPeriodFrom(null);
        $price1->setPeriodTo(3600);
        $price1->setLocation($this->getReference('P6'));
        $manager->persist($price1);

        $price2 = new Price();
        $price2->setActive(true);
        $price2->setPrice(new Money(20, 'LTL'));
        $price2->setPeriod(3600 * 24);
        $price2->setPeriodFrom(null);
        $price2->setPeriodTo(null);
        $price2->setLocation($this->getReference('P6'));
        $manager->persist($price2);

        //Pc
        $price0 = new Price();
        $price0->setActive(true);
        $price0->setPrice(new Money(0, 'LTL'));
        $price0->setPeriod(60 * 15);
        $price0->setPeriodFrom(null);
        $price0->setPeriodTo(60 * 15);
        $price0->setLocation($this->getReference('Pc'));
        $manager->persist($price0);

        $price1 = new Price();
        $price1->setActive(true);
        $price1->setPrice(new Money(10, 'LTL'));
        $price1->setPeriod(3600);
        $price1->setPeriodFrom(null);
        $price1->setPeriodTo(null);
        $price1->setLocation($this->getReference('Pc'));
        $manager->persist($price1);

        $price2 = new Price();
        $price2->setActive(true);
        $price2->setPrice(new Money(60, 'LTL'));
        $price2->setPeriod(3600 * 24);
        $price2->setPeriodFrom(null);
        $price2->setPeriodTo(null);
        $price2->setLocation($this->getReference('Pc'));
        $manager->persist($price2);

        //KUN
        $price1 = new Price();
        $price1->setActive(true);
        $price1->setPrice(new Money(3, 'LTL'));
        $price1->setPeriod(3600);
        $price1->setPeriodFrom(null);
        $price1->setPeriodTo(3600 * 24);
        $price1->setLocation($this->getReference('KUN'));
        $manager->persist($price1);

        $price2 = new Price();
        $price2->setActive(true);
        $price2->setPrice(new Money(14, 'LTL'));
        $price2->setPeriod(3600 * 24);
        $price2->setPeriodFrom(null);
        $price2->setPeriodTo(null);
        $price2->setLocation($this->getReference('KUN'));
        $manager->persist($price2);

        //DOM
        $price1 = new Price();
        $price1->setActive(true);
        $price1->setActiveFrom(new \DateTime('07:00:00'));
        $price1->setActiveTo(new \DateTime('23:00:00'));
        $price1->setPrice(new Money(1, 'LTL'));
        $price1->setPeriod(60 * 20);
        $price1->setPeriodFrom(null);
        $price1->setPeriodTo(3600 * 24);
        $price1->setLocation($this->getReference('DOM'));
        $manager->persist($price1);

        $price2 = new Price();
        $price2->setActive(true);
        $price2->setActiveFrom(new \DateTime('23:00:00'));
        $price2->setActiveTo(new \DateTime('07:00:00'));
        $price2->setPrice(new Money(1, 'LTL'));
        $price2->setPeriod(3600);
        $price2->setPeriodFrom(null);
        $price2->setPeriodTo(3600 * 24);
        $price2->setLocation($this->getReference('DOM'));
        $manager->persist($price2);

        $price3 = new Price();
        $price3->setActive(true);
        $price3->setPrice(new Money(30, 'LTL'));
        $price3->setPeriod(3600 * 24);
        $price3->setPeriodFrom(null);
        $price3->setPeriodTo(null);
        $price3->setLocation($this->getReference('DOM'));
        $manager->persist($price3);

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