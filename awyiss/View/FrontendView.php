<?php declare(strict_types=1);


namespace Awyiss\View;


use Awyiss\Middleware\LocaleMiddleware;
use Awyiss\Routing\Router;
use Awyiss\Utility\Inflector;
use Awyiss\View\Exception\MissingContentException;
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
	 * The class string to use for the row element
	 *
	 * @var string $rowClass
	 */
	public static string $rowClass = '';


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
	 * @inheritDoc
	 * @return void
	 * @throws \Twig\Error\LoaderError
	 * @throws \Exception
	 */
	public function initialize(): void {
		parent::initialize();

		$this->addHelper('Asset');
		$this->addHelper('Form', [
			'autoSetCustomValidity' => false,
			'errorClass' => 'Error',
			'templates' => 'form_templates',
		]);
		$this->addHelper('Html');
		$this->addHelper('Locale');
		$this->addHelper('Media');

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
			$this->addHelper('Time', ['outputTimezone' => $lo_frontendLanguage->timezone]);

			$lo_frontendLanguage = clone $lo_frontendLanguage;

			foreach ($lo_blocklistedProperties as $ls_property) {
				unset($lo_frontendLanguage->{$ls_property});
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
	}


	/**
	 * @param bool $flex
	 * @return $this
	 */
	public function enableFlexRow(bool $flex = true): static {
		static::$flexRow = $flex;

		return $this;
	}


	/**
	 * @return $this
	 */
	public function disableFlexRow(): static {
		static::$flexRow = false;

		return $this;
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
			return $this->cache(function () use ($ls_file, $data, $la_options): void {
				echo $this->_renderContent($ls_file, $data, $la_options);
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
		$ls_current = $this->_current;
		$ls_restore = $this->_currentType;
		$this->_currentType = static::TYPE_CONTENT;

		if ($options['callbacks']) {
			$this->dispatchEvent('View.beforeRender', [$file]);
		}

		$ls_content = $this->_render($file, array_merge($this->viewVars, $data));

		if ($options['callbacks']) {
			$this->dispatchEvent('View.afterRender', [$file, $ls_content]);
		}

		$this->_currentType = $ls_restore;
		$this->_current = $ls_current;

		return $ls_content;
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
}
