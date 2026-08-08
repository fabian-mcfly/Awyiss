<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\View\Helper;


use Awyiss\Awyiss;
use Awyiss\Middleware\LocaleMiddleware;
use Awyiss\Test\TestSuite\TestCase;
use Awyiss\View\BackendView;
use Awyiss\View\Helper\FormHelper;
use Awyiss\View\Helper\LocaleHelper;
use Awyiss\View\HelperRegistry;


/**
 * LocaleHelperTest class
 */
class LocaleHelperTest extends TestCase {
	/**
	 * @var \Awyiss\View\Helper\FormHelper
	 */
	protected FormHelper $formHelper;
	/**
	 * @var \Awyiss\View\Helper\LocaleHelper
	 */
	protected LocaleHelper $locale;


	/**
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$view = $this->getStubBuilder(BackendView::class)->disableOriginalConstructor()->enableAutoReturnValueGeneration()->getStub();

		$view->method('helpers')->willReturn(new HelperRegistry($view));

		$this->formHelper = new FormHelper($view, [
			'autoSetCustomValidity' => false,
			'templates' => 'form_templates_backend',
		]);

		$view->method('loadHelper')->willReturn($this->formHelper);

		$this->locale = new LocaleHelper($view, [
			'templates' => 'paginator_templates',
		]);

		LocaleMiddleware::resetLanguages();
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\LocaleHelper::languageTitle()
	 */
	public function testLanguageTitleReturnsCorrectTitle(): void {
		$result = $this->locale->languageTitle('en');

		$this->assertEquals('English', $result);

		$result = $this->locale->languageTitle('de');

		$this->assertEquals('Deutsch', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\LocaleHelper::languageTitle()
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
	 * @see \Awyiss\View\Helper\LocaleHelper::languageTitle()
	 */
	public function testLanguageTitleReturnsNullForInvalidShortcode(): void {
		$result = $this->locale->languageTitle('ch');

		$this->assertNull($result);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\LocaleHelper::languageTitle()
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
	 * @see \Awyiss\View\Helper\LocaleHelper::languageTitle()
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
	 * @see \Awyiss\View\Helper\LocaleHelper::languageTitle()
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
	 * @see \Awyiss\View\Helper\LocaleHelper::languageTitle()
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
	 * @see \Awyiss\View\Helper\LocaleHelper::control()
	 * @throws \Exception
	 */
	public function testControlReturnsSelectInput(): void {
		$result = $this->locale->control('language');

		$this->assertStringContainsString('<select name="language"', $result);
		$this->assertStringContainsString('<option value="de" title="Deutsch">Deutsch</option>', $result);
		$this->assertStringContainsString('<option value="en" title="English">English</option>', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\LocaleHelper::control()
	 * @throws \Exception
	 */
	public function testControlWithDifferentAttributes(): void {
		$result = $this->locale->control('language', ['class' => 'custom-class']);

		$this->assertStringContainsString('<select name="language" class="custom-class"', $result);
		$this->assertStringContainsString('<option value="de" title="Deutsch">Deutsch</option>', $result);
		$this->assertStringContainsString('<option value="en" title="English">English</option>', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\LocaleHelper::control()
	 * @throws \Exception
	 */
	public function testControlWithSelectedValue(): void {
		$result = $this->locale->control('language', ['val' => 'en']);

		$this->assertStringContainsString('<select name="language"', $result);
		$this->assertStringContainsString('<option value="de" title="Deutsch">Deutsch</option>', $result);
		$this->assertStringContainsString('<option value="en" title="English" selected="selected">English</option>', $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\LocaleHelper::allLanguages()
	 */
	public function testAllLanguagesReturnsAllLanguages(): void {
		$result = $this->locale->allLanguages();

		$this->assertEquals(['de' => 'Deutsch', 'en' => 'English', 'es' => 'Esperanto', 'zu' => 'Klingon'], $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\LocaleHelper::allLanguages()
	 */
	public function testAllLanguagesWithRawOption(): void {
		$result = $this->locale->allLanguages(true);

		$allLanguages = [];
		$query = $this->fetchTable('Languages')->find('all')->where(['realm !=' => 'Dummy']);
		foreach ($query->all() as $language) {
			if (!isset($allLanguages[ $language->shortcode ])) {
				$allLanguages[ $language->shortcode ] = $language;
			}
		}

		$this->assertEquals($allLanguages, $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\LocaleHelper::languagesForRealm()
	 */
	public function testLanguagesForRealmReturnsLanguagesForSpecificRealm(): void {
		$result = $this->locale->languagesForRealm(Awyiss::REALM_FRONTEND);

		$this->assertEquals(['de' => 'Deutsch', 'es' => 'Esperanto', 'zu' => 'Klingon'], $result);

		$result = $this->locale->languagesForRealm(Awyiss::REALM_BACKEND);

		$this->assertEquals(['en' => 'English', 'de' => 'Deutsch'], $result);
	}


	/**
	 * @return void
	 * @see \Awyiss\View\Helper\LocaleHelper::languagesForRealm()
	 */
	public function testLanguagesForRealmWithRawOption(): void {
		$result = $this->locale->languagesForRealm(Awyiss::REALM_FRONTEND, true);

		$query = $this->fetchTable('Languages')->find('all')->where(['realm' => Awyiss::REALM_FRONTEND]);
		$languages = $query->all()->indexBy('shortcode')->toArray();

		$this->assertEquals($languages, $result);
	}
}
