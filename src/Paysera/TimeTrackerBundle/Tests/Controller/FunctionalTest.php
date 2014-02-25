<?php
/**
 * Created by: Gediminas Samulis
 * Date: 2013-10-08
 */

namespace Paysera\TimeTrackerBundle\Tests\Controller;


use Evp\Component\Money\Money;
use Evp\Tests\WebTestCase;

class FunctionalTest extends WebTestCase
{
    /**
     * @var \Symfony\Bundle\FrameworkBundle\Client
     */
    protected $client;

    /**
     * Sets up the test variables
     */
    public function setUp()
    {
        $this->client = $this->createClientWithNewDatabase();
    }

    protected function createClientWithNewDatabase($dataFixturesDir = null)
    {
        if ($dataFixturesDir === null) {
            $dataFixturesDir = __DIR__ . '/../DataFixtures';
        }
        return parent::createClientWithNewDatabase($dataFixturesDir);
    }

    public function testReserve_returns_transaction_when_valid_request_provided()
    {
        $wallet = $this->getMock('Paysera_WalletApi_Entity_Wallet');
        $wallet
            ->expects($this->any())
            ->method('getId')
            ->will($this->returnValue(1))
        ;

        $walletClient = $this->getMockBuilder('Paysera_WalletApi_Client_WalletClient')->disableOriginalConstructor()->getMock();
        $walletClient
            ->expects($this->any())
            ->method('getWalletBy')
            ->will($this->returnValue($wallet))
        ;
        $transactionKey = 'abcdefgh';
        $transaction = $this->getMock('Paysera_WalletApi_Entity_Transaction');
        $transaction
            ->expects($this->any())
            ->method('getKey')
            ->will($this->returnValue($transactionKey))
        ;
        $walletClient
            ->expects($this->any())
            ->method('createTransaction')
            ->will($this->returnValue($transaction))
        ;

        $this->client->getContainer()->set('evp.paysera_wallet_client', $walletClient);

        //reserve:
        $params = array(
            'number' => 'ABC123',
            'location_id' => 'P1',
        );
        $this->client->request(
            'POST',
            '/rest/v1/transactions',
            $params
        );

        $result = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('ABC123', $result['number']);
        $this->assertTrue(
            $this->client->getResponse()->headers->contains(
                'Content-Type',
                'application/json'
            )
        );
    }

    public function testReserve_invalid_request_returned_when_parameter_not_provided()
    {
        //reserve:
        $params = array(
            'location_id' => 'P1',
        );
        $this->client->request(
            'POST',
            '/rest/v1/transactions',
            $params
        );

        $result = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('invalid_request', $result['error']);
        $this->assertEquals($this->client->getResponse()->getStatusCode(), 400);

        $this->assertTrue(
            $this->client->getResponse()->headers->contains(
                'Content-Type',
                'application/json'
            )
        );
    }

    public function testReserve_transaction_exists_returned_when_active_number_provided()
    {
        //reserve:
        $params = array(
            'number' => 'ABC666',
            'location_id' => 'P1',
        );
        $this->client->request(
            'POST',
            '/rest/v1/transactions',
            $params
        );

        $result = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('transaction_exists', $result['error']);
        $this->assertEquals($this->client->getResponse()->getStatusCode(), 400);
    }

    public function testReserve_400_no_funds_returned_if_no_funds()
    {
        $tracker = $this->getMockBuilder('Paysera\TimeTrackerBundle\Service\Tracker')->disableOriginalConstructor()->getMock();
        $tracker
            ->expects($this->any())
            ->method('reserve')
            ->will($this->throwException(new \Paysera_WalletApi_Exception_ResponseException(array('error' => 'not_enough_funds'), 0, '')))
        ;
        $this->client->getContainer()->set('paysera_time_tracker.tracker', $tracker);

        $wallet = $this->getMock('Paysera_WalletApi_Entity_Wallet');
        $wallet
            ->expects($this->any())
            ->method('getId')
            ->will($this->returnValue(1))
        ;
        $walletClient = $this->getMockBuilder('Paysera_WalletApi_Client_WalletClient')->disableOriginalConstructor()->getMock();
        $walletClient
            ->expects($this->any())
            ->method('getWalletBy')
            ->will($this->returnValue($wallet))
        ;
        $this->client->getContainer()->set('evp.paysera_wallet_client', $walletClient);

        //reserve:
        $params = array(
            'number' => 'ABC123',
            'location_id' => 'P1',
        );
        $this->client->request(
            'POST',
            '/rest/v1/transactions',
            $params
        );

        $result = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('no_funds', $result['error']);
        $this->assertEquals($this->client->getResponse()->getStatusCode(), 400);
    }


    public function testCancel_return_200_if_entry_canceled()
    {
        $walletClient = $this->getMockBuilder('Paysera_WalletApi_Client_WalletClient')->disableOriginalConstructor()->getMock();
        $this->client->getContainer()->set('evp.paysera_wallet_client', $walletClient);

        $number = 'ABC666';
        $this->client->request('DELETE', '/rest/v1/transactions/' . $number);

        $this->assertEquals($this->client->getResponse()->getStatusCode(), 200);
    }

    public function testCancel_404_returned_if_entry_not_found()
    {
        $number = 'NONUMBER';
        $this->client->request('DELETE', '/rest/v1/transactions/' . $number);

        $result = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('transaction_not_found', $result['error']);
        $this->assertEquals($this->client->getResponse()->getStatusCode(), 404);
    }

    public function testCancel_400_returned_if_entry_found_but_failed_to_cancel()
    {
        $walletClient = $this->getMockBuilder('Paysera_WalletApi_Client_WalletClient')->disableOriginalConstructor()->getMock();
        $walletClient
            ->expects($this->any())
            ->method('revokeTransaction')
            ->will($this->throwException(new \Paysera_WalletApi_Exception_ResponseException(array('error' => 'invalid_state'), 0, '')))
        ;
        $this->client->getContainer()->set('evp.paysera_wallet_client', $walletClient);

        $number = 'ABC666';
        $this->client->request('DELETE', '/rest/v1/transactions/' . $number);

        $result = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('error_canceling_transaction', $result['error']);
        $this->assertEquals($this->client->getResponse()->getStatusCode(), 400);
    }

    public function testConfirm_return_200_if_entry_confirmed()
    {
        $walletTransaction = new \Paysera_WalletApi_Entity_Transaction();
        $payment = new \Paysera_WalletApi_Entity_Payment();
        $payment->setDescription('Parking in P1');
        $payment->setPrice(new \Paysera_WalletApi_Entity_Money(66, 'EUR'));
        $walletTransaction->addPayment($payment);

        $walletClient = $this->getMockBuilder('Paysera_WalletApi_Client_WalletClient')->disableOriginalConstructor()->getMock();
        $walletClient
            ->expects($this->once())
            ->method('getTransaction')
            ->will($this->returnValue($walletTransaction))
        ;
        $this->client->getContainer()->set('evp.paysera_wallet_client', $walletClient);

        $sum = new Money(66, 'EUR');
        $pricingManager = $this->getMockBuilder('Paysera\TimeTrackerBundle\Service\PricingManager')
            ->disableOriginalConstructor()->getMock();
        $pricingManager
            ->expects($this->once())
            ->method('getSumByPeriod')
            ->will($this->returnValue($sum))
        ;
        $this->client->getContainer()->set('paysera_time_tracker.pricing_manager', $pricingManager);

        $number = 'ABC666';
        $this->client->request('PUT','/rest/v1/transactions/' . $number);

        $this->assertEquals($this->client->getResponse()->getStatusCode(), 200);
    }

    public function testConfirm_404_returned_if_entry_not_found()
    {
        $number = 'NONUMBER';
        $this->client->request('PUT', '/rest/v1/transactions/' . $number);

        $result = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('transaction_not_found', $result['error']);
        $this->assertEquals($this->client->getResponse()->getStatusCode(), 404);
    }

    public function testConfirm_400_returned_if_entry_found_but_failed_to_confirm()
    {
        $pricingManager = $this->getMockBuilder('Paysera\TimeTrackerBundle\Service\PricingManager')
            ->disableOriginalConstructor()->getMock();
        $pricingManager
            ->expects($this->any())
            ->method('getSumByPeriod')
            ->will($this->throwException(new \Paysera_WalletApi_Exception_ResponseException(array('error' => 'invalid_state'), 0, '')))
        ;
        $this->client->getContainer()->set('paysera_time_tracker.pricing_manager', $pricingManager);

        $number = 'ABC666';
        $this->client->request('PUT', '/rest/v1/transactions/' . $number);

        $result = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('error_confirming_transaction', $result['error']);
        $this->assertEquals($this->client->getResponse()->getStatusCode(), 400);
    }

    public function testGetEntry_entry_returned_if_found()
    {
        $number = 'ABC666';
        $this->client->request(
            'GET',
            '/rest/v1/transactions/' . $number
        );

        $result = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals($number, $result['number']);
    }

    public function testGetEntry_404_returned_if_entry_not_found()
    {
        $number = 'NONUMBER';
        $this->client->request('GET', '/rest/v1/transactions/' . $number);

        $result = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('transaction_not_found', $result['error']);
        $this->assertEquals($this->client->getResponse()->getStatusCode(), 404);
    }

    public function testGetEntries_entries_found()
    {
        $this->client->request('GET', '/rest/v1/transactions');

        $result = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertNotEmpty($result);
        $this->assertArrayHasKey('number', $result[0]);
        $this->assertNotEmpty($result[0]['number']);
        $this->assertNotEmpty($result[0]['transaction_key']);
    }

    public function testGetEntries_entries_not_found()
    {
        $entryRepository = $this->getMockBuilder('Paysera\TimeTrackerBundle\Repository\EntryRepository')
            ->disableOriginalConstructor()->getMock();

        $entryRepository
            ->expects($this->any())
            ->method('findBy')
            ->will($this->returnValue(array()))
        ;

        $this->client->getContainer()->set('paysera_time_tracker.repository.entry', $entryRepository);
        $this->client->request('GET', '/rest/v1/transactions');

        $result = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertCount(0, $result);
    }

}