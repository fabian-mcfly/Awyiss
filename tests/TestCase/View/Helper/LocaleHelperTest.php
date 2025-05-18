<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\View\Helper;


use Awyiss\Awyiss;
use Awyiss\Test\TestSuite\TestCase;
use Awyiss\View\BackendView;
use Awyiss\View\Helper\FormHelper;
use Awyiss\View\Helper\LocaleHelper;
use Awyiss\View\HelperRegistry;
use ReflectionClass;


/**
 * LocaleHelperTest class
 */
class LocaleHelperTest extends TestCase {
	/**
	 * @var \Awyiss\View\Helper\LocaleHelper
	 */
	protected LocaleHelper $locale;


	/**
	 * @inheritDoc
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public static function tearDownAfterClass(): void {
		$reflection = new ReflectionClass(BackendView::class);
		$property = $reflection->getProperty('twig');
		/** @noinspection PhpExpressionResultUnusedInspection */
		$property->setAccessible(true);
		$property->setValue(null);

		$property = $reflection->getProperty('twigInitialized');
		/** @noinspection PhpExpressionResultUnusedInspection */
		$property->setAccessible(true);
		$property->setValue(false);
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	protected function setUp(): void {
		parent::setUp();

		$view = $this->getMockBuilder(BackendView::class)->disableOriginalConstructor()->enableAutoReturnValueGeneration()->getMock();

		$view->method('helpers')->willReturn(new HelperRegistry($view));

		$this->formHelper = new FormHelper($view, [
			'autoSetCustomValidity' => false,
			'templates' => 'form_templates_backend',
		]);

		$view->method('loadHelper')->willReturn($this->formHelper);

		$this->locale = new LocaleHelper($view, [
			'templates' => 'paginator_templates',
		]);
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public function testLanguageTitleReturnsCorrectTitle(): void {
		$result = $this->locale->languageTitle('en');

		$this->assertEquals('English', $result);

		$result = $this->locale->languageTitle('de');

		$this->assertEquals('Deutsch', $result);
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public function testLanguageTitleReturnsNullForOmittedShortcode(): void {
		$result = $this->locale->languageTitle();

		$this->assertNull($result);

		/** @noinspection PhpRedundantOptionalArgumentInspection */
		$result = $this->locale->languageTitle(null);

		$this->assertNull($result);
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public function testLanguageTitleReturnsNullForInvalidShortcode(): void {
		$result = $this->locale->languageTitle('ch');

		$this->assertNull($result);
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public function testLanguageTitleReturnsCorrectTitleForProvidedLanguages(): void {
		$languagesByShortcode = [
			'en' => [
				Awyiss::REALM_FRONTEND => (object)['title' => 'English Frontend'],
				Awyiss::REALM_BACKEND => (object)['title' => 'English Backend'],
			],
		];

		$result = $this->locale->languageTitle('en', $languagesByShortcode);

		$this->assertEquals('English Frontend', $result);
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public function testLanguageTitleReturnsCorrectTitleForProvidedLanguagesWithFallbackToBackend(): void {
		$languagesByShortcode = [
			'de' => [
				Awyiss::REALM_BACKEND => (object)['title' => 'Deutsches Backend'],
			],
		];

		$result = $this->locale->languageTitle('de', $languagesByShortcode);

		$this->assertEquals('Deutsches Backend', $result);
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public function testLanguageTitleReturnsNullForInvalidShortcodeForProvidedLanguages(): void {
		$languagesByShortcode = [
			'en' => [
				Awyiss::REALM_FRONTEND => (object)['title' => 'English Frontend'],
			],
		];

		$result = $this->locale->languageTitle('fr', $languagesByShortcode);

		$this->assertNull($result);
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public function testLanguageTitleReturnsNullForProvidedLanguagesWithNoFallback(): void {
		$languagesByShortcode = [
			'de' => [],
		];

		$result = $this->locale->languageTitle('de', $languagesByShortcode);

		$this->assertNull($result);
	}


	/**
	 * @return void
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testControlReturnsSelectInput(): void {
		$result = $this->locale->control('language');

		$this->assertStringContainsString('<select name="language"', $result);
		$this->assertStringContainsString('<option value="de" title="Deutsch">Deutsch</option>', $result);
		$this->assertStringContainsString('<option value="en" title="English">English</option>', $result);
	}


	/**
	 * @return void
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testControlWithDifferentAttributes(): void {
		$result = $this->locale->control('language', ['class' => 'custom-class']);

		$this->assertStringContainsString('<select name="language" class="custom-class"', $result);
		$this->assertStringContainsString('<option value="de" title="Deutsch">Deutsch</option>', $result);
		$this->assertStringContainsString('<option value="en" title="English">English</option>', $result);
	}


	/**
	 * @return void
	 * @throws \Exception
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testControlWithSelectedValue(): void {
		$result = $this->locale->control('language', ['val' => 'en']);

		$this->assertStringContainsString('<select name="language"', $result);
		$this->assertStringContainsString('<option value="de" title="Deutsch">Deutsch</option>', $result);
		$this->assertStringContainsString('<option value="en" title="English" selected="selected">English</option>', $result);
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAllLanguagesReturnsAllLanguages(): void {
		$result = $this->locale->allLanguages();

		$this->assertEquals(['de' => 'Deutsch', 'en' => 'English', 'es' => 'Esperanto'], $result);
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testAllLanguagesWithRawOption(): void {
		$result = $this->locale->allLanguages(true);

		$allLanguages = [];
		$query = $this->fetchTable('Languages')->find('all')->where(['realm !=' => 'Dummy']);
		foreach ($query->all() as $lo_language) {
			if (!isset($allLanguages[ $lo_language->shortcode ])) {
				$allLanguages[ $lo_language->shortcode ] = $lo_language;
			}
		}

		$this->assertEquals($allLanguages, $result);
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public function testLanguagesForRealmReturnsLanguagesForSpecificRealm(): void {
		$result = $this->locale->languagesForRealm(Awyiss::REALM_FRONTEND);

		$this->assertEquals(['de' => 'Deutsch', 'es' => 'Esperanto'], $result);

		$result = $this->locale->languagesForRealm(Awyiss::REALM_BACKEND);

		$this->assertEquals(['en' => 'English', 'de' => 'Deutsch'], $result);
	}


	/**
	 * @return void
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function testLanguagesForRealmWithRawOption(): void {
		$result = $this->locale->languagesForRealm(Awyiss::REALM_FRONTEND, true);

		$query = $this->fetchTable('Languages')->find('all')->where(['realm' => Awyiss::REALM_FRONTEND]);
		$languages = $query->all()->indexBy('shortcode')->toArray();

		$this->assertEquals($languages, $result);
	}
}
