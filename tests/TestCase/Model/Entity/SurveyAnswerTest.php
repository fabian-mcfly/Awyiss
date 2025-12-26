<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Model\Entity;


use Awyiss\Model\Entity\SurveyAnswer;
use Awyiss\Test\TestSuite\TestCase;
use Cake\Datasource\FactoryLocator;


/**
 * SurveyAnswer Entity Test Case
 *
 * @see \Awyiss\Model\Entity\SurveyAnswer
 */
class SurveyAnswerTest extends TestCase {
	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\SurveyAnswer::$fieldMap
	 */
	public function testFieldMapCompleteness(): void {
		/** @var \Awyiss\Model\Table\SurveyAnswersTable $table */
		$table = FactoryLocator::get('Table')->get('SurveyAnswers');
		$entity = $table->newDefaultEntity();
		$entityArray = $entity->toArray();

		foreach ($entityArray as $key => $value) {
			$this->assertStringNotContainsString('_', $key);
		}
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\SurveyAnswer::$_accessible
	 */
	public function testAccessibleFields(): void {
		$entity = new SurveyAnswer();

		$this->assertSame([
			'surveyQuestionId' => true,
			'title' => true,
			'subtitle' => true,
			'text' => true,
			'systemOrder' => true,
			'active' => true,
			'_translations' => true,
			'_publicationData' => true,
			'customerGroupAccessSettings' => true,
			'customerGroupAssignments' => true,
			'mediaAssignments' => true,
			'mediaElementAssignments' => true,
		], $entity->getAccessible());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\SurveyAnswer
	 */
	public function testEntityConstruction(): void {
		$properties = [
			'id' => 1,
			'survey_question_id' => 123,
			'title' => 'Answer Title',
			'subtitle' => 'Answer Subtitle',
			'text' => 'Answer text',
			'system_order' => 1,
			'active' => true,
		];

		$entity = new SurveyAnswer($properties);

		$this->assertEquals(1, $entity->id);
		$this->assertEquals(123, $entity->surveyQuestionId);
		$this->assertEquals('Answer Title', $entity->title);
		$this->assertEquals('Answer Subtitle', $entity->subtitle);
		$this->assertEquals('Answer text', $entity->text);
		$this->assertEquals(1, $entity->systemOrder);
		$this->assertTrue($entity->active);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\SurveyAnswer::$fieldMap
	 */
	public function testFieldMapDuringConstruction(): void {
		$properties = [
			'survey_question_id' => 456,
			'system_order' => 2,
			'survey_question' => null,
			'survey_survey_answers' => [],
		];
		$entity = new SurveyAnswer($properties);
		$entityArray = $entity->toArray();

		foreach ($entityArray as $key => $value) {
			$this->assertStringNotContainsString('_', $key);
		}
	}
}
