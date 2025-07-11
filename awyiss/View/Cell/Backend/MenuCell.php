<?php declare(strict_types=1);


namespace Awyiss\View\Cell\Backend;


use Awyiss\Authorization\IdentityPermissionsInterface;
use Awyiss\Core\App;
use Awyiss\Middleware\LocaleMiddleware;
use Awyiss\Utility\Inflector;
use Cake\I18n\DateTime;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\View\Cell;
use Cake\View\StringTemplate;
use RuntimeException;


/**
 * Provides the backend menu with authorization check
 */
class MenuCell extends Cell {
	use LocatorAwareTrait;


	/**
	 * Options for the menu renderer
	 *
	 * @var array $rendererOptions
	 * @noinspection HtmlUnknownAttribute
	 */
	protected array $rendererOptions = [
		'formatters' => [],
		'templates' => [
			'noLink' => '<span class="Level{{level}}{{active}} {{identifier}}"{{tabindex}}>{{title}}</span>' . PHP_EOL,
		],
	];


	/**
	 * Set the formatters if they are not set
	 *
	 * @return void
	 */
	public function initialize(): void {
		$this->rendererOptions['formatters']['noLink'] ??= $this->renderNoLink(...);
	}


	/**
	 * Generate the menu and load templates/Backend/cell/menu/menu
	 *
	 * @return void
	 * @throws \ReflectionException
	 * @throws \Exception
	 */
	public function display(?string $currentPageRole = null): void {
		// Get the user's identity and session
		$lo_identity = $this->_getIdentity();
		$lo_session = $this->request->getSession();

		// Initialize an empty array for the menu data
		$la_menuData = [];

		// Define the session identifier for the menu
		$ls_sessionIdentifier = 'Backend.menu.' . LocaleMiddleware::getLanguage()->shortcode;

		// Try to read the menu from the session
		$ls_menu = $lo_session->read($ls_sessionIdentifier);

		// If the menu is in the session, decode it and get the time it was cached
		if ($ls_menu) {
			$la_menuData = json_decode($ls_menu, true);
			$lo_time = new DateTime($la_menuData['time']);

			// If the cached menu is outdated, clear the menu data
			if ($lo_time >= $lo_identity->changedOn) {
				$lo_table = $this->fetchTable('BackendMenuEntries');
				$lo_entity = $lo_table->find()->select('id')->find('withDeleted')->where([
					'OR' => [
						'created_on >' => $lo_time,
						'changed_on >' => $lo_time,
						'deleted_on >' => $lo_time,
					],
				])->first();

				if ($lo_entity) {
					$la_menuData = [];
				}
			}
		}
		// If the menu data is not in the session or is outdated, regenerate the menu data
		/** @noinspection PhpUndefinedVariableInspection */
		if (!$la_menuData || $lo_time < $lo_identity->changedOn) {
			/** @var class-string<\Awyiss\Utility\Menu\BackendMenuProvider> $ls_backendMenuProviderClass */
			$ls_backendMenuProviderClass = App::className('BackendMenuProvider', 'Utility/Menu');

			$lo_menu = new $ls_backendMenuProviderClass();
			$lo_menu = $lo_menu->getDynamicMenu();

			// Cache the menu data and the time it was cached
			$lo_session->write($ls_sessionIdentifier, json_encode([
				'menuData' => serialize($lo_menu),
				'time' => new DateTime(),
			]));
		}
		else {
			// If the menu data is in the session and is not outdated, use the cached menu data
			$lo_menu = unserialize($la_menuData['menuData']);
		}

		$lo_menu->setIdentity($lo_identity);

		/** @var class-string<\Awyiss\Utility\Menu\MenuRenderer> $ls_menuRendererClass */
		$ls_menuRendererClass = App::className('MenuRenderer', 'Utility/Menu');
		// Create a new menu renderer with the menu data
		$lo_renderer = new $ls_menuRendererClass($lo_menu, $this->rendererOptions);

		// Set the current route in the menu renderer
		$ls_url = '/backend/' . $this->request->getParam('lang') . '/';
		if ($currentPageRole) {
			$ls_url .= $currentPageRole . '/overview/';
		}
		else {
			$ls_url .= Inflector::dasherize($this->request->getParam('controller'));
			$ls_url .= '/' . $this->request->getParam('action') . '/';
		}
		$lo_renderer->setCurrentRoute($ls_url);

		// Render the menu
		$ls_menu = $lo_renderer->render('System');

		// Set the menu in the view variables
		$this->set([
			'menu' => $ls_menu,
		]);

		// Set the template for the view
		$this->viewBuilder()->setTemplatePath('Backend/cell/Menu')->setTemplate('menu');
	}


	/**
	 * Retreive the identity attribute from the current request
	 */
	protected function _getIdentity(): IdentityPermissionsInterface {
		/** @var IdentityPermissionsInterface|\Awyiss\Model\Entity\User $lo_identity */
		$lo_identity = $this->request->getAttribute('identity');

		if (!$lo_identity) {
			throw new RuntimeException('No identity found in the request.');
		}

		if (!($lo_identity instanceof IdentityPermissionsInterface)) {
			throw new RuntimeException(sprintf('Object `%s` does not implement `%s`', get_class($lo_identity), IdentityPermissionsInterface::class));
		}


		return $lo_identity;
	}


	/**
	 * @param array $data
	 * @param \Cake\View\StringTemplate $template
	 * @return string
	 */
	public function renderNoLink(array $data, StringTemplate $template): string {
		$la_data = $data;

		$la_data['tabindex'] = '';
		if (!empty($la_data['children'])) {
			$la_data['tabindex'] = ' tabindex="0"';
		}

		return $template->format('noLink', $la_data);
	}
}
