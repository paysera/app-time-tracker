<?php

namespace Paysera\TimeTrackerBundle\Controller;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Paysera\TimeTrackerBundle\Entity\Entry;
use Paysera\TimeTrackerBundle\Entity\Location;
use Paysera\TimeTrackerBundle\Repository\EntryRepository;
use Paysera\TimeTrackerBundle\Service\Tracker;

class ApiController
{
    /**
     * @var Request
     */
    protected $request;

    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var \Paysera_WalletApi_Client_WalletClient
     */
    protected $walletClient;

    /**
     * @var EntityRepository
     */
    protected $locationRepository;

    /**
     * @var EntryRepository
     */
    protected $entryRepository;

    /**
     * @var Tracker
     */
    protected $tracker;


    public function __construct(
        EntityManager $entityManager,
        \Paysera_WalletApi_Client_WalletClient $walletClient,
        EntityRepository $locationRepository,
        EntryRepository $entryRepository,
        Tracker $tracker
    ) {
        $this->entityManager = $entityManager;
        $this->walletClient = $walletClient;
        $this->locationRepository = $locationRepository;
        $this->entryRepository = $entryRepository;
        $this->tracker = $tracker;
    }

    public function setRequest(Request $request = null)
    {
        $this->request = $request;
    }

    public function reserve()
    {
        $number = $this->request->request->get('number');
        $locationName = $this->request->request->get('location_id');
        if ($number === null || $locationName === null) {
            return $this->getErrorResponse('invalid_request', 'Parameters number and location_id must be provided');
        }

        $entry = $this->getActiveEntryByNumber($number);
        if ($entry) {
            return $this->getErrorResponse('transaction_exists', null);
        }
        /** @var Location $location */
        $location = $this->locationRepository->findOneBy(array('name' => $locationName));
        if (!$location) {
            return $this->getErrorResponse('location_not_found', 'Location not found', 404);
        }

        try {
            $wallet = $this->walletClient->getWalletBy(array('licence_plate' => $number));
        } catch (\Paysera_WalletApi_Exception_ResponseException $e) {
            if ($e->getErrorCode() === 'not_found') {
                return $this->getErrorResponse('number_not_found', 'Provided number was not found', 404);
            } else {
                throw $e;
            }
        }

        try {
            $entry = $this->tracker->reserve($location, $number, $wallet->getId());
        } catch (\Paysera_WalletApi_Exception_ResponseException $e) {
            if (in_array(
                    $e->getErrorCode(),
                    array(
                        'no_active_allowance',
                        'allowance_exceeded',
                        'not_enough_funds',
                        'limits_exceeded',
                        'limit_violation'
                    )
                )
            ) {
                return $this->getErrorResponse('no_funds');
            } else {
                throw $e;
            }
        }

        $this->entityManager->flush();
        return new JsonResponse($this->encodeTransactionResponse($entry));
    }

    public function cancel($number)
    {
        $entry = $this->getActiveEntryByNumber($number);
        if (!$entry) {
            return $this->getErrorResponse('transaction_not_found', null, 404);
        }

        try {
            $this->tracker->cancel($entry);
        } catch (\Paysera_WalletApi_Exception_ResponseException $e) {
            if ($e->getErrorCode() === 'invalid_state') {
                return $this->getErrorResponse('error_canceling_transaction');
            } else {
                throw $e;
            }
        }

        $this->entityManager->flush();
        return new Response();
    }

    public function confirm($number)
    {
        $entry = $this->getActiveEntryByNumber($number);
        if (!$entry) {
            return $this->getErrorResponse('transaction_not_found', null, 404);
        }

        try {
            $this->tracker->confirm($entry);
        } catch (\Paysera_WalletApi_Exception_ResponseException $e) {
            if ($e->getErrorCode() === 'invalid_state') {
                return $this->getErrorResponse('error_confirming_transaction');
            } else {
                throw $e;
            }
        }

        $this->entityManager->flush();
        return new JsonResponse($this->encodeTransactionResponse($entry));
    }

    public function getEntry($number)
    {
        $entry = $this->entryRepository->findOneNewest($number);
        if (!$entry) {
            return $this->getErrorResponse('transaction_not_found', null, 404);
        }
        return new JsonResponse($this->encodeTransactionResponse($entry));
    }

    public function getEntries()
    {
        $entries = $this->entryRepository->findBy(array('status' => Entry::STATUS_ACTIVE), array('id' => 'DESC'));

        $entriesEncoded = array();
        foreach ($entries as $entry) {
            $entriesEncoded[] = $this->encodeTransactionResponse($entry);
        }

        return new JsonResponse($entriesEncoded);
    }

    protected function getActiveEntryByNumber($number)
    {
        $entry = $this->entryRepository->findOneBy(array('number' => $number, 'status' => Entry::STATUS_ACTIVE));
        return $entry;
    }

    protected function encodeTransactionResponse(Entry $entry)
    {
        return array_filter(
            array(
                'number' => $entry->getNumber(),
                'location_id' => $entry->getLocation()->getName(),
                'transaction_key' => $entry->getTransactionKey(),
                'price' => $entry->getPrice()->getAmountInCents(),
                'currency' => $entry->getPrice()->getCurrency(),
                'status' => $entry->getStatus(),
        )
        );
    }

    protected function getErrorResponse($error, $errorDescription = null, $status = 400)
    {
        $errorStructure = array(
            'error' => $error
        );
        if ($errorDescription !== null) {
            $errorStructure['error_description'] = $errorDescription;
        }

        return new JsonResponse($errorStructure, $status);
    }

}
