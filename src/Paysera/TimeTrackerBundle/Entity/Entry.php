<?php
/**
 * Created by: Gediminas Samulis
 * Date: 2013-10-01
 */

namespace Paysera\TimeTrackerBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Evp\Component\Money\Money;

class Entry
{
    const STATUS_ACTIVE = 'active';
    const STATUS_CONFIRMED = 'confirmed';
    const STATUS_CANCELED = 'canceled';

    /**
     * @var string
     */
    protected $number;

    /**
     * @var string
     */
    protected $transactionKey;

    /**
     * @var Money
     */
    protected $price;

    /**
     * @var integer
     */
    protected $priceAmount;

    /**
     * @var string
     */
    protected $priceCurrency;

    /**
     * @var string
     */
    protected $status;

    /**
     * @var integer
     */
    protected $id;

    /**
     * @var Location
     */
    protected $location;

    /**
     * @var \DateTime
     */
    protected $createdAt;

    /**
     * @var \DateTime
     */
    protected $updatedAt;

    /**
     * @var \DateTime
     */
    protected $nextReservationAt;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
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
     * @param string $number
     *
     * @return $this
     */
    public function setNumber($number)
    {
        $this->number = $number;

        return $this;
    }

    /**
     * @return string
     */
    public function getNumber()
    {
        return $this->number;
    }

    /**
     * @param string $status
     *
     * @return $this
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param string $transactionKey
     *
     * @return $this
     */
    public function setTransactionKey($transactionKey)
    {
        $this->transactionKey = $transactionKey;

        return $this;
    }

    /**
     * @return string
     */
    public function getTransactionKey()
    {
        return $this->transactionKey;
    }

    /**
     * @param \DateTime $createdAt
     *
     * @return $this
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @param \DateTime $nextReservationAt
     *
     * @return $this
     */
    public function setNextReservationAt($nextReservationAt)
    {
        $this->nextReservationAt = $nextReservationAt;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getNextReservationAt()
    {
        return $this->nextReservationAt;
    }

    /**
     * @param \DateTime $updatedAt
     *
     * @return $this
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
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