<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\I18n;


use Awyiss\Awyiss;
use Awyiss\Routing\Router;
use Awyiss\Test\TestSuite\TestCase;
use Cake\Http\ServerRequest;
use Cake\I18n\I18n;


/**
 * MessageFileLoader Test Case
 *
 * @see awyiss/i18n/functions.php
 */
class FunctionsTest extends TestCase {
	/**
	 * @var string
	 */
	protected static string $oldLocale;


	/**
	 * @inheritDoc
	 */
	protected function setUp(): void {
		parent::setUp();

		Awyiss::setRealm(Awyiss::REALM_BACKEND);
		I18n::setLocale('en_ZW');
	}


	/**
	 * @inheritDoc
	 */
	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();

		static::$oldLocale = I18n::getLocale();
	}


	/**
	 * @inheritDoc
	 */
	public static function tearDownAfterClass(): void {
		parent::tearDownAfterClass();

		I18n::setLocale(static::$oldLocale);
	}


	/**
	 * @return void
	 * @see __()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function test__UsesCurrentRealmAndSystemFallbackAsDomain(): void { // phpcs:ignore
		$message = __('dummy_string');
		$this->assertSame('This is a dummy string for testing purposes in en_ZW locale and Backend/system domain.', $message);

		Awyiss::setRealm(Awyiss::REALM_FRONTEND);
		$message = __('dummy_string');
		$this->assertSame('This is a dummy string for testing purposes in en_ZW locale and Frontend/system domain.', $message);
	}


	/**
	 * @return void
	 * @see __()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function test__NotUsesSystemFallbackAsDomainForBlocklistedString(): void { // phpcs:ignore
		$message = __('meta_title_overview');
		$this->assertSame('meta_title_overview', $message);

		$message = __('menu_title');
		$this->assertSame('menu_title', $message);

		$message = __('headline_overview');
		$this->assertSame('headline_overview', $message);
	}


	/**
	 * @return void
	 * @see __()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function test__UsesCurrentRealmAndControllerFromRequestAsDomain(): void { // phpcs:ignore
		$message = __('field_name');
		$this->assertSame('field_name', $message);

		$request = new ServerRequest([
			'params' => [
				'controller' => 'TestDomain',
			],
		]);
		Router::setRequest($request);

		$message = __('field_name');
		$this->assertSame('Testfield', $message);

		$message = __('headline_overview');
		$this->assertSame('This is a test headline for the en_ZW locale in the Backend/test_domain domain.', $message);
	}


	/**
	 * @return void
	 * @see __()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function test__AddsDomainToIdentifierWhenNotFound(): void { // phpcs:ignore
		$request = new ServerRequest([
			'params' => [
				'controller' => 'TestDomain',
			],
		]);
		Router::setRequest($request);

		$message = __('headline_overview');
		$this->assertSame('This is a test headline for the en_ZW locale in the Backend/test_domain domain.', $message);

		$message = __('menu_title');
		$this->assertSame('test_domain::menu_title', $message);
	}


	/**
	 * @return void
	 * @see __d()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function test__dAddsCurrentRealmToDomain(): void { // phpcs:ignore
		$message = __d('TestDomain', 'headline_overview');
		$this->assertSame('This is a test headline for the en_ZW locale in the Backend/test_domain domain.', $message);

		Awyiss::setRealm(Awyiss::REALM_FRONTEND);

		$message = __d('TestDomain', 'headline_overview');
		$this->assertSame('This is a test headline for the en_ZW locale in the Frontend/test_domain domain.', $message);
	}


	/**
	 * @return void
	 * @see __d()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function test__dNotAddsCurrentRealmToDomainWhenSlashInDomain(): void { // phpcs:ignore
		$message = __d('Frontend/TestDomain', 'headline_overview');
		$this->assertSame('This is a test headline for the en_ZW locale in the Frontend/test_domain domain.', $message);

		Awyiss::setRealm(Awyiss::REALM_FRONTEND);

		$message = __d('Backend/TestDomain', 'headline_overview');
		$this->assertSame('This is a test headline for the en_ZW locale in the Backend/test_domain domain.', $message);
	}


	/**
	 * @return void
	 * @see __d()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function test__dNotUsesSystemFallbackAsDomainForBlocklistedString(): void { // phpcs:ignore
		$message = __d('TestDomain', 'meta_title_overview');
		$this->assertSame('test_domain::meta_title_overview', $message);

		$message = __d('TestDomain', 'menu_title');
		$this->assertSame('test_domain::menu_title', $message);

		$message = __d('TestDomain', 'headline_overview');
		$this->assertSame('This is a test headline for the en_ZW locale in the Backend/test_domain domain.', $message);

		$message = __d('System', 'meta_title_overview');
		$this->assertSame('Overview - Awyiss CMS Backend', $message);

		$message = __d('System', 'menu_title');
		$this->assertSame('Menu Title', $message);

		$message = __d('System', 'headline_overview');
		$this->assertSame('Welcome to the Awyiss CMS Backend Overview', $message);
	}


	/**
	 * @return void
	 * @see __df()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function test__dfUsesFallbackDomainWhenNotFound(): void { // phpcs:ignore
		$message = __df('TestDomain', 'system', 'meta_title_overview');
		$this->assertSame('Overview - Awyiss CMS Backend', $message);

		$message = __df('TestDomain', 'system', 'menu_title');
		$this->assertSame('Menu Title', $message);

		$message = __df('TestDomain', 'system', 'headline_overview');
		$this->assertSame('This is a test headline for the en_ZW locale in the Backend/test_domain domain.', $message);

		$message = __df('TestDomain', 'validation', 'error_min_length');
		$this->assertSame('The field must be at least {0} characters long.', $message);
	}


	/**
	 * @return void
	 * @see __df()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function test__dfPrefixedWithMainDomainWhenNotFoundInFallback(): void { // phpcs:ignore
		$message = __df('TestDomain', 'validation', 'unknown_string');
		$this->assertSame('test_domain::unknown_string', $message);
	}


	/**
	 * @return void
	 * @see __df()
	 * @noinspection PhpVariableNamingConventionInspection
	 */
	public function test__dfUnderscoresFallbackDomain(): void { // phpcs:ignore
		$message = __df('system', 'TestDomain', 'field_name');
		$this->assertSame('Testfield', $message);
	}
}
