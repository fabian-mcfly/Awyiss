<?php declare(strict_types=1);


namespace Awyiss\View;


use Awyiss\Awyiss;
use Awyiss\Middleware\LocaleMiddleware;
use Awyiss\Routing\Router;


/**
 * Backend View
 */
class BackendView extends AppView {
	/**
	 * @inheritDoc
	 * @return void
	 * @throws \Twig\Error\LoaderError
	 * @throws \Exception
	 */
	public function initialize(): void {
		parent::initialize();

		$this->addHelper('Asset');
		$this->addHelper('Attributes');
		$this->addHelper('Authentication.Identity');
		$this->addHelper('Authorization');
		$this->addHelper('Categories');
		$this->addHelper('Flash');
		$this->addHelper('Form', [
			'autoSetCustomValidity' => false,
			'errorClass' => 'Error',
			'templates' => 'form_templates_backend',
		]);
		$this->addHelper('Html');
		$this->addHelper('Locale');
		$this->addHelper('Media');
		$this->addHelper('Paginator', [
			'aliasedFields' => $this->viewVars['paginate']['aliasedFields'] ?? [],
			'templates' => 'paginator_templates',
		]);
		$this->addHelper('SystemOrder', [
			'field' => $this->viewVars['systemOrderField'] ?? null,
			'relatedColumns' => $this->viewVars['systemOrderRelatedColumns'] ?? null,
			'options' => $this->viewVars['systemOrderRecords'] ?? null,
			'templates' => [
				'titleOption' => function (mixed $option): string {
					return __('system_order_after') . ' ' . $option->label;
				},
				'titleOptionCurrent' => function (mixed $option): string {
					return $option->label;
				},
				'titleOptionSelected' => function (mixed $option): string {
					return '-> ' . __('system_order_after') . ' ' . $option->label;
				},
			],
		]);

		/**
		 * @var \Awyiss\Model\Entity\Language|null $lo_userLanguage
		 */
		$lo_userLanguage = LocaleMiddleware::getLanguage(null);
		if ($lo_userLanguage) {
			$this->addHelper('Time', ['outputTimezone' => $lo_userLanguage->timezone]);
		}

		$this->addHelper('Url');


		// Set login logo path
		$ls_logoPath = null;
		$ls_extensions = ['png', 'jpg', 'svg'];
		$ls_basePath = ROOT . DS . CUSTOM_DIR . DS . 'assets' . DS . 'img' . DS . 'login-logo.';
		// For each extension, check if the file exists
		foreach ($ls_extensions as $ls_extension) {
			$ls_tempPath = $ls_basePath . $ls_extension;
			if (file_exists($ls_tempPath)) {
				$ls_logoPath = $ls_tempPath;
				break;
			}
		}

		// If the logo path is set, remove the root path and custom directory from the path
		$this->set('loginLogoPath', substr_replace($ls_logoPath, '', 0, strlen(ROOT . DS . CUSTOM_DIR) + 1));


		$lo_blocklistedProperties = ['realm', 'systemOrder', 'active', 'deleted', 'createdBy', 'createdOn', 'changedBy', 'changedOn', 'deletedBy', 'deletedOn', 'label'];
		// Unset language properties
		$lo_frontendLanguage = LocaleMiddleware::getLanguage();
		if ($lo_frontendLanguage) {
			$lo_frontendLanguage = clone $lo_frontendLanguage;

			foreach ($lo_blocklistedProperties as $ls_property) {
				unset($lo_frontendLanguage->{$ls_property});
			}
		}

		$lo_backendLanguage = LocaleMiddleware::getLanguage(Awyiss::REALM_BACKEND);
		if ($lo_backendLanguage) {
			$lo_backendLanguage = clone $lo_backendLanguage;

			foreach ($lo_blocklistedProperties as $ls_property) {
				unset($lo_backendLanguage->{$ls_property});
			}
		}

		$lo_twig = $this->getTwig();
		$lo_twig->addGlobal('baseUrl', Router::url('/', true));
		$lo_twig->addGlobal('currentLanguage', $lo_frontendLanguage);
		$lo_twig->addGlobal('currentPath', $this->getRequest()->getUri()->getPath());
		$lo_twig->addGlobal('currentUrl', $this->request->getUri()->__toString());
		$lo_twig->addGlobal('folder', '/' . ltrim($this->request->getAttribute('base'), '/'));
		$lo_twig->addGlobal('languages', LocaleMiddleware::getLanguages());
		$lo_twig->addGlobal('languageShortcode', $lo_frontendLanguage?->shortcode);
		$lo_twig->addGlobal('userLanguage', $lo_backendLanguage);
	}
}
