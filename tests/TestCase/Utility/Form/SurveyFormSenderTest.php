<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Utility\Form;


use Awyiss\Awyiss;
use Awyiss\Routing\Router;
use Awyiss\Test\TestSuite\TestCase;
use Awyiss\Utility\Form\SurveyFormSender;
use Awyiss\Utility\Survey\SurveyRenderer;
use Awyiss\View\FrontendView;
use Cake\Http\ServerRequest;


/**
 * Test case for SurveyFormSender
 *
 * @see \Awyiss\Utility\Form\SurveyFormSender
 */
class SurveyFormSenderTest extends TestCase {
	/**
	 * Setup test dependencies
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		$request = new ServerRequest([
			'url' => '/',
			'params' => [
				'lang' => 'de',
				'slug' => 'dummy',
				'_name' => Awyiss::REALM_FRONTEND,
				'prefix' => Awyiss::REALM_FRONTEND,
				'parts' => [],
				'pass' => [],
				'plugin' => null,
			],
		]);
		Router::setRequest($request);
	}


	/**
	 * Test createDataString in html format
	 * using `createBody` of the FormSender
	 *
	 * @return void
	 * @see \Awyiss\Utility\Form\SurveyFormSender::setSurveyProgress()
	 * @see \Awyiss\Utility\Form\SurveyFormSender::createDataString()
	 * @see \Awyiss\Utility\Form\FormSender::createBody()
	 * @throws \Exception
	 * @throws \ReflectionException
	 */
	public function testCreateDataStringHtml(): void {
		$renderer = new SurveyRenderer(new FrontendView());

		// Prepare request data to submit the form with valid data
		$renderer->initSurvey('dummy_survey', [
			'_survey_identifier' => 'dummy_survey',
			'survey' => [
				'dummy_survey' => [
					'8524de5e' => 4,
					'f69b1648' => [8, 9],
					'7d654446' => 'custom',
					'72054f17' => 11,
					'custom' => [
						'7d654446' => 'Custom Answer',
					],
				],
			],
		]);

		/** @var \Awyiss\Model\Entity\Survey $survey */
		$survey = $renderer->getSurvey();

		/** @var \Awyiss\Model\Entity\Form $form */
		$form = $this->fetchTable('Forms')->get(1);

		$form->setFormData([
			'email' => 'example@domain.com',
			'vorname' => 'Max',
			'nachname' => 'Mustermann',
			'nachricht' => 'This is a test message.',
		]);

		$sender = new SurveyFormSender($form);
		$sender->setSurveyProgress($survey, $survey->getProgress(), $survey->getCustomAnswers());

		$body = $this->callProtectedMethod($sender, 'createBody', $form->emailTemplate, 'html');

		$this->assertStringContainsString('<tr class="DataRowType-Survey SurveyIdentifier-DummySurvey">', $body);
		$this->assertStringContainsString('<td colspan="2"><strong>Dummy Survey</strong></td>', $body);

		$this->assertStringContainsString('<em>Question #1</em>', $body);
		$this->assertStringContainsString('&ndash; Answer #1.1', $body);

		$this->assertStringContainsString('<em>Question #2</em>', $body);
		$this->assertStringContainsString('<li>Answer #2.2</li>', $body);
		$this->assertStringContainsString('<li>Answer #2.3</li>', $body);

		$this->assertStringContainsString('<em>Question #4</em>', $body);
		$this->assertStringContainsString('&ndash; Custom Answer', $body);

		$this->assertStringContainsString('<em>Question #5</em>', $body);
		$this->assertStringContainsString('&ndash; Answer #5.2', $body);

		$this->assertStringContainsString('<tr class="DataRowType-SurveySpacer">', $body);

		$this->assertStringContainsString('<tr class="DataRowType-Text DataRowIdentifier-Vorname">', $body);
		$this->assertStringContainsString('Max', $body);

		$this->assertStringContainsString('<tr class="DataRowType-Text DataRowIdentifier-Nachname">', $body);
		$this->assertStringContainsString('Mustermann', $body);
	}


	/**
	 * Test createDataString in plaintext format
	 * using `createBody` of the FormSender
	 *
	 * @return void
	 * @see \Awyiss\Utility\Form\SurveyFormSender::setSurveyProgress()
	 * @see \Awyiss\Utility\Form\SurveyFormSender::createDataString()
	 * @see \Awyiss\Utility\Form\FormSender::createBody()
	 * @throws \Exception
	 * @throws \ReflectionException
	 */
	public function testCreateDataStringPlaintext(): void {
		$renderer = new SurveyRenderer(new FrontendView());

		// Prepare request data to submit the form with valid data
		$renderer->initSurvey('dummy_survey', [
			'_survey_identifier' => 'dummy_survey',
			'survey' => [
				'dummy_survey' => [
					'8524de5e' => 4,
					'f69b1648' => [8, 9],
					'7d654446' => 'custom',
					'72054f17' => 11,
					'custom' => [
						'7d654446' => 'Custom Answer',
					],
				],
			],
		]);

		/** @var \Awyiss\Model\Entity\Survey $survey */
		$survey = $renderer->getSurvey();

		/** @var \Awyiss\Model\Entity\Form $form */
		$form = $this->fetchTable('Forms')->get(1);

		$form->setFormData([
			'email' => 'example@domain.com',
			'vorname' => 'Max',
			'nachname' => 'Mustermann',
			'nachricht' => 'This is a test message.',
		]);

		$sender = new SurveyFormSender($form);
		$sender->setSurveyProgress($survey, $survey->getProgress(), $survey->getCustomAnswers());

		$body = $this->callProtectedMethod($sender, 'createBody', $form->emailTemplate, 'text');

		$this->assertStringContainsString('Dummy Survey' . PHP_EOL . '-------------', $body);

		$this->assertStringContainsString('Question #1' . PHP_EOL . '– Answer #1.1', $body);

		$this->assertStringContainsString('Question #2' . PHP_EOL . '– Answer #2.2' . PHP_EOL . '– Answer #2.3', $body);

		$this->assertStringContainsString('Question #4' . PHP_EOL . '– Custom Answer', $body);

		$this->assertStringContainsString('Question #5' . PHP_EOL . '– Answer #5.2', $body);

		$this->assertStringNotContainsString('<table', $body);
		$this->assertStringNotContainsString('</table>', $body);

		$this->assertStringContainsString('-----------------' . PHP_EOL . 'Persönliche Daten', $body);
	}


	/**
	 * Test templateData
	 *
	 * @return void
	 * @see \Awyiss\Utility\Form\SurveyFormSender::setSurveyProgress()
	 * @see \Awyiss\Utility\Form\SurveyFormSender::templateData()
	 * @throws \Exception
	 * @throws \ReflectionException
	 */
	public function testTemplateDataSetsSurveyData(): void {
		/** @var \Awyiss\Model\Entity\Survey $survey */
		$survey = $this->fetchTable('Surveys')->get(1);

		$progress = ['question1' => 'answer1', 'question2' => 'answer2'];
		$customAnswers = ['custom1' => 'value1', 'custom2' => 'value2'];

		/** @var \Awyiss\Model\Entity\Form $form */
		$form = $this->fetchTable('Forms')->get(1);

		$formSender = new SurveyFormSender($form);
		$formSender->setSurveyProgress($survey, $progress, $customAnswers);

		$templateData = $this->callProtectedMethod($formSender, 'templateData');

		$this->assertArrayHasKey('survey', $templateData);
		$this->assertSame($survey, $templateData['survey']);

		$this->assertArrayHasKey('surveyProgress', $templateData);
		$this->assertSame($progress, $templateData['surveyProgress']);

		$this->assertArrayHasKey('surveyCustomAnswers', $templateData);
		$this->assertSame($customAnswers, $templateData['surveyCustomAnswers']);

		$this->assertArrayHasKey('questionTypeEnum', $templateData);
		$this->assertSame('\Awyiss\Model\Enum\Survey\QuestionType', $templateData['questionTypeEnum']);

		$this->assertArrayHasKey('nextActionEnum', $templateData);
		$this->assertSame('\Awyiss\Model\Enum\Survey\NextAction', $templateData['nextActionEnum']);
	}
}
