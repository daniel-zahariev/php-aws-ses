<?php

use PHPUnit\Framework\TestCase;

class SimpleEmailServiceTest extends TestCase
{
	// private $prophet;

	// protected function setup()
 //    {
 //        $this->prophet = new \Prophecy\Prophet;
 //    }

 //    protected function tearDown()
 //    {
 //        $this->prophet->checkPredictions();
 //    }

	public function testIfSetMethodsReturnSelf()
	{
		$ses = new SimpleEmailService();

		$this->assertSame($ses, $ses->setAuth('', ''));
		$this->assertSame($ses, $ses->setVerifyHost(false));
		$this->assertSame($ses, $ses->setVerifyPeer(false));
		$this->assertSame($ses, $ses->setBulkMode(false));
	}

	public function testSetGet()
	{
		$ses = new SimpleEmailService();

		foreach([true, false] as $opt) {
			$this->assertSame($opt, $ses->enableVerifyHost($opt)->verifyHost());
			$this->assertSame($opt, $ses->enableVerifyPeer($opt)->verifyPeer());

			$this->assertSame($opt, $ses->setVerifyHost($opt)->getVerifyHost());
			$this->assertSame($opt, $ses->setVerifyPeer($opt)->getVerifyPeer());
			$this->assertSame($opt, $ses->setBulkMode($opt)->getBulkMode());
		}

		foreach(['a', 'b', 'c'] as $opt) {
			$ses->setAuth($opt, $opt);
			$this->assertSame($opt, $ses->getAccessKey());
			$this->assertSame($opt, $ses->getSecretKey());
			$this->assertSame($opt, $ses->setHost($opt)->getHost());
		}
	}

	/**
	 * @dataProvider initParams
	 */
	public function testSetAuth($accessKey, $secretKey, $host)
	{
		$ses = new SimpleEmailService($accessKey, $secretKey, $host);
		$this->assertSame($accessKey, $ses->getAccessKey());
		$this->assertSame($secretKey, $ses->getSecretKey());
		$this->assertSame($host, $ses->getHost());
	}

	public function initParams()
	{
		return [
			['accessKey', 'secretKey', null],
			['a', 'b', SimpleEmailService::AWS_US_EAST_1],
		];
	}

	/**
	 * @dataProvider validListVerifiedEmailAddresses
	 */
	public function testListVerifiedEmailAddresses($response, $expected)
	{
		$observer = $this->getMockBuilder(SimpleEmailServiceRequest::class)
                         ->setMethods(['setParameter', 'getResponse'])
                         ->getMock();
        
        $observer->expects($this->once())
                 ->method('setParameter')
                 ->with($this->equalTo('Action'), $this->equalTo('ListVerifiedEmailAddresses'));
        
        $observer->expects($this->once())
                 ->method('getResponse')
                 ->willReturn($response);
	
        $ses = new SimpleEmailService();
        $ses->setRequestHandler($observer);
        $this->assertSame($expected, $ses->listVerifiedEmailAddresses());
	}

	public function validListVerifiedEmailAddresses()
	{
		$faker = Faker\Factory::create();
		$emails = array_map(function() use ($faker) { return $faker->email; }, range(1, 10));
		$request_id = $faker->uuid;

		return [
			array_values([
				'response' => json_decode(json_encode([
					'code' => 200,
					'error' => false,
					'body' => [
						'ResponseMetadata' => [
							'RequestId' => $request_id,
						],
						'ListVerifiedEmailAddressesResult' => [
							'VerifiedEmailAddresses' => [
								'member' => $emails
							]
						]
					]
				])),
				'expected' => [
					'Addresses' => $emails,
					'RequestId' => $request_id,
				]
			])
		];
	}
}