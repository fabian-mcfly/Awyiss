<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Model\Table;


use Awyiss\Model\Entity\SurveyEntry;
use Awyiss\Model\Table\SurveyEntriesTable;
use Awyiss\ORM\Association\BelongsTo;
use Awyiss\Test\TestSuite\TestCase;
use Awyiss\Validation\Validator;
use Cake\Datasource\FactoryLocator;


/**
 * SurveyEntriesTable Test Case
 *
 * @see \Awyiss\Model\Table\SurveyEntriesTable
 */
class SurveyEntriesTableTest extends TestCase {
	/**
	 * @var \Awyiss\Model\Table\SurveyEntriesTable
	 */
	protected SurveyEntriesTable $surveyEntriesTable;


	/**
	 * @inheritDoc
	 */
	protected function setUp(): void {
		parent::setUp();

		/** @noinspection PhpFieldAssignmentTypeMismatchInspection */
		$this->surveyEntriesTable = FactoryLocator::get('Table')->get('SurveyEntries');
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\SurveyEntriesTable::ATTRIBUTABLE
	 */
	public function testAttributableConstant(): void {
		$this->assertFalse($this->surveyEntriesTable::ATTRIBUTABLE);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\SurveyEntriesTable::TABLE
	 */
	public function testTableConstant(): void {
		$this->assertEquals('survey_entries', $this->surveyEntriesTable::TABLE);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\SurveyEntriesTable::initializeAssociations()
	 */
	public function testInitializeAssociations(): void {
		$this->assertCount(6, $this->surveyEntriesTable->associations()->keys());

		// Test Surveys association (BelongsTo)
		$this->assertTrue($this->surveyEntriesTable->hasAssociation('Surveys'));
		$surveysAssociation = $this->surveyEntriesTable->getAssociation('Surveys');
		$this->assertInstanceOf(BelongsTo::class, $surveysAssociation);
		$this->assertFalse($surveysAssociation->getCascadeCallbacks());
		$this->assertFalse($surveysAssociation->getDependent());

		// Test Pages association (BelongsTo)
		$this->assertTrue($this->surveyEntriesTable->hasAssociation('Pages'));
		$pagesAssociation = $this->surveyEntriesTable->getAssociation('Pages');
		$this->assertInstanceOf(BelongsTo::class, $pagesAssociation);
		$this->assertFalse($pagesAssociation->getCascadeCallbacks());
		$this->assertFalse($pagesAssociation->getDependent());

		// Test Pages association finder configuration
		$this->assertSame(['all' => ['skipPageRoleCheck' => true]], $pagesAssociation->getFinder());

		// CustomerGroupAccessSettings, CustomerGroupAssignments and MediaAssignments are defined, but we don't care about it for this table

		// 'DeletedByUser' must also exist
		$this->assertTrue($this->surveyEntriesTable->hasAssociation('DeletedByUser'));
		$deletedByUserAssociation = $this->surveyEntriesTable->getAssociation('DeletedByUser');
		$this->assertInstanceOf(BelongsTo::class, $deletedByUserAssociation);
		$this->assertFalse($deletedByUserAssociation->getCascadeCallbacks());
		$this->assertFalse($deletedByUserAssociation->getDependent());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\SurveyEntriesTable::validationDefault()
	 */
	public function testValidationDefault(): void {
		$validator = new Validator();
		$result = $this->surveyEntriesTable->validationDefault($validator);

		$this->assertInstanceOf(Validator::class, $result);
		$this->assertSame('survey_entries', $result->getI18nDomain());

		// Test required fields
		$this->assertTrue($result->hasField('surveyId'));
		$this->assertSame('create', $result->field('surveyId')->isPresenceRequired());

		$this->assertTrue($result->hasField('ipHash'));
		$this->assertSame('create', $result->field('ipHash')->isPresenceRequired());

		$this->assertTrue($result->hasField('postHash'));
		$this->assertSame('create', $result->field('postHash')->isPresenceRequired());

		$this->assertTrue($result->hasField('identifier'));
		$this->assertSame('create', $result->field('identifier')->isPresenceRequired());

		// Test other fields
		$this->assertTrue($result->hasField('id'));
		$this->assertTrue($result->hasField('pageId'));
		$this->assertTrue($result->hasField('data'));
		$this->assertTrue($result->hasField('deleted'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\SurveyEntriesTable::validationDefault()
	 */
	public function testEntityValidationSuccess(): void {
		$data = [
			'surveyId' => 1,
			'pageId' => 1,
			'data' => 'Test survey entry data',
			'ipHash' => 'abc123hash',
			'postHash' => 'def456hash',
			'identifier' => 'test_entry_identifier',
			'deleted' => false,
		];

		$entity = $this->surveyEntriesTable->newDefaultEntity($data);
		$errors = $entity->getErrors();

		$this->assertEmpty($errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\SurveyEntriesTable::validationDefault()
	 */
	public function testEntityValidationMissingRequired(): void {
		$data = [
			'data' => 'Test data',
		];

		$entity = $this->surveyEntriesTable->newDefaultEntity();
		$this->surveyEntriesTable->patchEntity($entity, $data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('surveyId', $errors);
		$this->assertArrayHasKey('_required', $errors['surveyId']);
		$this->assertSame('survey_entries::error_required', $errors['surveyId']['_required']);

		$this->assertArrayHasKey('ipHash', $errors);
		$this->assertArrayHasKey('_required', $errors['ipHash']);
		$this->assertSame('survey_entries::error_required', $errors['ipHash']['_required']);

		$this->assertArrayHasKey('postHash', $errors);
		$this->assertArrayHasKey('_required', $errors['postHash']);
		$this->assertSame('survey_entries::error_required', $errors['postHash']['_required']);

		$this->assertArrayHasKey('identifier', $errors);
		$this->assertArrayHasKey('_required', $errors['identifier']);
		$this->assertSame('survey_entries::error_required', $errors['identifier']['_required']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\SurveyEntriesTable::validationDefault()
	 */
	public function testEntityValidationInvalidTypes(): void {
		$data = [
			'id' => 'not_an_integer',
			'surveyId' => 'not_an_integer',
			'pageId' => 'not_an_integer',
			'data' => true,
			'ipHash' => true,
			'postHash' => true,
			'identifier' => true,
			'deleted' => 'not_a_boolean',
		];

		$entity = $this->surveyEntriesTable->newDefaultEntity();
		$this->surveyEntriesTable->patchEntity($entity, $data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('id', $errors);
		$this->assertArrayHasKey('surveyId', $errors);
		$this->assertArrayHasKey('pageId', $errors);
		$this->assertArrayHasKey('data', $errors);
		$this->assertArrayHasKey('ipHash', $errors);
		$this->assertArrayHasKey('postHash', $errors);
		$this->assertArrayHasKey('identifier', $errors);
		$this->assertArrayHasKey('deleted', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\SurveyEntriesTable::validationDefault()
	 */
	public function testEntityValidationFieldLength(): void {
		$data = [
			'id' => 123456789123, // exceeds 11 char limit
			'surveyId' => 123456789123, // exceeds 11 char limit
			'pageId' => 123456789123, // exceeds 11 char limit
			'data' => str_repeat('a', 65536), // exceeds 65535 byte limit
			'ipHash' => str_repeat('b', 41), // exceeds 40 char limit
			'postHash' => str_repeat('c', 41), // exceeds 40 char limit
			'identifier' => str_repeat('d', 41), // exceeds 40 char limit
		];

		$entity = $this->surveyEntriesTable->newDefaultEntity();
		$this->surveyEntriesTable->patchEntity($entity, $data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('id', $errors);
		$this->assertArrayHasKey('surveyId', $errors);
		$this->assertArrayHasKey('pageId', $errors);
		$this->assertArrayHasKey('data', $errors);
		$this->assertArrayHasKey('ipHash', $errors);
		$this->assertArrayHasKey('postHash', $errors);
		$this->assertArrayHasKey('identifier', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\SurveyEntriesTable::validationDefault()
	 */
	public function testEntityValidationOptionalFields(): void {
		$data = [
			'surveyId' => 1,
			'pageId' => '', // Empty string should be allowed
			'data' => null, // Null should be allowed
			'ipHash' => 'test_ip_hash',
			'postHash' => 'test_post_hash',
			'identifier' => 'test_identifier',
		];

		$entity = $this->surveyEntriesTable->newDefaultEntity();
		$this->surveyEntriesTable->patchEntity($entity, $data);
		$errors = $entity->getErrors();

		// pageId and data are allowEmptyString/null, so empty/null values should be valid
		$this->assertArrayNotHasKey('pageId', $errors);
		$this->assertArrayNotHasKey('data', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\SurveyEntriesTable::validationDefault()
	 */
	public function testEntityValidationNullValues(): void {
		$data = [
			'surveyId' => 1,
			'pageId' => null, // Should be allowed
			'data' => null, // Should be allowed
			'ipHash' => 'test_ip_hash',
			'postHash' => 'test_post_hash',
			'identifier' => 'test_identifier',
		];

		$entity = $this->surveyEntriesTable->newDefaultEntity();
		$this->surveyEntriesTable->patchEntity($entity, $data);
		$errors = $entity->getErrors();

		// pageId and data allow null values
		$this->assertArrayNotHasKey('pageId', $errors);
		$this->assertArrayNotHasKey('data', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\SurveyEntriesTable::buildRules()
	 */
	public function testBuildRulesValidSurveyId(): void {
		$data = [
			'surveyId' => 1, // Existing survey
			'ipHash' => 'test_ip_hash',
			'postHash' => 'test_post_hash',
			'identifier' => 'test_identifier',
		];

		$entity = $this->surveyEntriesTable->newDefaultEntity();
		$this->surveyEntriesTable->patchEntity($entity, $data);

		$result = $this->surveyEntriesTable->checkRules($entity);
		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\SurveyEntriesTable::buildRules()
	 */
	public function testBuildRulesInvalidSurveyId(): void {
		$data = [
			'surveyId' => 99999, // Non-existing survey
			'ipHash' => 'test_ip_hash',
			'postHash' => 'test_post_hash',
			'identifier' => 'test_identifier',
		];

		$entity = $this->surveyEntriesTable->newDefaultEntity();
		$this->surveyEntriesTable->patchEntity($entity, $data);

		$result = $this->surveyEntriesTable->checkRules($entity);

		$this->assertFalse($result);

		$errors = $entity->getErrors();
		$this->assertArrayHasKey('surveyId', $errors);
		$this->assertArrayHasKey('surveyExists', $errors['surveyId']);
		$this->assertSame('survey_entries::error_survey_exists', $errors['surveyId']['surveyExists']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\SurveyEntriesTable::buildRules()
	 */
	public function testBuildRulesValidPageId(): void {
		$data = [
			'surveyId' => 1,
			'pageId' => 1, // Existing page
			'ipHash' => 'test_ip_hash',
			'postHash' => 'test_post_hash',
			'identifier' => 'test_identifier',
		];

		$entity = $this->surveyEntriesTable->newDefaultEntity();
		$this->surveyEntriesTable->patchEntity($entity, $data);

		$result = $this->surveyEntriesTable->checkRules($entity);
		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\SurveyEntriesTable::buildRules()
	 */
	public function testBuildRulesNullPageId(): void {
		$data = [
			'surveyId' => 1,
			'pageId' => null, // Null should be allowed
			'ipHash' => 'test_ip_hash',
			'postHash' => 'test_post_hash',
			'identifier' => 'test_identifier',
		];

		$entity = $this->surveyEntriesTable->newDefaultEntity();
		$this->surveyEntriesTable->patchEntity($entity, $data);

		$result = $this->surveyEntriesTable->checkRules($entity);
		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\SurveyEntriesTable::buildRules()
	 */
	public function testBuildRulesInvalidPageId(): void {
		$data = [
			'surveyId' => 1,
			'pageId' => 99999, // Non-existing page
			'ipHash' => 'test_ip_hash',
			'postHash' => 'test_post_hash',
			'identifier' => 'test_identifier',
		];

		$entity = $this->surveyEntriesTable->newDefaultEntity();
		$this->surveyEntriesTable->patchEntity($entity, $data);

		$result = $this->surveyEntriesTable->checkRules($entity);

		$this->assertFalse($result);

		$errors = $entity->getErrors();
		$this->assertArrayHasKey('pageId', $errors);
		$this->assertArrayHasKey('pageExists', $errors['pageId']);
		$this->assertSame('survey_entries::error_page_exists', $errors['pageId']['pageExists']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\SurveyEntriesTable::newDefaultEntity()
	 */
	public function testNewDefaultEntity(): void {
		/** @var \Awyiss\Model\Entity\SurveyEntry $entity */
		$entity = $this->surveyEntriesTable->newDefaultEntity();

		$this->assertInstanceOf(SurveyEntry::class, $entity);
		$this->assertTrue($entity->isNew());

		// Test default values
		$this->assertNull($entity->surveyId);
		$this->assertNull($entity->pageId);
		$this->assertNull($entity->data);
		$this->assertNull($entity->ipHash);
		$this->assertNull($entity->postHash);
		$this->assertNull($entity->identifier);
		$this->assertFalse($entity->deleted);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\SurveyEntriesTable::newDefaultEntity()
	 */
	public function testNewDefaultEntityWithData(): void {
		$additionalData = [
			'surveyId' => 2,
			'pageId' => 3,
			'data' => 'Custom survey entry data',
			'ipHash' => 'custom_ip_hash',
			'postHash' => 'custom_post_hash',
			'identifier' => 'custom_identifier',
			'deleted' => true,
		];

		$entity = $this->surveyEntriesTable->newDefaultEntity($additionalData);

		$this->assertInstanceOf(SurveyEntry::class, $entity);
		$this->assertTrue($entity->isNew());

		$this->assertSame(2, $entity->surveyId);
		$this->assertSame(3, $entity->pageId);
		$this->assertSame('Custom survey entry data', $entity->data);
		$this->assertSame('custom_ip_hash', $entity->ipHash);
		$this->assertSame('custom_post_hash', $entity->postHash);
		$this->assertSame('custom_identifier', $entity->identifier);
		$this->assertTrue($entity->deleted);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\SurveyEntriesTable
	 */
	public function testCategoriesBehavior(): void {
		$this->assertTrue($this->surveyEntriesTable->hasBehavior('Categories'));

		$config = $this->surveyEntriesTable->getBehavior('Categories')->getConfig();

		$this->assertTrue($config['allowAggregation']);
		$this->assertFalse($config['allowUnassigned']);
		$this->assertTrue($config['enabled']);
		$this->assertEquals('Surveys', $config['associationName']);
		$this->assertEquals('survey', $config['identifier']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\SurveyEntriesTable
	 */
	public function testSearchBehavior(): void {
		$this->assertTrue($this->surveyEntriesTable->hasBehavior('Search'));

		$config = $this->surveyEntriesTable->getBehavior('Search')->getConfig();

		$this->assertArrayHasKey('blocklistedColumns', $config);
		$this->assertEquals(['survey_id', 'page_id'], $config['blocklistedColumns']);
	}
}
