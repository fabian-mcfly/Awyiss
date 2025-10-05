<?php declare(strict_types=1);


namespace Awyiss\View;


use Awyiss\Awyiss;
use Awyiss\Core\App;
use Awyiss\Model\Entity\Language;
use Awyiss\Twig\Extension\AwyissExtension;
use Awyiss\Twig\Extension\EnumExtension;
use Awyiss\Twig\FileLoader;
use Cake\Core\Configure;
use Cake\TwigView\View\TwigView;
use Cake\View\Cell;
use Cake\View\Exception\MissingCellException;
use Cake\View\Helper;
use Twig\Environment;
use Twig\Loader\LoaderInterface;
use Twig\Markup;


/**
 * Application View
 *
 * @property \Awyiss\View\Helper\AssetHelper $Asset
 * @property \Awyiss\View\Helper\AttributesHelper $Attributes
 * @property \Awyiss\View\Helper\AuthorizationHelper $Authorization
 * @property \Awyiss\View\Helper\CategoriesHelper $Categories
 * @property \Awyiss\View\Helper\FlashHelper $Flash
 * @property \Awyiss\View\Helper\FormHelper $Form
 * @property \Awyiss\View\Helper\LocaleHelper $Locale
 * @property \Awyiss\View\Helper\MediaHelper $Media
 * @property \Awyiss\View\Helper\PaginatorHelper $Paginator
 * @property \Awyiss\View\Helper\SystemOrderHelper $SystemOrder
 * @property \Awyiss\View\Helper\UrlHelper $Url
 */
class AppView extends TwigView {
	/**
	 * @var array $helperCache An associative array to cache the helper instances.
	 */
	protected array $helperCache = [];


	/**
	 * @inheritDoc
	 * @return void
	 * @throws \Twig\Error\LoaderError
	 */
	public function initialize(): void {
		$this->setConfig('environment', [
			'auto_reload' => true,
			//'cache' => false,
			//'debug' => Configure::read('debug'),
			'strict_variables' => false,
		]);

		$lb_twigInitialized = static::$twig !== null;

		parent::initialize();

		if (!$lb_twigInitialized) {
			$this->initTwig($this->getTwig());
		}

		$this->set('Awyiss', [
			'VERSION' => Awyiss::VERSION,
			'VERSION_NAME' => Awyiss::VERSION_NAME,
		]);
	}


	/**
	 * 1:1 reimplemented from \Cake\View\CellTrait to use \Awyiss\Core\App
	 * instead of \Cake\Core\App for `App::className` to allow custom classes
	 * overriding the Awyiss cells.
	 *
	 * @inheritDoc
	 * @see \Cake\View\CellTrait::cell()
	 */
	public function cell(string $cell, array $data = [], array $options = []): Cell {
		$la_parts = explode('::', $cell);

		if (count($la_parts) === 2) {
			[$ls_pluginAndCell, $ls_action] = [$la_parts[0], $la_parts[1]];
		}
		else {
			[$ls_pluginAndCell, $ls_action] = [$la_parts[0], 'display'];
		}

		[$ls_plugin] = pluginSplit($ls_pluginAndCell);
		$ls_className = App::className($ls_pluginAndCell, 'View/Cell', 'Cell');

		if (!$ls_className) {
			throw new MissingCellException(['className' => $ls_pluginAndCell . 'Cell']);
		}

		$la_options = ['action' => $ls_action, 'args' => $data] + $options;

		return $this->_createCell($ls_className, $ls_action, $ls_plugin, $la_options);
	}


	/**
	 * Uses the Awyiss HelperRegistry
	 *
	 * @inheritDoc
	 */
	public function helpers(): HelperRegistry {
		return $this->_helpers ??= new HelperRegistry($this);
	}



	/**
	 * Creates a magic helper class instance for each loaded helper
	 *
	 * @inheritDoc
	 * @return void
	 */
	public function loadHelpers(): void {
		parent::loadHelpers();

		// Get the Twig instance.
		$lo_twig = $this->getTwig();

		$la_globals = $lo_twig->getGlobals();

		// Set the helper instances to the view.
		foreach ($this->helpers()->loaded() as $ls_helper) {
			if (isset($la_globals[ $ls_helper . 'Helper' ])) {
				continue;
			}

			$lo_helper = $this->helperClass($ls_helper);

			$this->helperCache[ $ls_helper ] = $lo_helper;

			$lo_twig->addGlobal($ls_helper . 'Helper', $lo_helper);
		}
	}


	/**
	 * @inheritDoc
	 * @return LoaderInterface
	 */
	protected function createLoader(): LoaderInterface {
		return new FileLoader($this->extensions);
	}


	/**
	 * Magic method to handle dynamic property access on the view.
	 *
	 * It allows to access methods of the helper instances directly.
	 *
	 * If the return value of the method is a string containing HTML tags,
	 * it will be wrapped in a Twig Markup object to prevent auto-escaping.
	 *
	 * @param string $name
	 * @return object
	 */
	protected function helperClass(string $name): object {
		return new class ($this->helpers()->{$name}) {
			/**
			 * @var mixed $helper The helper instance.
			 */
			protected Helper $helper;


			/**
			 * Anonymous class constructor.
			 *
			 * @param \Cake\View\Helper $helper The helper instance.
			 */
			public function __construct(Helper $helper) {
				$this->helper = $helper;
			}


			/**
			 * Magic method to handle dynamic method calls on the helper instance.
			 *
			 * @param string $method The name of the method.
			 * @param array $args The arguments to pass to the method.
			 * @return mixed The result of the method call, or null if the result is falsy.
			 * @throws \BadMethodCallException
			 */
			public function __call(string $method, array $args): mixed {
				$ls_method = $method;

				if (!method_exists($this->helper, $ls_method) && !method_exists($this->helper, '__call')) {
					$ls_method = 'get' . ucfirst($method);
				}

				$lx_result = call_user_func([$this->helper, $ls_method], ...$args);

				if (is_string($lx_result) && str_contains($lx_result, '<') && str_contains($lx_result, '>')) {
					return new Markup($lx_result, 'UTF-8');
				}


				return $lx_result;
			}
		};
	}


	/**
	 * @param \Twig\Environment $twig
	 * @return void
	 * @throws \Twig\Error\LoaderError
	 */
	protected function initTwig(Environment $twig): void {
		/** @var \Awyiss\Twig\FileLoader $lo_loader */
		$lo_loader = $twig->getLoader();

		$ls_awyissTemplatesPath = Configure::read('App.paths.templates.awyiss');
		$lo_loader->addPath($ls_awyissTemplatesPath, Configure::read('App.namespace'));

		$la_frontendPaths = [$ls_awyissTemplatesPath . 'Frontend' . DS];
		$la_backendPaths = [$ls_awyissTemplatesPath . 'Backend' . DS];
		if (defined('CUSTOM_DIR')) {
			$ls_customerTemplatesPath = Configure::read('App.paths.templates.customer');
			$lo_loader->addPath($ls_customerTemplatesPath, CUSTOM_NAMESPACE);

			array_unshift($la_frontendPaths, $ls_customerTemplatesPath . 'Frontend' . DS);
			array_unshift($la_backendPaths, $ls_customerTemplatesPath . 'Backend' . DS);
		}

		$lo_loader->setPaths($la_frontendPaths, 'Frontend');
		$lo_loader->setPaths($la_backendPaths, 'Backend');

		$twig->addExtension(new AwyissExtension());
		$twig->addExtension(new EnumExtension());

		if (defined('CUSTOM_NAMESPACE')) {
			//This looks for a custom Twig Extension class in \<CustomNamespace>\Twig\Extension\<CustomNamespace>Extension.php and adds it
			$ls_customExtensionClass = App::className(CUSTOM_NAMESPACE, 'Twig/Extension', 'Extension');
			if ($ls_customExtensionClass) {
				$twig->addExtension(new $ls_customExtensionClass());
			}
		}
	}


	/**
	 * Get the path to the login logo.
	 * This method checks for the existence of a login logo in the customer's `assets` directory
	 * with the name `login-logo` and the extensions `png`, `jpg`, or `svg`.
	 *
	 * @return string|null
	 */
	protected function getLoginLogoPath(): ?string {
		$ls_extensions = ['svg', 'png', 'jpg'];
		$ls_basePath = ROOT . DS . CUSTOM_DIR . DS . 'assets' . DS . 'img' . DS . 'login-logo.';

		// For each extension, check if the file exists
		foreach ($ls_extensions as $ls_extension) {
			$ls_tempPath = $ls_basePath . $ls_extension;
			if (file_exists($ls_tempPath)) {
				return substr_replace($ls_tempPath, '', 0, strlen(ROOT . DS . CUSTOM_DIR));
			}
		}

		return null;
	}


	/**
	 * @param \Awyiss\Model\Entity\Language $language
	 * @return \Awyiss\Model\Entity\Language
	 */
	protected function cleanLanguage(Language $language): Language {
		$la_blocklistedProperties = ['realm', 'systemOrder', 'active', 'deleted', 'createdBy', 'createdOn', 'changedBy', 'changedOn', 'deletedBy', 'deletedOn'];

		$lo_language = clone $language;

		foreach ($la_blocklistedProperties as $ls_property) {
			unset($lo_language->{$ls_property});
		}

		$la_virtualFields = $lo_language->getVirtual();
		$la_virtualFields = array_filter($la_virtualFields, function (string $key): bool {
			return $key !== 'label';
		});

		$lo_language->setVirtual($la_virtualFields, true);

		return $lo_language;
	}
}
