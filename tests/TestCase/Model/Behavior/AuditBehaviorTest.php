<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Model\Behavior;


use ArrayObject;
use Awyiss\Authorization\AuthorizationService;
use Awyiss\Awyiss;
use Awyiss\Middleware\LocaleMiddleware;
use Awyiss\Model\Behavior\AuditBehavior;
use Awyiss\Model\Entity\Audit;
use Awyiss\Model\Entity\ContentTemplateElement;
use Awyiss\Model\Entity\User;
use Awyiss\Model\Entity\Usergroup;
use Awyiss\Model\Table;
use Awyiss\Routing\Router;
use Awyiss\Test\TestSuite\TestCase;
use Cake\Event\Event;
use Cake\Http\ServerRequest;
use Cake\I18n\DateTime;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\Validation\ValidationRule;
use Cake\Validation\Validator;
use Customer\Model\Entity\AttributesNews;
use Customer\Model\Entity\Employer;


/**
 * AuditBehavior Test Case
 *
 * @see \Awyiss\Model\Behavior\AuditBehavior
 */
class AuditBehaviorTest extends TestCase {
	use IntegrationTestTrait;


	/**
	 * @var \Awyiss\Model\Table\PagesTable
	 */
	protected Table $table;
	/**
	 * @var \Awyiss\Model\Behavior\AuditBehavior
	 */
	protected AuditBehavior $behavior;
	/**
	 * @var \Awyiss\Model\Table\AuditTable
	 */
	protected Table $auditTable;


	/**
	 * @inheritDoc
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->configApplication(Awyiss::class, []);

		Awyiss::loadConfiguration('de', 'de');
		Awyiss::setRealm(Awyiss::REALM_BACKEND);
		LocaleMiddleware::setRealm(Awyiss::REALM_BACKEND);

		TableRegistry::getTableLocator()->clear();

		/** @noinspection PhpFieldAssignmentTypeMismatchInspection */
		$this->table = TableRegistry::getTableLocator()->get('Pages');

		/** @noinspection PhpFieldAssignmentTypeMismatchInspection */
		$this->behavior = $this->table->getBehavior('Audit');

		$this->auditTable = TableRegistry::getTableLocator()->get('Audit');
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\AuditBehavior::initialize()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testInitialization(): void {
		$config = $this->behavior->getConfig();

		$this->assertTrue($config['enabled']);

		$this->assertSame([
			'buildValidator',
			'beforeCopy',
			'beforeSave',
			'beforeDelete',
			'afterSave',
			'afterDelete',
		], $config['implementedEvents']);

		$this->assertSame([
			'countAuditData' => 'countAuditData',
			'getAuditData' => 'getAuditData',
			'getAuditHistoryFields' => 'getHistoryFields',
		], $config['implementedMethods']);

		$this->assertTrue($config['setTimeOnCreate']);
		$this->assertTrue($config['setTimeOnUpdate']);
		$this->assertTrue($config['setTimeOnDelete']);
		$this->assertFalse($config['skip']);

		$this->assertSame([
			'createdOn',
			'createdBy',
			'changedOn',
			'changedBy',
			'deletedOn',
			'deletedBy',
			'publicationStart',
			'publicationEnd',
			'_i18n',
			'_locale',
			'_joinData',
		], $config['ignoredFields']);

		$this->assertSame([
			'pageRoleId',
			'pageTemplateId',
			'parentId',
			'languageShortcode',
			'slug',
			'title',
			'redirectLink',
			'metaTitle',
			'metaDescription',
			'robotsIndex',
			'robotsFollow',
			'duplicateOf',
			'formId',
			'surveyId',
			'systemOrder',
			'active',
			'parentsActive',
			'deleted',
		], $config['historyFields']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\AuditBehavior::initialize()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testInitializeAddsFieldMapping(): void {
		/** @var \Awyiss\Model\Entity\Page $entityClass */
		$entityClass = $this->table->getEntityClass();

		$this->assertSame('createdOn', $entityClass::mapField('created_on'));
		$this->assertSame('createdBy', $entityClass::mapField('created_by'));
		$this->assertSame('changedOn', $entityClass::mapField('changed_on'));
		$this->assertSame('changedBy', $entityClass::mapField('changed_by'));
		$this->assertSame('deletedOn', $entityClass::mapField('deleted_on'));
		$this->assertSame('deletedBy', $entityClass::mapField('deleted_by'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\AuditBehavior::initialize()
	 * @see \Awyiss\Model\Behavior\AuditBehavior::addAssociation()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testInitializeAddsUserAssociations(): void {
		$this->assertTrue($this->table->hasAssociation('CreatedByUser'));
		$this->assertTrue($this->table->hasAssociation('ChangedByUser'));
		$this->assertTrue($this->table->hasAssociation('DeletedByUser'));

		$createdBy = $this->table->getAssociation('CreatedByUser');
		$this->assertSame('users', $createdBy->getTarget()->getTable());

		$changedBy = $this->table->getAssociation('ChangedByUser');
		$this->assertSame('users', $changedBy->getTarget()->getTable());

		$deletedBy = $this->table->getAssociation('DeletedByUser');
		$this->assertSame('users', $deletedBy->getTarget()->getTable());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\AuditBehavior::initialize()
	 * @see \Awyiss\Model\Behavior\AuditBehavior::implementedEvents()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testImplementedEvents(): void {
		$implementedEvents = $this->behavior->implementedEvents();

		$this->assertSame([
			'Model.buildValidator' => [
				'callable' => 'buildValidator',
				'priority' => 999999,
			],
			'Model.beforeCopy' => [
				'callable' => 'beforeCopy',
				'priority' => 999999,
			],
			'Model.beforeSave' => [
				'callable' => 'beforeSave',
				'priority' => 999999,
			],
			'Model.beforeDelete' => [
				'callable' => 'beforeDelete',
				'priority' => 999999,
			],
			'Model.afterSave' => [
				'callable' => 'afterSave',
				'priority' => 999999,
			],
			'Model.afterDelete' => [
				'callable' => 'afterDelete',
				'priority' => 999999,
			],
		], $implementedEvents);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\AuditBehavior::implementedMethods()
	 * @throws \ReflectionException
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testImplementedMethods(): void {
		$implementedMethods = $this->behavior->implementedMethods();

		$this->assertSame([
			'countAuditData' => 'countAuditData',
			'getAuditData' => 'getAuditData',
			'getAuditHistoryFields' => 'getHistoryFields',
		], $implementedMethods);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\AuditBehavior::findWithAuditUsers()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testFindWithAuditUsers(): void {
		/** @noinspection PhpFieldAssignmentTypeMismatchInspection */
		$this->table = $this->fetchTable('Employers');
		$this->behavior = $this->table->getBehavior('Audit');

		$entity = $this->table->newDefaultEntity([
			'title' => 'Test Employer',
			'languageShortcode' => 'de',
			'createdBy' => 1,
			'changedBy' => 2,
			'deletedBy' => 3,
		]);

		$result = $this->table->save($entity, ['audit' => ['skip' => true]]);

		$this->assertNotFalse($result);

		$query = $this->table->find()->where(['id' => $entity->id]);

		$modifiedQuery = $this->behavior->findWithAuditUsers($query);

		$this->assertSame($query, $modifiedQuery);

		$entity = $query->first();

		$this->assertTrue($entity->has('createdByUser'));
		$this->assertSame('awyiss', $entity->get('createdByUser'));

		$this->assertTrue($entity->has('changedByUser'));
		$this->assertSame('awyiss-undecided-access', $entity->get('changedByUser'));

		$this->assertTrue($entity->has('deletedByUser'));
		$this->assertSame('awyiss-no-access', $entity->get('deletedByUser'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\AuditBehavior::countAuditData()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testCountAuditData(): void {
		$this->login();

		/** @noinspection PhpFieldAssignmentTypeMismatchInspection */
		$this->table = $this->fetchTable('Employers');
		$this->behavior = $this->table->getBehavior('Audit');

		$entity = $this->table->newDefaultEntity([
			'title' => 'Test Employer',
			'languageShortcode' => 'de',
		]);

		$result = $this->table->save($entity);

		$this->assertNotFalse($result);

		$entity->title = 'Updated Employer';
		$result = $this->table->save($entity);
		$this->assertNotFalse($result);

		$entity->title = 'Another Updated Employer';
		$result = $this->table->save($entity);
		$this->assertNotFalse($result);

		$this->assertSame(2, $this->behavior->countAuditData($entity));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\AuditBehavior::getAuditData()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetAuditData(): void {
		$this->login();

		/** @noinspection PhpFieldAssignmentTypeMismatchInspection */
		$this->table = $this->fetchTable('Employers');
		$this->behavior = $this->table->getBehavior('Audit');

		$entity = $this->table->newDefaultEntity([
			'title' => 'Test Employer',
			'languageShortcode' => 'de',
		]);

		$result = $this->table->save($entity);

		$this->assertNotFalse($result);

		$entity->title = 'Updated Employer';
		$result = $this->table->save($entity);
		$this->assertNotFalse($result);

		$entity->title = 'Another Updated Employer';
		$result = $this->table->save($entity);
		$this->assertNotFalse($result);

		$auditData = $this->behavior->getAuditData($entity);

		$this->assertIsArray($auditData);
		$this->assertCount(2, $auditData);

		$this->assertInstanceOf(Audit::class, $auditData[0]);
		$this->assertInstanceOf(Audit::class, $auditData[1]);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\AuditBehavior::getHistoryFields()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetHistoryFields(): void {
		$historyFields = $this->behavior->getHistoryFields();

		$this->assertSame([
			'pageRoleId',
			'pageTemplateId',
			'parentId',
			'languageShortcode',
			'slug',
			'title',
			'redirectLink',
			'metaTitle',
			'metaDescription',
			'robotsIndex',
			'robotsFollow',
			'duplicateOf',
			'formId',
			'surveyId',
			'systemOrder',
			'active',
			'parentsActive',
			'deleted',
		], $historyFields);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\AuditBehavior::buildValidator()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBuildValidator(): void {
		$validator = new Validator();
		$event = new Event('Model.buildValidator', $this->table, ['validator' => $validator]);

		$this->behavior->buildValidator($event, $validator, 'default');

		$this->assertTrue($validator->hasField('createdOn'));
		$this->assertInstanceOf(ValidationRule::class, $validator->field('createdOn')->rule('dateTime'));

		$this->assertTrue($validator->hasField('createdBy'));
		$this->assertInstanceOf(ValidationRule::class, $validator->field('createdBy')->rule('isInteger'));

		$this->assertTrue($validator->hasField('changedOn'));
		$this->assertInstanceOf(ValidationRule::class, $validator->field('changedOn')->rule('dateTime'));

		$this->assertTrue($validator->hasField('changedBy'));
		$this->assertInstanceOf(ValidationRule::class, $validator->field('changedBy')->rule('isInteger'));

		$this->assertTrue($validator->hasField('deletedOn'));
		$this->assertInstanceOf(ValidationRule::class, $validator->field('deletedOn')->rule('dateTime'));

		$this->assertTrue($validator->hasField('deletedBy'));
		$this->assertInstanceOf(ValidationRule::class, $validator->field('deletedBy')->rule('isInteger'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\AuditBehavior::beforeDelete()
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBeforeDeleteSetsTransactionId(): void {
		$entity = $this->table->newDefaultEntity(['title' => 'Test Employer']);
		$options = new ArrayObject();
		$event = new Event('Model.beforeDelete', $this->table, ['entity' => $entity, 'options' => $options]);

		$this->behavior->beforeDelete($event, $entity, $options);

		$this->assertArrayHasKey('transactionId', $options);
		$this->assertIsString($options['transactionId']);
		$this->assertMatchesRegularExpression('/^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$/', $options['transactionId']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\AuditBehavior::beforeCopy()
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBeforeCopyUnsetsCreatedAndChanged(): void {
		$entity = $this->table->newDefaultEntity([
			'title' => 'Test Employer',
			'languageShortcode' => 'de',
			'createdOn' => new DateTime(),
			'createdBy' => 1,
			'changedOn' => new DateTime(),
			'changedBy' => 1,
		]);

		$this->assertTrue($entity->has('createdOn'));
		$this->assertTrue($entity->has('createdBy'));
		$this->assertTrue($entity->has('changedOn'));
		$this->assertTrue($entity->has('changedBy'));

		$options = new ArrayObject();
		$event = new Event('Model.beforeCopy', $this->table, ['entity' => $entity, 'options' => $options]);

		$this->behavior->beforeCopy($event, $entity, $options);

		$this->assertFalse($entity->has('createdOn'));
		$this->assertFalse($entity->has('createdBy'));
		$this->assertFalse($entity->has('changedOn'));
		$this->assertFalse($entity->has('changedBy'));
		$this->assertTrue($entity->has('title'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\AuditBehavior::beforeSave()
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBeforeSaveSetsTransactionId(): void {
		$entity = $this->table->newDefaultEntity(['title' => 'Test Employer']);
		$options = new ArrayObject();
		$event = new Event('Model.beforeSave', $this->table, ['entity' => $entity, 'options' => $options]);

		$this->behavior->beforeSave($event, $entity, $options);

		$this->assertArrayHasKey('transactionId', $options);
		$this->assertIsString($options['transactionId']);
		$this->assertMatchesRegularExpression('/^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$/', $options['transactionId']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\AuditBehavior::beforeSave()
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBeforeSaveWithNewEntity(): void {
		$this->login(2);

		$entity = $this->table->newDefaultEntity([
			'title' => 'Test Employer',
			'languageShortcode' => 'de',
		]);

		$options = new ArrayObject();
		$event = new Event('Model.beforeSave', $this->table, ['entity' => $entity, 'options' => $options]);

		$this->behavior->beforeSave($event, $entity, $options);

		$this->assertInstanceOf(DateTime::class, $entity->createdOn);
		$this->assertSame(2, $entity->createdBy);

		$this->assertNull($entity->changedOn);
		$this->assertNull($entity->changedBy);
		$this->assertNull($entity->deletedOn);
		$this->assertNull($entity->deletedBy);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\AuditBehavior::beforeSave()
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBeforeSaveWithExistingEntity(): void {
		$this->login(3);

		$entity = $this->table->newDefaultEntity([
			'title' => 'Test Employer',
			'languageShortcode' => 'de',
		]);
		$entity->id = 123;
		$entity->setNew(false);
		$entity->unset('attributes');

		$options = new ArrayObject();
		$event = new Event('Model.beforeSave', $this->table, ['entity' => $entity, 'options' => $options]);

		$this->behavior->beforeSave($event, $entity, $options);

		$this->assertNull($entity->createdOn);
		$this->assertNull($entity->createdBy);

		$this->assertInstanceOf(DateTime::class, $entity->get('changedOn'));
		$this->assertSame(3, $entity->changedBy);

		$this->assertNull($entity->deletedOn);
		$this->assertNull($entity->deletedBy);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\AuditBehavior::beforeSave()
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBeforeSaveWithSoftDelete(): void {
		$entity = $this->table->newDefaultEntity([
			'title' => 'Test Employer',
			'languageShortcode' => 'de',
		]);
		$entity->id = 123;
		$entity->set('deleted', true);
		$entity->setNew(false);
		$entity->unset('attributes');

		$options = new ArrayObject();
		$event = new Event('Model.beforeSave', $this->table, ['entity' => $entity, 'options' => $options]);

		$this->behavior->beforeSave($event, $entity, $options);

		$this->assertNull($entity->createdOn);
		$this->assertNull($entity->createdBy);

		$this->assertNull($entity->changedOn);
		$this->assertNull($entity->changedBy);

		$this->assertTrue($entity->has('deletedOn'));
		$this->assertInstanceOf(DateTime::class, $entity->get('deletedOn'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\AuditBehavior::beforeSave()
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBeforeSaveWithSkipOption(): void {
		$entity = $this->table->newDefaultEntity([
			'title' => 'Test Employer',
			'languageShortcode' => 'de',
		]);

		$options = new ArrayObject(['audit' => ['skip' => true]]);
		$event = new Event('Model.beforeSave', $this->table, ['entity' => $entity, 'options' => $options]);

		$this->behavior->beforeSave($event, $entity, $options);

		$this->assertNull($entity->createdOn);
		$this->assertNull($entity->createdBy);

		$this->assertNull($entity->changedOn);
		$this->assertNull($entity->changedBy);

		$this->assertNull($entity->deletedOn);
		$this->assertNull($entity->deletedBy);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\AuditBehavior::beforeSave()
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testBeforeSaveWithDisabledTimeSettings(): void {
		$this->behavior->setConfig('setTimeOnCreate', false);

		$entity = $this->table->newDefaultEntity([
			'title' => 'Test Employer',
			'languageShortcode' => 'de',
		]);

		$options = new ArrayObject();
		$event = new Event('Model.beforeSave', $this->table, ['entity' => $entity, 'options' => $options]);

		$this->behavior->beforeSave($event, $entity, $options);

		$this->assertNull($entity->createdOn);
		$this->assertNull($entity->createdBy);
		$this->assertNull($entity->changedOn);

		$this->behavior->setConfig('setTimeOnUpdate', false);

		$entity->id = 123;
		$entity->setNew(false);
		$entity->unset('attributes');

		$this->behavior->beforeSave($event, $entity, $options);

		$this->assertNull($entity->createdOn);
		$this->assertNull($entity->createdBy);
		$this->assertNull($entity->changedOn);

		$this->behavior->setConfig('setTimeOnDelete', false);

		$entity->deleted = true;

		$this->behavior->beforeSave($event, $entity, $options);

		$this->assertNull($entity->createdOn);
		$this->assertNull($entity->createdBy);
		$this->assertNull($entity->changedOn);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\AuditBehavior::afterSave()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAfterSaveWithNewEntity(): void {
		$this->login(3);

		$this->auditTable->deleteAll([]);

		$entity = $this->table->newDefaultEntity([
			'title' => 'Test Employer',
			'languageShortcode' => 'de',
		]);

		$options = new ArrayObject(['transactionId' => 'test-transaction-id']);
		$event = new Event('Model.afterSave', $this->table, ['entity' => $entity, 'options' => $options]);

		$this->behavior->afterSave($event, $entity, $options);

		$auditCount = $this->auditTable->find()->count();
		$this->assertSame(0, $auditCount);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\AuditBehavior::afterSave()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAfterSaveWithDisabledBehavior(): void {
		$this->login(3);

		$this->behavior->setConfig('enabled', false);

		$this->auditTable->deleteAll([]);

		$entity = $this->table->newDefaultEntity([
			'title' => 'Test Employer',
			'languageShortcode' => 'de',
		]);
		$entity->clean();
		$entity->setNew(false);
		$entity->unset('attributes');

		$entity->title = 'Updated Employer';

		$options = new ArrayObject(['transactionId' => 'test-transaction-id']);
		$event = new Event('Model.afterSave', $this->table, ['entity' => $entity, 'options' => $options]);

		$this->behavior->afterSave($event, $entity, $options);

		$auditCount = $this->auditTable->find()->count();
		$this->assertSame(0, $auditCount);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\AuditBehavior::afterSave()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAfterSaveWithSkipOption(): void {
		$this->login(3);

		$this->auditTable->deleteAll([]);

		$entity = $this->table->newDefaultEntity([
			'title' => 'Test Employer',
			'languageShortcode' => 'de',
		]);
		$entity->clean();
		$entity->setNew(false);
		$entity->unset('attributes');

		$entity->title = 'Updated Employer';

		$options = new ArrayObject(['transactionId' => 'test-transaction-id', 'audit' => ['skip' => true]]);
		$event = new Event('Model.afterSave', $this->table, ['entity' => $entity, 'options' => $options]);

		$this->behavior->afterSave($event, $entity, $options);

		$auditCount = $this->auditTable->find()->count();
		$this->assertSame(0, $auditCount);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\AuditBehavior::afterSave()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAfterSaveWithoutAuditData(): void {
		$this->login(3);

		$this->auditTable->deleteAll([]);

		$entity = $this->table->newDefaultEntity([
			'title' => 'Test Employer',
			'languageShortcode' => 'de',
		]);
		$entity->clean();
		$entity->setNew(false);
		$entity->unset('attributes');

		$entity->title = 'Updated Employer';

		$options = new ArrayObject(['transactionId' => 'test-transaction-id']);
		$event = new Event('Model.afterSave', $this->table, ['entity' => $entity, 'options' => $options]);

		$this->behavior->afterSave($event, $entity, $options);

		$auditCount = $this->auditTable->find()->count();
		$this->assertSame(0, $auditCount);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\AuditBehavior::afterSave()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAfterSaveWithoutBeforeSave(): void {
		$this->login(3);

		$this->auditTable->deleteAll([]);

		$entity = $this->table->newDefaultEntity([
			'title' => 'Test Employer',
			'languageShortcode' => 'de',
		]);
		$entity->clean();
		$entity->setNew(false);
		$entity->unset('attributes');

		$entity->title = 'Updated Employer';

		$options = new ArrayObject(['transactionId' => 'test-transaction-id']);
		$event = new Event('Model.afterSave', $this->table, ['entity' => $entity, 'options' => $options]);

		$this->behavior->afterSave($event, $entity, $options);

		$auditCount = $this->auditTable->find()->count();
		$this->assertSame(0, $auditCount);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\AuditBehavior::afterSave()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAfterSaveWithChangedData(): void {
		$this->login(3);

		$this->auditTable->deleteAll([]);

		$entity = $this->table->newDefaultEntity([
			'title' => 'Test Employer',
			'languageShortcode' => 'de',
		]);
		$entity->clean();
		$entity->setNew(false);
		$entity->unset('attributes');

		$entity->title = 'Updated Employer';
		$this->assertTrue($entity->isDirty('title'));

		$options = new ArrayObject(['transactionId' => 'test-transaction-id']);
		$event = new Event('Model.afterSave', $this->table, ['entity' => $entity, 'options' => $options]);

		$this->behavior->afterSave($event, $entity, $options);

		$auditCount = $this->auditTable->find()->count();
		$this->assertSame(0, $auditCount);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\AuditBehavior::afterSave()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAfterSaveWithPatchedChangedData(): void {
		$this->login(3);

		$this->auditTable->deleteAll([]);

		$entity = $this->table->newDefaultEntity([
			'title' => 'Test Employer',
			'languageShortcode' => 'de',
		]);
		$entity->clean();
		$entity->setNew(false);
		$entity->unset('attributes');

		$this->table->patchEntity($entity, [
			'title' => 'Updated Employer',
		]);
		$this->assertTrue($entity->isDirty('title'));

		$options = new ArrayObject(['transactionId' => 'test-transaction-id']);
		$event = new Event('Model.afterSave', $this->table, ['entity' => $entity, 'options' => $options]);

		$this->behavior->afterSave($event, $entity, $options);

		$auditCount = $this->auditTable->find()->count();
		$this->assertSame(0, $auditCount);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\AuditBehavior::afterSave()
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAfterSaveWithUnchangedData(): void {
		$this->login(3);

		$this->auditTable->deleteAll([]);

		$entity = $this->table->newDefaultEntity([
			'title' => 'Test Employer',
			'languageShortcode' => 'de',
		]);
		$entity->clean();
		$entity->setNew(false);
		$entity->unset('attributes');

		$entity->title = 'Test Employer';
		$this->assertFalse($entity->isDirty('title'));

		$options = new ArrayObject(['transactionId' => 'test-transaction-id']);
		$event = new Event('Model.beforeSave', $this->table, ['entity' => $entity, 'options' => $options]);

		$this->behavior->beforeSave($event, $entity, $options);

		$options = new ArrayObject(['transactionId' => 'test-transaction-id']);
		$event = new Event('Model.afterSave', $this->table, ['entity' => $entity, 'options' => $options]);

		$this->behavior->afterSave($event, $entity, $options);

		$auditCount = $this->auditTable->find()->count();
		$this->assertSame(0, $auditCount);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\AuditBehavior::afterSave()
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAfterSaveWithPatchedUnchangedData(): void {
		$this->login(3);

		$this->auditTable->deleteAll([]);

		$entity = $this->table->newDefaultEntity([
			'title' => 'Test Employer',
			'languageShortcode' => 'de',
		]);
		$entity->clean();
		$entity->setNew(false);
		$entity->unset('attributes');

		$this->table->patchEntity($entity, [
			'title' => 'Test Employer',
		]);
		$this->assertFalse($entity->isDirty('title'));

		$options = new ArrayObject(['transactionId' => 'test-transaction-id']);
		$event = new Event('Model.beforeSave', $this->table, ['entity' => $entity, 'options' => $options]);

		$this->behavior->beforeSave($event, $entity, $options);

		$options = new ArrayObject(['transactionId' => 'test-transaction-id']);
		$event = new Event('Model.afterSave', $this->table, ['entity' => $entity, 'options' => $options]);

		$this->behavior->afterSave($event, $entity, $options);

		$auditCount = $this->auditTable->find()->count();
		$this->assertSame(0, $auditCount);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\AuditBehavior::afterSave()
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAfterSaveWithDisabledAudit(): void {
		$this->login(3);

		$this->auditTable->deleteAll([]);

		$entity = new class extends Employer {
			/**
			 * @inheritDoc
			 */
			protected bool $_audit = false;
		};
		$entity->title = 'Test Employer';
		$entity->languageShortcode = 'de';
		$entity->clean();
		$entity->setNew(false);

		$entity->title = 'Updated Employer';

		$options = new ArrayObject(['transactionId' => 'test-transaction-id']);
		$event = new Event('Model.beforeSave', $this->table, ['entity' => $entity, 'options' => $options]);

		$this->behavior->beforeSave($event, $entity, $options);

		$options = new ArrayObject(['transactionId' => 'test-transaction-id']);
		$event = new Event('Model.afterSave', $this->table, ['entity' => $entity, 'options' => $options]);

		$this->behavior->afterSave($event, $entity, $options);

		$auditCount = $this->auditTable->find()->count();
		$this->assertSame(0, $auditCount);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\AuditBehavior::afterSave()
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAfterSaveWithSimpleChanges(): void {
		$this->login(3);

		$this->auditTable->deleteAll([]);

		$entity = $this->table->newDefaultEntity([
			'title' => 'Test Employer',
			'languageShortcode' => 'de',
		]);
		$entity->id = 123;
		$entity->clean();
		$entity->setNew(false);
		$entity->unset('attributes');

		$entity->title = 'Updated Employer';

		$options = new ArrayObject(['transactionId' => 'test-transaction-id']);
		$event = new Event('Model.beforeSave', $this->table, ['entity' => $entity, 'options' => $options]);

		$this->behavior->beforeSave($event, $entity, $options);

		$options = new ArrayObject(['transactionId' => 'test-transaction-id']);
		$event = new Event('Model.afterSave', $this->table, ['entity' => $entity, 'options' => $options]);

		$this->behavior->afterSave($event, $entity, $options);

		$result = $this->auditTable->find()->all();
		$this->assertCount(1, $result);

		$audit = $result->first();

		$this->assertInstanceOf(Audit::class, $audit);
		$this->assertSame('test-transaction-id', $audit->transactionId);
		$this->assertSame('u', $audit->type);
		$this->assertSame(123, $audit->foreignKey);
		$this->assertSame('pages', $audit->scope);
		$this->assertTrue($audit->createdOn->wasWithinLast('5 seconds'));
		$this->assertSame(3, $audit->createdBy);

		$this->assertSame([
			'active' => true,
			'deleted' => false,
			'duplicateOf' => null,
			'formId' => null,
			'id' => 123,
			'languageShortcode' => 'de',
			'metaDescription' => null,
			'metaTitle' => null,
			'pageRoleId' => 1,
			'pageTemplateId' => null,
			'parentId' => null,
			'parentsActive' => true,
			'redirectLink' => null,
			'robotsFollow' => true,
			'robotsIndex' => true,
			'slug' => null,
			'surveyId' => null,
			'systemOrder' => 0,
			'title' => 'Test Employer',
		], json_decode(gzuncompress(base64_decode($audit->dataOld)), true));

		$this->assertSame([
			'active' => true,
			'deleted' => false,
			'duplicateOf' => null,
			'formId' => null,
			'id' => 123,
			'languageShortcode' => 'de',
			'metaDescription' => null,
			'metaTitle' => null,
			'pageRoleId' => 1,
			'pageTemplateId' => null,
			'parentId' => null,
			'parentsActive' => true,
			'redirectLink' => null,
			'robotsFollow' => true,
			'robotsIndex' => true,
			'slug' => null,
			'surveyId' => null,
			'systemOrder' => 0,
			'title' => 'Updated Employer',
		], json_decode(gzuncompress(base64_decode($audit->dataNew)), true));

		$this->assertSame([
			'old' => [
				'title' => 'Test Employer',
			],
			'new' => [
				'title' => 'Updated Employer',
			],
		], $audit->diff);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\AuditBehavior::afterSave()
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAfterSaveWithIgnoredFields(): void {
		$this->login(3);

		$this->behavior->setConfig('ignoredFields', ['title']);

		$this->auditTable->deleteAll([]);

		$entity = $this->table->newDefaultEntity([
			'title' => 'Test Employer',
			'languageShortcode' => 'de',
		]);
		$entity->id = 123;
		$entity->clean();
		$entity->setNew(false);
		$entity->unset('attributes');

		$entity->title = 'Updated Employer';

		$options = new ArrayObject(['transactionId' => 'test-transaction-id']);
		$event = new Event('Model.beforeSave', $this->table, ['entity' => $entity, 'options' => $options]);

		$this->behavior->beforeSave($event, $entity, $options);

		$options = new ArrayObject(['transactionId' => 'test-transaction-id']);
		$event = new Event('Model.afterSave', $this->table, ['entity' => $entity, 'options' => $options]);

		$this->behavior->afterSave($event, $entity, $options);

		$result = $this->auditTable->find()->all();
		$this->assertCount(0, $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\AuditBehavior::afterSave()
	 * @see \Awyiss\Model\Behavior\AuditBehavior::auditPublicationData()
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAfterSaveWithPublicationDataAdded(): void {
		$this->login(3);

		$this->auditTable->deleteAll([]);

		$publicationDataTable = $this->fetchTable('PublicationData');

		$entity = $this->table->newDefaultEntity([
			'title' => 'Test Employer',
			'languageShortcode' => 'de',
			'_publicationData' => [
				'start' => ['dateTime' => null],
				'end' => ['dateTime' => null],
			],
		]);
		$entity->id = 123;
		$entity->clean();
		$entity->setNew(false);
		$entity->unset('attributes');

		$newStartData = $publicationDataTable->newDefaultEntity([
			'scope' => 'pages',
			'foreignKey' => 123,
			'type' => 'start',
			'dateTime' => new DateTime('2023-01-01 10:00:00'),
		]);

		$newEndData = $publicationDataTable->newDefaultEntity([
			'scope' => 'pages',
			'foreignKey' => 123,
			'type' => 'end',
			'dateTime' => new DateTime('2023-12-31 23:59:59'),
		]);

		$entity->_publicationData = [$newStartData, $newEndData];

		$options = new ArrayObject(['transactionId' => 'test-transaction-id']);
		$event = new Event('Model.beforeSave', $this->table, ['entity' => $entity, 'options' => $options]);

		$this->behavior->beforeSave($event, $entity, $options);

		$options = new ArrayObject(['transactionId' => 'test-transaction-id']);
		$event = new Event('Model.afterSave', $this->table, ['entity' => $entity, 'options' => $options]);

		$this->behavior->afterSave($event, $entity, $options);

		$result = $this->auditTable->find()->all();
		$this->assertCount(1, $result);

		$audit = $result->first();

		$this->assertSame([
			'old' => [
				'_publicationData' => [
					'start' => ['dateTime' => null],
					'end' => ['dateTime' => null],
				],
			],
			'new' => [
				'_publicationData' => [
					'start' => ['dateTime' => '2023-01-01 10:00:00'],
					'end' => ['dateTime' => '2023-12-31 23:59:59'],
				],
			],
		], $audit->diff);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\AuditBehavior::afterSave()
	 * @see \Awyiss\Model\Behavior\AuditBehavior::auditPublicationData()
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAfterSaveWithPublicationDataChanged(): void {
		$this->login(3);

		$this->auditTable->deleteAll([]);

		$publicationDataTable = $this->fetchTable('PublicationData');

		$entity = $this->table->newDefaultEntity([
			'title' => 'Test Employer',
			'languageShortcode' => 'de',
			'_publicationData' => [
				'start' => ['dateTime' => '2023-01-01 10:00:00'],
				'end' => ['dateTime' => '2023-12-31 23:59:59'],
			],
		]);
		$entity->id = 123;
		$entity->clean();
		$entity->setNew(false);
		$entity->unset('attributes');

		$newStartData = $publicationDataTable->newDefaultEntity([
			'scope' => 'pages',
			'foreignKey' => 123,
			'type' => 'start',
			'dateTime' => new DateTime('2023-02-01 09:00:00'),
		]);

		$newEndData = $publicationDataTable->newDefaultEntity([
			'scope' => 'pages',
			'foreignKey' => 123,
			'type' => 'end',
			'dateTime' => new DateTime('2023-11-30 22:00:00'),
		]);

		$entity->_publicationData = [$newStartData, $newEndData];

		$options = new ArrayObject(['transactionId' => 'test-transaction-id']);

		$event = new Event('Model.beforeSave', $this->table, ['entity' => $entity, 'options' => $options]);

		$this->behavior->beforeSave($event, $entity, $options);

		$options = new ArrayObject(['transactionId' => 'test-transaction-id']);
		$event = new Event('Model.afterSave', $this->table, ['entity' => $entity, 'options' => $options]);

		$this->behavior->afterSave($event, $entity, $options);

		$result = $this->auditTable->find()->all();
		$this->assertCount(1, $result);

		$audit = $result->first();

		$this->assertSame([
			'old' => [
				'_publicationData' => [
					'start' => ['dateTime' => '2023-01-01 10:00:00'],
					'end' => ['dateTime' => '2023-12-31 23:59:59'],
				],
			],
			'new' => [
				'_publicationData' => [
					'start' => ['dateTime' => '2023-02-01 09:00:00'],
					'end' => ['dateTime' => '2023-11-30 22:00:00'],
				],
			],
		], $audit->diff);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\AuditBehavior::afterSave()
	 * @see \Awyiss\Model\Behavior\AuditBehavior::auditPublicationData()
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAfterSaveWithPublicationDataUnchanged(): void {
		$this->login(3);

		$this->auditTable->deleteAll([]);

		$entity = $this->table->newDefaultEntity([
			'title' => 'Test Employer',
			'languageShortcode' => 'de',
			'_publicationData' => [
				'start' => ['dateTime' => '2023-01-01 10:00:00'],
				'end' => ['dateTime' => '2023-12-31 23:59:59'],
			],
		]);
		$entity->id = 123;
		$entity->clean();
		$entity->setNew(false);
		$entity->unset('attributes');

		$options = new ArrayObject(['transactionId' => 'test-transaction-id']);
		$event = new Event('Model.beforeSave', $this->table, ['entity' => $entity, 'options' => $options]);

		$this->behavior->beforeSave($event, $entity, $options);

		$options = new ArrayObject(['transactionId' => 'test-transaction-id']);
		$event = new Event('Model.afterSave', $this->table, ['entity' => $entity, 'options' => $options]);

		$this->behavior->afterSave($event, $entity, $options);

		$auditCount = $this->auditTable->find()->count();
		$this->assertSame(0, $auditCount);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\AuditBehavior::afterSave()
	 * @see \Awyiss\Model\Behavior\AuditBehavior::auditPublicationData()
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAfterSaveWithPublicationDataRemoved(): void {
		$this->login(3);

		$this->auditTable->deleteAll([]);

		$entity = $this->table->newDefaultEntity([
			'title' => 'Test Employer',
			'languageShortcode' => 'de',
			'_publicationData' => [
				'start' => ['dateTime' => '2023-01-01 10:00:00'],
				'end' => ['dateTime' => '2023-12-31 23:59:59'],
			],
		]);
		$entity->id = 123;
		$entity->clean();
		$entity->setNew(false);
		$entity->unset('attributes');

		$entity->_publicationData = null;

		$options = new ArrayObject(['transactionId' => 'test-transaction-id']);
		$event = new Event('Model.beforeSave', $this->table, ['entity' => $entity, 'options' => $options]);

		$this->behavior->beforeSave($event, $entity, $options);

		$options = new ArrayObject(['transactionId' => 'test-transaction-id']);
		$event = new Event('Model.afterSave', $this->table, ['entity' => $entity, 'options' => $options]);

		$this->behavior->afterSave($event, $entity, $options);

		$result = $this->auditTable->find()->all();
		$this->assertCount(1, $result);

		$audit = $result->first();

		$this->assertSame([
			'old' => [
				'_publicationData' => [
					'start' => ['dateTime' => '2023-01-01 10:00:00'],
					'end' => ['dateTime' => '2023-12-31 23:59:59'],
				],
			],
			'new' => [
				'_publicationData' => [
					'start' => ['dateTime' => null],
					'end' => ['dateTime' => null],
				],
			],
		], $audit->diff);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\AuditBehavior::afterSave()
	 * @see \Awyiss\Model\Behavior\AuditBehavior::auditTranslations()
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAfterSaveWithTranslationsAdded(): void {
		$this->login(3);

		/** @noinspection PhpFieldAssignmentTypeMismatchInspection */
		$this->table = $this->fetchTable('Employers');
		$this->behavior = $this->table->getBehavior('Audit');

		$this->auditTable->deleteAll([]);

		$entity = $this->table->newDefaultEntity([
			'title' => 'Original Title',
			'languageShortcode' => 'de',
		]);
		$entity->id = 123;
		$entity->clean();
		$entity->setNew(false);

		$this->table->patchEntity($entity, [
			'_translations' => [
				'en' => ['title' => 'English Title'],
				'de' => ['title' => 'German Title'],
			],
		]);

		$options = new ArrayObject(['transactionId' => 'test-transaction-id']);
		$event = new Event('Model.beforeSave', $this->table, ['entity' => $entity, 'options' => $options]);

		$behavior = $this->table->getBehavior('Audit');
		$behavior->beforeSave($event, $entity, $options);

		$options = new ArrayObject(['transactionId' => 'test-transaction-id']);
		$event = new Event('Model.afterSave', $this->table, ['entity' => $entity, 'options' => $options]);

		$behavior->afterSave($event, $entity, $options);

		$result = $this->auditTable->find()->all();
		$this->assertCount(1, $result);

		$audit = $result->first();

		// Patching the entity with translations will set the entity's fields
		// to the values of the main translation.
		$this->assertSame([
			'old' => [
				'title' => 'Original Title',
				'_translations' => null,
			],
			'new' => [
				'title' => 'German Title',
				'_translations' => [
					'en' => ['title' => 'English Title'],
					'de' => ['title' => 'German Title'],
				],
			],
		], $audit->diff);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\AuditBehavior::afterSave()
	 * @see \Awyiss\Model\Behavior\AuditBehavior::auditTranslations()
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAfterSaveWithTranslationsChanged(): void {
		$this->login(3);

		/** @noinspection PhpFieldAssignmentTypeMismatchInspection */
		$this->table = $this->fetchTable('Employers');
		$this->behavior = $this->table->getBehavior('Audit');

		$this->auditTable->deleteAll([]);

		$entity = $this->table->newDefaultEntity([
			'title' => 'Original Title',
			'languageShortcode' => 'de',
			'_translations' => [
				'en' => ['title' => 'English Title'],
				'fr' => ['title' => 'French Title'],
			],
		]);

		$entity->_translations['en']->clean();
		$entity->_translations['en']->setNew(false);
		$entity->_translations['fr']->clean();
		$entity->_translations['fr']->setNew(false);

		$entity->id = 123;
		$entity->clean();
		$entity->setNew(false);

		$this->table->patchEntity($entity, [
			'title' => 'Updated Title',
			'_translations' => [
				'en' => ['title' => 'Updated English Title'],
				'fr' => ['title' => 'Updated French Title'],
			],
		]);

		$options = new ArrayObject(['transactionId' => 'test-transaction-id']);
		$event = new Event('Model.beforeSave', $this->table, ['entity' => $entity, 'options' => $options]);

		$behavior = $this->table->getBehavior('Audit');
		$behavior->beforeSave($event, $entity, $options);

		$options = new ArrayObject(['transactionId' => 'test-transaction-id']);
		$event = new Event('Model.afterSave', $this->table, ['entity' => $entity, 'options' => $options]);

		$behavior->afterSave($event, $entity, $options);

		$result = $this->auditTable->find()->all();
		$this->assertCount(1, $result);

		$audit = $result->first();

		$this->assertSame([
			'old' => [
				'_translations' => [
					'en' => ['title' => 'English Title'],
					'fr' => ['title' => 'French Title'],
				],
			],
			'new' => [
				'_translations' => [
					'en' => ['title' => 'Updated English Title'],
					'fr' => ['title' => 'Updated French Title'],
				],
			],
		], $audit->diff);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\AuditBehavior::afterSave()
	 * @see \Awyiss\Model\Behavior\AuditBehavior::auditTranslations()
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAfterSaveWithTranslationsUnchanged(): void {
		$this->login(3);

		/** @noinspection PhpFieldAssignmentTypeMismatchInspection */
		$this->table = $this->fetchTable('Employers');
		$this->behavior = $this->table->getBehavior('Audit');

		$this->auditTable->deleteAll([]);

		$entity = $this->table->newDefaultEntity([
			'title' => 'Original Title',
			'languageShortcode' => 'de',
			'_translations' => [
				'en' => ['title' => 'English Title'],
				'fr' => ['title' => 'French Title'],
			],
		]);

		$entity->_translations['en']->clean();
		$entity->_translations['en']->setNew(false);
		$entity->_translations['fr']->clean();
		$entity->_translations['fr']->setNew(false);

		$entity->id = 123;
		$entity->clean();
		$entity->setNew(false);

		$this->table->patchEntity($entity, [
			'_translations' => [
				'en' => ['title' => 'English Title'],
				'fr' => ['title' => 'French Title'],
			],
		]);

		$options = new ArrayObject(['transactionId' => 'test-transaction-id']);
		$event = new Event('Model.beforeSave', $this->table, ['entity' => $entity, 'options' => $options]);

		$behavior = $this->table->getBehavior('Audit');
		$behavior->beforeSave($event, $entity, $options);

		$options = new ArrayObject(['transactionId' => 'test-transaction-id']);
		$event = new Event('Model.afterSave', $this->table, ['entity' => $entity, 'options' => $options]);

		$behavior->afterSave($event, $entity, $options);

		$auditCount = $this->auditTable->find()->count();
		$this->assertSame(0, $auditCount);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\AuditBehavior::afterSave()
	 * @see \Awyiss\Model\Behavior\AuditBehavior::auditTranslations()
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAfterSaveWithTranslationsChangesPartial(): void {
		$this->login(3);

		/** @noinspection PhpFieldAssignmentTypeMismatchInspection */
		$this->table = $this->fetchTable('Employers');
		$this->behavior = $this->table->getBehavior('Audit');

		$this->auditTable->deleteAll([]);

		$entity = $this->table->newDefaultEntity([
			'title' => 'Original Title',
			'languageShortcode' => 'de',
			'_translations' => [
				'en' => ['title' => 'English Title'],
				'fr' => ['title' => 'French Title'],
			],
		]);

		$entity->_translations['en']->clean();
		$entity->_translations['en']->setNew(false);
		$entity->_translations['fr']->clean();
		$entity->_translations['fr']->setNew(false);

		$entity->id = 123;
		$entity->clean();
		$entity->setNew(false);

		$this->table->patchEntity($entity, [
			'_translations' => [
				'en' => ['title' => 'Updated English Title'],
				'fr' => ['title' => 'French Title'],
			],
		]);

		$options = new ArrayObject(['transactionId' => 'test-transaction-id']);
		$event = new Event('Model.beforeSave', $this->table, ['entity' => $entity, 'options' => $options]);

		$behavior = $this->table->getBehavior('Audit');
		$behavior->beforeSave($event, $entity, $options);

		$options = new ArrayObject(['transactionId' => 'test-transaction-id']);
		$event = new Event('Model.afterSave', $this->table, ['entity' => $entity, 'options' => $options]);

		$behavior->afterSave($event, $entity, $options);

		$result = $this->auditTable->find()->all();
		$this->assertCount(1, $result);

		$audit = $result->first();

		$this->assertSame([
			'old' => [
				'_translations' => [
					'en' => ['title' => 'English Title'],
					'fr' => ['title' => 'French Title'],
				],
			],
			'new' => [
				'_translations' => [
					'en' => ['title' => 'Updated English Title'],
					'fr' => ['title' => 'French Title'],
				],
			],
		], $audit->diff);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\AuditBehavior::afterSave()
	 * @see \Awyiss\Model\Behavior\AuditBehavior::auditTranslations()
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAfterSaveWithTranslationsRemoved(): void {
		$this->login(3);

		/** @noinspection PhpFieldAssignmentTypeMismatchInspection */
		$this->table = $this->fetchTable('Employers');
		$this->behavior = $this->table->getBehavior('Audit');

		$this->auditTable->deleteAll([]);

		$entity = $this->table->newDefaultEntity([
			'title' => 'Original Title',
			'languageShortcode' => 'de',
			'_translations' => [
				'en' => ['title' => 'English Title'],
				'fr' => ['title' => 'French Title'],
			],
		]);

		$entity->_translations['en']->clean();
		$entity->_translations['en']->setNew(false);
		$entity->_translations['fr']->clean();
		$entity->_translations['fr']->setNew(false);

		$entity->id = 123;
		$entity->clean();
		$entity->setNew(false);

		$entity->_translations = [
			'en' => $entity->_translations['en'],
		];

		$options = new ArrayObject(['transactionId' => 'test-transaction-id']);
		$event = new Event('Model.beforeSave', $this->table, ['entity' => $entity, 'options' => $options]);

		$behavior = $this->table->getBehavior('Audit');
		$behavior->beforeSave($event, $entity, $options);

		$options = new ArrayObject(['transactionId' => 'test-transaction-id']);
		$event = new Event('Model.afterSave', $this->table, ['entity' => $entity, 'options' => $options]);

		$behavior->afterSave($event, $entity, $options);

		$result = $this->auditTable->find()->all();
		$this->assertCount(1, $result);

		$audit = $result->first();

		$this->assertSame([
			'old' => [
				'_translations' => [
					'en' => ['title' => 'English Title'],
					'fr' => ['title' => 'French Title'],
				],
			],
			'new' => [
				'_translations' => [
					'en' => ['title' => 'English Title'],
				],
			],
		], $audit->diff);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\AuditBehavior::afterSave()
	 * @see \Awyiss\Model\Behavior\AuditBehavior::auditTranslations()
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAfterSaveWithEntityWithoutTranslateBehavior(): void {
		$this->login(3);

		$this->auditTable->deleteAll([]);

		$entity = $this->table->newDefaultEntity([
			'title' => 'Test Employer',
			'languageShortcode' => 'de',
		]);
		$entity->id = 123;
		$entity->clean();
		$entity->setNew(false);
		$entity->unset('attributes');

		$this->table->patchEntity($entity, [
			'title' => 'Updated Employer',
			'_translations' => [
				'en' => ['title' => 'English Title'],
				'fr' => ['title' => 'French Title'],
			],
		]);

		$options = new ArrayObject(['transactionId' => 'test-transaction-id']);
		$event = new Event('Model.beforeSave', $this->table, ['entity' => $entity, 'options' => $options]);

		$this->behavior->beforeSave($event, $entity, $options);

		$options = new ArrayObject(['transactionId' => 'test-transaction-id']);
		$event = new Event('Model.afterSave', $this->table, ['entity' => $entity, 'options' => $options]);

		$this->behavior->afterSave($event, $entity, $options);

		$result = $this->auditTable->find()->all();
		$this->assertCount(1, $result);

		$audit = $result->first();

		$this->assertArrayNotHasKey('_translations', $audit->diff['old']);
		$this->assertArrayNotHasKey('_translations', $audit->diff['new']);
		$this->assertArrayHasKey('title', $audit->diff['old']);
		$this->assertArrayHasKey('title', $audit->diff['new']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\AuditBehavior::afterSave()
	 * @see \Awyiss\Model\Behavior\AuditBehavior::auditMediaAssignments()
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAfterSaveWithMediaAssignmentsAdded(): void {
		$this->login();

		$this->auditTable->deleteAll([]);

		$entity = $this->table->newDefaultEntity([
			'title' => 'Original Title',
			'languageShortcode' => 'de',
		]);
		$entity->id = 123;
		$entity->clean();
		$entity->setNew(false);
		$entity->unset('attributes');

		$this->table->patchEntity($entity, [
			'mediaAssignments' => [
				2 => [
					'media' => [
						'media_id' => 2,
					],
					'lightbox_media' => [
						'media_id' => 4,
					],
				],
			],
		]);

		$options = new ArrayObject(['transactionId' => 'test-transaction-id']);
		$event = new Event('Model.beforeSave', $this->table, ['entity' => $entity, 'options' => $options]);

		$this->behavior->beforeSave($event, $entity, $options);

		$options = new ArrayObject(['transactionId' => 'test-transaction-id']);
		$event = new Event('Model.afterSave', $this->table, ['entity' => $entity, 'options' => $options]);

		$this->behavior->afterSave($event, $entity, $options);

		$result = $this->auditTable->find()->all();

		$this->assertCount(1, $result);

		$audit = $result->first();

		$this->assertSame([
			'old' => [
				'mediaAssignments' => [],
			],
			'new' => [
				'mediaAssignments' => [
					'standard' => [
						'lightboxMedia' => [
							'mediaElementId' => 2,
							'mediaElementSelectorIdentifier' => 'lightbox_media',
							'mediaFolderId' => null,
							'mediaId' => 4,
							'scope' => 'pages',
							'systemOrder' => 1,
						],
						'media' => [
							'mediaElementId' => 2,
							'mediaElementSelectorIdentifier' => 'media',
							'mediaFolderId' => null,
							'mediaId' => 2,
							'scope' => 'pages',
							'systemOrder' => 1,
						],
					],
				],
			],
		], $audit->diff);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\AuditBehavior::afterSave()
	 * @see \Awyiss\Model\Behavior\AuditBehavior::auditMediaAssignments()
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAfterSaveWithMediaAssignmentsChanged(): void {
		$this->login();

		$this->auditTable->deleteAll([]);

		$entity = $this->table->newDefaultEntity([
			'title' => 'Original Title',
			'languageShortcode' => 'de',
			'mediaAssignments' => [
				2 => [
					'media' => [
						'mediaId' => 2,
					],
				],
			],
		]);
		$entity->id = 123;
		$entity->clean();
		$entity->setNew(false);
		$entity->unset('attributes');

		$this->table->patchEntity($entity, [
			'mediaAssignments' => [
				2 => [
					'media' => [
						'media_id' => 4,
					],
				],
			],
		]);

		$options = new ArrayObject(['transactionId' => 'test-transaction-id']);
		$event = new Event('Model.beforeSave', $this->table, ['entity' => $entity, 'options' => $options]);

		$this->behavior->beforeSave($event, $entity, $options);

		$options = new ArrayObject(['transactionId' => 'test-transaction-id']);
		$event = new Event('Model.afterSave', $this->table, ['entity' => $entity, 'options' => $options]);

		$this->behavior->afterSave($event, $entity, $options);

		$result = $this->auditTable->find()->all();

		$this->assertCount(1, $result);

		$audit = $result->first();

		$this->assertSame([
			'old' => [
				'mediaAssignments' => [
					'standard' => [
						'media' => [
							'mediaElementId' => 2,
							'mediaElementSelectorIdentifier' => 'media',
							'mediaFolderId' => null,
							'mediaId' => 2,
							'scope' => 'pages',
							'systemOrder' => 1,
						],
					],
				],
			],
			'new' => [
				'mediaAssignments' => [
					'standard' => [
						'media' => [
							'mediaElementId' => 2,
							'mediaElementSelectorIdentifier' => 'media',
							'mediaFolderId' => null,
							'mediaId' => 4,
							'scope' => 'pages',
							'systemOrder' => 1,
						],
					],
				],
			],
		], $audit->diff);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\AuditBehavior::afterSave()
	 * @see \Awyiss\Model\Behavior\AuditBehavior::auditMediaAssignments()
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAfterSaveWithMediaAssignmentsNotTracksIgnoredColumns(): void {
		$this->login();

		$this->auditTable->deleteAll([]);

		$entity = $this->table->newDefaultEntity([
			'title' => 'Original Title',
			'languageShortcode' => 'de',
			'mediaAssignments' => [
				2 => [
					'media' => [
						'mediaId' => 2,
					],
				],
			],
		]);

		$assignment = $entity->mediaAssignments[0];
		$assignment->id = 234;
		$assignment->foreignKey = 123;
		$assignment->deleted = true;
		$assignment->createdBy = 2;
		$assignment->createdOn = new DateTime('2023-01-01 10:00:00');
		$assignment->changedBy = 3;
		$assignment->changedOn = new DateTime('2023-01-01 10:00:00');
		$assignment->deletedBy = 1;
		$assignment->deletedOn = new DateTime('2023-01-01 10:00:00');
		$assignment->media = $this->fetchTable('Media')->newDefaultEntity();
		$assignment->mediaFolder = $this->fetchTable('MediaFolders')->newDefaultEntity();
		$assignment->clean();
		$assignment->setNew(false);

		$entity->id = 123;
		$entity->clean();
		$entity->setNew(false);
		$entity->unset('attributes');

		$this->table->patchEntity($entity, [
			'mediaAssignments' => [
				2 => [
					'media' => [
						'media_id' => 4,
						'id' => 234,
					],
				],
			],
		]);

		$assignment = $entity->mediaAssignments[0];
		$assignment->foreignKey = 123;
		$assignment->deleted = false;
		$assignment->createdBy = 3;
		$assignment->createdOn = new DateTime('2023-02-01 10:00:00');
		$assignment->changedBy = 1;
		$assignment->changedOn = new DateTime('2023-02-01 10:00:00');
		$assignment->deletedBy = 2;
		$assignment->deletedOn = new DateTime('2023-02-01 10:00:00');
		$assignment->media = $this->fetchTable('Media')->newDefaultEntity();
		$assignment->mediaFolder = $this->fetchTable('MediaFolders')->newDefaultEntity();

		$options = new ArrayObject(['transactionId' => 'test-transaction-id']);
		$event = new Event('Model.beforeSave', $this->table, ['entity' => $entity, 'options' => $options]);

		$this->behavior->beforeSave($event, $entity, $options);

		$options = new ArrayObject(['transactionId' => 'test-transaction-id']);
		$event = new Event('Model.afterSave', $this->table, ['entity' => $entity, 'options' => $options]);

		$this->behavior->afterSave($event, $entity, $options);

		$result = $this->auditTable->find()->all();

		$this->assertCount(1, $result);

		$audit = $result->first();

		$old = $audit->diff['old']['mediaAssignments']['standard']['media'];

		$this->assertArrayNotHasKey('id', $old);
		$this->assertArrayNotHasKey('foreignKey', $old);
		$this->assertArrayNotHasKey('deleted', $old);
		$this->assertArrayNotHasKey('createdBy', $old);
		$this->assertArrayNotHasKey('createdOn', $old);
		$this->assertArrayNotHasKey('changedBy', $old);
		$this->assertArrayNotHasKey('changedOn', $old);
		$this->assertArrayNotHasKey('deletedBy', $old);
		$this->assertArrayNotHasKey('deletedOn', $old);
		$this->assertArrayNotHasKey('media', $old);
		$this->assertArrayNotHasKey('mediaFolder', $old);

		$new = $audit->diff['new']['mediaAssignments']['standard']['media'];

		$this->assertArrayNotHasKey('id', $new);
		$this->assertArrayNotHasKey('foreignKey', $new);
		$this->assertArrayNotHasKey('deleted', $new);
		$this->assertArrayNotHasKey('createdBy', $new);
		$this->assertArrayNotHasKey('createdOn', $new);
		$this->assertArrayNotHasKey('changedBy', $new);
		$this->assertArrayNotHasKey('changedOn', $new);
		$this->assertArrayNotHasKey('deletedBy', $new);
		$this->assertArrayNotHasKey('deletedOn', $new);
		$this->assertArrayNotHasKey('media', $new);
		$this->assertArrayNotHasKey('mediaFolder', $new);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\AuditBehavior::afterSave()
	 * @see \Awyiss\Model\Behavior\AuditBehavior::auditMediaAssignments()
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAfterSaveWithMediaAssignmentsRemoved(): void {
		$this->login();

		$this->auditTable->deleteAll([]);

		$entity = $this->table->newDefaultEntity([
			'title' => 'Original Title',
			'languageShortcode' => 'de',
			'mediaAssignments' => [
				2 => [
					'media' => [
						'mediaId' => 2,
					],
				],
			],
		]);
		$entity->id = 123;
		$entity->clean();
		$entity->setNew(false);
		$entity->unset('attributes');

		$this->table->patchEntity($entity, [
			'mediaAssignments' => [],
		]);

		$options = new ArrayObject(['transactionId' => 'test-transaction-id']);
		$event = new Event('Model.beforeSave', $this->table, ['entity' => $entity, 'options' => $options]);

		$this->behavior->beforeSave($event, $entity, $options);

		$options = new ArrayObject(['transactionId' => 'test-transaction-id']);
		$event = new Event('Model.afterSave', $this->table, ['entity' => $entity, 'options' => $options]);

		$this->behavior->afterSave($event, $entity, $options);

		$result = $this->auditTable->find()->all();

		$this->assertCount(1, $result);

		$audit = $result->first();

		$this->assertSame([
			'old' => [
				'mediaAssignments' => [
					'standard' => [
						'media' => [
							'mediaElementId' => 2,
							'mediaElementSelectorIdentifier' => 'media',
							'mediaFolderId' => null,
							'mediaId' => 2,
							'scope' => 'pages',
							'systemOrder' => 1,
						],
					],
				],
			],
			'new' => [
				'mediaAssignments' => [],
			],
		], $audit->diff);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\AuditBehavior::afterSave()
	 * @see \Awyiss\Model\Behavior\AuditBehavior::auditMediaAssignments()
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAfterSaveWithMediaAssignmentsUnchanged(): void {
		$this->login();

		$this->auditTable->deleteAll([]);

		$entity = $this->table->newDefaultEntity([
			'title' => 'Original Title',
			'languageShortcode' => 'de',
			'mediaAssignments' => [
				2 => [
					'media' => [
						'mediaId' => 2,
					],
				],
			],
		]);
		$entity->id = 123;
		$entity->clean();
		$entity->setNew(false);
		$entity->unset('attributes');

		$this->table->patchEntity($entity, [
			'mediaAssignments' => [
				2 => [
					'media' => [
						'media_id' => 2,
					],
				],
			],
		]);

		$options = new ArrayObject(['transactionId' => 'test-transaction-id']);
		$event = new Event('Model.beforeSave', $this->table, ['entity' => $entity, 'options' => $options]);

		$this->behavior->beforeSave($event, $entity, $options);

		$options = new ArrayObject(['transactionId' => 'test-transaction-id']);

		$event = new Event('Model.afterSave', $this->table, ['entity' => $entity, 'options' => $options]);
		$this->behavior->afterSave($event, $entity, $options);

		$auditCount = $this->auditTable->find()->count();

		$this->assertSame(0, $auditCount);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\AuditBehavior::afterSave()
	 * @see \Awyiss\Model\Behavior\AuditBehavior::cleanHasManyAssociationData()
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAfterSaveWithHasManyAssociationWithCascadingAndOwnAuditNotTracksChanges(): void {
		$this->login();

		$this->auditTable->deleteAll([]);

		$contentTable = $this->fetchTable('Contents');
		$content = $contentTable->newDefaultEntity([
			'body' => 'Initial content',
			'pageId' => 320,
		]);
		$content->clean();
		$content->setNew(false);

		$entity = $this->table->newDefaultEntity([
			'title' => 'Test Page',
			'languageShortcode' => 'de',
		]);
		$entity->id = 320;
		$entity->clean();
		$entity->setNew(false);
		$entity->unset('attributes');

		$entity->set('contents', [$content], ['asOriginal' => true]);

		// Patch the contents association
		$this->table->patchEntity($entity, [
			'title' => 'Updated Page',
			'contents' => [
				['body' => 'Initial content', 'pageId' => 320],
			],
		]);

		$options = new ArrayObject(['transactionId' => 'test-transaction-id']);
		$event = new Event('Model.beforeSave', $this->table, ['entity' => $entity, 'options' => $options]);

		$this->behavior->beforeSave($event, $entity, $options);

		$options = new ArrayObject(['transactionId' => 'test-transaction-id']);

		$event = new Event('Model.afterSave', $this->table, ['entity' => $entity, 'options' => $options]);

		$this->behavior->afterSave($event, $entity, $options);

		$result = $this->auditTable->find()->all();

		$this->assertCount(1, $result);

		$audit = $result->first();

		$this->assertSame([
			'old' => [
				'title' => 'Test Page',
			],
			'new' => [
				'title' => 'Updated Page',
			],
		], $audit->diff);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\AuditBehavior::afterSave()
	 * @see \Awyiss\Model\Behavior\AuditBehavior::cleanHasManyAssociationData()
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAfterSaveWithHasManyAssociationAdded(): void {
		$this->login();

		$this->table = $this->fetchTable('ContentTemplates');
		$this->behavior = $this->table->getBehavior('Audit');

		$this->auditTable->deleteAll([]);

		$entity = $this->table->newDefaultEntity([
			'title' => 'Test Template',
			'languageShortcode' => 'de',
		]);
		$entity->id = 320;
		$entity->clean();
		$entity->setNew(false);

		// Patch the contents association
		$this->table->patchEntity($entity, [
			'contentTemplateElements' => [
				['identifier' => 'active', 'columnSpan' => '12/12'],
				['identifier' => 'title', 'columnSpan' => '6/12'],
			],
		]);

		$this->assertInstanceOf(ContentTemplateElement::class, $entity->contentTemplateElements[0]);
		$this->assertInstanceOf(ContentTemplateElement::class, $entity->contentTemplateElements[1]);

		$options = new ArrayObject(['transactionId' => 'test-transaction-id']);
		$event = new Event('Model.beforeSave', $this->table, ['entity' => $entity, 'options' => $options]);

		$this->behavior->beforeSave($event, $entity, $options);

		$options = new ArrayObject(['transactionId' => 'test-transaction-id']);

		$event = new Event('Model.afterSave', $this->table, ['entity' => $entity, 'options' => $options]);

		$this->behavior->afterSave($event, $entity, $options);

		$result = $this->auditTable->find()->all();

		$this->assertCount(1, $result);

		$audit = $result->first();

		$this->assertSame([
			'old' => [
				'contentTemplateElements' => null,
			],
			'new' => [
				'contentTemplateElements' => [
					[
						'identifier' => 'active',
						'columnSpan' => '12/12',
					],
					[
						'identifier' => 'title',
						'columnSpan' => '6/12',
					],
				],
			],
		], $audit->diff);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\AuditBehavior::afterSave()
	 * @see \Awyiss\Model\Behavior\AuditBehavior::cleanHasManyAssociationData()
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAfterSaveWithHasManyAssociationChanged(): void {
		$this->login();

		$this->table = $this->fetchTable('ContentTemplates');
		$this->behavior = $this->table->getBehavior('Audit');

		$this->auditTable->deleteAll([]);

		$contentTemplateElementsTable = $this->fetchTable('ContentTemplateElements');
		$element1 = $contentTemplateElementsTable->newDefaultEntity([
			'identifier' => 'active',
			'columnSpan' => '12/12',
		]);
		$element1->id = 345;
		$element1->clean();
		$element1->setNew(false);

		$element2 = $contentTemplateElementsTable->newDefaultEntity([
			'identifier' => 'title',
			'columnSpan' => '6/12',
		]);
		$element2->id = 346;
		$element2->clean();
		$element2->setNew(false);

		$entity = $this->table->newDefaultEntity([
			'title' => 'Test Template',
		]);
		$entity->id = 321;
		$entity->set('contentTemplateElements', [$element1, $element2], ['asOriginal' => true]);
		$entity->clean();
		$entity->setNew(false);

		// Patch the contents association
		$this->table->patchEntity($entity, [
			'contentTemplateElements' => [
				['identifier' => 'active', 'columnSpan' => '12/12'],
				['identifier' => 'title', 'columnSpan' => '8/12'],
			],
		]);

		$this->assertInstanceOf(ContentTemplateElement::class, $entity->contentTemplateElements[0]);
		$this->assertInstanceOf(ContentTemplateElement::class, $entity->contentTemplateElements[1]);

		$options = new ArrayObject(['transactionId' => 'test-transaction-id']);
		$event = new Event('Model.beforeSave', $this->table, ['entity' => $entity, 'options' => $options]);

		$this->behavior->beforeSave($event, $entity, $options);

		$options = new ArrayObject(['transactionId' => 'test-transaction-id']);
		$event = new Event('Model.afterSave', $this->table, ['entity' => $entity, 'options' => $options]);

		$this->behavior->afterSave($event, $entity, $options);

		$result = $this->auditTable->find()->all();

		$this->assertCount(1, $result);

		$audit = $result->first();

		$this->assertSame([
			'old' => [
				'contentTemplateElements' => [
					[
						'identifier' => 'active',
						'columnSpan' => '12/12',
						'title' => null,
						'fieldset' => '',
						'required' => false,
						'systemOrder' => 0,
					],
					[
						'identifier' => 'title',
						'columnSpan' => '6/12',
						'title' => null,
						'fieldset' => '',
						'required' => false,
						'systemOrder' => 0,
					],
				],
			],
			'new' => [
				'contentTemplateElements' => [
					[
						'identifier' => 'active',
						'columnSpan' => '12/12',
						'title' => null,
						'fieldset' => '',
						'required' => false,
						'systemOrder' => 0,
					],
					[
						'identifier' => 'title',
						'columnSpan' => '8/12',
						'title' => null,
						'fieldset' => '',
						'required' => false,
						'systemOrder' => 0,
					],
				],
			],
		], $audit->diff);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\AuditBehavior::afterSave()
	 * @see \Awyiss\Model\Behavior\AuditBehavior::cleanHasManyAssociationData()
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAfterSaveWithHasManyAssociationUnchanged(): void {
		$this->login();

		$this->table = $this->fetchTable('ContentTemplates');
		$this->behavior = $this->table->getBehavior('Audit');

		$this->auditTable->deleteAll([]);

		$contentTemplateElementsTable = $this->fetchTable('ContentTemplateElements');
		$element1 = $contentTemplateElementsTable->newDefaultEntity([
			'identifier' => 'active',
			'columnSpan' => '12/12',
		]);
		$element1->id = 345;
		$element1->clean();
		$element1->setNew(false);

		$element2 = $contentTemplateElementsTable->newDefaultEntity([
			'identifier' => 'title',
			'columnSpan' => '6/12',
		]);
		$element2->id = 346;
		$element2->clean();
		$element2->setNew(false);

		$entity = $this->table->newDefaultEntity([
			'title' => 'Test Template',
		]);
		$entity->id = 321;
		$entity->set('contentTemplateElements', [$element1, $element2], ['asOriginal' => true]);
		$entity->clean();
		$entity->setNew(false);

		// Patch the contents association
		$this->table->patchEntity($entity, [
			'contentTemplateElements' => [
				['identifier' => 'active', 'columnSpan' => '12/12'],
				['identifier' => 'title', 'columnSpan' => '6/12'],
			],
		]);

		$this->assertInstanceOf(ContentTemplateElement::class, $entity->contentTemplateElements[0]);
		$this->assertInstanceOf(ContentTemplateElement::class, $entity->contentTemplateElements[1]);

		$options = new ArrayObject(['transactionId' => 'test-transaction-id']);
		$event = new Event('Model.beforeSave', $this->table, ['entity' => $entity, 'options' => $options]);

		$this->behavior->beforeSave($event, $entity, $options);

		$options = new ArrayObject(['transactionId' => 'test-transaction-id']);
		$event = new Event('Model.afterSave', $this->table, ['entity' => $entity, 'options' => $options]);

		$this->behavior->afterSave($event, $entity, $options);

		$result = $this->auditTable->find()->all();

		$this->assertCount(0, $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\AuditBehavior::afterSave()
	 * @see \Awyiss\Model\Behavior\AuditBehavior::cleanHasManyAssociationData()
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAfterSaveWithHasManyAssociationRemoved(): void {
		$this->login();

		$this->table = $this->fetchTable('ContentTemplates');
		$this->behavior = $this->table->getBehavior('Audit');

		$this->auditTable->deleteAll([]);

		$contentTemplateElementsTable = $this->fetchTable('ContentTemplateElements');
		$element1 = $contentTemplateElementsTable->newDefaultEntity([
			'identifier' => 'active',
			'columnSpan' => '12/12',
		]);
		$element1->id = 345;
		$element1->clean();
		$element1->setNew(false);

		$element2 = $contentTemplateElementsTable->newDefaultEntity([
			'identifier' => 'title',
			'columnSpan' => '6/12',
		]);
		$element2->id = 346;
		$element2->clean();
		$element2->setNew(false);

		$entity = $this->table->newDefaultEntity([
			'title' => 'Test Template',
		]);
		$entity->id = 321;
		$entity->set('contentTemplateElements', [$element1, $element2], ['asOriginal' => true]);
		$entity->clean();
		$entity->setNew(false);

		// Patch the contents association
		$this->table->patchEntity($entity, [
			'contentTemplateElements' => null,
		]);

		$this->assertEmpty($entity->contentTemplateElements);

		$options = new ArrayObject(['transactionId' => 'test-transaction-id']);
		$event = new Event('Model.beforeSave', $this->table, ['entity' => $entity, 'options' => $options]);

		$this->behavior->beforeSave($event, $entity, $options);

		$options = new ArrayObject(['transactionId' => 'test-transaction-id']);
		$event = new Event('Model.afterSave', $this->table, ['entity' => $entity, 'options' => $options]);

		$this->behavior->afterSave($event, $entity, $options);

		$result = $this->auditTable->find()->all();

		$this->assertCount(1, $result);

		$audit = $result->first();

		$this->assertSame([
			'old' => [
				'contentTemplateElements' => [
					[
						'identifier' => 'active',
						'columnSpan' => '12/12',
						'title' => null,
						'fieldset' => '',
						'required' => false,
						'systemOrder' => 0,
					],
					[
						'identifier' => 'title',
						'columnSpan' => '6/12',
						'title' => null,
						'fieldset' => '',
						'required' => false,
						'systemOrder' => 0,
					],
				],
			],
			'new' => [
				'contentTemplateElements' => null,
			],
		], $audit->diff);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\AuditBehavior::afterSave()
	 * @see \Awyiss\Model\Behavior\AuditBehavior::cleanHasManyAssociationData()
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAfterSaveWithHasManyAssociationLoadsExistingAssociations(): void {
		$this->login();

		$this->table = $this->fetchTable('ContentTemplates');
		$this->behavior = $this->table->getBehavior('Audit');

		$this->auditTable->deleteAll([]);

		/** @var \Awyiss\Model\Entity\ContentTemplate $entity */
		$entity = $this->table->get(2);

		$this->assertEmpty($entity->contentTemplateElements);

		$this->table->patchEntity($entity, [
			'contentTemplateElements' => [
				['identifier' => 'active', 'columnSpan' => '12/12'],
				['identifier' => 'title', 'columnSpan' => '6/12'],
			],
		]);

		$this->assertInstanceOf(ContentTemplateElement::class, $entity->contentTemplateElements[0]);
		$this->assertInstanceOf(ContentTemplateElement::class, $entity->contentTemplateElements[1]);

		$options = new ArrayObject(['transactionId' => 'test-transaction-id']);
		$event = new Event('Model.beforeSave', $this->table, ['entity' => $entity, 'options' => $options]);

		$this->behavior->beforeSave($event, $entity, $options);

		$options = new ArrayObject(['transactionId' => 'test-transaction-id']);
		$event = new Event('Model.afterSave', $this->table, ['entity' => $entity, 'options' => $options]);

		$this->behavior->afterSave($event, $entity, $options);

		$result = $this->auditTable->find()->all();

		$this->assertCount(1, $result);

		$audit = $result->first();

		$this->assertSame([
			'old' => [
				'contentTemplateElements' => [
					[
						'identifier' => 'active',
						'title' => null,
						'fieldset' => 'presentation',
						'columnSpan' => '12/12',
						'required' => false,
						'systemOrder' => 1,
					],
					[
						'identifier' => 'active',
						'title' => null,
						'fieldset' => 'presentation',
						'columnSpan' => '12/12',
						'required' => false,
						'systemOrder' => 1,
					],
					[
						'identifier' => 'form_id',
						'title' => null,
						'fieldset' => 'content',
						'columnSpan' => '12/12',
						'required' => false,
						'systemOrder' => 1,
					],
					[
						'identifier' => 'content_template_id',
						'title' => null,
						'fieldset' => 'presentation',
						'columnSpan' => '12/12',
						'required' => true,
						'systemOrder' => 2,
					],
					[
						'identifier' => 'attributes.background_color',
						'title' => null,
						'fieldset' => 'presentation',
						'columnSpan' => '12/12',
						'required' => false,
						'systemOrder' => 2,
					],
					[
						'identifier' => 'survey_id',
						'title' => null,
						'fieldset' => 'content',
						'columnSpan' => '12/12',
						'required' => false,
						'systemOrder' => 2,
					],
					[
						'identifier' => 'language_shortcode',
						'title' => null,
						'fieldset' => 'conditions',
						'columnSpan' => '12/12',
						'required' => false,
						'systemOrder' => 3,
					],
					[
						'identifier' => 'page_id',
						'title' => null,
						'fieldset' => 'conditions',
						'columnSpan' => '12/12',
						'required' => false,
						'systemOrder' => 4,
					],
					[
						'identifier' => 'content_area_id',
						'title' => null,
						'fieldset' => 'conditions',
						'columnSpan' => '12/12',
						'required' => false,
						'systemOrder' => 5,
					],
					[
						'identifier' => 'system_order',
						'title' => null,
						'fieldset' => 'conditions',
						'columnSpan' => '12/12',
						'required' => false,
						'systemOrder' => 6,
					],
					[
						'identifier' => 'css_class',
						'title' => null,
						'fieldset' => 'presentation',
						'columnSpan' => '12/12',
						'required' => false,
						'systemOrder' => 7,
					],
				],
			],
			'new' => [
				'contentTemplateElements' => [
					[
						'identifier' => 'active',
						'columnSpan' => '12/12',
						'title' => null,
						'fieldset' => 'presentation',
						'required' => false,
						'systemOrder' => 1,
					],
					[
						'identifier' => 'title',
						'columnSpan' => '6/12',
						'title' => null,
						'fieldset' => 'presentation',
						'required' => false,
						'systemOrder' => 1,
					],
				],
			],
		], $audit->diff);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\AuditBehavior::afterSave()
	 * @see \Awyiss\Model\Behavior\AuditBehavior::cleanHasOneAssociationData()
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAfterSaveWithHasOneAssociationAdded(): void {
		$this->login();

		/** @noinspection PhpFieldAssignmentTypeMismatchInspection */
		$this->table = $this->fetchTable('News');
		$this->behavior = $this->table->getBehavior('Audit');

		$this->auditTable->deleteAll([]);

		$entity = $this->table->newDefaultEntity([
			'title' => 'New News',
		]);
		$entity->id = 1001;
		$entity->clean();
		$entity->setNew(false);

		$this->table->patchEntity($entity, [
			'attributes' => [
				'teaser' => 'This is a teaser text.',
			],
		]);

		$this->assertInstanceOf(AttributesNews::class, $entity->attributes);

		$options = new ArrayObject(['transactionId' => 'test-transaction-id']);
		$event = new Event('Model.beforeSave', $this->table, ['entity' => $entity, 'options' => $options]);

		$this->behavior->beforeSave($event, $entity, $options);

		$options = new ArrayObject(['transactionId' => 'test-transaction-id']);
		$event = new Event('Model.afterSave', $this->table, ['entity' => $entity, 'options' => $options]);

		$this->behavior->afterSave($event, $entity, $options);

		$result = $this->auditTable->find()->all();

		$this->assertCount(1, $result);

		$audit = $result->first();

		$this->assertSame([
			'old' => [
				'attributes' => [],
			],
			'new' => [
				'attributes' => [
					'teaser' => 'This is a teaser text.',
					'text' => null,
					'date' => null,
				],
			],
		], $audit->diff);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\AuditBehavior::afterSave()
	 * @see \Awyiss\Model\Behavior\AuditBehavior::cleanHasOneAssociationData()
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAfterSaveWithHasOneAssociationChanged(): void {
		$this->login();

		/** @noinspection PhpFieldAssignmentTypeMismatchInspection */
		$this->table = $this->fetchTable('News');
		$this->behavior = $this->table->getBehavior('Audit');

		$this->auditTable->deleteAll([]);

		$entity = $this->table->newDefaultEntity([
			'title' => 'New News',
			'attributes' => [
				'teaser' => 'This is a teaser text.',
			],
		]);

		$attributes = $entity->attributes;
		$attributes->id = 1002;
		$attributes->clean();
		$attributes->setNew(false);

		$entity->id = 1001;
		$entity->clean();
		$entity->setNew(false);

		$this->table->patchEntity($entity, [
			'attributes' => [
				'teaser' => 'This is an updated teaser text.',
			],
		]);

		$this->assertInstanceOf(AttributesNews::class, $entity->attributes);

		$options = new ArrayObject(['transactionId' => 'test-transaction-id']);
		$event = new Event('Model.beforeSave', $this->table, ['entity' => $entity, 'options' => $options]);

		$this->behavior->beforeSave($event, $entity, $options);

		$options = new ArrayObject(['transactionId' => 'test-transaction-id']);
		$event = new Event('Model.afterSave', $this->table, ['entity' => $entity, 'options' => $options]);

		$this->behavior->afterSave($event, $entity, $options);

		$result = $this->auditTable->find()->all();

		$this->assertCount(1, $result);

		$audit = $result->first();

		$this->assertSame([
			'old' => [
				'attributes' => [
					'teaser' => 'This is a teaser text.',
					'text' => null,
					'date' => null,
				],
			],
			'new' => [
				'attributes' => [
					'teaser' => 'This is an updated teaser text.',
					'text' => null,
					'date' => null,
				],
			],
		], $audit->diff);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\AuditBehavior::afterSave()
	 * @see \Awyiss\Model\Behavior\AuditBehavior::cleanHasOneAssociationData()
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAfterSaveWithHasOneAssociationUnchanged(): void {
		$this->login();

		/** @noinspection PhpFieldAssignmentTypeMismatchInspection */
		$this->table = $this->fetchTable('News');
		$this->behavior = $this->table->getBehavior('Audit');

		$this->auditTable->deleteAll([]);

		$entity = $this->table->newDefaultEntity([
			'title' => 'New News',
			'attributes' => [
				'teaser' => 'This is a teaser text.',
			],
		]);

		$attributes = $entity->attributes;
		$attributes->id = 1002;
		$attributes->clean();
		$attributes->setNew(false);

		$entity->id = 1001;
		$entity->clean();
		$entity->setNew(false);

		$this->table->patchEntity($entity, [
			'attributes' => [
				'teaser' => 'This is a teaser text.',
			],
		]);

		$this->assertInstanceOf(AttributesNews::class, $entity->attributes);

		$options = new ArrayObject(['transactionId' => 'test-transaction-id']);
		$event = new Event('Model.beforeSave', $this->table, ['entity' => $entity, 'options' => $options]);

		$this->behavior->beforeSave($event, $entity, $options);

		$options = new ArrayObject(['transactionId' => 'test-transaction-id']);
		$event = new Event('Model.afterSave', $this->table, ['entity' => $entity, 'options' => $options]);

		$this->behavior->afterSave($event, $entity, $options);

		$result = $this->auditTable->find()->all();

		$this->assertCount(0, $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\AuditBehavior::afterSave()
	 * @see \Awyiss\Model\Behavior\AuditBehavior::cleanBelongsToManyAssociationData()
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAfterSaveWithBelongsToManyAssociationAdded(): void {
		$this->login();

		$this->table = $this->fetchTable('Users');
		$this->behavior = $this->table->getBehavior('Audit');

		$this->auditTable->deleteAll([]);

		$entity = $this->table->newDefaultEntity([
			'username' => 'newuser',
			'email' => 'dummy@domain.com',
		]);

		$entity->id = 1001;
		$entity->clean();
		$entity->setNew(false);

		$this->table->patchEntity($entity, [
			'usergroups' => [
				['id' => 1, 'title' => 'Group 123'],
				['id' => 2, 'title' => 'Group 234'],
			],
		]);

		$this->assertInstanceOf(Usergroup::class, $entity->usergroups[0]);
		$this->assertInstanceOf(Usergroup::class, $entity->usergroups[1]);

		$options = new ArrayObject(['transactionId' => 'test-transaction-id']);
		$event = new Event('Model.beforeSave', $this->table, ['entity' => $entity, 'options' => $options]);

		$this->behavior->beforeSave($event, $entity, $options);

		$options = new ArrayObject(['transactionId' => 'test-transaction-id']);
		$event = new Event('Model.afterSave', $this->table, ['entity' => $entity, 'options' => $options]);

		$this->behavior->afterSave($event, $entity, $options);

		$result = $this->auditTable->find()->all();

		$this->assertCount(1, $result);

		$audit = $result->first();

		$this->assertSame([
			'old' => [
				'usergroups' => [
					'_ids' => [],
				],
			],
			'new' => [
				'usergroups' => [
					'_ids' => [1, 2],
				],
			],
		], $audit->diff);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\AuditBehavior::afterSave()
	 * @see \Awyiss\Model\Behavior\AuditBehavior::cleanBelongsToManyAssociationData()
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAfterSaveWithBelongsToManyAssociationChanged(): void {
		$this->login();

		$this->table = $this->fetchTable('Users');
		$this->behavior = $this->table->getBehavior('Audit');

		$this->auditTable->deleteAll([]);

		$entity = $this->table->newDefaultEntity([
			'username' => 'newuser',
			'email' => 'dummy@domain.com',
		]);

		$entity->id = 1001;
		$entity->clean();
		$entity->setNew(false);

		$entity->set('usergroups', [
			$this->table->Usergroups->get(1),
			$this->table->Usergroups->get(2),
		], ['asOriginal' => true]);

		$this->assertInstanceOf(Usergroup::class, $entity->usergroups[0]);
		$this->assertInstanceOf(Usergroup::class, $entity->usergroups[1]);

		$this->table->patchEntity($entity, [
			'usergroups' => [
				['id' => 3, 'title' => 'Group 345'],
			],
		]);

		$this->assertInstanceOf(Usergroup::class, $entity->usergroups[0]);

		$options = new ArrayObject(['transactionId' => 'test-transaction-id']);
		$event = new Event('Model.beforeSave', $this->table, ['entity' => $entity, 'options' => $options]);

		$this->behavior->beforeSave($event, $entity, $options);

		$options = new ArrayObject(['transactionId' => 'test-transaction-id']);
		$event = new Event('Model.afterSave', $this->table, ['entity' => $entity, 'options' => $options]);

		$this->behavior->afterSave($event, $entity, $options);

		$result = $this->auditTable->find()->all();

		$this->assertCount(1, $result);

		$audit = $result->first();

		$this->assertSame([
			'old' => [
				'usergroups' => [
					'_ids' => [1, 2],
				],
			],
			'new' => [
				'usergroups' => [
					'_ids' => [3],
				],
			],
		], $audit->diff);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\AuditBehavior::afterSave()
	 * @see \Awyiss\Model\Behavior\AuditBehavior::cleanBelongsToManyAssociationData()
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAfterSaveWithBelongsToManyAssociationUnchanged(): void {
		$this->login();

		$this->table = $this->fetchTable('Users');
		$this->behavior = $this->table->getBehavior('Audit');

		$this->auditTable->deleteAll([]);

		$entity = $this->table->newDefaultEntity([
			'username' => 'newuser',
			'email' => 'dummy@domain.com',
		]);

		$entity->id = 1001;
		$entity->clean();
		$entity->setNew(false);

		$entity->set('usergroups', [
			$this->table->Usergroups->get(1),
			$this->table->Usergroups->get(2),
		], ['asOriginal' => true]);

		$this->assertInstanceOf(Usergroup::class, $entity->usergroups[0]);
		$this->assertInstanceOf(Usergroup::class, $entity->usergroups[1]);

		$this->table->patchEntity($entity, [
			'usergroups' => [
				['id' => 1],
			],
		]);

		$this->table->patchEntity($entity, [
			'usergroups' => [
				['id' => 1],
				['id' => 2],
			],
		]);

		$this->assertInstanceOf(Usergroup::class, $entity->usergroups[0]);
		$this->assertInstanceOf(Usergroup::class, $entity->usergroups[1]);

		$options = new ArrayObject(['transactionId' => 'test-transaction-id']);
		$event = new Event('Model.beforeSave', $this->table, ['entity' => $entity, 'options' => $options]);

		$this->behavior->beforeSave($event, $entity, $options);

		$options = new ArrayObject(['transactionId' => 'test-transaction-id']);
		$event = new Event('Model.afterSave', $this->table, ['entity' => $entity, 'options' => $options]);

		$this->behavior->afterSave($event, $entity, $options);

		$result = $this->auditTable->find()->all();

		$this->assertCount(0, $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\AuditBehavior::afterSave()
	 * @see \Awyiss\Model\Behavior\AuditBehavior::cleanBelongsToManyAssociationData()
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAfterSaveWithBelongsToManyAssociationRemoved(): void {
		$this->login();

		$this->table = $this->fetchTable('Users');
		$this->behavior = $this->table->getBehavior('Audit');

		$this->auditTable->deleteAll([]);

		$entity = $this->table->newDefaultEntity([
			'username' => 'newuser',
			'email' => 'dummy@domain.com',
		]);

		$entity->id = 1001;
		$entity->clean();
		$entity->setNew(false);

		$entity->set('usergroups', [
			$this->table->Usergroups->get(1),
			$this->table->Usergroups->get(2),
		], ['asOriginal' => true]);

		$this->assertInstanceOf(Usergroup::class, $entity->usergroups[0]);
		$this->assertInstanceOf(Usergroup::class, $entity->usergroups[1]);

		$this->table->patchEntity($entity, [
			'usergroups' => null,
		]);

		$this->assertEmpty($entity->usergroups);

		$options = new ArrayObject(['transactionId' => 'test-transaction-id']);
		$event = new Event('Model.beforeSave', $this->table, ['entity' => $entity, 'options' => $options]);

		$this->behavior->beforeSave($event, $entity, $options);

		$options = new ArrayObject(['transactionId' => 'test-transaction-id']);
		$event = new Event('Model.afterSave', $this->table, ['entity' => $entity, 'options' => $options]);

		$this->behavior->afterSave($event, $entity, $options);

		$result = $this->auditTable->find()->all();

		$this->assertCount(1, $result);

		$audit = $result->first();

		$this->assertSame([
			'old' => [
				'usergroups' => [
					'_ids' => [1, 2],
				],
			],
			'new' => [
				'usergroups' => [
					'_ids' => [],
				],
			],
		], $audit->diff);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Behavior\AuditBehavior::afterSave()
	 * @see \Awyiss\Model\Behavior\AuditBehavior::cleanBelongsToManyAssociationData()
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAfterSaveWithBelongsToManyAssociationLoadsExistingAssociations(): void {
		$this->login();

		$this->table = $this->fetchTable('Users');
		$this->behavior = $this->table->getBehavior('Audit');

		$this->auditTable->deleteAll([]);

		/** @var \Awyiss\Model\Entity\User $entity */
		$entity = $this->table->get(1);

		$this->assertEmpty($entity->usergroups);

		$this->table->patchEntity($entity, [
			'usergroups' => [
				['id' => 2, 'title' => 'Group 234'],
				['id' => 1, 'title' => 'Group 123'],
			],
		]);

		$this->assertInstanceOf(Usergroup::class, $entity->usergroups[0]);
		$this->assertInstanceOf(Usergroup::class, $entity->usergroups[1]);

		$options = new ArrayObject(['transactionId' => 'test-transaction-id']);
		$event = new Event('Model.beforeSave', $this->table, ['entity' => $entity, 'options' => $options]);

		$this->behavior->beforeSave($event, $entity, $options);

		$options = new ArrayObject(['transactionId' => 'test-transaction-id']);
		$event = new Event('Model.afterSave', $this->table, ['entity' => $entity, 'options' => $options]);

		$this->behavior->afterSave($event, $entity, $options);

		$result = $this->auditTable->find()->all();

		$this->assertCount(1, $result);

		$audit = $result->first();

		$this->assertSame([
			'old' => [
				'usergroups' => [
					'_ids' => [1, 2],
				],
			],
			'new' => [
				'usergroups' => [
					'_ids' => [2, 1],
				],
			],
		], $audit->diff);
	}


	/**
	 * @param int $userId The user ID to log in as.
	 * @return \Awyiss\Model\Entity\User
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	protected function login(int $userId = 1): User {
		$request = new ServerRequest([
			'url' => '/dummy',
			'params' => [
				'lang' => 'xy',
				'controller' => 'dashboard',
				'action' => 'overview',
				'_name' => 'Backend',
				'prefix' => 'Backend',
				'parts' => [],
				'pass' => [],
				'plugin' => null,
			],
		]);

		$request = $request->withAttribute('authorization', new AuthorizationService('Backend'));

		$user = parent::login($userId);
		$request = $request->withAttribute('identity', $user);

		Router::setRequest($request);

		return $user;
	}
}
