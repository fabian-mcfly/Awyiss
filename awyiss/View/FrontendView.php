<?php declare(strict_types=1);


namespace Awyiss\View;


use Awyiss\Awyiss;
use Awyiss\Middleware\LocaleMiddleware;
use Awyiss\Model\Entity\Page;
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
	final public const string TYPE_CONTENT = 'content';
	/**
	 * Constant for view file type 'content'
	 *
	 * @var string
	 */
	final public const string TYPE_WIDGET = 'widget';



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

		$twig = $this->getTwig();

		// Find the customer logo
		$logoPath = $this->getLoginLogoPath();
		if ($logoPath) {
			$twig->addGlobal('loginLogoPath', $logoPath);
		}

		// Unset language properties
		$frontendLanguage = LocaleMiddleware::getLanguage();
		if ($frontendLanguage) {
			$timezone = Configure::read('Awyiss.System.' . Awyiss::getRealm() . '.timezone');
			if ($timezone === 'auto') {
				$timezone = $frontendLanguage->timezone;
			}

			$this->addHelper('Time', ['outputTimezone' => $timezone]);

			if ($frontendLanguage->dateFormat) {
				$twig->addGlobal('dateFormat', $frontendLanguage->dateFormat);
			}
			if ($frontendLanguage->timeFormat) {
				$twig->addGlobal('timeFormat', $frontendLanguage->timeFormat);
			}

			$frontendLanguage = $this->cleanLanguage($frontendLanguage);
		}

		$backendLanguage = null;
		if ($this->getRequest()->getSession()->read('Backend.languageShortcode')) {
			$backendLanguage = LocaleMiddleware::getLanguageByShortcode($this->getRequest()->getSession()->read('Backend.languageShortcode'), Awyiss::REALM_BACKEND);
			if ($backendLanguage) {
				$backendLanguage = $this->cleanLanguage($backendLanguage);
			}
		}

		$folder = '/' . (ltrim($this->getRequest()->getAttribute('base'), '/') ?? '');
		if (!str_ends_with($folder, '/')) {
			$folder .= '/';
		}

		$uri = $this->getRequest()->getUri();
		if ($folder !== '/' && !str_starts_with($uri->getPath(), $folder)) {
			$uri = $uri->withPath($folder . ltrim($uri->getPath(), '/'));
		}

		$twig->addGlobal('baseUrl', Router::url('/', true));
		$twig->addGlobal('config', Configure::read());
		$twig->addGlobal('currentBackendLanguage', $backendLanguage);
		$twig->addGlobal('currentLanguage', $frontendLanguage);
		$twig->addGlobal('currentPath', $this->getRequest()->getUri()->getPath());
		$twig->addGlobal('currentUrl', $uri->__toString());
		$twig->addGlobal('designSettings', $this->getDesignVariables(true));
		$twig->addGlobal('environment', Configure::read('debug') ? 'Env-' . Inflector::ucparts(CONFIG_ENV) : null);
		$twig->addGlobal('folder', $folder);
		$twig->addGlobal('languages', LocaleMiddleware::getLanguages());
		$twig->addGlobal('languageShortcode', $frontendLanguage?->shortcode);
		$twig->addGlobal('previewMode', $this->getRequest()->getSession()->read('previewMode', []));
		$twig->addGlobal('webfont', $this->getWebfontData());
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
		$options += ['callbacks' => false, 'cache' => null, 'plugin' => null, 'ignoreMissing' => false];
		if (isset($options['cache'])) {
			$options['cache'] = $this->_contentCache(
				$name,
				$data,
				array_diff_key($options, ['callbacks' => false, 'plugin' => null, 'ignoreMissing' => null])
			);
		}

		$pluginCheck = $options['plugin'] !== false;
		$file = $this->_getContentFileName($name, $pluginCheck);

		if ($file && $options['cache']) {
			return $this->cache(function () use ($file, $data, $options): void {
				echo $this->_renderContent($file, $data, $options);
			}, $options['cache']);
		}

		if ($file) {
			return $this->_renderContent($file, $data, $options);
		}

		if ($options['ignoreMissing']) {
			return '';
		}

		[$plugin, $contentName] = $this->pluginSplit($name, $pluginCheck);
		$paths = iterator_to_array($this->getContentPaths($plugin));
		throw new MissingContentException([$name . $this->_ext, $contentName . $this->_ext], $paths);
	}


	/**
	 * Generate the cache configuration options for a content.
	 *
	 * @param string $name Content name
	 * @param array $data Data
	 * @param array<string, mixed> $options Content options
	 * @return array<string, mixed> Content Cache configuration.
	 * @psalm-return array{key:string, config:string}
	 * @noinspection DuplicatedCode
	 */
	protected function _contentCache(string $name, array $data, array $options): array {
		if (isset($options['cache']['key'], $options['cache']['config'])) {
			/** @psalm-var array{key:string, config:string} $cache */
			$cache = $options['cache'];
			$cache['key'] = 'content_' . $cache['key'];

			return $cache;
		}

		[$plugin, $name] = $this->pluginSplit($name);

		$pluginKey = null;
		if ($plugin) {
			$pluginKey = str_replace('/', '_', Inflector::underscore($plugin));
		}
		$contentKey = str_replace(['\\', '/'], '_', $name);

		$cache = $options['cache'];
		unset($options['cache']);
		$keys = array_merge(
			[$pluginKey, $contentKey],
			array_keys($options),
			array_keys($data)
		);
		$config = [
			'config' => $this->contentCache,
			'key' => implode('_', $keys),
		];
		if (is_array($cache)) {
			$config = $cache + $config;
		}
		$config['key'] = 'content_' . $config['key'];

		/** @var array{config: string, key: string} */
		return $config;
	}


	/**
	 * Finds a content filename, returns false on failure.
	 *
	 * @param string $name The name of the content to find.
	 * @param bool $pluginCheck - if false will ignore the request's plugin if parsed plugin is not loaded
	 * @return string|false Either a string to the content filename or false when one can't be found.
	 */
	protected function _getContentFileName(string $name, bool $pluginCheck = true): string|false {
		[$plugin, $name] = $this->pluginSplit($name, $pluginCheck);

		$name .= $this->_ext;
		foreach ($this->getContentPaths($plugin) as $path) {
			if (is_file($path . $name)) {
				return $path . $name;
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
		$contentPaths = $this->_getSubPaths(static::TYPE_CONTENT);
		foreach ($this->_paths($plugin) as $path) {
			foreach ($contentPaths as $subdir) {
				yield $path . $subdir . DIRECTORY_SEPARATOR;
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
		$options += ['callbacks' => false, 'cache' => null, 'plugin' => null, 'ignoreMissing' => false];
		if (isset($options['cache'])) {
			$options['cache'] = $this->_widgetCache(
				$name,
				$data,
				array_diff_key($options, ['callbacks' => false, 'plugin' => null, 'ignoreMissing' => null])
			);
		}

		$pluginCheck = $options['plugin'] !== false;
		$file = $this->_getWidgetFileName($name, $pluginCheck);
		if ($file && $options['cache']) {
			return $this->cache(function () use ($file, $data, $options): void {
				echo $this->_renderWidget($file, $data, $options);
			}, $options['cache']);
		}
		if ($file) {
			return $this->_renderWidget($file, $data, $options);
		}

		if ($options['ignoreMissing']) {
			return '';
		}

		[$plugin, $widgetName] = $this->pluginSplit($name, $pluginCheck);
		$paths = iterator_to_array($this->getWidgetPaths($plugin));
		throw new MissingWidgetException([$name . $this->_ext, $widgetName . $this->_ext], $paths);
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
			/** @psalm-var array{key:string, config:string} $cache */
			$cache = $options['cache'];
			$cache['key'] = 'widget_' . $cache['key'];

			return $cache;
		}

		[$plugin, $name] = $this->pluginSplit($name);

		$pluginKey = null;
		if ($plugin) {
			$pluginKey = str_replace('/', '_', Inflector::underscore($plugin));
		}
		$widgetKey = str_replace(['\\', '/'], '_', $name);

		$cache = $options['cache'];
		unset($options['cache']);
		$keys = array_merge(
			[$pluginKey, $widgetKey],
			array_keys($options),
			array_keys($data)
		);
		$config = [
			'config' => $this->widgetCache,
			'key' => implode('_', $keys),
		];
		if (is_array($cache)) {
			$config = $cache + $config;
		}
		$config['key'] = 'widget_' . $config['key'];

		/** @var array{config: string, key: string} */
		return $config;
	}


	/**
	 * Finds a widget filename, returns false on failure.
	 *
	 * @param string $name The name of the widget to find.
	 * @param bool $pluginCheck - if false will ignore the request's plugin if parsed plugin is not loaded
	 * @return string|false Either a string to the widget filename or false when one can't be found.
	 */
	protected function _getWidgetFileName(string $name, bool $pluginCheck = true): string|false {
		[$plugin, $name] = $this->pluginSplit($name, $pluginCheck);

		$name .= $this->_ext;
		foreach ($this->getWidgetPaths($plugin) as $path) {
			if (is_file($path . $name)) {
				return $path . $name;
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
		$widgetPaths = $this->_getSubPaths(static::TYPE_WIDGET);
		foreach ($this->_paths($plugin) as $path) {
			foreach ($widgetPaths as $subdir) {
				yield $path . $subdir . DIRECTORY_SEPARATOR;
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
		$current = $this->_current;
		$restore = $this->_currentType;
		$this->_currentType = $type === 'widget' ? static::TYPE_WIDGET : static::TYPE_CONTENT;

		if ($callbacks) {
			$this->dispatchEvent('View.beforeRender', [$file]);
		}

		$widget = $this->_render($file, array_merge($this->viewVars, $data));

		if ($callbacks) {
			$this->dispatchEvent('View.afterRender', [$file, $widget]);
		}

		$this->_currentType = $restore;
		$this->_current = $current;

		return $widget;
	}


	/**
	 * @param bool $allowDesignPreview
	 * @return array
	 */
	protected function getDesignVariables(bool $allowDesignPreview = false): array {
		if ($allowDesignPreview) {
			$designPreviewIdentifier = $this->request->getSession()->read('designPreviewIdentifier');

			$design = null;
			if ($designPreviewIdentifier) {
				$designTable = FactoryLocator::get('Table')->get('Designs');
				/** @var \Awyiss\Model\Entity\Design $design */
				$design = $designTable->find('all')->where([
					'identifier' => $designPreviewIdentifier,
					'in_use' => false,
				])->first();
			}

			if ($design) {
				return $design->settings ?? [];
			}
		}

		/** @var \Awyiss\Middleware\DesignMiddleware $designMiddleware */
		$designMiddleware = $this->getRequest()->getAttribute('design');

		return $designMiddleware?->getDesignVariables() ?? [];
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
		$variables = $this->getDesignVariables();

		if (!$variables) {
			return [];
		}

		$webfontData = [];

		foreach ($variables as $variable => $value) {
			if (!is_array($value) || !isset($value['font']['name'])) {
				continue;
			}

			$webfontData[ $variable ] = [
				'name' => $value['font']['name'],
				'variants' => $value['variants'] ?? [],
			];
		}

		return $webfontData;
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

		if ($this->get('page') && $this->get('page') instanceof Page) {
			$this->set('ogImage', Router::url('/_open-graph-image/id:' . $this->get('page')->id . '/', true));
			$this->set('ogImageWidth', 1200);
			$this->set('ogImageHeight', 680);
			return;
		}

		// Find the customer logo
		$logoPath = $this->getLoginLogoPath();
		if ($logoPath) {
			// If the logo path is found, set the Open Graph image URL
			$this->set('ogImage', Router::url($logoPath, true));
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
