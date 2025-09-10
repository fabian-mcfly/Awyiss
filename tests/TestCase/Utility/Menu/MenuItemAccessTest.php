<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Utility\Menu;


use Awyiss\Test\TestSuite\TestCase;
use Awyiss\Utility\Menu\MenuItemAccess;
use RuntimeException;
use stdClass;


/**
 * Test case for MenuItemAccess class.
 *
 * @see \Awyiss\Utility\Menu\MenuItemAccess
 */
class MenuItemAccessTest extends TestCase {
	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testConstructor(): void {
		$access = new stdClass();
		$access->scope = 'test-scope';
		$access->identifier = 'test-identifier';
		$access->additionalData = ['key' => 'value'];

		$menuItemAccess = new MenuItemAccess($access);

		$this->assertSame('test-scope', $menuItemAccess->getScope());
		$this->assertSame('test-identifier', $menuItemAccess->getIdentifier());
		$this->assertSame(['key' => 'value'], $menuItemAccess->getAdditionalData());
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testConstructorWithRequiredProperties(): void {
		$access = new stdClass();
		$access->scope = 'test-scope';
		$access->identifier = 'test-identifier';

		$menuItemAccess = new MenuItemAccess($access);

		$this->assertSame('test-scope', $menuItemAccess->getScope());
		$this->assertSame('test-identifier', $menuItemAccess->getIdentifier());
		$this->assertNull($menuItemAccess->getAdditionalData());
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testConstructorWithArrayIdentifier(): void {
		$access = new stdClass();
		$access->scope = 'test-scope';
		$access->identifier = ['id1', 'id2'];

		$menuItemAccess = new MenuItemAccess($access);

		$this->assertSame('test-scope', $menuItemAccess->getScope());
		$this->assertSame(['id1', 'id2'], $menuItemAccess->getIdentifier());
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testConstructorThrowsExceptionWhenScopeIsMissing(): void {
		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('Access scope is required');

		$access = new stdClass();
		$access->identifier = 'test-identifier';

		new MenuItemAccess($access);
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testConstructorThrowsExceptionWhenIdentifierIsMissing(): void {
		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('Access identifier is required');

		$access = new stdClass();
		$access->scope = 'test-scope';

		new MenuItemAccess($access);
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testSetAdditionalData(): void {
		$access = new stdClass();
		$access->scope = 'test-scope';
		$access->identifier = 'test-identifier';

		$menuItemAccess = new MenuItemAccess($access);
		$this->assertNull($menuItemAccess->getAdditionalData());

		$menuItemAccess->setAdditionalData(['key' => 'value']);
		$this->assertSame(['key' => 'value'], $menuItemAccess->getAdditionalData());

		$menuItemAccess->setAdditionalData(null);
		$this->assertNull($menuItemAccess->getAdditionalData());
	}
}
