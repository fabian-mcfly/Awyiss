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
			'auto_reload' => Configure::read('debug'),
			'cache' => !Configure::read('debug'),
			'debug' => Configure::read('debug'),
			'use_yield' => true,
			'strict_variables' => false,
		]);

		$twigInitialized = static::$twig !== null;

		parent::initialize();

		if (!$twigInitialized) {
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
		$parts = explode('::', $cell);

		if (count($parts) === 2) {
			[$pluginAndCell, $action] = [$parts[0], $parts[1]];
		}
		else {
			[$pluginAndCell, $action] = [$parts[0], 'display'];
		}

		[$plugin] = pluginSplit($pluginAndCell);
		$className = App::className($pluginAndCell, 'View/Cell', 'Cell');

		if (!$className) {
			throw new MissingCellException(['className' => $pluginAndCell . 'Cell']);
		}

		$options = ['action' => $action, 'args' => $data] + $options;

		return $this->_createCell($className, $action, $plugin, $options);
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
		$twig = $this->getTwig();

		$globals = $twig->getGlobals();

		// Set the helper instances to the view.
		foreach ($this->helpers()->loaded() as $helper) {
			if (isset($globals[ $helper . 'Helper' ])) {
				continue;
			}

			$helperClass = $this->helperClass($helper);

			$this->helperCache[ $helper ] = $helperClass;

			$twig->addGlobal($helper . 'Helper', $helperClass);
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
				if (!method_exists($this->helper, $method) && !method_exists($this->helper, '__call')) {
					$method = 'get' . ucfirst($method);
				}

				$result = call_user_func([$this->helper, $method], ...$args);

				if (is_string($result)) {
					return new Markup($result, 'UTF-8');
				}

				return $result;
			}
		};
	}


	/**
	 * @param \Twig\Environment $twig
	 * @return void
	 * @throws \Twig\Error\LoaderError
	 */
	protected function initTwig(Environment $twig): void {
		/** @var \Awyiss\Twig\FileLoader $loader */
		$loader = $twig->getLoader();

		$awyissTemplatesPath = Configure::read('App.paths.templates.awyiss');
		$loader->addPath($awyissTemplatesPath, Configure::read('App.namespace'));

		$frontendPaths = [$awyissTemplatesPath . 'Frontend' . DS];
		$backendPaths = [$awyissTemplatesPath . 'Backend' . DS];
		if (defined('CUSTOM_DIR')) {
			$customerTemplatesPath = Configure::read('App.paths.templates.customer');
			$loader->addPath($customerTemplatesPath, CUSTOM_NAMESPACE);

			array_unshift($frontendPaths, $customerTemplatesPath . 'Frontend' . DS);
			array_unshift($backendPaths, $customerTemplatesPath . 'Backend' . DS);
		}

		$loader->setPaths($frontendPaths, 'Frontend');
		$loader->setPaths($backendPaths, 'Backend');

		$twig->addExtension(new AwyissExtension());
		$twig->addExtension(new EnumExtension());

		if (defined('CUSTOM_NAMESPACE')) {
			//This looks for a custom Twig Extension class in \<CustomNamespace>\Twig\Extension\<CustomNamespace>Extension.php and adds it
			$customExtensionClass = App::className(CUSTOM_NAMESPACE, 'Twig/Extension', 'Extension');
			if ($customExtensionClass) {
				$twig->addExtension(new $customExtensionClass());
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
		$extensions = ['svg', 'png', 'jpg'];
		$basePath = ROOT . DS . CUSTOM_DIR . DS . 'assets' . DS . 'img' . DS . 'login-logo.';

		// For each extension, check if the file exists
		foreach ($extensions as $extension) {
			$tempPath = $basePath . $extension;
			if (file_exists($tempPath)) {
				return substr_replace($tempPath, '', 0, strlen(ROOT . DS . CUSTOM_DIR));
			}
		}

		return null;
	}


	/**
	 * @param \Awyiss\Model\Entity\Language $language
	 * @return \Awyiss\Model\Entity\Language
	 */
	protected function cleanLanguage(Language $language): Language {
		$blocklistedProperties = [
			'realm',
			'systemOrder',
			'active',
			'deleted',
			'createdBy',
			'createdOn',
			'changedBy',
			'changedOn',
			'deletedBy',
			'deletedOn',
		];

		$language = clone $language;

		foreach ($blocklistedProperties as $property) {
			unset($language->{$property});
		}

		$virtualFields = $language->getVirtual();
		$virtualFields = array_filter($virtualFields, function (string $key): bool {
			return $key !== 'label';
		});

		$language->setVirtual($virtualFields, true);

		return $language;
	}
}
