<?php declare(strict_types=1);


namespace Awyiss\Middleware;


use Awyiss\Model\Entity\Language;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;


class LocaleMiddleware implements MiddlewareInterface {
	use \Cake\ORM\Locator\LocatorAwareTrait;

	/** @var \Awyiss\Application */
	protected \Awyiss\Application $application;
	protected array $languages = ['frontend' => [], 'backend' => []];
	protected array $languagesByShortcode = [];
	protected string $source;
	protected string $type;

	public const SOURCE_URL = '__URL__';
	public const SOURCE_SESSION = '__SESSION__';


	public function __construct (string $as_type, string $as_source = self::SOURCE_URL) {
		$this->source = $as_source;
		$this->type = $as_type;

		$this->loadLanguages();

		\Cake\I18n\I18n::config('_fallback', function($as_domain, $as_locale) {
			$ls_domain = $as_domain;
			if ( ! str_contains($ls_domain, '/')) {
				$ls_domain = (defined('IS_BACKEND') && IS_BACKEND ? 'backend' : 'frontend') . DS . $ls_domain;
			}

			$lo_fileLoader = new \Awyiss\I18n\MessagesFileLoader($ls_domain, $as_locale, 'po');
			$lo_default = $lo_fileLoader();

			return new \Cake\I18n\Package('default', NULL, $lo_default->getMessages());
		});
	}


	/**
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function process (ServerRequestInterface $ao_request, RequestHandlerInterface $ao_handler): ResponseInterface {
		$lo_request = $ao_request;
		$lo_language = NULL;

		if ($this->source === static::SOURCE_SESSION) {
			$lo_language = $this->getLanguageFromSession($this->type, $lo_request);
		}
		else {
			dd($this->source, __FILE__, __LINE__);
		}

		if ($lo_language) {
			ini_set('intl.default_locale', $lo_language->locale);
			\Cake\I18n\I18n::setLocale($lo_language->locale);
		}

		$lo_request = $lo_request->withAttribute('locale', $this);

		return $ao_handler->handle($lo_request);
	}


	protected function loadLanguages (): void {
		$lo_tableLocator = $this->getTableLocator();

		$lo_result = $lo_tableLocator->get('Languages')->find()->order(['system_order' => 'ASC']);

		foreach ($lo_result->all() as $lo_language) {
			/** @var Language $lo_language */
			$this->languages[ $lo_language['type'] ][ $lo_language['shortcode'] ] = $lo_language;

			if (!isset($this->languagesByShortcode[ $lo_language['shortcode'] ])) {
				$this->languagesByShortcode[ $lo_language['shortcode'] ] = [
					'frontend' => NULL,
					'backend' => NULL,
				];
			}

			$this->languagesByShortcode[ $lo_language['shortcode'] ][ $lo_language['type'] ] = $lo_language;
		}
	}


	public function getLanguages (?string $as_type = NULL): array {
		if (empty($as_type)) {
			return $this->languages;
		}

		return $this->languages[ $as_type ] ?? [];
	}


	/**
	 * @noinspection PhpUnused
	 */
	public function getLanguageByShortcode (string $as_shortcode, ?string $as_type = NULL): ?Language {
		$ls_type = $as_type ?? $this->type;

		return $this->languages[ $ls_type ][ $as_shortcode ] ?? NULL;
	}


	/**
	 * @noinspection PhpUnused
	 */
	public function getLanguagesByShortcode (?string $as_shortcode = NULL): ?array {
		if (empty($as_shortcode)) {
			return $this->languagesByShortcode;
		}

		return $this->languagesByShortcode[ $as_shortcode ] ?? NULL;
	}


	/**
	 * @throws \Exception
	 *
	 * @noinspection PhpUnused
	 */
	public function getLanguageFromUrl (): ?Language {
		$ls_langShortcode = \Cake\Routing\Router::getRequest()->getParam('lang');
		$lo_language = static::getLanguages('frontend')[ $ls_langShortcode ] ?? NULL;
		if ( ! $lo_language) {
			throw new \Exception(__('::language_shortcode_not_found'), 404);
		}

		return $lo_language;
	}


	public function getLanguageFromSession (?string $as_type = NULL, ?ServerRequestInterface $ao_request = NULL): ?Language {
		/** @var \Cake\Http\Session $lo_session */
		$lo_session = ($ao_request ?? \Cake\Routing\Router::getRequest())->getAttribute('session');
		$ls_languageShortcode = $lo_session->read(($as_type ?? $this->type) . '.languageShortcode');

		return $this->languages[ $this->type ][ $ls_languageShortcode ] ?? NULL;
	}
}