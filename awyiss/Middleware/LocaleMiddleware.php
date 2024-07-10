<?php declare(strict_types=1);


namespace Awyiss\Middleware;


use Awyiss\Awyiss;
use Awyiss\I18n\MessagesFileLoader;
use Awyiss\Model\Entity\Language;
use Awyiss\Routing\Router;
use Cake\Database\TypeFactory;
use Cake\Datasource\FactoryLocator;
use Cake\I18n\I18n;
use Cake\I18n\Package;
use Cake\ORM\Locator\LocatorAwareTrait;
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
	 * @var string|null $realm The realm the middleware was loaded with
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

		I18n::config('_fallback', function ($domain, $locale) {
			$ls_domain = $domain;
			if (!str_contains($ls_domain, '/')) {
				$ls_domain = static::getRealm() . '/' . $ls_domain;
			}

			$lo_fileLoader = new MessagesFileLoader($ls_domain, $locale, 'po');
			$lo_default = $lo_fileLoader();

			return new Package('default', null, $lo_default->getMessages());
		});

		$lo_language = static::getLanguage(static::getRealm());

		if ($lo_language) {
			ini_set('intl.default_locale', $lo_language->locale);
			I18n::setLocale($lo_language->locale);
			TypeFactory::build('datetime')->setUserTimezone($lo_language->timezone);

			$lo_tableLocator = FactoryLocator::get('Table');
			/** @noinspection PhpPossiblePolymorphicInvocationInspection */
			$lo_tableLocator->setTranslateLanguage($lo_language);

			// Add the TranslateBehavior to the LanguagesTable as it's not set on instantiation
			/** @noinspection PhpPossiblePolymorphicInvocationInspection */
			$lo_tableLocator->get('Languages')->addTranslateBehavior($lo_language);
		}

		$lo_request = $request->withAttribute('locale', $this);


		return $handler->handle($lo_request);
	}


	/**
	 * @param string|null $realm
	 * @param bool $fallback If true, the default language will be returned if no language is found
	 * @param string $retrievalStategy
	 * @return Language|null
	 * @throws \Exception
	 */
	public static function getLanguage(?string $realm = Awyiss::REALM_FRONTEND, bool $fallback = true, string $retrievalStategy = self::SOURCE_AUTO): ?Language {
		if (!static::$languagesLoaded) {
			static::loadLanguages();
		}

		$ls_realm = $realm;
		if (!$ls_realm) {
			$ls_realm = static::getRealm();
		}

		$ls_retrievalStategy = $retrievalStategy;
		if ($ls_retrievalStategy === static::SOURCE_AUTO) {
			if (!isset(static::$retrievalStrategy[ $ls_realm ])) {
				throw new RuntimeException(sprintf('Cannot use auto-detection of the retrievel strategy. No retrievel strategy defined for realm `%s`.', $ls_realm));
			}

			$ls_retrievalStategy = static::$retrievalStrategy[ $ls_realm ];
		}

		$lo_language = null;
		if ($ls_retrievalStategy === static::SOURCE_SESSION) {
			$lo_language = static::getLanguageFromSession($ls_realm);
		}
		elseif ($ls_retrievalStategy === static::SOURCE_URL) {
			$lo_language = static::getLanguageFromUrl($ls_realm);
		}

		return $lo_language ?? ($fallback ? static::getDefaultLanguage($ls_realm ?? static::getRealm()) : null);
	}


	/**
	 * @param string|null $realm
	 * @return array|array<Language>
	 */
	public static function getLanguages(?string $realm = null): array {
		if (!static::$languagesLoaded) {
			static::loadLanguages();
		}

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
		if (!static::$languagesLoaded) {
			static::loadLanguages();
		}


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
		if (!static::$languagesLoaded) {
			static::loadLanguages();
		}


		return static::$languages[ $realm ?? static::getRealm() ][ $shortcode ] ?? null;
	}


	/**
	 * @param string|null $shortcode
	 * @return Language|array|null
	 */
	public static function getLanguagesByShortcode(?string $shortcode = null): array|Language|null {
		if (!static::$languagesLoaded) {
			static::loadLanguages();
		}

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
	 * @return string
	 */
	public static function getSessionIdentifier(?string $realm = null): string {
		$ls_realm = $realm ?? static::getRealm();

		return $ls_realm . '.languageShortcode';
	}


	/**
	 * @return void
	 */
	protected static function loadLanguages(): void {
		$lo_tableLocator = FactoryLocator::get('Table');

		/** @var \Awyiss\Model\Table\LanguagesTable $lo_table */
		$lo_table = $lo_tableLocator->get('Languages');
		$lo_result = $lo_table->find('all');

		/** @var Language $lo_language */
		foreach ($lo_result->all() as $lo_language) {
			/** @var Language $lo_language */
			static::$languages[ $lo_language->realm ][ $lo_language->shortcode ] = $lo_language;

			if ($lo_language->active) {
				if (!isset(static::$defaultLanguages[ $lo_language->realm ])) {
					static::$defaultLanguages[ $lo_language->realm ] = $lo_language;
				}

				if (!isset(static::$languagesByShortcode[ $lo_language->shortcode ])) {
					static::$languagesByShortcode[ $lo_language->shortcode ] = [
						Awyiss::REALM_FRONTEND => null,
						Awyiss::REALM_BACKEND => null,
					];
				}
			}

			static::$languagesByShortcode[ $lo_language->shortcode ][ $lo_language->realm ] = $lo_language;
		}

		static::$languagesLoaded = true;
	}


	/**
	 * @throws \Exception
	 * @noinspection PhpUnused
	 */
	protected static function getLanguageFromUrl(?string $realm = null): ?Language {
		if (!static::$languagesLoaded) {
			static::loadLanguages();
		}

		$ls_realm = $realm ?? static::getRealm();

		$ls_langShortcode = Router::getRequest()?->getParam('lang');
		$lo_language = static::getLanguages($ls_realm)[ $ls_langShortcode ] ?? null;

		if ($lo_language && $lo_language->active) {
			return $lo_language;
		}

		return null;
	}


	/**
	 * @param string|null $realm
	 * @param ServerRequestInterface|null $request
	 * @return Language|null
	 */
	protected static function getLanguageFromSession(?string $realm = null, ?ServerRequestInterface $request = null): ?Language {
		if (!static::$languagesLoaded) {
			static::loadLanguages();
		}

		$ls_realm = $realm ?? static::getRealm();

		/** @var \Cake\Http\Session $lo_session */
		$lo_session = ($request ?? Router::getRequest())?->getAttribute('session');

		if (!$lo_session) {
			return null;
		}

		$ls_languageShortcode = $lo_session->read(static::getSessionIdentifier($ls_realm));

		if (!$ls_languageShortcode) {
			return null;
		}

		return static::$languages[ $ls_realm ][ $ls_languageShortcode ] ?? null;
	}
}
