<?php declare(strict_types=1);


namespace Awyiss\Middleware;


use Awyiss\I18n\MessagesFileLoader;
use Awyiss\Model\Entity\Language;
use Cake\Datasource\FactoryLocator;
use Cake\I18n\I18n;
use Cake\I18n\Package;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\Routing\Router;
use Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;


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
	protected static array $defaultLanguages = ['frontend' => NULL, 'backend' => NULL];
	/**
	 * @var array{frontend: array<string, Language[]>, backend: array<string, Language[]>}
	 */
	protected static array $languages = ['frontend' => [], 'backend' => []];
	protected static bool $languagesLoaded = FALSE;
	/**
	 * @var array<string, array{frontend: ?Language[], backend: ?Language[]}>
	 */
	protected static array $languagesByShortcode = [];
	protected static string $source;
	protected static string $type;


	final public const SOURCE_URL = '__URL__';
	final public const SOURCE_SESSION = '__SESSION__';


	/**
	 * @param string $as_type
	 * @param string $as_source
	 */
	public function __construct (string $as_type, string $as_source = self::SOURCE_URL) {
		static::$source = $as_source;
		static::$type = $as_type;
	}


	/**
	 * @param \Psr\Http\Message\ServerRequestInterface $ao_request
	 * @param \Psr\Http\Server\RequestHandlerInterface $ao_handler
	 *
	 * @return \Psr\Http\Message\ResponseInterface
	 *
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function process (ServerRequestInterface $ao_request, RequestHandlerInterface $ao_handler): ResponseInterface {
		static::loadLanguages();

		I18n::config('_fallback', function($as_domain, $as_locale) {
			$ls_domain = $as_domain;
			if ( ! str_contains($ls_domain, '/')) {
				$ls_domain = (defined('IS_BACKEND') && IS_BACKEND ? 'backend' : 'frontend') . DS . $ls_domain;
			}

			$lo_fileLoader = new MessagesFileLoader($ls_domain, $as_locale, 'po');
			$lo_default = $lo_fileLoader();

			return new Package('default', NULL, $lo_default->getMessages());
		});

		$lo_request = $ao_request;
		$lo_language = NULL;

		if (static::$source === static::SOURCE_SESSION) {
			$lo_language = static::getLanguageFromSession(static::$type, $lo_request);
		}
		else {
			dd(static::$source, __FILE__, __LINE__);
		}

		if ($lo_language) {
			ini_set('intl.default_locale', $lo_language->locale);
			I18n::setLocale($lo_language->locale);
		}

		$lo_request = $lo_request->withAttribute('locale', $this);

		return $ao_handler->handle($lo_request);
	}


	/**
	 * @return void
	 */
	protected static function loadLanguages (): void {
		$lo_tableLocator = FactoryLocator::get('Table');

		$lo_result = $lo_tableLocator->get('Languages')->find('all', [
			'authorization' => ['skip' => TRUE]
		])->order(['system_order' => 'ASC']);

		/** @var Language $lo_language */
		foreach ($lo_result->all() as $lo_language) {
			/** @var Language $lo_language */
			static::$languages[ $lo_language['type'] ][ $lo_language['shortcode'] ] = $lo_language;

			if (!isset(static::$defaultLanguages[ $lo_language['type'] ])) {
				static::$defaultLanguages[ $lo_language['type'] ] = $lo_language;
			}

			if (!isset(static::$languagesByShortcode[ $lo_language['shortcode'] ])) {
				static::$languagesByShortcode[ $lo_language['shortcode'] ] = [
					'frontend' => NULL,
					'backend' => NULL,
				];
			}

			static::$languagesByShortcode[ $lo_language['shortcode'] ][ $lo_language['type'] ] = $lo_language;
		}

		static::$languagesLoaded = TRUE;
	}


	/**
	 * @param NULL|string $as_type
	 *
	 * @return array|\Awyiss\Model\Entity\Language[]
	 */
	public static function getLanguages (?string $as_type = NULL): array {
		if (!static::$languagesLoaded) {
			static::loadLanguages();
		}

		if (empty($as_type)) {
			return static::$languages;
		}

		return static::$languages[ $as_type ] ?? [];
	}


	/**
	 * @param string $as_type
	 *
	 * @return ?\Awyiss\Model\Entity\Language
	 */
	public static function getDefaultLanguage (string $as_type): ?Language {
		if (!static::$languagesLoaded) {
			static::loadLanguages();
		}

		return static::$defaultLanguages[ $as_type ] ?? NULL;
	}


	/**
	 * If `$as_type` is NULL, the type the middleware was loaded with will be used.
	 *
	 * @param string $as_shortcode
	 * @param NULL|string $as_type
	 *
	 * @return ?\Awyiss\Model\Entity\Language
	 *
	 * @noinspection PhpUnused
	 */
	public static function getLanguageByShortcode (string $as_shortcode, ?string $as_type = NULL): ?Language {
		if (!static::$languagesLoaded) {
			static::loadLanguages();
		}

		$ls_type = $as_type ?? static::$type;

		return static::$languages[ $ls_type ][ $as_shortcode ] ?? NULL;
	}


	/**
	 * @param NULL|string $as_shortcode
	 *
	 * @return NULL|array|\Awyiss\Model\Entity\Language
	 */
	public static function getLanguagesByShortcode (?string $as_shortcode = NULL): array|Language|NULL {
		if (!static::$languagesLoaded) {
			static::loadLanguages();
		}

		if (empty($as_shortcode)) {
			return static::$languagesByShortcode;
		}

		return static::$languagesByShortcode[ $as_shortcode ] ?? NULL;
	}


	/**
	 * @throws \Exception
	 *
	 * @noinspection PhpUnused
	 * @noinspection PhpUnusedParameterInspection
	 */
	public static function getLanguageFromUrl ($ab_fallback = FALSE, ?string $as_type = NULL): ?Language {
		if (!static::$languagesLoaded) {
			static::loadLanguages();
		}

		$ls_type = $as_type ?? static::$type;

		$ls_langShortcode = Router::getRequest()->getParam('lang');
		$lo_language = static::getLanguages($ls_type)[ $ls_langShortcode ] ?? NULL;

		if ( ! $lo_language) {
			$lo_language = current(static::getLanguages($ls_type)) ?? NULL;
		}

		if ( ! $lo_language) {
			throw new Exception(__('::language_shortcode_not_found'), 404);
		}

		return $lo_language;
	}


	/**
	 * @param NULL|string $as_type
	 * @param NULL|\Psr\Http\Message\ServerRequestInterface $ao_request
	 *
	 * @return NULL|\Awyiss\Model\Entity\Language
	 */
	public static function getLanguageFromSession (?string $as_type = NULL, ?ServerRequestInterface $ao_request = NULL): ?Language {
		if (!static::$languagesLoaded) {
			static::loadLanguages();
		}

		/** @var \Cake\Http\Session $lo_session */
		$lo_session = ($ao_request ?? Router::getRequest())->getAttribute('session');
		$ls_languageShortcode = $lo_session->read(($as_type ?? static::$type) . '.languageShortcode');

		return static::$languages[ static::$type ][ $ls_languageShortcode ] ?? NULL;
	}
}