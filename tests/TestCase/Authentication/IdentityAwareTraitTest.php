<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Authentication;


use Awyiss\Authentication\IdentityAwareTrait;
use Awyiss\Model\Entity\User;
use Awyiss\Test\TestSuite\TestCase;


/**
 * Test class for Awyiss\Authentication\IdentityAwareTrait
 */
class IdentityAwareTraitTest extends TestCase {
	use IdentityAwareTrait;


	/**
	 * @return void
	 */
	public function testGetIdentity(): void {
		$identity = $this->getIdentity();
		$this->assertNull($identity);

		$this->login();
		$identity = $this->getIdentity();
		$this->assertInstanceOf(User::class, $identity);
	}


	/**
	 * @return void
	 */
	public function testSetIdentity(): void {
		$this->assertNull($this->identity);

		$this->login();
		$this->getIdentity();

		$this->assertInstanceOf(User::class, $this->identity);
	}
}
