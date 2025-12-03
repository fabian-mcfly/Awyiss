<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Middleware;


use Awyiss\Awyiss;
use Awyiss\Middleware\LocaleMiddleware;
use Awyiss\Model\Entity\Language;
use Awyiss\Routing\Router;
use Awyiss\Test\TestSuite\TestCase;
use Cake\Core\Configure;
use Cake\Database\TypeFactory;
use Cake\Datasource\FactoryLocator;
use Cake\Http\ServerRequest;
use Cake\I18n\DateTime;
use Cake\I18n\I18n;
use Cake\TestSuite\IntegrationTestTrait;


/**
 * LocaleMiddleware Test Case
 *
 * @see \Awyiss\Middleware\LocaleMiddleware
 */
class LocaleMiddlewareTest extends TestCase {
	use IntegrationTestTrait;


	/**
	 * @var string
	 */
	protected static string $oldLocale;


	/**
	 * @inheritDoc
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->configApplication(Awyiss::class, []);
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

		ini_set('intl.default_locale', static::$oldLocale);
		I18n::setLocale(static::$oldLocale);
		setlocale(LC_ALL, static::$oldLocale . '.utf8');
		TypeFactory::build('datetime')->setUserTimezone(null);

		$tableLocator = FactoryLocator::get('Table');
		$tableLocator->clear();
	}


	/**
	 * @return void
	 * @see \Awyiss\Middleware\LocaleMiddleware::process()
	 */
	public function testProcessSetsRealm(): void {
		$this->get('/backend/zu/users/overview/foo:bar/baz:qux');
		$realm = LocaleMiddleware::getRealm();

		$this->assertSame(Awyiss::REALM_BACKEND, $realm);

		$this->get('/zu/users/overview/foo:bar/baz:qux');
		$realm = LocaleMiddleware::getRealm();

		$this->assertSame(Awyiss::REALM_FRONTEND, $realm);
	}


	/**
	 * @return void
	 * @see \Awyiss\Middleware\LocaleMiddleware::process()
	 */
	public function testProcessSetsLocaleAttribute(): void {
		$this->get('/zu/users/overview/foo:bar/baz:qux');
		$request = $this->_controller->getRequest();

		$this->assertInstanceOf(LocaleMiddleware::class, $request->getAttribute('locale'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Middleware\LocaleMiddleware::useLanguage()
	 * @throws \Exception
	 */
	public function testUseLanguageInFrontendRealm(): void {
		Awyiss::setRealm(Awyiss::REALM_FRONTEND);
		LocaleMiddleware::setRealm(Awyiss::REALM_FRONTEND);
		$table = $this->fetchTable('Languages');

		$frontendLanguage = $table->newDefaultEntity();
		$frontendLanguage->shortcode = 'en';
		$frontendLanguage->locale = 'en_US';
		$frontendLanguage->dateFormat = 'Y-m-d';
		$frontendLanguage->timeFormat = 'H:i:s';
		$frontendLanguage->timezone = 'America/Detroit';

		$backendLanguage = $table->newDefaultEntity();
		$backendLanguage->shortcode = 'de';
		$backendLanguage->locale = 'de_DE';
		$backendLanguage->dateFormat = 'd.m.Y';
		$backendLanguage->timeFormat = 'H:i';
		$backendLanguage->timezone = 'Europe/Berlin';

		LocaleMiddleware::useLanguage($frontendLanguage, $backendLanguage);

		$this->assertSame('en_US', ini_get('intl.default_locale'));
		$this->assertSame('en_US', I18n::getLocale());
		$this->assertSame('en_US.utf8', setlocale(LC_ALL, 0));
		$this->assertSame('Y-m-d H:i:s', DateTime::$niceFormat);

		/** @var \Awyiss\Model\Entity\Language $language */
		$language = $table->get(1);
		$table->patchEntity($language, ['createdOn' => '2024-01-01 12:00:00'], ['fields' => ['createdOn']]);
		// Timezone should be America/Detroit, so createdOn, converted to UTC, should be 17:00:00
		$this->assertSame('2024-01-01 17:00:00', $language->createdOn->i18nFormat('yyyy-MM-dd HH:mm:ss'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Middleware\LocaleMiddleware::useLanguage()
	 * @throws \Exception
	 */
	public function testUseLanguageInBackendRealm(): void {
		Awyiss::setRealm(Awyiss::REALM_BACKEND);
		LocaleMiddleware::setRealm(Awyiss::REALM_BACKEND);
		$table = $this->fetchTable('Languages');

		$frontendLanguage = $table->newDefaultEntity();
		$frontendLanguage->shortcode = 'en';
		$frontendLanguage->locale = 'en_US';
		$frontendLanguage->dateFormat = 'Y-m-d';
		$frontendLanguage->timeFormat = 'H:i:s';
		$frontendLanguage->timezone = 'UTC';

		$backendLanguage = $table->newDefaultEntity();
		$backendLanguage->shortcode = 'de';
		$backendLanguage->locale = 'de_DE';
		$backendLanguage->dateFormat = 'd.m.Y';
		$backendLanguage->timeFormat = 'H:i';
		$backendLanguage->timezone = 'Europe/Berlin';

		LocaleMiddleware::useLanguage($frontendLanguage, $backendLanguage);

		$this->assertSame('de_DE', ini_get('intl.default_locale'));
		$this->assertSame('de_DE', I18n::getLocale());
		$this->assertSame('de_DE.utf8', setlocale(LC_ALL, 0));
		$this->assertSame('d.m.Y H:i', DateTime::$niceFormat);

		/** @var \Awyiss\Model\Entity\Language $language */
		$language = $table->get(1);
		$table->patchEntity($language, ['createdOn' => '2024-01-01 12:00:00'], ['fields' => ['createdOn']]);
		// Timezone should be Europe/Berlin, so createdOn, converted to UTC, should be 11:00
		$this->assertSame('2024-01-01 11:00:00', $language->createdOn->i18nFormat('yyyy-MM-dd HH:mm:ss'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Middleware\LocaleMiddleware::useLanguage()
	 * @throws \Exception
	 */
	public function testUseLanguageUsesRealmTimezoneFromConfig(): void {
		Awyiss::setRealm(Awyiss::REALM_BACKEND);
		LocaleMiddleware::setRealm(Awyiss::REALM_BACKEND);
		$table = $this->fetchTable('Languages');

		Configure::write('Awyiss.System.' . Awyiss::REALM_BACKEND . '.timezone', 'Asia/Tokyo');

		$backendLanguage = $table->newDefaultEntity();
		$backendLanguage->shortcode = 'de';
		$backendLanguage->locale = 'de_DE';
		$backendLanguage->dateFormat = 'd.m.Y';
		$backendLanguage->timeFormat = 'H:i';
		$backendLanguage->timezone = 'Europe/Berlin';

		LocaleMiddleware::useLanguage(null, $backendLanguage);

		$this->assertSame('de_DE', ini_get('intl.default_locale'));
		$this->assertSame('de_DE', I18n::getLocale());
		$this->assertSame('de_DE.utf8', setlocale(LC_ALL, 0));
		$this->assertSame('d.m.Y H:i', DateTime::$niceFormat);

		/** @var \Awyiss\Model\Entity\Language $language */
		$language = $table->get(1);
		$table->patchEntity($language, ['createdOn' => '2024-01-01 12:00:00'], ['fields' => ['createdOn']]);
		// Timezone should be Asia/Tokyo, so createdOn, converted to UTC, should be 03:00
		$this->assertSame('2024-01-01 03:00:00', $language->createdOn->i18nFormat('yyyy-MM-dd HH:mm:ss'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Middleware\LocaleMiddleware::useLanguage()
	 * @throws \Exception
	 * @noinspection PhpPossiblePolymorphicInvocationInspection
	 */
	public function testUseLanguageSetsLocaleInTranslateBehavior(): void {
		Awyiss::setRealm(Awyiss::REALM_BACKEND);
		LocaleMiddleware::setRealm(Awyiss::REALM_BACKEND);

		$tableLocator = $this->getTableLocator();
		$tableLocator->clear();

		$table = $this->fetchTable('Languages');

		$frontendLanguage = $table->newDefaultEntity();
		$frontendLanguage->shortcode = 'en';
		$frontendLanguage->locale = 'en_US';
		$frontendLanguage->dateFormat = 'Y-m-d';
		$frontendLanguage->timeFormat = 'H:i:s';
		$frontendLanguage->timezone = 'America/Detroit';

		$backendLanguage = $table->newDefaultEntity();
		$backendLanguage->shortcode = 'ru';
		$backendLanguage->locale = 'ru_RU';
		$backendLanguage->dateFormat = 'd.m.Y';
		$backendLanguage->timeFormat = 'H:i';
		$backendLanguage->timezone = 'Europe/Moscow';

		/** @var \Awyiss\Model\Table\ContentTemplatesTable $contentTemplates */
		$contentTemplatesTable = $tableLocator->get('ContentTemplates');
		/** @var \Awyiss\Model\Table\FormsTable $formsTable */
		$formsTable = $tableLocator->get('Forms');
		/** @var \Awyiss\Model\Table\MediaTable $mediaTable */
		$mediaTable = $tableLocator->get('Media');
		/** @var \Awyiss\Model\Table\PageTemplatesTable $pageTemplates */
		$pageTemplatesTable = $tableLocator->get('PageTemplates');

		$contentTemplatesTableTranslateBehavior = $contentTemplatesTable->getBehavior('Translate');
		$formsTableTranslateBehavior = $formsTable->getBehavior('Translate');
		$mediaTableTranslateBehavior = $mediaTable->getBehavior('Translate');
		$pageTemplatesTableTranslateBehavior = $pageTemplatesTable->getBehavior('Translate');

		// Should be 'de' by default
		$this->assertSame('de', $contentTemplatesTableTranslateBehavior->getLocale());
		$this->assertSame('de', $formsTableTranslateBehavior->getLocale());
		$this->assertSame('de', $mediaTableTranslateBehavior->getLocale());
		$this->assertSame('de', $pageTemplatesTableTranslateBehavior->getLocale());

		LocaleMiddleware::useLanguage($frontendLanguage, $backendLanguage);

		// Should be 'en' after using the language
		$this->assertSame('en', $formsTableTranslateBehavior->getLocale());
		$this->assertSame('en', $mediaTableTranslateBehavior->getLocale());

		// ContentTemplates and PageTemplates should still have 'de' as locale, because the realm is frontend
		$this->assertSame('ru', $contentTemplatesTableTranslateBehavior->getLocale());
		$this->assertSame('ru', $pageTemplatesTableTranslateBehavior->getLocale());
	}


	/**
	 * @return void
	 * @see \Awyiss\Middleware\LocaleMiddleware::useLanguage()
	 * @throws \Exception
	 * @noinspection PhpPossiblePolymorphicInvocationInspection
	 */
	public function testUseLanguageSetsLocaleInTranslateBehaviorForFrontendRealm(): void {
		Awyiss::setRealm(Awyiss::REALM_FRONTEND);
		LocaleMiddleware::setRealm(Awyiss::REALM_FRONTEND);

		$tableLocator = $this->getTableLocator();
		$tableLocator->clear();

		$table = $this->fetchTable('Languages');

		$frontendLanguage = $table->newDefaultEntity();
		$frontendLanguage->shortcode = 'en';
		$frontendLanguage->locale = 'en_US';
		$frontendLanguage->dateFormat = 'Y-m-d';
		$frontendLanguage->timeFormat = 'H:i:s';
		$frontendLanguage->timezone = 'America/Detroit';

		$backendLanguage = $table->newDefaultEntity();
		$backendLanguage->shortcode = 'ru';
		$backendLanguage->locale = 'ru_RU';
		$backendLanguage->dateFormat = 'd.m.Y';
		$backendLanguage->timeFormat = 'H:i';
		$backendLanguage->timezone = 'Europe/Moscow';

		/** @var \Awyiss\Model\Table\ContentTemplatesTable $contentTemplates */
		$contentTemplatesTable = $tableLocator->get('ContentTemplates');
		/** @var \Awyiss\Model\Table\FormsTable $formsTable */
		$formsTable = $tableLocator->get('Forms');
		/** @var \Awyiss\Model\Table\MediaTable $mediaTable */
		$mediaTable = $tableLocator->get('Media');
		/** @var \Awyiss\Model\Table\PageTemplatesTable $pageTemplates */
		$pageTemplatesTable = $tableLocator->get('PageTemplates');

		$contentTemplatesTableTranslateBehavior = $contentTemplatesTable->getBehavior('Translate');
		$formsTableTranslateBehavior = $formsTable->getBehavior('Translate');
		$mediaTableTranslateBehavior = $mediaTable->getBehavior('Translate');
		$pageTemplatesTableTranslateBehavior = $pageTemplatesTable->getBehavior('Translate');

		// Should be 'de' by default
		$this->assertSame('de', $contentTemplatesTableTranslateBehavior->getLocale());
		$this->assertSame('de', $formsTableTranslateBehavior->getLocale());
		$this->assertSame('de', $mediaTableTranslateBehavior->getLocale());
		$this->assertSame('de', $pageTemplatesTableTranslateBehavior->getLocale());

		LocaleMiddleware::useLanguage($frontendLanguage, $backendLanguage);

		// Should be 'en' after using the language
		$this->assertSame('en', $formsTableTranslateBehavior->getLocale());
		$this->assertSame('en', $mediaTableTranslateBehavior->getLocale());

		// ContentTemplates and PageTemplates should still have 'de' as locale, because the realm is frontend
		$this->assertSame('de', $contentTemplatesTableTranslateBehavior->getLocale());
		$this->assertSame('de', $pageTemplatesTableTranslateBehavior->getLocale());
	}


	/**
	 * @return void
	 * @see \Awyiss\Middleware\LocaleMiddleware::useLanguage()
	 * @throws \Exception
	 */
	public function testUseLanguageAddsTranslateBehaviorToLanguagesTable(): void {
		Awyiss::setRealm(Awyiss::REALM_FRONTEND);
		LocaleMiddleware::setRealm(Awyiss::REALM_FRONTEND);

		$table = $this->fetchTable('Languages');

		$frontendLanguage = $table->newDefaultEntity();
		$frontendLanguage->shortcode = 'en';
		$frontendLanguage->locale = 'en_US';
		$frontendLanguage->dateFormat = 'Y-m-d';
		$frontendLanguage->timeFormat = 'H:i:s';
		$frontendLanguage->timezone = 'America/Detroit';

		$tableLocator = $this->getTableLocator();
		$tableLocator->clear();

		$table = $this->fetchTable('Languages');
		$this->assertFalse($table->hasBehavior('Translate'));

		LocaleMiddleware::useLanguage($frontendLanguage);

		$this->assertTrue($table->hasBehavior('Translate'));
	}


	/**
	 * @return void
	 * @see \Awyiss\Middleware\LocaleMiddleware::getLanguage()
	 * @throws \Exception
	 */
	public function testGetLanguageForFrontendRealm(): void {
		$this->loadRoutes();

		Awyiss::setRealm(Awyiss::REALM_FRONTEND);
		LocaleMiddleware::setRealm(Awyiss::REALM_FRONTEND);

		$request = new ServerRequest([
			'url' => '/zu/users/overview/foo:bar/baz:qux',
			'params' => [
				'controller' => 'Users',
				'action' => 'overview',
				'lang' => 'zu',
			],
		]);

		$sessionIdentifier = LocaleMiddleware::getSessionIdentifier(Awyiss::REALM_BACKEND);
		$request->getSession()->write($sessionIdentifier, 'zu');

		Router::setRequest($request);

		$language = LocaleMiddleware::getLanguage();

		$this->assertInstanceOf(Language::class, $language);
		$this->assertSame(102, $language->id);
		$this->assertSame('zu', $language->shortcode);

		/** @noinspection PhpRedundantOptionalArgumentInspection */
		$language = LocaleMiddleware::getLanguage(Awyiss::REALM_FRONTEND);

		$this->assertInstanceOf(Language::class, $language);
		$this->assertSame(102, $language->id);
		$this->assertSame('zu', $language->shortcode);
	}


	/**
	 * @return void
	 * @see \Awyiss\Middleware\LocaleMiddleware::getLanguage()
	 * @throws \Exception
	 */
	public function testGetLanguageForFrontendRealmFallback(): void {
		$this->loadRoutes();

		Awyiss::setRealm(Awyiss::REALM_FRONTEND);
		LocaleMiddleware::setRealm(Awyiss::REALM_FRONTEND);

		$request = new ServerRequest([
			'url' => '/zu/users/overview/foo:bar/baz:qux',
			'params' => [
				'controller' => 'Users',
				'action' => 'overview',
				'lang' => 'abcdef',
			],
		]);

		$sessionIdentifier = LocaleMiddleware::getSessionIdentifier(Awyiss::REALM_BACKEND);
		$request->getSession()->write($sessionIdentifier, 'zu');

		Router::setRequest($request);

		/** @noinspection PhpRedundantOptionalArgumentInspection */
		$language = LocaleMiddleware::getLanguage(Awyiss::REALM_FRONTEND, true);

		$this->assertInstanceOf(Language::class, $language);
		$this->assertSame(1, $language->id);
		$this->assertSame('de', $language->shortcode);

		$language = LocaleMiddleware::getLanguage(Awyiss::REALM_FRONTEND, false);

		$this->assertNull($language);
	}


	/**
	 * @return void
	 * @see \Awyiss\Middleware\LocaleMiddleware::getLanguage()
	 * @throws \Exception
	 */
	public function testGetLanguageForBackendRealm(): void {
		$this->loadRoutes();

		Awyiss::setRealm(Awyiss::REALM_BACKEND);
		LocaleMiddleware::setRealm(Awyiss::REALM_BACKEND);

		$request = new ServerRequest([
			'url' => '/backend/zu/users/overview/foo:bar/baz:qux',
			'params' => [
				'controller' => 'Users',
				'action' => 'overview',
				'lang' => 'zu',
			],
		]);

		$sessionIdentifier = LocaleMiddleware::getSessionIdentifier(Awyiss::REALM_BACKEND);
		$request->getSession()->write($sessionIdentifier, 'en');

		Router::setRequest($request);

		$language = LocaleMiddleware::getLanguage(Awyiss::REALM_BACKEND);

		$this->assertInstanceOf(Language::class, $language);
		$this->assertSame(3, $language->id);
		$this->assertSame('en', $language->shortcode);
	}


	/**
	 * @return void
	 * @see \Awyiss\Middleware\LocaleMiddleware::getLanguage()
	 * @throws \Exception
	 */
	public function testGetLanguageForBackendRealmFallback(): void {
		$this->loadRoutes();

		Awyiss::setRealm(Awyiss::REALM_BACKEND);
		LocaleMiddleware::setRealm(Awyiss::REALM_BACKEND);

		$request = new ServerRequest([
			'url' => '/backend/zu/users/overview/foo:bar/baz:qux',
			'params' => [
				'controller' => 'Users',
				'action' => 'overview',
				'lang' => 'zu',
			],
		]);

		$sessionIdentifier = LocaleMiddleware::getSessionIdentifier(Awyiss::REALM_BACKEND);
		$request->getSession()->write($sessionIdentifier, 'abcdef');

		Router::setRequest($request);

		/** @noinspection PhpRedundantOptionalArgumentInspection */
		$language = LocaleMiddleware::getLanguage(Awyiss::REALM_BACKEND, true);

		$this->assertInstanceOf(Language::class, $language);
		$this->assertSame(2, $language->id);
		$this->assertSame('de', $language->shortcode);

		$language = LocaleMiddleware::getLanguage(Awyiss::REALM_BACKEND, false);

		$this->assertNull($language);
	}


	/**
	 * @return void
	 * @see \Awyiss\Middleware\LocaleMiddleware::getLanguages()
	 */
	public function testGetLanguages(): void {
		$languages = LocaleMiddleware::getLanguages();
		$this->assertSame([
			'Frontend',
			'Backend',
			'Dummy',
		], array_keys($languages));

		foreach ($languages as $realm => $realmLanguages) {
			$this->assertIsArray($realmLanguages);

			foreach ($realmLanguages as $language) {
				$this->assertInstanceOf(Language::class, $language);
				$this->assertSame($realm, $language->realm);
			}
		}
	}


	/**
	 * @return void
	 * @see \Awyiss\Middleware\LocaleMiddleware::getLanguages()
	 */
	public function testGetLanguagesWithRealm(): void {
		$languages = LocaleMiddleware::getLanguages(Awyiss::REALM_FRONTEND);

		$this->assertSame([
			'de',
			'zu',
			'es',
		], array_keys($languages));

		foreach ($languages as $language) {
			$this->assertInstanceOf(Language::class, $language);
			$this->assertSame(Awyiss::REALM_FRONTEND, $language->realm);
		}

		$languages = LocaleMiddleware::getLanguages(Awyiss::REALM_BACKEND);

		$this->assertSame([
			'de',
			'en',
		], array_keys($languages));

		foreach ($languages as $language) {
			$this->assertInstanceOf(Language::class, $language);
			$this->assertSame(Awyiss::REALM_BACKEND, $language->realm);
		}
	}


	/**
	 * @return void
	 * @see \Awyiss\Middleware\LocaleMiddleware::getDefaultLanguage()
	 */
	public function testGetDefaultLanguage(): void {
		$language = LocaleMiddleware::getDefaultLanguage(Awyiss::REALM_FRONTEND);
		$this->assertInstanceOf(Language::class, $language);
		$this->assertSame(1, $language->id);
		$this->assertSame('de', $language->shortcode);

		$language = LocaleMiddleware::getDefaultLanguage(Awyiss::REALM_BACKEND);
		$this->assertInstanceOf(Language::class, $language);
		$this->assertSame(2, $language->id);
		$this->assertSame('de', $language->shortcode);
	}


	/**
	 * @return void
	 * @see \Awyiss\Middleware\LocaleMiddleware::getDefaultLanguage()
	 */
	public function testGetDefaultLanguageIgnoresInactiveLanguages(): void {
		$table = $this->fetchTable('Languages');
		$table->updateAll(['active' => 0], ['shortcode' => 'de']);

		LocaleMiddleware::resetLanguages();

		$defaultFrontendLanguage = LocaleMiddleware::getDefaultLanguage(Awyiss::REALM_FRONTEND);
		$defaultBackendLanguage = LocaleMiddleware::getDefaultLanguage(Awyiss::REALM_BACKEND);

		// Restore the active status
		$table->updateAll(['active' => 1], ['shortcode' => 'de']);

		LocaleMiddleware::resetLanguages();

		$this->assertInstanceOf(Language::class, $defaultFrontendLanguage);
		$this->assertSame(102, $defaultFrontendLanguage->id);
		$this->assertSame('zu', $defaultFrontendLanguage->shortcode);

		$this->assertInstanceOf(Language::class, $defaultBackendLanguage);
		$this->assertSame(3, $defaultBackendLanguage->id);
		$this->assertSame('en', $defaultBackendLanguage->shortcode);
	}


	/**
	 * @return void
	 * @see \Awyiss\Middleware\LocaleMiddleware::getRealm()
	 * @see \Awyiss\Middleware\LocaleMiddleware::setRealm()
	 */
	public function testGetRealmSetRealm(): void {
		LocaleMiddleware::setRealm(Awyiss::REALM_FRONTEND);
		$this->assertSame(Awyiss::REALM_FRONTEND, LocaleMiddleware::getRealm());

		LocaleMiddleware::setRealm(Awyiss::REALM_BACKEND);
		$this->assertSame(Awyiss::REALM_BACKEND, LocaleMiddleware::getRealm());

		LocaleMiddleware::setRealm('Dummy');
		$this->assertSame('Dummy', LocaleMiddleware::getRealm());
	}


	/**
	 * @return void
	 * @see \Awyiss\Middleware\LocaleMiddleware::getSessionIdentifier()
	 */
	public function testGetSessionIdentifier(): void {
		$identifier = LocaleMiddleware::getSessionIdentifier(Awyiss::REALM_FRONTEND);
		$this->assertSame(Awyiss::REALM_FRONTEND . '.languageShortcode', $identifier);

		$identifier = LocaleMiddleware::getSessionIdentifier(Awyiss::REALM_BACKEND);
		$this->assertSame(Awyiss::REALM_BACKEND . '.languageShortcode', $identifier);

		$identifier = LocaleMiddleware::getSessionIdentifier('Dummy');
		$this->assertSame('Dummy.languageShortcode', $identifier);
	}


	/**
	 * @return void
	 * @see \Awyiss\Middleware\LocaleMiddleware::getLanguageByShortcode()
	 */
	public function testGetLanguageByShortcode(): void {
		LocaleMiddleware::setRealm(Awyiss::REALM_FRONTEND);

		$language = LocaleMiddleware::getLanguageByShortcode('en');
		$this->assertNull($language);

		$language = LocaleMiddleware::getLanguageByShortcode('zu');
		$this->assertInstanceOf(Language::class, $language);
		$this->assertSame(102, $language->id);
		$this->assertSame('zu', $language->shortcode);

		$language = LocaleMiddleware::getLanguageByShortcode('en', Awyiss::REALM_BACKEND);
		$this->assertInstanceOf(Language::class, $language);
		$this->assertSame(3, $language->id);
		$this->assertSame('en', $language->shortcode);

		$language = LocaleMiddleware::getLanguageByShortcode('zu', Awyiss::REALM_BACKEND);
		$this->assertNull($language);

		LocaleMiddleware::setRealm(Awyiss::REALM_BACKEND);

		$language = LocaleMiddleware::getLanguageByShortcode('en', Awyiss::REALM_FRONTEND);
		$this->assertNull($language);

		$language = LocaleMiddleware::getLanguageByShortcode('zu', Awyiss::REALM_FRONTEND);
		$this->assertInstanceOf(Language::class, $language);
		$this->assertSame(102, $language->id);
		$this->assertSame('zu', $language->shortcode);

		$language = LocaleMiddleware::getLanguageByShortcode('en');
		$this->assertInstanceOf(Language::class, $language);
		$this->assertSame(3, $language->id);
		$this->assertSame('en', $language->shortcode);

		$language = LocaleMiddleware::getLanguageByShortcode('zu');
		$this->assertNull($language);
	}


	/**
	 * @return void
	 * @see \Awyiss\Middleware\LocaleMiddleware::getLanguagesByShortcode()
	 */
	public function testGetLanguagesByShortcode(): void {
		$languages = LocaleMiddleware::getLanguagesByShortcode('en');
		$this->assertCount(2, $languages);

		$this->assertNull($languages[ Awyiss::REALM_FRONTEND ]);
		$this->assertInstanceOf(Language::class, $languages[ Awyiss::REALM_BACKEND ]);
		$this->assertSame(3, $languages[ Awyiss::REALM_BACKEND ]->id);
		$this->assertSame('en', $languages[ Awyiss::REALM_BACKEND ]->shortcode);

		$languages = LocaleMiddleware::getLanguagesByShortcode('zu');
		$this->assertCount(2, $languages);
		$this->assertInstanceOf(Language::class, $languages[ Awyiss::REALM_FRONTEND ]);
		$this->assertSame(102, $languages[ Awyiss::REALM_FRONTEND ]->id);
		$this->assertSame('zu', $languages[ Awyiss::REALM_FRONTEND ]->shortcode);
		$this->assertNull($languages[ Awyiss::REALM_BACKEND ]);

		$languages = LocaleMiddleware::getLanguagesByShortcode('de');
		$this->assertCount(2, $languages);
		$this->assertInstanceOf(Language::class, $languages[ Awyiss::REALM_FRONTEND ]);
		$this->assertSame(1, $languages[ Awyiss::REALM_FRONTEND ]->id);
		$this->assertSame('de', $languages[ Awyiss::REALM_FRONTEND ]->shortcode);
		$this->assertInstanceOf(Language::class, $languages[ Awyiss::REALM_BACKEND ]);
		$this->assertSame(2, $languages[ Awyiss::REALM_BACKEND ]->id);
		$this->assertSame('de', $languages[ Awyiss::REALM_BACKEND ]->shortcode);
	}
}
