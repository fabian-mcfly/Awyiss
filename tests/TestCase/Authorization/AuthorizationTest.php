<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Authorization;


use Awyiss\Authorization\Authorization;
use Awyiss\Authorization\AuthorizationService;
use Awyiss\Test\TestSuite\TestCase;
use Psr\Http\Message\ServerRequestInterface;


/**
 * AuthorizationTest class
 */
class AuthorizationTest extends TestCase {
	/**
	 * @return void
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	public function testGetAuthorizationService(): void {
		$authorization = new Authorization('Backend');

		$service = $authorization->getAuthorizationService($this->createMock(ServerRequestInterface::class));

		/** @noinspection PhpConditionAlreadyCheckedInspection */
		$this->assertInstanceOf(AuthorizationService::class, $service);

		$policies = $service->getPolicies();
		$this->assertNotEmpty($policies);

		//Make sure the key `Languages` is a string (`\Awyiss\Authorization\Policy\Backend\LanguagesPolicy`)
		$this->assertArrayHasKey('Languages', $policies);
		$this->assertEquals('\Awyiss\Authorization\Policy\Backend\LanguagesPolicy', $policies['Languages']);
	}
}
