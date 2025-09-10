<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Model\Entity;


use Awyiss\Model\Entity\SurveyQuestion;
use Awyiss\Model\Entity\SurveySurveyQuestion;
use Awyiss\Model\Enum\Survey\NextAction;
use Awyiss\Test\TestSuite\TestCase;
use Cake\Datasource\FactoryLocator;


/**
 * SurveySurveyQuestion Entity Test Case
 *
 * @see \Awyiss\Model\Entity\SurveySurveyQuestion
 */
class SurveySurveyQuestionTest extends TestCase {
	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\SurveySurveyQuestion::$fieldMap
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testFieldMapCompleteness(): void {
		/** @var \Awyiss\Model\Table\SurveySurveyQuestionsTable $table */
		$table = FactoryLocator::get('Table')->get('SurveySurveyQuestions');
		$entity = $table->newDefaultEntity();
		$entityArray = $entity->toArray();

		foreach ($entityArray as $key => $value) {
			$this->assertStringNotContainsString('_', $key);
		}
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\SurveySurveyQuestion::$_accessible
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAccessibleFields(): void {
		$entity = new SurveySurveyQuestion();

		$this->assertSame([
			'surveyId' => true,
			'surveyQuestionId' => true,
			'identifier' => true,
			'title' => true,
			'subtitle' => true,
			'text' => true,
			'nextAction' => true,
			'nextActionTarget' => true,
			'allowCustomAnswer' => true,
			'customAnswerTitle' => true,
			'systemOrder' => true,
			'active' => true,
			'_translations' => true,
			'_publicationData' => true,
			'mediaAssignments' => true,
			'mediaElementAssignments' => true,
		], $entity->getAccessible());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\SurveySurveyQuestion::$_virtual
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testVirtualFields(): void {
		$entity = new SurveySurveyQuestion();

		$this->assertSame(['label'], $entity->getVirtual());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\SurveySurveyQuestion
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testEntityConstruction(): void {
		$properties = [
			'id' => 1,
			'survey_id' => 123,
			'survey_question_id' => 456,
			'identifier' => 'q1',
			'title' => 'Survey Question',
			'subtitle' => 'Question Subtitle',
			'text' => 'Question text',
			'next_action' => NextAction::NextQuestion,
			'next_action_target' => 'q2',
			'allow_custom_answer' => true,
			'custom_answer_title' => 'Other',
			'system_order' => 1,
			'active' => true,
		];

		$entity = new SurveySurveyQuestion($properties);

		$this->assertEquals(1, $entity->id);
		$this->assertEquals(123, $entity->surveyId);
		$this->assertEquals(456, $entity->surveyQuestionId);
		$this->assertEquals('q1', $entity->identifier);
		$this->assertEquals('Survey Question', $entity->title);
		$this->assertEquals('Question Subtitle', $entity->subtitle);
		$this->assertEquals('Question text', $entity->text);
		$this->assertEquals(NextAction::NextQuestion, $entity->nextAction);
		$this->assertEquals('q2', $entity->nextActionTarget);
		$this->assertTrue($entity->allowCustomAnswer);
		$this->assertEquals('Other', $entity->customAnswerTitle);
		$this->assertEquals(1, $entity->systemOrder);
		$this->assertTrue($entity->active);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\SurveySurveyQuestion::$fieldMap
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testFieldMapDuringConstruction(): void {
		$properties = [
			'survey_id' => 789,
			'survey_question_id' => 101,
			'next_action' => NextAction::SaveAndEnd,
			'next_action_target' => 'target',
			'allow_custom_answer' => false,
			'custom_answer_title' => 'Custom',
			'system_order' => 2,
		];
		$entity = new SurveySurveyQuestion($properties);
		$entityArray = $entity->toArray();

		foreach ($entityArray as $key => $value) {
			$this->assertStringNotContainsString('_', $key);
		}
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\SurveySurveyQuestion::_getLabel()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testLabelVirtualPropertyWithTitle(): void {
		$entity = new SurveySurveyQuestion([
			'title' => 'Test Question',
			'identifier' => 'q1',
		]);

		$this->assertEquals('Test Question (q1)', $entity->label);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\SurveySurveyQuestion::_getLabel()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testLabelVirtualPropertyWithSurveyQuestion(): void {
		$surveyQuestion = new SurveyQuestion([
			'title' => 'Question From Relation',
			'active' => true,
		]);

		$entity = new SurveySurveyQuestion([
			'identifier' => 'q2',
			'surveyQuestion' => $surveyQuestion,
		]);

		$this->assertEquals('Question From Relation (q2)', $entity->label);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\SurveySurveyQuestion::_getLabel()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testLabelVirtualPropertyWithInactiveSurveyQuestion(): void {
		$surveyQuestion = new SurveyQuestion([
			'title' => 'Inactive Question',
			'active' => false,
		]);

		$entity = new SurveySurveyQuestion([
			'identifier' => 'q3',
			'surveyQuestion' => $surveyQuestion,
		]);

		$this->assertSame('survey_questions::inactive Inactive Question (q3)', $entity->label);
	}
}
