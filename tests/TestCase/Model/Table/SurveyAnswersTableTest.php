<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Model\Table;


use Awyiss\Awyiss;
use Awyiss\Model\Entity\SurveyAnswer;
use Awyiss\Model\Table\SurveyAnswersTable;
use Awyiss\ORM\Association\BelongsTo;
use Awyiss\ORM\Association\HasMany;
use Awyiss\ORM\Association\HasOne;
use Awyiss\Test\TestSuite\TestCase;
use Awyiss\Validation\Validator;
use Cake\Datasource\FactoryLocator;
use Cake\Datasource\RulesChecker;


/**
 * SurveyAnswersTable Test Case
 *
 * @see \Awyiss\Model\Table\SurveyAnswersTable
 */
class SurveyAnswersTableTest extends TestCase {
	/**
	 * @var \Awyiss\Model\Table\SurveyAnswersTable
	 */
	protected SurveyAnswersTable $surveyAnswersTable;


	/**
	 * @inheritDoc
	 */
	protected function setUp(): void {
		parent::setUp();

		/** @noinspection PhpFieldAssignmentTypeMismatchInspection */
		$this->surveyAnswersTable = FactoryLocator::get('Table')->get('SurveyAnswers');
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\SurveyAnswersTable::ATTRIBUTABLE
	 */
	public function testAttributableConstant(): void {
		/** @noinspection PhpUnitAssertTrueWithIncompatibleTypeArgumentInspection */
		$this->assertTrue($this->surveyAnswersTable::ATTRIBUTABLE);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\SurveyAnswersTable::TABLE
	 */
	public function testTableConstant(): void {
		$this->assertEquals('survey_answers', $this->surveyAnswersTable::TABLE);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\SurveyAnswersTable::initializeAssociations()
	 */
	public function testInitializeAssociations(): void {
		$this->assertCount(11, $this->surveyAnswersTable->associations()->keys());

		// Test SurveyQuestions association (BelongsTo)
		$this->assertTrue($this->surveyAnswersTable->hasAssociation('SurveyQuestions'));
		$surveyQuestionsAssociation = $this->surveyAnswersTable->getAssociation('SurveyQuestions');
		$this->assertInstanceOf(BelongsTo::class, $surveyQuestionsAssociation);
		$this->assertEquals('surveyQuestionId', $surveyQuestionsAssociation->getForeignKey());
		$this->assertEquals('INNER', $surveyQuestionsAssociation->getJoinType());

		// Test SurveySurveyAnswers association (HasMany)
		$this->assertTrue($this->surveyAnswersTable->hasAssociation('SurveySurveyAnswers'));
		$surveySurveyAnswersAssociation = $this->surveyAnswersTable->getAssociation('SurveySurveyAnswers');
		$this->assertInstanceOf(HasMany::class, $surveySurveyAnswersAssociation);
		$this->assertTrue($surveySurveyAnswersAssociation->getCascadeCallbacks());
		$this->assertTrue($surveySurveyAnswersAssociation->getDependent());
		$this->assertEquals('surveyAnswerId', $surveySurveyAnswersAssociation->getForeignKey());
		$this->assertEquals('replace', $surveySurveyAnswersAssociation->getSaveStrategy());

		// 'MediaAssignments' must also exist
		$this->assertTrue($this->surveyAnswersTable->hasAssociation('MediaAssignments'));
		$mediaAssignmentsAssociation = $this->surveyAnswersTable->getAssociation('MediaAssignments');
		$this->assertInstanceOf(HasMany::class, $mediaAssignmentsAssociation);
		$this->assertTrue($mediaAssignmentsAssociation->getCascadeCallbacks());
		$this->assertTrue($mediaAssignmentsAssociation->getDependent());

		// Test SurveySurveyQuestions association (BelongsTo)
		$this->assertTrue($this->surveyAnswersTable->hasAssociation('SurveySurveyQuestions'));
		$surveySurveyQuestionsAssociation = $this->surveyAnswersTable->getAssociation('SurveySurveyQuestions');
		$this->assertInstanceOf(BelongsTo::class, $surveySurveyQuestionsAssociation);
		$this->assertEquals('surveySurveyQuestionId', $surveySurveyQuestionsAssociation->getForeignKey());
		$this->assertEquals('INNER', $surveySurveyQuestionsAssociation->getJoinType());

		// Test inherited associations (from Table parent class)
		$this->assertTrue($this->surveyAnswersTable->hasAssociation('CreatedByUser'));
		$createdByUserAssociation = $this->surveyAnswersTable->getAssociation('CreatedByUser');
		$this->assertInstanceOf(BelongsTo::class, $createdByUserAssociation);
		$this->assertFalse($createdByUserAssociation->getCascadeCallbacks());
		$this->assertFalse($createdByUserAssociation->getDependent());

		$this->assertTrue($this->surveyAnswersTable->hasAssociation('ChangedByUser'));
		$changedByUserAssociation = $this->surveyAnswersTable->getAssociation('ChangedByUser');
		$this->assertInstanceOf(BelongsTo::class, $changedByUserAssociation);
		$this->assertFalse($changedByUserAssociation->getCascadeCallbacks());
		$this->assertFalse($changedByUserAssociation->getDependent());

		$this->assertTrue($this->surveyAnswersTable->hasAssociation('DeletedByUser'));
		$deletedByUserAssociation = $this->surveyAnswersTable->getAssociation('DeletedByUser');
		$this->assertInstanceOf(BelongsTo::class, $deletedByUserAssociation);
		$this->assertFalse($deletedByUserAssociation->getCascadeCallbacks());
		$this->assertFalse($deletedByUserAssociation->getDependent());

		// Test translation associations
		$this->assertTrue($this->surveyAnswersTable->hasAssociation('SurveyAnswers_title_translation'));
		$titleTranslationAssociation = $this->surveyAnswersTable->getAssociation('SurveyAnswers_title_translation');
		$this->assertInstanceOf(HasOne::class, $titleTranslationAssociation);
		$this->assertFalse($titleTranslationAssociation->getCascadeCallbacks());
		$this->assertFalse($titleTranslationAssociation->getDependent());

		$this->assertTrue($this->surveyAnswersTable->hasAssociation('SurveyAnswers_subtitle_translation'));
		$subtitleTranslationAssociation = $this->surveyAnswersTable->getAssociation('SurveyAnswers_subtitle_translation');
		$this->assertInstanceOf(HasOne::class, $subtitleTranslationAssociation);
		$this->assertFalse($subtitleTranslationAssociation->getCascadeCallbacks());
		$this->assertFalse($subtitleTranslationAssociation->getDependent());

		$this->assertTrue($this->surveyAnswersTable->hasAssociation('SurveyAnswers_text_translation'));
		$textTranslationAssociation = $this->surveyAnswersTable->getAssociation('SurveyAnswers_text_translation');
		$this->assertInstanceOf(HasOne::class, $textTranslationAssociation);
		$this->assertFalse($textTranslationAssociation->getCascadeCallbacks());
		$this->assertFalse($textTranslationAssociation->getDependent());

		$this->assertTrue($this->surveyAnswersTable->hasAssociation('I18n'));
		$i18nAssociation = $this->surveyAnswersTable->getAssociation('I18n');
		$this->assertInstanceOf(HasMany::class, $i18nAssociation);
		$this->assertFalse($i18nAssociation->getCascadeCallbacks());
		$this->assertTrue($i18nAssociation->getDependent());
		$this->assertEquals('append', $i18nAssociation->getSaveStrategy());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\SurveyAnswersTable::validationDefault()
	 */
	public function testValidationDefault(): void {
		$validator = new Validator();
		$result = $this->surveyAnswersTable->validationDefault($validator);

		$this->assertInstanceOf(Validator::class, $result);
		$this->assertSame('SurveyAnswers', $result->getI18nDomain());

		// Test required fields
		$this->assertTrue($result->hasField('title'));
		$this->assertSame('create', $result->field('title')->isPresenceRequired());

		// Test other fields
		$this->assertTrue($result->hasField('id'));
		$this->assertTrue($result->hasField('surveyQuestionId'));
		$this->assertTrue($result->hasField('subtitle'));
		$this->assertTrue($result->hasField('text'));
		$this->assertTrue($result->hasField('systemOrder'));
		$this->assertTrue($result->hasField('active'));
		$this->assertTrue($result->hasField('deleted'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\SurveyAnswersTable::validationDefault()
	 */
	public function testEntityValidationSuccess(): void {
		$data = [
			'surveyQuestionId' => 1,
			'title' => 'Test Survey Answer',
			'subtitle' => 'Test subtitle',
			'text' => 'This is a test answer text.',
			'systemOrder' => 1,
			'active' => true,
			'deleted' => false,
		];

		$entity = $this->surveyAnswersTable->newDefaultEntity($data);
		$errors = $entity->getErrors();

		$this->assertEmpty($errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\SurveyAnswersTable::validationDefault()
	 */
	public function testEntityValidationMissingRequired(): void {
		$data = [
			'surveyQuestionId' => 1,
			'subtitle' => 'Test subtitle',
		];

		$entity = $this->surveyAnswersTable->newDefaultEntity();
		$this->surveyAnswersTable->patchEntity($entity, $data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('title', $errors);
		$this->assertArrayHasKey('_required', $errors['title']);
		$this->assertSame('survey_answers::error_required', $errors['title']['_required']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\SurveyAnswersTable::validationDefault()
	 */
	public function testEntityValidationInvalidTypes(): void {
		$data = [
			'id' => 'not_an_integer',
			'surveyQuestionId' => 'not_an_integer',
			'title' => true,
			'subtitle' => true,
			'text' => true,
			'systemOrder' => 'not_an_integer',
			'active' => 'not_a_boolean',
			'deleted' => 'not_a_boolean',
		];

		$entity = $this->surveyAnswersTable->newDefaultEntity();
		$this->surveyAnswersTable->patchEntity($entity, $data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('id', $errors);
		$this->assertArrayHasKey('surveyQuestionId', $errors);
		$this->assertArrayHasKey('title', $errors);
		$this->assertArrayHasKey('subtitle', $errors);
		$this->assertArrayHasKey('text', $errors);
		$this->assertArrayHasKey('systemOrder', $errors);
		$this->assertArrayHasKey('active', $errors);
		$this->assertArrayHasKey('deleted', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\SurveyAnswersTable::validationDefault()
	 */
	public function testEntityValidationFieldLength(): void {
		$data = [
			'id' => 123456789123, // exceeds 11 char limit
			'surveyQuestionId' => 123456789123, // exceeds 11 char limit
			'title' => str_repeat('a', 256), // exceeds 255 char limit
			'subtitle' => str_repeat('b', 256), // exceeds 255 char limit
			'text' => str_repeat('c', 65536), // exceeds 65535 byte limit
		];

		$entity = $this->surveyAnswersTable->newDefaultEntity();
		$this->surveyAnswersTable->patchEntity($entity, $data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('id', $errors);
		$this->assertArrayHasKey('surveyQuestionId', $errors);
		$this->assertArrayHasKey('title', $errors);
		$this->assertArrayHasKey('subtitle', $errors);
		$this->assertArrayHasKey('text', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\SurveyAnswersTable::validationDefault()
	 */
	public function testEntityValidationBlankFields(): void {
		$data = [
			'surveyQuestionId' => 1,
			'title' => '   ', // Only whitespace
			'subtitle' => '   ', // Only whitespace
		];

		$entity = $this->surveyAnswersTable->newDefaultEntity();
		$this->surveyAnswersTable->patchEntity($entity, $data);
		$errors = $entity->getErrors();

		$this->assertArrayHasKey('title', $errors);
		$this->assertArrayHasKey('notBlank', $errors['title']);

		$this->assertArrayHasKey('subtitle', $errors);
		$this->assertArrayHasKey('notBlank', $errors['subtitle']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\SurveyAnswersTable::validationDefault()
	 */
	public function testEntityValidationOptionalFields(): void {
		$data = [
			'surveyQuestionId' => 1,
			'title' => 'Test Answer',
			'subtitle' => '', // Empty string should be allowed
			'text' => '', // Empty string should be allowed
		];

		$entity = $this->surveyAnswersTable->newDefaultEntity();
		$this->surveyAnswersTable->patchEntity($entity, $data);
		$errors = $entity->getErrors();

		// subtitle and text are allowEmptyString, so empty strings should be valid
		$this->assertArrayNotHasKey('subtitle', $errors);
		$this->assertArrayNotHasKey('text', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\SurveyAnswersTable::validationDefault()
	 */
	public function testEntityValidationNullValues(): void {
		$data = [
			'surveyQuestionId' => 1,
			'title' => 'Test Answer',
			'subtitle' => null, // Should be allowed
			'text' => null, // Should be allowed
			'systemOrder' => null, // Should be allowed
		];

		$entity = $this->surveyAnswersTable->newDefaultEntity();
		$this->surveyAnswersTable->patchEntity($entity, $data);
		$errors = $entity->getErrors();

		// subtitle, text, and systemOrder allow empty/null values
		$this->assertArrayNotHasKey('subtitle', $errors);
		$this->assertArrayNotHasKey('text', $errors);
		$this->assertArrayNotHasKey('systemOrder', $errors);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\SurveyAnswersTable::buildRules()
	 */
	public function testBuildRulesDeleteNoLinkedSurveys(): void {
		// Create a survey answer that is not linked to any surveys
		$data = [
			'id' => 123,
			'surveyQuestionId' => 1,
			'title' => 'Unlinked Answer',
			'active' => true,
			'deleted' => false,
		];

		$entity = $this->surveyAnswersTable->newDefaultEntity();
		$this->surveyAnswersTable->patchEntity($entity, $data, ['accessibleFields' => ['id']]);

		$result = $this->surveyAnswersTable->checkRules($entity, RulesChecker::DELETE);
		$this->assertTrue($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\SurveyAnswersTable::buildRules()
	 */
	public function testBuildRulesDeleteWithLinkedSurveys(): void {
		/** @var \Awyiss\Model\Entity\SurveyAnswer $entity */
		$entity = $this->surveyAnswersTable->get(1);

		$result = $this->surveyAnswersTable->checkRules($entity, RulesChecker::DELETE);

		$this->assertFalse($result);

		$errors = $entity->getErrors();
		$this->assertArrayHasKey('_general', $errors);
		$this->assertArrayHasKey('noLinkedSurveys', $errors['_general']);
		$this->assertSame('survey_answers::error_linked_surveys', $errors['_general']['noLinkedSurveys']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\SurveyAnswersTable::newDefaultEntity()
	 */
	public function testNewDefaultEntity(): void {
		/** @var \Awyiss\Model\Entity\SurveyAnswer $entity */
		$entity = $this->surveyAnswersTable->newDefaultEntity();

		$this->assertInstanceOf(SurveyAnswer::class, $entity);
		$this->assertTrue($entity->isNew());

		// Test default values
		$this->assertNull($entity->surveyQuestionId);
		$this->assertNull($entity->title);
		$this->assertNull($entity->subtitle);
		$this->assertNull($entity->text);
		$this->assertSame(0, $entity->systemOrder);
		$this->assertTrue($entity->active);
		$this->assertFalse($entity->deleted);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\SurveyAnswersTable::newDefaultEntity()
	 */
	public function testNewDefaultEntityWithData(): void {
		$additionalData = [
			'surveyQuestionId' => 2,
			'title' => 'Custom Answer',
			'subtitle' => 'Custom subtitle',
			'text' => 'Custom answer text',
			'systemOrder' => 5,
			'active' => false,
			'deleted' => true,
		];

		$entity = $this->surveyAnswersTable->newDefaultEntity($additionalData);

		$this->assertInstanceOf(SurveyAnswer::class, $entity);
		$this->assertTrue($entity->isNew());

		$this->assertSame(2, $entity->surveyQuestionId);
		$this->assertSame('Custom Answer', $entity->title);
		$this->assertSame('Custom subtitle', $entity->subtitle);
		$this->assertSame('Custom answer text', $entity->text);
		$this->assertSame(5, $entity->systemOrder);
		$this->assertFalse($entity->active);
		$this->assertTrue($entity->deleted);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\SurveyAnswersTable
	 */
	public function testCategoriesBehavior(): void {
		$this->assertTrue($this->surveyAnswersTable->hasBehavior('Categories'));

		$config = $this->surveyAnswersTable->getBehavior('Categories')->getConfig();

		$this->assertFalse($config['allowAggregation']);
		$this->assertFalse($config['allowUnassigned']);
		$this->assertTrue($config['enabled']);
		$this->assertEquals('SurveyQuestions', $config['associationName']);
		$this->assertEquals('question', $config['identifier']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\SurveyAnswersTable
	 */
	public function testSearchBehavior(): void {
		$this->assertTrue($this->surveyAnswersTable->hasBehavior('Search'));

		$config = $this->surveyAnswersTable->getBehavior('Search')->getConfig();

		$this->assertArrayHasKey('blocklistedColumns', $config);
		$this->assertEquals(['surveyQuestionId'], $config['blocklistedColumns']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\SurveyAnswersTable
	 */
	public function testSystemOrderBehavior(): void {
		$this->assertTrue($this->surveyAnswersTable->hasBehavior('SystemOrder'));

		$config = $this->surveyAnswersTable->getBehavior('SystemOrder')->getConfig();

		$this->assertArrayHasKey('relatedColumns', $config);
		$this->assertEquals(['surveyQuestionId'], $config['relatedColumns']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\SurveyAnswersTable::$translate
	 */
	public function testTranslateBehavior(): void {
		$this->assertTrue($this->surveyAnswersTable->hasBehavior('Translate'));

		$config = $this->surveyAnswersTable->getBehavior('Translate')->getConfig();

		$this->assertSame(Awyiss::REALM_FRONTEND, $config['realm']);

		$this->assertIsArray($config['fields']);
		$this->assertSame(['title', 'subtitle', 'text'], $config['fields']);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Table\SurveyAnswersTable::getDisabledQuestions()
	 */
	public function testGetDisabledQuestions(): void {
		$disabledQuestions = $this->surveyAnswersTable->getDisabledQuestions();

		$this->assertIsArray($disabledQuestions);

		// Questions 3 and 4 are free_text and info_text types,
		// which must never have answers.
		$this->assertSame([3, 4], $disabledQuestions);
	}
}
