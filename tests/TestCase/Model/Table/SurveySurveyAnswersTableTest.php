<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Model\Table;


use Awyiss\Awyiss;
use Awyiss\Model\Entity\SurveySurveyAnswer;
use Awyiss\Model\Enum\Survey\NextAction;
use Awyiss\Model\Table\SurveySurveyAnswersTable;
use Awyiss\ORM\Association\BelongsTo;
use Awyiss\ORM\Association\HasMany;
use Awyiss\ORM\Association\HasOne;
use Awyiss\Test\TestSuite\TestCase;
use Awyiss\Validation\Validator;
use Cake\Datasource\FactoryLocator;


/**
 * SurveySurveyAnswersTable Test Case
 *
 * @see \Awyiss\Model\Table\SurveySurveyAnswersTable
 */
class SurveySurveyAnswersTableTest extends TestCase {
	/**
	 * @var \Awyiss\Model\Table\SurveySurveyAnswersTable
	 */
	protected SurveySurveyAnswersTable $surveySurveyAnswersTable;


	/**
	 * @inheritDoc
	 */
	protected function setUp(): void {
		parent::setUp();

		/** @noinspection PhpFieldAssignmentTypeMismatchInspection */
		$this->surveySurveyAnswersTable = FactoryLocator::get('Table')->get('SurveySurveyAnswers');
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\SurveySurveyAnswersTable::ATTRIBUTABLE
	 */
	public function testAttributableConstant(): void {
		$this->assertFalse($this->surveySurveyAnswersTable::ATTRIBUTABLE);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\SurveySurveyAnswersTable::TABLE
	 */
	public function testTableConstant(): void {
		$this->assertEquals('survey_survey_answers', $this->surveySurveyAnswersTable::TABLE);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\SurveySurveyAnswersTable::initializeAssociations()
	 */
	public function testInitializeAssociations(): void {
		$this->assertCount(10, $this->surveySurveyAnswersTable->associations()->keys());

		$this->assertTrue($this->surveySurveyAnswersTable->hasAssociation('SurveyAnswers'));
		$surveyAnswersAssociation = $this->surveySurveyAnswersTable->getAssociation('SurveyAnswers');
		$this->assertInstanceOf(BelongsTo::class, $surveyAnswersAssociation);
		$this->assertSame('surveyAnswerId', $surveyAnswersAssociation->getForeignKey());
		$this->assertSame('INNER', $surveyAnswersAssociation->getJoinType());

		$this->assertTrue($this->surveySurveyAnswersTable->hasAssociation('SurveySurveyQuestions'));
		$surveySurveyQuestionsAssociation = $this->surveySurveyAnswersTable->getAssociation('SurveySurveyQuestions');
		$this->assertInstanceOf(BelongsTo::class, $surveySurveyQuestionsAssociation);
		$this->assertSame('surveySurveyQuestionId', $surveySurveyQuestionsAssociation->getForeignKey());
		$this->assertSame('INNER', $surveySurveyQuestionsAssociation->getJoinType());

		// 'MediaAssignments' must also exist
		$this->assertTrue($this->surveySurveyAnswersTable->hasAssociation('MediaAssignments'));
		$mediaAssignmentsAssociation = $this->surveySurveyAnswersTable->getAssociation('MediaAssignments');
		$this->assertInstanceOf(HasMany::class, $mediaAssignmentsAssociation);
		$this->assertTrue($mediaAssignmentsAssociation->getCascadeCallbacks());
		$this->assertTrue($mediaAssignmentsAssociation->getDependent());

		// 'CreatedByUser' must also exist
		$this->assertTrue($this->surveySurveyAnswersTable->hasAssociation('CreatedByUser'));
		$createdByUserAssociation = $this->surveySurveyAnswersTable->getAssociation('CreatedByUser');
		$this->assertInstanceOf(BelongsTo::class, $createdByUserAssociation);
		$this->assertFalse($createdByUserAssociation->getCascadeCallbacks());
		$this->assertFalse($createdByUserAssociation->getDependent());

		// 'ChangedByUser' must also exist
		$this->assertTrue($this->surveySurveyAnswersTable->hasAssociation('ChangedByUser'));
		$changedByUserAssociation = $this->surveySurveyAnswersTable->getAssociation('ChangedByUser');
		$this->assertInstanceOf(BelongsTo::class, $changedByUserAssociation);
		$this->assertFalse($changedByUserAssociation->getCascadeCallbacks());
		$this->assertFalse($changedByUserAssociation->getDependent());

		// 'DeletedByUser' must also exist
		$this->assertTrue($this->surveySurveyAnswersTable->hasAssociation('DeletedByUser'));
		$deletedByUserAssociation = $this->surveySurveyAnswersTable->getAssociation('DeletedByUser');
		$this->assertInstanceOf(BelongsTo::class, $deletedByUserAssociation);
		$this->assertFalse($deletedByUserAssociation->getCascadeCallbacks());
		$this->assertFalse($deletedByUserAssociation->getDependent());

		// 'SurveySurveyAnswers_title_translation' must also exist
		$this->assertTrue($this->surveySurveyAnswersTable->hasAssociation('SurveySurveyAnswers_title_translation'));
		$titleTranslationAssociation = $this->surveySurveyAnswersTable->getAssociation('SurveySurveyAnswers_title_translation');
		$this->assertInstanceOf(HasOne::class, $titleTranslationAssociation);
		$this->assertFalse($titleTranslationAssociation->getCascadeCallbacks());
		$this->assertFalse($titleTranslationAssociation->getDependent());

		// 'SurveySurveyAnswers_title_translation' must also exist
		$this->assertTrue($this->surveySurveyAnswersTable->hasAssociation('SurveySurveyAnswers_subtitle_translation'));
		$subtitleTranslationAssociation = $this->surveySurveyAnswersTable->getAssociation('SurveySurveyAnswers_subtitle_translation');
		$this->assertInstanceOf(HasOne::class, $subtitleTranslationAssociation);
		$this->assertFalse($subtitleTranslationAssociation->getCascadeCallbacks());
		$this->assertFalse($subtitleTranslationAssociation->getDependent());

		// 'SurveySurveyAnswers_title_translation' must also exist
		$this->assertTrue($this->surveySurveyAnswersTable->hasAssociation('SurveySurveyAnswers_text_translation'));
		$textTranslationAssociation = $this->surveySurveyAnswersTable->getAssociation('SurveySurveyAnswers_text_translation');
		$this->assertInstanceOf(HasOne::class, $textTranslationAssociation);
		$this->assertFalse($textTranslationAssociation->getCascadeCallbacks());
		$this->assertFalse($textTranslationAssociation->getDependent());

		// 'I18n' must also exist
		$this->assertTrue($this->surveySurveyAnswersTable->hasAssociation('I18n'));
		$i18nAssociation = $this->surveySurveyAnswersTable->getAssociation('I18n');
		$this->assertInstanceOf(HasMany::class, $i18nAssociation);
		$this->assertFalse($i18nAssociation->getCascadeCallbacks());
		$this->assertTrue($i18nAssociation->getDependent());
		$this->assertEquals('append', $i18nAssociation->getSaveStrategy());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\SurveySurveyAnswersTable::validationDefault()
	 */
	public function testValidationDefault(): void {
		$validator = new Validator();
		$result = $this->surveySurveyAnswersTable->validationDefault($validator);

		$this->assertInstanceOf(Validator::class, $result);
		$this->assertSame('SurveySurveyAnswers', $result->getI18nDomain());

		// Test that all fields have validation rules
		$this->assertTrue($result->hasField('id'));
		$this->assertTrue($result->hasField('surveyAnswerId'));
		$this->assertTrue($result->hasField('surveySurveyQuestionId'));
		$this->assertTrue($result->hasField('title'));
		$this->assertTrue($result->hasField('subtitle'));
		$this->assertTrue($result->hasField('text'));
		$this->assertTrue($result->hasField('nextAction'));
		$this->assertTrue($result->hasField('nextActionTarget'));
		$this->assertTrue($result->hasField('systemOrder'));
		$this->assertTrue($result->hasField('active'));
		$this->assertTrue($result->hasField('deleted'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\SurveySurveyAnswersTable::validationDefault()
	 */
	public function testEntityValidationSuccess(): void {
		$data = [
			'surveyAnswerId' => 1,
			'surveySurveyQuestionId' => 1,
			'title' => 'Test Answer',
			'subtitle' => 'Test Subtitle',
			'text' => 'This is a test answer text',
			'nextAction' => NextAction::ShowForm,
			'nextActionTarget' => 'question_2',
			'systemOrder' => 1,
			'active' => true,
			'deleted' => false,
		];

		$entity = $this->surveySurveyAnswersTable->newDefaultEntity($data);
		$errors = $entity->getErrors();

		$this->assertEmpty($errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\SurveySurveyAnswersTable::validationDefault()
	 */
	public function testEntityValidationEmptyRequiredFields(): void {
		$data = [
			'subtitle' => 'Test Subtitle',
		];

		$entity = $this->surveySurveyAnswersTable->newDefaultEntity();
		$this->surveySurveyAnswersTable->patchEntity($entity, $data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('surveyAnswerId', $errors);
		$this->assertArrayNotHasKey('surveySurveyQuestionId', $errors);
		$this->assertArrayHasKey('systemOrder', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\SurveySurveyAnswersTable::validationDefault()
	 */
	public function testEntityValidationInvalidTypes(): void {
		$data = [
			'id' => 'not_an_integer',
			'surveyAnswerId' => 'not_an_integer',
			'surveySurveyQuestionId' => 'not_an_integer',
			'title' => true,
			'subtitle' => true,
			'text' => true,
			'nextAction' => true,
			'nextActionTarget' => true,
			'systemOrder' => 'not_an_integer',
			'active' => 'not_a_boolean',
			'deleted' => 'not_a_boolean',
		];

		$entity = $this->surveySurveyAnswersTable->newDefaultEntity();
		$this->surveySurveyAnswersTable->patchEntity($entity, $data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('id', $errors);
		$this->assertArrayHasKey('surveyAnswerId', $errors);
		$this->assertArrayHasKey('surveySurveyQuestionId', $errors);
		$this->assertArrayHasKey('title', $errors);
		$this->assertArrayHasKey('subtitle', $errors);
		$this->assertArrayHasKey('text', $errors);
		$this->assertArrayHasKey('nextAction', $errors);
		$this->assertArrayHasKey('nextActionTarget', $errors);
		$this->assertArrayHasKey('systemOrder', $errors);
		$this->assertArrayHasKey('active', $errors);
		$this->assertArrayHasKey('deleted', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\SurveySurveyAnswersTable::validationDefault()
	 */
	public function testEntityValidationFieldLength(): void {
		$data = [
			'id' => 123456789123, // exceeds 11 char limit
			'surveyAnswerId' => 123456789123, // exceeds 11 char limit
			'surveySurveyQuestionId' => 123456789123, // exceeds 11 char limit
			'title' => str_repeat('a', 256), // exceeds 255 char limit
			'subtitle' => str_repeat('b', 256), // exceeds 255 char limit
			'text' => str_repeat('c', 65536), // exceeds 65535 byte limit
			'nextAction' => str_repeat('d', 21), // exceeds 20 char limit
			'nextActionTarget' => str_repeat('e', 21), // exceeds 20 char limit
		];

		$entity = $this->surveySurveyAnswersTable->newDefaultEntity();
		$this->surveySurveyAnswersTable->patchEntity($entity, $data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('id', $errors);
		$this->assertArrayHasKey('surveyAnswerId', $errors);
		$this->assertArrayHasKey('surveySurveyQuestionId', $errors);
		$this->assertArrayHasKey('title', $errors);
		$this->assertArrayHasKey('subtitle', $errors);
		$this->assertArrayHasKey('text', $errors);

		$this->assertArrayHasKey('nextAction', $errors);
		$this->assertArrayHasKey('enum', $errors['nextAction']);
		$this->assertSame('SurveySurveyAnswers::error_enum', $errors['nextAction']['enum']);

		$this->assertArrayHasKey('nextActionTarget', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\SurveySurveyAnswersTable::validationDefault()
	 */
	public function testEntityValidationTitleBlank(): void {
		$data = [
			'surveyAnswerId' => 1,
			'surveySurveyQuestionId' => 1,
			'title' => '   ', // Only whitespace
		];

		$entity = $this->surveySurveyAnswersTable->newDefaultEntity();
		$this->surveySurveyAnswersTable->patchEntity($entity, $data);
		$errors = $entity->getErrors();

		$this->assertArrayNotHasKey('title', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\SurveySurveyAnswersTable::validationDefault()
	 */
	public function testEntityValidationSubtitleBlank(): void {
		$data = [
			'surveyAnswerId' => 1,
			'surveySurveyQuestionId' => 1,
			'title' => 'Valid Title',
			'subtitle' => '   ', // Only whitespace - but allowed to be empty
		];

		$entity = $this->surveySurveyAnswersTable->newDefaultEntity();
		$this->surveySurveyAnswersTable->patchEntity($entity, $data);
		$errors = $entity->getErrors();

		$this->assertArrayNotHasKey('subtitle', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\SurveySurveyAnswersTable::validationDefault()
	 */
	public function testEntityValidationOptionalFields(): void {
		$data = [
			'surveyAnswerId' => 1,
			'surveySurveyQuestionId' => 1,
			'systemOrder' => 0,
			// title is optional (allowEmptyString)
			// subtitle is optional (allowEmptyString)
			// text is optional (allowEmptyString)
		];

		$entity = $this->surveySurveyAnswersTable->newDefaultEntity();
		$this->surveySurveyAnswersTable->patchEntity($entity, $data);
		$errors = $entity->getErrors();

		$this->assertArrayNotHasKey('title', $errors);
		$this->assertArrayNotHasKey('subtitle', $errors);
		$this->assertArrayNotHasKey('text', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\SurveySurveyAnswersTable::validationDefault()
	 */
	public function testEntityValidationNextActionTargetBlank(): void {
		$data = [
			'surveyAnswerId' => 1,
			'surveySurveyQuestionId' => 1,
			'title' => 'Test Answer',
			'nextAction' => NextAction::ShowFormAndSave,
			'nextActionTarget' => '   ', // Only whitespace
		];

		$entity = $this->surveySurveyAnswersTable->newDefaultEntity();
		$this->surveySurveyAnswersTable->patchEntity($entity, $data);
		$errors = $entity->getErrors();

		$this->assertArrayNotHasKey('nextActionTarget', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\SurveySurveyAnswersTable::buildRules()
	 */
	public function testBuildRulesValidSurveyAnswerId(): void {
		$data = [
			'surveyAnswerId' => 1, // Existing survey answer
			'surveySurveyQuestionId' => 2,
			'systemOrder' => 0,
		];

		$entity = $this->surveySurveyAnswersTable->newDefaultEntity();
		$this->surveySurveyAnswersTable->patchEntity($entity, $data);

		$result = $this->surveySurveyAnswersTable->checkRules($entity);
		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\SurveySurveyAnswersTable::buildRules()
	 */
	public function testBuildRulesInvalidSurveyAnswerId(): void {
		$data = [
			'surveyAnswerId' => 99999, // Non-existing survey answer
			'surveySurveyQuestionId' => 2,
			'systemOrder' => 0,
		];

		$entity = $this->surveySurveyAnswersTable->newDefaultEntity();
		$this->surveySurveyAnswersTable->patchEntity($entity, $data);

		$result = $this->surveySurveyAnswersTable->checkRules($entity);

		$this->assertFalse($result);

		$errors = $entity->getErrors();
		$this->assertArrayHasKey('surveyAnswerId', $errors);
		$this->assertArrayHasKey('validSurveyAnswerId', $errors['surveyAnswerId']);
		$this->assertSame('Surveys::error_valid_survey_answer_id', $errors['surveyAnswerId']['validSurveyAnswerId']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\SurveySurveyAnswersTable::buildRules()
	 */
	public function testBuildRulesValidSurveySurveyQuestionId(): void {
		$data = [
			'surveyAnswerId' => 1,
			'surveySurveyQuestionId' => 2, // Existing survey survey question
			'systemOrder' => 0,
		];

		$entity = $this->surveySurveyAnswersTable->newDefaultEntity();
		$this->surveySurveyAnswersTable->patchEntity($entity, $data);

		$result = $this->surveySurveyAnswersTable->checkRules($entity);
		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\SurveySurveyAnswersTable::buildRules()
	 */
	public function testBuildRulesInvalidSurveySurveyQuestionId(): void {
		$data = [
			'surveyAnswerId' => 1,
			'surveySurveyQuestionId' => 99999, // Non-existing survey survey question
			'systemOrder' => 0,
		];

		$entity = $this->surveySurveyAnswersTable->newDefaultEntity();
		$this->surveySurveyAnswersTable->patchEntity($entity, $data);

		$result = $this->surveySurveyAnswersTable->checkRules($entity);

		$this->assertFalse($result);

		$errors = $entity->getErrors();
		$this->assertArrayHasKey('surveySurveyQuestionId', $errors);
		$this->assertArrayHasKey('validSurveySurveyQuestionId', $errors['surveySurveyQuestionId']);
		$this->assertSame('Surveys::error_valid_survey_survey_question_id', $errors['surveySurveyQuestionId']['validSurveySurveyQuestionId']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\SurveySurveyAnswersTable::buildRules()
	 */
	public function testBuildRulesInvalidSurveyAnswerIdForSurveySurveyQuestionId(): void {
		$data = [
			'surveyAnswerId' => 4, // Answer 4 does not exist for question 1 (proxies by surveySurveyQuestionId 2)
			'surveySurveyQuestionId' => 2,
			'systemOrder' => 0,
		];

		$entity = $this->surveySurveyAnswersTable->newDefaultEntity();
		$this->surveySurveyAnswersTable->patchEntity($entity, $data);

		$result = $this->surveySurveyAnswersTable->checkRules($entity);

		$this->assertFalse($result);

		$errors = $entity->getErrors();
		$this->assertArrayHasKey('surveyAnswerId', $errors);
		$this->assertArrayHasKey('validSurveyAnswerId', $errors['surveyAnswerId']);
		$this->assertSame('Surveys::error_valid_survey_answer_id', $errors['surveyAnswerId']['validSurveyAnswerId']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\SurveySurveyAnswersTable::buildRules()
	 */
	public function testBuildRulesValidNextAction(): void {
		$data = [
			'surveyAnswerId' => 1,
			'surveySurveyQuestionId' => 2,
			'nextAction' => NextAction::ShowForm,  // Patching entity will convert to enum
			'systemOrder' => 0,
		];

		$entity = $this->surveySurveyAnswersTable->newDefaultEntity();

		$this->surveySurveyAnswersTable->patchEntity($entity, $data);

		$this->assertSame(NextAction::ShowForm, $entity->nextAction);

		$result = $this->surveySurveyAnswersTable->checkRules($entity);

		$this->assertTrue($result);

		$entity->nextAction = NextAction::ShowFormAndSave;

		$result = $this->surveySurveyAnswersTable->checkRules($entity);

		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\SurveySurveyQuestionsTable::buildRules()
	 */
	public function testBuildRulesEmptyNextAction(): void {
		$data = [
			'surveyAnswerId' => 1,
			'surveySurveyQuestionId' => 2,
			'nextAction' => null,
			'systemOrder' => 0,
		];

		$entity = $this->surveySurveyAnswersTable->newDefaultEntity();
		$this->surveySurveyAnswersTable->patchEntity($entity, $data);

		$result = $this->surveySurveyAnswersTable->checkRules($entity);
		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\SurveySurveyAnswersTable::buildRules()
	 */
	public function testBuildRulesInvalidNextAction(): void {
		$data = [
			'surveyAnswerId' => 1,
			'surveySurveyQuestionId' => 2,
			'nextAction' => 'invalid_action', // Patching entity will convert to enum but fail here
			'systemOrder' => 0,
		];

		$entity = $this->surveySurveyAnswersTable->newDefaultEntity();

		$this->surveySurveyAnswersTable->patchEntity($entity, $data);

		$this->assertNull($entity->nextAction);

		$result = $this->surveySurveyAnswersTable->checkRules($entity);

		$this->assertTrue($result);

		$entity->nextAction = 'invalid_action'; // Setting a value directly will not convert to enum

		$result = $this->surveySurveyAnswersTable->checkRules($entity);

		$this->assertFalse($result);

		$errors = $entity->getErrors();
		$this->assertArrayHasKey('nextAction', $errors);
		$this->assertArrayHasKey('validNextAction', $errors['nextAction']);
		$this->assertSame('SurveySurveyAnswers::error_valid_next_action', $errors['nextAction']['validNextAction']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\SurveySurveyAnswersTable::newDefaultEntity()
	 */
	public function testNewDefaultEntity(): void {
		/** @var \Awyiss\Model\Entity\SurveySurveyAnswer $entity */
		$entity = $this->surveySurveyAnswersTable->newDefaultEntity();

		$this->assertInstanceOf(SurveySurveyAnswer::class, $entity);
		$this->assertTrue($entity->isNew());

		// Test default values
		$this->assertNull($entity->surveyAnswerId);
		$this->assertNull($entity->surveySurveyQuestionId);
		$this->assertNull($entity->title);
		$this->assertNull($entity->subtitle);
		$this->assertNull($entity->text);
		$this->assertNull($entity->nextAction);
		$this->assertNull($entity->nextActionTarget);
		$this->assertSame(0, $entity->systemOrder);
		$this->assertTrue($entity->active);
		$this->assertFalse($entity->deleted);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\SurveySurveyAnswersTable::newDefaultEntity()
	 */
	public function testNewDefaultEntityWithData(): void {
		$additionalData = [
			'surveyAnswerId' => 2,
			'surveySurveyQuestionId' => 3,
			'title' => 'Custom Answer Title',
			'subtitle' => 'Custom Subtitle',
			'text' => 'Custom answer text content',
			'nextAction' => NextAction::ShowForm,
			'nextActionTarget' => 'question_5',
			'systemOrder' => 5,
			'active' => false,
			'deleted' => true,
		];

		$entity = $this->surveySurveyAnswersTable->newDefaultEntity($additionalData);

		$this->assertInstanceOf(SurveySurveyAnswer::class, $entity);
		$this->assertTrue($entity->isNew());

		$this->assertSame(2, $entity->surveyAnswerId);
		$this->assertSame(3, $entity->surveySurveyQuestionId);
		$this->assertSame('Custom Answer Title', $entity->title);
		$this->assertSame('Custom Subtitle', $entity->subtitle);
		$this->assertSame('Custom answer text content', $entity->text);
		$this->assertSame(NextAction::ShowForm, $entity->nextAction);
		$this->assertSame('question_5', $entity->nextActionTarget);
		$this->assertSame(5, $entity->systemOrder);
		$this->assertFalse($entity->active);
		$this->assertTrue($entity->deleted);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\SurveySurveyAnswersTable::initializeSchema()
	 */
	public function testInitializeSchemaNextActionColumn(): void {
		$schema = $this->surveySurveyAnswersTable->getSchema();

		// Test that nextAction column is configured as an enum type
		$this->assertSame('enum-awyiss-model-enum-survey-nextaction', $schema->getColumnType('nextAction'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\SurveySurveyAnswersTable::$systemOrder
	 */
	public function testSystemOrderBehavior(): void {
		$this->assertTrue($this->surveySurveyAnswersTable->hasBehavior('SystemOrder'));

		$config = $this->surveySurveyAnswersTable->getBehavior('SystemOrder')->getConfig();

		$this->assertSame(['surveySurveyQuestionId'], $config['relatedColumns']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\SurveySurveyAnswersTable::$translate
	 */
	public function testTranslateBehavior(): void {
		$this->assertTrue($this->surveySurveyAnswersTable->hasBehavior('Translate'));

		$config = $this->surveySurveyAnswersTable->getBehavior('Translate')->getConfig();

		$this->assertSame(Awyiss::REALM_FRONTEND, $config['realm']);

		$this->assertIsArray($config['fields']);
		$this->assertSame(['title', 'subtitle', 'text'], $config['fields']);
	}
}
