<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Model\Entity;


use Awyiss\Model\Entity\SurveyQuestion;
use Awyiss\Model\Enum\Survey\QuestionType;
use Awyiss\Test\TestSuite\TestCase;
use Cake\Datasource\FactoryLocator;


/**
 * SurveyQuestion Entity Test Case
 *
 * @see \Awyiss\Model\Entity\SurveyQuestion
 */
class SurveyQuestionTest extends TestCase {
	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\SurveyQuestion::$fieldMap
	 */
	public function testFieldMapCompleteness(): void {
		/** @var \Awyiss\Model\Table\SurveyQuestionsTable $table */
		$table = FactoryLocator::get('Table')->get('SurveyQuestions');
		$entity = $table->newDefaultEntity();
		$entityArray = $entity->toArray();

		foreach ($entityArray as $key => $value) {
			$this->assertStringNotContainsString('_', $key);
		}
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\SurveyQuestion::$_accessible
	 */
	public function testAccessibleFields(): void {
		$entity = new SurveyQuestion();

		$this->assertSame([
			'type' => true,
			'title' => true,
			'subtitle' => true,
			'text' => true,
			'active' => true,
			'_translations' => true,
			'_publicationData' => true,
			'mediaAssignments' => true,
			'mediaElementAssignments' => true,
		], $entity->getAccessible());
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\SurveyQuestion
	 */
	public function testEntityConstruction(): void {
		$properties = [
			'id' => 1,
			'type' => QuestionType::SingleChoice,
			'title' => 'Test Question',
			'subtitle' => 'Test Subtitle',
			'text' => 'Question text',
			'active' => true,
			'survey_answers' => [],
			'survey_survey_questions' => [],
		];

		$entity = new SurveyQuestion($properties);

		$this->assertEquals(1, $entity->id);
		$this->assertEquals(QuestionType::SingleChoice, $entity->type);
		$this->assertEquals('Test Question', $entity->title);
		$this->assertEquals('Test Subtitle', $entity->subtitle);
		$this->assertEquals('Question text', $entity->text);
		$this->assertTrue($entity->active);
		$this->assertEquals([], $entity->surveyAnswers);
		$this->assertEquals([], $entity->surveySurveyQuestions);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\SurveyQuestion::$defaultValues
	 */
	public function testDefaultValues(): void {
		/** @var \Awyiss\Model\Table\SurveyQuestionsTable $table */
		$table = FactoryLocator::get('Table')->get('SurveyQuestions');
		$entity = $table->newDefaultEntity();

		$this->assertEquals(QuestionType::SingleChoice, $entity->type);
	}


	/**
	 * @return void
	 * @see \Awyiss\Model\Entity\SurveyQuestion::$fieldMap
	 */
	public function testFieldMapDuringConstruction(): void {
		$properties = [
			'survey_answers' => [],
			'survey_survey_questions' => [],
		];
		$entity = new SurveyQuestion($properties);
		$entityArray = $entity->toArray();

		foreach ($entityArray as $key => $value) {
			$this->assertStringNotContainsString('_', $key);
		}
	}
}
