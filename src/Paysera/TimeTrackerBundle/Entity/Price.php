<?php
/**
 * Created by: Gediminas Samulis
 * Date: 2013-10-01
 */

namespace Paysera\TimeTrackerBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Evp\Component\Money\Money;

class Price
{

    const STORING_FORMAT = 'H:i:s';
    /**
     * @var integer
     */
    protected $id;

    /**
     * @var integer
     */
    protected $period;

    /**
     * @var float
     */
    protected $priceAmount;

    /**
     * @var string
     */
    protected $priceCurrency;

    /**
     * @var Money
     */
    protected $price;

    /**
     * @var integer
     */
    protected $periodFrom;

    /**
     * @var integer
     */
    protected $periodTo;

    /**
     * @var boolean
     */
    protected $active;

    /**
     * @var string
     */
    protected $activeFrom;

    /**
     * @var string
     */
    protected $activeTo;

    /**
     * @var Location
     */
    protected $location;

    /**
     * @param boolean $active
     *
     * @return $this
     */
    public function setActive($active)
    {
        $this->active = $active;

        return $this;
    }

    /**
     * @return boolean
     */
    public function getActive()
    {
        return $this->active;
    }

    /**
     * @param \DateTime $activeFrom
     *
     * @return $this
     */
    public function setActiveFrom(\DateTime $activeFrom = null)
    {
        $this->activeFrom = $activeFrom !== null ? $activeFrom->format(self::STORING_FORMAT) : null;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getActiveFrom()
    {
        return $this->activeFrom !== null ? new \DateTime($this->activeFrom) : null;
    }

    /**
     * @param \DateTime $activeTo
     *
     * @return $this
     */
    public function setActiveTo(\DateTime $activeTo = null)
    {
        $this->activeTo = $activeTo !== null ? $activeTo->format(self::STORING_FORMAT) : null;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getActiveTo()
    {
        return $this->activeTo !== null ? new \DateTime($this->activeTo) : null;
    }

    /**
     * @param \Paysera\TimeTrackerBundle\Entity\Location $location
     *
     * @return $this
     */
    public function setLocation($location)
    {
        $this->location = $location;

        return $this;
    }

    /**
     * @return \Paysera\TimeTrackerBundle\Entity\Location
     */
    public function getLocation()
    {
        return $this->location;
    }

    /**
     * @param int $period
     *
     * @return $this
     */
    public function setPeriod($period)
    {
        $this->period = $period;

        return $this;
    }

    /**
     * @return int
     */
    public function getPeriod()
    {
        return $this->period;
    }

    /**
     * @param int $periodFrom
     *
     * @return $this
     */
    public function setPeriodFrom($periodFrom)
    {
        $this->periodFrom = $periodFrom;

        return $this;
    }

    /**
     * @return int
     */
    public function getPeriodFrom()
    {
        return $this->periodFrom;
    }

    /**
     * @param int $periodTo
     *
     * @return $this
     */
    public function setPeriodTo($periodTo)
    {
        $this->periodTo = $periodTo;

        return $this;
    }

    /**
     * @return int
     */
    public function getPeriodTo()
    {
        return $this->periodTo;
    }

    /**
     * Sets price
     *
     * @param Money $price

     * @return $this
     */
    public function setPrice(Money $price)
    {
        $this->price = $price;
        $this->priceAmount = $price->getAmount();
        $this->priceCurrency = $price->getCurrency();
        return $this;
    }

    /**
     * Gets price
     *
     * @return Money
     */
    public function getPrice()
    {
        if ($this->price === null && $this->priceAmount !== null && $this->priceCurrency !== null) {
            $this->price = new Money($this->priceAmount, $this->priceCurrency);
        }
        return $this->price;
    }

}