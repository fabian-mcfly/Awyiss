<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Utility\Survey;


use Awyiss\Awyiss;
use Awyiss\Core\App;
use Awyiss\Model\Entity\Survey;
use Awyiss\Test\TestSuite\TestCase;
use Awyiss\Utility\Survey\SurveyRenderer;
use Awyiss\View\FrontendView;
use BackedEnum;
use Cake\Http\Exception\RedirectException;
use Cake\Http\ServerRequest;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\TestSuite\IntegrationTestTrait;
use Customer\Utility\Form\FormRenderer as CustomFormRenderer;
use PHPUnit\Framework\Attributes\TestWith;
use RuntimeException;


/**
 * SurveyRendererTest class
 *
 * @see \Awyiss\Utility\Survey\SurveyRenderer
 */
class SurveyRendererTest extends TestCase {
	use IntegrationTestTrait;
	use LocatorAwareTrait;


	/**
	 * @var \Awyiss\Utility\Survey\SurveyRenderer
	 */
	protected SurveyRenderer $surveyRenderer;
	/**
	 * @var \Awyiss\Model\Entity\Survey
	 */
	protected Survey $survey;
	/**
	 * @var \Awyiss\View\FrontendView
	 */
	protected FrontendView $view;


	/**
	 * @inheritDoc
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	protected function setUp(): void {
		$this->configApplication(Awyiss::class, []);

		parent::setUp();

		$this->loadRoutes();

		Awyiss::setRealm('Frontend');

		$request = new ServerRequest([
			'url' => '/',
			'params' => [
				'lang' => 'de',
				'slug' => 'dummy',
				'_name' => 'Frontend',
				'prefix' => 'Frontend',
				'parts' => [],
				'pass' => [],
				'plugin' => null,
			],
		]);

		$this->view = new FrontendView($request);

		$table = $this->getTableLocator()->get('Surveys');
		/** @noinspection PhpFieldAssignmentTypeMismatchInspection */
		$this->survey = $table->get(1);
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	protected function tearDown(): void {
		parent::tearDown();

		$table = $this->getTableLocator()->get('Surveys');
		// Mark the survey as dirty to ensure it gets saved
		/** @noinspection PhpRedundantOptionalArgumentInspection */
		$this->survey->setDirty('title', true);
		// Restore the original state of the survey
		$table->save($this->survey);

		$table = $this->getTableLocator()->get('FormEntries');
		$table->deleteAll(['id >' => 3]);

		$table = $this->getTableLocator()->get('SurveyEntries');
		$table->deleteAll(['id >' => 1]);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Survey\SurveyRenderer::getSurveyByIdentifier()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetSurveyByIdentifierWithIdReturnsSurvey(): void {
		$renderer = new SurveyRenderer($this->view);
		$result = $renderer->getSurveyByIdentifier(1);

		$this->assertInstanceOf(Survey::class, $result);
		$this->assertEquals('dummy_survey', $result->identifier);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Survey\SurveyRenderer::getSurveyByIdentifier()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetSurveyByIdentifierWithStringIdentifierReturnsSurvey(): void {
		$renderer = new SurveyRenderer($this->view);
		$result = $renderer->getSurveyByIdentifier('dummy_survey');

		$this->assertInstanceOf(Survey::class, $result);
		$this->assertEquals('dummy_survey', $result->identifier);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Survey\SurveyRenderer::getSurveyByIdentifier()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetSurveyByIdentifierReturnsNullIfInactive(): void {
		$renderer = new SurveyRenderer($this->view);
		$result = $renderer->getSurveyByIdentifier('dummy_survey2');

		$this->assertNull($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Survey\SurveyRenderer::getSurveyByIdentifier()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetSurveyByIdentifierReturnsSurveyInPreviewModeIfInactive(): void {
		// Mock the preview mode inside the SurveyRenderer
		$renderer = $this->getMockBuilder(SurveyRenderer::class)->onlyMethods(['isPreview'])
			->setConstructorArgs([$this->view])
			->getMock();
		$renderer->method('isPreview')->willReturn(true);

		$result = $renderer->getSurveyByIdentifier('dummy_survey2');

		$this->assertInstanceOf(Survey::class, $result);
		$this->assertEquals('dummy_survey2', $result->identifier);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Survey\SurveyRenderer::getSurveyByIdentifier()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testGetSurveyByIdentifierReturnsNullIfNotFound(): void {
		$renderer = new SurveyRenderer($this->view);
		$result = $renderer->getSurveyByIdentifier('unknown_survey');

		$this->assertNull($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Survey\SurveyRenderer::initSurvey()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testInitSurveyInitializesSurvey(): void {
		$renderer = new SurveyRenderer($this->view);

		$result = $renderer->initSurvey(1, []);

		$this->assertSame($renderer, $result);
		$this->assertInstanceOf(Survey::class, $renderer->getSurvey());
		$this->assertEquals('dummy_survey', $renderer->getSurvey()->identifier);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Survey\SurveyRenderer::initSurvey()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testInitSurveyInitializesSurveyWithValidIdentifier(): void {
		$renderer = new SurveyRenderer($this->view);

		$result = $renderer->initSurvey('dummy_survey', []);

		$this->assertSame($renderer, $result);
		$this->assertInstanceOf(Survey::class, $renderer->getSurvey());
		$this->assertEquals('dummy_survey', $renderer->getSurvey()->identifier);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Survey\SurveyRenderer::initSurvey()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testInitSurveyWithInvalidIdentifier(): void {
		$renderer = new SurveyRenderer($this->view);

		$result = $renderer->initSurvey('nonexistent_survey', []);

		$this->assertSame($renderer, $result);
		$this->assertNull($renderer->getSurvey());
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Survey\SurveyRenderer::initSurvey()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testInitSurveyWithInactiveSurveyReturnsNull(): void {
		$renderer = new SurveyRenderer($this->view);
		$result = $renderer->initSurvey('dummy_survey2', []);
		$this->assertSame($renderer, $result);
		$this->assertNull($renderer->getSurvey());
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Survey\SurveyRenderer::initSurvey()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testInitSurveyWithInactiveSurveyInPreviewMode(): void {
		$renderer = $this->getMockBuilder(SurveyRenderer::class)->onlyMethods(['isPreview'])->setConstructorArgs([$this->view])->getMock();
		$renderer->method('isPreview')->willReturn(true);

		$result = $renderer->initSurvey('dummy_survey2', []);
		$this->assertSame($renderer, $result);
		$this->assertInstanceOf(Survey::class, $renderer->getSurvey());
		$this->assertEquals('dummy_survey2', $renderer->getSurvey()->identifier);
	}


	/**
	 * @param bool $previewMode
	 * @param int $expectedQuestions
	 * @return void
	 * @see \Awyiss\Utility\Survey\SurveyRenderer::initSurvey()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	#[TestWith([false, 4])]
	#[TestWith([true, 6])]
	public function testInitSurveyPassesPreviewMode(bool $previewMode, int $expectedQuestions): void {
		$renderer = $this->getMockBuilder(SurveyRenderer::class)->onlyMethods(['isPreview'])->setConstructorArgs([$this->view])->getMock();
		$renderer->method('isPreview')->willReturn($previewMode);

		$renderer->initSurvey('dummy_survey', []);

		$survey = $renderer->getSurvey();
		$questions = $survey->getQuestions();

		$this->assertCount($expectedQuestions, $questions);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Survey\SurveyRenderer::initSurvey()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testInitSurveyWithValidRequestData(): void {
		$renderer = new SurveyRenderer($this->view);
		$requestData = [
			'survey' => [
				'dummy_survey' => [
					'8524de5e' => 4,
					'f69b1648' => [7, 8],
				],
			],
		];
		$renderer->initSurvey('dummy_survey', $requestData);

		$this->assertEquals($requestData['survey']['dummy_survey'], $renderer->getSurvey()->getProgress());
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Survey\SurveyRenderer::initSurvey()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testInitSurveyWithInvalidRequestData(): void {
		$renderer = new SurveyRenderer($this->view);
		$requestData = [
			'survey' => [
				'dummy_survey' => [
					'8524de5e' => 4,
					'foo' => 'bar',
				],
			],
		];
		$renderer->initSurvey('dummy_survey', $requestData);

		$this->assertEquals(['8524de5e' => 4], $renderer->getSurvey()->getProgress());
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Survey\SurveyRenderer::process()
	 * @throws \ReflectionException
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection HtmlUnknownAnchorTarget
	 */
	public function testProcess(): void {
		$renderer = new SurveyRenderer($this->view);
		$renderer->initSurvey('dummy_survey', []);
		$renderer->process();

		$result = $renderer->getSurveyBody([]);

		$this->assertStringContainsString('<form method="post" action="#Survey-DummySurvey">', $result);
		$this->assertStringContainsString('<input type="radio" name="survey[dummy_survey][8524de5e]" value="4" id="SurveyAnswer-Input4">', $result);
		$this->assertStringContainsString('<input type="hidden" name="_survey_identifier" value="dummy_survey">', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Survey\SurveyRenderer::process()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testProcessThrowsIfNoSurveyInitialized(): void {
		$renderer = new SurveyRenderer($this->view);
		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('No survey was initialized.');
		$renderer->process();
	}


	/**
	 * @dataProvider saveEntryAndRedirectActionsProvider
	 * @param \BackedEnum $action
	 * @return void
	 * @see \Awyiss\Utility\Survey\SurveyRenderer::process()
	 * @throws \ReflectionException
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testProcessSavesAndRedirectsWhenConditionsMet(BackedEnum $action): void {
		$renderer = new SurveyRenderer($this->view);

		$renderer->initSurvey('dummy_survey', ['_survey_identifier' => 'dummy_survey']);
		$survey = $renderer->getSurvey();
		$survey->setCurrentAction($action);

		$table = $this->getTableLocator()->get('SurveyEntries');
		$entries = $table->find('all')->count();

		$thrown = false;
		try {
			$renderer->process();
		}
		catch (RedirectException $ex) {
			// Make sure this exception is thrown to indicate a redirect
			$this->assertStringContainsString('/survey-entry:', $ex->getMessage());
			$thrown = true;
		}

		$this->assertTrue($thrown, 'Expected RedirectException to be thrown.');

		$result = $renderer->getSurveyBody([]);

		$this->assertStringContainsString('<div class="Survey-SuccessMessage"><p>Success</p></div>', $result);

		$this->assertSame($entries + 1, $table->find('all')->count());
	}


	/**
	 * @dataProvider saveEntryAndRedirectActionsProvider
	 * @param \BackedEnum $action
	 * @return void
	 * @see \Awyiss\Utility\Survey\SurveyRenderer::process()
	 * @throws \ReflectionException
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection HtmlUnknownAnchorTarget
	 */
	public function testProcessNotSavesAndRedirectsWhenDifferentSurvey(BackedEnum $action): void {
		$renderer = new SurveyRenderer($this->view);

		$renderer->initSurvey('dummy_survey', ['_survey_identifier' => 'dummy_survey2']);
		$survey = $renderer->getSurvey();
		$survey->setCurrentAction($action);

		$table = $this->getTableLocator()->get('SurveyEntries');
		$entries = $table->find('all')->count();

		$thrown = false;
		try {
			$renderer->process();
		}
		catch (RedirectException) {
			$thrown = true;
		}

		$this->assertFalse($thrown, 'Expected RedirectException not to be thrown.');

		$result = $renderer->getSurveyBody([]);

		$this->assertStringContainsString('<form method="post" action="#Survey-DummySurvey">', $result);

		$this->assertSame($entries, $table->find('all')->count());
	}


	/**
	 * @dataProvider saveEntryAndRedirectActionsProvider
	 * @param \BackedEnum $action
	 * @return void
	 * @see \Awyiss\Utility\Survey\SurveyRenderer::process()
	 * @throws \ReflectionException
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection HtmlUnknownAnchorTarget
	 */
	public function testProcessNotSavesAndRedirectsWhenFormIdentifierIsSet(BackedEnum $action): void {
		$renderer = new SurveyRenderer($this->view);

		$renderer->initSurvey('dummy_survey', [
			'_survey_identifier' => 'dummy_survey',
			'_form_identifier' => 'form_123',
		]);
		$survey = $renderer->getSurvey();
		$survey->setCurrentAction($action);

		$table = $this->getTableLocator()->get('SurveyEntries');
		$entries = $table->find('all')->count();

		$thrown = false;
		try {
			$renderer->process();
		}
		catch (RedirectException) {
			$thrown = true;
		}

		$this->assertFalse($thrown, 'Expected RedirectException not to be thrown.');

		$result = $renderer->getSurveyBody([]);

		$this->assertStringContainsString('<form method="post" action="#Survey-DummySurvey">', $result);

		$this->assertSame($entries, $table->find('all')->count());
	}


	/**
	 * @dataProvider showFormActionsProvider
	 * @param \BackedEnum $action
	 * @return void
	 * @see \Awyiss\Utility\Survey\SurveyRenderer::process()
	 * @throws \ReflectionException
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection HtmlUnknownAnchorTarget
	 */
	public function testProcessDoesNotSavesAndRedirectsForOtherActions(BackedEnum $action): void {
		$renderer = new SurveyRenderer($this->view);

		$renderer->initSurvey('dummy_survey', [
			'_survey_identifier' => 'dummy_survey',
		]);
		$survey = $renderer->getSurvey();
		$survey->setCurrentAction($action);

		$table = $this->getTableLocator()->get('SurveyEntries');
		$entries = $table->find('all')->count();

		$thrown = false;
		try {
			$renderer->process();
		}
		catch (RedirectException) {
			$thrown = true;
		}

		$this->assertFalse($thrown, 'Expected RedirectException not to be thrown.');

		$result = $renderer->getSurveyBody([]);

		$this->assertStringContainsString('<form method="post" action="#Survey-DummySurvey">', $result);

		$this->assertSame($entries, $table->find('all')->count());
	}


	/**
	 * @return array
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public static function saveEntryAndRedirectActionsProvider(): array {
		/** @var class-string<\Awyiss\Model\Enum\Survey\NextAction> $enum */
		$enum = App::className('NextAction', 'Model/Enum/Survey');

		return [
			[$enum::SaveAndEnd],
			[$enum::SaveAndShowForm],
		];
	}

	/**
	 * @return array
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public static function showFormActionsProvider(): array {
		/** @var class-string<\Awyiss\Model\Enum\Survey\NextAction> $enum */
		$enum = App::className('NextAction', 'Model/Enum/Survey');

		return [
			[$enum::ShowForm],
			[$enum::ShowFormAndSave],
		];
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Survey\SurveyRenderer::process()
	 * @see \Awyiss\Utility\Survey\SurveyRenderer::processSurveyEntryFromHash()
	 * @throws \ReflectionException
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testProcessProcessesEntryFromHash(): void {
		$renderer = new SurveyRenderer($this->view);

		$renderer->initSurvey('dummy_survey', ['_survey_identifier' => 'dummy_survey']);

		$survey = $renderer->getSurvey();

		$this->assertEquals([], $survey->getProgress());
		$this->assertTrue($survey->hasNextAction());

		$renderer->process('419840e6c9eae0682dec94a92e065136');

		$this->assertEquals([
			'8524de5e' => 4,
			'f69b1648' => [8, 9],
			'7d654446' => 'custom',
			'72054f17' => 11,
		], $survey->getProgress());
		$this->assertFalse($survey->hasNextAction());

		$result = $renderer->getSurveyBody([]);

		$this->assertStringContainsString('<div class="Survey-SuccessMessage"><p>Success</p></div>', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Survey\SurveyRenderer::process()
	 * @see \Awyiss\Utility\Survey\SurveyRenderer::processSurveyEntryFromHash()
	 * @throws \ReflectionException
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testProcessProcessesEntryFromHashWithInvalidHash(): void {
		$renderer = new SurveyRenderer($this->view);

		$renderer->initSurvey('dummy_survey', ['_survey_identifier' => 'dummy_survey']);

		$survey = $renderer->getSurvey();

		$this->assertEquals([], $survey->getProgress());
		$this->assertTrue($survey->hasNextAction());

		$renderer->process('invalid_hash');

		$this->assertEquals([], $survey->getProgress());
		$this->assertTrue($survey->hasNextAction());

		$result = $renderer->getSurveyBody([]);

		$this->assertStringNotContainsString('<div class="Survey-SuccessMessage"><p>Success</p></div>', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Survey\SurveyRenderer::process()
	 * @see \Awyiss\Utility\Survey\SurveyRenderer::processSurveyFromData()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testProcessLoadsProgressFromFormEntryHash(): void {
		$renderer = new SurveyRenderer($this->view);

		$renderer->initSurvey('dummy_survey', ['_survey_identifier' => 'dummy_survey']);

		$survey = $renderer->getSurvey();

		$this->assertEquals([], $survey->getProgress());
		$this->assertTrue($survey->hasNextAction());

		$renderer->process(null, '915c372723fe959f267987d352681425');

		$this->assertEquals([
			'8524de5e' => 4,
			'f69b1648' => [8, 9],
			'7d654446' => 'custom',
			'72054f17' => 11,
		], $survey->getProgress());
		$this->assertFalse($survey->hasNextAction());
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Survey\SurveyRenderer::process()
	 * @see \Awyiss\Utility\Survey\SurveyRenderer::processSurveyFromData()
	 * @throws \ReflectionException
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testProcessNotLoadsProgressFromFormEntryHash(): void {
		$renderer = new SurveyRenderer($this->view);

		$renderer->initSurvey('dummy_survey', ['_survey_identifier' => 'dummy_survey']);

		$survey = $renderer->getSurvey();

		$this->assertEquals([], $survey->getProgress());
		$this->assertTrue($survey->hasNextAction());

		// Using a different form entry hash
		$renderer->process(null, 'nonexistent_form_entry_hash');

		$this->assertEquals([], $survey->getProgress());
		$this->assertTrue($survey->hasNextAction());

		$result = $renderer->getSurveyBody([]);

		$this->assertStringNotContainsString('<div class="Survey-SuccessMessage"><p>Success</p></div>', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Survey\SurveyRenderer::process()
	 * @see \Awyiss\Utility\Survey\SurveyRenderer::processSurveyFromData()
	 * @throws \ReflectionException
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testProcessNotLoadsProgressFromFormEntryHashWithDataOfDifferentSurvey(): void {
		$renderer = new SurveyRenderer($this->view);

		$renderer->initSurvey('dummy_survey', ['_survey_identifier' => 'dummy_survey']);

		$survey = $renderer->getSurvey();

		$this->assertEquals([], $survey->getProgress());
		$this->assertTrue($survey->hasNextAction());

		// Using a different form entry hash
		$renderer->process(null, '915c3724723345akn53dc7d352681425');

		$this->assertEquals([], $survey->getProgress());
		$this->assertTrue($survey->hasNextAction());

		$result = $renderer->getSurveyBody([]);

		$this->assertStringNotContainsString('<div class="Survey-SuccessMessage"><p>Success</p></div>', $result);
	}


	/**
	 * @dataProvider showFormActionsProvider
	 * @param \BackedEnum $action
	 * @return void
	 * @see \Awyiss\Utility\Survey\SurveyRenderer::process()
	 * @throws \ReflectionException
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testProcessShowsForm(BackedEnum $action): void {
		$renderer = new SurveyRenderer($this->view);

		// Prepare request data to submit the form with valid data
		$renderer->initSurvey('dummy_survey', [
			'_survey_identifier' => 'dummy_survey',
		]);

		$table = $this->getTableLocator()->get('FormEntries');
		$entries = $table->find('all')->count();

		$survey = $renderer->getSurvey();
		$survey->finalAction = $action;

		$survey->setProgress([
			'8524de5e' => 4,
			'f69b1648' => [8, 9],
			'7d654446' => 'custom',
			'72054f17' => 11,
		]);

		$renderer->process();

		$result = $renderer->getSurveyBody([]);

		$this->assertStringContainsString('<form method="post" action="#Survey-DummySurvey"', $result);
		$this->assertStringContainsString('<input type="hidden" name="_form_identifier" value="contact">', $result);

		$this->assertStringContainsString('<input type="hidden" name="survey[dummy_survey][8524de5e]" value="4">', $result);
		$this->assertStringContainsString('<input type="hidden" name="survey[dummy_survey][f69b1648][]" value="8">', $result);
		$this->assertStringContainsString('<input type="hidden" name="survey[dummy_survey][f69b1648][]" value="9">', $result);
		$this->assertStringContainsString('<input type="hidden" name="survey[dummy_survey][7d654446]" value="custom">', $result);
		$this->assertStringContainsString('<input type="hidden" name="survey[dummy_survey][72054f17]" value="11', $result);

		$this->assertSame($entries, $table->find('all')->count());
	}


	/**
	 * @dataProvider showFormActionsProvider
	 * @param \BackedEnum $action
	 * @return void
	 * @see \Awyiss\Utility\Survey\SurveyRenderer::process()
	 * @throws \ReflectionException
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testProcessShowsFormWithInvalidDataContainsError(BackedEnum $action): void {
		$renderer = new SurveyRenderer($this->view);

		// Prepare request data to submit the form with valid data
		$renderer->initSurvey('dummy_survey', [
			'_survey_identifier' => 'dummy_survey',
			'_form_identifier' => 'contact',
		]);

		$table = $this->getTableLocator()->get('FormEntries');
		$entries = $table->find('all')->count();

		$survey = $renderer->getSurvey();
		$survey->finalAction = $action;

		$survey->setProgress([
			'8524de5e' => 4,
			'f69b1648' => [8, 9],
			'7d654446' => 'custom',
			'72054f17' => 11,
		]);

		$renderer->process();

		$result = $renderer->getSurveyBody([]);

		$this->assertStringContainsString('<form method="post" action="#Survey-DummySurvey"', $result);
		$this->assertStringContainsString('<input type="hidden" name="_form_identifier" value="contact">', $result);

		$this->assertStringContainsString('<input type="hidden" name="survey[dummy_survey][8524de5e]" value="4">', $result);
		$this->assertStringContainsString('<input type="hidden" name="survey[dummy_survey][f69b1648][]" value="8">', $result);
		$this->assertStringContainsString('<input type="hidden" name="survey[dummy_survey][f69b1648][]" value="9">', $result);
		$this->assertStringContainsString('<input type="hidden" name="survey[dummy_survey][7d654446]" value="custom">', $result);
		$this->assertStringContainsString('<input type="hidden" name="survey[dummy_survey][72054f17]" value="11', $result);

		$this->assertStringContainsString('<div class="Form-ErrorMessage" id="Form-Contact">', $result);

		$this->assertSame($entries, $table->find('all')->count());
	}


	/**
	 * @dataProvider showFormActionsProvider
	 * @param \BackedEnum $action
	 * @return void
	 * @see \Awyiss\Utility\Survey\SurveyRenderer::process()
	 * @throws \ReflectionException
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testProcessShowsFormWithInvalidDataContainsNotErrorWithOtherFormIdentifier(BackedEnum $action): void {
		$renderer = new SurveyRenderer($this->view);

		// Prepare request data to submit the form with valid data
		$renderer->initSurvey('dummy_survey', [
			'_survey_identifier' => 'dummy_survey',
			'_form_identifier' => 'contact2',
		]);

		$table = $this->getTableLocator()->get('FormEntries');
		$entries = $table->find('all')->count();

		$survey = $renderer->getSurvey();
		$survey->finalAction = $action;

		$survey->setProgress([
			'8524de5e' => 4,
			'f69b1648' => [8, 9],
			'7d654446' => 'custom',
			'72054f17' => 11,
		]);

		$renderer->process();

		$result = $renderer->getSurveyBody([]);

		$this->assertStringContainsString('<form method="post" action="#Survey-DummySurvey"', $result);
		$this->assertStringContainsString('<input type="hidden" name="_form_identifier" value="contact">', $result);

		$this->assertStringContainsString('<input type="hidden" name="survey[dummy_survey][8524de5e]" value="4">', $result);
		$this->assertStringContainsString('<input type="hidden" name="survey[dummy_survey][f69b1648][]" value="8">', $result);
		$this->assertStringContainsString('<input type="hidden" name="survey[dummy_survey][f69b1648][]" value="9">', $result);
		$this->assertStringContainsString('<input type="hidden" name="survey[dummy_survey][7d654446]" value="custom">', $result);
		$this->assertStringContainsString('<input type="hidden" name="survey[dummy_survey][72054f17]" value="11', $result);

		$this->assertStringNotContainsString('<div class="Form-ErrorMessage">', $result);

		$this->assertSame($entries, $table->find('all')->count());
	}


	/**
	 * @dataProvider showFormActionsProvider
	 * @param \BackedEnum $action
	 * @return void
	 * @see \Awyiss\Utility\Survey\SurveyRenderer::process()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testProcessSubmitsFormWithValidData(BackedEnum $action): void {
		$renderer = new SurveyRenderer($this->view);
		/** @var \Awyiss\Model\Entity\Page $page */
		$page = $this->getTableLocator()->get('Pages')->get(1);

		// Prepare request data to submit the form with valid data
		$renderer->initSurvey('dummy_survey', [
			'_survey_identifier' => 'dummy_survey',
			'_form_identifier' => 'contact',
			'vorname' => 'John',
			'nachricht' => 'Dummy Message',
			'nachname' => 'Doe',
			'datenschutz_akzeptiert' => 'Ja',
			'email' => 'domain@example.com',
		], $page);

		$table = $this->getTableLocator()->get('FormEntries');
		$entries = $table->find('all')->count();

		$survey = $renderer->getSurvey();
		$survey->finalAction = $action;
		$enum = $survey->getNextActionEnum();

		$survey->setProgress([
			'8524de5e' => 4,
			'f69b1648' => [8, 9],
			'7d654446' => 'custom',
			'72054f17' => 11,
		]);

		$thrown = false;
		try {
			$renderer->process();
		}
		catch (RedirectException $ex) {
			// Make sure this exception is thrown to indicate a redirect
			$this->assertStringContainsString('/form-entry:', $ex->getMessage());

			if ($action === $enum::ShowFormAndSave) {
				// Make sure this exception is thrown to indicate a redirect
				$this->assertStringContainsString('/survey-entry:', $ex->getMessage());
			}

			$thrown = true;
		}

		$this->assertTrue($thrown, 'Expected RedirectException to be thrown.');

		$this->assertSame($entries + 1, $table->find('all')->count());
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Survey\SurveyRenderer::process()
	 * @throws \ReflectionException
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection HtmlUnknownTarget
	 */
	public function testParseAwyissImageTagInSuccessMessage(): void {
		$view = new FrontendView($this->view->getRequest());
		$view->set('fullWidth', 1440);

		$renderer = new SurveyRenderer($view);

		$renderer->initSurvey('dummy_survey3', [
			'_survey_identifier' => 'dummy_survey3',
			'survey' => [
				'dummy_survey3' => [
					'9f8b2c3d' => 13,
				],
			],
		]);

		$survey = $renderer->getSurvey();

		$this->assertEquals([
			'9f8b2c3d' => 13,
		], $survey->getProgress());
		$this->assertFalse($survey->hasNextAction());

		try {
			$renderer->process();
		}
		catch (RedirectException) {
		}

		$result = $renderer->getSurveyBody([]);

		$this->assertStringContainsString('<div class="Survey-SuccessMessage"><p>Success with inline img tag</p><p><picture>', $result);
		$this->assertStringContainsString('<img data-src="_resized/dummypath/logo-awyiss-[w1440].avif" alt="logo-awyiss.png"', $result);
		$this->assertStringContainsString('<noscript><img src="_resized/dummypath/logo-awyiss-[w1440].avif" alt="logo-awyiss.png"', $result);
		$this->assertStringContainsString('</picture></p><p>between two paragraphs</p></div>', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Survey\SurveyRenderer::process()
	 * @throws \ReflectionException
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection HtmlUnknownTarget
	 */
	public function testParseAwyissImageTagInErrorMessage(): void {
		$view = new FrontendView($this->view->getRequest());
		$view->set('fullWidth', 1440);

		$renderer = new SurveyRenderer($view);

		$renderer->initSurvey('dummy_survey3', [
			'_survey_identifier' => 'dummy_survey3',
			'survey' => [
				'dummy_survey3' => [
					'9f8b2c3d' => 14,
				],
			],
		]);

		$survey = $renderer->getSurvey();

		$this->assertEquals([
			'9f8b2c3d' => 14,
		], $survey->getProgress());
		$this->assertFalse($survey->hasNextAction());

		$renderer->process();

		$result = $renderer->getSurveyBody([]);

		$this->assertStringContainsString('<div class="Survey-FailureMessage"><p>Failure with inline img tag</p><p><picture>', $result);
		$this->assertStringContainsString('<img data-src="_resized/dummypath/logo-awyiss-[w1440].avif" alt="logo-awyiss.png"', $result);
		$this->assertStringContainsString('<noscript><img src="_resized/dummypath/logo-awyiss-[w1440].avif" alt="logo-awyiss.png"', $result);
		$this->assertStringContainsString('</picture></p><p>between two paragraphs</p></div>', $result);
	}





	/**
	 * @return void
	 * @see \Awyiss\Utility\Survey\SurveyRenderer::process()
	 * @throws \ReflectionException
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection HtmlUnknownTarget
	 */
	public function testParseAwyissImageTagInInfoTextQuestion(): void {
		$view = new FrontendView($this->view->getRequest());
		$view->set('fullWidth', 1440);

		$renderer = $this->getMockBuilder(SurveyRenderer::class)->onlyMethods(['isPreview'])->setConstructorArgs([$view])->getMock();
		$renderer->method('isPreview')->willReturn(true);

		// Prepare request data to submit the form with valid data
		$renderer->initSurvey('dummy_survey', [
			'_survey_identifier' => 'dummy_survey',
		]);

		$survey = $renderer->getSurvey();

		$survey->setProgress([
			'8524de5e' => 4,
			'f69b1648' => [8, 9],
		]);

		$renderer->process();

		$result = $renderer->getSurveyBody([]);

		$this->assertStringContainsString('<div class="Text SurveyQuestion-Text"><p>Info text with inline img tag<br><picture>', $result);
		$this->assertStringContainsString('<img data-src="_resized/dummypath/logo-awyiss-[w1440].avif" alt="logo-awyiss.png"', $result);
		$this->assertStringContainsString('<noscript><img src="_resized/dummypath/logo-awyiss-[w1440].avif" alt="logo-awyiss.png"', $result);
		$this->assertStringContainsString('</picture></p></div>', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Survey\SurveyRenderer::process()
	 * @throws \ReflectionException
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection HtmlUnknownTarget
	 */
	public function testParseModuleInSuccessMessage(): void {
		$view = new FrontendView($this->view->getRequest());
		$view->set('fullWidth', 1440);

		$renderer = new SurveyRenderer($view);

		$renderer->initSurvey('dummy_survey3', [
			'_survey_identifier' => 'dummy_survey3',
			'survey' => [
				'dummy_survey3' => [
					'9f8b2c3d' => 13,
				],
			],
		]);

		$survey = $renderer->getSurvey();
		$survey->successMessage = '<p><module data-identifier="test" data-label="Testmodul">{"key":"value"}</module></p>';

		$this->assertEquals([
			'9f8b2c3d' => 13,
		], $survey->getProgress());
		$this->assertFalse($survey->hasNextAction());

		try {
			$renderer->process();
		}
		catch (RedirectException) {
		}

		$result = $renderer->getSurveyBody([]);

		$this->assertStringContainsString('<div class="Survey-SuccessMessage"><p>Rendered Output (and key is `value`)</p></div>', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Survey\SurveyRenderer::process()
	 * @throws \ReflectionException
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection HtmlUnknownTarget
	 */
	public function testParseModuleInErrorMessage(): void {
		$view = new FrontendView($this->view->getRequest());
		$view->set('fullWidth', 1440);

		$renderer = new SurveyRenderer($view);

		$renderer->initSurvey('dummy_survey3', [
			'_survey_identifier' => 'dummy_survey3',
			'survey' => [
				'dummy_survey3' => [
					'9f8b2c3d' => 14,
				],
			],
		]);

		$survey = $renderer->getSurvey();
		$survey->failureMessage = '<p><module data-identifier="test" data-label="Testmodul">{"key":"value"}</module></p>';

		$this->assertEquals([
			'9f8b2c3d' => 14,
		], $survey->getProgress());
		$this->assertFalse($survey->hasNextAction());

		$renderer->process();

		$result = $renderer->getSurveyBody([]);

		$this->assertStringContainsString('<div class="Survey-FailureMessage"><p>Rendered Output (and key is `value`)</p></div>', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Survey\SurveyRenderer::getSurveyBody()
	 * @throws \ReflectionException
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testParseModuleInInfoTextQuestion(): void {
		$view = new FrontendView($this->view->getRequest());
		$view->set('fullWidth', 1440);

		$renderer = $this->getMockBuilder(SurveyRenderer::class)->onlyMethods(['isPreview'])->setConstructorArgs([$view])->getMock();
		$renderer->method('isPreview')->willReturn(true);

		// Prepare request data to submit the form with valid data
		$renderer->initSurvey('dummy_survey', [
			'_survey_identifier' => 'dummy_survey',
		]);

		$survey = $renderer->getSurvey();

		$survey->setProgress([
			'8524de5e' => 4,
			'f69b1648' => [8, 9],
		]);

		$question = $survey->getCurrentAction();
		$question->surveyQuestion->text = '<p><module data-identifier="test" data-label="Testmodul">{"key":"value"}</module></p>';

		$renderer->process();

		$result = $renderer->getSurveyBody([]);

		$this->assertStringContainsString('<div class="Text SurveyQuestion-Text"><p>Rendered Output (and key is `value`)</p></div>', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Survey\SurveyRenderer::getSurveyBody()
	 * @throws \ReflectionException
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testResultClassFinalResult(): void {
		$view = new FrontendView($this->view->getRequest());
		$view->set('fullWidth', 1440);

		$renderer = new SurveyRenderer($view);

		$renderer->initSurvey('dummy_survey4', [
			'_survey_identifier' => 'dummy_survey4',
			'survey' => [
				'dummy_survey4' => [
					'c3b2a1e4' => 16,
				],
			],
		]);

		try {
			$renderer->process();
		}
		catch (RedirectException) {
		}

		$result = $renderer->getSurveyBody([]);

		$this->assertStringContainsString('<div class="Survey-SuccessMessage">Alternative Success Message from DummySurvey4SurveyResults</div>', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Survey\SurveyRenderer::getSurveyBody()
	 * @throws \ReflectionException
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testResultClassStepResult(): void {
		$view = new FrontendView($this->view->getRequest());
		$view->set('fullWidth', 1440);

		$renderer = $this->getMockBuilder(SurveyRenderer::class)->onlyMethods(['isPreview'])->setConstructorArgs([$view])->getMock();
		$renderer->method('isPreview')->willReturn(true);

		$renderer->initSurvey('dummy_survey4', [
			'_survey_identifier' => 'dummy_survey4',
			'survey' => [
				'dummy_survey4' => [
					'c3b2a1e4' => 16,
				],
			],
		]);

		try {
			$renderer->process();
		}
		catch (RedirectException) {
		}

		$result = $renderer->getSurveyBody([]);

		$this->assertStringContainsString('<div class="Text SurveyQuestion-Result">Alternative Step Result from DummySurvey4SurveyResults</div>', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Survey\SurveyRenderer::getSurveyBody()
	 * @throws \ReflectionException
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection HtmlUnknownAnchorTarget
	 */
	public function testGetSurveyBodySingleChoice(): void {
		$renderer = new SurveyRenderer($this->view);

		// Prepare request data to submit the form with valid data
		$renderer->initSurvey('dummy_survey', []);

		$renderer->process();

		$result = $renderer->getSurveyBody([]);

		$this->assertStringContainsString('<form method="post" action="#Survey-DummySurvey">', $result);
		$this->assertStringContainsString('<div class="SurveyQuestion SurveyQuestionType-SingleChoice" id="SurveyQuestion-8524de5e">', $result);
		$this->assertStringContainsString('<input type="radio" name="survey[dummy_survey][8524de5e]" value="4" id="SurveyAnswer-Input4">', $result);
		$this->assertStringContainsString('<input type="radio" name="survey[dummy_survey][8524de5e]" value="5" id="SurveyAnswer-Input5">', $result);
		$this->assertStringContainsString('<input type="radio" name="survey[dummy_survey][8524de5e]" value="6" id="SurveyAnswer-Input6">', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Survey\SurveyRenderer::getSurveyBody()
	 * @throws \ReflectionException
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection HtmlUnknownAnchorTarget
	 */
	public function testGetSurveyBodyMultipleChoice(): void {
		$renderer = new SurveyRenderer($this->view);

		// Prepare request data to submit the form with valid data
		$renderer->initSurvey('dummy_survey', [
			'_survey_identifier' => 'dummy_survey',
			'survey' => [
				'dummy_survey' => [
					'8524de5e' => 4,
				],
			],
		]);

		$renderer->process();

		$result = $renderer->getSurveyBody([]);

		$this->assertStringContainsString('<form method="post" action="#Survey-DummySurvey">', $result);
		$this->assertStringContainsString('<div class="SurveyQuestion SurveyQuestionType-MultipleChoice" id="SurveyQuestion-F69b1648">', $result);
		$this->assertStringContainsString('<input type="checkbox" name="survey[dummy_survey][f69b1648][]" value="7" id="SurveyAnswer-Input7">', $result);
		$this->assertStringContainsString('<input type="checkbox" name="survey[dummy_survey][f69b1648][]" value="8" id="SurveyAnswer-Input8">', $result);
		$this->assertStringContainsString('<input type="checkbox" name="survey[dummy_survey][f69b1648][]" value="9" id="SurveyAnswer-Input9">', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Survey\SurveyRenderer::getSurveyBody()
	 * @throws \ReflectionException
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection HtmlUnknownAnchorTarget
	 */
	public function testGetSurveyBodyInfoText(): void {
		// Mock the preview mode inside the SurveyRenderer
		$renderer = $this->getMockBuilder(SurveyRenderer::class)->onlyMethods(['isPreview'])->setConstructorArgs([$this->view])->getMock();
		$renderer->method('isPreview')->willReturn(true);

		// Prepare request data to submit the form with valid data
		$renderer->initSurvey('dummy_survey', [
			'_survey_identifier' => 'dummy_survey',
			'survey' => [
				'dummy_survey' => [
					'8524de5e' => 4,
					'f69b1648' => [8, 9],
				],
			],
		]);

		$renderer->process();

		$result = $renderer->getSurveyBody([]);


		$this->assertStringContainsString('<form method="post" action="#Survey-DummySurvey">', $result);
		$this->assertStringContainsString('<div class="SurveyQuestion SurveyQuestionType-InfoText" id="SurveyQuestion-0194a883">', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Survey\SurveyRenderer::getSurveyBody()
	 * @throws \ReflectionException
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection HtmlUnknownAnchorTarget
	 */
	public function testGetSurveyBodyFreeText(): void {
		$renderer = new SurveyRenderer($this->view);

		// Prepare request data to submit the form with valid data
		$renderer->initSurvey('dummy_survey', [
			'_survey_identifier' => 'dummy_survey',
			'survey' => [
				'dummy_survey' => [
					'8524de5e' => 4,
					'f69b1648' => [8, 9],
				],
			],
		]);

		$renderer->process();

		$result = $renderer->getSurveyBody([]);


		$this->assertStringContainsString('<form method="post" action="#Survey-DummySurvey">', $result);
		$this->assertStringContainsString('<div class="SurveyQuestion SurveyQuestionType-FreeText" id="SurveyQuestion-7d654446">', $result);
		$this->assertStringContainsString('<textarea name="survey[dummy_survey][custom][7d654446]"', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Survey\SurveyRenderer::getFormRenderer()
	 * @throws \ReflectionException
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection HtmlUnknownAnchorTarget
	 */
	public function testGetFormRendererLoadsCustomFormRenderer(): void {
		$renderer = new SurveyRenderer($this->view);

		// Prepare request data to submit the form with valid data
		$renderer->initSurvey('dummy_survey', [
			'_survey_identifier' => 'dummy_survey',
			'survey' => [
				'dummy_survey' => [
					'8524de5e' => 4,
					'f69b1648' => [8, 9],
				],
			],
		]);

		$formRenderer = $this->callProtectedMethod($renderer, 'getFormRenderer');

		$this->assertInstanceOf(CustomFormRenderer::class, $formRenderer);
	}
}
