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

		$this->addHelper('Authentication.Identity');

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
		$lo_twig = $this->getTwig();
		$ls_logoPath = $this->getLoginLogoPath();

		if ($ls_logoPath) {
			// If the logo path is set, remove the root path and custom directory from the path
			$lo_twig->addGlobal('loginLogoPath', $ls_logoPath);
		}

		$this->addFrontendLanguage($lo_twig);

		$this->addUserLanguage($lo_twig);

		$ls_folder = '/' . (ltrim($this->getRequest()->getAttribute('base'), '/') ?? '');
		if (!str_ends_with($ls_folder, '/')) {
			$ls_folder .= '/';
		}

		$lo_uri = $this->getRequest()->getUri();
		if ($ls_folder !== '/' && !str_starts_with($lo_uri->getPath(), $ls_folder)) {
			$lo_uri = $lo_uri->withPath($ls_folder . ltrim($lo_uri->getPath(), '/'));
		}

		$lo_twig->addGlobal('baseUrl', Router::url('/', true));
		$lo_twig->addGlobal('currentPath', $this->getRequest()->getUri()->getPath());
		$lo_twig->addGlobal('currentUrl', $lo_uri->__toString());
		$lo_twig->addGlobal('config', Configure::read());
		$lo_twig->addGlobal('folder', $ls_folder);
		$lo_twig->addGlobal('languages', LocaleMiddleware::getLanguages());
		$lo_twig->addGlobal('localConfig', LocalConfig::read());
	}


	/**
	 * @param \Twig\Environment $twig
	 * @return void
	 * @throws \Exception
	 */
	protected function addFrontendLanguage(Environment $twig): void {
		$lo_frontendLanguage = LocaleMiddleware::getLanguage();

		if ($lo_frontendLanguage) {
			$lo_frontendLanguage = $this->cleanLanguage($lo_frontendLanguage);
		}

		$twig->addGlobal('currentLanguage', $lo_frontendLanguage);
		$twig->addGlobal('languageShortcode', $lo_frontendLanguage?->shortcode);
	}


	/**
	 * @param \Twig\Environment $twig
	 * @return void
	 * @throws \Exception
	 */
	protected function addUserLanguage(Environment $twig): void {
		$lo_backendLanguage = LocaleMiddleware::getLanguage(Awyiss::REALM_BACKEND);

		if (!$lo_backendLanguage) {
			$twig->addGlobal('userLanguage', null);

			return;
		}

		$ls_timezone = Configure::read('Awyiss.System.' . Awyiss::getRealm() . '.timezone');
		if ($ls_timezone === 'auto') {
			$ls_timezone = $lo_backendLanguage->timezone;
		}

		$this->addHelper('Time', ['outputTimezone' => $ls_timezone]);

		$twig->addGlobal('dateFormat', $lo_backendLanguage->dateFormat ?? 'yyyy-MM-dd');
		$twig->addGlobal('timeFormat', $lo_backendLanguage->timeFormat ?? 'HH:mm');

		$lo_backendLanguage = $this->cleanLanguage($lo_backendLanguage);

		$twig->addGlobal('userLanguage', $lo_backendLanguage);
	}
}
