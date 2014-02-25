<?php
/**
 * Created by: Gediminas Samulis
 * Date: 2013-10-01
 */

namespace Paysera\TimeTrackerBundle\Service;


use Doctrine\ORM\EntityManager;
use Evp\Component\Money\Money;
use Psr\Log\LoggerInterface;
use Paysera\TimeTrackerBundle\Entity\Entry;
use Paysera\TimeTrackerBundle\Entity\Location;
use Paysera\TimeTrackerBundle\Repository\EntryRepository;

class Tracker
{
    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var \DateInterval
     */
    protected $minReservePeriod;

    /**
     * @var PricingManager
     */
    protected $pricingManager;

    /**
     * @var \Paysera_WalletApi_Client_WalletClient
     */
    protected $walletClient;

    /**
     * @var string
     */
    protected $paymentDescriptionPattern;

    /**
     * @var EntryRepository
     */
    protected $entryRepository;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    public function __construct(
        EntityManager $entityManager,
        \DateInterval $minReservePeriod,
        $pricingManager,
        \Paysera_WalletApi_Client_WalletClient $walletClient,
        $paymentDescriptionPattern,
        EntryRepository $entryRepository,
        LoggerInterface $logger
    ) {
        $this->entityManager = $entityManager;
        $this->minReservePeriod = $minReservePeriod;
        $this->pricingManager = $pricingManager;
        $this->walletClient = $walletClient;
        $this->paymentDescriptionPattern = $paymentDescriptionPattern;
        $this->entryRepository = $entryRepository;
        $this->logger = $logger;
    }

    public function reserve(Location $location, $number, $walletId, Entry $entry = null, \DateTime $now = null)
    {
        if ($now === null) {
            $now = new \DateTime();
        }

        if (!$entry) {
            $dateFrom = clone $now;
        } else {
            $dateFrom = $entry->getCreatedAt();
        }
        $now->add($this->minReservePeriod);

        /** @var Money $reserveAmount */
        $reserveAmount = $this->pricingManager->getSumByPeriod(
            $location,
            $dateFrom,
            $now
        );
        if (!$reserveAmount) {
            throw new \RuntimeException(sprintf('Pricing not configured for location %s', $location->getName()));
        }

        $dateTo = $this->pricingManager->getPeriodBySum($location, $dateFrom, $reserveAmount);
        $nextReservationAt = clone $dateTo;
        $nextReservationAt->sub($this->minReservePeriod);

        if (!$entry) {
            $entry = new Entry();
            $entry->setLocation($location);
            $entry->setStatus(Entry::STATUS_ACTIVE);
            $entry->setNumber($number);
            $this->entityManager->persist($entry);
        }
        $entry->setNextReservationAt($nextReservationAt);
        $entry->setPrice($reserveAmount);
        $entry->setUpdatedAt(new \DateTime());

        if ($reserveAmount->isPositive()) {
            $transaction = new \Paysera_WalletApi_Entity_Transaction();
            $transaction->setReserveUntil(new \DateTime('+7 days'));
            $payment = new \Paysera_WalletApi_Entity_Payment();
            $payment->setPrice(new \Paysera_WalletApi_Entity_Money($reserveAmount->getAmount(), $reserveAmount->getCurrency()));

            $payment->setDescription(str_replace('%location_name%', $location->getName(), $this->paymentDescriptionPattern));
            $transaction->addPayment($payment);
            /** @var \Paysera_WalletApi_Entity_Transaction $createdTransaction */
            $createdTransaction = $this->walletClient->createTransaction($transaction);
            $this->walletClient->acceptTransactionUsingAllowance($createdTransaction->getKey(), $walletId);

            $entry->setTransactionKey($createdTransaction->getKey());
        } else {
            $entry->setTransactionKey(null);
        }

        return $entry;
    }

    public function cancel(Entry $entry)
    {
        $this->walletClient->revokeTransaction($entry->getTransactionKey());
        $entry->setUpdatedAt(new \DateTime());
        $entry->setStatus(Entry::STATUS_CANCELED);
    }

    public function confirm(Entry $entry)
    {
        $dateFrom = $entry->getCreatedAt();
        $dateTo = new \DateTime();

        /** @var Money $sum */
        $sum = $this->pricingManager->getSumByPeriod($entry->getLocation(), $dateFrom, $dateTo);

        $walletTransaction = $this->walletClient->getTransaction($entry->getTransactionKey());

        $transactionPrices = array();
        foreach ($walletTransaction->getPayments() as $payment) {
            $transactionPrices[] = \Paysera_WalletApi_Entity_TransactionPrice::create()
                ->setPaymentId($payment->getId())
                ->setPrice(new \Paysera_WalletApi_Entity_Money($sum->getAmount(), $sum->getCurrency()))
            ;
        }
        $this->walletClient->confirmTransaction($entry->getTransactionKey(), $transactionPrices);
        $entry->setStatus(Entry::STATUS_CONFIRMED);
        $entry->setPrice($sum);
        $entry->setUpdatedAt(new \DateTime());
    }

    public function renewReservations()
    {
        $date = new \DateTime();
        $entries = $this->entryRepository->findWaitingForRenewReservation($date);
        foreach ($entries as $entry) {
            //revoke current transaction:
            if ($entry->getTransactionKey()) {
                try {
                    $this->walletClient->revokeTransaction($entry->getTransactionKey());
                } catch (\Paysera_WalletApi_Exception_ResponseException $e) {
                    if ($e->getErrorCode() === 'invalid_state') {
                        $this->logger->warning(
                            'Error revoking current transaction when renewing transaction reservation',
                            array((string)$e)
                        );
                        continue;
                    } else {
                        throw $e;
                    }
                }
            }

            try {
                $wallet = $this->walletClient->getWalletBy(array('licence_plate' => $entry->getNumber()));
            } catch (\Paysera_WalletApi_Exception_ResponseException $e) {
                if ($e->getErrorCode() === 'not_found') {
                    $this->logger->error(
                        'Wallet not found by existing number when renewing reservations.',
                        array((string)$e)
                    );
                    continue;
                } else {
                    throw $e;
                }
            }

            //reserve again:
            try {
                $this->reserve($entry->getLocation(), $entry->getNumber(), $wallet->getId(), $entry, $date);
                $this->entityManager->flush();
            } catch (\Paysera_WalletApi_Exception_ResponseException $e) {
                if ($e->getErrorCode() === 'invalid_state') {
                    $this->logger->warning(
                        'Error reserving transaction when renewing transaction reservation',
                        array((string)$e)
                    );
                } else {
                    throw $e;
                }
            }
        }
    }

}