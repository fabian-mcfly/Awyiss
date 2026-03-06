<?php declare(strict_types=1);


namespace Awyiss\Middleware;


use Awyiss\Awyiss;
use Awyiss\Model\Entity\Language;
use Awyiss\Routing\Router;
use Cake\Core\Configure;
use Cake\Database\TypeFactory;
use Cake\Datasource\FactoryLocator;
use Cake\I18n\DateTime;
use Cake\I18n\I18n;
use Cake\ORM\Locator\LocatorAwareTrait;
use Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;


/**
 * Middleware that handles locale specific logic.
 *
 * It loads languages from the database, sets the locale for I18n
 */
class LocaleMiddleware implements MiddlewareInterface {
	use LocatorAwareTrait;


	/**
	 * Auto-detect language
	 *
	 * @noinspection PhpUnused
	 */
	final public const string SOURCE_AUTO = '__AUTO__';
	/**
	 * Use URL param for language
	 */
	final public const string SOURCE_URL = '__URL__';
	/**
	 * Use Session for language
	 */
	final public const string SOURCE_SESSION = '__SESSION__';


	/**
	 * @var array{frontend: ?Language, backend: ?Language}
	 */
	protected static array $defaultLanguages = [
		Awyiss::REALM_FRONTEND => null,
		Awyiss::REALM_BACKEND => null,
	];
	/**
	 * @var array{frontend: array<string, Language[]>, backend: array<string, Language[]>}
	 */
	protected static array $languages = [
		Awyiss::REALM_FRONTEND => [],
		Awyiss::REALM_BACKEND => [],
	];
	protected static bool $languagesLoaded = false;
	/**
	 * @var array<string, array{frontend: ?Language[], backend: ?Language[]}>
	 */
	protected static array $languagesByShortcode = [];
	/**
	 * @var string $realm The realm the middleware was loaded with
	 */
	protected static string $realm;
	/**
	 * @var array{frontend: string, backend: string} $retrievalStrategy The retrieval strategy for the languages
	 */
	protected static array $retrievalStrategy = [
		Awyiss::REALM_FRONTEND => self::SOURCE_URL,
		Awyiss::REALM_BACKEND => self::SOURCE_SESSION,
	];


	/**
	 * @param ServerRequestInterface $request
	 * @param RequestHandlerInterface $handler
	 * @return ResponseInterface
	 * @throws \Exception
	 */
	public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface {
		static::$realm = Awyiss::getRealm();

		if (!in_array(static::getRealm(), Awyiss::getRealms())) {
			throw new RuntimeException(sprintf('Unknown realm set in `%s`. `%s` given.', static::class, static::getRealm() ?? 'null'));
		}

		static::loadLanguages();

		/** @noinspection PhpRedundantOptionalArgumentInspection */
		$frontendLanguage = static::getLanguage(Awyiss::REALM_FRONTEND);
		$backendLanguage = static::getLanguage(Awyiss::REALM_BACKEND);

		if ($frontendLanguage || $backendLanguage) {
			static::useLanguage($frontendLanguage, $backendLanguage);

			// After detecting the frontend and backend language, reset the languages to fetch them again with the customer language
			static::resetLanguages();
		}

		$request = $request->withAttribute('locale', $this);


		return $handler->handle($request);
	}


	/**
	 * @param \Awyiss\Model\Entity\Language|null $frontendLanguage
	 * @param \Awyiss\Model\Entity\Language|null $backendLanguage
	 * @return void
	 * @throws \Exception
	 */
	public static function useLanguage(?Language $frontendLanguage = null, ?Language $backendLanguage = null): void {
		$mainLanguage = $frontendLanguage;
		if ($backendLanguage && Awyiss::getRealm() === Awyiss::REALM_BACKEND) {
			$mainLanguage = $backendLanguage;
		}

		if (!$mainLanguage) {
			return;
		}

		ini_set('intl.default_locale', $mainLanguage->locale);
		I18n::setLocale($mainLanguage->locale);
		setlocale(LC_ALL, $mainLanguage->locale . '.utf8');

		if ($mainLanguage->dateFormat && $mainLanguage->timeFormat) {
			DateTime::$niceFormat = $mainLanguage->dateFormat . ' ' . $mainLanguage->timeFormat;
		}

		$timezone = Configure::read('Awyiss.System.' . static::getRealm() . '.timezone', 'auto');
		if ($timezone && $timezone !== 'auto') {
			TypeFactory::build('datetime')->setUserTimezone($timezone);
		}
		else {
			TypeFactory::build('datetime')->setUserTimezone($mainLanguage->timezone);
		}

		/** @var \Awyiss\ORM\Locator\TableLocator $tableLocator */
		$tableLocator = FactoryLocator::get('Table');

		// Check all loaded instances of the TableLocator
		// and set the TranslateBehavior's locale
		foreach ($tableLocator->getInstances() as $table) {
			if (!$table->hasBehavior('Translate')) {
				continue;
			}

			$translateBehavior = $table->getBehavior('Translate');

			if (
				$frontendLanguage &&
				$translateBehavior->getConfig('realm') === Awyiss::REALM_FRONTEND
			) {
				$translateBehavior->setLocale($frontendLanguage->shortcode);
			}

			if (
				$backendLanguage &&
				$translateBehavior->getConfig('realm', LocaleMiddleware::getRealm()) === Awyiss::REALM_BACKEND
			) {
				$translateBehavior->setLocale($backendLanguage->shortcode);
			}
		}

		// Add the TranslateBehavior to the LanguagesTable as it's not set on instantiation
		$languagesTable = $tableLocator->get('Languages');
		if (!$languagesTable->hasBehavior('Translate')) {
			$languagesTable->addTranslateBehavior($mainLanguage);
		}
	}


	/**
	 * @param string|null $realm
	 * @param bool $fallback If true, the default language will be returned if no language is found
	 * @return \Awyiss\Model\Entity\Language|null
	 * @throws \Exception
	 */
	public static function getLanguage(?string $realm = Awyiss::REALM_FRONTEND, bool $fallback = true): ?Language {
		static::loadLanguages();

		$realm = $realm ?: static::getRealm();

		if (!isset(static::$retrievalStrategy[ $realm ])) {
			throw new RuntimeException(sprintf('Cannot use auto-detection of the retrievel strategy. No retrievel strategy defined for realm `%s`.', $realm));
		}

		$retrievalStategy = static::$retrievalStrategy[ $realm ];

		$language = null;
		if ($retrievalStategy === static::SOURCE_SESSION) {
			$language = static::getLanguageFromSession($realm);
		}
		elseif ($retrievalStategy === static::SOURCE_URL) {
			$language = static::getLanguageFromUrl($realm);
		}

		return $language ?? ($fallback ? static::getDefaultLanguage($realm ?? static::getRealm()) : null);
	}


	/**
	 * @param string|null $realm
	 * @return array|array<Language>
	 */
	public static function getLanguages(?string $realm = null): array {
		static::loadLanguages();

		if (empty($realm)) {
			return static::$languages;
		}

		return static::$languages[ $realm ] ?? [];
	}


	/**
	 * @param string|null $realm
	 * @return ?Language
	 */
	public static function getDefaultLanguage(?string $realm = null): ?Language {
		static::loadLanguages();

		return static::$defaultLanguages[ $realm ?? static::getRealm() ] ?? null;
	}


	/**
	 * If `$realm` is null, the realm the middleware was loaded with will be used.
	 *
	 * @param string $shortcode
	 * @param string|null $realm
	 * @return ?Language
	 * @noinspection PhpUnused
	 */
	public static function getLanguageByShortcode(string $shortcode, ?string $realm = null): ?Language {
		static::loadLanguages();

		return static::$languages[ $realm ?? static::getRealm() ][ $shortcode ] ?? null;
	}


	/**
	 * @param string|null $shortcode
	 * @return Language|array|null
	 */
	public static function getLanguagesByShortcode(?string $shortcode = null): array|Language|null {
		static::loadLanguages();

		if (empty($shortcode)) {
			return static::$languagesByShortcode;
		}

		return static::$languagesByShortcode[ $shortcode ] ?? null;
	}


	/**
	 * @return string|null
	 */
	public static function getRealm(): ?string {
		return static::$realm;
	}


	/**
	 * @param string $realm
	 * @return void
	 * @noinspection PhpUnused
	 */
	public static function setRealm(string $realm): void {
		static::$realm = $realm;
	}


	/**
	 * @param string|null $realm
	 * @return string
	 */
	public static function getSessionIdentifier(?string $realm = null): string {
		$realm ??= static::getRealm();

		return $realm . '.languageShortcode';
	}


	/**
	 * @return void
	 */
	public static function resetLanguages(): void {
		static::$languagesLoaded = false;

		static::$defaultLanguages = [
			Awyiss::REALM_FRONTEND => null,
			Awyiss::REALM_BACKEND => null,
		];
		static::$languages = [
			Awyiss::REALM_FRONTEND => [],
			Awyiss::REALM_BACKEND => [],
		];
		static::$languagesByShortcode = [];
	}


	/**
	 * @return void
	 */
	protected static function loadLanguages(): void {
		if (static::$languagesLoaded) {
			return;
		}

		$tableLocator = FactoryLocator::get('Table');

		try {
			/** @var \Awyiss\Model\Table\LanguagesTable $languagesTable */
			$languagesTable = $tableLocator->get('Languages');
		}
		catch (Exception $e) {
			// If the table cannot be loaded, we are likely in the middle of a migration where the languages table is not yet created
			// In this case, we just return early and will try to load the languages again on the next request
			return;
		}
		$result = $languagesTable->find('all');

		/** @var Language $language */
		foreach ($result->all() as $language) {
			/** @var Language $language */
			static::$languages[ $language->realm ][ $language->shortcode ] = $language;

			if ($language->active) {
				if (!isset(static::$defaultLanguages[ $language->realm ])) {
					static::$defaultLanguages[ $language->realm ] = $language;
				}

				if (!isset(static::$languagesByShortcode[ $language->shortcode ])) {
					static::$languagesByShortcode[ $language->shortcode ] = [
						Awyiss::REALM_FRONTEND => null,
						Awyiss::REALM_BACKEND => null,
					];
				}
			}

			static::$languagesByShortcode[ $language->shortcode ][ $language->realm ] = $language;
		}

		static::$languagesLoaded = true;
	}


	/**
	 * @throws \Exception
	 * @noinspection PhpUnused
	 */
	protected static function getLanguageFromUrl(?string $realm = null): ?Language {
		static::loadLanguages();

		$realm ??= static::getRealm();

		$langShortcode = Router::getRequest()?->getParam('lang');
		$language = static::getLanguages($realm)[ $langShortcode ] ?? null;

		if ($language && $language->active) {
			return $language;
		}

		return null;
	}


	/**
	 * @param string|null $realm
	 * @param ServerRequestInterface|null $request
	 * @return Language|null
	 */
	protected static function getLanguageFromSession(?string $realm = null, ?ServerRequestInterface $request = null): ?Language {
		static::loadLanguages();

		$realm ??= static::getRealm();

		/** @var \Cake\Http\Session $session */
		$session = ($request ?? Router::getRequest())?->getAttribute('session');

		if (!$session) {
			return null;
		}

		$languageShortcode = $session->read(static::getSessionIdentifier($realm));

		if (!$languageShortcode) {
			return null;
		}

		return static::$languages[ $realm ][ $languageShortcode ] ?? null;
	}
}
