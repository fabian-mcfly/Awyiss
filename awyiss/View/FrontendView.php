<?php declare(strict_types=1);


namespace Awyiss\View;


use Awyiss\Awyiss;
use Awyiss\Middleware\LocaleMiddleware;
use Awyiss\Routing\Router;
use Awyiss\Utility\Inflector;
use Awyiss\View\Exception\MissingContentException;
use Awyiss\View\Exception\MissingWidgetException;
use Cake\Core\Configure;
use Cake\Datasource\FactoryLocator;
use Generator;


/**
 * Frontend View
 */
class FrontendView extends AppView {
	/**
	 * Constant for view file type 'content'
	 *
	 * @var string
	 */
	public const TYPE_CONTENT = 'content';
	/**
	 * Constant for view file type 'content'
	 *
	 * @var string
	 */
	public const TYPE_WIDGET = 'widget';


	/**
	 * The class string to use for the row element
	 *
	 * @var string $rowClass
	 */
	protected static string $rowClass = '';
	/**
	 * The class name for inactive elements in preview mode
	 */
	protected static string $previewModeElementClass = 'AwyissFrontendPreview-InactiveElement';


	/**
	 * The Cache configuration View will use to store cached contents. Changing this will change
	 * the default configuration contents are stored under. You can also choose a cache config
	 * per element.
	 *
	 * @see \Cake\View\View::content()
	 * @var string
	 */
	protected string $contentCache = 'default';
	/**
	 * The Cache configuration View will use to store cached widgets. Changing this will change
	 * the default configuration widgets are stored under. You can also choose a cache config
	 * per element.
	 *
	 * @see \Cake\View\View::widget()
	 * @var string
	 */
	protected string $widgetCache = 'default';


	/**
	 * @inheritDoc
	 * @return void
	 * @throws \Twig\Error\LoaderError
	 * @throws \Exception
	 * @noinspection DuplicatedCode
	 */
	public function initialize(): void {
		parent::initialize();

		$this->addHelper('Asset');
		$this->addHelper('Form', [
			'autoSetCustomValidity' => false,
			'templates' => 'form_templates',
		]);
		$this->addHelper('Html');
		$this->addHelper('Locale');
		$this->addHelper('Media');
		$this->addHelper('Paginator', [
			'templates' => 'paginator_templates',
		]);
		$this->addHelper('Url');

		$lo_twig = $this->getTwig();

		// Find the customer logo
		$ls_logoPath = $this->getLoginLogoPath();
		if ($ls_logoPath) {
			$lo_twig->addGlobal('loginLogoPath', $ls_logoPath);
		}

		$lo_blocklistedProperties = ['realm', 'systemOrder', 'active', 'deleted', 'createdBy', 'createdOn', 'changedBy', 'changedOn', 'deletedBy', 'deletedOn', 'label'];
		// Unset language properties
		$lo_frontendLanguage = LocaleMiddleware::getLanguage();
		if ($lo_frontendLanguage) {
			$ls_timezone = Configure::read('Awyiss.System.' . Awyiss::getRealm() . '.timezone');
			if ($ls_timezone === 'auto') {
				$ls_timezone = $lo_frontendLanguage->timezone;
			}

			$this->addHelper('Time', ['outputTimezone' => $ls_timezone]);

			$lo_frontendLanguage = clone $lo_frontendLanguage;

			foreach ($lo_blocklistedProperties as $ls_property) {
				unset($lo_frontendLanguage->{$ls_property});
			}

			if ($lo_frontendLanguage->dateFormat) {
				$lo_twig->addGlobal('dateFormat', $lo_frontendLanguage->dateFormat);
			}
			if ($lo_frontendLanguage->timeFormat) {
				$lo_twig->addGlobal('timeFormat', $lo_frontendLanguage->timeFormat);
			}
		}

		$lo_twig->addGlobal('baseUrl', Router::url('/', true));
		$lo_twig->addGlobal('config', Configure::read());
		$lo_twig->addGlobal('currentLanguage', $lo_frontendLanguage);
		$lo_twig->addGlobal('currentPath', $this->getRequest()->getUri()->getPath());
		$lo_twig->addGlobal('currentUrl', $this->getRequest()->getUri()->__toString());
		$lo_twig->addGlobal('designSettings', $this->getDesignVariables(true));
		$lo_twig->addGlobal('environment', Configure::read('debug') ? 'Env-' . Inflector::ucparts(CONFIG_ENV) : 'l');
		$lo_twig->addGlobal('folder', '/' . ltrim($this->getRequest()->getAttribute('base'), '/') ?? '');
		$lo_twig->addGlobal('languages', LocaleMiddleware::getLanguages());
		$lo_twig->addGlobal('languageShortcode', $lo_frontendLanguage?->shortcode);
		$lo_twig->addGlobal('previewMode', $this->getRequest()->getSession()->read('previewMode', []));
		$lo_twig->addGlobal('webfont', $this->getWebfontData());

		// Add the webfont timestamp to the global variables
		$ls_webfontFilePath = ROOT . DS . CUSTOM_DIR . DS . 'assets' . DS . 'css' . DS . 'webfonts.css';
		$lo_twig->addGlobal('webfontTimestamp', file_exists($ls_webfontFilePath) ? filemtime($ls_webfontFilePath) : null);
	}


	/**
	 * 1:1 implementation of \Cake\View\View::element() with the only difference being the template path
	 *
	 * @param string $name Name of template file in the `templates/content/` folder
	 * @param array $data Array of data to be made available to the rendered view (i.e. the Content)
	 * @param array<string, mixed> $options Array of options. Possible keys are:
	 * - `cache` - Can either be `true`, to enable caching using the config in View::$contentCache. Or an array
	 *   If an array, the following keys can be used:
	 *   - `config` - Used to store the cached content in a custom cache configuration.
	 *   - `key` - Used to define the key used in the Cache::write(). It will be prefixed with `content_`
	 * - `callbacks` - Set to true to fire beforeRender and afterRender helper callbacks for this content.
	 *   Defaults to false.
	 * - `ignoreMissing` - Used to allow missing contents. Set to true to not throw exceptions.
	 * - `plugin` - setting to false will force to use the application's content from plugin templates, when the
	 *   plugin has content with same name. Defaults to true
	 * @return string Rendered Content
	 * @throws \Awyiss\View\Exception\MissingContentException When a content is missing and `ignoreMissing`
	 *   is false.
	 * @psalm-param array{cache?:array|true, callbacks?:bool, plugin?:string|false, ignoreMissing?:bool} $options
	 */
	public function content(string $name, array $data = [], array $options = []): string {
		$la_options = $options + ['callbacks' => false, 'cache' => null, 'plugin' => null, 'ignoreMissing' => false];
		if (isset($la_options['cache'])) {
			$la_options['cache'] = $this->_contentCache(
				$name,
				$data,
				array_diff_key($la_options, ['callbacks' => false, 'plugin' => null, 'ignoreMissing' => null])
			);
		}

		$lb_pluginCheck = $la_options['plugin'] !== false;
		$ls_file = $this->_getContentFileName($name, $lb_pluginCheck);

		if ($ls_file && $la_options['cache']) {
			$la_data = $data;
			return $this->cache(function () use ($ls_file, $la_data, $la_options): void {
				echo $this->_renderContent($ls_file, $la_data, $la_options);
			}, $la_options['cache']);
		}

		if ($ls_file) {
			return $this->_renderContent($ls_file, $data, $la_options);
		}

		if ($la_options['ignoreMissing']) {
			return '';
		}

		[$ls_plugin, $ls_contentName] = $this->pluginSplit($name, $lb_pluginCheck);
		$la_paths = iterator_to_array($this->getContentPaths($ls_plugin));
		throw new MissingContentException([$name . $this->_ext, $ls_contentName . $this->_ext], $la_paths);
	}


	/**
	 * Generate the cache configuration options for a content.
	 *
	 * @param string $name Content name
	 * @param array $data Data
	 * @param array<string, mixed> $options Content options
	 * @return array<string, mixed> Content Cache configuration.
	 * @psalm-return array{key:string, config:string}
	 */
	protected function _contentCache(string $name, array $data, array $options): array {
		if (isset($options['cache']['key'], $options['cache']['config'])) {
			/** @psalm-var array{key:string, config:string} $la_cache */
			$la_cache = $options['cache'];
			$la_cache['key'] = 'content_' . $la_cache['key'];

			return $la_cache;
		}

		[$ls_plugin, $ls_name] = $this->pluginSplit($name);

		$ls_pluginKey = null;
		if ($ls_plugin) {
			$ls_pluginKey = str_replace('/', '_', Inflector::underscore($ls_plugin));
		}
		$ls_contentKey = str_replace(['\\', '/'], '_', $ls_name);

		$la_cache = $options['cache'];
		/** @noinspection PhpVariableNamingConventionInspection */
		unset($options['cache']);
		$la_keys = array_merge(
			[$ls_pluginKey, $ls_contentKey],
			array_keys($options),
			array_keys($data)
		);
		$la_config = [
			'config' => $this->contentCache,
			'key' => implode('_', $la_keys),
		];
		if (is_array($la_cache)) {
			$la_config = $la_cache + $la_config;
		}
		$la_config['key'] = 'content_' . $la_config['key'];

		/** @var array{config: string, key: string} */
		return $la_config;
	}


	/**
	 * Finds a content filename, returns false on failure.
	 *
	 * @param string $name The name of the content to find.
	 * @param bool $pluginCheck - if false will ignore the request's plugin if parsed plugin is not loaded
	 * @return string|false Either a string to the content filename or false when one can't be found.
	 */
	protected function _getContentFileName(string $name, bool $pluginCheck = true): string|false {
		[$ls_plugin, $ls_name] = $this->pluginSplit($name, $pluginCheck);

		$ls_name .= $this->_ext;
		foreach ($this->getContentPaths($ls_plugin) as $ls_path) {
			if (is_file($ls_path . $ls_name)) {
				return $ls_path . $ls_name;
			}
		}

		return false;
	}


	/**
	 * Get an iterator for content paths.
	 *
	 * @param string|null $plugin The plugin to fetch paths for.
	 * @return \Generator
	 */
	protected function getContentPaths(?string $plugin): Generator {
		$la_contentPaths = $this->_getSubPaths(static::TYPE_CONTENT);
		foreach ($this->_paths($plugin) as $ls_path) {
			foreach ($la_contentPaths as $ls_subdir) {
				yield $ls_path . $ls_subdir . DIRECTORY_SEPARATOR;
			}
		}
	}


	/**
	 * Renders a content and fires the before and afterRender callbacks for it
	 * and writes to the cache if a cache is used
	 *
	 * @param string $file Content file path
	 * @param array $data Data to render
	 * @param array<string, mixed> $options Content options
	 * @return string
	 * @triggers View.beforeRender $this, [$file]
	 * @triggers View.afterRender $this, [$file, $content]
	 */
	protected function _renderContent(string $file, array $data, array $options): string {
		return $this->renderContentOrWidget('content', $options['callbacks'], $file, $data);
	}


	/**
	 * 1:1 implementation of \Cake\View\View::element() with the only difference being the template path
	 *
	 * @param string $name Name of template file in the `templates/widget/` folder
	 * @param array $data Array of data to be made available to the rendered view (i.e. the Widget)
	 * @param array<string, mixed> $options Array of options. Possible keys are:
	 * - `cache` - Can either be `true`, to enable caching using the config in View::$widgetCache. Or an array
	 *   If an array, the following keys can be used:
	 *   - `config` - Used to store the cached widget in a custom cache configuration.
	 *   - `key` - Used to define the key used in the Cache::write(). It will be prefixed with `widget_`
	 * - `callbacks` - Set to true to fire beforeRender and afterRender helper callbacks for this widget.
	 *   Defaults to false.
	 * - `ignoreMissing` - Used to allow missing widgets. Set to true to not throw exceptions.
	 * - `plugin` - setting to false will force to use the application's widget from plugin templates, when the
	 *   plugin has widget with same name. Defaults to true
	 * @return string Rendered Widget
	 * @throws \Awyiss\View\Exception\MissingWidgetException When a widget is missing and `ignoreMissing`
	 *   is false.
	 * @psalm-param array{cache?:array|true, callbacks?:bool, plugin?:string|false, ignoreMissing?:bool} $options
	 */
	public function widget(string $name, array $data = [], array $options = []): string {
		$la_options = $options + ['callbacks' => false, 'cache' => null, 'plugin' => null, 'ignoreMissing' => false];
		if (isset($la_options['cache'])) {
			$la_options['cache'] = $this->_widgetCache(
				$name,
				$data,
				array_diff_key($la_options, ['callbacks' => false, 'plugin' => null, 'ignoreMissing' => null])
			);
		}

		$lb_pluginCheck = $la_options['plugin'] !== false;
		$ls_file = $this->_getWidgetFileName($name, $lb_pluginCheck);
		if ($ls_file && $la_options['cache']) {
			$la_data = $data;
			return $this->cache(function () use ($ls_file, $la_data, $la_options): void {
				echo $this->_renderWidget($ls_file, $la_data, $la_options);
			}, $la_options['cache']);
		}
		if ($ls_file) {
			return $this->_renderWidget($ls_file, $data, $la_options);
		}

		if ($la_options['ignoreMissing']) {
			return '';
		}

		[$ls_plugin, $ls_widgetName] = $this->pluginSplit($name, $lb_pluginCheck);
		$la_paths = iterator_to_array($this->getWidgetPaths($ls_plugin));
		throw new MissingWidgetException([$name . $this->_ext, $ls_widgetName . $this->_ext], $la_paths);
	}


	/**
	 * Generate the cache configuration options for a widget.
	 *
	 * @param string $name Widget name
	 * @param array $data Data
	 * @param array<string, mixed> $options Widget options
	 * @return array<string, mixed> Widget Cache configuration.
	 * @psalm-return array{key:string, config:string}
	 * @noinspection DuplicatedCode
	 */
	protected function _widgetCache(string $name, array $data, array $options): array {
		if (isset($options['cache']['key'], $options['cache']['config'])) {
			/** @psalm-var array{key:string, config:string} $la_cache */
			$la_cache = $options['cache'];
			$la_cache['key'] = 'widget_' . $la_cache['key'];

			return $la_cache;
		}

		[$ls_plugin, $ls_name] = $this->pluginSplit($name);

		$ls_pluginKey = null;
		if ($ls_plugin) {
			$ls_pluginKey = str_replace('/', '_', Inflector::underscore($ls_plugin));
		}
		$ls_widgetKey = str_replace(['\\', '/'], '_', $ls_name);

		$la_cache = $options['cache'];
		/** @noinspection PhpVariableNamingConventionInspection */
		unset($options['cache']);
		$la_keys = array_merge(
			[$ls_pluginKey, $ls_widgetKey],
			array_keys($options),
			array_keys($data)
		);
		$la_config = [
			'config' => $this->widgetCache,
			'key' => implode('_', $la_keys),
		];
		if (is_array($la_cache)) {
			$la_config = $la_cache + $la_config;
		}
		$la_config['key'] = 'widget_' . $la_config['key'];

		/** @var array{config: string, key: string} */
		return $la_config;
	}


	/**
	 * Finds a widget filename, returns false on failure.
	 *
	 * @param string $name The name of the widget to find.
	 * @param bool $pluginCheck - if false will ignore the request's plugin if parsed plugin is not loaded
	 * @return string|false Either a string to the widget filename or false when one can't be found.
	 */
	protected function _getWidgetFileName(string $name, bool $pluginCheck = true): string|false {
		[$ls_plugin, $ls_name] = $this->pluginSplit($name, $pluginCheck);

		$ls_name .= $this->_ext;
		foreach ($this->getWidgetPaths($ls_plugin) as $ls_path) {
			if (is_file($ls_path . $ls_name)) {
				return $ls_path . $ls_name;
			}
		}

		return false;
	}


	/**
	 * Get an iterator for widget paths.
	 *
	 * @param string|null $plugin The plugin to fetch paths for.
	 * @return \Generator
	 */
	protected function getWidgetPaths(?string $plugin): Generator {
		$la_widgetPaths = $this->_getSubPaths(static::TYPE_WIDGET);
		foreach ($this->_paths($plugin) as $ls_path) {
			foreach ($la_widgetPaths as $ls_subdir) {
				yield $ls_path . $ls_subdir . DIRECTORY_SEPARATOR;
			}
		}
	}


	/**
	 * Renders a widget and fires the before and afterRender callbacks for it
	 * and writes to the cache if a cache is used
	 *
	 * @param string $file Widget file path
	 * @param array $data Data to render
	 * @param array<string, mixed> $options Widget options
	 * @return string
	 * @triggers View.beforeRender $this, [$file]
	 * @triggers View.afterRender $this, [$file, $widget]
	 */
	protected function _renderWidget(string $file, array $data, array $options): string {
		return $this->renderContentOrWidget('widget', $options['callbacks'], $file, $data);
	}


	/**
	 * @param string $type
	 * @param bool $callbacks
	 * @param string $file
	 * @param array $data
	 * @return string
	 */
	protected function renderContentOrWidget(string $type, bool $callbacks, string $file, array $data): string {
		$ls_current = $this->_current;
		$ls_restore = $this->_currentType;
		$this->_currentType = $type === 'widget' ? static::TYPE_WIDGET : static::TYPE_CONTENT;

		if ($callbacks) {
			$this->dispatchEvent('View.beforeRender', [$file]);
		}

		$ls_widget = $this->_render($file, array_merge($this->viewVars, $data));

		if ($callbacks) {
			$this->dispatchEvent('View.afterRender', [$file, $ls_widget]);
		}

		$this->_currentType = $ls_restore;
		$this->_current = $ls_current;

		return $ls_widget;
	}


	/**
	 * @param bool $allowDesignPreview
	 * @return array
	 */
	protected function getDesignVariables(bool $allowDesignPreview = false): array {
		if ($allowDesignPreview) {
			$ls_designPreviewIdentifier = $this->request->getSession()->read('designPreviewIdentifier');

			$lo_design = null;
			if ($ls_designPreviewIdentifier) {
				$lo_designTable = FactoryLocator::get('Table')->get('Designs');
				/** @var \Awyiss\Model\Entity\Design $lo_design */
				$lo_design = $lo_designTable->find('all')->where([
					'identifier' => $ls_designPreviewIdentifier,
					'in_use' => false,
				])->first();
			}

			if ($lo_design) {
				return $lo_design->settings ?? [];
			}
		}

		/** @var \Awyiss\Middleware\DesignMiddleware $lo_designMiddleware */
		$lo_designMiddleware = $this->getRequest()->getAttribute('design');

		return $lo_designMiddleware?->getDesignVariables() ?? [];
	}


	/**
	 * @return string
	 */
	public static function getRowClass(): string {
		return self::$rowClass;
	}


	/**
	 * @param string $rowClass
	 * @return void
	 */
	public static function setRowClass(string $rowClass): void {
		self::$rowClass = $rowClass;
	}


	/**
	 * Get the data for the webfont from the design settings
	 *
	 * @return array
	 */
	protected function getWebfontData(): array {
		$la_variables = $this->getDesignVariables();

		if (!$la_variables) {
			return [];
		}

		$la_webfontData = [];

		foreach ($la_variables as $ls_variable => $lx_value) {
			if (!is_array($lx_value) || !isset($lx_value['font']['name'])) {
				continue;
			}

			$la_webfontData[ $ls_variable ] = [
				'name' => $lx_value['font']['name'],
				'variants' => $lx_value['variants'] ?? [],
			];
		}

		return $la_webfontData;
	}


	/**
	 * @inheritDoc
	 */
	public function renderLayout(string $content, ?string $layout = null): string {
		$this->setOgImage();

		return parent::renderLayout($content, $layout);
	}


	/**
	 * @return void
	 */
	protected function setOgImage(): void {
		if ($this->fetch('ogImage') || $this->get('ogImage')) {
			return;
		}

		// Find the customer logo
		$ls_logoPath = $this->getLoginLogoPath();
		if ($ls_logoPath) {
			// If the logo path is found, set the Open Graph image URL
			$this->set('ogImage', Router::url('/', true) . $ls_logoPath);
		}
	}


	/**
	 * @return string
	 */
	public static function getPreviewModeElementClass(): string {
		return self::$previewModeElementClass;
	}


	/**
	 * @param string $class
	 * @return void
	 */
	public static function setPreviewModeElementClass(string $class): void {
		self::$previewModeElementClass = $class;
	}
}
