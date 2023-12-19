<?php declare(strict_types=1);


namespace Awyiss\Middleware;


use Awyiss\Awyiss;
use Awyiss\I18n\MessagesFileLoader;
use Awyiss\Model\Entity\Language;
use Awyiss\Routing\Router;
use Cake\Database\TypeFactory;
use Cake\Datasource\FactoryLocator;
use Cake\Http\Session;
use Cake\I18n\I18n;
use Cake\I18n\Package;
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
	 * @var array{frontend: ?Language, backend: ?Language}
	 */
	protected static array $defaultLanguages = [
		Awyiss::REALM_FRONTEND => NULL,
		Awyiss::REALM_BACKEND => NULL,
	];
	/**
	 * @var array{frontend: array<string, Language[]>, backend: array<string, Language[]>}
	 */
	protected static array $languages = [
		Awyiss::REALM_FRONTEND => [],
		Awyiss::REALM_BACKEND => [],
	];
	protected static bool $languagesLoaded = FALSE;
	/**
	 * @var array<string, array{frontend: ?Language[], backend: ?Language[]}>
	 */
	protected static array $languagesByShortcode = [];
	protected static ?string $realm = NULL;
	protected static array $retrievalStrategy = [
		Awyiss::REALM_FRONTEND => self::SOURCE_URL,
		Awyiss::REALM_BACKEND => self::SOURCE_SESSION,
	];


	/**
	 * Auto-detect language
	 */
	final public const SOURCE_AUTO = '__AUTO__';
	/**
	 * Use URL param for language
	 */
	final public const SOURCE_URL = '__URL__';
	/**
	 * Use Session for language
	 */
	final public const SOURCE_SESSION = '__SESSION__';


	/**
	 * @param null|string $as_realm
	 */
	public function __construct (?string $as_realm = NULL) {
		static::$realm = $as_realm;
	}


	/**
	 * @param ServerRequestInterface $ao_request
	 * @param RequestHandlerInterface $ao_handler
	 *
	 * @return ResponseInterface
	 *
	 * @throws Exception
	 *
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function process (ServerRequestInterface $ao_request, RequestHandlerInterface $ao_handler): ResponseInterface {
		if ( ! in_array(static::$realm, Awyiss::getRealms())) {
			throw new RuntimeException(sprintf('Unknown realm set in `%s`. `%s` given.', static::class, static::$realm ?? 'NULL'));
		}

		static::loadLanguages();

		I18n::config('_fallback', function($as_domain, $as_locale) {
			$ls_domain = $as_domain;
			if ( ! str_contains($ls_domain, '/')) {
				$ls_domain = static::getRealm() . DS . $ls_domain;
			}

			$lo_fileLoader = new MessagesFileLoader($ls_domain, $as_locale, 'po');
			$lo_default = $lo_fileLoader();

			return new Package('default', NULL, $lo_default->getMessages());
		});

		$lo_language = NULL;
		if (static::$retrievalStrategy[ static::getRealm() ] === self::SOURCE_URL) {
			$lo_language = static::getLanguageFromUrl(static::getRealm());
		}
		elseif (static::$retrievalStrategy[ static::getRealm() ] === self::SOURCE_SESSION) {
			$lo_language = static::getLanguageFromSession(static::getRealm());
		}

		if ($lo_language) {
			ini_set('intl.default_locale', $lo_language->locale);
			I18n::setLocale($lo_language->locale);
			TypeFactory::build('datetime')->setUserTimezone($lo_language->timezone);

			$lo_tableLocator = FactoryLocator::get('Table');
			/** @noinspection PhpPossiblePolymorphicInvocationInspection */
			$lo_tableLocator->setTranslateLanguage($lo_language);
		}

		$lo_request = $ao_request->withAttribute('locale', $this);

		return $ao_handler->handle($lo_request);
	}


	/**
	 * @param string|null $as_realm
	 * @param bool $ab_fallback
	 * @param string $as_retrievalStategy
	 *
	 * @return Language|null
	 * @throws Exception
	 */
	public static function getLanguage (?string $as_realm = Awyiss::REALM_FRONTEND, bool $ab_fallback = TRUE, string $as_retrievalStategy = self::SOURCE_AUTO): ?Language {
		if ( ! static::$languagesLoaded) {
			static::loadLanguages();
		}

		$ls_realm = $as_realm;
		if ( ! $ls_realm) {
			$ls_realm = static::$realm;
		}

		$ls_retrievalStategy = $as_retrievalStategy;
		if ($ls_retrievalStategy === static::SOURCE_AUTO) {
			if ( ! isset(static::$retrievalStrategy[ $ls_realm ])) {
				throw new RuntimeException(sprintf('Cannot use auto-detection of the retrievel strategy. No retrievel strategy defined for realm `%s`.', $ls_realm));
			}

			$ls_retrievalStategy = static::$retrievalStrategy[ $ls_realm ];
		}

		if ($ls_retrievalStategy === static::SOURCE_SESSION) {
			$lo_language = static::getLanguageFromSession($ls_realm);
		}
		elseif ($ls_retrievalStategy === static::SOURCE_URL) {
			$lo_language = static::getLanguageFromUrl($ls_realm);
		}

		return $lo_language ?? ($ab_fallback ? static::getDefaultLanguage($ls_realm ?? static::$realm) : NULL);
	}


	/**
	 * @param NULL|string $as_realm
	 *
	 * @return array|Language[]
	 */
	public static function getLanguages (?string $as_realm = NULL): array {
		if ( ! static::$languagesLoaded) {
			static::loadLanguages();
		}

		if (empty($as_realm)) {
			return static::$languages;
		}

		return static::$languages[ $as_realm ] ?? [];
	}


	/**
	 * @param NULL|string $as_realm
	 *
	 * @return ?Language
	 */
	public static function getDefaultLanguage (?string $as_realm = NULL): ?Language {
		if ( ! static::$languagesLoaded) {
			static::loadLanguages();
		}

		return static::$defaultLanguages[ $as_realm ?? static::getRealm() ] ?? NULL;
	}


	/**
	 * If `$as_realm` is NULL, the realm the middleware was loaded with will be used.
	 *
	 * @param string $as_shortcode
	 * @param NULL|string $as_realm
	 *
	 * @return ?Language
	 *
	 * @noinspection PhpUnused
	 */
	public static function getLanguageByShortcode (string $as_shortcode, ?string $as_realm = NULL): ?Language {
		if ( ! static::$languagesLoaded) {
			static::loadLanguages();
		}

		return static::$languages[ $as_realm ?? static::getRealm() ][ $as_shortcode ] ?? NULL;
	}


	/**
	 * @param NULL|string $as_shortcode
	 *
	 * @return NULL|array|Language
	 */
	public static function getLanguagesByShortcode (?string $as_shortcode = NULL): array|Language|null {
		if ( ! static::$languagesLoaded) {
			static::loadLanguages();
		}

		if (empty($as_shortcode)) {
			return static::$languagesByShortcode;
		}

		return static::$languagesByShortcode[ $as_shortcode ] ?? NULL;
	}


	/**
	 * @return null|string
	 */
	public static function getRealm (): ?string {
		return static::$realm;
	}


	/**
	 * @param string $realm
	 *
	 * @return void
	 *
	 * @noinspection PhpUnused
	 */
	public static function setRealm (string $realm): void {
		static::$realm = $realm;
	}


	/**
	 * @return void
	 */
	protected static function loadLanguages (): void {
		$lo_tableLocator = FactoryLocator::get('Table');

		$lo_result = $lo_tableLocator->get('Languages')->find('all', authorize: [
			'skip' => TRUE,
		]);

		/** @var Language $lo_language */
		foreach ($lo_result->all() as $lo_language) {
			/** @var Language $lo_language */
			static::$languages[ $lo_language->realm ][ $lo_language->shortcode ] = $lo_language;

			if ( ! isset(static::$defaultLanguages[ $lo_language->realm ])) {
				static::$defaultLanguages[ $lo_language->realm ] = $lo_language;
			}

			if ( ! isset(static::$languagesByShortcode[ $lo_language->shortcode ])) {
				static::$languagesByShortcode[ $lo_language->shortcode ] = [
					Awyiss::REALM_FRONTEND => NULL,
					Awyiss::REALM_BACKEND => NULL,
				];
			}

			static::$languagesByShortcode[ $lo_language->shortcode ][ $lo_language->realm ] = $lo_language;
		}

		static::$languagesLoaded = TRUE;
	}


	/**
	 * @throws Exception
	 *
	 * @noinspection PhpUnused
	 */
	protected static function getLanguageFromUrl (?string $as_realm = NULL): ?Language {
		if ( ! static::$languagesLoaded) {
			static::loadLanguages();
		}

		$ls_realm = $as_realm ?? static::getRealm();

		$ls_langShortcode = Router::getRequest()->getParam('lang');
		$lo_language = static::getLanguages($ls_realm)[ $ls_langShortcode ] ?? NULL;

		if ( ! $lo_language) {
			$lo_language = current(static::getLanguages($ls_realm)) ?? NULL;
		}

		if ( ! $lo_language) {
			throw new Exception(__d('languages', 'language_shortcode_not_found'), 404);
		}

		return $lo_language;
	}


	/**
	 * @param null|string $as_realm
	 * @param NULL|ServerRequestInterface $ao_request
	 *
	 * @return NULL|Language
	 */
	protected static function getLanguageFromSession (?string $as_realm = NULL, ?ServerRequestInterface $ao_request = NULL): ?Language {
		if ( ! static::$languagesLoaded) {
			static::loadLanguages();
		}

		$ls_realm = $as_realm ?? static::getRealm();

		/** @var Session $lo_session */
		$lo_session = ($ao_request ?? Router::getRequest())->getAttribute('session');
		$ls_languageShortcode = $lo_session->read(static::getSessionIdentifier());

		return static::$languages[ $ls_realm ][ $ls_languageShortcode ] ?? NULL;
	}


	/**
	 * @return string
	 */
	public static function getSessionIdentifier (): string {
		return static::$realm . '.languageShortcode';
	}
}
