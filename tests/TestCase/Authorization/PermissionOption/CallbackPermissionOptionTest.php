<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Authorization\PermissionOption;


use Awyiss\Authorization\Permission\PermissionAccess;
use Awyiss\Authorization\Permission\PermissionCollection;
use Awyiss\Authorization\PermissionOption\CallbackPermissionOption;
use Awyiss\Authorization\PermissionOption\PermissionOptionCollection;
use Awyiss\Test\TestSuite\TestCase;


/**
 * CallbackPermissionOptionTest class
 */
class CallbackPermissionOptionTest extends TestCase {
	/**
	 * @return void
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public function testConstructorInitializesCallbacksCorrectly(): void {
		$permissionOptionCollection = $this->createMock(PermissionOptionCollection::class);

		$config = [
			'callbacks' => [
				'general' => function () {
					return true;
				},
				'Entity.create' => function () {
					return false;
				},
			],
		];

		$callbackPermissionOption = new CallbackPermissionOption($config, $permissionOptionCollection);

		$this->assertIsCallable($callbackPermissionOption->getCallback('general'));
		$this->assertIsCallable($callbackPermissionOption->getCallback('Entity.create'));
		$this->assertNull($callbackPermissionOption->getCallback('Entity.update'));
	}


	/**
	 * @return void
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public function testSetCallback(): void {
		$permissionOptionCollection = $this->createMock(PermissionOptionCollection::class);
		$callbackPermissionOption = new CallbackPermissionOption([], $permissionOptionCollection);

		$callback = function () {
			return true;
		};
		$callbackPermissionOption->setCallback('Entity.create', $callback);

		$this->assertSame($callback, $callbackPermissionOption->getCallback('Entity.create'));
	}


	/**
	 * @return void
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public function testSetCallbacks(): void {
		$permissionOptionCollection = $this->createMock(PermissionOptionCollection::class);
		$callbackPermissionOption = new CallbackPermissionOption([], $permissionOptionCollection);

		$callbacks = [
			'general' => function () {
				return true;
			},
			'Entity.create' => function () {
				return false;
			},
		];
		$callbackPermissionOption->setCallbacks($callbacks);

		$this->assertSame($callbacks['general'], $callbackPermissionOption->getCallback('general'));
		$this->assertSame($callbacks['Entity.create'], $callbackPermissionOption->getCallback('Entity.create'));
	}


	/**
	 * @return void
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public function testIsAccessibleWithCallback(): void {
		$permissionOptionCollection = $this->createMock(PermissionOptionCollection::class);
		$permissionCollection = $this->createMock(PermissionCollection::class);

		$callback = function () {
			return true;
		};

		$config = [
			'callbacks' => [
				'general' => $callback,
			],
		];

		$callbackPermissionOption = new CallbackPermissionOption($config, $permissionOptionCollection);

		$this->assertTrue($callbackPermissionOption->isAccessible(PermissionAccess::Granted, [], [], $permissionCollection));
	}


	/**
	 * @return void
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public function testIsAccessibleNotWithCallback(): void {
		$permissionOptionCollection = $this->createMock(PermissionOptionCollection::class);
		$permissionCollection = $this->createMock(PermissionCollection::class);

		$callback = function ($accessible) {
			return !$accessible;
		};

		$config = [
			'callbacks' => [
				'general' => $callback,
			],
		];

		$callbackPermissionOption = new CallbackPermissionOption($config, $permissionOptionCollection);

		$this->assertFalse($callbackPermissionOption->isAccessible(PermissionAccess::Granted, [], [], $permissionCollection));
	}


	/**
	 * @return void
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public function testIsAccessibleWithoutCallback(): void {
		$permissionOptionCollection = $this->createMock(PermissionOptionCollection::class);
		$permissionCollection = $this->createMock(PermissionCollection::class);

		$callbackPermissionOption = new CallbackPermissionOption([], $permissionOptionCollection);

		$this->assertTrue($callbackPermissionOption->isAccessible(PermissionAccess::Granted, [], [], $permissionCollection));
	}
}
