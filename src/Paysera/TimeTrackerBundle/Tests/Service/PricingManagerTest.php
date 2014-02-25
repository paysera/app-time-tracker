<?php
/**
 * Created by: Gediminas Samulis
 * Date: 2013-10-02
 */

namespace Paysera\TimeTrackerBundle\Tests\Service;


use Evp\Component\Money\Money;
use Paysera\TimeTrackerBundle\Entity\Location;
use Paysera\TimeTrackerBundle\Entity\Price;
use Paysera\TimeTrackerBundle\Service\PricingManager;

class PricingManagerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $priceRepository;

    /**
     * @var PricingManager
     */
    protected $service;

    public function setUp()
    {
       $this->priceRepository = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
           ->disableOriginalConstructor()->getMock()
       ;

       $this->service = new PricingManager($this->priceRepository);
    }

    /**
     * @dataProvider testGetSumByPeriodDataProvider
     */
    public function testGetSumByPeriod_results_are_as_expected(
        array $prices = null,
        \DateTime $dateFrom,
        \DateTime $dateTo,
        $sum = null
    ) {
        $location = $this->createLocation();

        $this->priceRepository
            ->expects($this->any())
            ->method('findBy')
            ->will($this->returnValue($prices))
        ;

        $result = $this->service->getSumByPeriod($location, $dateFrom, $dateTo);
        $this->assertEquals($sum, $result);
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testGetSumByPeriod_when_dateFrom_is_more_than_dateTo_exception_is_thrown()
    {
        $location = $this->createLocation();
        $this->service->getSumByPeriod($location, new \DateTime('2013-10-10 10:10'), new \DateTime('2013-10-10 09:10'));
    }

    /**
     * @dataProvider testGetPeriodBySumDataProvider
     */
    public function testGetPeriodBySum(array $prices = null, \DateTime $dateFrom, Money $sum, \DateTime $period = null)
    {
        $location = $this->createLocation();

        $this->priceRepository
            ->expects($this->any())
            ->method('findBy')
            ->will($this->returnValue($prices))
        ;

        $result = $this->service->getPeriodBySum($location, $dateFrom, $sum);
        $this->assertEquals($period, $result);
    }

    public function testGetSumByPeriodDataProvider()
    {
        $prices1 = $this->createPrices1();
        $prices2 = $this->createPrices2();
        $prices3 = $this->createPrices3();
        $prices4 = $this->createPrices4();
        $prices5 = $this->createPrices5();
        $prices6 = $this->createPrices6();

        return array(
            array($prices1, new \DateTime('2013-10-10 21:00'), new \DateTime('2013-10-11 05:00'), new Money(12, 'EUR')),
            array($prices1, new \DateTime('2013-10-10 08:00'), new \DateTime('2013-10-11 08:00'), new Money(30, 'EUR')),
            array($prices1, new \DateTime('2013-10-10 08:00'), new \DateTime('2013-10-12 08:00'), new Money(60, 'EUR')),
            array($prices1, new \DateTime('2013-10-10 08:00'), new \DateTime('2013-10-10 18:00'), new Money(30, 'EUR')),
            array($prices1, new \DateTime('2013-10-10 08:00'), new \DateTime('2013-10-10 20:00'), new Money(30, 'EUR')),
            array($prices1, new \DateTime('2013-10-10 08:00'), new \DateTime('2013-10-12 08:01'), new Money(90, 'EUR')),
            array($prices1, new \DateTime('2013-10-10 08:00'), new \DateTime('2013-10-20 08:00'), new Money(300, 'EUR')),
            array($prices1, new \DateTime('2013-10-10 06:00'), new \DateTime('2013-10-10 08:00'), new Money(4, 'EUR')),
            array($prices1, new \DateTime('2013-10-10 06:00'), new \DateTime('2013-10-10 07:15'), new Money(2, 'EUR')),
            array($prices1, new \DateTime('2013-10-10 06:59:55'), new \DateTime('2013-10-10 07:00:05'), new Money(2, 'EUR')),
            array($prices1, new \DateTime('2013-10-10 07:00'), new \DateTime('2013-10-10 07:15'), new Money(1, 'EUR')),
            array($prices1, new \DateTime('2013-10-10 07:00'), new \DateTime('2013-10-10 07:40'), new Money(2, 'EUR')),
            array($prices1, new \DateTime('2013-10-10 07:00'), new \DateTime('2013-10-10 07:40:05'), new Money(3, 'EUR')),
            array($prices1, new \DateTime('2013-10-10 06:00'), new \DateTime('2013-10-10 06:01'), new Money(1, 'EUR')),
            array($prices1, new \DateTime('2013-10-10 06:00'), new \DateTime('2013-10-10 06:50'), new Money(1, 'EUR')),
            array($prices1, new \DateTime('2013-10-10 10:00'), new \DateTime('2013-10-10 11:39'), new Money(5, 'EUR')),
            array($prices1, new \DateTime('2013-10-10 23:00'), new \DateTime('2013-10-11 00:39'), new Money(2, 'EUR')),
            array($prices1, new \DateTime('2013-10-10 10:00'), new \DateTime('2013-10-11 10:00'), new Money(30, 'EUR')),
            array($prices1, new \DateTime('2013-10-10 10:00'), new \DateTime('2013-10-11 10:01'), new Money(60, 'EUR')),

            array($prices2, new \DateTime('2013-10-10 10:00'), new \DateTime('2013-10-13 10:00'), new Money(60, 'EUR')),
            array($prices2, new \DateTime('2013-10-10 18:45'), new \DateTime('2013-10-10 20:00'), new Money(7, 'EUR')),
            array($prices2, new \DateTime('2013-10-10 18:45'), new \DateTime('2013-10-10 21:00'), new Money(7, 'EUR')),
            array($prices2, new \DateTime('2013-10-10 18:45'), new \DateTime('2013-10-10 22:00'), new Money(10, 'EUR')),
            array($prices2, new \DateTime('2013-10-10 18:45'), new \DateTime('2013-10-11 03:00'), new Money(20, 'EUR')),
            array($prices2, new \DateTime('2013-10-10 09:00'), new \DateTime('2013-10-10 12:00'), new Money(3, 'EUR')),
            array($prices2, new \DateTime('2013-10-10 05:00'), new \DateTime('2013-10-10 10:00'), new Money(11, 'EUR')),
            array($prices2, new \DateTime('2013-10-10 05:00'), new \DateTime('2013-10-10 12:00'), new Money(13, 'EUR')),
            array($prices2, new \DateTime('2013-10-10 07:50'), new \DateTime('2013-10-10 08:10'), new Money(7, 'EUR')),
            array($prices2, new \DateTime('2013-10-10 08:00'), new \DateTime('2013-10-11 08:00'), new Money(20, 'EUR')),
            array($prices2, new \DateTime('2013-10-10 08:00'), new \DateTime('2013-10-11 08:01'), new Money(40, 'EUR')),

            array($prices3, new \DateTime('2013-10-10 10:00'), new \DateTime('2013-10-10 10:10'), new Money(6, 'EUR')),
            array($prices3, new \DateTime('2013-10-10 10:00'), new \DateTime('2013-10-10 10:30'), new Money(6, 'EUR')),
            array($prices3, new \DateTime('2013-10-10 10:00'), new \DateTime('2013-10-10 11:00'), new Money(6, 'EUR')),
            array($prices3, new \DateTime('2013-10-10 10:00'), new \DateTime('2013-10-10 11:01'), new Money(12, 'EUR')),
            array($prices3, new \DateTime('2013-10-10 10:00'), new \DateTime('2013-10-10 19:00'), new Money(54, 'EUR')),
            array($prices3, new \DateTime('2013-10-10 10:00'), new \DateTime('2013-10-10 20:00'), new Money(60, 'EUR')),
            array($prices3, new \DateTime('2013-10-10 10:00'), new \DateTime('2013-10-10 21:00'), new Money(60, 'EUR')),
            array($prices3, new \DateTime('2013-10-10 10:00'), new \DateTime('2013-10-11 10:00'), new Money(60, 'EUR')),
            array($prices3, new \DateTime('2013-10-10 10:00'), new \DateTime('2013-10-11 10:01'), new Money(120, 'EUR')),
            array($prices3, new \DateTime('2013-10-10 10:00'), new \DateTime('2013-10-20 10:00'), new Money(600, 'EUR')),

            array($prices4, new \DateTime('2013-10-10 10:00'), new \DateTime('2013-10-10 10:14:59'), new Money(0, 'EUR')),
            array($prices4, new \DateTime('2013-10-10 10:00'), new \DateTime('2013-10-10 10:15'), new Money(10, 'EUR')),
            array($prices4, new \DateTime('2013-10-10 10:00'), new \DateTime('2013-10-10 10:05'), new Money(0, 'EUR')),
            array($prices4, new \DateTime('2013-10-10 10:00'), new \DateTime('2013-10-10 10:16'), new Money(10, 'EUR')),
            array($prices4, new \DateTime('2013-10-10 10:00'), new \DateTime('2013-10-10 16:00'), new Money(60, 'EUR')),

            array($prices5, new \DateTime('2013-10-10 10:00'), new \DateTime('2013-10-10 10:30'), new Money(6, 'EUR')),
            array($prices5, new \DateTime('2013-10-10 10:00'), new \DateTime('2013-10-10 10:59'), new Money(6, 'EUR')),
            array($prices5, new \DateTime('2013-10-10 10:00'), new \DateTime('2013-10-10 11:00'), new Money(30, 'EUR')),
            array($prices5, new \DateTime('2013-10-10 10:00'), new \DateTime('2013-10-10 11:01'), new Money(30, 'EUR')),
            array($prices5, new \DateTime('2013-10-10 10:00'), new \DateTime('2013-10-11 10:00'), new Money(30, 'EUR')),
            array($prices5, new \DateTime('2013-10-10 10:00'), new \DateTime('2013-10-11 10:01'), new Money(60, 'EUR')),
            array($prices5, new \DateTime('2013-05-01 10:00'), new \DateTime('2013-08-09 10:00'), new Money(3000, 'EUR')), //100 days

            array($prices6, new \DateTime('2013-10-10 19:00'), new \DateTime('2013-10-10 23:30'), new Money(1, 'EUR')),
            array($prices6, new \DateTime('2013-10-10 20:00'), new \DateTime('2013-10-11 03:00'), new Money(0, 'EUR')),
            array($prices6, new \DateTime('2013-10-10 20:00'), new \DateTime('2013-10-11 08:30'), new Money(1, 'EUR')),
            array($prices6, new \DateTime('2013-10-10 19:30'), new \DateTime('2013-10-11 08:30'), new Money(2, 'EUR')),
            array($prices6, new \DateTime('2013-10-10 19:55'), new \DateTime('2013-10-11 08:05'), new Money(2, 'EUR')),
            array($prices6, new \DateTime('2013-10-10 08:00'), new \DateTime('2013-10-10 23:00'), new Money(12, 'EUR')),

            array(array(), new \DateTime('2013-10-10 10:00'), new \DateTime('2013-10-20 10:00'), null),
            array(null, new \DateTime('2013-10-10 10:00'), new \DateTime('2013-10-20 10:00'), null),
        );
    }

    public function testGetPeriodBySumDataProvider()
    {
        $prices1 = $this->createPrices1();
        $prices2 = $this->createPrices2();
        $prices3 = $this->createPrices3();
        $prices21 = $this->createPrices2(5, 3, 2);
        $prices4 = $this->createPrices4();
        $prices5 = $this->createPrices5();
        $prices6 = $this->createPrices6();

        return array(
            array($prices3, new \DateTime('2013-10-10 10:00'), new Money(6, 'EUR'), new \DateTime('2013-10-10 11:00')),
            array($prices3, new \DateTime('2013-10-10 10:00'), new Money(12, 'EUR'), new \DateTime('2013-10-10 12:00')),
            array($prices3, new \DateTime('2013-10-10 10:00'), new Money(54, 'EUR'), new \DateTime('2013-10-10 19:00')),
            array($prices3, new \DateTime('2013-10-10 10:00'), new Money(60, 'EUR'), new \DateTime('2013-10-11 10:00')),
            array($prices3, new \DateTime('2013-10-10 10:00'), new Money(80, 'EUR'), new \DateTime('2013-10-11 10:00')),
            array($prices3, new \DateTime('2013-10-10 10:00'), new Money(119, 'EUR'), new \DateTime('2013-10-11 10:00')),
            array($prices3, new \DateTime('2013-10-10 10:00'), new Money(120, 'EUR'), new \DateTime('2013-10-12 10:00')),
            array($prices3, new \DateTime('2013-10-10 10:00'), new Money(5, 'EUR'), new \DateTime('2013-10-10 10:00')),

            array($prices1, new \DateTime('2013-10-10 07:00'), new Money(3, 'EUR'), new \DateTime('2013-10-10 08:00')),
            array($prices1, new \DateTime('2013-10-10 07:00'), new Money(5, 'EUR'), new \DateTime('2013-10-10 08:40')),
            array($prices1, new \DateTime('2013-10-10 01:00'), new Money(5, 'EUR'), new \DateTime('2013-10-10 06:00')),
            array($prices1, new \DateTime('2013-10-10 10:00'), new Money(30, 'EUR'), new \DateTime('2013-10-11 10:00')),
            array($prices1, new \DateTime('2013-10-10 10:00'), new Money(60, 'EUR'), new \DateTime('2013-10-12 10:00')),
            array($prices1, new \DateTime('2013-10-10 07:00'), new Money(1, 'EUR'), new \DateTime('2013-10-10 07:20')),
            array($prices1, new \DateTime('2013-10-10 07:00'), new Money(2, 'EUR'), new \DateTime('2013-10-10 07:40')),
            array($prices1, new \DateTime('2013-10-10 06:00'), new Money(2, 'EUR'), new \DateTime('2013-10-10 07:20')),
            array($prices1, new \DateTime('2013-10-10 05:00'), new Money(2, 'EUR'), new \DateTime('2013-10-10 07:00')),
            array($prices1, new \DateTime('2013-10-10 05:00'), new Money(5, 'EUR'), new \DateTime('2013-10-10 08:00')),
            array($prices1, new \DateTime('2013-10-10 06:59:55'), new Money(4, 'EUR'), new \DateTime('2013-10-10 08:00')),
            array($prices1, new \DateTime('2013-10-10 06:30:00'), new Money(7, 'EUR'), new \DateTime('2013-10-10 09:00')),

            array($prices2, new \DateTime('2013-10-10 05:00'), new Money(11, 'EUR'), new \DateTime('2013-10-10 10:00')),
            array($prices2, new \DateTime('2013-10-10 07:00'), new Money(7, 'EUR'), new \DateTime('2013-10-10 12:00')),
            array($prices2, new \DateTime('2013-10-10 07:00'), new Money(5, 'EUR'), new \DateTime('2013-10-10 10:00')),
            array($prices2, new \DateTime('2013-10-10 07:00'), new Money(4, 'EUR'), new \DateTime('2013-10-10 09:00')),

            array($prices21, new \DateTime('2013-10-10 07:00'), new Money(11, 'EUR'), new \DateTime('2013-10-10 12:00')),
            array($prices21, new \DateTime('2013-10-10 07:00'), new Money(8, 'EUR'), new \DateTime('2013-10-10 09:00')),

            array($prices4, new \DateTime('2013-10-10 10:00'), new Money(0, 'EUR'), new \DateTime('2013-10-10 10:15')),
            array($prices4, new \DateTime('2013-10-10 10:00'), new Money(10, 'EUR'), new \DateTime('2013-10-10 11:00')),
            array($prices4, new \DateTime('2013-10-10 10:00'), new Money(60, 'EUR'), new \DateTime('2013-10-11 10:00')),

            array($prices5, new \DateTime('2013-10-10 10:00'), new Money(6, 'EUR'), new \DateTime('2013-10-10 11:00')),
            array($prices5, new \DateTime('2013-10-10 10:00'), new Money(12, 'EUR'), new \DateTime('2013-10-10 11:00')),
            array($prices5, new \DateTime('2013-10-10 10:00'), new Money(29, 'EUR'), new \DateTime('2013-10-10 11:00')),
            array($prices5, new \DateTime('2013-10-10 10:00'), new Money(30, 'EUR'), new \DateTime('2013-10-11 10:00')),

            array($prices6, new \DateTime('2013-10-10 08:00'), new Money(2, 'EUR'), new \DateTime('2013-10-10 10:00')),
            array($prices6, new \DateTime('2013-10-10 08:00'), new Money(12, 'EUR'), new \DateTime('2013-10-11 08:00')),
            array($prices6, new \DateTime('2013-10-10 19:00'), new Money(2, 'EUR'), new \DateTime('2013-10-11 09:00')),
            array($prices6, new \DateTime('2013-10-10 21:00'), new Money(2, 'EUR'), new \DateTime('2013-10-11 10:00')),
            array($prices6, new \DateTime('2013-10-10 19:00'), new Money(1, 'EUR'), new \DateTime('2013-10-11 08:00')),
            array($prices6, new \DateTime('2013-10-10 21:00'), new Money(0, 'EUR'), new \DateTime('2013-10-11 08:00')),
            array($prices6, new \DateTime('2013-10-10 07:00'), new Money(14, 'EUR'), new \DateTime('2013-10-11 10:00')),

            array(array(), new \DateTime('2013-10-10 07:00'), new Money(666, 'EUR'), null),
            array(null, new \DateTime('2013-10-10 07:00'), new Money(666, 'EUR'), null),
        );
    }

    protected function createLocation()
    {
        $location = new Location();
        $location->setName('P2');

        return $location;
    }

    protected function createPrices1() //DOM
    {
        $location = $this->createLocation();

        $price1 = new Price();
        $price1->setActive(true);
        $price1->setActiveFrom(new \DateTime('07:00:00'));
        $price1->setActiveTo(new \DateTime('23:00:00'));
        $price1->setPrice(new Money(1, 'EUR'));
        $price1->setPeriod(60 * 20);
        $price1->setPeriodFrom(null);
        $price1->setPeriodTo(3600 * 24);
        $price1->setLocation($location);

        $price2 = new Price();
        $price2->setActive(true);
        $price2->setActiveFrom(new \DateTime('23:00:00'));
        $price2->setActiveTo(new \DateTime('07:00:00'));
        $price2->setPrice(new Money(1, 'EUR'));
        $price2->setPeriod(3600);
        $price2->setPeriodFrom(null);
        $price2->setPeriodTo(3600 * 24);
        $price2->setLocation($location);

        $price3 = new Price();
        $price3->setActive(true);
        $price3->setPrice(new Money(30, 'EUR'));
        $price3->setPeriod(3600 * 24);
        $price3->setPeriodFrom(null);
        $price3->setPeriodTo(null);
        $price3->setLocation($location);

        $prices = array(
            $price1, $price2, $price3
        );

        return $prices;
    }

    protected function createPrices2($discountHoursMargin = 2, $dayPrice = 2, $dayPriceDiscounted = 1) //sample
    {
        $location = $this->createLocation();

        $price1 = new Price();
        $price1->setActive(true);
        $price1->setActiveFrom(new \DateTime('08:00:00'));
        $price1->setActiveTo(new \DateTime('19:00:00'));
        $price1->setPrice(new Money($dayPrice, 'EUR'));
        $price1->setPeriod(3600);
        $price1->setPeriodFrom(0);
        $price1->setPeriodTo(3600 * $discountHoursMargin);
        $price1->setLocation($location);

        $price2 = new Price();
        $price2->setActive(true);
        $price2->setActiveFrom(new \DateTime('19:00:00'));
        $price2->setActiveTo(new \DateTime('08:00:00'));
        $price2->setPrice(new Money(3, 'EUR'));
        $price2->setPeriod(3600);
        $price2->setPeriodFrom(3600 * $discountHoursMargin);
        $price2->setPeriodTo(3600 * 24);
        $price2->setLocation($location);

        $price3 = new Price();
        $price3->setActive(true);
        $price3->setActiveFrom(new \DateTime('19:00:00'));
        $price3->setActiveTo(new \DateTime('08:00:00'));
        $price3->setPrice(new Money(5, 'EUR'));
        $price3->setPeriod(3600);
        $price3->setPeriodFrom(0);
        $price3->setPeriodTo(3600 * $discountHoursMargin);
        $price3->setLocation($location);

        $price4 = new Price();
        $price4->setActive(true);
        $price4->setActiveFrom(new \DateTime('08:00:00'));
        $price4->setActiveTo(new \DateTime('19:00:00'));
        $price4->setPrice(new Money($dayPriceDiscounted, 'EUR'));
        $price4->setPeriod(3600);
        $price4->setPeriodFrom(3600 * $discountHoursMargin);
        $price4->setPeriodTo(3600 * 24);
        $price4->setLocation($location);

        $price5 = new Price();
        $price5->setActive(true);
        $price5->setActiveFrom(null);
        $price5->setActiveTo(null);
        $price5->setPrice(new Money(20, 'EUR'));
        $price5->setPeriod(3600 * 24);
        $price5->setPeriodFrom(null);
        $price5->setPeriodTo(null);
        $price5->setLocation($location);

        $prices = array(
            $price1, $price2, $price3, $price4, $price5
        );

        return $prices;
    }

    protected function createPrices3() //P2
    {
        $location = $this->createLocation();

        $price1 = new Price();
        $price1->setActive(true);
        $price1->setPrice(new Money(6, 'EUR'));
        $price1->setPeriod(3600);
        $price1->setPeriodFrom(null);
        $price1->setPeriodTo(3600 * 24);
        $price1->setLocation($location);

        $price2 = new Price();
        $price2->setActive(true);
        $price2->setPrice(new Money(60, 'EUR'));
        $price2->setPeriod(3600 * 24);
        $price2->setPeriodFrom(null);
        $price2->setPeriodTo(null);
        $price2->setLocation($location);

        $prices = array(
            $price1, $price2
        );

        return $prices;
    }

    protected function createPrices4() //Pc
    {
        $location = $this->createLocation();

        $price0 = new Price();
        $price0->setActive(true);
        $price0->setPrice(new Money(0, 'EUR'));
        $price0->setPeriod(15 * 60);
        $price0->setPeriodFrom(null);
        $price0->setPeriodTo(15 * 60);
        $price0->setLocation($location);

        $price1 = new Price();
        $price1->setActive(true);
        $price1->setPrice(new Money(10, 'EUR'));
        $price1->setPeriod(3600);
        $price1->setPeriodFrom(null);
        $price1->setPeriodTo(null);
        $price1->setLocation($location);

        $price2 = new Price();
        $price2->setActive(true);
        $price2->setPrice(new Money(60, 'EUR'));
        $price2->setPeriod(3600 * 24);
        $price2->setPeriodFrom(null);
        $price2->setPeriodTo(null);
        $price2->setLocation($location);

        $prices = array(
            $price0, $price1, $price2
        );

        return $prices;
    }

    protected function createPrices5() //P1
    {
        $location = $this->createLocation();

        $price1 = new Price();
        $price1->setActive(true);
        $price1->setPrice(new Money(6, 'EUR'));
        $price1->setPeriod(3600);
        $price1->setPeriodFrom(null);
        $price1->setPeriodTo(3600);
        $price1->setLocation($location);

        $price2 = new Price();
        $price2->setActive(true);
        $price2->setPrice(new Money(30, 'EUR'));
        $price2->setPeriod(3600 * 24);
        $price2->setPeriodFrom(null);
        $price2->setPeriodTo(null);
        $price2->setLocation($location);

        return array($price1, $price2);
    }

    protected function createPrices6() //hourly rate at day, free at night, like in Vilnius municipality zones
    {
        $location = $this->createLocation();

        //day:
        $price1 = new Price();
        $price1->setActive(true);
        $price1->setPrice(new Money(1, 'EUR'));
        $price1->setPeriod(3600);
        $price1->setActiveFrom(new \DateTime('08:00:00'));
        $price1->setActiveTo(new \DateTime('20:00:00'));
        $price1->setLocation($location);

        //night:
        $price2 = new Price();
        $price2->setActive(true);
        $price2->setPrice(new Money(0, 'EUR'));
        $price2->setPeriod(3600);
        $price2->setActiveFrom(new \DateTime('20:00:00'));
        $price2->setActiveTo(new \DateTime('08:00:00'));
        $price2->setLocation($location);

        return array($price1, $price2);
    }


}
