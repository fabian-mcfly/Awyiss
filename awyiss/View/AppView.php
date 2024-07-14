<?php declare(strict_types=1);


namespace Awyiss\View;


use Awyiss\Awyiss;
use Awyiss\Core\App;
use Awyiss\Twig\Extension\AwyissExtension;
use Awyiss\Twig\Extension\EnumExtension;
use Awyiss\Twig\FileLoader;
use Cake\Core\Configure;
use Cake\TwigView\View\TwigView;
use Cake\View\Cell;
use Cake\View\Exception\MissingCellException;
use Cake\View\Helper;
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
	 * @var bool $initialized A flag to check if the view has been initialized.
	 */
	protected static bool $initialized = false;
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

		parent::initialize();

		$lo_twig = $this->getTwig();

		if (!static::$initialized) {
			/** @var FileLoader $lo_loader */
			$lo_loader = $lo_twig->getLoader();

			$lo_loader->addPath(ROOT . DS . APP_DIR . DS . 'templates' . DS, Configure::read('App.namespace'));

			if (defined('CUSTOM_DIR')) {
				$lo_loader->addPath(ROOT . DS . CUSTOM_DIR . DS . 'templates' . DS, CUSTOM_NAMESPACE);

				$lo_loader->setPaths([
					ROOT . DS . CUSTOM_DIR . DS . 'templates' . DS . 'Frontend' . DS,
					ROOT . DS . APP_DIR . DS . 'templates' . DS . 'Frontend' . DS,
				], 'Frontend');

				$lo_loader->setPaths([
					ROOT . DS . CUSTOM_DIR . DS . 'templates' . DS . 'Backend' . DS,
					ROOT . DS . APP_DIR . DS . 'templates' . DS . 'Backend' . DS,
				], 'Backend');
			}
			else {
				$lo_loader->addPath(ROOT . DS . APP_DIR . DS . 'templates' . DS . 'Frontend' . DS, 'Frontend');

				$lo_loader->addPath(ROOT . DS . APP_DIR . DS . 'templates' . DS . 'Backend' . DS, 'Backend');
			}

			$lo_twig->addExtension(new AwyissExtension());
			$lo_twig->addExtension(new EnumExtension());

			if (defined('CUSTOM_NAMESPACE')) {
				//This looks for a custom Twig Extension class in \<custom namespace>\Twig\Extension\<CustomNamespace>Extension.php and adds it
				$ls_customExtensionClass = '\\' . CUSTOM_NAMESPACE . '\Twig\Extension\\' . CUSTOM_NAMESPACE . 'Extension';
				if (class_exists($ls_customExtensionClass)) {
					$lo_twig->addExtension(new $ls_customExtensionClass());
				}
			}

			static::$initialized = true;
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

				if (!method_exists($this->helper, $ls_method)) {
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
}
