<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Model\Table;


use Awyiss\Model\Entity\UrlHistory;
use Awyiss\Model\Table\UrlHistoryTable;
use Awyiss\ORM\Association\BelongsTo;
use Awyiss\Test\TestSuite\TestCase;
use Awyiss\Validation\Validator;
use Cake\Datasource\FactoryLocator;


/**
 * UrlHistoryTable Test Case
 *
 * @see \Awyiss\Model\Table\UrlHistoryTable
 */
class UrlHistoryTableTest extends TestCase {
	/**
	 * @var \Awyiss\Model\Table\UrlHistoryTable
	 */
	protected UrlHistoryTable $urlHistoryTable;


	/**
	 * @inheritDoc
	 */
	protected function setUp(): void {
		parent::setUp();

		/** @noinspection PhpFieldAssignmentTypeMismatchInspection */
		$this->urlHistoryTable = FactoryLocator::get('Table')->get('UrlHistory');
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\UrlHistoryTable::ATTRIBUTABLE
	 */
	public function testAttributableConstant(): void {
		/** @noinspection PhpUnitAssertTrueWithIncompatibleTypeArgumentInspection */
		$this->assertTrue($this->urlHistoryTable::ATTRIBUTABLE);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\UrlHistoryTable::TABLE
	 */
	public function testTableConstant(): void {
		$this->assertEquals('url_history', $this->urlHistoryTable::TABLE);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\UrlHistoryTable::initializeAssociations()
	 */
	public function testInitializeAssociations(): void {
		$this->assertCount(6, $this->urlHistoryTable->associations()->keys());

		$this->assertTrue($this->urlHistoryTable->hasAssociation('Pages'));
		$pagesAssociation = $this->urlHistoryTable->getAssociation('Pages');
		$this->assertInstanceOf(BelongsTo::class, $pagesAssociation);
		$this->assertSame('foreignKey', $pagesAssociation->getForeignKey());
		$this->assertSame(['UrlHistory.scope' => 'Pages'], $pagesAssociation->getConditions());

		$this->assertTrue($this->urlHistoryTable->hasAssociation('Media'));
		$mediaAssociation = $this->urlHistoryTable->getAssociation('Media');
		$this->assertInstanceOf(BelongsTo::class, $mediaAssociation);
		$this->assertSame('foreignKey', $mediaAssociation->getForeignKey());
		$this->assertSame(['UrlHistory.scope' => 'Media'], $mediaAssociation->getConditions());

		// 'CreatedByUser' must also exist
		$this->assertTrue($this->urlHistoryTable->hasAssociation('CreatedByUser'));
		$createdByUserAssociation = $this->urlHistoryTable->getAssociation('CreatedByUser');
		$this->assertInstanceOf(BelongsTo::class, $createdByUserAssociation);
		$this->assertFalse($createdByUserAssociation->getCascadeCallbacks());
		$this->assertFalse($createdByUserAssociation->getDependent());

		// 'ChangedByUser' must also exist
		$this->assertTrue($this->urlHistoryTable->hasAssociation('ChangedByUser'));
		$changedByUserAssociation = $this->urlHistoryTable->getAssociation('ChangedByUser');
		$this->assertInstanceOf(BelongsTo::class, $changedByUserAssociation);
		$this->assertFalse($changedByUserAssociation->getCascadeCallbacks());
		$this->assertFalse($changedByUserAssociation->getDependent());

		// 'DeletedByUser' must also exist
		$this->assertTrue($this->urlHistoryTable->hasAssociation('DeletedByUser'));
		$deletedByUserAssociation = $this->urlHistoryTable->getAssociation('DeletedByUser');
		$this->assertInstanceOf(BelongsTo::class, $deletedByUserAssociation);
		$this->assertFalse($deletedByUserAssociation->getCascadeCallbacks());
		$this->assertFalse($deletedByUserAssociation->getDependent());

		// MediaAssignments is also defined, but we don't care about it for this table
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\UrlHistoryTable::getAvailableScopes()
	 */
	public function testGetAvailableScopes(): void {
		$scopes = $this->urlHistoryTable->getAvailableScopes();

		$this->assertIsArray($scopes);
		$this->assertSame(['pages', 'media'], $scopes);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\UrlHistoryTable::validationDefault()
	 */
	public function testValidationDefault(): void {
		$validator = new Validator();
		$result = $this->urlHistoryTable->validationDefault($validator);

		$this->assertInstanceOf(Validator::class, $result);
		$this->assertSame('UrlHistory', $result->getI18nDomain());

		// Test that all fields have validation rules
		$this->assertTrue($result->hasField('url'));
		$this->assertTrue($result->hasField('target'));
		$this->assertTrue($result->hasField('foreignKey'));
		$this->assertTrue($result->hasField('status'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\UrlHistoryTable::validationDefault()
	 */
	public function testEntityValidationSuccess(): void {
		$data = [
			'url' => '/old-page',
			'target' => '/new-page',
			'status' => 301,
		];

		$entity = $this->urlHistoryTable->newDefaultEntity($data);
		$errors = $entity->getErrors();

		$this->assertEmpty($errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\UrlHistoryTable::validationDefault()
	 */
	public function testEntityValidationSuccessWithScope(): void {
		$data = [
			'url' => '/old-page',
			'scope' => 'Pages',
			'foreignKey' => 1,
			'status' => 301,
		];

		$entity = $this->urlHistoryTable->newDefaultEntity($data);
		$errors = $entity->getErrors();

		$this->assertEmpty($errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\UrlHistoryTable::validationDefault()
	 */
	public function testEntityValidationEmptyUrl(): void {
		$data = [
			'url' => '',
			'target' => '/new-page',
			'status' => 301,
		];

		$entity = $this->urlHistoryTable->newDefaultEntity();
		$this->urlHistoryTable->patchEntity($entity, $data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('url', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\UrlHistoryTable::validationDefault()
	 */
	public function testEntityValidationBlankUrl(): void {
		$data = [
			'url' => '   ', // Only whitespace
			'target' => '/new-page',
			'status' => 301,
		];

		$entity = $this->urlHistoryTable->newDefaultEntity();
		$this->urlHistoryTable->patchEntity($entity, $data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('url', $errors);
		$this->assertArrayHasKey('notBlank', $errors['url']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\UrlHistoryTable::validationDefault()
	 */
	public function testEntityValidationUrlTooLong(): void {
		$data = [
			'url' => str_repeat('a', 1025), // exceeds 1024 char limit
			'target' => '/new-page',
			'status' => 301,
		];

		$entity = $this->urlHistoryTable->newDefaultEntity();
		$this->urlHistoryTable->patchEntity($entity, $data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('url', $errors);
		$this->assertArrayHasKey('maxLength', $errors['url']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\UrlHistoryTable::validationDefault()
	 */
	public function testEntityValidationTargetRequiredWhenNoScope(): void {
		$data = [
			'url' => '/old-page',
			'status' => 301,
			// No target and no scope - target should be required
		];

		$entity = $this->urlHistoryTable->newDefaultEntity();
		$this->urlHistoryTable->patchEntity($entity, $data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('target', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\UrlHistoryTable::validationDefault()
	 */
	public function testEntityValidationTargetNotRequiredWhenScope(): void {
		$data = [
			'url' => '/old-page',
			'scope' => 'Pages',
			'foreignKey' => 1,
			'status' => 301,
			// No target but scope is provided - target should not be required
		];

		$entity = $this->urlHistoryTable->newDefaultEntity();
		$this->urlHistoryTable->patchEntity($entity, $data);
		$errors = $entity->getErrors();

		$this->assertArrayNotHasKey('target', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\UrlHistoryTable::validationDefault()
	 */
	public function testEntityValidationTargetTooLong(): void {
		$data = [
			'url' => '/old-page',
			'target' => str_repeat('b', 1025), // exceeds 1024 char limit
			'status' => 301,
		];

		$entity = $this->urlHistoryTable->newDefaultEntity();
		$this->urlHistoryTable->patchEntity($entity, $data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('target', $errors);
		$this->assertArrayHasKey('maxLength', $errors['target']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\UrlHistoryTable::validationDefault()
	 */
	public function testEntityValidationForeignKeyRequiredWhenScope(): void {
		$data = [
			'url' => '/old-page',
			'scope' => 'Pages',
			'status' => 301,
			// No foreignKey but scope is provided - foreignKey should be required
		];

		$entity = $this->urlHistoryTable->newDefaultEntity();
		$this->urlHistoryTable->patchEntity($entity, $data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('foreignKey', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\UrlHistoryTable::validationDefault()
	 */
	public function testEntityValidationForeignKeyNotRequiredWhenNoScope(): void {
		$data = [
			'url' => '/old-page',
			'target' => '/new-page',
			'status' => 301,
			// No scope and no foreignKey - foreignKey should not be required
		];

		$entity = $this->urlHistoryTable->newDefaultEntity();
		$this->urlHistoryTable->patchEntity($entity, $data);
		$errors = $entity->getErrors();

		$this->assertArrayNotHasKey('foreignKey', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\UrlHistoryTable::validationDefault()
	 */
	public function testEntityValidationForeignKeyInvalidType(): void {
		$data = [
			'url' => '/old-page',
			'scope' => 'Pages',
			'foreignKey' => 'not_an_integer',
			'status' => 301,
		];

		$entity = $this->urlHistoryTable->newDefaultEntity();
		$this->urlHistoryTable->patchEntity($entity, $data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('foreignKey', $errors);
		$this->assertArrayHasKey('isInteger', $errors['foreignKey']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\UrlHistoryTable::validationDefault()
	 */
	public function testEntityValidationForeignKeyTooLong(): void {
		$data = [
			'url' => '/old-page',
			'scope' => 'Pages',
			'foreignKey' => 123456789123, // exceeds 11 char limit
			'status' => 301,
		];

		$entity = $this->urlHistoryTable->newDefaultEntity();
		$this->urlHistoryTable->patchEntity($entity, $data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('foreignKey', $errors);
		$this->assertArrayHasKey('maxLength', $errors['foreignKey']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\UrlHistoryTable::validationDefault()
	 */
	public function testEntityValidationStatusRequired(): void {
		$data = [
			'url' => '/old-page',
			'target' => '/new-page',
			// No status - should be required
		];

		$entity = $this->urlHistoryTable->newDefaultEntity();
		$this->urlHistoryTable->patchEntity($entity, $data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('status', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\UrlHistoryTable::validationDefault()
	 */
	public function testEntityValidationStatusInvalidType(): void {
		$data = [
			'url' => '/old-page',
			'target' => '/new-page',
			'status' => 'not_an_integer',
		];

		$entity = $this->urlHistoryTable->newDefaultEntity();
		$this->urlHistoryTable->patchEntity($entity, $data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('status', $errors);
		$this->assertArrayHasKey('inList', $errors['status']);
		$this->assertSame('UrlHistory::error_in_list', $errors['status']['inList']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\UrlHistoryTable::validationDefault()
	 */
	public function testEntityValidationStatusWrongLength(): void {
		$data = [
			'url' => '/old-page',
			'target' => '/new-page',
			'status' => 30, // Not 3 digits
		];

		$entity = $this->urlHistoryTable->newDefaultEntity();
		$this->urlHistoryTable->patchEntity($entity, $data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('status', $errors);
		$this->assertArrayHasKey('inList', $errors['status']);
		$this->assertSame('UrlHistory::error_in_list', $errors['status']['inList']);
	}


	/**
	 * @testWith [301, true]
	 *           [302, true]
	 *           [307, true]
	 *           [308, true]
	 *           [404, false]
	 * @param int $status
	 * @param bool $allowed
	 * @return void
	 * @see \Awyiss\Model\Table\UrlHistoryTable::validationDefault()
	 */
	public function testEntityValidationStatusValidLengths(int $status, bool $allowed): void {
		$data = [
			'url' => '/old-page',
			'target' => '/new-page',
			'status' => $status,
		];

		$entity = $this->urlHistoryTable->newDefaultEntity();
		$this->urlHistoryTable->patchEntity($entity, $data);
		$errors = $entity->getErrors();

		if ($allowed) {
			$this->assertEmpty($errors);
		}
		else {
			$this->assertArrayHasKey('status', $errors);
			$this->assertArrayHasKey('inList', $errors['status']);
			$this->assertSame('UrlHistory::error_in_list', $errors['status']['inList']);
		}
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\UrlHistoryTable::validationDefault()
	 */
	public function testEntityValidationInvalidTypes(): void {
		$data = [
			'url' => true,
			'target' => true,
			'status' => 'not_an_integer',
		];

		$entity = $this->urlHistoryTable->newDefaultEntity();
		$this->urlHistoryTable->patchEntity($entity, $data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('url', $errors);
		$this->assertArrayHasKey('target', $errors);
		$this->assertArrayHasKey('status', $errors);

		$data = [
			'url' => true,
			'scope' => true,
			'foreignKey' => 'not_an_integer',
			'status' => 'not_an_integer',
		];

		$entity = $this->urlHistoryTable->newDefaultEntity();
		$this->urlHistoryTable->patchEntity($entity, $data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('url', $errors);
		$this->assertArrayHasKey('foreignKey', $errors);
		$this->assertArrayHasKey('status', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\UrlHistoryTable::buildRules()
	 */
	public function testBuildRulesValidPagesScope(): void {
		$data = [
			'url' => '/old-page',
			'scope' => 'Pages',
			'foreignKey' => 1, // Existing page
			'status' => 301,
		];

		$entity = $this->urlHistoryTable->newDefaultEntity();
		$this->urlHistoryTable->patchEntity($entity, $data);

		$result = $this->urlHistoryTable->checkRules($entity);
		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\UrlHistoryTable::buildRules()
	 */
	public function testBuildRulesInvalidPagesScope(): void {
		$data = [
			'url' => '/old-page',
			'scope' => 'Pages',
			'foreignKey' => 99999, // Non-existing page
			'status' => 301,
		];

		$entity = $this->urlHistoryTable->newDefaultEntity();
		$this->urlHistoryTable->patchEntity($entity, $data);

		$result = $this->urlHistoryTable->checkRules($entity);

		$this->assertFalse($result);

		$errors = $entity->getErrors();
		$this->assertArrayHasKey('foreignKey', $errors);
		$this->assertArrayHasKey('validForeignKey', $errors['foreignKey']);
		$this->assertSame('UrlHistory::error_valid_foreign_key', $errors['foreignKey']['validForeignKey']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\UrlHistoryTable::buildRules()
	 */
	public function testBuildRulesValidMediaScope(): void {
		$data = [
			'url' => '/old-media',
			'scope' => 'Media',
			'foreignKey' => 1, // Existing media
			'status' => 301,
		];

		$entity = $this->urlHistoryTable->newDefaultEntity();
		$this->urlHistoryTable->patchEntity($entity, $data);

		$result = $this->urlHistoryTable->checkRules($entity);
		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\UrlHistoryTable::buildRules()
	 */
	public function testBuildRulesInvalidMediaScope(): void {
		$data = [
			'url' => '/old-media',
			'scope' => 'Media',
			'foreignKey' => 99999, // Non-existing media
			'status' => 301,
		];

		$entity = $this->urlHistoryTable->newDefaultEntity();
		$this->urlHistoryTable->patchEntity($entity, $data);

		$result = $this->urlHistoryTable->checkRules($entity);

		$this->assertFalse($result);

		$errors = $entity->getErrors();
		$this->assertArrayHasKey('foreignKey', $errors);
		$this->assertArrayHasKey('validForeignKey', $errors['foreignKey']);
		$this->assertSame('UrlHistory::error_valid_foreign_key', $errors['foreignKey']['validForeignKey']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\UrlHistoryTable::buildRules()
	 */
	public function testBuildRulesNoScopeEmptyForeignKey(): void {
		$data = [
			'url' => '/old-page',
			'target' => '/new-page',
			'status' => 301,
			// No scope, no foreignKey - should be valid
		];

		$entity = $this->urlHistoryTable->newDefaultEntity();
		$this->urlHistoryTable->patchEntity($entity, $data);

		$result = $this->urlHistoryTable->checkRules($entity);
		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\UrlHistoryTable::buildRules()
	 */
	public function testBuildRulesNoScopeWithForeignKey(): void {
		$data = [
			'url' => '/old-page',
			'foreignKey' => 123, // Has foreignKey but no scope - should fail
			'status' => 301,
		];

		$entity = $this->urlHistoryTable->newDefaultEntity();
		$this->urlHistoryTable->patchEntity($entity, $data, ['events' => false]);

		$result = $this->urlHistoryTable->checkRules($entity);

		$this->assertFalse($result);

		$errors = $entity->getErrors();

		$this->assertArrayHasKey('foreignKey', $errors);
		$this->assertArrayHasKey('validForeignKey', $errors['foreignKey']);
		$this->assertSame('UrlHistory::error_valid_foreign_key', $errors['foreignKey']['validForeignKey']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\UrlHistoryTable::buildRules()
	 */
	public function testBuildRulesValidTargetWithScope(): void {
		$data = [
			'url' => '/old-page',
			'scope' => 'Pages',
			'foreignKey' => 1,
			// No target but scope is provided - should fail validTarget rule
			'status' => 301,
		];

		$entity = $this->urlHistoryTable->newDefaultEntity();
		$this->urlHistoryTable->patchEntity($entity, $data, ['events' => false]);

		$result = $this->urlHistoryTable->checkRules($entity);

		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\UrlHistoryTable::buildRules()
	 */
	public function testBuildRulesInvalidTargetWithScope(): void {
		$data = [
			'url' => '/old-page',
			'scope' => 'Pages',
			'foreignKey' => 1,
			'target' => '/new-page',
			'status' => 301,
		];

		$entity = $this->urlHistoryTable->newDefaultEntity();
		$this->urlHistoryTable->patchEntity($entity, $data, ['events' => false]);

		$result = $this->urlHistoryTable->checkRules($entity);
		$this->assertFalse($result);

		$errors = $entity->getErrors();

		$this->assertArrayHasKey('target', $errors);
		$this->assertArrayHasKey('validTarget', $errors['target']);
		$this->assertSame('UrlHistory::error_valid_target', $errors['target']['validTarget']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\UrlHistoryTable::newDefaultEntity()
	 */
	public function testNewDefaultEntity(): void {
		/** @var \Awyiss\Model\Entity\UrlHistory $entity */
		$entity = $this->urlHistoryTable->newDefaultEntity();

		$this->assertInstanceOf(UrlHistory::class, $entity);
		$this->assertTrue($entity->isNew());

		// Test default values
		$this->assertNull($entity->url);
		$this->assertNull($entity->target);
		$this->assertNull($entity->scope);
		$this->assertNull($entity->foreignKey);
		$this->assertNull($entity->status);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\UrlHistoryTable::newDefaultEntity()
	 */
	public function testNewDefaultEntityWithData(): void {
		$additionalData = [
			'url' => '/custom-old-page',
			'target' => '/custom-new-page',
			'scope' => 'Pages',
			'foreignKey' => 2,
			'status' => 302,
		];

		$entity = $this->urlHistoryTable->newDefaultEntity($additionalData);

		$this->assertInstanceOf(UrlHistory::class, $entity);
		$this->assertTrue($entity->isNew());

		$this->assertSame('/custom-old-page', $entity->url);
		$this->assertSame('/custom-new-page', $entity->target);
		$this->assertSame('Pages', $entity->scope);
		$this->assertSame(2, $entity->foreignKey);
		$this->assertSame(302, $entity->status);
	}
}
