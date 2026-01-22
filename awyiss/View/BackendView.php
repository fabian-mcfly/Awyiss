<?php declare(strict_types=1);


namespace Awyiss\View;


use Awyiss\Awyiss;
use Awyiss\Core\LocalConfig;
use Awyiss\Middleware\LocaleMiddleware;
use Awyiss\Routing\Router;
use Cake\Core\Configure;
use Twig\Environment;


/**
 * Backend View
 */
class BackendView extends AppView {
	/**
	 * @inheritDoc
	 * @return void
	 * @throws \Twig\Error\LoaderError
	 * @throws \Exception
	 * @noinspection DuplicatedCode
	 */
	public function initialize(): void {
		parent::initialize();

		$this->addHelpers();

		$this->addTwigGlobals();
	}


	/**
	 * Add helpers
	 *
	 * @return void
	 */
	protected function addHelpers(): void {
		$this->addHelper('Asset');

		$this->addHelper('Attributes');

		$this->addHelper('Audit');

		$this->addHelper('Authentication.Identity', [
			'identityAttribute' => Awyiss::REALM_BACKEND . 'Identity',
		]);

		$this->addHelper('Authorization');

		$this->addHelper('Categories', [
			'templates' => 'form_templates_backend',
		]);

		$this->addHelper('Flash');

		$this->addHelper('Form', [
			'autoSetCustomValidity' => false,
			'templates' => 'form_templates_backend',
		]);

		$this->addHelper('Html');

		$this->addHelper('Locale');

		$this->addHelper('Media');

		$this->addHelper('Paginator', [
			'aliasedFields' => $this->viewVars['paginate']['aliasedFields'] ?? [],
			'templates' => 'paginator_templates',
		]);

		$this->addHelper('Survey');

		$this->addHelper('SystemOrder', [
			'field' => $this->viewVars['systemOrderField'] ?? null,
			'relatedColumns' => $this->viewVars['systemOrderRelatedColumns'] ?? null,
			'options' => $this->viewVars['systemOrderRecords'] ?? null,
			'templates' => [
				'titleOption' => function (mixed $option): string {
					return __('system_order_after') . ' ' . $option->label;
				},
				'titleOptionCurrent' => function (mixed $option): string {
					return __('system_order_after') . ' ' . $option->label;
				},
				'titleOptionSelected' => function (mixed $option): string {
					return '-> ' . __('system_order_after') . ' ' . $option->label;
				},
			],
		]);

		$this->addHelper('Url');
	}


	/**
	 * @return void
	 * @throws \Exception
	 */
	protected function addTwigGlobals(): void {
		$twig = $this->getTwig();
		$logoPath = $this->getLoginLogoPath();

		if ($logoPath) {
			// If the logo path is set, remove the root path and custom directory from the path
			$twig->addGlobal('loginLogoPath', $logoPath);
		}

		$this->addFrontendLanguage($twig);

		$this->addUserLanguage($twig);

		$folder = '/' . (ltrim($this->getRequest()->getAttribute('base'), '/') ?? '');
		if (!str_ends_with($folder, '/')) {
			$folder .= '/';
		}

		$uri = $this->getRequest()->getUri();
		if ($folder !== '/' && !str_starts_with($uri->getPath(), $folder)) {
			$uri = $uri->withPath($folder . ltrim($uri->getPath(), '/'));
		}

		$twig->addGlobal('baseUrl', Router::url('/', true));
		$twig->addGlobal('currentPath', $this->getRequest()->getUri()->getPath());
		$twig->addGlobal('currentUrl', $uri->__toString());
		$twig->addGlobal('config', Configure::read());
		$twig->addGlobal('folder', $folder);
		$twig->addGlobal('languages', LocaleMiddleware::getLanguages());
		$twig->addGlobal('localConfig', LocalConfig::read());
	}


	/**
	 * @param \Twig\Environment $twig
	 * @return void
	 * @throws \Exception
	 */
	protected function addFrontendLanguage(Environment $twig): void {
		$frontendLanguage = LocaleMiddleware::getLanguage();

		if ($frontendLanguage) {
			$frontendLanguage = $this->cleanLanguage($frontendLanguage);
		}

		$twig->addGlobal('currentLanguage', $frontendLanguage);
		$twig->addGlobal('languageShortcode', $frontendLanguage?->shortcode);
	}


	/**
	 * @param \Twig\Environment $twig
	 * @return void
	 * @throws \Exception
	 */
	protected function addUserLanguage(Environment $twig): void {
		$backendLanguage = LocaleMiddleware::getLanguage(Awyiss::REALM_BACKEND);

		if (!$backendLanguage) {
			$twig->addGlobal('userLanguage', null);

			return;
		}

		$timezone = Configure::read('Awyiss.System.' . Awyiss::getRealm() . '.timezone');
		if ($timezone === 'auto') {
			$timezone = $backendLanguage->timezone;
		}

		$this->addHelper('Time', ['outputTimezone' => $timezone]);

		$twig->addGlobal('dateFormat', $backendLanguage->dateFormat ?? 'yyyy-MM-dd');
		$twig->addGlobal('timeFormat', $backendLanguage->timeFormat ?? 'HH:mm');

		$backendLanguage = $this->cleanLanguage($backendLanguage);

		$twig->addGlobal('userLanguage', $backendLanguage);
	}
}
