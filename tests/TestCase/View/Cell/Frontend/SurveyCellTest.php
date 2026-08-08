<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\View\Cell\Frontend;


use Awyiss\Awyiss;
use Awyiss\Middleware\LocaleMiddleware;
use Awyiss\Test\TestSuite\TestCase;
use Awyiss\View\FrontendView;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\View\CellTrait;


/**
 * SurveyCellTest class
 */
class SurveyCellTest extends TestCase {
	use CellTrait;
	use IntegrationTestTrait;


	/**
	 * @var \Cake\Http\Response
	 */
	protected Response $response;
	/**
	 * @var \Cake\Http\ServerRequest
	 */
	protected ServerRequest $request;
	/**
	 * @var \Awyiss\View\FrontendView
	 */
	protected FrontendView $view;


	/**
	 * @inheritDoc
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	public function setUp(): void {
		parent::setUp();

		$this->configApplication(Awyiss::class, []);

		Awyiss::setRealm('Frontend');
		LocaleMiddleware::setRealm(Awyiss::REALM_FRONTEND);
		Awyiss::loadConfiguration('xy', 'yx');
		$this->loadRoutes();

		$this->request = new ServerRequest([
			'url' => '/dummy',
			'params' => [
				'lang' => 'xy',
				'controller' => 'TheController',
				'action' => 'theAction',
				'_name' => 'Frontend',
				'prefix' => 'Frontend',
				'parts' => [],
				'pass' => [],
				'plugin' => null,
			],
		]);

		$this->response = $this->createStub(Response::class);
		$this->view = new FrontendView($this->request);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Cell\Frontend\SurveyCell::display()
	 * @noinspection HtmlUnknownAnchorTarget
	 */
	public function testDisplay(): void {
		$page = $this->getTableLocator()->get('Pages')->get(1);

		$output = (string)$this->cell('Frontend/Survey', [
			'dummySurvey',
			$page,
			$this->view,
			[
				'fullWidth' => 1440.00,
				'singleColumnBreakpoint' => 768.00,
			],
		]);

		$this->assertStringContainsString('<div class="Survey" id="Survey-DummySurvey">', $output);
		$this->assertStringContainsString('<form method="post" action="#Survey-DummySurvey">', $output);
		$this->assertStringContainsString('<p class="Title SurveyQuestion-Title">Question #1</p>', $output);
		$this->assertStringContainsString('<input type="radio" name="survey[dummySurvey][8524de5e]" value="4" id="SurveyAnswer-Input4">', $output);
		$this->assertStringContainsString('<input type="radio" name="survey[dummySurvey][8524de5e]" value="5" id="SurveyAnswer-Input5">', $output);
		$this->assertStringContainsString('<input type="radio" name="survey[dummySurvey][8524de5e]" value="6" id="SurveyAnswer-Input6">', $output);
		$this->assertStringContainsString('<input type="hidden" name="_surveyIdentifier" value="dummySurvey">', $output);
		$this->assertStringContainsString('<button type="submit" name="survey[dummySurvey][action]" value="proceed" class="Button Survey-NextAction">', $output);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Cell\Frontend\SurveyCell::display()
	 * @noinspection HtmlUnknownAnchorTarget
	 */
	public function testDisplayWithPostDataShowsMatchingStep(): void {
		$page = $this->getTableLocator()->get('Pages')->get(1);

		$postData = [
			'survey' => [
				'dummySurvey' => [
					'action' => 'proceed',
					'8524de5e' => '5', // Answer to question 1
				],
			],
			'_surveyIdentifier' => 'dummySurvey',
		];

		$this->request = $this->request->withParsedBody($postData);

		$output = (string)$this->cell('Frontend/Survey', [
			'dummySurvey',
			$page,
			$this->view,
			[
				'fullWidth' => 1440.00,
				'singleColumnBreakpoint' => 768.00,
			],
		]);

		$this->assertStringContainsString('<div class="Survey" id="Survey-DummySurvey">', $output);
		$this->assertStringContainsString('<form method="post" action="#Survey-DummySurvey">', $output);
		$this->assertStringContainsString('<p class="Title SurveyQuestion-Title">Question #2</p>', $output);
		$this->assertStringContainsString('<input type="checkbox" name="survey[dummySurvey][f69b1648][]" value="7" id="SurveyAnswer-Input7">', $output);
		$this->assertStringContainsString('<input type="checkbox" name="survey[dummySurvey][f69b1648][]" value="8" id="SurveyAnswer-Input8">', $output);
		$this->assertStringContainsString('<input type="checkbox" name="survey[dummySurvey][f69b1648][]" value="9" id="SurveyAnswer-Input9">', $output);
		$this->assertStringContainsString('<input type="hidden" name="_surveyIdentifier" value="dummySurvey">', $output);
		$this->assertStringContainsString('<input type="hidden" name="survey[dummySurvey][lastAction]" value="8524de5e">', $output);
		$this->assertStringContainsString('<button type="submit" name="survey[dummySurvey][action]" value="goBack" class="Button Survey-GoBack" formnovalidate>Surveys::back</button>', $output);
		$this->assertStringContainsString('<button type="submit" name="survey[dummySurvey][action]" value="proceed" class="Button Survey-NextAction">', $output);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Cell\Frontend\SurveyCell::display()
	 * @noinspection HtmlUnknownAnchorTarget
	 */
	public function testDisplayWithBackPostDataShowsPreviousStep(): void {
		$page = $this->getTableLocator()->get('Pages')->get(1);

		$postData = [
			'survey' => [
				'dummySurvey' => [
					'action' => 'goBack',
					'lastAction' => '8524de5e',
					'8524de5e' => '5', // Answer to question 1
				],
			],
			'_surveyIdentifier' => 'dummySurvey',
		];

		$this->request = $this->request->withParsedBody($postData);

		$output = (string)$this->cell('Frontend/Survey', [
			'dummySurvey',
			$page,
			$this->view,
			[
				'fullWidth' => 1440.00,
				'singleColumnBreakpoint' => 768.00,
			],
		]);

		$this->assertStringContainsString('<div class="Survey" id="Survey-DummySurvey">', $output);
		$this->assertStringContainsString('<form method="post" action="#Survey-DummySurvey">', $output);
		$this->assertStringContainsString('<p class="Title SurveyQuestion-Title">Question #1</p>', $output);
		$this->assertStringContainsString('<input type="radio" name="survey[dummySurvey][8524de5e]" value="4" id="SurveyAnswer-Input4">', $output);
		$this->assertStringContainsString('<input type="radio" name="survey[dummySurvey][8524de5e]" value="5" id="SurveyAnswer-Input5">', $output);
		$this->assertStringContainsString('<input type="radio" name="survey[dummySurvey][8524de5e]" value="6" id="SurveyAnswer-Input6">', $output);
		$this->assertStringContainsString('<input type="hidden" name="_surveyIdentifier" value="dummySurvey">', $output);
		$this->assertStringContainsString('<button type="submit" name="survey[dummySurvey][action]" value="proceed" class="Button Survey-NextAction">', $output);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Cell\Frontend\SurveyCell::display()
	 * @noinspection HtmlUnknownTarget
	 * @noinspection HtmlRequiredAltAttribute
	 */
	public function testDisplayWithColumnWidth(): void {
		$surveyQuestionsTable = $this->getTableLocator()->get('SurveyQuestions');
		// Activate the third question
		$surveyQuestionsTable->updateAll(['active' => true], ['id' => 3]);

		$page = $this->getTableLocator()->get('Pages')->get(1);

		$postData = [
			'survey' => [
				'dummySurvey' => [
					'action' => 'proceed',
					'8524de5e' => '5', // Answer to question 1
					'f69b1648' => ['7', '8'], // Answers to question 2
				],
			],
			'_surveyIdentifier' => 'dummySurvey',
		];

		$this->request = $this->request->withParsedBody($postData);

		$output = (string)$this->cell('Frontend/Survey', [
			'dummySurvey',
			$page,
			$this->view,
			[
				'fullWidth' => 1440.00,
				'singleColumnBreakpoint' => 768.00,
				'columnWidth' => 40.00,
			],
		]);

		// Deactivate the third question
		$surveyQuestionsTable->updateAll(['active' => false], ['id' => 3]);

		$this->assertStringContainsString('<div class="Text SurveyQuestion-Text"><p>Info text with inline img tag<br><picture>', $output);
		$this->assertStringContainsString('<img data-src="_resized/dummypath/logo-awyiss-[w576].avif"', $output);
		$this->assertStringContainsString('<noscript><img src="_resized/dummypath/logo-awyiss-[w576].avif"', $output);
		$this->assertStringContainsString('</picture></p></div>', $output);

		// Activate the third question
		$surveyQuestionsTable->updateAll(['active' => true], ['id' => 3]);

		$output = (string)$this->cell('Frontend/Survey', [
			'dummySurvey',
			$page,
			$this->view,
			[
				'fullWidth' => 1440.00,
				'singleColumnBreakpoint' => 768.00,
				'columnWidth' => 100.00,
			],
		]);

		// Deactivate the third question
		$surveyQuestionsTable->updateAll(['active' => false], ['id' => 3]);

		$this->assertStringContainsString('<div class="Text SurveyQuestion-Text"><p>Info text with inline img tag<br><picture>', $output);
		$this->assertStringContainsString('<img data-src="_resized/dummypath/logo-awyiss-[w1440].avif"', $output);
		$this->assertStringContainsString('<noscript><img src="_resized/dummypath/logo-awyiss-[w1440].avif"', $output);
		$this->assertStringContainsString('</picture></p></div>', $output);
	}
}
