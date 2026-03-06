<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Model\Table;


use Awyiss\Awyiss;
use Awyiss\Model\Entity\Survey;
use Awyiss\Model\Entity\SurveySurveyAnswer;
use Awyiss\Model\Enum\Survey\NextAction;
use Awyiss\Model\Enum\Survey\Type;
use Awyiss\Model\Table\SurveysTable;
use Awyiss\ORM\Association\BelongsTo;
use Awyiss\ORM\Association\HasMany;
use Awyiss\ORM\Association\HasOne;
use Awyiss\Test\TestSuite\TestCase;
use Awyiss\Validation\Validator;
use Cake\Datasource\FactoryLocator;
use Cake\Datasource\RulesChecker;


/**
 * SurveysTable Test Case
 *
 * @see \Awyiss\Model\Table\SurveysTable
 */
class SurveysTableTest extends TestCase {
	/**
	 * @var \Awyiss\Model\Table\SurveysTable
	 */
	protected SurveysTable $surveysTable;


	/**
	 * @inheritDoc
	 */
	protected function setUp(): void {
		parent::setUp();

		/** @noinspection PhpFieldAssignmentTypeMismatchInspection */
		$this->surveysTable = FactoryLocator::get('Table')->get('Surveys');
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\SurveysTable::ATTRIBUTABLE
	 */
	public function testAttributableConstant(): void {
		/** @noinspection PhpUnitAssertTrueWithIncompatibleTypeArgumentInspection */
		$this->assertTrue($this->surveysTable::ATTRIBUTABLE);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\SurveysTable::TABLE
	 */
	public function testTableConstant(): void {
		$this->assertEquals('surveys', $this->surveysTable::TABLE);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\SurveysTable::initializeAssociations()
	 */
	public function testInitializeAssociations(): void {
		$this->assertCount(14, $this->surveysTable->associations()->keys());

		$this->assertTrue($this->surveysTable->hasAssociation('Contents'));
		$contentsAssociation = $this->surveysTable->getAssociation('Contents');
		$this->assertInstanceOf(HasMany::class, $contentsAssociation);

		$this->assertTrue($this->surveysTable->hasAssociation('Forms'));
		$formsAssociation = $this->surveysTable->getAssociation('Forms');
		$this->assertInstanceOf(BelongsTo::class, $formsAssociation);

		$this->assertTrue($this->surveysTable->hasAssociation('Pages'));
		$pagesAssociation = $this->surveysTable->getAssociation('Pages');
		$this->assertInstanceOf(HasMany::class, $pagesAssociation);

		$this->assertTrue($this->surveysTable->hasAssociation('GlobalContents'));
		$globalContentsAssociation = $this->surveysTable->getAssociation('GlobalContents');
		$this->assertInstanceOf(HasMany::class, $globalContentsAssociation);

		$this->assertTrue($this->surveysTable->hasAssociation('SurveyEntries'));
		$surveyEntriesAssociation = $this->surveysTable->getAssociation('SurveyEntries');
		$this->assertInstanceOf(HasMany::class, $surveyEntriesAssociation);
		$this->assertTrue($surveyEntriesAssociation->getCascadeCallbacks());
		$this->assertTrue($surveyEntriesAssociation->getDependent());

		$this->assertTrue($this->surveysTable->hasAssociation('SurveySurveyQuestions'));
		$surveySurveyQuestionsAssociation = $this->surveysTable->getAssociation('SurveySurveyQuestions');
		$this->assertInstanceOf(HasMany::class, $surveySurveyQuestionsAssociation);
		$this->assertTrue($surveySurveyQuestionsAssociation->getCascadeCallbacks());
		$this->assertTrue($surveySurveyQuestionsAssociation->getDependent());

		// 'MediaAssignments' must also exist
		$this->assertTrue($this->surveysTable->hasAssociation('MediaAssignments'));
		$mediaAssignmentsAssociation = $this->surveysTable->getAssociation('MediaAssignments');
		$this->assertInstanceOf(HasMany::class, $mediaAssignmentsAssociation);
		$this->assertTrue($mediaAssignmentsAssociation->getCascadeCallbacks());
		$this->assertTrue($mediaAssignmentsAssociation->getDependent());

		// 'CreatedByUser' must also exist
		$this->assertTrue($this->surveysTable->hasAssociation('CreatedByUser'));
		$createdByUserAssociation = $this->surveysTable->getAssociation('CreatedByUser');
		$this->assertInstanceOf(BelongsTo::class, $createdByUserAssociation);
		$this->assertFalse($createdByUserAssociation->getCascadeCallbacks());
		$this->assertFalse($createdByUserAssociation->getDependent());

		// 'ChangedByUser' must also exist
		$this->assertTrue($this->surveysTable->hasAssociation('ChangedByUser'));
		$changedByUserAssociation = $this->surveysTable->getAssociation('ChangedByUser');
		$this->assertInstanceOf(BelongsTo::class, $changedByUserAssociation);
		$this->assertFalse($changedByUserAssociation->getCascadeCallbacks());
		$this->assertFalse($changedByUserAssociation->getDependent());

		// 'DeletedByUser' must also exist
		$this->assertTrue($this->surveysTable->hasAssociation('DeletedByUser'));
		$deletedByUserAssociation = $this->surveysTable->getAssociation('DeletedByUser');
		$this->assertInstanceOf(BelongsTo::class, $deletedByUserAssociation);
		$this->assertFalse($deletedByUserAssociation->getCascadeCallbacks());
		$this->assertFalse($deletedByUserAssociation->getDependent());

		// 'Surveys_title_translation' must also exist
		$this->assertTrue($this->surveysTable->hasAssociation('Surveys_title_translation'));
		$titleTranslationAssociation = $this->surveysTable->getAssociation('Surveys_title_translation');
		$this->assertInstanceOf(HasOne::class, $titleTranslationAssociation);
		$this->assertFalse($titleTranslationAssociation->getCascadeCallbacks());
		$this->assertFalse($titleTranslationAssociation->getDependent());

		// 'Surveys_successMessage_translation' must also exist
		$this->assertTrue($this->surveysTable->hasAssociation('Surveys_successMessage_translation'));
		$successMessageTranslationAssociation = $this->surveysTable->getAssociation('Surveys_successMessage_translation');
		$this->assertInstanceOf(HasOne::class, $successMessageTranslationAssociation);
		$this->assertFalse($successMessageTranslationAssociation->getCascadeCallbacks());
		$this->assertFalse($successMessageTranslationAssociation->getDependent());

		// 'Surveys_failureMessage_translation' must also exist
		$this->assertTrue($this->surveysTable->hasAssociation('Surveys_failureMessage_translation'));
		$failureMessageTranslationAssociation = $this->surveysTable->getAssociation('Surveys_failureMessage_translation');
		$this->assertInstanceOf(HasOne::class, $failureMessageTranslationAssociation);
		$this->assertFalse($failureMessageTranslationAssociation->getCascadeCallbacks());
		$this->assertFalse($failureMessageTranslationAssociation->getDependent());

		// 'I18n' must also exist
		$this->assertTrue($this->surveysTable->hasAssociation('I18n'));
		$i18nAssociation = $this->surveysTable->getAssociation('I18n');
		$this->assertInstanceOf(HasMany::class, $i18nAssociation);
		$this->assertFalse($i18nAssociation->getCascadeCallbacks());
		$this->assertTrue($i18nAssociation->getDependent());
		$this->assertEquals('append', $i18nAssociation->getSaveStrategy());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\SurveysTable::validationDefault()
	 */
	public function testValidationDefault(): void {
		$validator = new Validator();
		$result = $this->surveysTable->validationDefault($validator);

		$this->assertInstanceOf(Validator::class, $result);
		$this->assertSame('Surveys', $result->getI18nDomain());

		// Test required fields
		$this->assertTrue($result->hasField('title'));
		$this->assertSame('create', $result->field('title')->isPresenceRequired());

		$this->assertTrue($result->hasField('identifier'));
		$this->assertSame('create', $result->field('identifier')->isPresenceRequired());

		// Test other fields
		$this->assertTrue($result->hasField('id'));
		$this->assertTrue($result->hasField('successMessage'));
		$this->assertTrue($result->hasField('failureMessage'));
		$this->assertTrue($result->hasField('finalAction'));
		$this->assertTrue($result->hasField('formId'));
		$this->assertTrue($result->hasField('active'));
		$this->assertTrue($result->hasField('deleted'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\SurveysTable::validationDefault()
	 */
	public function testEntityValidationSuccess(): void {
		$data = [
			'title' => 'Test Survey',
			'identifier' => 'testSurvey',
			'successMessage' => 'Thank you for your response!',
			'failureMessage' => 'Sorry, there was an error.',
			'finalAction' => NextAction::ShowForm,
			'formId' => 1,
			'active' => true,
			'deleted' => false,
		];

		$entity = $this->surveysTable->newDefaultEntity($data);
		$errors = $entity->getErrors();

		$this->assertEmpty($errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\SurveysTable::validationDefault()
	 */
	public function testEntityValidationMissingRequired(): void {
		$data = [
			'successMessage' => 'Thank you!',
		];

		$entity = $this->surveysTable->newDefaultEntity();
		$this->surveysTable->patchEntity($entity, $data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('title', $errors);
		$this->assertArrayHasKey('_required', $errors['title']);
		$this->assertSame('surveys::error_required', $errors['title']['_required']);

		$this->assertArrayHasKey('identifier', $errors);
		$this->assertArrayHasKey('_required', $errors['identifier']);
		$this->assertSame('surveys::error_required', $errors['identifier']['_required']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\SurveysTable::validationDefault()
	 */
	public function testEntityValidationInvalidTypes(): void {
		$data = [
			'id' => 'not_an_integer',
			'type' => true,
			'title' => true,
			'identifier' => true,
			'successMessage' => true,
			'failureMessage' => true,
			'finalAction' => true,
			'formId' => 'not_an_integer',
			'active' => 'not_a_boolean',
			'deleted' => 'not_a_boolean',
		];

		$entity = $this->surveysTable->newDefaultEntity();
		$this->surveysTable->patchEntity($entity, $data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('id', $errors);

		$this->assertArrayHasKey('type', $errors);
		$this->assertArrayHasKey('enum', $errors['type']);
		$this->assertSame('surveys::error_enum', $errors['type']['enum']);

		$this->assertArrayHasKey('title', $errors);
		$this->assertArrayHasKey('identifier', $errors);
		$this->assertArrayHasKey('successMessage', $errors);
		$this->assertArrayHasKey('failureMessage', $errors);

		$this->assertArrayHasKey('finalAction', $errors);
		$this->assertArrayHasKey('enum', $errors['finalAction']);
		$this->assertSame('surveys::error_enum', $errors['finalAction']['enum']);

		$this->assertArrayHasKey('formId', $errors);
		$this->assertArrayHasKey('active', $errors);
		$this->assertArrayHasKey('deleted', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\SurveysTable::validationDefault()
	 */
	public function testEntityValidationFieldLength(): void {
		$data = [
			'id' => 123456789123, // exceeds 11 char limit
			'title' => str_repeat('a', 256), // exceeds 255 char limit
			'identifier' => str_repeat('b', 51), // exceeds 50 char limit
			'successMessage' => str_repeat('c', 65536), // exceeds 65535 byte limit
			'failureMessage' => str_repeat('d', 65536), // exceeds 65535 byte limit
			'finalAction' => str_repeat('e', 21), // exceeds 20 char limit
			'formId' => 123456789123, // exceeds 11 char limit
		];

		$entity = $this->surveysTable->newDefaultEntity();
		$this->surveysTable->patchEntity($entity, $data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('id', $errors);
		$this->assertArrayHasKey('title', $errors);
		$this->assertArrayHasKey('identifier', $errors);
		$this->assertArrayHasKey('successMessage', $errors);
		$this->assertArrayHasKey('failureMessage', $errors);
		$this->assertArrayHasKey('finalAction', $errors);
		$this->assertArrayHasKey('formId', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\SurveysTable::validationDefault()
	 */
	public function testEntityValidationBlankFields(): void {
		$data = [
			'title' => '   ', // Only whitespace
			'identifier' => '   ', // Only whitespace
		];

		$entity = $this->surveysTable->newDefaultEntity();
		$this->surveysTable->patchEntity($entity, $data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('title', $errors);
		$this->assertArrayHasKey('notBlank', $errors['title']);

		$this->assertArrayHasKey('identifier', $errors);
		$this->assertArrayHasKey('notBlank', $errors['identifier']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\SurveysTable::buildRules()
	 */
	public function testBuildRulesUniqueIdentifier(): void {
		$data = [
			'title' => 'Unique Survey',
			'identifier' => 'uniqueSurveyIdentifier',
			'finalAction' => 'saveAndEnd',
		];

		$entity = $this->surveysTable->newDefaultEntity();
		$this->surveysTable->patchEntity($entity, $data);

		$result = $this->surveysTable->checkRules($entity);
		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\SurveysTable::buildRules()
	 */
	public function testBuildRulesDuplicateIdentifier(): void {
		$data = [
			'title' => 'New Survey',
			'identifier' => 'dummySurvey',
			'finalAction' => 'saveAndEnd',
		];

		// Try to create another with the same identifier
		$entity = $this->surveysTable->newDefaultEntity();
		$this->surveysTable->patchEntity($entity, $data);

		$result = $this->surveysTable->checkRules($entity);

		$this->assertFalse($result);

		$errors = $entity->getErrors();
		$this->assertArrayHasKey('identifier', $errors);
		$this->assertArrayHasKey('identifierUnique', $errors['identifier']);
		$this->assertSame('surveys::error_identifier_unique', $errors['identifier']['identifierUnique']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\SurveysTable::buildRules()
	 */
	public function testBuildRulesValidType(): void {
		$data = [
			'title' => 'Survey with Invalid Final Action',
			'identifier' => 'surveyInvalidAction',
			'type' => 'configurator', // Patching entity will convert to enum
		];

		$entity = $this->surveysTable->newDefaultEntity();

		$this->surveysTable->patchEntity($entity, $data);

		$this->assertSame(Type::Configurator, $entity->type);

		$result = $this->surveysTable->checkRules($entity);

		$this->assertTrue($result);

		$entity->type = Type::Linear;

		$result = $this->surveysTable->checkRules($entity);

		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\SurveysTable::buildRules()
	 */
	public function testBuildRulesInvalidType(): void {
		$data = [
			'title' => 'Survey with Invalid Type',
			'identifier' => 'surveyInvalidType',
			'type' => 'invalidType', // Patching entity will convert to enum but fail here
		];

		$entity = $this->surveysTable->newDefaultEntity();

		$this->surveysTable->patchEntity($entity, $data);

		$this->assertSame(Type::Linear, $entity->type);

		$result = $this->surveysTable->checkRules($entity);

		$this->assertTrue($result);

		$entity->type = 'invalidType'; // Setting a value directly will not convert to enum

		$result = $this->surveysTable->checkRules($entity);

		$this->assertFalse($result);

		$errors = $entity->getErrors();
		$this->assertArrayHasKey('type', $errors);
		$this->assertArrayHasKey('validType', $errors['type']);
		$this->assertSame('surveys::error_valid_type', $errors['type']['validType']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\SurveysTable::buildRules()
	 */
	public function testBuildRulesValidFormId(): void {
		$data = [
			'title' => 'Survey with Form',
			'identifier' => 'surveyWithForm',
			'finalAction' => NextAction::ShowForm,
			'formId' => 1, // Existing form
		];

		$entity = $this->surveysTable->newDefaultEntity();
		$this->surveysTable->patchEntity($entity, $data);

		$result = $this->surveysTable->checkRules($entity);
		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\SurveysTable::buildRules()
	 */
	public function testBuildRulesNullFormIdWhenNotRequired(): void {
		$data = [
			'title' => 'Survey without Form',
			'identifier' => 'surveyWithoutForm',
			'finalAction' => 'saveAndEnd',
			'formId' => null,
		];

		$entity = $this->surveysTable->newDefaultEntity();
		$this->surveysTable->patchEntity($entity, $data);

		$result = $this->surveysTable->checkRules($entity);
		$this->assertTrue($result);
	}


	/**
	 * @testWith ["showForm"]
	 *           ["saveAndShowForm"]
	 *           ["showFormAndSave"]
	 * @param string $finalAction
	 * @return void
	 * @see \Awyiss\Model\Table\SurveysTable::buildRules()
	 */
	public function testBuildRulesNullFormIdWhenRequired(string $finalAction): void {
		$data = [
			'title' => 'Survey without Form',
			'identifier' => 'surveyWithoutForm',
			'finalAction' => $finalAction,
			'formId' => null,
		];

		$entity = $this->surveysTable->newDefaultEntity();
		$this->surveysTable->patchEntity($entity, $data);

		$result = $this->surveysTable->checkRules($entity);
		$this->assertFalse($result);

		$errors = $entity->getErrors();

		$this->assertArrayHasKey('formId', $errors);
		$this->assertArrayHasKey('formIdSetWhenRequired', $errors['formId']);
		$this->assertSame('surveys::error_form_id_set_when_required', $errors['formId']['formIdSetWhenRequired']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\SurveysTable::buildRules()
	 */
	public function testBuildRulesInvalidFormId(): void {
		$data = [
			'title' => 'Survey with Invalid Form',
			'identifier' => 'surveyInvalidForm',
			'finalAction' => NextAction::ShowForm,
			'formId' => 99999, // Non-existing form
		];

		$entity = $this->surveysTable->newDefaultEntity();
		$this->surveysTable->patchEntity($entity, $data);

		$result = $this->surveysTable->checkRules($entity);

		$this->assertFalse($result);

		$errors = $entity->getErrors();
		$this->assertArrayHasKey('formId', $errors);
		$this->assertArrayHasKey('validFormId', $errors['formId']);
		$this->assertSame('surveys::error_valid_form_id', $errors['formId']['validFormId']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\SurveysTable::buildRules()
	 */
	public function testBuildRulesValidFinalAction(): void {
		$data = [
			'title' => 'Survey with Invalid Final Action',
			'identifier' => 'surveyInvalidAction',
			'finalAction' => 'saveAndShowForm', // Patching entity will convert to enum
			'formId' => 1,
		];

		$entity = $this->surveysTable->newDefaultEntity();

		$this->surveysTable->patchEntity($entity, $data);

		$this->assertSame(NextAction::SaveAndShowForm, $entity->finalAction);

		$result = $this->surveysTable->checkRules($entity);

		$this->assertTrue($result);

		$entity->finalAction = NextAction::ShowFormAndSave;

		$result = $this->surveysTable->checkRules($entity);

		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\SurveysTable::buildRules()
	 */
	public function testBuildRulesInvalidFinalAction(): void {
		$data = [
			'title' => 'Survey with Invalid Final Action',
			'identifier' => 'surveyInvalidAction',
			'finalAction' => 'invalid_action', // Patching entity will convert to enum but fail here
		];

		$entity = $this->surveysTable->newDefaultEntity();

		$this->surveysTable->patchEntity($entity, $data);

		$this->assertSame(NextAction::SaveAndEnd, $entity->finalAction);

		$result = $this->surveysTable->checkRules($entity);

		$this->assertTrue($result);

		$entity->finalAction = 'invalid_action'; // Setting a value directly will not convert to enum

		$result = $this->surveysTable->checkRules($entity);

		$this->assertFalse($result);

		$errors = $entity->getErrors();
		$this->assertArrayHasKey('finalAction', $errors);
		$this->assertArrayHasKey('validFinalAction', $errors['finalAction']);
		$this->assertSame('surveys::error_valid_final_action', $errors['finalAction']['validFinalAction']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\SurveysTable::buildRules()
	 */
	public function testBuildRulesFormIdSetWhenRequiredValid(): void {
		// Survey with form ID when final action requires form
		$data = [
			'title' => 'Survey with Required Form',
			'identifier' => 'surveyFormRequired',
			'finalAction' => NextAction::ShowForm,
			'formId' => 1,
		];

		$entity = $this->surveysTable->newDefaultEntity();
		$this->surveysTable->patchEntity($entity, $data);

		$result = $this->surveysTable->checkRules($entity);
		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\SurveysTable::buildRules()
	 */
	public function testBuildRulesFormIdSetWhenRequiredInvalid(): void {
		// Survey without form ID when final action requires form
		$data = [
			'title' => 'Survey Missing Required Form',
			'identifier' => 'surveyMissingForm',
			'finalAction' => NextAction::ShowForm,
			'formId' => null,
		];

		$entity = $this->surveysTable->newDefaultEntity();
		$this->surveysTable->patchEntity($entity, $data);

		$result = $this->surveysTable->checkRules($entity);

		$this->assertFalse($result);

		$errors = $entity->getErrors();
		$this->assertArrayHasKey('formId', $errors);
		$this->assertArrayHasKey('formIdSetWhenRequired', $errors['formId']);
		$this->assertSame('surveys::error_form_id_set_when_required', $errors['formId']['formIdSetWhenRequired']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\SurveysTable::buildRules()
	 */
	public function testBuildRulesNoCircularReferencesValid(): void {
		// Survey without circular references
		$data = [
			'title' => 'Linear Survey',
			'identifier' => 'linearSurvey',
			'finalAction' => 'saveAndEnd',
			'type' => 'linear',
		];

		$entity = $this->getMockBuilder(Survey::class)->disableOriginalConstructor()->onlyMethods(['hasCycle'])->getMock();
		$entity->expects($this->once())->method('hasCycle')->willReturn(false);

		$this->surveysTable->patchEntity($entity, $data);

		$result = $this->surveysTable->checkRules($entity);
		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\SurveysTable::buildRules()
	 */
	public function testBuildRulesCircularReferencesInvalid(): void {
		// Survey without circular references
		$data = [
			'title' => 'Linear Survey',
			'identifier' => 'linearSurvey',
			'finalAction' => 'saveAndEnd',
			'type' => 'linear',
		];

		$entity = $this->getMockBuilder(Survey::class)->disableOriginalConstructor()->onlyMethods(['hasCycle'])->getMock();
		$entity->expects($this->once())->method('hasCycle')->willReturn(true);

		$this->surveysTable->patchEntity($entity, $data);

		$result = $this->surveysTable->checkRules($entity);
		$this->assertFalse($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\SurveysTable::buildRules()
	 */
	public function testBuildRulesNoRepeatedQuestionsInLinearSurveyValid(): void {
		// Linear survey with unique questions
		$data = [
			'title' => 'Linear Survey Unique Questions',
			'identifier' => 'linearUnique',
			'finalAction' => 'saveAndEnd',
			'type' => 'linear',
			'surveySurveyQuestions' => [
				['surveyQuestionId' => 1, 'identifier' => '8524de5e'],
				['surveyQuestionId' => 2, 'identifier' => '8524de5e'],
				['surveyQuestionId' => 3, 'identifier' => '8524de5e'],
			],
		];

		$entity = $this->surveysTable->newDefaultEntity();
		$this->surveysTable->patchEntity($entity, $data, [
			'accessibleFields' => ['surveySurveyQuestions'],
		]);

		$this->assertNotNull($entity->surveySurveyQuestions);
		$this->assertNotEmpty($entity->surveySurveyQuestions);

		$result = $this->surveysTable->checkRules($entity);
		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\SurveysTable::buildRules()
	 */
	public function testBuildRulesNoRepeatedQuestionsInLinearSurveyInvalid(): void {
		// Linear survey with repeated questions
		$data = [
			'title' => 'Linear Survey Repeated Questions',
			'identifier' => 'linearRepeated',
			'finalAction' => 'saveAndEnd',
			'type' => 'linear',
			'surveySurveyQuestions' => [
				['surveyQuestionId' => 1, 'identifier' => '8524de5e'],
				['surveyQuestionId' => 2, 'identifier' => '8524de5e'],
				['surveyQuestionId' => 1, 'identifier' => '8524de5e'], // Repeated question
			],
		];

		$entity = $this->surveysTable->newDefaultEntity();
		$this->surveysTable->patchEntity($entity, $data, [
			'accessibleFields' => ['surveySurveyQuestions'],
		]);

		$this->assertNotNull($entity->surveySurveyQuestions);
		$this->assertNotEmpty($entity->surveySurveyQuestions);

		$result = $this->surveysTable->checkRules($entity);

		$this->assertFalse($result);

		$errors = $entity->getErrors();
		$this->assertArrayHasKey('surveySurveyQuestions', $errors);
		$this->assertArrayHasKey('noRepeatedQuestionsInLinearSurvey', $errors['surveySurveyQuestions']);
		$this->assertSame('surveys::error_no_repeated_questions_in_linear_survey', $errors['surveySurveyQuestions']['noRepeatedQuestionsInLinearSurvey']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\SurveysTable::buildRules()
	 */
	public function testBuildRulesRepeatedQuestionsInConfiguratorSurveyValid(): void {
		// Linear survey with repeated questions
		$data = [
			'title' => 'Linear Survey Repeated Questions',
			'identifier' => 'linearRepeated',
			'finalAction' => 'saveAndEnd',
			'type' => 'configurator',
			'surveySurveyQuestions' => [
				['surveyQuestionId' => 1, 'identifier' => '8524de5e', 'nextAction' => 'nextQuestion'],
				['surveyQuestionId' => 2, 'identifier' => '8524de5e', 'nextAction' => 'nextQuestion'],
				['surveyQuestionId' => 1, 'identifier' => '8524de5e', 'nextAction' => 'nextQuestion'], // Repeated question
			],
		];

		$entity = $this->surveysTable->newDefaultEntity();
		$this->surveysTable->patchEntity($entity, $data, [
			'accessibleFields' => ['surveySurveyQuestions'],
		]);

		$this->assertNotNull($entity->surveySurveyQuestions);
		$this->assertNotEmpty($entity->surveySurveyQuestions);

		$result = $this->surveysTable->checkRules($entity);

		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\SurveysTable::buildRules()
	 */
	public function _testBuildRulesNoInvalidNextActionsLinearSurveyValid(): void {
		// Linear survey without next actions (valid)
		$data = [
			'title' => 'Linear Survey No Actions',
			'identifier' => 'linearNoActions',
			'finalAction' => 'saveAndEnd',
			'type' => 'linear',
			'surveySurveyQuestions' => [
				['surveyQuestionId' => 1, 'identifier' => '8524de5e'],
				['surveyQuestionId' => 2, 'identifier' => '8524de5e'],
			],
		];

		$entity = $this->surveysTable->newDefaultEntity();
		$this->surveysTable->patchEntity($entity, $data, [
			'accessibleFields' => ['surveySurveyQuestions'],
		]);

		$this->assertNotNull($entity->surveySurveyQuestions);
		$this->assertNotEmpty($entity->surveySurveyQuestions);

		$result = $this->surveysTable->checkRules($entity);
		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\SurveysTable::buildRules()
	 */
	public function testBuildRulesNoInvalidNextActionsLinearSurveyInvalidFromQuestion(): void {
		// Linear survey with next actions (invalid)
		$data = [
			'title' => 'Linear Survey No Actions',
			'identifier' => 'linearNoActions',
			'finalAction' => 'saveAndEnd',
			'type' => 'linear',
			'surveySurveyQuestions' => [
				['surveyQuestionId' => 1, 'identifier' => '8524de5e'],
				['surveyQuestionId' => 2, 'identifier' => '8524de5e', 'nextAction' => 'nextQuestion'],
			],
		];

		$entity = $this->surveysTable->newDefaultEntity();
		$this->surveysTable->patchEntity($entity, $data, [
			'accessibleFields' => ['surveySurveyQuestions'],
		]);

		$this->assertNotNull($entity->surveySurveyQuestions);
		$this->assertNotEmpty($entity->surveySurveyQuestions);

		$entity->surveySurveyQuestions[0]->surveySurveyAnswers = [
			new SurveySurveyAnswer()->patch([
				'surveyAnswerId' => 1,
			]),
			new SurveySurveyAnswer()->patch([
				'surveyAnswerId' => 2,
			]),
		];

		$this->assertNotNull($entity->surveySurveyQuestions[0]->surveySurveyAnswers);
		$this->assertNotEmpty($entity->surveySurveyQuestions[0]->surveySurveyAnswers);

		$result = $this->surveysTable->checkRules($entity);

		$this->assertFalse($result);

		$errors = $entity->getErrors();
		$this->assertArrayHasKey('surveySurveyQuestions', $errors);
		$this->assertArrayHasKey('noInvalidNextActions', $errors['surveySurveyQuestions']);
		$this->assertSame('surveys::error_no_invalid_next_actions', $errors['surveySurveyQuestions']['noInvalidNextActions']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\SurveysTable::buildRules()
	 */
	public function testBuildRulesNoInvalidNextActionsLinearSurveyInvalidFromAnswer(): void {
		// Linear survey with next actions (invalid)
		$data = [
			'title' => 'Linear Survey No Actions',
			'identifier' => 'linearNoActions',
			'finalAction' => 'saveAndEnd',
			'type' => 'linear',
			'surveySurveyQuestions' => [
				['surveyQuestionId' => 1, 'identifier' => '8524de5e'],
				['surveyQuestionId' => 2, 'identifier' => '8524de5e'],
			],
		];

		$entity = $this->surveysTable->newDefaultEntity();
		$this->surveysTable->patchEntity($entity, $data, [
			'accessibleFields' => ['surveySurveyQuestions'],
		]);

		$this->assertNotNull($entity->surveySurveyQuestions);
		$this->assertNotEmpty($entity->surveySurveyQuestions);

		$entity->surveySurveyQuestions[0]->surveySurveyAnswers = [
			new SurveySurveyAnswer()->patch([
				'surveyAnswerId' => 1,
			]),
			new SurveySurveyAnswer()->patch([
				'surveyAnswerId' => 2,
				'nextAction' => 'nextQuestion', // Invalid next action for linear survey
			]),
		];

		$this->assertNotNull($entity->surveySurveyQuestions[0]->surveySurveyAnswers);
		$this->assertNotEmpty($entity->surveySurveyQuestions[0]->surveySurveyAnswers);


		$result = $this->surveysTable->checkRules($entity);

		$this->assertFalse($result);

		$errors = $entity->getErrors();

		$this->assertArrayHasKey('surveySurveyQuestions', $errors);
		$this->assertArrayHasKey('noInvalidNextActions', $errors['surveySurveyQuestions']);
		$this->assertSame('surveys::error_no_invalid_next_actions', $errors['surveySurveyQuestions']['noInvalidNextActions']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\SurveysTable::buildRules()
	 */
	public function testBuildRulesNoInvalidNextActionsNonLinearSurveyValid(): void {
		// Linear survey with next actions (invalid)
		$data = [
			'title' => 'Linear Survey No Actions',
			'identifier' => 'linearNoActions',
			'finalAction' => 'saveAndEnd',
			'type' => 'configurator',
			'surveySurveyQuestions' => [
				['surveyQuestionId' => 1, 'identifier' => '8524de5e', 'nextAction' => 'nextQuestion'],
				['surveyQuestionId' => 2, 'identifier' => '8524de5e', 'nextAction' => 'nextQuestion'],
			],
		];

		$entity = $this->surveysTable->newDefaultEntity();
		$this->surveysTable->patchEntity($entity, $data, [
			'accessibleFields' => ['surveySurveyQuestions'],
		]);

		$this->assertNotNull($entity->surveySurveyQuestions);
		$this->assertNotEmpty($entity->surveySurveyQuestions);

		$entity->surveySurveyQuestions[0]->surveySurveyAnswers = [
			new SurveySurveyAnswer()->patch([
				'surveyAnswerId' => 1,
				'nextAction' => NextAction::NextQuestion,
			]),
			new SurveySurveyAnswer()->patch([
				'surveyAnswerId' => 2,
				'nextAction' => NextAction::NextQuestion,
			]),
		];

		$this->assertNotNull($entity->surveySurveyQuestions[0]->surveySurveyAnswers);
		$this->assertNotEmpty($entity->surveySurveyQuestions[0]->surveySurveyAnswers);

		$result = $this->surveysTable->checkRules($entity);

		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\SurveysTable::buildRules()
	 */
	public function testBuildRulesNoInvalidNextActionsNonLinearSurveyInvalidFromQuestion(): void {
		// Linear survey with next actions (invalid)
		$data = [
			'title' => 'Linear Survey No Actions',
			'identifier' => 'linearNoActions',
			'finalAction' => 'saveAndEnd',
			'type' => 'configurator',
			'surveySurveyQuestions' => [
				['surveyQuestionId' => 1, 'identifier' => '8524de5e', 'nextAction' => 'nextQuestion'],
				['surveyQuestionId' => 2, 'identifier' => '8524de5e'], // Missing next action
			],
		];

		$entity = $this->surveysTable->newDefaultEntity();
		$this->surveysTable->patchEntity($entity, $data, [
			'accessibleFields' => ['surveySurveyQuestions'],
		]);

		$this->assertNotNull($entity->surveySurveyQuestions);
		$this->assertNotEmpty($entity->surveySurveyQuestions);

		$entity->surveySurveyQuestions[0]->surveySurveyAnswers = [
			new SurveySurveyAnswer()->patch([
				'surveyAnswerId' => 1,
				'nextAction' => NextAction::NextQuestion,
			]),
			new SurveySurveyAnswer()->patch([
				'surveyAnswerId' => 2,
				'nextAction' => NextAction::NextQuestion,
			]),
		];

		$this->assertNotNull($entity->surveySurveyQuestions[0]->surveySurveyAnswers);
		$this->assertNotEmpty($entity->surveySurveyQuestions[0]->surveySurveyAnswers);

		$result = $this->surveysTable->checkRules($entity);

		$this->assertFalse($result);

		$errors = $entity->getErrors();
		$this->assertArrayHasKey('surveySurveyQuestions', $errors);
		$this->assertArrayHasKey('noInvalidNextActions', $errors['surveySurveyQuestions']);
		$this->assertSame('surveys::error_no_invalid_next_actions', $errors['surveySurveyQuestions']['noInvalidNextActions']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\SurveysTable::buildRules()
	 */
	public function testBuildRulesNoInvalidNextActionsNonLinearSurveyInvalidFromAnswer(): void {
		// Linear survey with next actions (invalid)
		$data = [
			'title' => 'Linear Survey No Actions',
			'identifier' => 'linearNoActions',
			'finalAction' => 'saveAndEnd',
			'type' => 'configurator',
			'surveySurveyQuestions' => [
				['surveyQuestionId' => 1, 'identifier' => '8524de5e', 'nextAction' => 'nextQuestion'],
				['surveyQuestionId' => 2, 'identifier' => '8524de5e', 'nextAction' => 'nextQuestion'],
			],
		];

		$entity = $this->surveysTable->newDefaultEntity();
		$this->surveysTable->patchEntity($entity, $data, [
			'accessibleFields' => ['surveySurveyQuestions'],
		]);

		$this->assertNotNull($entity->surveySurveyQuestions);
		$this->assertNotEmpty($entity->surveySurveyQuestions);

		$entity->surveySurveyQuestions[0]->surveySurveyAnswers = [
			new SurveySurveyAnswer()->patch([
				'surveyAnswerId' => 1,
				'nextAction' => NextAction::NextQuestion,
			]),
			new SurveySurveyAnswer()->patch([
				'surveyAnswerId' => 2,
				'nextAction' => 'unknown_action', // Invalid next action
			]),
		];

		$this->assertNotNull($entity->surveySurveyQuestions[0]->surveySurveyAnswers);
		$this->assertNotEmpty($entity->surveySurveyQuestions[0]->surveySurveyAnswers);

		$result = $this->surveysTable->checkRules($entity);

		$this->assertFalse($result);

		$errors = $entity->getErrors();
		$this->assertArrayHasKey('surveySurveyQuestions', $errors);
		$this->assertArrayHasKey('noInvalidNextActions', $errors['surveySurveyQuestions']);
		$this->assertSame('surveys::error_no_invalid_next_actions', $errors['surveySurveyQuestions']['noInvalidNextActions']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\SurveysTable::buildRules()
	 */
	public function testBuildRulesDeleteNoLinkedContents(): void {
		// Survey without linked contents should allow deletion
		$survey = $this->surveysTable->get(4); // Survey without contents

		$result = $this->surveysTable->checkRules($survey, RulesChecker::DELETE);
		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\SurveysTable::buildRules()
	 */
	public function testBuildRulesDeleteWithLinkedContents(): void {
		// Survey with linked contents should prevent deletion
		$survey = $this->surveysTable->get(1); // Survey with contents

		$result = $this->surveysTable->checkRules($survey, RulesChecker::DELETE);

		$this->assertFalse($result);

		$errors = $survey->getErrors();
		$this->assertArrayHasKey('_general', $errors);
		$this->assertArrayHasKey('noLinkedContents', $errors['_general']);
		$this->assertSame('surveys::error_linked_contents', $errors['_general']['noLinkedContents']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\SurveysTable::buildRules()
	 */
	public function testBuildRulesDeleteNoLinkedPages(): void {
		// Survey without linked pages should allow deletion
		$survey = $this->surveysTable->get(4); // Survey without pages

		$result = $this->surveysTable->checkRules($survey, RulesChecker::DELETE);
		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\SurveysTable::buildRules()
	 */
	public function testBuildRulesDeleteWithLinkedPages(): void {
		// Survey with linked pages should prevent deletion
		$survey = $this->surveysTable->get(2); // Survey with pages

		$result = $this->surveysTable->checkRules($survey, RulesChecker::DELETE);

		$this->assertFalse($result);

		$errors = $survey->getErrors();
		$this->assertArrayHasKey('_general', $errors);
		$this->assertArrayHasKey('noLinkedPages', $errors['_general']);
		$this->assertSame('surveys::error_linked_pages', $errors['_general']['noLinkedPages']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\SurveysTable::buildRules()
	 */
	public function testBuildRulesDeleteNoLinkedGlobalContents(): void {
		// Survey without linked global contents should allow deletion
		$survey = $this->surveysTable->get(4); // Survey without global contents

		$result = $this->surveysTable->checkRules($survey, RulesChecker::DELETE);
		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\SurveysTable::buildRules()
	 */
	public function testBuildRulesDeleteWithLinkedGlobalContents(): void {
		// Survey with linked global contents should prevent deletion
		$survey = $this->surveysTable->get(3); // Survey with global contents

		$result = $this->surveysTable->checkRules($survey, RulesChecker::DELETE);

		$this->assertFalse($result);

		$errors = $survey->getErrors();
		$this->assertArrayHasKey('_general', $errors);
		$this->assertArrayHasKey('noLinkedGlobalContents', $errors['_general']);
		$this->assertSame('surveys::error_linked_global_contents', $errors['_general']['noLinkedGlobalContents']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\SurveysTable::newDefaultEntity()
	 */
	public function testNewDefaultEntity(): void {
		/** @var \Awyiss\Model\Entity\Survey $entity */
		$entity = $this->surveysTable->newDefaultEntity();

		$this->assertInstanceOf(Survey::class, $entity);
		$this->assertTrue($entity->isNew());

		// Test default values
		$this->assertNull($entity->title);
		$this->assertNull($entity->identifier);
		$this->assertNull($entity->successMessage);
		$this->assertNull($entity->failureMessage);
		$this->assertSame(NextAction::SaveAndEnd, $entity->finalAction);
		$this->assertNull($entity->formId);
		$this->assertTrue($entity->active);
		$this->assertFalse($entity->deleted);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\SurveysTable::newDefaultEntity()
	 */
	public function testNewDefaultEntityWithData(): void {
		$additionalData = [
			'title' => 'Custom Survey',
			'identifier' => 'customSurvey',
			'successMessage' => 'Custom success message',
			'failureMessage' => 'Custom failure message',
			'finalAction' => NextAction::ShowForm,
			'formId' => 2,
			'active' => false,
			'deleted' => true,
		];

		$entity = $this->surveysTable->newDefaultEntity($additionalData);

		$this->assertInstanceOf(Survey::class, $entity);
		$this->assertTrue($entity->isNew());

		$this->assertSame('Custom Survey', $entity->title);
		$this->assertSame('customSurvey', $entity->identifier);
		$this->assertSame('Custom success message', $entity->successMessage);
		$this->assertSame('Custom failure message', $entity->failureMessage);
		$this->assertSame(NextAction::ShowForm, $entity->finalAction);
		$this->assertSame(2, $entity->formId);
		$this->assertFalse($entity->active);
		$this->assertTrue($entity->deleted);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\SurveysTable::initializeSchema()
	 */
	public function testInitializeSchemaFinalActionColumn(): void {
		$schema = $this->surveysTable->getSchema();

		// Test that finalAction column is configured as an enum type
		$this->assertSame('enum-awyiss-model-enum-survey-nextaction', $schema->getColumnType('finalAction'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\SurveysTable::initializeSchema()
	 */
	public function testInitializeSchemaType(): void {
		$schema = $this->surveysTable->getSchema();

		// Test that type column is configured as an enum type
		$this->assertSame('enum-awyiss-model-enum-survey-type', $schema->getColumnType('type'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\SurveysTable::$translate
	 */
	public function testTranslateBehavior(): void {
		$this->assertTrue($this->surveysTable->hasBehavior('Translate'));

		$config = $this->surveysTable->getBehavior('Translate')->getConfig();

		$this->assertSame(Awyiss::REALM_FRONTEND, $config['realm']);

		$this->assertIsArray($config['fields']);
		$this->assertSame(['title', 'successMessage', 'failureMessage'], $config['fields']);
	}

	/**
	 * @return void
	 * @see \Awyiss\Model\Table\SurveysTable::availableFinalActions()
	 */
	public function testAvailableFinalActions(): void {
		$finalActions = $this->surveysTable->availableFinalActions();

		$this->assertIsArray($finalActions);
		$this->assertNotEmpty($finalActions);

		$this->assertSame(['saveAndEnd', 'showForm', 'saveAndShowForm', 'showFormAndSave'], array_keys($finalActions));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\SurveysTable::availableNextActions()
	 */
	public function testAvailableNextActions(): void {
		$nextActions = $this->surveysTable->availableNextActions();

		$this->assertIsArray($nextActions);
		$this->assertNotEmpty($nextActions);

		$this->assertSame([
			'nextQuestion',
			'specificQuestion',
			'saveAndEnd',
			'showForm',
			'saveAndShowForm',
			'showFormAndSave',
			'abort',
		], array_keys($nextActions));
	}
}
