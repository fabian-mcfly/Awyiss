<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Model\Table;


use Awyiss\Model\Entity\FormEntry;
use Awyiss\Model\Table\FormEntriesTable;
use Awyiss\ORM\Association\BelongsTo;
use Awyiss\ORM\Association\HasMany;
use Awyiss\Test\TestSuite\TestCase;
use Awyiss\Validation\Validator;
use Cake\Datasource\FactoryLocator;


/**
 * FormEntriesTable Test Case
 *
 * @see \Awyiss\Model\Table\FormEntriesTable
 */
class FormEntriesTableTest extends TestCase {
	/**
	 * @var \Awyiss\Model\Table\FormEntriesTable
	 */
	protected FormEntriesTable $formEntriesTable;


	/**
	 * @inheritDoc
	 */
	protected function setUp(): void {
		parent::setUp();

		/** @noinspection PhpFieldAssignmentTypeMismatchInspection */
		$this->formEntriesTable = FactoryLocator::get('Table')->get('FormEntries');
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\FormEntriesTable::ATTRIBUTABLE
	 */
	public function testAttributableConstant(): void {
		$this->assertFalse($this->formEntriesTable::ATTRIBUTABLE);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\FormEntriesTable::TABLE
	 */
	public function testTableConstant(): void {
		$this->assertEquals('form_entries', $this->formEntriesTable::TABLE);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\FormEntriesTable::initializeAssociations()
	 */
	public function testInitializeAssociations(): void {
		$this->assertCount(5, $this->formEntriesTable->associations()->keys());

		// Test Forms association (BelongsTo)
		$this->assertTrue($this->formEntriesTable->hasAssociation('Forms'));
		$formsAssociation = $this->formEntriesTable->getAssociation('Forms');
		$this->assertInstanceOf(BelongsTo::class, $formsAssociation);

		// Test Pages association (BelongsTo)
		$this->assertTrue($this->formEntriesTable->hasAssociation('Pages'));
		$pagesAssociation = $this->formEntriesTable->getAssociation('Pages');
		$this->assertInstanceOf(BelongsTo::class, $pagesAssociation);

		// Test Pages association has skipPageRoleCheck finder option
		$this->assertSame('pageId', $pagesAssociation->getForeignKey());
		$this->assertSame(['all' => ['skipPageRoleCheck' => true]], $pagesAssociation->getFinder());

		// Test Languages association (BelongsTo)
		$this->assertTrue($this->formEntriesTable->hasAssociation('Languages'));
		$languagesAssociation = $this->formEntriesTable->getAssociation('Languages');
		$this->assertInstanceOf(BelongsTo::class, $languagesAssociation);
		$this->assertFalse($languagesAssociation->getCascadeCallbacks());
		$this->assertFalse($languagesAssociation->getDependent());
		$this->assertEquals('shortcode', $languagesAssociation->getBindingKey());
		$this->assertEquals('languageShortcode', $languagesAssociation->getForeignKey());

		// 'MediaAssignments' must also exist
		$this->assertTrue($this->formEntriesTable->hasAssociation('MediaAssignments'));
		$mediaAssignmentsAssociation = $this->formEntriesTable->getAssociation('MediaAssignments');
		$this->assertInstanceOf(HasMany::class, $mediaAssignmentsAssociation);
		$this->assertTrue($mediaAssignmentsAssociation->getCascadeCallbacks());
		$this->assertTrue($mediaAssignmentsAssociation->getDependent());

		$this->assertTrue($this->formEntriesTable->hasAssociation('DeletedByUser'));
		$deletedByUserAssociation = $this->formEntriesTable->getAssociation('DeletedByUser');
		$this->assertInstanceOf(BelongsTo::class, $deletedByUserAssociation);
		$this->assertFalse($deletedByUserAssociation->getCascadeCallbacks());
		$this->assertFalse($deletedByUserAssociation->getDependent());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\FormEntriesTable::validationDefault()
	 */
	public function testValidationDefault(): void {
		$validator = new Validator();
		$result = $this->formEntriesTable->validationDefault($validator);

		$this->assertInstanceOf(Validator::class, $result);
		$this->assertSame('FormEntries', $result->getI18nDomain());

		// Test required fields
		$this->assertTrue($result->hasField('formId'));
		$this->assertSame('create', $result->field('formId')->isPresenceRequired());

		$this->assertTrue($result->hasField('pageId'));
		$this->assertSame('create', $result->field('pageId')->isPresenceRequired());

		$this->assertTrue($result->hasField('ipHash'));
		$this->assertSame('create', $result->field('ipHash')->isPresenceRequired());

		$this->assertTrue($result->hasField('postHash'));
		$this->assertSame('create', $result->field('postHash')->isPresenceRequired());

		$this->assertTrue($result->hasField('identifier'));
		$this->assertSame('create', $result->field('identifier')->isPresenceRequired());

		// Test other fields exist
		$this->assertTrue($result->hasField('subject'));
		$this->assertTrue($result->hasField('subjectConfirmation'));
		$this->assertTrue($result->hasField('body'));
		$this->assertTrue($result->hasField('bodyConfirmation'));
		$this->assertTrue($result->hasField('data'));
		$this->assertTrue($result->hasField('deleted'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\FormEntriesTable::validationDefault()
	 */
	public function testEntityValidationSuccess(): void {
		$data = [
			'formId' => 1,
			'pageId' => 1,
			'subject' => 'Test Subject',
			'subjectConfirmation' => 'Test Confirmation Subject',
			'body' => 'Test email body',
			'bodyConfirmation' => 'Test confirmation email body',
			'data' => 'Test data',
			'ipHash' => 'f0fdb4c3f58e3e3f8e77162d893d3055',
			'postHash' => '9bb58f26192e4ba00f01e2e7b136bbd8',
			'identifier' => 'aa43b23308dd6bdff9edb15deb2b3b41',
			'deleted' => false,
		];

		$entity = $this->formEntriesTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertEmpty($errors, 'Valid data should not produce validation errors');
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\FormEntriesTable::validationDefault()
	 */
	public function testEntityValidationMissingRequired(): void {
		$data = [
			'subject' => 'Test Subject',
		];

		$entity = $this->formEntriesTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('formId', $errors);
		$this->assertArrayHasKey('pageId', $errors);
		$this->assertArrayHasKey('ipHash', $errors);
		$this->assertArrayHasKey('postHash', $errors);
		$this->assertArrayHasKey('identifier', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\FormEntriesTable::validationDefault()
	 */
	public function testEntityValidationInvalidTypes(): void {
		$data = [
			'formId' => 'not_an_integer',
			'pageId' => 'not_an_integer',
			'subject' => true,
			'subjectConfirmation' => true,
			'body' => true,
			'bodyConfirmation' => true,
			'data' => true,
			'ipHash' => true,
			'postHash' => true,
			'identifier' => true,
			'deleted' => 'not_a_boolean',
		];

		$entity = $this->formEntriesTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('formId', $errors);
		$this->assertArrayHasKey('pageId', $errors);
		$this->assertArrayHasKey('subject', $errors);
		$this->assertArrayHasKey('subjectConfirmation', $errors);
		$this->assertArrayHasKey('body', $errors);
		$this->assertArrayHasKey('bodyConfirmation', $errors);
		$this->assertArrayHasKey('data', $errors);
		$this->assertArrayHasKey('ipHash', $errors);
		$this->assertArrayHasKey('postHash', $errors);
		$this->assertArrayHasKey('identifier', $errors);
		$this->assertArrayHasKey('deleted', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\FormEntriesTable::validationDefault()
	 */
	public function testEntityValidationFieldLength(): void {
		$data = [
			'formId' => 123456789123, // exceeds 11 char limit
			'pageId' => 123456789123, // exceeds 11 char limit
			'subject' => str_repeat('a', 256), // exceeds 255 char limit
			'subjectConfirmation' => str_repeat('b', 256), // exceeds 255 char limit
			'body' => str_repeat('c', 65536), // exceeds 65535 byte limit
			'bodyConfirmation' => str_repeat('d', 65536), // exceeds 65535 byte limit
			'data' => str_repeat('e', 65536), // exceeds 65535 byte limit
			'ipHash' => str_repeat('f', 41), // exceeds 40 char limit
			'postHash' => str_repeat('g', 41), // exceeds 40 char limit
			'identifier' => str_repeat('h', 41), // exceeds 40 char limit
		];

		$entity = $this->formEntriesTable->newEntity($data, ['guard' => false]);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('formId', $errors);
		$this->assertArrayHasKey('pageId', $errors);
		$this->assertArrayHasKey('subject', $errors);
		$this->assertArrayHasKey('subjectConfirmation', $errors);
		$this->assertArrayHasKey('body', $errors);
		$this->assertArrayHasKey('bodyConfirmation', $errors);
		$this->assertArrayHasKey('data', $errors);
		$this->assertArrayHasKey('ipHash', $errors);
		$this->assertArrayHasKey('postHash', $errors);
		$this->assertArrayHasKey('identifier', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\FormEntriesTable::validationDefault()
	 */
	public function testEntityValidationNotEmptyString(): void {
		$data = [
			'formId' => 1,
			'pageId' => 1,
			'ipHash' => '   ', // only whitespace
			'postHash' => '   ', // only whitespace
			'identifier' => '   ', // only whitespace
		];

		$entity = $this->formEntriesTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('ipHash', $errors);
		$this->assertArrayHasKey('postHash', $errors);
		$this->assertArrayHasKey('identifier', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\FormEntriesTable::validationDefault()
	 */
	public function testEntityValidationAllowEmptyStringFields(): void {
		$data = [
			'formId' => 1,
			'pageId' => null, // pageId allows empty
			'subject' => null, // subject allows empty
			'subjectConfirmation' => null, // subjectConfirmation allows empty
			'ipHash' => 'f0fdb4c3f58e3e3f8e77162d893d3055',
			'postHash' => '9bb58f26192e4ba00f01e2e7b136bbd8',
			'identifier' => 'aa43b23308dd6bdff9edb15deb2b3b41',
		];

		$entity = $this->formEntriesTable->newEntity($data);
		$errors = $entity->getErrors();

		$this->assertArrayNotHasKey('pageId', $errors);
		$this->assertArrayNotHasKey('subject', $errors);
		$this->assertArrayNotHasKey('subjectConfirmation', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\FormEntriesTable::buildRules()
	 */
	public function testBuildRulesValidForm(): void {
		// Test with existing form
		$data = [
			'formId' => 1,
			'pageId' => 1,
			'ipHash' => 'f0fdb4c3f58e3e3f8e77162d893d3055',
			'postHash' => '9bb58f26192e4ba00f01e2e7b136bbd8',
			'identifier' => 'aa43b23308dd6bdff9edb15deb2b3b41',
		];

		$entity = $this->formEntriesTable->newEntity($data);
		$result = $this->formEntriesTable->checkRules($entity);

		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\FormEntriesTable::buildRules()
	 */
	public function testBuildRulesInvalidForm(): void {
		// Test with non-existing form
		$data = [
			'formId' => 99999,
			'pageId' => 1,
			'ipHash' => 'f0fdb4c3f58e3e3f8e77162d893d3055',
			'postHash' => '9bb58f26192e4ba00f01e2e7b136bbd8',
			'identifier' => 'aa43b23308dd6bdff9edb15deb2b3b41',
		];

		$entity = $this->formEntriesTable->newEntity($data);
		$result = $this->formEntriesTable->checkRules($entity);

		$this->assertFalse($result);

		$errors = $entity->getErrors();
		$this->assertArrayHasKey('formId', $errors);
		$this->assertArrayHasKey('formExists', $errors['formId']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\FormEntriesTable::buildRules()
	 */
	public function testBuildRulesValidPage(): void {
		// Test with existing page
		$data = [
			'formId' => 1,
			'pageId' => 1,
			'ipHash' => 'f0fdb4c3f58e3e3f8e77162d893d3055',
			'postHash' => '9bb58f26192e4ba00f01e2e7b136bbd8',
			'identifier' => 'aa43b23308dd6bdff9edb15deb2b3b41',
		];

		$entity = $this->formEntriesTable->newEntity($data);
		$result = $this->formEntriesTable->checkRules($entity);

		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\FormEntriesTable::buildRules()
	 */
	public function testBuildRulesInvalidPage(): void {
		// Test with non-existing page
		$data = [
			'formId' => 1,
			'pageId' => 99999,
			'ipHash' => 'f0fdb4c3f58e3e3f8e77162d893d3055',
			'postHash' => '9bb58f26192e4ba00f01e2e7b136bbd8',
			'identifier' => 'aa43b23308dd6bdff9edb15deb2b3b41',
		];

		$entity = $this->formEntriesTable->newEntity($data);
		$result = $this->formEntriesTable->checkRules($entity);

		$this->assertFalse($result);

		$errors = $entity->getErrors();
		$this->assertArrayHasKey('pageId', $errors);
		$this->assertArrayHasKey('pageExists', $errors['pageId']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\FormEntriesTable::newDefaultEntity()
	 */
	public function testNewDefaultEntity(): void {
		$entity = $this->formEntriesTable->newDefaultEntity();

		$this->assertInstanceOf(FormEntry::class, $entity);
		$this->assertTrue($entity->isNew());

		// Check default values
		$this->assertNull($entity->formId);
		$this->assertNull($entity->pageId);
		$this->assertNull($entity->subject);
		$this->assertNull($entity->subjectConfirmation);
		$this->assertNull($entity->body);
		$this->assertNull($entity->bodyConfirmation);
		$this->assertNull($entity->data);
		$this->assertNull($entity->ipHash);
		$this->assertNull($entity->postHash);
		$this->assertNull($entity->identifier);
		$this->assertFalse($entity->deleted);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\FormEntriesTable::newDefaultEntity()
	 */
	public function testNewDefaultEntityWithData(): void {
		$additionalData = [
			'formId' => 1,
			'pageId' => 1,
			'subject' => 'Custom Subject',
			'subjectConfirmation' => 'Custom Confirmation',
			'body' => 'Custom body',
			'bodyConfirmation' => 'Custom confirmation body',
			'data' => 'Custom data',
			'ipHash' => 'f0fdb4c3f58e3e3f8e77162d893d3055',
			'postHash' => '9bb58f26192e4ba00f01e2e7b136bbd8',
			'identifier' => 'aa43b23308dd6bdff9edb15deb2b3b41',
			'deleted' => true,
		];

		$entity = $this->formEntriesTable->newDefaultEntity($additionalData);

		$this->assertInstanceOf(FormEntry::class, $entity);
		$this->assertTrue($entity->isNew());

		// Check custom values
		$this->assertSame(1, $entity->formId);
		$this->assertSame(1, $entity->pageId);
		$this->assertSame('Custom Subject', $entity->subject);
		$this->assertSame('Custom Confirmation', $entity->subjectConfirmation);
		$this->assertSame('Custom body', $entity->body);
		$this->assertSame('Custom confirmation body', $entity->bodyConfirmation);
		$this->assertSame('Custom data', $entity->data);
		$this->assertSame('f0fdb4c3f58e3e3f8e77162d893d3055', $entity->ipHash);
		$this->assertSame('9bb58f26192e4ba00f01e2e7b136bbd8', $entity->postHash);
		$this->assertSame('aa43b23308dd6bdff9edb15deb2b3b41', $entity->identifier);
		$this->assertTrue($entity->deleted);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\FormEntriesTable::$categories
	 */
	public function testCategoriesBehavior(): void {
		$this->assertTrue($this->formEntriesTable->hasBehavior('Categories'));

		$config = $this->formEntriesTable->getBehavior('Categories')->getConfig();

		$this->assertTrue($config['allowAggregation']);
		$this->assertFalse($config['allowUnassigned']);
		$this->assertTrue($config['enabled']);
		$this->assertEquals('Forms', $config['associationName']);
		$this->assertEquals('form', $config['identifier']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\FormEntriesTable::$search
	 */
	public function testSearchBehavior(): void {
		$this->assertTrue($this->formEntriesTable->hasBehavior('Search'));

		$config = $this->formEntriesTable->getBehavior('Search')->getConfig();

		$this->assertArrayHasKey('blocklistedColumns', $config);
		$this->assertSame(['formId', 'pageId'], $config['blocklistedColumns']);
	}
}
