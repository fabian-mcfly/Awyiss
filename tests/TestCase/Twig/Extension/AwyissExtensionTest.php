<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Twig\Extension;


use Awyiss\Awyiss;
use Awyiss\Routing\Router;
use Awyiss\Test\TestSuite\TestCase;
use Awyiss\Twig\Extension\AwyissExtension;
use Awyiss\Twig\NodeVisitor\ExtendsNodeVisitor;
use Awyiss\View\FrontendView;
use Cake\Core\Configure;
use Cake\Http\ServerRequest;
use Cake\I18n\I18n;
use Cake\TestSuite\IntegrationTestTrait;
use Customer\Configuration\ConfigOptions\DummyConfigOptions;
use DateTime;
use InvalidArgumentException;
use stdClass;
use Twig\TwigFilter;
use Twig\TwigFunction;
use Twig\TwigTest;


/**
 * Test case for AwyissExtension
 *
 * @see \Awyiss\Twig\Extension\AwyissExtension
 */
class AwyissExtensionTest extends TestCase {
	use IntegrationTestTrait;


	/**
	 * @var string
	 */
	protected string $locale;
	/**
	 * @var \Awyiss\Twig\Extension\AwyissExtension
	 */
	protected AwyissExtension $extension;
	/**
	 * @var \Awyiss\View\FrontendView
	 */
	protected FrontendView $view;


	/**
	 * @inheritDoc
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->configApplication(Awyiss::class, []);

		Awyiss::loadConfiguration('xy', 'xy');
		Configure::write('Awyiss.Forms.Frontend.protection.methods', []);

		$request = new ServerRequest([
			'url' => 'xy/dummy-slug',
			'params' => [
				'lang' => 'xy',
				'slug' => 'dummy-slug',
				'_name' => Awyiss::REALM_FRONTEND,
				'prefix' => Awyiss::REALM_FRONTEND,
				'parts' => [],
				'pass' => [],
				'plugin' => null,
			],
		]);
		Router::setRequest($request);

		$this->loadRoutes();

		$this->extension = new AwyissExtension();
		$this->view = new FrontendView($request);

		$this->locale = ini_get('intl.default_locale');
		ini_set('intl.default_locale', 'de_DE');
		I18n::setLocale('de_DE');
		setlocale(LC_ALL, 'de_DE.utf8');
	}


	/**
	 * @inheritDoc
	 */
	protected function tearDown(): void {
		parent::tearDown();

		ini_set('intl.default_locale', $this->locale);
		I18n::setLocale($this->locale);
		setlocale(LC_ALL, $this->locale . '.utf8');
	}


	/**
	 * @return void
	 * @see \Awyiss\Twig\Extension\AwyissExtension::getFilters()
	 */
	public function testGetFiltersReturnsCorrectFilters(): void {
		$filters = $this->extension->getFilters();

		$this->assertIsArray($filters);
		$this->assertCount(7, $filters);

		$filterNames = array_map(fn (TwigFilter $filter) => $filter->getName(), $filters);
		$expectedNames = ['inlineCss', 'dataAttr', 'jsonEncode', 'jsonDecode', 'prefixNumericClass', 'repeat', 'ucparts'];

		$this->assertEquals($expectedNames, $filterNames);
	}


	/**
	 * @return void
	 * @see \Awyiss\Twig\Extension\AwyissExtension::getFunctions()
	 */
	public function testGetFunctionsReturnsCorrectFunctions(): void {
		$functions = $this->extension->getFunctions();

		$this->assertIsArray($functions);
		$this->assertCount(28, $functions);

		$functionNames = array_map(fn (TwigFunction $function) => $function->getName(), $functions);
		$expectedNames = [
			'combine',
			'content',
			'customerCenterUrl',
			'dump',
			'form',
			'getClass',
			'globalContent',
			'__',
			'__f',
			'__n',
			'__d',
			'__dn',
			'__x',
			'__xn',
			'__dx',
			'__dxn',
			'__df',
			'__dfx',
			'__l',
			'__ld',
			'hashPrinter',
			'localConfig',
			'menu',
			'naturalSort',
			'staticCall',
			'survey',
			'widget',
			'wordCount',
		];

		foreach ($expectedNames as $name) {
			$this->assertContains($name, $functionNames);
		}
	}


	/**
	 * @return void
	 * @see \Awyiss\Twig\Extension\AwyissExtension::getTests()
	 */
	public function testGetTestsReturnsCorrectTests(): void {
		$tests = $this->extension->getTests();

		$this->assertIsArray($tests);
		$this->assertCount(6, $tests);

		$testNames = array_map(fn (TwigTest $test) => $test->getName(), $tests);
		$expectedNames = ['array', 'file', 'instanceOf', 'numeric', 'pageRole', 'string'];

		$this->assertEquals($expectedNames, $testNames);
	}


	/**
	 * @return void
	 * @see \Awyiss\Twig\Extension\AwyissExtension::getNodeVisitors()
	 */
	public function testGetNodeVisitorsReturnsExtendsNodeVisitor(): void {
		$visitors = $this->extension->getNodeVisitors();

		$this->assertIsArray($visitors);
		$this->assertCount(1, $visitors);
		$this->assertInstanceOf(ExtendsNodeVisitor::class, $visitors[0]);
	}


	/**
	 * @return void
	 * @see \Awyiss\Twig\Extension\AwyissExtension::inlineCss()
	 */
	public function testInlineCssFilter(): void {
		$filters = $this->extension->getFilters();
		$inlineCssFilter = $filters[0];
		$callable = $inlineCssFilter->getCallable();

		$result = $callable('<p>Test</p>', 'p { color: red; }');

		$this->assertStringContainsString('<p style="color: red;">Test</p>', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\Twig\Extension\AwyissExtension::htmlDataAttributes()
	 */
	public function testDataAttrFilter(): void {
		$filters = $this->extension->getFilters();
		$dataAttrFilter = $filters[1];
		$callable = $dataAttrFilter->getCallable();

		$result = $callable(['test' => 'value', 'another' => 'data']);

		$this->assertSame('data-test="value" data-another="data"', $result);
	}


	/**
	 * @return void
	 */
	public function testJsonEncodeFilter(): void {
		$filters = $this->extension->getFilters();
		$jsonEncodeFilter = $filters[2];
		$callable = $jsonEncodeFilter->getCallable();

		$result = $callable(['key' => 'value']);

		$this->assertEquals('{"key":"value"}', $result);
	}


	/**
	 * @return void
	 */
	public function testJsonDecodeFilter(): void {
		$filters = $this->extension->getFilters();
		$jsonDecodeFilter = $filters[3];
		$callable = $jsonDecodeFilter->getCallable();

		$result = $callable('{"key": "value"}');

		$this->assertEquals(['key' => 'value'], $result);
	}


	/**
	 * @return void
	 */
	public function testPrefixNumericClassFilter(): void {
		$filters = $this->extension->getFilters();
		$prefixFilter = $filters[4];
		$callable = $prefixFilter->getCallable();

		$result = $callable('123test');

		$this->assertEquals('Page123test', $result);

		$result = $callable('test123');

		$this->assertEquals('test123', $result);
	}


	/**
	 * @return void
	 */
	public function testRepeatFilter(): void {
		$filters = $this->extension->getFilters();
		$repeatFilter = $filters[5];
		$callable = $repeatFilter->getCallable();

		$result = $callable('hello', 3);

		$this->assertEquals('hellohellohello', $result);
	}


	/**
	 * @return void
	 */
	public function testUcpartsFilterr(): void {
		$filters = $this->extension->getFilters();
		$ucpartsFilter = $filters[6];
		$callable = $ucpartsFilter->getCallable();

		$result = $callable('hello_world');

		$this->assertSame('Hello_World', $result);

		$result = $callable('hello-world', '-');

		$this->assertSame('Hello-World', $result);

		$result = $callable('hello-world', false);

		$this->assertSame('HelloWorld', $result);
	}


	/**
	 * @return void
	 */
	public function testCombineFunction(): void {
		$functions = $this->extension->getFunctions();
		$combineFunction = $functions[0];
		$callable = $combineFunction->getCallable();

		$result = $callable(['a', 'b'], [1, 2]);

		$this->assertEquals(['a' => 1, 'b' => 2], $result);
	}


	/**
	 * @return void
	 */
	public function testContentFunction(): void {
		$functions = $this->extension->getFunctions();
		$contentFunction = $functions[1];
		$callable = $contentFunction->getCallable();

		$output = $callable([
			'page' => $this->fetchTable('Pages')->get(1),
			'_view' => $this->view,
		], 'ContentArea');

		$this->assertIsString($output);
	}


	/**
	 * @return void
	 */
	public function testContentFunctionWithArguments(): void {
		$functions = $this->extension->getFunctions();
		$contentFunction = $functions[1];
		$callable = $contentFunction->getCallable();

		$output = $callable(
			[
				'page' => $this->fetchTable('Pages')->get(1),
				'_view' => $this->view,
			],
			'ContentArea',
			[
				'fullWidth' => 1440.00,
				'includeWrapper' => true,
				'singleColumnBreakpoint' => 768.00,
			]
		);

		$output = trim(preg_replace('/\s+/', ' ', $output));
		$output = str_replace('> ', '>' . PHP_EOL, $output);

		$this->assertStringEqualsFile(ROOT . DS . 'tests' . DS . 'comparisons' . DS . 'output' . DS . 'Content-Page1.txt', $output);
	}


	/**
	 * @return void
	 */
	public function testContentFunctionWithoutPage(): void {
		$functions = $this->extension->getFunctions();
		$contentFunction = $functions[1];
		$callable = $contentFunction->getCallable();

		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('The "content" function requires a Page entity in the context.');

		$callable([], 'test_content');
	}


	/**
	 * @return void
	 */
	public function testCustomerCenterUrlFunction(): void {
		$functions = $this->extension->getFunctions();
		$customerCenterUrlFunction = $functions[2];
		$callable = $customerCenterUrlFunction->getCallable();

		// Test with explicit language parameter
		$result = $callable(['languageShortcode' => 'es'], 'login', 'es');
		$this->assertSame('/es/customer-center/login/', $result);

		// Test with language from context
		$result = $callable(['languageShortcode' => 'es'], 'logout');
		$this->assertSame('/es/customer-center/logout/', $result);

		// Test another action
		$result = $callable(['languageShortcode' => 'es'], 'register', 'es');
		$this->assertSame('/es/customer-center/register/', $result);
	}


	/**
	 * @return void
	 */
	public function testCustomerCenterUrlFunctionWithDifferentLanguage(): void {
		$functions = $this->extension->getFunctions();
		$customerCenterUrlFunction = $functions[2];
		$callable = $customerCenterUrlFunction->getCallable();

		// Test with German language that has custom configuration
		$result = $callable(['languageShortcode' => 'de'], 'login', 'de');
		$this->assertSame('/de/konto/anmelden/', $result);

		$result = $callable(['languageShortcode' => 'de'], 'logout', 'de');
		$this->assertSame('/de/konto/abmelden/', $result);

		$result = $callable(['languageShortcode' => 'de'], 'dashboard', 'de');
		$this->assertSame('/de/konto/uebersicht/', $result);
	}


	/**
	 * @return void
	 */
	public function testCustomerCenterUrlFunctionWithInvalidAction(): void {
		$functions = $this->extension->getFunctions();
		$customerCenterUrlFunction = $functions[2];
		$callable = $customerCenterUrlFunction->getCallable();

		// Test with non-existent action
		$result = $callable(['languageShortcode' => 'xy'], 'nonExistentAction', 'xy');
		$this->assertNull($result);
	}


	/**
	 * @return void
	 */
	public function testCustomerCenterUrlFunctionWithoutLanguage(): void {
		$functions = $this->extension->getFunctions();
		$customerCenterUrlFunction = $functions[2];
		$callable = $customerCenterUrlFunction->getCallable();

		// Test without language in context or parameter - should use default from LocaleMiddleware
		$result = $callable([], 'login');
		$this->assertSame('/de/konto/anmelden/', $result);
	}


	/**
	 * @return void
	 */
	public function testCustomerCenterUrlFunctionWithoutConfig(): void {
		$functions = $this->extension->getFunctions();
		$customerCenterUrlFunction = $functions[2];
		$callable = $customerCenterUrlFunction->getCallable();

		// Temporarily remove the customer center configuration
		$originalConfig = Configure::read('Route.CustomerCenter');
		Configure::write('Route.CustomerCenter', null);

		$result = $callable(['languageShortcode' => 'xy'], 'login', 'xy');
		$this->assertNull($result);

		// Restore configuration
		Configure::write('Route.CustomerCenter', $originalConfig);
	}


	/**
	 * @return void
	 */
	public function testCustomerCenterUrlFunctionWithCamelCaseAction(): void {
		$functions = $this->extension->getFunctions();
		$customerCenterUrlFunction = $functions[2];
		$callable = $customerCenterUrlFunction->getCallable();

		// Test with camelCase action names
		$result = $callable(['languageShortcode' => 'es'], 'editProfile', 'es');
		$this->assertSame('/es/customer-center/edit-profile/', $result);

		$result = $callable(['languageShortcode' => 'es'], 'changePassword', 'es');
		$this->assertSame('/es/customer-center/change-password/', $result);

		$result = $callable(['languageShortcode' => 'de'], 'forgotPassword', 'de');
		$this->assertSame('/de/konto/passwort-vergessen/', $result);
	}


	/**
	 * @return void
	 */
	public function testFormFunction(): void {
		$functions = $this->extension->getFunctions();
		$formFunction = $functions[4];
		$callable = $formFunction->getCallable();

		$output = $callable([
			'page' => $this->fetchTable('Pages')->get(1),
			'_view' => $this->view,
		], 1);

		$this->assertIsString($output);

		$output = $callable([
			'page' => $this->fetchTable('Pages')->get(1),
			'_view' => $this->view,
		], 'contact');

		$this->assertIsString($output);
	}


	/**
	 * @return void
	 */
	public function testFormFunctionWithArguments(): void {
		$functions = $this->extension->getFunctions();
		$formFunction = $functions[4];
		$callable = $formFunction->getCallable();

		$output = $callable(
			[
				'page' => $this->fetchTable('Pages')->get(1),
				'_view' => $this->view,
			],
			'contact',
			[
				'fullWidth' => 1440.00,
				'singleColumnBreakpoint' => 768.00,
			]
		);

		$output = trim(preg_replace('/\s+/', ' ', $output));
		$output = str_replace('> ', '>' . PHP_EOL, $output);
		$output = str_replace('> ', '>' . PHP_EOL, $output);

		// The form uses the current timestamp for the id of the action. The comparison file uses the string '{{now}}' instead.
		$output = preg_replace('/data-[0-9]{10}/', 'data-{{now}}', $output);
		$output = preg_replace('/formElementsChecksum = \'[0-9]{10}/', 'formElementsChecksum = \'{{now}}', $output);

		$this->assertStringEqualsFile(ROOT . DS . 'tests' . DS . 'comparisons' . DS . 'output' . DS . 'Form-Contact.txt', $output);
	}


	/**
	 * @return void
	 */
	public function testFormFunctionWithoutPage(): void {
		$functions = $this->extension->getFunctions();
		$formFunction = $functions[4];
		$callable = $formFunction->getCallable();

		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('The "form" function requires a Page entity in the context.');

		$callable([], 'test_form');
	}


	/**
	 * @return void
	 */
	public function testGetClassFunction(): void {
		$functions = $this->extension->getFunctions();
		$getClassFunction = $functions[5];
		$callable = $getClassFunction->getCallable();

		$object = new DummyConfigOptions();
		$result = $callable($object);

		$this->assertEquals('Customer\Configuration\ConfigOptions\DummyConfigOptions', $result);
	}


	/**
	 * @return void
	 */
	public function testGlobalContentFunction(): void {
		$functions = $this->extension->getFunctions();
		$globalContentFunction = $functions[6];
		$callable = $globalContentFunction->getCallable();

		$output = $callable(['_view' => $this->view], 'dummy_row_overflow', [
			'fullWidth' => 1440.00,
			'singleColumnBreakpoint' => 768.00,
		]);

		$output = trim(preg_replace('/\s+/', ' ', $output));
		$output = str_replace('> ', '>' . PHP_EOL, $output);

		$this->assertStringEqualsFile(ROOT . DS . 'tests' . DS . 'comparisons' . DS . 'output' . DS . 'GlobalContent-DummyRowOverflow.txt', $output);
	}


	/**
	 * @return void
	 * @noinspection HtmlRequiredAltAttribute, HtmlUnknownTarget
	 */
	public function testGlobalContentFunctionWithArguments(): void {
		$functions = $this->extension->getFunctions();
		$globalContentFunction = $functions[6];
		$callable = $globalContentFunction->getCallable();

		$output = $callable(
			['_view' => $this->view],
			'inline_img',
			[
				'fullWidth' => 1440.00,
				'singleColumnBreakpoint' => 768.00,
			]
		);

		$this->assertStringContainsString('<p>Global Content with inline img tag</p><p><picture>', $output);
		$this->assertStringContainsString('<img data-src="_resized/dummypath/logo-awyiss-[w1152].avif"', $output);
		$this->assertStringContainsString('<source media="(width <= 768px)" data-srcset="_resized/dummypath/logo-awyiss-[w768].avif 1x, _resized/dummypath/logo-awyiss-[w1536].avif 2x" type="image/avif">', $output);
		$this->assertStringContainsString('<source media="(width <= 1280px)" data-srcset="_resized/dummypath/logo-awyiss-[w1024].avif 1x, _resized/dummypath/logo-awyiss-[w2048].avif 2x" type="image/avif">', $output);
		$this->assertStringContainsString('<noscript><img src="_resized/dummypath/logo-awyiss-[w1152].avif"', $output);
		$this->assertStringContainsString('</picture></p><p>between two paragraphs</p>', $output);
	}


	/**
	 * @return void
	 */
	public function testTranslationFunctions(): void {
		$functions = $this->extension->getFunctions();

		// Test that all translation functions exist
		$translationFunctions = ['__', '__f', '__n', '__d', '__dn', '__x', '__xn', '__dx', '__dxn', '__df', '__dfx', '__l', '__ld'];

		foreach ($translationFunctions as $index => $funcName) {
			$function = $functions[ 7 + $index ]; // Translation functions start at index 7
			$this->assertEquals($funcName, $function->getName());
			$this->assertEquals($funcName, $function->getCallable());
		}
	}


	/**
	 * @return void
	 */
	public function testHashPrinterFunction(): void {
		$functions = $this->extension->getFunctions();
		$hashPrinterFunction = $functions[20];
		$callable = $hashPrinterFunction->getCallable();

		$data = [
			['title' => 'Item 1', 'level' => 1, 'id' => 1],
			['title' => 'Item 2', 'level' => 2, 'id' => 2],
		];

		$result = $callable($data, 'title', 'id');

		$this->assertEquals([
			1 => '- Item 1',
			2 => '- - Item 2',
		], $result);

		$result = $callable($data, 'title', 'id', '*');

		$this->assertEquals([
			1 => '*Item 1',
			2 => '**Item 2',
		], $result);

		$result = $callable($data, 'title', 'id', '* ', 1);

		$this->assertEquals([
			1 => 'Item 1',
			2 => '* Item 2',
		], $result);

		$result = $callable($data, 'title', 'title', '* ', 1);

		$this->assertEquals([
			'Item 1' => 'Item 1',
			'Item 2' => '* Item 2',
		], $result);
	}


	/**
	 * @return void
	 */
	public function testMenuFunction(): void {
		$functions = $this->extension->getFunctions();
		$menuFunction = $functions[22];
		$callable = $menuFunction->getCallable();

		$output = $callable(
			[
				'languageShortcode' => 'de',
				'_view' => $this->view,
			],
			'main'
		);

		$output = trim(preg_replace('/\s+/', ' ', $output));
		$output = str_replace('> ', '>' . PHP_EOL, $output);

		$this->assertStringEqualsFile(ROOT . DS . 'tests' . DS . 'comparisons' . DS . 'output' . DS . 'Menu-Main.txt', $output);
	}


	/**
	 * @return void
	 */
	public function testMenuFunctionWithoutLanguageShortcode(): void {
		$functions = $this->extension->getFunctions();
		$menuFunction = $functions[22];
		$callable = $menuFunction->getCallable();

		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('The "menu" function requires languageShortcode string in the context.');

		$callable([], 'main_menu');
	}


	/**
	 * @return void
	 */
	public function testNaturalSortFunction(): void {
		$functions = $this->extension->getFunctions();
		$naturalSortFunction = $functions[23];
		$callable = $naturalSortFunction->getCallable();

		$data = ['item10', 'item2', 'item1'];
		$result = $callable($data);

		$this->assertEquals(['item1', 'item2', 'item10'], array_values($result));

		$data = [
			['name' => 'item10'],
			['name' => 'item2'],
			['name' => 'item1'],
		];
		$result = $callable($data, 'name');

		$resultNames = array_map(fn ($item) => $item['name'], array_values($result));
		$this->assertEquals(['item1', 'item2', 'item10'], $resultNames);

		$data = ['Oman', 'Schweiz', 'Äthiopien', 'Österreich', 'Australien', 'Deutschland'];
		$result = $callable($data);

		$this->assertNotSame($data, $result);
		$this->assertSame([2 => 'Äthiopien', 4 => 'Australien', 5 => 'Deutschland', 0 => 'Oman', 3 => 'Österreich', 1 => 'Schweiz'], $result);
	}


	/**
	 * @return void
	 */
	public function testStaticCallFunction(): void {
		$functions = $this->extension->getFunctions();
		$staticCallFunction = $functions[24];
		$callable = $staticCallFunction->getCallable();

		$result = $callable('DateTime', 'createFromFormat', 'Y-m-d', '2020-02-02');

		$this->assertInstanceOf(DateTime::class, $result);
		$this->assertSame('2020-02-02', $result->format('Y-m-d'));

		$object = DateTime::createFromFormat('Y-m-d', '2020-02-02');
		$result = $callable($object, 'format', 'Y-m-d');

		$this->assertIsString($result);
		$this->assertSame('2020-02-02', $result);

		$result = $callable('NonExistentClass', 'method');

		$this->assertNull($result);

		$result = $callable('DateTime', 'nonExistentMethod');

		$this->assertNull($result);
	}


	/**
	 * @return void
	 * @noinspection HtmlUnknownAnchorTarget
	 */
	public function testSurveyFunction(): void {
		$functions = $this->extension->getFunctions();
		$surveyFunction = $functions[25];
		$callable = $surveyFunction->getCallable();

		$output = $callable(
			[
				'page' => $this->fetchTable('Pages')->get(1),
				'_view' => $this->view,
			],
			'dummy_survey'
		);

		$this->assertStringContainsString('<div class="Survey" id="Survey-DummySurvey">', $output);
		$this->assertStringContainsString('<form method="post" action="#Survey-DummySurvey">', $output);
		$this->assertStringContainsString('<p class="Title SurveyQuestion-Title">Question #1</p>', $output);
		$this->assertStringContainsString('<input type="radio" name="survey[dummy_survey][8524de5e]" value="4" id="SurveyAnswer-Input4">', $output);
		$this->assertStringContainsString('<input type="radio" name="survey[dummy_survey][8524de5e]" value="5" id="SurveyAnswer-Input5">', $output);
		$this->assertStringContainsString('<input type="radio" name="survey[dummy_survey][8524de5e]" value="6" id="SurveyAnswer-Input6">', $output);
		$this->assertStringContainsString('<input type="hidden" name="_survey_identifier" value="dummy_survey">', $output);
		$this->assertStringContainsString(
			'<button type="submit" name="survey[dummy_survey][action]" value="proceed" class="Button Survey-NextAction">surveys::next</button>',
			$output
		);
	}


	/**
	 * @return void
	 * @noinspection HtmlRequiredAltAttribute, HtmlUnknownTarget
	 */
	public function testSurveyFunctionWithArguments(): void {
		$functions = $this->extension->getFunctions();
		$surveyFunction = $functions[25];
		$callable = $surveyFunction->getCallable();

		$surveyQuestionsTable = $this->getTableLocator()->get('SurveyQuestions');
		// Activate the third question
		$surveyQuestionsTable->updateAll(['active' => true], ['id' => 3]);

		$postData = [
			'survey' => [
				'dummy_survey' => [
					'action' => 'proceed',
					'8524de5e' => '5', // Answer to question 1
					'f69b1648' => ['7', '8'], // Answers to question 2
				],
			],
			'_survey_identifier' => 'dummy_survey',
		];

		$request = $this->view->getRequest()->withParsedBody($postData);
		$this->view->setRequest($request);

		$output = $callable(
			[
				'page' => $this->fetchTable('Pages')->get(1),
				'_view' => $this->view,
			],
			'dummy_survey',
			[
				'fullWidth' => 1440.00,
				'singleColumnBreakpoint' => 768.00,
				'columnWidth' => 40.00,
			]
		);

		// Deactivate the third question
		$surveyQuestionsTable->updateAll(['active' => false], ['id' => 3]);

		$this->assertStringContainsString('<div class="Text SurveyQuestion-Text"><p>Info text with inline img tag<br><picture>', $output);
		$this->assertStringContainsString('<img data-src="_resized/dummypath/logo-awyiss-[w576].avif"', $output);
		$this->assertStringContainsString('<noscript><img src="_resized/dummypath/logo-awyiss-[w576].avif"', $output);
		$this->assertStringContainsString('</picture></p></div>', $output);
	}


	/**
	 * @return void
	 */
	public function testSurveyFunctionWithoutPage(): void {
		$functions = $this->extension->getFunctions();
		$surveyFunction = $functions[25];
		$callable = $surveyFunction->getCallable();

		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('The "survey" function requires a Page entity in the context.');

		$callable([], 'test_survey');
	}


	/**
	 * @return void
	 */
	public function testWidgetFunctionCallsWidgetFunctionMethod(): void {
		$functions = $this->extension->getFunctions();
		$widgetFunction = $functions[26];
		$callable = $widgetFunction->getCallable();

		$result = $callable(['_view' => $this->view], 'test', ['key' => 'some_value']);

		$this->assertSame('Rendered Output (and key is `some_value`)', $result);
	}


	/**
	 * @return void
	 */
	public function testArrayTest(): void {
		$tests = $this->extension->getTests();
		$arrayTest = $tests[0];
		$callable = $arrayTest->getCallable();

		$this->assertTrue($callable([]));
		$this->assertTrue($callable(['key' => 'value']));
		$this->assertFalse($callable('string'));
		$this->assertFalse($callable(123));
		$this->assertFalse($callable(new stdClass()));
	}


	/**
	 * @return void
	 */
	public function testFileTest(): void {
		$tests = $this->extension->getTests();
		$fileTest = $tests[1];
		$callable = $fileTest->getCallable();

		// Create a temporary file for testing
		$tempFile = WWW_ROOT . 'test_file.txt';
		if (file_exists($tempFile)) {
			unlink($tempFile);
		}
		file_put_contents($tempFile, 'test content');

		$result = $callable('test_file.txt');
		$result2 = $callable('test_file2.txt');

		// Clean up
		unlink($tempFile);

		$this->assertTrue($result);
		$this->assertFalse($result2);
	}


	/**
	 * @return void
	 */
	public function testFileTestWithDirectoryTraversal(): void {
		$tests = $this->extension->getTests();
		$fileTest = $tests[1];
		$callable = $fileTest->getCallable();

		// Create a temporary file for testing
		$tempFile = WWW_ROOT . 'test_file.txt';
		file_put_contents($tempFile, 'test content');

		// Attempt to access a file outside the webroot must fail
		$result = $callable(WWW_ROOT . '../webroot/../test_file.txt');
		// Even though the file exists
		$result2 = file_exists(WWW_ROOT . '../webroot/test_file.txt');

		// Clean up
		unlink($tempFile);

		$this->assertFalse($result);
		$this->assertTrue($result2);
	}


	/**
	 * @return void
	 */
	public function testFileTestWithDirectory(): void {
		$tests = $this->extension->getTests();
		$fileTest = $tests[1];
		$callable = $fileTest->getCallable();

		// Create a temporary directory
		$tempDir = WWW_ROOT . 'test_dir';
		mkdir($tempDir);

		// Attempt to access a directory must fail
		$result = $callable('test_dir');
		// even though the directory exists
		$result2 = file_exists(WWW_ROOT . 'test_dir');

		// Clean up
		rmdir($tempDir);

		$this->assertFalse($result);
		$this->assertTrue($result2);
	}


	/**
	 * @return void
	 */
	public function testInstanceOfTest(): void {
		$tests = $this->extension->getTests();
		$instanceOfTest = $tests[2];
		$callable = $instanceOfTest->getCallable();

		$object = new DummyConfigOptions();
		$this->assertTrue($callable($object, DummyConfigOptions::class));
		$this->assertFalse($callable($object, DateTime::class));
		$this->assertTrue($callable(new DateTime(), DateTime::class));
	}


	/**
	 * @return void
	 */
	public function testNumericTest(): void {
		$tests = $this->extension->getTests();
		$numericTest = $tests[3];
		$callable = $numericTest->getCallable();

		$this->assertTrue($callable(123));
		$this->assertTrue($callable('123'));
		$this->assertTrue($callable(12.34));
		$this->assertTrue($callable('12.34'));
		$this->assertFalse($callable('abc'));
		$this->assertFalse($callable([]));
		$this->assertFalse($callable(new stdClass()));
	}


	/**
	 * @return void
	 */
	public function testPageRoleTest(): void {
		$tests = $this->extension->getTests();
		$pageRoleTest = $tests[4];
		$callable = $pageRoleTest->getCallable();

		$this->assertTrue($callable('news'));
		$this->assertFalse($callable('dummy'));
	}


	/**
	 * @return void
	 */
	public function testStringTest(): void {
		$tests = $this->extension->getTests();
		$stringTest = $tests[5];
		$callable = $stringTest->getCallable();

		$this->assertTrue($callable('hello'));
		$this->assertTrue($callable(''));
		$this->assertFalse($callable(123));
		$this->assertFalse($callable([]));
		$this->assertFalse($callable(new stdClass()));
	}
}
