<?php
/**
 * Created by: Gediminas Samulis
 * Date: 2013-10-01
 */

namespace Paysera\TimeTrackerBundle\Service;


use Doctrine\ORM\EntityRepository;
use Evp\Component\Money\Money;
use Paysera\TimeTrackerBundle\Entity\Location;
use Paysera\TimeTrackerBundle\Entity\Price;

class PricingManager
{
    protected $paramsHashes = array();

    /**
     * @var EntityRepository
     */
    protected $priceRepository;

    public function __construct(EntityRepository $priceRepository) {
        $this->priceRepository = $priceRepository;
    }

    public function getSumByPeriod(Location $location, \DateTime $dateFrom, \DateTime $dateTo)
    {
        if ($dateFrom > $dateTo) {
            throw new \RuntimeException('dateFrom cannot be more than dateTo');
        }

        /** @var Price[] $price */
        $prices = $this->priceRepository->findBy(array('location' => $location, 'active' => true));
        if ($prices) {
            $sum = $this->calculateSumByPricesAndDates($prices, $dateFrom, $dateFrom, $dateTo);
            return $sum;
        }

        return null;
    }

    public function getPeriodBySum(Location $location, \DateTime $dateFrom, Money $sum)
    {
        /** @var Price[] $prices */
        $prices = $this->priceRepository->findBy(array('location' => $location, 'active' => true));

        if ($prices) {
            $dateTo = $this->calculatePeriodByPricesAndSum($prices, $dateFrom, $sum);
            return $dateTo;
        }

        return null;
    }

    /**
     * @param Price[] $prices
     * @param \DateTime $dateFromInitial
     * @param \DateTime $dateFrom
     * @param \DateTime $dateTo
     * @return null
     */
    protected function calculateSumByPricesAndDates(
        array $prices,
        \DateTime $dateFromInitial,
        \DateTime $dateFrom,
        \DateTime $dateTo
    ) {
        $minPrice = null;
        foreach ($prices as $price) {
            if ($this->isPriceActiveByTime($price ,$dateFrom)) {
                $periodTotal = $dateTo->getTimeStamp() - $dateFromInitial->getTimestamp();

                if ($this->isPriceActiveByPeriod($price, $periodTotal)) {
                    $dateToRestricted = $this->restrictDateToByPrice($price, $dateFrom, $dateTo);
                    $period = $dateToRestricted->getTimeStamp() - $dateFrom->getTimestamp();

                    $sum = $price->getPrice()->mul(ceil($period / $price->getPeriod()));
                    if ($dateToRestricted < $dateTo) {
                        $childrenSum = $this->calculateSumByPricesAndDates(
                            $prices,
                            $dateFromInitial,
                            $dateToRestricted,
                            $dateTo
                        );
                        $sum = $sum->add($childrenSum);
                    }
                    if ($minPrice === null || $sum->isLt($minPrice)) {
                        $minPrice = $sum;
                    }
                }
            }
        }
        return $minPrice;
    }

    /**
     * @param Price[] $prices
     * @param \DateTime $dateFrom
     * @param Money $sum
     * @return \DateTime
     */
    protected function calculatePeriodByPricesAndSum(
        array $prices,
        \DateTime $dateFrom,
        Money $sum
    ) {
        $maxDateTo = null;
        $invalidPricesUsed = true;

        while ($invalidPricesUsed) {
            $maxDateTo = $this->calculatePeriodByPricesAndSumPreliminary($prices, $dateFrom, $dateFrom, $sum);

            //validate used prices, repeat process without invalid prices if found:
            $invalidPricesUsed = false;
            $period = $maxDateTo->getTimestamp() - $dateFrom->getTimestamp();

            $validPrices = array();
            /** @var Price $price */
            foreach ($prices as $price) {
                if (
                    (!$price->getPeriodFrom() || $period >= $price->getPeriodFrom())
                    && (!$price->getPeriodTo() || $period <= $price->getPeriodTo())
                ) {
                    $validPrices[]  = $price;
                } else {
                    $invalidPricesUsed = true;
                }
            }
            $prices = $validPrices;
        }
        return $maxDateTo;
    }

    /**
     * @param Price[] $prices
     * @param \DateTime $dateFromInitial
     * @param \DateTime $dateFrom
     * @param Money $sum
     * @return \DateTime|mixed
     */
    protected function calculatePeriodByPricesAndSumPreliminary(
        array $prices,
        \DateTime $dateFromInitial,
        \DateTime $dateFrom,
        Money $sum
    ) {
        $maxDateTo = $dateFrom;
        foreach ($prices as $price) {
            if ($this->isPriceActiveByTime($price ,$dateFrom)) {
                $dateToRestricted = $this->restrictDateToByPrice($price, $dateFrom);
                if ($price->getPrice()->isPositive()) {
                    $period = floor($sum->getAmount() / $price->getPrice()->getAmount()) * $price->getPeriod();
                } else {
                    $period = $price->getPeriod();
                }
                $dateToCalculated = clone $dateFrom;
                $dateToCalculated->add(new \DateInterval('PT' . $period . 'S'));

                $dateToRestrictedByPeriodTo = null;
                if ($price->getPeriodTo()) {
                    $dateToRestrictedByPeriodTo = clone $dateFromInitial;
                    $dateToRestrictedByPeriodTo->add(new \DateInterval('PT' . $price->getPeriodTo() . 'S'));
                }

                $dateTo = min(array_filter(array($dateToRestricted, $dateToCalculated, $dateToRestrictedByPeriodTo)));

                $amount = $price->getPrice()->mul(
                    ceil(($dateTo->getTimestamp() - $dateFrom->getTimestamp()) / $price->getPeriod())
                );
                $remainingSum = $sum->sub($amount);

                if ($dateTo > $dateFrom) {
                    $dateTo = $this->calculatePeriodByPricesAndSumPreliminary(
                        $prices,
                        $dateFromInitial,
                        $dateTo,
                        $remainingSum
                    );
                }

                $maxDateTo = max($maxDateTo, $dateTo);
            }
        }

        return $maxDateTo;
    }

    protected function isPriceActiveByTime(Price $price, \DateTime $date)
    {
        if ($price->getActiveFrom() === null || $price->getActiveTo() === null) {
            return true;
        }
        $dateTime = $this->getTimeFromDate($date);
        $priceActiveFromTime = $this->getTimeFromDate($price->getActiveFrom());
        $priceActiveToTime = $this->getTimeFromDate($price->getActiveTo());

        if ($priceActiveFromTime < $priceActiveToTime) {
            return $dateTime >= $priceActiveFromTime && $dateTime < $priceActiveToTime;
        } else {
            return $dateTime >= $priceActiveFromTime || $dateTime < $priceActiveToTime;
        }
    }

    protected function isPriceActiveByPeriod(Price $price, $period)
    {
        if (!$price->getPeriodFrom() && !$price->getPeriodTo()) {
            return true;
        }

        return $period >= $price->getPeriodFrom() && (!$price->getPeriodTo() || $period < $price->getPeriodTo());
    }

    protected function restrictDateToByPrice(Price $price, \DateTime $dateFrom, \DateTime $dateTo = null)
    {
        if ($price->getActiveTo() === null) {
            return $dateTo;
        }

        $priceActiveTo = new \DateTime($dateFrom->format('Y-m-d') . ' ' .$price->getActiveTo()->format('H:i:s'));
        if (
            $price->getActiveTo() <= $price->getActiveFrom()
            && $price->getActiveFrom() <= $this->getTimeFromDate($dateFrom)
        ) {
            $priceActiveTo->add(new \DateInterval('P1D'));
        }

        if ($dateTo === null) {
           return $priceActiveTo;
        }
        return min($dateTo, $priceActiveTo);
    }

    protected function getTimeFromDate(\DateTime $date)
    {
        return new \DateTime($date->format('H:i:s'));
    }


}