<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Model\Table;


use Awyiss\Awyiss;
use Awyiss\Model\Entity\SurveySurveyQuestion;
use Awyiss\Model\Enum\Survey\NextAction;
use Awyiss\Model\Table\SurveySurveyQuestionsTable;
use Awyiss\ORM\Association\BelongsTo;
use Awyiss\ORM\Association\HasMany;
use Awyiss\ORM\Association\HasOne;
use Awyiss\Test\TestSuite\TestCase;
use Awyiss\Validation\Validator;
use Cake\Datasource\FactoryLocator;


/**
 * SurveySurveyQuestionsTable Test Case
 *
 * @see \Awyiss\Model\Table\SurveySurveyQuestionsTable
 */
class SurveySurveyQuestionsTableTest extends TestCase {
	/**
	 * @var \Awyiss\Model\Table\SurveySurveyQuestionsTable
	 */
	protected SurveySurveyQuestionsTable $surveySurveyQuestionsTable;


	/**
	 * @inheritDoc
	 */
	protected function setUp(): void {
		parent::setUp();

		/** @noinspection PhpFieldAssignmentTypeMismatchInspection */
		$this->surveySurveyQuestionsTable = FactoryLocator::get('Table')->get('SurveySurveyQuestions');
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\SurveySurveyQuestionsTable::ATTRIBUTABLE
	 */
	public function testAttributableConstant(): void {
		$this->assertFalse($this->surveySurveyQuestionsTable::ATTRIBUTABLE);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\SurveySurveyQuestionsTable::TABLE
	 */
	public function testTableConstant(): void {
		$this->assertEquals('survey_survey_questions', $this->surveySurveyQuestionsTable::TABLE);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\SurveySurveyQuestionsTable::initializeAssociations()
	 */
	public function testInitializeAssociations(): void {
		$this->assertCount(14, $this->surveySurveyQuestionsTable->associations()->keys());

		$this->assertTrue($this->surveySurveyQuestionsTable->hasAssociation('Surveys'));
		$surveysAssociation = $this->surveySurveyQuestionsTable->getAssociation('Surveys');
		$this->assertInstanceOf(BelongsTo::class, $surveysAssociation);
		$this->assertSame('survey_id', $surveysAssociation->getForeignKey());
		$this->assertSame('INNER', $surveysAssociation->getJoinType());

		$this->assertTrue($this->surveySurveyQuestionsTable->hasAssociation('SurveyQuestions'));
		$surveyQuestionsAssociation = $this->surveySurveyQuestionsTable->getAssociation('SurveyQuestions');
		$this->assertInstanceOf(BelongsTo::class, $surveyQuestionsAssociation);
		$this->assertSame('survey_question_id', $surveyQuestionsAssociation->getForeignKey());
		$this->assertSame('INNER', $surveyQuestionsAssociation->getJoinType());

		$this->assertTrue($this->surveySurveyQuestionsTable->hasAssociation('SurveySurveyAnswers'));
		$surveySurveyAnswersAssociation = $this->surveySurveyQuestionsTable->getAssociation('SurveySurveyAnswers');
		$this->assertInstanceOf(HasMany::class, $surveySurveyAnswersAssociation);
		$this->assertSame('survey_survey_question_id', $surveySurveyAnswersAssociation->getForeignKey());
		$this->assertTrue($surveySurveyAnswersAssociation->getCascadeCallbacks());
		$this->assertTrue($surveySurveyAnswersAssociation->getDependent());
		$this->assertSame('replace', $surveySurveyAnswersAssociation->getSaveStrategy());

		// 'CustomerGroupAccessSettings' must also exist
		$this->assertTrue($this->surveySurveyQuestionsTable->hasAssociation('CustomerGroupAccessSettings'));
		$customerGroupAccessSettingsAssociation = $this->surveySurveyQuestionsTable->getAssociation('CustomerGroupAccessSettings');
		$this->assertInstanceOf(HasOne::class, $customerGroupAccessSettingsAssociation);
		$this->assertTrue($customerGroupAccessSettingsAssociation->getCascadeCallbacks());
		$this->assertTrue($customerGroupAccessSettingsAssociation->getDependent());

		// 'CustomerGroupAssignments' must also exist
		$this->assertTrue($this->surveySurveyQuestionsTable->hasAssociation('CustomerGroupAssignments'));
		$customerGroupAssignmentsAssociation = $this->surveySurveyQuestionsTable->getAssociation('CustomerGroupAssignments');
		$this->assertInstanceOf(HasMany::class, $customerGroupAssignmentsAssociation);
		$this->assertTrue($customerGroupAssignmentsAssociation->getCascadeCallbacks());
		$this->assertTrue($customerGroupAssignmentsAssociation->getDependent());

		// 'MediaAssignments' must also exist
		$this->assertTrue($this->surveySurveyQuestionsTable->hasAssociation('MediaAssignments'));
		$mediaAssignmentsAssociation = $this->surveySurveyQuestionsTable->getAssociation('MediaAssignments');
		$this->assertInstanceOf(HasMany::class, $mediaAssignmentsAssociation);
		$this->assertTrue($mediaAssignmentsAssociation->getCascadeCallbacks());
		$this->assertTrue($mediaAssignmentsAssociation->getDependent());

		// 'CreatedByUser' must also exist
		$this->assertTrue($this->surveySurveyQuestionsTable->hasAssociation('CreatedByUser'));
		$createdByUserAssociation = $this->surveySurveyQuestionsTable->getAssociation('CreatedByUser');
		$this->assertInstanceOf(BelongsTo::class, $createdByUserAssociation);
		$this->assertFalse($createdByUserAssociation->getCascadeCallbacks());
		$this->assertFalse($createdByUserAssociation->getDependent());

		// 'ChangedByUser' must also exist
		$this->assertTrue($this->surveySurveyQuestionsTable->hasAssociation('ChangedByUser'));
		$changedByUserAssociation = $this->surveySurveyQuestionsTable->getAssociation('ChangedByUser');
		$this->assertInstanceOf(BelongsTo::class, $changedByUserAssociation);
		$this->assertFalse($changedByUserAssociation->getCascadeCallbacks());
		$this->assertFalse($changedByUserAssociation->getDependent());

		// 'DeletedByUser' must also exist
		$this->assertTrue($this->surveySurveyQuestionsTable->hasAssociation('DeletedByUser'));
		$deletedByUserAssociation = $this->surveySurveyQuestionsTable->getAssociation('DeletedByUser');
		$this->assertInstanceOf(BelongsTo::class, $deletedByUserAssociation);
		$this->assertFalse($deletedByUserAssociation->getCascadeCallbacks());
		$this->assertFalse($deletedByUserAssociation->getDependent());

		// 'SurveySurveyQuestions_title_translation' must also exist
		$this->assertTrue($this->surveySurveyQuestionsTable->hasAssociation('SurveySurveyQuestions_title_translation'));
		$titleTranslationAssociation = $this->surveySurveyQuestionsTable->getAssociation('SurveySurveyQuestions_title_translation');
		$this->assertInstanceOf(HasOne::class, $titleTranslationAssociation);
		$this->assertFalse($titleTranslationAssociation->getCascadeCallbacks());
		$this->assertFalse($titleTranslationAssociation->getDependent());

		// 'SurveySurveyQuestions_title_translation' must also exist
		$this->assertTrue($this->surveySurveyQuestionsTable->hasAssociation('SurveySurveyQuestions_subtitle_translation'));
		$subtitleTranslationAssociation = $this->surveySurveyQuestionsTable->getAssociation('SurveySurveyQuestions_subtitle_translation');
		$this->assertInstanceOf(HasOne::class, $subtitleTranslationAssociation);
		$this->assertFalse($subtitleTranslationAssociation->getCascadeCallbacks());
		$this->assertFalse($subtitleTranslationAssociation->getDependent());

		// 'SurveySurveyQuestions_title_translation' must also exist
		$this->assertTrue($this->surveySurveyQuestionsTable->hasAssociation('SurveySurveyQuestions_text_translation'));
		$textTranslationAssociation = $this->surveySurveyQuestionsTable->getAssociation('SurveySurveyQuestions_text_translation');
		$this->assertInstanceOf(HasOne::class, $textTranslationAssociation);
		$this->assertFalse($textTranslationAssociation->getCascadeCallbacks());
		$this->assertFalse($textTranslationAssociation->getDependent());

		// 'SurveySurveyQuestions_title_translation' must also exist
		$this->assertTrue($this->surveySurveyQuestionsTable->hasAssociation('SurveySurveyQuestions_customAnswerTitle_translation'));
		$titleTranslationAssociation = $this->surveySurveyQuestionsTable->getAssociation('SurveySurveyQuestions_customAnswerTitle_translation');
		$this->assertInstanceOf(HasOne::class, $titleTranslationAssociation);
		$this->assertFalse($titleTranslationAssociation->getCascadeCallbacks());
		$this->assertFalse($titleTranslationAssociation->getDependent());

		// 'I18n' must also exist
		$this->assertTrue($this->surveySurveyQuestionsTable->hasAssociation('I18n'));
		$i18nAssociation = $this->surveySurveyQuestionsTable->getAssociation('I18n');
		$this->assertInstanceOf(HasMany::class, $i18nAssociation);
		$this->assertFalse($i18nAssociation->getCascadeCallbacks());
		$this->assertTrue($i18nAssociation->getDependent());
		$this->assertEquals('append', $i18nAssociation->getSaveStrategy());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\SurveySurveyQuestionsTable::validationDefault()
	 */
	public function testValidationDefault(): void {
		$validator = new Validator();
		$result = $this->surveySurveyQuestionsTable->validationDefault($validator);

		$this->assertInstanceOf(Validator::class, $result);
		$this->assertSame('survey_survey_questions', $result->getI18nDomain());

		// Test required fields
		$this->assertTrue($result->hasField('identifier'));
		$this->assertSame('create', $result->field('identifier')->isPresenceRequired());

		// Test other fields
		$this->assertTrue($result->hasField('id'));
		$this->assertTrue($result->hasField('surveyId'));
		$this->assertTrue($result->hasField('surveyQuestionId'));
		$this->assertTrue($result->hasField('title'));
		$this->assertTrue($result->hasField('subtitle'));
		$this->assertTrue($result->hasField('text'));
		$this->assertTrue($result->hasField('nextAction'));
		$this->assertTrue($result->hasField('nextActionTarget'));
		$this->assertTrue($result->hasField('allowCustomAnswer'));
		$this->assertTrue($result->hasField('customAnswerTitle'));
		$this->assertTrue($result->hasField('systemOrder'));
		$this->assertTrue($result->hasField('active'));
		$this->assertTrue($result->hasField('deleted'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\SurveySurveyQuestionsTable::validationDefault()
	 */
	public function testEntityValidationSuccess(): void {
		$data = [
			'identifier' => 'test_survey_question',
			'surveyId' => 1,
			'surveyQuestionId' => 1,
			'title' => 'Test Survey Question',
			'subtitle' => 'Test Subtitle',
			'text' => 'This is a test question text',
			'nextAction' => NextAction::NextQuestion,
			'nextActionTarget' => 'question_2',
			'allowCustomAnswer' => true,
			'customAnswerTitle' => 'Other (please specify)',
			'systemOrder' => 1,
			'active' => true,
			'deleted' => false,
		];

		$entity = $this->surveySurveyQuestionsTable->newDefaultEntity($data);
		$errors = $entity->getErrors();

		$this->assertEmpty($errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\SurveySurveyQuestionsTable::validationDefault()
	 */
	public function testEntityValidationMissingRequired(): void {
		$data = [
			'surveyId' => 1,
		];

		$entity = $this->surveySurveyQuestionsTable->newDefaultEntity();
		$this->surveySurveyQuestionsTable->patchEntity($entity, $data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('identifier', $errors);
		$this->assertArrayHasKey('_required', $errors['identifier']);
		$this->assertSame('survey_survey_questions::error_required', $errors['identifier']['_required']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\SurveySurveyQuestionsTable::validationDefault()
	 */
	public function testEntityValidationInvalidTypes(): void {
		$data = [
			'id' => 'not_an_integer',
			'identifier' => true,
			'surveyId' => 'not_an_integer',
			'surveyQuestionId' => 'not_an_integer',
			'title' => true,
			'subtitle' => true,
			'text' => true,
			'nextAction' => true,
			'nextActionTarget' => true,
			'allowCustomAnswer' => 'not_a_boolean',
			'customAnswerTitle' => true,
			'systemOrder' => 'not_an_integer',
			'active' => 'not_a_boolean',
			'deleted' => 'not_a_boolean',
		];

		$entity = $this->surveySurveyQuestionsTable->newDefaultEntity();
		$this->surveySurveyQuestionsTable->patchEntity($entity, $data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('id', $errors);
		$this->assertArrayHasKey('identifier', $errors);
		$this->assertArrayHasKey('surveyId', $errors);
		$this->assertArrayHasKey('surveyQuestionId', $errors);
		$this->assertArrayHasKey('title', $errors);
		$this->assertArrayHasKey('subtitle', $errors);
		$this->assertArrayHasKey('text', $errors);
		$this->assertArrayHasKey('nextAction', $errors);
		$this->assertArrayHasKey('nextActionTarget', $errors);
		$this->assertArrayHasKey('allowCustomAnswer', $errors);
		$this->assertArrayHasKey('customAnswerTitle', $errors);
		$this->assertArrayHasKey('systemOrder', $errors);
		$this->assertArrayHasKey('active', $errors);
		$this->assertArrayHasKey('deleted', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\SurveySurveyQuestionsTable::validationDefault()
	 */
	public function testEntityValidationFieldLength(): void {
		$data = [
			'id' => 123456789123, // exceeds 11 char limit
			'surveyId' => 123456789123, // exceeds 11 char limit
			'surveyQuestionId' => 123456789123, // exceeds 11 char limit
			'identifier' => '123456789', // exceeds 9 char limit
			'title' => str_repeat('a', 256), // exceeds 255 char limit
			'subtitle' => str_repeat('b', 256), // exceeds 255 char limit
			'text' => str_repeat('c', 65536), // exceeds 65535 byte limit
			'nextAction' => str_repeat('d', 21), // exceeds 20 char limit
			'nextActionTarget' => str_repeat('e', 21), // exceeds 20 char limit
			'customAnswerTitle' => str_repeat('f', 256), // exceeds 255 char limit
		];

		$entity = $this->surveySurveyQuestionsTable->newDefaultEntity();
		$this->surveySurveyQuestionsTable->patchEntity($entity, $data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('id', $errors);
		$this->assertArrayHasKey('surveyId', $errors);
		$this->assertArrayHasKey('surveyQuestionId', $errors);
		$this->assertArrayHasKey('title', $errors);
		$this->assertArrayHasKey('subtitle', $errors);
		$this->assertArrayHasKey('text', $errors);

		$this->assertArrayHasKey('nextAction', $errors);
		$this->assertArrayHasKey('enum', $errors['nextAction']);
		$this->assertSame('survey_survey_questions::error_enum', $errors['nextAction']['enum']);

		$this->assertArrayHasKey('nextActionTarget', $errors);
		$this->assertArrayHasKey('customAnswerTitle', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\SurveySurveyQuestionsTable::validationDefault()
	 */
	public function testEntityValidationEmptyRequiredFields(): void {
		$data = [
			'identifier' => 'test_question',
			'surveyId' => '', // Empty required field
			'surveyQuestionId' => '', // Empty required field
			'title' => '', // Empty required field
		];

		$entity = $this->surveySurveyQuestionsTable->newDefaultEntity();
		$this->surveySurveyQuestionsTable->patchEntity($entity, $data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('surveyId', $errors);
		$this->assertArrayHasKey('surveyQuestionId', $errors);
		$this->assertArrayHasKey('title', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\SurveySurveyQuestionsTable::validationDefault()
	 */
	public function testEntityValidationBlankFields(): void {
		$data = [
			'identifier' => 'test_question',
			'surveyId' => 1,
			'surveyQuestionId' => 1,
			'title' => '   ', // Only whitespace
			'subtitle' => '   ', // Only whitespace - but allowed to be empty
			'nextAction' => '   ', // Only whitespace
			'nextActionTarget' => '   ', // Only whitespace
			'customAnswerTitle' => '   ', // Only whitespace - but allowed to be empty
		];

		$entity = $this->surveySurveyQuestionsTable->newDefaultEntity();
		$this->surveySurveyQuestionsTable->patchEntity($entity, $data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('title', $errors);
		$this->assertArrayHasKey('notBlank', $errors['title']);

		$this->assertArrayHasKey('subtitle', $errors);
		$this->assertArrayHasKey('notBlank', $errors['subtitle']);

		$this->assertArrayHasKey('nextActionTarget', $errors);
		$this->assertArrayHasKey('notBlank', $errors['nextActionTarget']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\SurveySurveyQuestionsTable::validationDefault()
	 */
	public function testEntityValidationOptionalFields(): void {
		$data = [
			'identifier' => 'test_question',
			'surveyId' => 1,
			'surveyQuestionId' => 1,
			'title' => 'Test Question',
			// subtitle is optional (allowEmptyString)
			// text is optional (allowEmptyString)
			// customAnswerTitle is optional (allowEmptyString)
		];

		$entity = $this->surveySurveyQuestionsTable->newDefaultEntity();
		$this->surveySurveyQuestionsTable->patchEntity($entity, $data);
		$errors = $entity->getErrors();

		$this->assertArrayNotHasKey('subtitle', $errors);
		$this->assertArrayNotHasKey('text', $errors);
		$this->assertArrayNotHasKey('customAnswerTitle', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\SurveySurveyQuestionsTable::buildRules()
	 */
	public function testBuildRulesUniqueIdentifierPerSurvey(): void {
		// 8524de5e exists for survey 1, but is allowed for survey 2
		$data = [
			'identifier' => '8524de5e',
			'surveyId' => 2,
			'surveyQuestionId' => 1,
			'title' => 'Unique Question',
		];

		$entity = $this->surveySurveyQuestionsTable->newDefaultEntity();
		$this->surveySurveyQuestionsTable->patchEntity($entity, $data);

		$result = $this->surveySurveyQuestionsTable->checkRules($entity);
		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\SurveySurveyQuestionsTable::buildRules()
	 */
	public function testBuildRulesNonUniqueIdentifierPerSurvey(): void {
		// 8524de5e exists for survey 1
		$data = [
			'identifier' => '8524de5e',
			'surveyId' => 1,
			'surveyQuestionId' => 1,
			'title' => 'First Question',
		];

		// Try to create another with the same identifier and surveyId
		$entity = $this->surveySurveyQuestionsTable->newDefaultEntity();
		$this->surveySurveyQuestionsTable->patchEntity($entity, $data);

		$result = $this->surveySurveyQuestionsTable->checkRules($entity);

		$this->assertFalse($result);

		$errors = $entity->getErrors();
		$this->assertArrayHasKey('identifier', $errors);
		$this->assertArrayHasKey('identifierUnique', $errors['identifier']);
		$this->assertSame('surveys::error_identifier_unique', $errors['identifier']['identifierUnique']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\SurveySurveyQuestionsTable::buildRules()
	 */
	public function testBuildRulesValidSurveyId(): void {
		$data = [
			'identifier' => '87d4f766',
			'surveyId' => 1, // Existing survey
			'surveyQuestionId' => 1,
			'title' => 'Test Question',
		];

		$entity = $this->surveySurveyQuestionsTable->newDefaultEntity();
		$this->surveySurveyQuestionsTable->patchEntity($entity, $data);

		$result = $this->surveySurveyQuestionsTable->checkRules($entity);
		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\SurveySurveyQuestionsTable::buildRules()
	 */
	public function testBuildRulesInvalidSurveyId(): void {
		$data = [
			'identifier' => '87d4f766',
			'surveyId' => 99999, // Non-existing survey
			'surveyQuestionId' => 1,
			'title' => 'Test Question',
		];

		$entity = $this->surveySurveyQuestionsTable->newDefaultEntity();
		$this->surveySurveyQuestionsTable->patchEntity($entity, $data);

		$result = $this->surveySurveyQuestionsTable->checkRules($entity);

		$this->assertFalse($result);

		$errors = $entity->getErrors();
		$this->assertArrayHasKey('surveyId', $errors);
		$this->assertArrayHasKey('validSurveyId', $errors['surveyId']);
		$this->assertSame('surveys::error_valid_survey_id', $errors['surveyId']['validSurveyId']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\SurveySurveyQuestionsTable::buildRules()
	 */
	public function testBuildRulesValidSurveyQuestionId(): void {
		$data = [
			'identifier' => '87d4f766',
			'surveyId' => 1,
			'surveyQuestionId' => 1, // Existing survey question
			'title' => 'Test Question',
		];

		$entity = $this->surveySurveyQuestionsTable->newDefaultEntity();
		$this->surveySurveyQuestionsTable->patchEntity($entity, $data);

		$result = $this->surveySurveyQuestionsTable->checkRules($entity);
		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\SurveySurveyQuestionsTable::buildRules()
	 */
	public function testBuildRulesInvalidSurveyQuestionId(): void {
		$data = [
			'identifier' => '87d4f766',
			'surveyId' => 1,
			'surveyQuestionId' => 99999, // Non-existing survey question
			'title' => 'Test Question',
		];

		$entity = $this->surveySurveyQuestionsTable->newDefaultEntity();
		$this->surveySurveyQuestionsTable->patchEntity($entity, $data);

		$result = $this->surveySurveyQuestionsTable->checkRules($entity);

		$this->assertFalse($result);

		$errors = $entity->getErrors();
		$this->assertArrayHasKey('surveyQuestionId', $errors);
		$this->assertArrayHasKey('validSurveyQuestionId', $errors['surveyQuestionId']);
		$this->assertSame('surveys::error_valid_survey_question_id', $errors['surveyQuestionId']['validSurveyQuestionId']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\SurveySurveyQuestionsTable::buildRules()
	 */
	public function testBuildRulesValidNextAction(): void {
		$data = [
			'identifier' => '87d4f766',
			'surveyId' => 1,
			'surveyQuestionId' => 1,
			'title' => 'Test Question',
			'nextAction' => 'show_form', // Patching entity will convert to enum
		];

		$entity = $this->surveySurveyQuestionsTable->newDefaultEntity();

		$this->surveySurveyQuestionsTable->patchEntity($entity, $data);

		$this->assertSame(NextAction::ShowForm, $entity->nextAction);

		$result = $this->surveySurveyQuestionsTable->checkRules($entity);

		$this->assertTrue($result);

		$entity->nextAction = NextAction::ShowFormAndSave;

		$result = $this->surveySurveyQuestionsTable->checkRules($entity);

		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\SurveySurveyQuestionsTable::buildRules()
	 */
	public function testBuildRulesEmptyNextAction(): void {
		$data = [
			'identifier' => '87d4f766',
			'surveyId' => 1,
			'surveyQuestionId' => 1,
			'title' => 'Test Question',
			'nextAction' => null,
		];

		$entity = $this->surveySurveyQuestionsTable->newDefaultEntity();
		$this->surveySurveyQuestionsTable->patchEntity($entity, $data);

		$result = $this->surveySurveyQuestionsTable->checkRules($entity);
		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\SurveySurveyQuestionsTable::buildRules()
	 */
	public function testBuildRulesInvalidNextAction(): void {
		$data = [
			'identifier' => '87d4f766',
			'surveyId' => 1,
			'surveyQuestionId' => 1,
			'title' => 'Test Question',
			'nextAction' => 'invalid_action', // Patching entity will convert to enum but fail here
		];

		$entity = $this->surveySurveyQuestionsTable->newDefaultEntity();

		$this->surveySurveyQuestionsTable->patchEntity($entity, $data);

		$this->assertNull($entity->nextAction);

		$result = $this->surveySurveyQuestionsTable->checkRules($entity);

		$this->assertTrue($result);

		$entity->nextAction = 'invalid_action'; // Setting a value directly will not convert to enum

		$result = $this->surveySurveyQuestionsTable->checkRules($entity);

		$this->assertFalse($result);

		$errors = $entity->getErrors();
		$this->assertArrayHasKey('nextAction', $errors);
		$this->assertArrayHasKey('validNextAction', $errors['nextAction']);
		$this->assertSame('survey_survey_questions::error_valid_next_action', $errors['nextAction']['validNextAction']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\SurveySurveyQuestionsTable::newDefaultEntity()
	 */
	public function testNewDefaultEntity(): void {
		/** @var \Awyiss\Model\Entity\SurveySurveyQuestion $entity */
		$entity = $this->surveySurveyQuestionsTable->newDefaultEntity();

		$this->assertInstanceOf(SurveySurveyQuestion::class, $entity);
		$this->assertTrue($entity->isNew());

		// Test default values
		$this->assertNull($entity->identifier);
		$this->assertNull($entity->surveyId);
		$this->assertNull($entity->surveyQuestionId);
		$this->assertNull($entity->title);
		$this->assertNull($entity->subtitle);
		$this->assertNull($entity->text);
		$this->assertNull($entity->nextAction);
		$this->assertNull($entity->nextActionTarget);
		$this->assertNull($entity->allowCustomAnswer);
		$this->assertNull($entity->customAnswerTitle);
		$this->assertSame(0, $entity->systemOrder);
		$this->assertTrue($entity->active);
		$this->assertFalse($entity->deleted);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\SurveySurveyQuestionsTable::newDefaultEntity()
	 */
	public function testNewDefaultEntityWithData(): void {
		$additionalData = [
			'identifier' => 'custom_question',
			'surveyId' => 2,
			'surveyQuestionId' => 3,
			'title' => 'Custom Question Title',
			'subtitle' => 'Custom Subtitle',
			'text' => 'Custom question text content',
			'nextAction' => 'abort',
			'nextActionTarget' => 'question_5',
			'allowCustomAnswer' => false,
			'customAnswerTitle' => 'Custom Answer',
			'systemOrder' => 5,
			'active' => false,
			'deleted' => true,
		];

		$entity = $this->surveySurveyQuestionsTable->newDefaultEntity($additionalData);

		$this->assertInstanceOf(SurveySurveyQuestion::class, $entity);
		$this->assertTrue($entity->isNew());

		$this->assertSame('custom_question', $entity->identifier);
		$this->assertSame(2, $entity->surveyId);
		$this->assertSame(3, $entity->surveyQuestionId);
		$this->assertSame('Custom Question Title', $entity->title);
		$this->assertSame('Custom Subtitle', $entity->subtitle);
		$this->assertSame('Custom question text content', $entity->text);
		$this->assertSame(NextAction::Abort, $entity->nextAction);
		$this->assertSame('question_5', $entity->nextActionTarget);
		$this->assertFalse($entity->allowCustomAnswer);
		$this->assertSame('Custom Answer', $entity->customAnswerTitle);
		$this->assertSame(5, $entity->systemOrder);
		$this->assertFalse($entity->active);
		$this->assertTrue($entity->deleted);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\SurveySurveyQuestionsTable::initializeSchema()
	 */
	public function testInitializeSchemaNextActionColumn(): void {
		$schema = $this->surveySurveyQuestionsTable->getSchema();

		// Test that next_action column is configured as an enum type
		$this->assertSame('enum-awyiss-model-enum-survey-nextaction', $schema->getColumnType('next_action'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\SurveySurveyQuestionsTable::$systemOrder
	 */
	public function testSystemOrderBehavior(): void {
		$this->assertTrue($this->surveySurveyQuestionsTable->hasBehavior('SystemOrder'));

		$config = $this->surveySurveyQuestionsTable->getBehavior('SystemOrder')->getConfig();

		$this->assertSame(['surveyId'], $config['relatedColumns']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\SurveySurveyQuestionsTable::$translate
	 */
	public function testTranslateBehavior(): void {
		$this->assertTrue($this->surveySurveyQuestionsTable->hasBehavior('Translate'));

		$config = $this->surveySurveyQuestionsTable->getBehavior('Translate')->getConfig();

		$this->assertSame(Awyiss::REALM_FRONTEND, $config['realm']);

		$this->assertIsArray($config['fields']);
		$this->assertSame(['title', 'subtitle', 'text', 'customAnswerTitle'], $config['fields']);
	}
}
