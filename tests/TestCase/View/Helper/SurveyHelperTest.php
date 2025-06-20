<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\View\Helper;


use Awyiss\Model\Entity\SurveyQuestion;
use Awyiss\Model\Entity\SurveySurveyQuestion;
use Awyiss\Test\TestSuite\TestCase;
use Awyiss\View\Helper\SurveyHelper;
use Cake\View\View;


/**
 * SurveyHelperTest class
 */
class SurveyHelperTest extends TestCase {
	/**
	 * @var \Awyiss\View\Helper\SurveyHelper
	 */
	protected SurveyHelper $helper;

	/**
	 * @inheritDoc
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	protected function setUp(): void {
		parent::setUp();

		$view = $this->getMockBuilder(View::class)
			->disableOriginalConstructor()
			->getMock();

		$this->helper = new SurveyHelper($view);
	}

	/**
	 * Test realNextQuestion method when there's a next question with the same active status
	 * or when there's an active question after the current one
	 *
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testRealNextQuestionWithSameActiveStatus(): void {
		// Create survey survey question entities
		$question1 = new SurveySurveyQuestion([
			'identifier' => 'q1',
			'surveyQuestion' => new SurveyQuestion(['active' => true]),
		]);
		$question2 = new SurveySurveyQuestion([
			'identifier' => 'q2',
			'surveyQuestion' => new SurveyQuestion(['active' => true]),
		]);
		$question3 = new SurveySurveyQuestion([
			'identifier' => 'q3',
			'surveyQuestion' => new SurveyQuestion(['active' => false]),
		]);
		$question4 = new SurveySurveyQuestion([
			'identifier' => 'q4',
			'surveyQuestion' => new SurveyQuestion(['active' => false]),
		]);
		$question5 = new SurveySurveyQuestion([
			'identifier' => 'q5',
			'surveyQuestion' => new SurveyQuestion(['active' => true]),
		]);

		$questions = [$question1, $question2, $question3, $question4, $question5];

		// Test finding the next question after q1 (should be q2 since both are active)
		$result = $this->helper->realNextQuestion($questions, 'q1');
		$this->assertSame($question2, $result);

		// Test finding the next question after q2 (should be q5 because q5 is active)
		$result = $this->helper->realNextQuestion($questions, 'q2');
		$this->assertSame($question5, $result);

		// Test finding the next question after q3 (should be q4 since both are inactive)
		$result = $this->helper->realNextQuestion($questions, 'q3');
		$this->assertSame($question4, $result);
	}

	/**
	 * Test realNextQuestion method when there's a next question with the same active status
	 * or when there's an active question after the current one
	 *
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testRealNextQuestionWithActiveStatus(): void {
		// Create survey survey question entities
		$question1 = new SurveySurveyQuestion([
			'identifier' => 'q1',
			'surveyQuestion' => new SurveyQuestion(['active' => false]),
		]);
		$question2 = new SurveySurveyQuestion([
			'identifier' => 'q2',
			'surveyQuestion' => new SurveyQuestion(['active' => false]),
		]);
		$question3 = new SurveySurveyQuestion([
			'identifier' => 'q3',
			'surveyQuestion' => new SurveyQuestion(['active' => false]),
		]);
		$question4 = new SurveySurveyQuestion([
			'identifier' => 'q4',
			'surveyQuestion' => new SurveyQuestion(['active' => true]),
		]);
		$question5 = new SurveySurveyQuestion([
			'identifier' => 'q5',
			'surveyQuestion' => new SurveyQuestion(['active' => false]),
		]);

		$questions = [$question1, $question2, $question3, $question4, $question5];

		// Test finding the next question after q1 (should be q2 because both are inactive)
		$result = $this->helper->realNextQuestion($questions, 'q1');
		$this->assertSame($question2, $result);

		// Test finding the next question after q2 (should be q3 because both are inactive)
		$result = $this->helper->realNextQuestion($questions, 'q2');
		$this->assertSame($question3, $result);

		// Test finding the next question after q3 (should be q4 because q4 is active)
		$result = $this->helper->realNextQuestion($questions, 'q3');
		$this->assertSame($question4, $result);

		// No next question after q4 since it's the last active question
		$result = $this->helper->realNextQuestion($questions, 'q4');
		$this->assertFalse($result);
	}

	/**
	 * Test realNextQuestion method when there's no next question
	 *
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testRealNextQuestionWithNoNextQuestion(): void {
		// Create survey survey question entities
		$question1 = new SurveySurveyQuestion([
			'identifier' => 'q1',
			'surveyQuestion' => new SurveyQuestion(['active' => true]),
		]);
		$question2 = new SurveySurveyQuestion([
			'identifier' => 'q2',
			'surveyQuestion' => new SurveyQuestion(['active' => false]),
		]);
		$question3 = new SurveySurveyQuestion([
			'identifier' => 'q3',
			'surveyQuestion' => new SurveyQuestion(['active' => true]),
		]);
		$question4 = new SurveySurveyQuestion([
			'identifier' => 'q4',
			'surveyQuestion' => new SurveyQuestion(['active' => false]),
		]);
		$question5 = new SurveySurveyQuestion([
			'identifier' => 'q5',
			'surveyQuestion' => new SurveyQuestion(['active' => true]),
		]);

		$questions = [$question1, $question2, $question3, $question4, $question5];

		// Test finding the next question after q5 (should be false since it's the last question)
		$result = $this->helper->realNextQuestion($questions, 'q5');
		$this->assertFalse($result);

		// Test finding the next question after q1 (should be q3 because q3 is active)
		$result = $this->helper->realNextQuestion($questions, 'q1');
		$this->assertSame($question3, $result);

		// Test finding the next question after q2 (should be q3 because q3 is active)
		$result = $this->helper->realNextQuestion($questions, 'q2');
		$this->assertSame($question3, $result);
	}

	/**
	 * Test realNextQuestion method with an empty array
	 *
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testRealNextQuestionWithEmptyArray(): void {
		$result = $this->helper->realNextQuestion([], 'q1');
		$this->assertFalse($result);
	}

	/**
	 * Test realNextQuestion method with an invalid identifier
	 *
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testRealNextQuestionWithInvalidIdentifier(): void {
		// Create survey survey question entities
		$question1 = new SurveySurveyQuestion([
			'identifier' => 'q1',
			'surveyQuestion' => new SurveyQuestion(['active' => true]),
		]);
		$question2 = new SurveySurveyQuestion([
			'identifier' => 'q2',
			'surveyQuestion' => new SurveyQuestion(['active' => false]),
		]);
		$question3 = new SurveySurveyQuestion([
			'identifier' => 'q3',
			'surveyQuestion' => new SurveyQuestion(['active' => true]),
		]);
		$question4 = new SurveySurveyQuestion([
			'identifier' => 'q4',
			'surveyQuestion' => new SurveyQuestion(['active' => false]),
		]);
		$question5 = new SurveySurveyQuestion([
			'identifier' => 'q5',
			'surveyQuestion' => new SurveyQuestion(['active' => true]),
		]);

		$questions = [$question1, $question2, $question3, $question4, $question5];

		// Test finding the next question after an invalid identifier (should be false)
		$result = $this->helper->realNextQuestion($questions, 'invalid');
		$this->assertFalse($result);

		// Verify that valid identifiers still work correctly
		$result = $this->helper->realNextQuestion($questions, 'q1');
		$this->assertSame($question3, $result); // Skips q2 because q3 is active (condition #1)

		$result = $this->helper->realNextQuestion($questions, 'q2');
		$this->assertSame($question3, $result); // q3 is active, which satisfies condition #1
	}

	/**
	 * Test realNextQuestion method with a complex pattern of active statuses
	 * demonstrating that the next question can be several questions ahead
	 * based on either being active or having the same active status as the current question
	 *
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testRealNextQuestionWithMultipleQuestionsWithSameActiveStatus(): void {
		// Create survey survey question entities
		$question1 = new SurveySurveyQuestion([
			'identifier' => 'q1',
			'surveyQuestion' => new SurveyQuestion(['active' => true]),
		]);
		$question2 = new SurveySurveyQuestion([
			'identifier' => 'q2',
			'surveyQuestion' => new SurveyQuestion(['active' => false]),
		]);
		$question3 = new SurveySurveyQuestion([
			'identifier' => 'q3',
			'surveyQuestion' => new SurveyQuestion(['active' => false]),
		]);
		$question4 = new SurveySurveyQuestion([
			'identifier' => 'q4',
			'surveyQuestion' => new SurveyQuestion(['active' => false]),
		]);
		$question5 = new SurveySurveyQuestion([
			'identifier' => 'q5',
			'surveyQuestion' => new SurveyQuestion(['active' => true]),
		]);
		$question6 = new SurveySurveyQuestion([
			'identifier' => 'q6',
			'surveyQuestion' => new SurveyQuestion(['active' => false]),
		]);
		$question7 = new SurveySurveyQuestion([
			'identifier' => 'q7',
			'surveyQuestion' => new SurveyQuestion(['active' => true]),
		]);

		$questions = [$question1, $question2, $question3, $question4, $question5, $question6, $question7];

		// Test finding the next question after q1 (should be q5 because q5 is active - skipping 3 inactive questions)
		$result = $this->helper->realNextQuestion($questions, 'q1');
		$this->assertSame($question5, $result);

		// Test finding the next question after q2 (should be q3 - both are inactive)
		$result = $this->helper->realNextQuestion($questions, 'q2');
		$this->assertSame($question3, $result);

		// Test finding the next question after q5 (should be q7 because q7 is active - skipping 1 inactive question)
		$result = $this->helper->realNextQuestion($questions, 'q5');
		$this->assertSame($question7, $result);

		// Test finding the next question after q6 (should be q7 because q7)
		$result = $this->helper->realNextQuestion($questions, 'q6');
		$this->assertSame($question7, $result);
	}
}
