<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Model\Table;


use Awyiss\Awyiss;
use Awyiss\Model\Entity\SurveyQuestion;
use Awyiss\Model\Enum\Survey\QuestionType;
use Awyiss\Model\Table\SurveyQuestionsTable;
use Awyiss\ORM\Association\BelongsTo;
use Awyiss\ORM\Association\HasMany;
use Awyiss\Test\TestSuite\TestCase;
use Awyiss\Validation\Validator;
use Cake\Datasource\FactoryLocator;
use Cake\Datasource\RulesChecker;


/**
 * SurveyQuestionsTable Test Case
 *
 * @see \Awyiss\Model\Table\SurveyQuestionsTable
 */
class SurveyQuestionsTableTest extends TestCase {
	/**
	 * @var \Awyiss\Model\Table\SurveyQuestionsTable
	 */
	protected SurveyQuestionsTable $surveyQuestionsTable;


	/**
	 * @inheritDoc
	 */
	protected function setUp(): void {
		parent::setUp();

		/** @noinspection PhpFieldAssignmentTypeMismatchInspection */
		$this->surveyQuestionsTable = FactoryLocator::get('Table')->get('SurveyQuestions');
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\SurveyQuestionsTable::ATTRIBUTABLE
	 */
	public function testAttributableConstant(): void {
		/** @noinspection PhpUnitAssertTrueWithIncompatibleTypeArgumentInspection */
		$this->assertTrue($this->surveyQuestionsTable::ATTRIBUTABLE);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\SurveyQuestionsTable::TABLE
	 */
	public function testTableConstant(): void {
		$this->assertEquals('survey_questions', $this->surveyQuestionsTable::TABLE);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\SurveyQuestionsTable::initializeAssociations()
	 */
	public function testInitializeAssociations(): void {
		$this->assertCount(10, $this->surveyQuestionsTable->associations()->keys());

		// Test SurveyAnswers association
		$this->assertTrue($this->surveyQuestionsTable->hasAssociation('SurveyAnswers'));
		$surveyAnswersAssociation = $this->surveyQuestionsTable->getAssociation('SurveyAnswers');
		$this->assertInstanceOf(HasMany::class, $surveyAnswersAssociation);
		$this->assertTrue($surveyAnswersAssociation->getCascadeCallbacks());
		$this->assertTrue($surveyAnswersAssociation->getDependent());
		$this->assertEquals('surveyQuestionId', $surveyAnswersAssociation->getForeignKey());
		$this->assertEquals('replace', $surveyAnswersAssociation->getSaveStrategy());

		// Test SurveySurveyQuestions association
		$this->assertTrue($this->surveyQuestionsTable->hasAssociation('SurveySurveyQuestions'));
		$surveySurveyQuestionsAssociation = $this->surveyQuestionsTable->getAssociation('SurveySurveyQuestions');
		$this->assertInstanceOf(HasMany::class, $surveySurveyQuestionsAssociation);
		$this->assertTrue($surveySurveyQuestionsAssociation->getCascadeCallbacks());
		$this->assertTrue($surveySurveyQuestionsAssociation->getDependent());
		$this->assertEquals('surveyQuestionId', $surveySurveyQuestionsAssociation->getForeignKey());
		$this->assertEquals('replace', $surveySurveyQuestionsAssociation->getSaveStrategy());

		// 'MediaAssignments' must also exist
		$this->assertTrue($this->surveyQuestionsTable->hasAssociation('MediaAssignments'));
		$mediaAssignmentsAssociation = $this->surveyQuestionsTable->getAssociation('MediaAssignments');
		$this->assertInstanceOf(HasMany::class, $mediaAssignmentsAssociation);
		$this->assertTrue($mediaAssignmentsAssociation->getCascadeCallbacks());
		$this->assertTrue($mediaAssignmentsAssociation->getDependent());

		// 'CreatedByUser' must also exist
		$this->assertTrue($this->surveyQuestionsTable->hasAssociation('CreatedByUser'));
		$createdByUserAssociation = $this->surveyQuestionsTable->getAssociation('CreatedByUser');
		$this->assertInstanceOf(BelongsTo::class, $createdByUserAssociation);
		$this->assertFalse($createdByUserAssociation->getCascadeCallbacks());
		$this->assertFalse($createdByUserAssociation->getDependent());

		// 'ChangedByUser' must also exist
		$this->assertTrue($this->surveyQuestionsTable->hasAssociation('ChangedByUser'));
		$changedByUserAssociation = $this->surveyQuestionsTable->getAssociation('ChangedByUser');
		$this->assertInstanceOf(BelongsTo::class, $changedByUserAssociation);
		$this->assertFalse($changedByUserAssociation->getCascadeCallbacks());
		$this->assertFalse($changedByUserAssociation->getDependent());

		// 'DeletedByUser' must also exist
		$this->assertTrue($this->surveyQuestionsTable->hasAssociation('DeletedByUser'));
		$deletedByUserAssociation = $this->surveyQuestionsTable->getAssociation('DeletedByUser');
		$this->assertInstanceOf(BelongsTo::class, $deletedByUserAssociation);
		$this->assertFalse($deletedByUserAssociation->getCascadeCallbacks());
		$this->assertFalse($deletedByUserAssociation->getDependent());

		// Test translation associations
		$this->assertTrue($this->surveyQuestionsTable->hasAssociation('SurveyQuestions_title_translation'));
		$titleTranslationAssociation = $this->surveyQuestionsTable->getAssociation('SurveyQuestions_title_translation');
		$this->assertFalse($titleTranslationAssociation->getCascadeCallbacks());
		$this->assertFalse($titleTranslationAssociation->getDependent());

		$this->assertTrue($this->surveyQuestionsTable->hasAssociation('SurveyQuestions_subtitle_translation'));
		$subtitleTranslationAssociation = $this->surveyQuestionsTable->getAssociation('SurveyQuestions_subtitle_translation');
		$this->assertFalse($subtitleTranslationAssociation->getCascadeCallbacks());
		$this->assertFalse($subtitleTranslationAssociation->getDependent());

		$this->assertTrue($this->surveyQuestionsTable->hasAssociation('SurveyQuestions_text_translation'));
		$textTranslationAssociation = $this->surveyQuestionsTable->getAssociation('SurveyQuestions_text_translation');
		$this->assertFalse($textTranslationAssociation->getCascadeCallbacks());
		$this->assertFalse($textTranslationAssociation->getDependent());

		// 'I18n' must also exist
		$this->assertTrue($this->surveyQuestionsTable->hasAssociation('I18n'));
		$i18nAssociation = $this->surveyQuestionsTable->getAssociation('I18n');
		$this->assertInstanceOf(HasMany::class, $i18nAssociation);
		$this->assertFalse($i18nAssociation->getCascadeCallbacks());
		$this->assertTrue($i18nAssociation->getDependent());
		$this->assertEquals('append', $i18nAssociation->getSaveStrategy());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\SurveyQuestionsTable::validationDefault()
	 */
	public function testValidationDefault(): void {
		$validator = new Validator();
		$result = $this->surveyQuestionsTable->validationDefault($validator);

		$this->assertInstanceOf(Validator::class, $result);
		$this->assertSame('SurveyQuestions', $result->getI18nDomain());

		// Test required fields
		$this->assertTrue($result->hasField('title'));
		$this->assertSame('create', $result->field('title')->isPresenceRequired());

		// Test other fields
		$this->assertTrue($result->hasField('id'));
		$this->assertTrue($result->hasField('type'));
		$this->assertTrue($result->hasField('subtitle'));
		$this->assertTrue($result->hasField('text'));
		$this->assertTrue($result->hasField('active'));
		$this->assertTrue($result->hasField('deleted'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\SurveyQuestionsTable::validationDefault()
	 */
	public function testEntityValidationSuccess(): void {
		$data = [
			'type' => 'singleChoice',
			'title' => 'Test Survey Question',
			'subtitle' => 'Test subtitle',
			'text' => 'This is a test question text.',
			'active' => true,
			'deleted' => false,
		];

		$entity = $this->surveyQuestionsTable->newDefaultEntity($data);
		$errors = $entity->getErrors();

		$this->assertEmpty($errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\SurveyQuestionsTable::validationDefault()
	 */
	public function testEntityValidationMissingRequired(): void {
		$data = [
			'type' => 'singleChoice',
			'subtitle' => 'Test subtitle',
		];

		$entity = $this->surveyQuestionsTable->newDefaultEntity();
		$this->surveyQuestionsTable->patchEntity($entity, $data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('title', $errors);
		$this->assertArrayHasKey('_required', $errors['title']);
		$this->assertSame('survey_questions::error_required', $errors['title']['_required']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\SurveyQuestionsTable::validationDefault()
	 */
	public function testEntityValidationMissingType(): void {
		$data = [
			'title' => 'Test Question',
			'subtitle' => 'Test subtitle',
		];

		$entity = $this->surveyQuestionsTable->newDefaultEntity();
		$this->surveyQuestionsTable->patchEntity($entity, $data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('type', $errors);
		$this->assertArrayHasKey('_required', $errors['type']);
		$this->assertSame('survey_questions::error_required', $errors['type']['_required']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\SurveyQuestionsTable::validationDefault()
	 */
	public function testEntityValidationInvalidTypes(): void {
		$data = [
			'id' => 'not_an_integer',
			'type' => true,
			'title' => true,
			'subtitle' => true,
			'text' => true,
			'active' => 'not_a_boolean',
			'deleted' => 'not_a_boolean',
		];

		$entity = $this->surveyQuestionsTable->newDefaultEntity();
		$this->surveyQuestionsTable->patchEntity($entity, $data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('id', $errors);

		$this->assertArrayHasKey('type', $errors);
		$this->assertArrayHasKey('enum', $errors['type']);
		$this->assertSame('survey_questions::error_enum', $errors['type']['enum']);

		$this->assertArrayHasKey('title', $errors);
		$this->assertArrayHasKey('subtitle', $errors);
		$this->assertArrayHasKey('text', $errors);
		$this->assertArrayHasKey('active', $errors);
		$this->assertArrayHasKey('deleted', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\SurveyQuestionsTable::validationDefault()
	 */
	public function testEntityValidationFieldLength(): void {
		$data = [
			'id' => 123456789123, // exceeds 11 char limit
			'type' => str_repeat('a', 21), // exceeds 20 char limit
			'title' => str_repeat('b', 256), // exceeds 255 char limit
			'subtitle' => str_repeat('c', 256), // exceeds 255 char limit
			'text' => str_repeat('d', 65536), // exceeds 65535 byte limit
		];

		$entity = $this->surveyQuestionsTable->newDefaultEntity();
		$this->surveyQuestionsTable->patchEntity($entity, $data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('id', $errors);
		$this->assertArrayHasKey('type', $errors);
		$this->assertArrayHasKey('title', $errors);
		$this->assertArrayHasKey('subtitle', $errors);
		$this->assertArrayHasKey('text', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\SurveyQuestionsTable::validationDefault()
	 */
	public function testEntityValidationBlankFields(): void {
		$data = [
			'title' => '   ', // Only whitespace
			'subtitle' => '   ', // Only whitespace
		];

		$entity = $this->surveyQuestionsTable->newDefaultEntity();
		$this->surveyQuestionsTable->patchEntity($entity, $data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('title', $errors);
		$this->assertArrayHasKey('notBlank', $errors['title']);

		$this->assertArrayHasKey('subtitle', $errors);
		$this->assertArrayHasKey('notBlank', $errors['subtitle']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\SurveyQuestionsTable::validationDefault()
	 */
	public function testEntityValidationOptionalFields(): void {
		$data = [
			'type' => 'singleChoice',
			'title' => 'Test Question',
			'subtitle' => '', // Empty string should be allowed
			'text' => '', // Empty string should be allowed
		];

		$entity = $this->surveyQuestionsTable->newDefaultEntity();
		$this->surveyQuestionsTable->patchEntity($entity, $data);
		$errors = $entity->getErrors();

		// subtitle and text are allowEmptyString, so empty strings should be valid
		$this->assertArrayNotHasKey('subtitle', $errors);
		$this->assertArrayNotHasKey('text', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\SurveyQuestionsTable::buildRules()
	 */
	public function testBuildRulesValidType(): void {
		$data = [
			'type' => 'singleChoice', // Patching entity will convert to enum
			'title' => 'Valid Question',
		];

		$entity = $this->surveyQuestionsTable->newDefaultEntity();

		$this->surveyQuestionsTable->patchEntity($entity, $data);

		$this->assertSame(QuestionType::SingleChoice, $entity->type);

		$result = $this->surveyQuestionsTable->checkRules($entity);

		$this->assertTrue($result);

		$entity->type = QuestionType::FreeText;

		$result = $this->surveyQuestionsTable->checkRules($entity);

		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\SurveyQuestionsTable::buildRules()
	 */
	public function testBuildRulesInvalidType(): void {
		$data = [
			'type' => 'invalid_type', // Patching entity will convert to enum but fail here
			'title' => 'Invalid Question',
		];

		$entity = $this->surveyQuestionsTable->newDefaultEntity();

		$this->surveyQuestionsTable->patchEntity($entity, $data);

		$this->assertSame(QuestionType::SingleChoice, $entity->type);

		$result = $this->surveyQuestionsTable->checkRules($entity);

		$this->assertTrue($result);

		$entity->type = 'invalid_type'; // Setting a value directly will not convert to enum

		$result = $this->surveyQuestionsTable->checkRules($entity);

		$this->assertFalse($result);

		$errors = $entity->getErrors();
		$this->assertArrayHasKey('type', $errors);
		$this->assertArrayHasKey('validType', $errors['type']);
		$this->assertSame('survey_questions::error_valid_type', $errors['type']['validType']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\SurveyQuestionsTable::buildRules()
	 */
	public function testBuildRulesDeleteNoLinkedSurveys(): void {
		// Create a survey question that is not linked to any surveys
		$data = [
			'id' => 999,
			'type' => 'singleChoice',
			'title' => 'Unlinked Question',
			'active' => true,
			'deleted' => false,
		];

		$entity = $this->surveyQuestionsTable->newDefaultEntity();
		$this->surveyQuestionsTable->patchEntity($entity, $data, ['accessibleFields' => ['id']]);

		$result = $this->surveyQuestionsTable->checkRules($entity, RulesChecker::DELETE);
		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\SurveyQuestionsTable::buildRules()
	 */
	public function testBuildRulesDeleteWithLinkedSurveys(): void {
		/** @var \Awyiss\Model\Entity\SurveyQuestion $entity */
		$entity = $this->surveyQuestionsTable->get(1);

		$result = $this->surveyQuestionsTable->checkRules($entity, RulesChecker::DELETE);

		$this->assertFalse($result);

		$errors = $entity->getErrors();
		$this->assertArrayHasKey('_general', $errors);
		$this->assertArrayHasKey('noLinkedSurveys', $errors['_general']);
		$this->assertSame('survey_questions::error_linked_surveys', $errors['_general']['noLinkedSurveys']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\SurveyQuestionsTable::newDefaultEntity()
	 */
	public function testNewDefaultEntity(): void {
		/** @var \Awyiss\Model\Entity\SurveyQuestion $entity */
		$entity = $this->surveyQuestionsTable->newDefaultEntity();

		$this->assertInstanceOf(SurveyQuestion::class, $entity);
		$this->assertTrue($entity->isNew());

		// Test default values
		$this->assertSame(QuestionType::SingleChoice, $entity->type);
		$this->assertNull($entity->title);
		$this->assertNull($entity->subtitle);
		$this->assertNull($entity->text);
		$this->assertTrue($entity->active);
		$this->assertFalse($entity->deleted);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\SurveyQuestionsTable::newDefaultEntity()
	 */
	public function testNewDefaultEntityWithData(): void {
		$additionalData = [
			'type' => 'multipleChoice',
			'title' => 'Custom Question',
			'subtitle' => 'Custom subtitle',
			'text' => 'Custom question text',
			'active' => false,
			'deleted' => true,
		];

		$entity = $this->surveyQuestionsTable->newDefaultEntity($additionalData);

		$this->assertInstanceOf(SurveyQuestion::class, $entity);
		$this->assertTrue($entity->isNew());

		$this->assertSame(QuestionType::MultiChoice, $entity->type);
		$this->assertSame('Custom Question', $entity->title);
		$this->assertSame('Custom subtitle', $entity->subtitle);
		$this->assertSame('Custom question text', $entity->text);
		$this->assertFalse($entity->active);
		$this->assertTrue($entity->deleted);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\SurveyQuestionsTable::initializeSchema()
	 */
	public function testInitializeSchemaTypeColumn(): void {
		$schema = $this->surveyQuestionsTable->getSchema();

		// Test that type column is configured as an enum type
		$this->assertSame('enum-awyiss-model-enum-survey-questiontype', $schema->getColumnType('type'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\SurveyQuestionsTable::$translate
	 */
	public function testTranslateBehavior(): void {
		$this->assertTrue($this->surveyQuestionsTable->hasBehavior('Translate'));

		$config = $this->surveyQuestionsTable->getBehavior('Translate')->getConfig();

		$this->assertSame(Awyiss::REALM_FRONTEND, $config['realm']);

		$this->assertIsArray($config['fields']);
		$this->assertSame(['title', 'subtitle', 'text'], $config['fields']);
	}
}
