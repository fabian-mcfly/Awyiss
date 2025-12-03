<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Model\Entity;


use Awyiss\Model\Entity\SurveyAnswer;
use Awyiss\Model\Entity\SurveySurveyAnswer;
use Awyiss\Model\Enum\Survey\NextAction;
use Awyiss\Test\TestSuite\TestCase;
use Cake\Datasource\FactoryLocator;


/**
 * SurveySurveyAnswer Entity Test Case
 *
 * @see \Awyiss\Model\Entity\SurveySurveyAnswer
 */
class SurveySurveyAnswerTest extends TestCase {
	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\SurveySurveyAnswer::$fieldMap
	 */
	public function testFieldMapCompleteness(): void {
		/** @var \Awyiss\Model\Table\SurveySurveyAnswersTable $table */
		$table = FactoryLocator::get('Table')->get('SurveySurveyAnswers');
		$entity = $table->newDefaultEntity();
		$entityArray = $entity->toArray();

		foreach ($entityArray as $key => $value) {
			$this->assertStringNotContainsString('_', $key);
		}
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\SurveySurveyAnswer::$_accessible
	 */
	public function testAccessibleFields(): void {
		$entity = new SurveySurveyAnswer();

		$this->assertSame([
			'surveyAnswerId' => true,
			'surveySurveyQuestionId' => true,
			'title' => true,
			'subtitle' => true,
			'text' => true,
			'nextAction' => true,
			'nextActionTarget' => true,
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
	 * @see \Awyiss\Model\Entity\SurveySurveyAnswer::$_virtual
	 */
	public function testVirtualFields(): void {
		$entity = new SurveySurveyAnswer();

		$this->assertSame(['label'], $entity->getVirtual());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\SurveySurveyAnswer
	 */
	public function testEntityConstruction(): void {
		$properties = [
			'id' => 1,
			'survey_answer_id' => 123,
			'survey_survey_question_id' => 456,
			'title' => 'Test Answer',
			'subtitle' => 'Test Subtitle',
			'text' => 'Answer text',
			'next_action' => NextAction::NextQuestion,
			'next_action_target' => 'next_question',
			'system_order' => 1,
			'active' => true,
		];

		$entity = new SurveySurveyAnswer($properties);

		$this->assertEquals(1, $entity->id);
		$this->assertEquals(123, $entity->surveyAnswerId);
		$this->assertEquals(456, $entity->surveySurveyQuestionId);
		$this->assertEquals('Test Answer', $entity->title);
		$this->assertEquals('Test Subtitle', $entity->subtitle);
		$this->assertEquals('Answer text', $entity->text);
		$this->assertEquals(NextAction::NextQuestion, $entity->nextAction);
		$this->assertEquals('next_question', $entity->nextActionTarget);
		$this->assertEquals(1, $entity->systemOrder);
		$this->assertTrue($entity->active);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\SurveySurveyAnswer::$fieldMap
	 */
	public function testFieldMapDuringConstruction(): void {
		$properties = [
			'survey_answer_id' => 789,
			'survey_survey_question_id' => 101,
			'next_action' => NextAction::SpecificQuestion,
			'next_action_target' => 'target',
			'system_order' => 2,
		];
		$entity = new SurveySurveyAnswer($properties);
		$entityArray = $entity->toArray();

		foreach ($entityArray as $key => $value) {
			$this->assertStringNotContainsString('_', $key);
		}
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\SurveySurveyAnswer::_getLabel()
	 */
	public function testLabelVirtualPropertyWithTitle(): void {
		$entity = new SurveySurveyAnswer(['title' => 'Test Answer', 'active' => true]);

		$this->assertSame('Test Answer', $entity->label);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\SurveySurveyAnswer::_getLabel()
	 */
	public function testLabelVirtualPropertyWithTitleInactive(): void {
		$entity = new SurveySurveyAnswer(['title' => 'Test Answer', 'active' => false]);

		$this->assertSame('survey_answers::inactive Test Answer', $entity->label);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\SurveySurveyAnswer::_getLabel()
	 */
	public function testLabelVirtualPropertyWithSurveyAnswer(): void {
		$surveyAnswer = new SurveyAnswer([
			'title' => 'Answer From Relation',
			'active' => true,
		]);

		$entity = new SurveySurveyAnswer([
			'title' => null,
			'surveyAnswer' => $surveyAnswer,
		]);

		$this->assertSame('survey_answers::inactive Answer From Relation', $entity->label);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\SurveySurveyAnswer::_getLabel()
	 */
	public function testLabelVirtualPropertyWithInactiveSurveyAnswer(): void {
		$surveyAnswer = new SurveyAnswer([
			'title' => 'Inactive Answer',
			'active' => false,
		]);

		$entity = new SurveySurveyAnswer([
			'title' => null,
			'surveyAnswer' => $surveyAnswer,
			'active' => true,
		]);

		$this->assertSame('survey_answers::inactive Inactive Answer', $entity->label);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\SurveySurveyAnswer::_getLabel()
	 */
	public function testLabelVirtualPropertyWithInactiveEntity(): void {
		$entity = new SurveySurveyAnswer([
			'title' => 'Answer Title',
			'active' => false,
		]);

		$this->assertSame('survey_answers::inactive Answer Title', $entity->label);
	}
}
