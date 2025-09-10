<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Survey;


use Awyiss\Model\Entity\Survey;
use Awyiss\Survey\AbstractSurveyResults;
use Awyiss\Test\TestSuite\TestCase;
use Awyiss\Utility\Media\MediaRenderOptions;
use Cake\View\View;


/**
 * Test case for AbstractSurveyResults
 *
 * @see \Awyiss\Survey\AbstractSurveyResults
 */
class AbstractSurveyResultsTest extends TestCase {
	/**
	 * @var \Awyiss\Model\Entity\Survey|\PHPUnit\Framework\MockObject\MockObject
	 * @noinspection PhpDocFieldTypeMismatchInspection
	 */
	protected Survey $survey;
	/**
	 * @var \Cake\View\View|\PHPUnit\Framework\MockObject\MockObject
	 * @noinspection PhpDocFieldTypeMismatchInspection
	 */
	protected View $view;
	/**
	 * @var \Awyiss\Survey\AbstractSurveyResults
	 */
	protected AbstractSurveyResults $surveyResults;


	/**
	 * @inheritDoc
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->survey = $this->createMock(Survey::class);
		$this->view = $this->createMock(View::class);

		// Create a concrete implementation for testing
		$this->surveyResults = new class ($this->survey, $this->view) extends AbstractSurveyResults {
			/**
			 * @inheritDoc
			 */
			public function getFinalResult(?string $successMessage, MediaRenderOptions $mediaRenderOptions): string {
				return $successMessage ?? 'Default final result';
			}


			/**
			 * @inheritDoc
			 */
			public function getStepResult(string $identifier, MediaRenderOptions $mediaRenderOptions): ?string {
				return sprintf('Step result for: %s', $identifier);
			}
		};
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testConstructorSetsProperties(): void {
		$progress = ['step1' => 'completed', 'step2' => 'pending'];
		$customAnswers = ['answer1' => 'value1', 'answer2' => 'value2'];

		$surveyResults = new class ($this->survey, $this->view, $progress, $customAnswers) extends AbstractSurveyResults {
			/**
			 * @inheritDoc
			 */
			public function getFinalResult(?string $successMessage, MediaRenderOptions $mediaRenderOptions): string {
				return 'test';
			}

			/**
			 * @inheritDoc
			 */
			public function getStepResult(string $identifier, MediaRenderOptions $mediaRenderOptions): ?string {
				return 'test';
			}


			/**
			 * @return \Awyiss\Model\Entity\Survey
			 */
			public function getSurvey(): Survey {
				return $this->survey;
			}


			/**
			 * @return \Cake\View\View
			 */
			public function getView(): View {
				return $this->view;
			}
		};

		$this->assertSame($this->survey, $surveyResults->getSurvey());
		$this->assertSame($this->view, $surveyResults->getView());
		$this->assertEquals($progress, $surveyResults->getProgress());
		$this->assertEquals($customAnswers, $surveyResults->getCustomAnswers());
	}


	/**
	 * @return void
	 */
	public function testConstructorWithDefaultValues(): void {
		$this->assertEquals([], $this->surveyResults->getProgress());
		$this->assertEquals([], $this->surveyResults->getCustomAnswers());
	}


	/**
	 * @return void
	 */
	public function testGetProgress(): void {
		$this->assertEquals([], $this->surveyResults->getProgress());
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testSetProgress(): void {
		$progress = [
			'question_1' => 'answered',
			'question_2' => 'skipped',
			'question_3' => 'pending',
		];

		$result = $this->surveyResults->setProgress($progress);

		$this->assertSame($this->surveyResults, $result);
		$this->assertEquals($progress, $this->surveyResults->getProgress());
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testSetProgressOverwritesPrevious(): void {
		$initialProgress = ['step1' => 'completed'];
		$newProgress = ['step2' => 'pending', 'step3' => 'started'];

		$this->surveyResults->setProgress($initialProgress);
		$this->assertEquals($initialProgress, $this->surveyResults->getProgress());

		$this->surveyResults->setProgress($newProgress);
		$this->assertEquals($newProgress, $this->surveyResults->getProgress());
	}


	/**
	 * @return void
	 */
	public function testSetProgressWithEmptyArray(): void {
		$this->surveyResults->setProgress(['initial' => 'data']);
		$this->surveyResults->setProgress([]);

		$this->assertEquals([], $this->surveyResults->getProgress());
	}


	/**
	 * @return void
	 */
	public function testGetCustomAnswers(): void {
		$this->assertEquals([], $this->surveyResults->getCustomAnswers());
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testSetCustomAnswers(): void {
		$customAnswers = [
			'user_name' => 'John Doe',
			'user_email' => 'john@example.com',
			'preferences' => ['option1', 'option2'],
		];

		$result = $this->surveyResults->setCustomAnswers($customAnswers);

		$this->assertSame($this->surveyResults, $result);
		$this->assertEquals($customAnswers, $this->surveyResults->getCustomAnswers());
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testSetCustomAnswersOverwritesPrevious(): void {
		$initialAnswers = ['answer1' => 'value1'];
		$newAnswers = ['answer2' => 'value2', 'answer3' => 'value3'];

		$this->surveyResults->setCustomAnswers($initialAnswers);
		$this->assertEquals($initialAnswers, $this->surveyResults->getCustomAnswers());

		$this->surveyResults->setCustomAnswers($newAnswers);
		$this->assertEquals($newAnswers, $this->surveyResults->getCustomAnswers());
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testSetCustomAnswersWithEmptyArray(): void {
		$this->surveyResults->setCustomAnswers(['initial' => 'data']);
		$this->surveyResults->setCustomAnswers([]);

		$this->assertEquals([], $this->surveyResults->getCustomAnswers());
	}


	/**
	 * @return void
	 * @see \Awyiss\Survey\AbstractSurveyResults::getFinalResult()
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetFinalResultWithMessage(): void {
		$successMessage = 'Survey completed successfully!';
		$mediaRenderOptions = $this->createMock(MediaRenderOptions::class);

		$result = $this->surveyResults->getFinalResult($successMessage, $mediaRenderOptions);

		$this->assertEquals($successMessage, $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Survey\AbstractSurveyResults::getFinalResult()
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetFinalResultWithNullMessage(): void {
		$mediaRenderOptions = $this->createMock(MediaRenderOptions::class);

		$result = $this->surveyResults->getFinalResult(null, $mediaRenderOptions);

		$this->assertEquals('Default final result', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Survey\AbstractSurveyResults::getStepResult()
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetStepResult(): void {
		$identifier = 'question_info_1';
		$mediaRenderOptions = $this->createMock(MediaRenderOptions::class);

		$result = $this->surveyResults->getStepResult($identifier, $mediaRenderOptions);

		$this->assertEquals(sprintf('Step result for: %s', $identifier), $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Survey\AbstractSurveyResults::getStepResult()
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetStepResultWithDifferentIdentifiers(): void {
		$mediaRenderOptions = $this->createMock(MediaRenderOptions::class);

		$identifiers = ['intro', 'question_1', 'conclusion', 'special-chars_123'];

		foreach ($identifiers as $identifier) {
			$result = $this->surveyResults->getStepResult($identifier, $mediaRenderOptions);
			$this->assertEquals(sprintf('Step result for: %s', $identifier), $result);
		}
	}
}
