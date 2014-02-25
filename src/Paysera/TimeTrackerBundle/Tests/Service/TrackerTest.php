<?php
/**
 * Created by: Gediminas Samulis
 * Date: 2013-10-08
 */

namespace Paysera\TimeTrackerBundle\Tests\Service;


use Doctrine\ORM\EntityManager;
use Doctrine\ORM\TransactionRequiredException;
use Evp\Component\Money\Money;
use Psr\Log\LoggerInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Paysera\TimeTrackerBundle\Entity\Entry;
use Paysera\TimeTrackerBundle\Entity\Location;
use Paysera\TimeTrackerBundle\Repository\EntryRepository;
use Paysera\TimeTrackerBundle\Service\PricingManager;
use Paysera\TimeTrackerBundle\Service\Tracker;

class TrackerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Tracker
     */
    protected $service;

    /**
     * @var EntityManager
     */
    protected $entityMamager;

    /**
     * @var PricingManager
     */
    protected $pricingManager;

    /**
     * @var \Paysera_WalletApi_Client_WalletClient
     */
    protected $walletClient;

    /**
     * @var EntryRepository
     */
    protected $entryRepository;

    /**
     * @var \DateInterval
     */
    public $minReservePeriod;

    /**
     * @var LoggerInterface
     */
    protected $logger;


    /**
     * @var \DateTime
     */
    public $tempDateNow;



    public function setUp()
    {
        $this->entityMamager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()->getMock();

        $this->pricingManager = $this->getMockBuilder('Paysera\TimeTrackerBundle\Service\PricingManager')
            ->disableOriginalConstructor()->getMock();

        $this->walletClient = $this->getMockBuilder('Paysera_WalletApi_Client_WalletClient')
            ->disableOriginalConstructor()->getMock();

        $this->entryRepository = $this->getMockBuilder('Paysera\TimeTrackerBundle\Repository\EntryRepository')
            ->disableOriginalConstructor()->getMock();

        $this->logger = $this->getMockBuilder('Psr\Log\LoggerInterface')->disableOriginalConstructor()->getMock();

        $this->minReservePeriod = new \DateInterval('PT7201S');

        $this->service = new Tracker(
            $this->entityMamager,
            $this->minReservePeriod,
            $this->pricingManager,
            $this->walletClient,
            'For parking in %location_name%',
            $this->entryRepository,
            $this->logger
        );
    }

    /**
     * @expectedException \RunTimeException
     */
    public function testReserve_exception_thrown_when_no_reserve_amount_returned()
    {
        $location = new Location();
        $this->service->reserve($location, 'ABC123', 1);
    }

    /**
     * Tests that entry is returned with expected properties.
     * Also tests that getSumByPeriod is called with period equal to $this->minReservePeriod
     * and entry->nextReservationAt is greater than now
     * when Entry is not provided
     */
    public function testReserve_return_entry_with_provided_location_and_number_also_check_times_when_no_Entry_provided()
    {
        $self = $this;
        $callbackArg2 = function($arg) use($self) {
            $self->tempDateNow = $arg;
            return true;
        };
        $callbackArg3 = function($arg) use($self) {
            $date = clone $self->tempDateNow;
            return $arg == $date->add($self->minReservePeriod);
        };

        $location = new Location();
        $this->pricingManager
            ->expects($this->any())
            ->method('getSumByPeriod')
            ->with($this->anything(), $this->callback($callbackArg2), $this->callback($callbackArg3))
            ->will($this->returnValue(new Money('10', 'EUR')))
        ;

        $transactionKey = 'abcdefgh';
        $transaction = $this->getMock('Paysera_WalletApi_Entity_Transaction');
        $transaction
            ->expects($this->any())
            ->method('getKey')
            ->will($this->returnValue($transactionKey))
        ;
        $this->walletClient
            ->expects($this->any())
            ->method('createTransaction')
            ->will($this->returnValue($transaction))
        ;

        $this->pricingManager
            ->expects($this->any())
            ->method('getPeriodBySum')
            ->will($this->returnValue(new \DateTime('+7500 seconds')))
        ;

        $number = 'ABC123';
        $entry = $this->service->reserve($location, $number, 1);
        $this->assertEquals($location, $entry->getLocation());
        $this->assertEquals($number, $entry->getNumber());
        $this->assertGreaterThan($this->tempDateNow, $entry->getNextReservationAt());
    }

    /**
     * Tests that getSumByPeriod is called with period greater than $this->minReservePeriod
     * and entry->nextReservationAt is greater than now
     * when Entry is provided
     */
    public function testReserve_check_times_when_entry_provided()
    {
        $entry = new Entry();
        $entry->setCreatedAt(new \DateTime('-10 hours'));

        $self = $this;
        $callbackArg2 = function($arg) use($self, $entry) {
            return $arg == $entry->getCreatedAt();
        };
        $callbackArg3 = function($arg) use($self, $entry) {
            return $arg->getTimeStamp() - $entry->getCreatedAt()->getTimestamp() > $self->minReservePeriod->s;
        };

        $location = new Location();
        $this->pricingManager
            ->expects($this->any())
            ->method('getSumByPeriod')
            ->with($this->anything(), $this->callback($callbackArg2), $this->callback($callbackArg3))
            ->will($this->returnValue(new Money('10', 'EUR')))
        ;

        $transactionKey = 'abcdefgh';
        $transaction = $this->getMock('Paysera_WalletApi_Entity_Transaction');
        $transaction
            ->expects($this->any())
            ->method('getKey')
            ->will($this->returnValue($transactionKey))
        ;
        $this->walletClient
            ->expects($this->any())
            ->method('createTransaction')
            ->will($this->returnValue($transaction))
        ;

        $this->pricingManager
            ->expects($this->any())
            ->method('getPeriodBySum')
            ->will($this->returnValue(new \DateTime('+7200 seconds')))
        ;

        $entry = $this->service->reserve($location, 'ABC123', 1, $entry);
        $this->assertGreaterThan($this->tempDateNow, $entry->getNextReservationAt());
    }

    /**
     * Tests that transaction is not created and not attached to entry if amount for reservation period is 0
     */
    public function testReserve_do_not_create_transaction_when_reserve_amount_appears_to_be_0()
    {
        $this->pricingManager
            ->expects($this->any())
            ->method('getSumByPeriod')
            ->will($this->returnValue(new Money(0, 'EUR')))
        ;

        $this->walletClient
            ->expects($this->never())
            ->method('createTransaction')
        ;

        $this->pricingManager
            ->expects($this->any())
            ->method('getPeriodBySum')
            ->will($this->returnValue(new \DateTime('+7200 seconds')))
        ;

        $location = new Location();

        //when no entry exists
        $entry = $this->service->reserve($location, 'ABC123', 1);
        $this->assertNull($entry->getTransactionKey());

        //when existing entry provided
        $existingEntry = new Entry();
        $existingEntry->setTransactionKey('abcdefg');
        $entry = $this->service->reserve($location, 'ABC123', 1, $existingEntry);
        $this->assertNull($entry->getTransactionKey());
    }

    public function testCancel()
    {
        $entry = new Entry();
        $entry->setStatus(Entry::STATUS_ACTIVE);
        $transactionKey = 'abcdefgh';
        $entry->setTransactionKey($transactionKey);

        $dateBefore = new \DateTime('-1 hours');
        $entry->setUpdatedAt($dateBefore);

        $this->walletClient
            ->expects($this->once())
            ->method('revokeTransaction')
            ->with($this->equalTo($transactionKey))
            ->will($this->returnValue($this->anything()))
        ;

        $this->service->cancel($entry);

        $this->assertGreaterThan($dateBefore, $entry->getUpdatedAt());
        $this->assertEquals(Entry::STATUS_CANCELED, $entry->getStatus());
    }

    public function testConfirm()
    {
        $entry = new Entry();
        $entry->setStatus(Entry::STATUS_ACTIVE);
        $entry->setTransactionKey('abcdefg');
        $lastEntryUpdateAt = new \DateTime('-12 hours');
        $entry->setUpdatedAt($lastEntryUpdateAt);
        $entry->setCreatedAt($lastEntryUpdateAt);
        $entry->setLocation(new Location());

        $sum = new Money(66, 'EUR');

        $walletTransaction = new \Paysera_WalletApi_Entity_Transaction();
        $payment = new \Paysera_WalletApi_Entity_Payment;
        $payment->setDescription('Parking in P1');
        $payment->setPrice(new \Paysera_WalletApi_Entity_Money(66, 'EUR'));
        $walletTransaction->addPayment($payment);

        $transactionPrice = new \Paysera_WalletApi_Entity_TransactionPrice();
        $transactionPrice->setPrice(new \Paysera_WalletApi_Entity_Money($sum->getAmount(), $sum->getCurrency()));
        $transactionPrice->setPaymentId($payment->getId());

        $this->pricingManager
            ->expects($this->once())
            ->method('getSumByPeriod')
            ->will($this->returnValue($sum))
        ;

        $this->walletClient
            ->expects($this->once())
            ->method('getTransaction')
            ->will($this->returnValue($walletTransaction))
        ;

        $this->walletClient
            ->expects($this->once())
            ->method('confirmTransaction')
            ->with($this->equalTo($entry->getTransactionKey()), $this->equalTo(array($transactionPrice)))
        ;

        $this->service->confirm($entry);

        $this->assertEquals(Entry::STATUS_CONFIRMED, $entry->getStatus());
        $this->assertEquals($sum, $entry->getPrice());
        $this->assertGreaterThan($lastEntryUpdateAt, $entry->getUpdatedAt());
    }


    public function testRenewReservations_revokeTransaction_getWalletBy_and_reserve_are_called()
    {
        $entry = new Entry();
        $entry->setStatus(Entry::STATUS_ACTIVE);
        $entry->setTransactionKey('abcdefg');
        $lastEntryUpdateAt = new \DateTime('-12 hours');
        $entry->setUpdatedAt($lastEntryUpdateAt);
        $entry->setCreatedAt($lastEntryUpdateAt);
        $entry->setLocation(new Location());
        $entry->setNumber('ABC123');

        $wallet = new \Paysera_WalletApi_Entity_Wallet();


        $this->entryRepository
            ->expects($this->once())
            ->method('findWaitingForRenewReservation')
            ->will($this->returnValue(array($entry)))
        ;

        $this->walletClient
            ->expects($this->any())
            ->method('revokeTransaction')
            ->with($entry->getTransactionKey())
        ;

        $this->walletClient
            ->expects($this->any())
            ->method('getWalletBy')
            ->with($this->equalTo(array('licence_plate' => $entry->getNumber())))
            ->will($this->returnValue($wallet))
        ;

        //assert by caught expection that reserve was actually called:
        try {
            $this->service->renewReservations();
        } catch (\Exception $e) {
            $this->assertStringStartsWith('Pricing not configured for location', $e->getMessage());
        }
    }

    public function testRenewReservations_do_not_revoke_transaction_if_entry_has_no_associated_transaction()
    {
        $entry = new Entry();
        $entry->setStatus(Entry::STATUS_ACTIVE);
        $lastEntryUpdateAt = new \DateTime('-12 hours');
        $entry->setUpdatedAt($lastEntryUpdateAt);
        $entry->setCreatedAt($lastEntryUpdateAt);
        $entry->setLocation(new Location());
        $entry->setNumber('ABC123');

        $wallet = new \Paysera_WalletApi_Entity_Wallet();


        $this->entryRepository
            ->expects($this->once())
            ->method('findWaitingForRenewReservation')
            ->will($this->returnValue(array($entry)))
        ;

        $this->walletClient
            ->expects($this->never())
            ->method('revokeTransaction')
        ;

        $this->walletClient
            ->expects($this->any())
            ->method('getWalletBy')
            ->with($this->equalTo(array('licence_plate' => $entry->getNumber())))
            ->will($this->returnValue($wallet))
        ;

        try {
            $this->service->renewReservations(); //dont care
        } catch (\Exception $e) {
        }
    }


}
