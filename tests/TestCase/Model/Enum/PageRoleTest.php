<?php


/**
 * @noinspection PhpMultipleClassDeclarationsInspection
 */


declare(strict_types=1); // phpcs:ignore


namespace Awyiss\Test\TestCase\Model\Enum;


use Awyiss\Model\Enum\PageRole;
use Awyiss\Model\Enum\PageRoleEnumInterface;
use Awyiss\Test\TestSuite\TestCase;
use Cake\Database\Type\EnumLabelInterface;
use ValueError;


/**
 * PageRole Test Case
 *
 * @see \Awyiss\Model\Enum\PageRole
 */
class PageRoleTest extends TestCase {
	/**
	 * @return void
	 * @see \Awyiss\Model\Enum\PageRole
	 */
	public function testEnumCases(): void {
		$this->assertEquals(1, PageRole::Page->value);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Enum\PageRole
	 */
	public function testEnumFromMethod(): void {
		$this->assertEquals(PageRole::Page, PageRole::from(1));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Enum\PublicationDataType
	 * @throws \ValueError
	 */
	public function testEnumFromMethodThrowsExceptionForInvalidValue(): void {
		$this->expectException(ValueError::class);
		$this->expectExceptionMessage('123 is not a valid backing value for enum Awyiss\Model\Enum\PageRole');

		/** @noinspection PhpExpressionResultUnusedInspection, PhpCaseWithValueNotFoundInEnumInspection */
		PageRole::from(123);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Enum\PageRole
	 * @noinspection PhpCaseWithValueNotFoundInEnumInspection
	 */
	public function testEnumTryFromMethodInvalid(): void {
		$this->assertNull(PageRole::tryFrom(0));
		$this->assertNull(PageRole::tryFrom(2));
		$this->assertNull(PageRole::tryFrom(999));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Enum\PageRole
	 */
	public function testEnumImplementsInterfaces(): void {
		$this->assertInstanceOf(EnumLabelInterface::class, PageRole::Page);
		$this->assertInstanceOf(PageRoleEnumInterface::class, PageRole::Page);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Trait\PageRoleEnumTrait::tryFromName()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testTryFromNameValid(): void {
		$result = PageRole::tryFromName('page');
		$this->assertEquals(PageRole::Page, $result);

		$result = PageRole::tryFromName('pages');
		$this->assertEquals(PageRole::Page, $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Trait\PageRoleEnumTrait::tryFromName()
	 */
	public function testTryFromNameInvalid(): void {
		$this->assertNull(PageRole::tryFromName('invalid'));
		$this->assertNull(PageRole::tryFromName(''));
		$this->assertNull(PageRole::tryFromName('article'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Trait\PageRoleEnumTrait::tableAlias()
	 */
	public function testTableAlias(): void {
		$this->assertEquals('Pages', PageRole::Page->tableAlias());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Trait\PageRoleEnumTrait::tableName()
	 */
	public function testTableName(): void {
		$this->assertEquals('pages', PageRole::Page->tableName());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Trait\PageRoleEnumTrait::label()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testLabel(): void {
		$label = PageRole::Page->label();

		$this->assertIsString($label);
		$this->assertSame('Seite', $label);
	}
}
