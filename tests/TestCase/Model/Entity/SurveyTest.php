<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Model\Entity;


use Awyiss\Model\Entity\Form;
use Awyiss\Model\Entity\Survey;
use Awyiss\Model\Entity\SurveySurveyQuestion;
use Awyiss\Model\Enum\Survey\NextAction;
use Awyiss\Model\Enum\Survey\QuestionType;
use Awyiss\Model\Enum\Survey\Type;
use Awyiss\Test\TestSuite\TestCase;
use Cake\Datasource\FactoryLocator;
use Cake\View\View;
use InvalidArgumentException;


/**
 * Survey Entity Test Case
 *
 * @see \Awyiss\Model\Entity\Survey
 */
class SurveyTest extends TestCase {
	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Survey::$fieldMap
	 */
	public function testFieldMapCompleteness(): void {
		/** @var \Awyiss\Model\Table\SurveysTable $table */
		$table = FactoryLocator::get('Table')->get('Surveys');
		$entity = $table->newDefaultEntity();
		$entityArray = $entity->toArray();

		foreach ($entityArray as $key => $value) {
			$this->assertStringNotContainsString('_', $key);
		}
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Survey::$_accessible
	 */
	public function testAccessibleFields(): void {
		$entity = new Survey();

		$this->assertSame([
			'type' => true,
			'title' => true,
			'identifier' => true,
			'successMessage' => true,
			'failureMessage' => true,
			'finalAction' => true,
			'formId' => true,
			'active' => true,
			'_translations' => true,
			'_publicationData' => true,
			'mediaAssignments' => true,
			'mediaElementAssignments' => true,
		], $entity->getAccessible());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Survey::$defaultValues
	 */
	public function testDefaultValues(): void {
		/** @var \Awyiss\Model\Table\SurveysTable $table */
		$table = FactoryLocator::get('Table')->get('Surveys');
		$entity = $table->newDefaultEntity();

		$this->assertEquals(Type::Linear, $entity->type);
		$this->assertEquals(NextAction::SaveAndEnd, $entity->finalAction);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Survey
	 */
	public function testEntityConstruction(): void {
		$properties = [
			'id' => 1,
			'type' => Type::Configurator,
			'title' => 'Test Survey',
			'identifier' => 'test_survey',
			'success_message' => '<p>Success</p>',
			'failure_message' => '<p>Failure</p>',
			'final_action' => NextAction::SaveAndEnd,
			'form_id' => 1,
			'active' => true,
		];

		$entity = new Survey($properties);

		$this->assertEquals(1, $entity->id);
		$this->assertEquals(Type::Configurator, $entity->type);
		$this->assertEquals('Test Survey', $entity->title);
		$this->assertEquals('test_survey', $entity->identifier);
		$this->assertEquals('<p>Success</p>', $entity->successMessage);
		$this->assertEquals('<p>Failure</p>', $entity->failureMessage);
		$this->assertEquals(NextAction::SaveAndEnd, $entity->finalAction);
		$this->assertEquals(1, $entity->formId);
		$this->assertTrue($entity->active);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Survey::$fieldMap
	 */
	public function testFieldMapDuringConstruction(): void {
		$properties = [
			'success_message' => '<p>Success</p>',
			'failure_message' => '<p>Failure</p>',
			'final_action' => NextAction::SaveAndEnd,
			'form_id' => 2,
			'survey_survey_questions' => [],
			'survey_entries' => [],
		];
		$entity = new Survey($properties);
		$entityArray = $entity->toArray();

		foreach ($entityArray as $key => $value) {
			$this->assertStringNotContainsString('_', $key);
		}
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Survey::_setIdentifier()
	 * @noinspection PhpConditionAlreadyCheckedInspection
	 */
	public function testIdentifierCleaningViaPropertyAssignment(): void {
		$entity = new Survey();

		$entity->identifier = 'testSurvey';
		$this->assertEquals('testsurvey', $entity->identifier);

		$entity->identifier = 'Test Survey Name';
		$this->assertEquals('test_survey_name', $entity->identifier);

		$entity->identifier = 'Test-Survey-Name';
		$this->assertEquals('test_survey_name', $entity->identifier);

		$entity->identifier = 'Test@Survey#Name';
		$this->assertEquals('test_survey_name', $entity->identifier);

		$entity->identifier = 'already_underscored';
		$this->assertEquals('already_underscored', $entity->identifier);

		$entity->identifier = null;
		$this->assertNull($entity->identifier);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Survey::_setIdentifier()
	 */
	public function testIdentifierCleaningViaSetMethod(): void {
		$entity = new Survey();

		$entity->set('identifier', 'testSurvey');
		$this->assertEquals('testsurvey', $entity->identifier);

		$entity->set('identifier', 'Test Survey Name');
		$this->assertEquals('test_survey_name', $entity->identifier);

		$entity->set('identifier', 'Test-Survey-Name');
		$this->assertEquals('test_survey_name', $entity->identifier);

		$entity->set('identifier', 'Test@Survey#Name');
		$this->assertEquals('test_survey_name', $entity->identifier);

		$entity->set('identifier', 'already_underscored');
		$this->assertEquals('already_underscored', $entity->identifier);

		/** @noinspection PhpRedundantOptionalArgumentInspection */
		$entity->set('identifier', null);
		$this->assertNull($entity->identifier);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Survey::initialize()
	 */
	public function testInitializeLoadsQuestions(): void {
		/** @var \Awyiss\Model\Table\SurveysTable $table */
		$table = FactoryLocator::get('Table')->get('Surveys');
		/** @var \Awyiss\Model\Entity\Survey $entity */
		$entity = $table->get(1);
		$view = new View();
		$result = $entity->initialize($view);

		$this->assertSame($entity, $result);
		$this->assertCount(4, $entity->getQuestions());

		$lastActiveQuestion = $entity->getQuestions()->take(1, 3)->first();
		$this->assertSame('72054f17', $lastActiveQuestion->identifier);
		$this->assertCount(3, $lastActiveQuestion->surveySurveyAnswers);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Survey::initialize()
	 */
	public function testInitializeSetsProgress(): void {
		/** @var \Awyiss\Model\Table\SurveysTable $table */
		$table = FactoryLocator::get('Table')->get('Surveys');
		/** @var \Awyiss\Model\Entity\Survey $entity */
		$entity = $table->get(1);
		$view = new View();
		$progressData = [
			'8524de5e' => 4,
			'f69b1648' => [8, 9],
			'7d654446' => 'custom',
			'72054f17' => 11,
			'custom' => [
				'7d654446' => 'custom answer',
			],
		];

		/** @noinspection PhpRedundantOptionalArgumentInspection */
		$result = $entity->initialize($view, $progressData, null, false);

		$this->assertSame($entity, $result);
		$this->assertEquals([
			'8524de5e' => 4,
			'f69b1648' => [8, 9],
			'7d654446' => 'custom',
			'72054f17' => 11,
		], $entity->getProgress());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Survey::initialize()
	 */
	public function testInitializeWithPreviewLoadsInactiveQuestions(): void {
		/** @var \Awyiss\Model\Table\SurveysTable $table */
		$table = FactoryLocator::get('Table')->get('Surveys');
		/** @var \Awyiss\Model\Entity\Survey $entity */
		$entity = $table->get(1);
		$view = new View();

		$result = $entity->initialize($view, [], null, true);

		$this->assertSame($entity, $result);
		$this->assertCount(6, $entity->getQuestions());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Survey::initialize()
	 */
	public function testInitializeWithPreviewLoadsInactiveAnswers(): void {
		/** @var \Awyiss\Model\Table\SurveysTable $table */
		$table = FactoryLocator::get('Table')->get('Surveys');
		/** @var \Awyiss\Model\Entity\Survey $entity */
		$entity = $table->get(1);
		$view = new View();

		$entity->initialize($view, [], null, true);

		$lastActiveQuestion = $entity->getQuestions()->take(1, 4)->first();
		$this->assertSame('72054f17', $lastActiveQuestion->identifier);
		$this->assertCount(4, $lastActiveQuestion->surveySurveyAnswers);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Survey::getCurrentAction()
	 */
	public function testGetCurrentAction(): void {
		/** @var \Awyiss\Model\Table\SurveysTable $table */
		$table = FactoryLocator::get('Table')->get('Surveys');
		/** @var \Awyiss\Model\Entity\Survey $entity */
		$entity = $table->get(1);
		$view = new View();
		$entity->initialize($view, [], null, true);

		$this->assertInstanceOf(SurveySurveyQuestion::class, $entity->getCurrentAction());
		$this->assertSame(2, $entity->getCurrentAction()->id);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Survey::getCurrentAction()
	 */
	public function testGetCurrentActionWithProgress(): void {
		/** @var \Awyiss\Model\Table\SurveysTable $table */
		$table = FactoryLocator::get('Table')->get('Surveys');
		/** @var \Awyiss\Model\Entity\Survey $entity */
		$entity = $table->get(1);
		$view = new View();
		$entity->initialize($view, [
			'8524de5e' => 4,
			'f69b1648' => [8, 9],
			'7d654446' => 'custom',
			'72054f17' => 11,
			'custom' => [
				'7d654446' => 'custom answer',
			],
		], null, true);

		$this->assertInstanceOf(SurveySurveyQuestion::class, $entity->getCurrentAction());
		$this->assertSame(4, $entity->getCurrentAction()->id);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Survey::getCurrentAction()
	 */
	public function testGetCurrentActionWithoutQuestions(): void {
		$entity = new Survey();

		$this->assertNull($entity->getCurrentAction());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Survey::setCurrentAction()
	 */
	public function testSetCurrentActionWithValidQuestion(): void {
		/** @var \Awyiss\Model\Table\SurveysTable $table */
		$table = FactoryLocator::get('Table')->get('Surveys');
		/** @var \Awyiss\Model\Entity\Survey $entity */
		$entity = $table->get(1);
		$view = new View();
		$entity->initialize($view, [], null, true);

		$question = $entity->getQuestions()->last();

		$result = $entity->setCurrentAction($question);

		$this->assertSame($entity, $result);
		$this->assertEquals($question, $entity->getCurrentAction());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Survey::setCurrentAction()
	 */
	public function testSetCurrentActionWithInvalidQuestion(): void {
		/** @var \Awyiss\Model\Table\SurveysTable $table */
		$table = FactoryLocator::get('Table')->get('Surveys');
		/** @var \Awyiss\Model\Entity\Survey $entity */
		$entity = $table->get(1);
		$view = new View();
		$entity->initialize($view, [], null, true);

		$invalidQuestion = new SurveySurveyQuestion(['id' => 999, 'survey_id' => 1]);

		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('The provided question is not part of the survey.');

		// Attempt to set a question not part of the survey
		$entity->setCurrentAction($invalidQuestion);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Survey::setCurrentAction()
	 */
	public function testSetCurrentActionWithValidEnum(): void {
		$entity = new Survey();
		$view = new View();
		$entity->initialize($view, [], null, true);

		$entity->setCurrentAction(NextAction::SaveAndEnd);

		$this->assertEquals(NextAction::SaveAndEnd, $entity->getCurrentAction());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Survey::setCurrentAction()
	 */
	public function testSetCurrentActionWithInvalidEnum(): void {
		/** @var \Awyiss\Model\Table\SurveysTable $table */
		$table = FactoryLocator::get('Table')->get('Surveys');
		/** @var \Awyiss\Model\Entity\Survey $entity */
		$entity = $table->get(1);

		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('The provided action is invalid. `InfoText` given.');

		// Invalid enum case
		$entity->setCurrentAction(QuestionType::InfoText);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Survey::setCurrentAction()
	 */
	public function testSetCurrentActionWithNull(): void {
		/** @var \Awyiss\Model\Table\SurveysTable $table */
		$table = FactoryLocator::get('Table')->get('Surveys');
		/** @var \Awyiss\Model\Entity\Survey $entity */
		$entity = $table->get(1);
		$view = new View();
		$entity->initialize($view, [], null, true);

		// Setting current action to null should reset it
		$result = $entity->setCurrentAction(null);

		$this->assertSame($entity, $result);
		$this->assertInstanceOf(SurveySurveyQuestion::class, $entity->getCurrentAction());
		$this->assertSame(2, $entity->getCurrentAction()->id);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Survey::setCurrentAction()
	 */
	public function testSetCurrentActionWithFalse(): void {
		/** @var \Awyiss\Model\Table\SurveysTable $table */
		$table = FactoryLocator::get('Table')->get('Surveys');
		/** @var \Awyiss\Model\Entity\Survey $entity */
		$entity = $table->get(1);
		$view = new View();
		$entity->initialize($view, [], null, true);

		// Setting current action to false should reset it
		$result = $entity->setCurrentAction(false);

		$this->assertSame($entity, $result);
		$this->assertFalse($entity->getCurrentAction());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Survey::getNextAction()
	 */
	public function testGetNextAction(): void {
		/** @var \Awyiss\Model\Table\SurveysTable $table */
		$table = FactoryLocator::get('Table')->get('Surveys');
		/** @var \Awyiss\Model\Entity\Survey $entity */
		$entity = $table->get(1);
		/** @noinspection PhpRedundantOptionalArgumentInspection */
		$entity->initialize(new View(), []);

		$result = $entity->getNextAction();

		// Should return the second question (f69b1648)
		$this->assertInstanceOf(SurveySurveyQuestion::class, $result);
		$this->assertEquals('f69b1648', $result->identifier);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Survey::getNextAction()
	 */
	public function testGetNextActionWithProgress(): void {
		/** @var \Awyiss\Model\Table\SurveysTable $table */
		$table = FactoryLocator::get('Table')->get('Surveys');
		/** @var \Awyiss\Model\Entity\Survey $entity */
		$entity = $table->get(1);
		$entity->initialize(new View(), [
			'8524de5e' => 4,
			'f69b1648' => [8, 9],
		]);

		$result = $entity->getNextAction();

		$this->assertInstanceOf(SurveySurveyQuestion::class, $result);
		$this->assertEquals('72054f17', $result->identifier);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Survey::getNextAction()
	 */
	public function testGetNextActionWithProgressLast(): void {
		/** @var \Awyiss\Model\Table\SurveysTable $table */
		$table = FactoryLocator::get('Table')->get('Surveys');
		/** @var \Awyiss\Model\Entity\Survey $entity */
		$entity = $table->get(1);
		$entity->initialize(new View(), [
			'8524de5e' => 4,
			'f69b1648' => [8, 9],
			'7d654446' => 'custom',
			'custom' => [
				'7d654446' => 'custom answer',
			],
		]);

		$result = $entity->getNextAction();

		$this->assertInstanceOf(NextAction::class, $result);
		$this->assertEquals(NextAction::SaveAndEnd, $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Survey::getNextAction()
	 */
	public function testGetNextActionWithProgressEnd(): void {
		/** @var \Awyiss\Model\Table\SurveysTable $table */
		$table = FactoryLocator::get('Table')->get('Surveys');
		/** @var \Awyiss\Model\Entity\Survey $entity */
		$entity = $table->get(1);
		$entity->initialize(new View(), [
			'8524de5e' => 4,
			'f69b1648' => [8, 9],
			'7d654446' => 'custom',
			'72054f17' => 11,
			'custom' => [
				'7d654446' => 'custom answer',
			],
		]);

		$result = $entity->getNextAction();

		// Should return false, as the last question (72054f17) is already answered
		$this->assertFalse($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Survey::getNextAction()
	 */
	public function testGetNextActionWithQuestionNextAction(): void {
		/** @var \Awyiss\Model\Table\SurveysTable $table */
		$table = FactoryLocator::get('Table')->get('Surveys');
		/** @var \Awyiss\Model\Entity\Survey $entity */
		$entity = $table->get(1);
		$entity->initialize(new View(), [
			'8524de5e' => 4,
			'f69b1648' => [8, 9],
			'7d654446' => 'custom',
			'72054f17' => 11,
			'custom' => [
				'7d654446' => 'custom answer',
			],
		]);

		$question = $entity->getQuestions()->take(1, 2)->first();
		$this->assertEquals('7d654446', $question->identifier);

		$result = $entity->getNextAction($question);

		$this->assertEquals('72054f17', $result->identifier);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Survey::getNextAction()
	 */
	public function testGetNextActionWithAnswerNextAction(): void {
		/** @var \Awyiss\Model\Table\SurveysTable $table */
		$table = FactoryLocator::get('Table')->get('Surveys');
		/** @var \Awyiss\Model\Entity\Survey $entity */
		$entity = $table->get(1);
		$entity->initialize(new View(), [
			'8524de5e' => 5,
			'f69b1648' => [8, 9],
			'7d654446' => 'custom',
			'72054f17' => 11,
			'custom' => [
				'7d654446' => 'custom answer',
			],
		]);

		/** @var \Awyiss\Model\Entity\SurveySurveyQuestion $question */
		$question = $entity->getQuestions()->first();
		$answers = $question->surveySurveyAnswers;

		$this->assertNull($answers[4]->nextAction);
		$result = $entity->getNextAction($question, 4);
		$this->assertInstanceOf(SurveySurveyQuestion::class, $result);
		$this->assertEquals('f69b1648', $result->identifier);

		$this->assertInstanceOf(NextAction::class, $answers[5]->nextAction);
		$this->assertSame('0194a883', $answers[5]->nextActionTarget);
		// If the next action target of an answer points to an inactive question, the next action is the next active question
		$result = $entity->getNextAction($question, 5);
		$this->assertInstanceOf(SurveySurveyQuestion::class, $result);
		$this->assertEquals('f69b1648', $result->identifier);

		$this->assertInstanceOf(NextAction::class, $answers[6]->nextAction);
		$this->assertSame('72054f17', $answers[6]->nextActionTarget);
		$result = $entity->getNextAction($question, 6);
		$this->assertInstanceOf(SurveySurveyQuestion::class, $result);
		$this->assertEquals('72054f17', $result->identifier);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Survey::hasCycle()
	 */
	public function testHasCycleFalse(): void {
		/** @var \Awyiss\Model\Table\SurveysTable $table */
		$table = FactoryLocator::get('Table')->get('Surveys');
		/** @var \Awyiss\Model\Entity\Survey $entity */
		$entity = $table->get(
			1,
			contain: [
				'SurveySurveyQuestions' => [
					'SurveySurveyAnswers',
				],
			],
		);
		/** @noinspection PhpRedundantOptionalArgumentInspection */
		$entity->initialize(new View(), []);

		$this->assertFalse($entity->hasCycle());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Survey::hasCycle()
	 */
	public function testHasCycleTrue(): void {
		/** @var \Awyiss\Model\Table\SurveysTable $table */
		$table = FactoryLocator::get('Table')->get('Surveys');
		/** @var \Awyiss\Model\Entity\Survey $entity */
		$entity = $table->get(
			1,
			contain: [
				'SurveySurveyQuestions' => [
					'SurveySurveyAnswers',
				],
			],
		);
		/** @noinspection PhpRedundantOptionalArgumentInspection */
		$entity->initialize(new View(), []);

		// Get a question
		/** @var \Awyiss\Model\Entity\SurveySurveyQuestion $question */
		$question = $entity->surveySurveyQuestions[3];
		// Set the target of the last question to point back to the second question
		$question->nextAction = NextAction::SpecificQuestion;
		$question->nextActionTarget = '8524de5e';

		$this->assertTrue($entity->hasCycle());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Survey::buildQuestionsGraph()
	 */
	public function testBuildQuestionsGraph(): void {
		/** @var \Awyiss\Model\Table\SurveysTable $table */
		$table = FactoryLocator::get('Table')->get('Surveys');
		/** @var \Awyiss\Model\Entity\Survey $entity */
		$entity = $table->get(
			1,
			contain: [
				'SurveySurveyQuestions' => [
					'SurveySurveyAnswers',
				],
			],
		);
		/** @noinspection PhpRedundantOptionalArgumentInspection */
		$entity->initialize(new View(), []);

		$result = $entity->buildQuestionsGraph();

		$this->assertSame([
			'8524de5e' => [
				'0194a883',
				'72054f17',
				'f69b1648',
			],
			'f69b1648' => [
				'0194a883',
			],
			'0194a883' => [
				'7d654446',
			],
			'7d654446' => [
				'72054f17',
			],
			'72054f17' => [
				'e5f3b6a9',
				'e5f3b6a9',
			],
			'e5f3b6a9' => [],
		], $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Survey::buildResultsArray()
	 */
	public function testBuildResultsArray(): void {
		/** @var \Awyiss\Model\Table\SurveysTable $table */
		$table = FactoryLocator::get('Table')->get('Surveys');
		/** @var \Awyiss\Model\Entity\Survey $entity */
		$entity = $table->get(
			1,
			contain: [
				'SurveySurveyQuestions' => [
					'SurveySurveyAnswers',
				],
			],
		);
		/** @noinspection PhpRedundantOptionalArgumentInspection */
		$entity->initialize(new View(), []);

		$result = $entity->buildResultsArray();

		$this->assertSame([
			[
				'8524de5e' => 4,
				'f69b1648' => [
					0 => 7,
				],
				'7d654446' => 'custom',
				'72054f17' => 10,
			],
			[
				'8524de5e' => 4,
				'f69b1648' => [
					0 => 7,
				],
				'7d654446' => 'custom',
				'72054f17' => 11,
			],
			[
				'8524de5e' => 4,
				'f69b1648' => [
					0 => 7,
				],
				'7d654446' => 'custom',
				'72054f17' => 12,
			],
			[
				'8524de5e' => 4,
				'f69b1648' => [
					0 => 8,
				],
				'7d654446' => 'custom',
				'72054f17' => 10,
			],
			[
				'8524de5e' => 4,
				'f69b1648' => [
					0 => 8,
				],
				'7d654446' => 'custom',
				'72054f17' => 11,
			],
			[
				'8524de5e' => 4,
				'f69b1648' => [
					0 => 8,
				],
				'7d654446' => 'custom',
				'72054f17' => 12,
			],
			[
				'8524de5e' => 4,
				'f69b1648' => [
					0 => 7,
					1 => 8,
				],
				'7d654446' => 'custom',
				'72054f17' => 10,
			],
			[
				'8524de5e' => 4,
				'f69b1648' => [
					0 => 7,
					1 => 8,
				],
				'7d654446' => 'custom',
				'72054f17' => 11,
			],
			[
				'8524de5e' => 4,
				'f69b1648' => [
					0 => 7,
					1 => 8,
				],
				'7d654446' => 'custom',
				'72054f17' => 12,
			],
			[
				'8524de5e' => 4,
				'f69b1648' => [
					0 => 9,
				],
				'7d654446' => 'custom',
				'72054f17' => 10,
			],
			[
				'8524de5e' => 4,
				'f69b1648' => [
					0 => 9,
				],
				'7d654446' => 'custom',
				'72054f17' => 11,
			],
			[
				'8524de5e' => 4,
				'f69b1648' => [
					0 => 9,
				],
				'7d654446' => 'custom',
				'72054f17' => 12,
			],
			[
				'8524de5e' => 4,
				'f69b1648' => [
					0 => 7,
					1 => 9,
				],
				'7d654446' => 'custom',
				'72054f17' => 10,
			],
			[
				'8524de5e' => 4,
				'f69b1648' => [
					0 => 7,
					1 => 9,
				],
				'7d654446' => 'custom',
				'72054f17' => 11,
			],
			[
				'8524de5e' => 4,
				'f69b1648' => [
					0 => 7,
					1 => 9,
				],
				'7d654446' => 'custom',
				'72054f17' => 12,
			],
			[
				'8524de5e' => 4,
				'f69b1648' => [
					0 => 8,
					1 => 9,
				],
				'7d654446' => 'custom',
				'72054f17' => 10,
			],
			[
				'8524de5e' => 4,
				'f69b1648' => [
					0 => 8,
					1 => 9,
				],
				'7d654446' => 'custom',
				'72054f17' => 11,
			],
			[
				'8524de5e' => 4,
				'f69b1648' => [
					0 => 8,
					1 => 9,
				],
				'7d654446' => 'custom',
				'72054f17' => 12,
			],
			[
				'8524de5e' => 4,
				'f69b1648' => [
					0 => 7,
					1 => 8,
					2 => 9,
				],
				'7d654446' => 'custom',
				'72054f17' => 10,
			],
			[
				'8524de5e' => 4,
				'f69b1648' => [
					0 => 7,
					1 => 8,
					2 => 9,
				],
				'7d654446' => 'custom',
				'72054f17' => 11,
			],
			[
				'8524de5e' => 4,
				'f69b1648' => [
					0 => 7,
					1 => 8,
					2 => 9,
				],
				'7d654446' => 'custom',
				'72054f17' => 12,
			],
			[
				'8524de5e' => 4,
				'f69b1648' => [
					0 => '*',
				],
				'7d654446' => 'custom',
				'72054f17' => 10,
			],
			[
				'8524de5e' => 4,
				'f69b1648' => [
					0 => '*',
				],
				'7d654446' => 'custom',
				'72054f17' => 11,
			],
			[
				'8524de5e' => 4,
				'f69b1648' => [
					0 => '*',
				],
				'7d654446' => 'custom',
				'72054f17' => 12,
			],
			[
				'8524de5e' => 5,
				'f69b1648' => [
					0 => 7,
				],
				'7d654446' => 'custom',
				'72054f17' => 10,
			],
			[
				'8524de5e' => 5,
				'f69b1648' => [
					0 => 7,
				],
				'7d654446' => 'custom',
				'72054f17' => 11,
			],
			[
				'8524de5e' => 5,
				'f69b1648' => [
					0 => 7,
				],
				'7d654446' => 'custom',
				'72054f17' => 12,
			],
			[
				'8524de5e' => 5,
				'f69b1648' => [
					0 => 8,
				],
				'7d654446' => 'custom',
				'72054f17' => 10,
			],
			[
				'8524de5e' => 5,
				'f69b1648' => [
					0 => 8,
				],
				'7d654446' => 'custom',
				'72054f17' => 11,
			],
			[
				'8524de5e' => 5,
				'f69b1648' => [
					0 => 8,
				],
				'7d654446' => 'custom',
				'72054f17' => 12,
			],
			[
				'8524de5e' => 5,
				'f69b1648' => [
					0 => 7,
					1 => 8,
				],
				'7d654446' => 'custom',
				'72054f17' => 10,
			],
			[
				'8524de5e' => 5,
				'f69b1648' => [
					0 => 7,
					1 => 8,
				],
				'7d654446' => 'custom',
				'72054f17' => 11,
			],
			[
				'8524de5e' => 5,
				'f69b1648' => [
					0 => 7,
					1 => 8,
				],
				'7d654446' => 'custom',
				'72054f17' => 12,
			],
			[
				'8524de5e' => 5,
				'f69b1648' => [
					0 => 9,
				],
				'7d654446' => 'custom',
				'72054f17' => 10,
			],
			[
				'8524de5e' => 5,
				'f69b1648' => [
					0 => 9,
				],
				'7d654446' => 'custom',
				'72054f17' => 11,
			],
			[
				'8524de5e' => 5,
				'f69b1648' => [
					0 => 9,
				],
				'7d654446' => 'custom',
				'72054f17' => 12,
			],
			[
				'8524de5e' => 5,
				'f69b1648' => [
					0 => 7,
					1 => 9,
				],
				'7d654446' => 'custom',
				'72054f17' => 10,
			],
			[
				'8524de5e' => 5,
				'f69b1648' => [
					0 => 7,
					1 => 9,
				],
				'7d654446' => 'custom',
				'72054f17' => 11,
			],
			[
				'8524de5e' => 5,
				'f69b1648' => [
					0 => 7,
					1 => 9,
				],
				'7d654446' => 'custom',
				'72054f17' => 12,
			],
			[
				'8524de5e' => 5,
				'f69b1648' => [
					0 => 8,
					1 => 9,
				],
				'7d654446' => 'custom',
				'72054f17' => 10,
			],
			[
				'8524de5e' => 5,
				'f69b1648' => [
					0 => 8,
					1 => 9,
				],
				'7d654446' => 'custom',
				'72054f17' => 11,
			],
			[
				'8524de5e' => 5,
				'f69b1648' => [
					0 => 8,
					1 => 9,
				],
				'7d654446' => 'custom',
				'72054f17' => 12,
			],
			[
				'8524de5e' => 5,
				'f69b1648' => [
					0 => 7,
					1 => 8,
					2 => 9,
				],
				'7d654446' => 'custom',
				'72054f17' => 10,
			],
			[
				'8524de5e' => 5,
				'f69b1648' => [
					0 => 7,
					1 => 8,
					2 => 9,
				],
				'7d654446' => 'custom',
				'72054f17' => 11,
			],
			[
				'8524de5e' => 5,
				'f69b1648' => [
					0 => 7,
					1 => 8,
					2 => 9,
				],
				'7d654446' => 'custom',
				'72054f17' => 12,
			],
			[
				'8524de5e' => 5,
				'f69b1648' => [
					0 => '*',
				],
				'7d654446' => 'custom',
				'72054f17' => 10,
			],
			[
				'8524de5e' => 5,
				'f69b1648' => [
					0 => '*',
				],
				'7d654446' => 'custom',
				'72054f17' => 11,
			],
			[
				'8524de5e' => 5,
				'f69b1648' => [
					0 => '*',
				],
				'7d654446' => 'custom',
				'72054f17' => 12,
			],
			[
				'8524de5e' => 6,
				'72054f17' => 10,
			],
			[
				'8524de5e' => 6,
				'72054f17' => 11,
			],
			[
				'8524de5e' => 6,
				'72054f17' => 12,
			],
		], $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Survey::buildResultPath()
	 */
	public function testBuildResultPath(): void {
		/** @var \Awyiss\Model\Table\SurveysTable $table */
		$table = FactoryLocator::get('Table')->get('Surveys');
		/** @var \Awyiss\Model\Entity\Survey $entity */
		$entity = $table->get(1);
		$view = new View();
		$progressData = [
			'8524de5e' => 4,
			'f69b1648' => [8, 9],
			'7d654446' => 'custom',
			'72054f17' => 11,
			'custom' => [
				'7d654446' => 'custom answer',
			],
		];

		/** @noinspection PhpRedundantOptionalArgumentInspection */
		$entity->initialize($view, $progressData, null, false);

		$this->assertSame('8524de5e:4-f69b1648:8,9-7d654446:custom-72054f17:11', $entity->buildResultPath());

		$progressData = [
			'8524de5e' => 5,
			'f69b1648' => [7],
			'7d654446' => 'custom',
			'72054f17' => 10,
			'custom' => [
				'7d654446' => 'custom answer',
			],
		];

		$entity->setProgress($progressData);

		$this->assertSame('8524de5e:5-f69b1648:7-7d654446:custom-72054f17:10', $entity->buildResultPath());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Survey::getNextActionEnum()
	 */
	public function testGetNextActionEnum(): void {
		$entity = new Survey();

		$result = $entity->getNextActionEnum();

		$this->assertStringContainsString('NextAction', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Survey::getQuestionTypeEnum()
	 */
	public function testGetQuestionTypeEnum(): void {
		$entity = new Survey();

		$result = $entity->getQuestionTypeEnum();

		$this->assertStringContainsString('QuestionType', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Survey::setProgress()
	 */
	public function testSetProgressValidatesQuestionSequence(): void {
		$entity = new Survey(['id' => 1]);
		/** @noinspection PhpRedundantOptionalArgumentInspection */
		$entity->initialize(new View(), []);

		// Try to progress to second question without answering first - should break at validation
		$progressData = [
			'f69b1648' => [7, 8], // Second question without first
		];

		$result = $entity->setProgress($progressData);

		$this->assertSame($entity, $result);
		$this->assertEquals([], $entity->getProgress());
		$this->assertInstanceOf(SurveySurveyQuestion::class, $entity->getCurrentAction());
		$this->assertSame(2, $entity->getCurrentAction()->id);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Survey::setProgress()
	 */
	public function testSetProgressValidatesAnswerSequenceWithPreviousQuestion(): void {
		$entity = new Survey(['id' => 1]);
		/** @noinspection PhpRedundantOptionalArgumentInspection */
		$entity->initialize(new View(), []);

		// Valid first question, then try invalid sequence for second
		$progressData = [
			'8524de5e' => 4, // First question valid
			'7d654446' => 'text', // Try to jump to fourth question instead of second
		];

		$result = $entity->setProgress($progressData);

		$this->assertSame($entity, $result);
		// Should stop at first question due to validation failure on second
		$this->assertEquals(['8524de5e' => 4], $entity->getProgress());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Survey::setProgress()
	 */
	public function testSetProgressTypeConversionSingleChoice(): void {
		$entity = new Survey(['id' => 1]);
		/** @noinspection PhpRedundantOptionalArgumentInspection */
		$entity->initialize(new View(), []);

		// String answer should be converted to int for single choice
		$progressData = [
			'8524de5e' => '4', // String should become int
		];

		$result = $entity->setProgress($progressData);

		$this->assertSame($entity, $result);
		$this->assertEquals(['8524de5e' => 4], $entity->getProgress()); // Should be int, not string
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Survey::setProgress()
	 */
	public function testSetProgressTypeConversionMultipleChoice(): void {
		$entity = new Survey(['id' => 1]);
		/** @noinspection PhpRedundantOptionalArgumentInspection */
		$entity->initialize(new View(), []);

		// Multiple choice answers should be converted to ints except 'custom'
		$progressData = [
			'8524de5e' => 4,
			'f69b1648' => ['7', '8'], // Strings should become ints
		];

		$result = $entity->setProgress($progressData);

		$this->assertSame($entity, $result);
		$this->assertEquals(['8524de5e' => 4, 'f69b1648' => [7, 8]], $entity->getProgress());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Survey::setProgress()
	 */
	public function testSetProgressTypeConversionInfoText(): void {
		$entity = new Survey(['id' => 1]);
		$entity->initialize(new View(), [], null, true); // Use preview mode to include inactive questions

		// Info text questions should always get null regardless of input
		$progressData = [
			'8524de5e' => 5, // Jump to info text question (only works in preview)
			'0194a883' => 'anything', // Any input should become null for info text
		];

		$result = $entity->setProgress($progressData);

		$this->assertSame($entity, $result);
		$this->assertEquals(['8524de5e' => 5, '0194a883' => null], $entity->getProgress());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Survey::setProgress()
	 */
	public function testSetProgressValidateProgressFreeTextEmpty(): void {
		$entity = new Survey(['id' => 1]);
		/** @noinspection PhpRedundantOptionalArgumentInspection */
		$entity->initialize(new View(), []);

		// Empty string should fail validateProgress for free text
		$progressData = [
			'8524de5e' => 4, // First question
			'f69b1648' => [7, 8], // Second question
			'7d654446' => '', // Empty string should fail for free text
		];

		$result = $entity->setProgress($progressData);

		$this->assertSame($entity, $result);
		$this->assertEquals(['8524de5e' => 4, 'f69b1648' => [7, 8]], $entity->getProgress());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Survey::setProgress()
	 */
	public function testSetProgressCustomAnswerHandlingFreeText(): void {
		$entity = new Survey(['id' => 1]);
		/** @noinspection PhpRedundantOptionalArgumentInspection */
		$entity->initialize(new View(), []);

		// Free text with custom answer
		$progressData = [
			'8524de5e' => 4, // First question
			'f69b1648' => [7, 8], // Second question
			'7d654446' => 'custom', // Free text question
			'custom' => ['7d654446' => 'My free text custom answer'],
		];

		$result = $entity->setProgress($progressData);

		$this->assertSame($entity, $result);
		$this->assertEquals(['8524de5e' => 4, 'f69b1648' => [7, 8], '7d654446' => 'custom'], $entity->getProgress());
		$this->assertEquals(['7d654446' => 'My free text custom answer'], $entity->getCustomAnswers());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Survey::setProgress()
	 */
	public function testSetProgressSetsCurrentActionToNextActionAfterProcessing(): void {
		$entity = new Survey(['id' => 1]);
		/** @noinspection PhpRedundantOptionalArgumentInspection */
		$entity->initialize(new View(), []);

		// After setting progress, currentAction should be set to getNextAction() result
		$entity->setProgress(['8524de5e' => 4, 'f69b1648' => [7, 8], '7d654446' => 'free text answer']);

		// Should call getNextAction() and set currentAction accordingly
		$currentAction = $entity->getCurrentAction();
		$this->assertEquals('72054f17', $currentAction->identifier);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Survey::setProgress()
	 */
	public function testSetProgressAnswerSpecificJumpSkipsInactiveQuestion(): void {
		$entity = new Survey(['id' => 1]);
		/** @noinspection PhpRedundantOptionalArgumentInspection */
		$entity->initialize(new View(), []);

		// Answer 5 tries to jump to inactive question 0194a883, should fall back to next question
		$progressData = [
			'8524de5e' => 5, // This answer tries to jump to inactive question 0194a883
		];

		$result = $entity->setProgress($progressData);

		$this->assertSame($entity, $result);
		$this->assertEquals(['8524de5e' => 5], $entity->getProgress());

		// Should fall back to next active question (f69b1648) since target is inactive
		$currentAction = $entity->getCurrentAction();
		$this->assertEquals('f69b1648', $currentAction->identifier);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Survey::setProgress()
	 */
	public function testSetProgressAnswerSpecificJumpNotSkipsInactiveQuestionInPreviewMode(): void {
		$entity = new Survey(['id' => 1]);
		/** @noinspection PhpRedundantOptionalArgumentInspection */
		$entity->initialize(new View(), [], null, true); // Use preview mode to include inactive questions

		// Answer 5 tries to jump to inactive question 0194a883, should not fall back in preview mode
		$progressData = [
			'8524de5e' => 5, // This answer tries to jump to inactive question 0194a883
		];

		$result = $entity->setProgress($progressData);

		$this->assertSame($entity, $result);
		$this->assertEquals(['8524de5e' => 5], $entity->getProgress());

		// Should stay on the inactive question since we're in preview mode
		$currentAction = $entity->getCurrentAction();
		$this->assertEquals('0194a883', $currentAction->identifier);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Survey::getForm()
	 */
	public function testGetFormWithAnswerShowForm(): void {
		/** @var \Awyiss\Model\Table\SurveysTable $table */
		$table = FactoryLocator::get('Table')->get('Surveys');
		/** @var \Awyiss\Model\Entity\Survey $entity */
		$entity = $table->get(1);
		/** @noinspection PhpRedundantOptionalArgumentInspection */
		$entity->initialize(new View(), ['8524de5e' => 4]);

		// Get a question and modify its answer to have ShowForm action
		/** @var \Awyiss\Model\Entity\SurveySurveyQuestion $question */
		$question = $entity->getQuestions()->first();
		/** @var \Awyiss\Model\Entity\SurveySurveyAnswer $answer */
		$answer = $question->surveySurveyAnswers[4];
		$answer->nextAction = NextAction::ShowForm;
		$answer->nextActionTarget = '2';

		$result = $entity->getForm($question, $answer);

		$this->assertInstanceOf(Form::class, $result);
		$this->assertEquals(2, $result->id);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Survey::getForm()
	 */
	public function testGetFormWithAnswerSaveAndShowForm(): void {
		/** @var \Awyiss\Model\Table\SurveysTable $table */
		$table = FactoryLocator::get('Table')->get('Surveys');
		/** @var \Awyiss\Model\Entity\Survey $entity */
		$entity = $table->get(1);
		/** @noinspection PhpRedundantOptionalArgumentInspection */
		$entity->initialize(new View(), ['8524de5e' => 4]);

		// Get a question and modify its answer to have SaveAndShowForm action
		/** @var \Awyiss\Model\Entity\SurveySurveyQuestion $question */
		$question = $entity->getQuestions()->first();
		/** @var \Awyiss\Model\Entity\SurveySurveyAnswer $answer */
		$answer = $question->surveySurveyAnswers[4];
		$answer->nextAction = NextAction::SaveAndShowForm;
		$answer->nextActionTarget = '1';

		$result = $entity->getForm($question, $answer);

		$this->assertInstanceOf(Form::class, $result);
		$this->assertEquals(1, $result->id);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Survey::getForm()
	 */
	public function testGetFormWithAnswerShowFormAndSave(): void {
		/** @var \Awyiss\Model\Table\SurveysTable $table */
		$table = FactoryLocator::get('Table')->get('Surveys');
		/** @var \Awyiss\Model\Entity\Survey $entity */
		$entity = $table->get(1);
		/** @noinspection PhpRedundantOptionalArgumentInspection */
		$entity->initialize(new View(), ['8524de5e' => 4]);

		// Get a question and modify its answer to have ShowFormAndSave action
		/** @var \Awyiss\Model\Entity\SurveySurveyQuestion $question */
		$question = $entity->getQuestions()->first();
		/** @var \Awyiss\Model\Entity\SurveySurveyAnswer $answer */
		$answer = $question->surveySurveyAnswers[4];
		$answer->nextAction = NextAction::ShowFormAndSave;
		$answer->nextActionTarget = '1';

		$result = $entity->getForm($question, $answer);

		$this->assertInstanceOf(Form::class, $result);
		$this->assertEquals(1, $result->id);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Survey::getForm()
	 */
	public function testGetFormWithAnswerUsesDefaultFormWhenNoTarget(): void {
		/** @var \Awyiss\Model\Table\SurveysTable $table */
		$table = FactoryLocator::get('Table')->get('Surveys');
		/** @var \Awyiss\Model\Entity\Survey $entity */
		$entity = $table->get(1);
		/** @noinspection PhpRedundantOptionalArgumentInspection */
		$entity->initialize(new View(), ['8524de5e' => 4]);

		// Get a question and modify its answer to have ShowForm action but no target
		/** @var \Awyiss\Model\Entity\SurveySurveyQuestion $question */
		$question = $entity->getQuestions()->first();
		/** @var \Awyiss\Model\Entity\SurveySurveyAnswer $answer */
		$answer = $question->surveySurveyAnswers[4];
		$answer->nextAction = NextAction::ShowForm;
		$answer->nextActionTarget = null; // Should fall back to survey's formId

		$result = $entity->getForm($question, $answer);

		$this->assertInstanceOf(Form::class, $result);
		$this->assertEquals(1, $result->id); // Should use survey's formId (1)
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Survey::getForm()
	 */
	public function testGetFormWithQuestionShowForm(): void {
		/** @var \Awyiss\Model\Table\SurveysTable $table */
		$table = FactoryLocator::get('Table')->get('Surveys');
		/** @var \Awyiss\Model\Entity\Survey $entity */
		$entity = $table->get(1);
		/** @noinspection PhpRedundantOptionalArgumentInspection */
		$entity->initialize(new View(), ['8524de5e' => 4]);

		// Get a question and modify it to have ShowForm action
		/** @var \Awyiss\Model\Entity\SurveySurveyQuestion $question */
		$question = $entity->getQuestions()->first();
		$question->nextAction = NextAction::ShowForm;
		$question->nextActionTarget = '2';

		// Ensure no answer has form action (should fall back to question)
		foreach ($question->surveySurveyAnswers as $answer) {
			$answer->nextAction = NextAction::NextQuestion;
		}

		$result = $entity->getForm($question);

		$this->assertInstanceOf(Form::class, $result);
		$this->assertEquals(2, $result->id);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Survey::getForm()
	 */
	public function testGetFormWithQuestionSaveAndShowForm(): void {
		/** @var \Awyiss\Model\Table\SurveysTable $table */
		$table = FactoryLocator::get('Table')->get('Surveys');
		/** @var \Awyiss\Model\Entity\Survey $entity */
		$entity = $table->get(1);
		/** @noinspection PhpRedundantOptionalArgumentInspection */
		$entity->initialize(new View(), ['8524de5e' => 4]);

		// Get a question and modify it to have SaveAndShowForm action
		/** @var \Awyiss\Model\Entity\SurveySurveyQuestion $question */
		$question = $entity->getQuestions()->first();
		$question->nextAction = NextAction::SaveAndShowForm;
		$question->nextActionTarget = '1';

		$result = $entity->getForm($question);

		$this->assertInstanceOf(Form::class, $result);
		$this->assertEquals(1, $result->id);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Survey::getForm()
	 */
	public function testGetFormWithQuestionUsesDefaultFormWhenNoTarget(): void {
		/** @var \Awyiss\Model\Table\SurveysTable $table */
		$table = FactoryLocator::get('Table')->get('Surveys');
		/** @var \Awyiss\Model\Entity\Survey $entity */
		$entity = $table->get(1);
		/** @noinspection PhpRedundantOptionalArgumentInspection */
		$entity->initialize(new View(), ['8524de5e' => 4]);

		// Get a question and modify it to have ShowForm action but no target
		/** @var \Awyiss\Model\Entity\SurveySurveyQuestion $question */
		$question = $entity->getQuestions()->first();
		$question->nextAction = NextAction::ShowFormAndSave;
		$question->nextActionTarget = null; // Should fall back to survey's formId

		$result = $entity->getForm($question);

		$this->assertInstanceOf(Form::class, $result);
		$this->assertEquals(1, $result->id); // Should use survey's formId (1)
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Survey::getForm()
	 */
	public function testGetFormWithFinalActionShowForm(): void {
		/** @var \Awyiss\Model\Table\SurveysTable $table */
		$table = FactoryLocator::get('Table')->get('Surveys');
		/** @var \Awyiss\Model\Entity\Survey $entity */
		$entity = $table->get(1);
		/** @noinspection PhpRedundantOptionalArgumentInspection */
		$entity->initialize(new View(), ['8524de5e' => 4]);

		// Modify survey's final action to show form
		$entity->finalAction = NextAction::ShowForm;

		// Get a question and ensure it doesn't have form actions
		/** @var \Awyiss\Model\Entity\SurveySurveyQuestion $question */
		$question = $entity->getQuestions()->first();
		$question->nextAction = NextAction::NextQuestion;
		foreach ($question->surveySurveyAnswers as $answer) {
			$answer->nextAction = NextAction::NextQuestion;
		}

		$result = $entity->getForm($question);

		$this->assertInstanceOf(Form::class, $result);
		$this->assertEquals(1, $result->id); // Should use survey's formId (1)
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Survey::getForm()
	 */
	public function testGetFormWithFinalActionSaveAndShowForm(): void {
		/** @var \Awyiss\Model\Table\SurveysTable $table */
		$table = FactoryLocator::get('Table')->get('Surveys');
		/** @var \Awyiss\Model\Entity\Survey $entity */
		$entity = $table->get(1);
		/** @noinspection PhpRedundantOptionalArgumentInspection */
		$entity->initialize(new View(), ['8524de5e' => 4]);

		// Modify survey's final action to SaveAndShowForm
		$entity->finalAction = NextAction::SaveAndShowForm;

		// Get a question and ensure it doesn't have form actions
		/** @var \Awyiss\Model\Entity\SurveySurveyQuestion $question */
		$question = $entity->getQuestions()->first();
		$question->nextAction = NextAction::NextQuestion;

		$result = $entity->getForm($question);

		$this->assertInstanceOf(Form::class, $result);
		$this->assertEquals(1, $result->id);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Survey::getForm()
	 */
	public function testGetFormWithFinalActionShowFormAndSave(): void {
		/** @var \Awyiss\Model\Table\SurveysTable $table */
		$table = FactoryLocator::get('Table')->get('Surveys');
		/** @var \Awyiss\Model\Entity\Survey $entity */
		$entity = $table->get(1);
		/** @noinspection PhpRedundantOptionalArgumentInspection */
		$entity->initialize(new View(), ['8524de5e' => 4]);

		// Modify survey's final action to ShowFormAndSave
		$entity->finalAction = NextAction::ShowFormAndSave;

		// Get a question and ensure it doesn't have form actions
		/** @var \Awyiss\Model\Entity\SurveySurveyQuestion $question */
		$question = $entity->getQuestions()->first();
		$question->nextAction = NextAction::NextQuestion;

		$result = $entity->getForm($question);

		$this->assertInstanceOf(Form::class, $result);
		$this->assertEquals(1, $result->id);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Survey::getForm()
	 */
	public function testGetFormReturnsNullWhenNoFormActions(): void {
		/** @var \Awyiss\Model\Table\SurveysTable $table */
		$table = FactoryLocator::get('Table')->get('Surveys');
		/** @var \Awyiss\Model\Entity\Survey $entity */
		$entity = $table->get(1);
		/** @noinspection PhpRedundantOptionalArgumentInspection */
		$entity->initialize(new View(), ['8524de5e' => 4]);

		// Ensure no form actions anywhere
		$entity->finalAction = NextAction::SaveAndEnd;

		/** @var \Awyiss\Model\Entity\SurveySurveyQuestion $question */
		$question = $entity->getQuestions()->first();
		$question->nextAction = NextAction::NextQuestion;
		foreach ($question->surveySurveyAnswers as $answer) {
			$answer->nextAction = NextAction::NextQuestion;
		}

		$result = $entity->getForm($question);

		$this->assertNull($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Survey::getForm()
	 */
	public function testGetFormReturnsNullWhenQuestionIsNull(): void {
		/** @var \Awyiss\Model\Table\SurveysTable $table */
		$table = FactoryLocator::get('Table')->get('Surveys');
		/** @var \Awyiss\Model\Entity\Survey $entity */
		$entity = $table->get(1);
		/** @noinspection PhpRedundantOptionalArgumentInspection */
		$entity->initialize(new View(), []);

		// Call getForm with null question
		$result = $entity->getForm();

		$this->assertNull($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Survey::getForm()
	 */
	public function testGetFormUsesProgressDataWhenNoQuestionProvided(): void {
		/** @var \Awyiss\Model\Table\SurveysTable $table */
		$table = FactoryLocator::get('Table')->get('Surveys');
		/** @var \Awyiss\Model\Entity\Survey $entity */
		$entity = $table->get(1);
		/** @noinspection PhpRedundantOptionalArgumentInspection */
		$entity->initialize(new View(), ['8524de5e' => 4]);

		// Modify the question to have form action
		/** @var \Awyiss\Model\Entity\SurveySurveyQuestion $question */
		$question = $entity->getQuestions()->first();
		$question->nextAction = NextAction::ShowForm;
		$question->nextActionTarget = '2';

		$result = $entity->getForm(); // No question parameter

		$this->assertInstanceOf(Form::class, $result);
		$this->assertEquals(2, $result->id);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Survey::getForm()
	 */
	public function testGetFormUsesProgressDataForAnswerSelection(): void {
		/** @var \Awyiss\Model\Table\SurveysTable $table */
		$table = FactoryLocator::get('Table')->get('Surveys');
		/** @var \Awyiss\Model\Entity\Survey $entity */
		$entity = $table->get(1);
		/** @noinspection PhpRedundantOptionalArgumentInspection */
		$entity->initialize(new View(), ['8524de5e' => 4]);

		// Modify answer to have form action
		/** @var \Awyiss\Model\Entity\SurveySurveyQuestion $question */
		$question = $entity->getQuestions()->first();
		/** @var \Awyiss\Model\Entity\SurveySurveyAnswer $answer */
		$answer = $question->surveySurveyAnswers[4];
		$answer->nextAction = NextAction::ShowForm;
		$answer->nextActionTarget = '2';

		$result = $entity->getForm(); // No question or answer parameter

		$this->assertInstanceOf(Form::class, $result);
		$this->assertEquals(2, $result->id);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Survey::getForm()
	 */
	public function testGetFormInNormalModeSkipsInactiveForms(): void {
		/** @var \Awyiss\Model\Table\SurveysTable $table */
		$table = FactoryLocator::get('Table')->get('Surveys');
		/** @var \Awyiss\Model\Entity\Survey $entity */
		$entity = $table->get(1);
		/** @noinspection PhpRedundantOptionalArgumentInspection */
		$entity->initialize(new View(), ['8524de5e' => 4], null, false);

		// Get a question and modify its answer to target an inactive form
		/** @var \Awyiss\Model\Entity\SurveySurveyQuestion $question */
		$question = $entity->getQuestions()->first();
		/** @var \Awyiss\Model\Entity\SurveySurveyAnswer $answer */
		$answer = $question->surveySurveyAnswers[4];
		$answer->nextAction = NextAction::ShowForm;
		$answer->nextActionTarget = 3;

		$result = $entity->getForm($question, $answer);

		// In normal mode, should skip inactive forms
		$this->assertNull($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Survey::getForm()
	 */
	public function testGetFormInPreviewModeIncludesInactiveForms(): void {
		/** @var \Awyiss\Model\Table\SurveysTable $table */
		$table = FactoryLocator::get('Table')->get('Surveys');
		/** @var \Awyiss\Model\Entity\Survey $entity */
		$entity = $table->get(1);
		/** @noinspection PhpRedundantOptionalArgumentInspection */
		$entity->initialize(new View(), [], null, true); // Preview mode

		// Get a question and modify it to have ShowForm action
		/** @var \Awyiss\Model\Entity\SurveySurveyQuestion $question */
		$question = $entity->getQuestions()->first();
		$question->nextAction = NextAction::ShowForm;
		$question->nextActionTarget = 3;

		$result = $entity->getForm($question);

		// In preview mode, should find inactive forms too
		$this->assertInstanceOf(Form::class, $result);
		$this->assertEquals(3, $result->id);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Survey::getForm()
	 */
	public function testGetFormWithInvalidAnswerKey(): void {
		/** @var \Awyiss\Model\Table\SurveysTable $table */
		$table = FactoryLocator::get('Table')->get('Surveys');
		/** @var \Awyiss\Model\Entity\Survey $entity */
		$entity = $table->get(1);
		$entity->initialize(new View(), ['8524de5e' => 999]); // Invalid answer ID

		$this->assertNull($entity->getForm());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Survey::getForm()
	 */
	public function testGetFormAnswerTakesPrecedenceOverQuestion(): void {
		/** @var \Awyiss\Model\Table\SurveysTable $table */
		$table = FactoryLocator::get('Table')->get('Surveys');
		/** @var \Awyiss\Model\Entity\Survey $entity */
		$entity = $table->get(1);
		$entity->initialize(new View(), ['8524de5e' => 4]);

		// Modify both question and answer to have different form targets
		/** @var \Awyiss\Model\Entity\SurveySurveyQuestion $question */
		$question = $entity->getQuestions()->first();
		$question->nextAction = NextAction::ShowForm;
		$question->nextActionTarget = 1;

		/** @var \Awyiss\Model\Entity\SurveySurveyAnswer $answer */
		$answer = $question->surveySurveyAnswers[4];
		$answer->nextAction = NextAction::ShowForm;
		$answer->nextActionTarget = 2;

		$result = $entity->getForm(); // Should use answer's form (2), not question's (1)

		$this->assertInstanceOf(Form::class, $result);
		$this->assertEquals(2, $result->id); // Answer takes precedence
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\Survey::getForm()
	 */
	public function testGetFormQuestionTakesPrecedenceOverFinalAction(): void {
		/** @var \Awyiss\Model\Table\SurveysTable $table */
		$table = FactoryLocator::get('Table')->get('Surveys');
		/** @var \Awyiss\Model\Entity\Survey $entity */
		$entity = $table->get(1);
		/** @noinspection PhpRedundantOptionalArgumentInspection */
		$entity->initialize(new View(), ['8524de5e' => 4]);

		// Modify survey's final action and question action to target different forms
		$entity->finalAction = NextAction::ShowForm;
		$entity->formId = 1;

		/** @var \Awyiss\Model\Entity\SurveySurveyQuestion $question */
		$question = $entity->getQuestions()->first();
		$question->nextAction = NextAction::ShowForm;
		$question->nextActionTarget = 2;

		// Ensure answers don't have form actions
		foreach ($question->surveySurveyAnswers as $answer) {
			$answer->nextAction = NextAction::NextQuestion;
		}

		$result = $entity->getForm($question); // Should use question's form (2), not final action (1)

		$this->assertInstanceOf(Form::class, $result);
		$this->assertEquals(2, $result->id); // Question takes precedence over final action
	}
}
