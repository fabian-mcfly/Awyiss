<?php declare(strict_types=1);


namespace Awyiss\View\Helper;


use Awyiss\Awyiss;
use Awyiss\Core\App;
use Awyiss\Utility\Minify\Css;
use Cake\Core\Configure;
use Cake\Log\Log;
use Cake\Routing\Router;
use Cake\View\Helper;
use Exception;
use InvalidArgumentException;
use MatthiasMullie\Minify;
use ScssPhp\ScssPhp\Exception\SassException;
use SplFileInfo;


/**
 * AssetHelper
 * Registers and resolves CSS, JS and font assets, renders the corresponding HTML tags and import maps.
 * Optionally produces minified asset files and sets HTTP/2 preload headers for delivered assets.
 */
class AssetHelper extends Helper {
	/**
	 * An associative array of assets. The keys are the asset names, and the values are arrays of options for each asset.
	 *
	 * @var array $assets
	 */
	protected static array $assets = [
		'all' => [],
		'css' => [
			'critical' => [],
			'nonCritical' => [],
		],
		'cssLayer' => [],
		'js' => [
			'critical' => [],
			'nonCritical' => [],
		],
		'font' => [
			'critical' => [],
			'nonCritical' => [],
		],
	];
	/**
	 * An associative array of checked assets. The keys are the asset names, and the values are the asset paths.
	 *
	 * @var array $checkedAssets
	 */
	protected static array $checkedAssets = [];
	/**
	 * A boolean indicating whether assets should be minified automatically. Defaults to true.
	 *
	 * @var bool $autoMinify
	 */
	protected static bool $autoMinify = true;
	/**
	 * An array of JavaScript modules included in an import map.
	 *
	 * @var array $jsModules
	 */
	protected static array $jsModules = [];
	/**
	 * An array of assets to include in a <noscript> tag.
	 *
	 * @var array $noScriptAssets
	 */
	protected static array $noScriptAssets = [];
	/**
	 * An array of content-specific (S)CSS blocks, indexed by content ID or unique identifier.
	 *
	 * @var array $contentStyleBlocks
	 */
	protected static array $contentStyleBlocks = [];
	/**
	 * The realm of the application. This is used to determine the base path for assets.
	 *
	 * @var string $realm
	 */
	protected static string $realm;
	/**
	 * An associative array of realm folders. The keys are the realm names, and the values are arrays of folder paths for each realm.
	 *
	 * @var array $realmFolders
	 */
	protected static array $realmFolders;
	/**
	 * The default asset configuration.
	 *
	 * @var array<string, array
	 */
	protected static array $assetDefaults = [
		'all' => [],
		'css' => [
			'critical' => [],
			'nonCritical' => [],
		],
		'cssLayer' => [],
		'js' => [
			'critical' => [],
			'nonCritical' => [],
		],
		'font' => [
			'critical' => [],
			'nonCritical' => [],
		],
	];


	/**
	 * @inheritDoc
	 */
	public function initialize(array $config): void {
		static::$realm = Awyiss::getRealm();

		static::$realmFolders = Configure::read('App.paths.assets');

		static::$autoMinify = !Configure::read('debug', false);
	}


	/**
	 * @return bool
	 */
	public function getAutoMinify(): bool {
		return static::$autoMinify;
	}


	/**
	 * @param bool $autoMinify
	 * @return $this
	 */
	public function setAutoMinify(bool $autoMinify = true): static {
		static::$autoMinify = $autoMinify;

		return $this;
	}


	/**
	 * @return string
	 */
	public function getRealm(): string {
		return static::$realm;
	}


	/**
	 * @param string $realm
	 * @return $this
	 */
	public function setRealm(string $realm): static {
		static::$realm = $realm;

		return $this;
	}


	/**
	 * Adds an asset to the assets array.
	 * This method allows you to add an asset to the assets array. Each asset can have several properties:
	 * - minified: A boolean indicating whether the asset is minified.
	 * - critical: A boolean indicating whether the asset is critical.
	 * - priority: An integer indicating the priority of the asset. Higher numbers indicate higher priority.
	 * The minified option defaults to true for production environments, false for development environments.
	 *
	 * @param array|string $asset The asset to add. This can be either a string representing the asset,
	 *  or an array with the asset as the key and an array of options as the value.
	 * @param array $attributes
	 * @param bool $critical (optional) Whether the asset is critical. Defaults to false.
	 * @param bool|null $minified (optional) Whether the asset is minified. Defaults to false.
	 * @param int $priority (optional) The priority of the asset. Defaults to 10.
	 * @return void
	 * @noinspection DuplicatedCode
	 */
	public function add(
		array|string $asset,
		array $attributes = [],
		bool $critical = false,
		?bool $minified = null,
		int $priority = 10
	): void {
		// Determines if the asset is minified based on the provided value or the application's debug configuration
		$minified ??= $this->getAutoMinify();

		// If the provided asset is an array, use it as is. Otherwise, create an array with the asset
		// as the key and an array of options as the value.
		$assets = is_array($asset) ? $asset : [$asset];

		// Iterate over each asset
		foreach ($assets as $key => $value) {
			// If the key is a string, use it as the filename. Otherwise, use the value as the filename.
			$fileName = is_string($key) ? $key : $value;

			// Get the extension of the filename. If the filename is a Google Fonts URL, set the extension to 'css'.
			$extension = pathinfo($fileName, PATHINFO_EXTENSION) ?: (str_contains($fileName, '//fonts.googleapis.com') ? 'css' : '');

			// If the extension is a font type, set the extension to 'font'.
			$extension = in_array($extension, ['woff', 'woff2', 'ttf']) ? 'font' : $extension;

			if (!in_array($extension, ['css', 'js', 'font'])) {
				Log::warning('Unknown asset type: ' . $extension);

				// If debug is enabled, throw the exception.
				if (Configure::read('debug')) {
					throw new InvalidArgumentException(sprintf('Unknown asset type: `%s`', $extension));
				}

				continue;
			}

			// If the filename is already in the 'all' assets array, skip this iteration
			if (array_key_exists($fileName, static::$assets['all'])) {
				continue;
			}

			// If the value is an array, use it as the options. Otherwise, create an array of options with the provided values.
			$options = $this->buildOptions(is_array($value) ? $value : [], $attributes, $minified, $critical, $priority);

			// Add the asset to the 'all' assets array
			static::$assets['all'][ $fileName ] = $options;

			// If the asset is critical, set the key to 'critical'. Otherwise, set it to 'nonCritical'.
			$criticalKey = $options['critical'] ? 'critical' : 'nonCritical';

			// Add the asset to the appropriate assets array based on its extension and criticality
			static::$assets[ $extension ][ $criticalKey ][ $fileName ] = $options;
		}
	}


	/**
	 * Adds a new css layer (single layer or group),
	 * with a given name and priority.
	 *
	 * Layers will be ordered by priority, with the
	 * highest priority last.
	 *
	 * Layers provided as an array will be added
	 * with their internal priority `as-is`.
	 */
	public function addCssLayer(string|array $layer, int $priority = 10): void {
		$layer = is_string($layer) ? $layer : implode(', ', $layer);

		if (!isset(static::$assets['cssLayer'][ $layer ])) {
			static::$assets['cssLayer'][ $layer ] = [
				'layer' => $layer,
				'priority' => $priority,
			];
		}
	}


	/**
	 * Returns the defined css layers
	 */
	public function getCssLayers(): array {
		return static::$assets['cssLayer'];
	}


	/**
	 * Simplified add method for adding assets that will only be included in the <noscript> tag.
	 * Manually added NoScript assets are never critical.
	 *
	 * @param array|string $asset
	 * @param array $attributes
	 * @param bool|null $minified
	 * @param int $priority
	 * @return void
	 * @noinspection DuplicatedCode
	 */
	public function addNoScriptAsset(array|string $asset, array $attributes = [], ?bool $minified = null, int $priority = 10): void {
		// Determines if the asset is minified based on the provided value or the application's debug configuration
		$minified ??= $this->getAutoMinify();

		// If the provided asset is an array, use it as is. Otherwise, create an array with the asset
		// as the key and an array of options as the value.
		$assets = is_array($asset) ? $asset : [$asset];

		foreach ($assets as $key => $value) {
			// If the key is a string, use it as the filename. Otherwise, use the value as the filename.
			$fileName = is_string($key) ? $key : $value;

			// Get the extension of the filename. If the filename is a Google Fonts URL, set the extension to 'css'.
			$extension = pathinfo($fileName, PATHINFO_EXTENSION) ?: (str_contains($fileName, '//fonts.googleapis.com') ? 'css' : '');

			// If the extension is a font type, set the extension to 'font'.
			$extension = in_array($extension, ['woff', 'woff2', 'ttf']) ? 'font' : $extension;

			if (!in_array($extension, ['css', 'js', 'font'])) {
				Log::warning('Unknown asset type: ' . $extension);

				// If debug is enabled, throw the exception.
				if (Configure::read('debug')) {
					throw new InvalidArgumentException(sprintf('Unknown asset type: `%s`', $extension));
				}

				continue;
			}

			if ($extension !== 'css') {
				Log::warning('NoScript assets must be CSS files.');

				continue;
			}

			// If the filename is already in the 'all' assets array, skip this iteration
			if (array_key_exists($fileName, static::$noScriptAssets)) {
				continue;
			}

			// If the value is an array, use it as the options. Otherwise, create an array of options with the provided values.
			$options = $this->buildOptions(is_array($value) ? $value : [], $attributes, $minified, false, $priority);

			static::$noScriptAssets[ $fileName ] = $options;
		}
	}


	/**
	 * Removes an asset from the assets array.
	 * This method removes an asset from the assets array. It first checks if the asset is an array or a string.
	 * If the asset is a string, it is converted to an array. The method then iterates over each asset in the array.
	 * For each asset, it determines the extension of the asset. If the extension is not recognized,
	 *  it tries to determine it from the URL, if it's a URL.
	 * If the asset is a font file (with extension 'woff', 'woff2', or 'ttf'), the extension is set to 'font'.
	 * The method then removes the asset from the 'all', 'critical', and 'nonCritical' arrays in the assets array,
	 *  using the asset's extension and name.
	 *
	 * @param array|string $asset The asset to remove. This can be either a string representing the asset, or an array of assets.
	 * @return void
	 */
	public function remove(array|string $asset): void {
		$assets = is_array($asset) ? $asset : [$asset];

		foreach ($assets as $asset) {
			$extension = pathinfo($asset, PATHINFO_EXTENSION);

			// If the extension is not recognized, try to determine it from the url, if it's a url
			if (empty($extension)) {
				// fonts.googleapis.com is a special case, as it's not a file, but a URL
				if (str_contains($asset, '//fonts.googleapis.com')) {
					$extension = 'css';
				}
			}

			// Special case for font files
			switch ($extension) {
				case 'woff':
				case 'woff2':
				case 'ttf':
					$extension = 'font';
			}

			// Remove the asset from the assets array
			unset(static::$assets['all'][ $asset ]);
			unset(static::$assets[ $extension ]['critical'][ $asset ]);
			unset(static::$assets[ $extension ]['nonCritical'][ $asset ]);
			unset(static::$noScriptAssets[ $asset ]);
		}
	}


	/**
	 * Generates an HTML tag for the given asset.
	 *
	 * If the asset is a font (woff, woff2, ttf), a `<link>`-tag with `rel="preload"` is generated.
	 *
	 * If the asset is a JavaScript file, a `<script>`-tag is generated.
	 * If the 'critical' option of the asset is true, the tag is generated without the `async`-attribute
	 * but with the `defer`-attribute.
	 *
	 * If the asset is a CSS file, a `<link>`-tag with `rel="stylesheet"` is generated.
	 * If the 'critical'-option of the asset is true, the tag is generated without the `onload`-attribute.
	 *
	 * @param string $assetPath The path to the asset. This should be a full URL.
	 * @param array $options An array of options for the asset. This should include a 'critical' key with a boolean value
	 *  indicating whether the asset is critical.
	 * @param bool $lazyLoad (optional) Whether to generate a lazy loading tag for the asset. Defaults to true.
	 * @return string An HTML tag for the asset.
	 */
	public function createAssetTag(string $assetPath, array $options, bool $lazyLoad = true): string {
		// Get the extension of the asset
		$extension = pathinfo($assetPath, PATHINFO_EXTENSION);

		// Generate the additional attributes string
		$additionalAttributes = $this->generateAttributesString($options);

		// Get the nonce from the request attributes
		$nonce = '';
		if ($extension === 'js') {
			$nonce = $this
				->getView()
				->getRequest()
				->getAttribute('cspScriptNonce')
			;
		}
		elseif ($extension === 'css') {
			$nonce = $this
				->getView()
				->getRequest()
				->getAttribute('cspStyleNonce')
			;
		}

		if ($nonce) {
			$nonce = ' nonce="' . $nonce . '"';
		}

		// If the extension is a font type, generate a <link> tag with rel="preload" for the font
		if ($extension === 'woff' || $extension === 'woff2' || $extension === 'ttf') {
			// Generate a <link> tag with rel="preload" for the font
			return '<link' . $nonce . ' rel="preload" href="' . $assetPath . '" as="font" type="font/' . $extension . '" crossorigin'
				. $additionalAttributes . '>' . PHP_EOL
			;
		}

		// If the asset is critical, generate a <script> tag for JavaScript files and a <link> tag with rel="stylesheet" for CSS files
		if ($options['critical'] ?? null) {
			if ($extension === 'js') {
				return '<script' . $nonce . ' defer src="' . $assetPath . '"' . $additionalAttributes . '></script>' . PHP_EOL;
			}

			return '<link' . $nonce . ' rel="stylesheet" type="text/css" href="' . $assetPath . '"' . $additionalAttributes . '>' . PHP_EOL;
		}

		// If the asset is a JavaScript file, generate a <script> tag
		if ($extension === 'js') {
			if ($lazyLoad) {
				return '<script' . $nonce . ' async src="' . $assetPath . '"' . $additionalAttributes . '></script>' . PHP_EOL;
			}

			return '<script' . $nonce . ' defer src="' . $assetPath . '"' . $additionalAttributes . '></script>' . PHP_EOL;
		}

		// If the asset is a CSS file and lazy loading is enabled, generate a <link> tag with rel="preload"
		if ($lazyLoad) {
			return '<link' . $nonce . ' rel="preload" href="' . $assetPath . '" as="style"'
				. $additionalAttributes . ' data-lazyload="true">' . PHP_EOL
			;
		}

		// If none of the above conditions are met, generate a <link> tag with rel="stylesheet" for the asset
		return '<link' . $nonce . ' rel="stylesheet" type="text/css" href="' . $assetPath . '"' . $additionalAttributes . '>' . PHP_EOL;
	}


	/**
	 * Creates a style tag containing the layer definition
	 *
	 * @returns string
	 */
	public function createLayerTag(): string {
		$layers = static::$assets['cssLayer'];

		if (empty($layers)) {
			return '';
		}

		// Sort the layers by priority
		usort($layers, function ($a, $b) {
			return $a['priority'] <=> $b['priority'];
		});

		$layer = '@layer ';
		foreach ($layers as $layerOptions) {
			$layer .= $layerOptions['layer'] . ', ';
		}
		$layer = rtrim($layer, ', ');

		$nonce = $this
			->getView()
			->getRequest()
			->getAttribute('cspStyleNonce')
		;

		return '<style' . ($nonce ? ' nonce="' . $nonce . '"' : '') . '>' . $layer . ';</style>' . PHP_EOL;
	}


	/**
	 * Retrieves the path to an asset, optionally minifying it if required.
	 * If the asset is a full URL, it returns the URL directly.
	 * Otherwise, it constructs the asset path based on the provided options and the application's configuration.
	 * If the asset should be minified, it minifies the asset and returns the path to the minified version.
	 *
	 * @param string $asset The name of the asset.
	 * @param array $options An array of options for retrieving the asset path. Possible keys:
	 *  - `realm`: The realm to use for the asset path. Defaults to the current realm.
	 *  - `minified`: Whether to return the path to the minified version of the asset. Defaults to false.
	 *  - `includeTimestamp`: Whether to include the modification timestamp of the asset in the path. Defaults to true.
	 * @return string|null The path to the asset, or null if the asset is not found.
	 * @throws \Exception
	 */
	public function getAssetPath(string $asset, array $options = []): ?string {
		$realm = $options['realm'] ?? static::$realm;

		// If the asset has already been checked, return the asset path
		if (isset(static::$checkedAssets[ $realm . '__' . $asset ])) {
			return static::$checkedAssets[ $realm . '__' . $asset ];
		}

		// If the asset is a full URL, return it
		if (preg_match('/^((http|https|ftp):\\/\\/|\\/\\/)/', $asset)) {
			static::$checkedAssets[ $realm . '__' . $asset ] = $asset;

			return $asset;
		}

		// If the asset is already a full URL, return it
		$subPath = $extension = pathinfo($asset, PATHINFO_EXTENSION);

		// If the extension is a font type, set the extension to 'font'
		if ($extension === 'woff' || $extension === 'woff2' || $extension === 'ttf') {
			$subPath = 'font';
		}

		foreach (static::$realmFolders[ $realm ] as $key => $folder) {
			$minified = $options['minified'] ?? false;

			$assetPath = $folder . $subPath . '/' . $asset;
			if (!file_exists($assetPath)) {
				continue;
			}

			if (str_ends_with($assetPath, '.min.' . $extension)) {
				$minified = false;
			}

			// Convert the filesystem path to a path relative to the application's base path
			$prePath = rtrim($key === 'awyiss' ? realpath(APP . '..') : realpath(ROOT . DS . CUSTOM_DIR), DS) . DS;
			if (str_starts_with(realpath($assetPath), $prePath)) {
				$relativePath = substr(realpath($assetPath), strlen($prePath));
			}

			// Check if the file is minified and append ".min" to the filename before the extension if it is
			$fileName = substr($relativePath, 0, -strlen($extension));

			// If the file should be minified, append "min" to the filename before the extension
			if ($minified) {
				$fileName .= 'min.';

				$minifiedPath = $this->getMinifiedPath($prePath . $fileName, $extension, $assetPath);

				// Get the file modification time
				$modTime = filemtime($minifiedPath);

				if ($options['localPath'] ?? false) {
					return $minifiedPath;
				}
			}
			else {
				// Get the file modification time
				$modTime = filemtime($assetPath);

				if ($options['localPath'] ?? false) {
					return $assetPath;
				}
			}

			if ($key === 'customer' && str_starts_with($fileName, DS . CUSTOM_DIR . DS)) {
				$fileName = substr($fileName, strlen(DS . CUSTOM_DIR));
			}

			if ($options['includeTimestamp'] ?? true) {
				// Append the modification time to the filename
				$fileName .= $modTime . '.';
			}

			// Generate a URL for the asset using CakePHP's Router and append the modification time to the filename
			return static::$checkedAssets[ $realm . '__' . $asset ] = Router::url('/' . $fileName . $extension, true);
		}

		// If the asset is not found, return null
		static::$checkedAssets[ $realm . '__' . $asset ] = null;


		return null;
	}


	/**
	 * Generates HTML tags for assets based on type for a given criticality.
	 * Optionally includes a <noscript> tag with tags for non-JavaScript assets.
	 *
	 * @param string $type The type of assets to generate tags for. Defaults to 'all'.
	 * @param bool|null $critical (optional) Whether to generate tags for critical assets. If null, tags are generated for all assets.
	 *  Defaults to null.
	 * @param bool $includeNoScript (optional) Whether to include a <noscript> tag with tags for non-JavaScript assets. Defaults to true.
	 * @return string A string of HTML tags for the specified type of assets.
	 * @throws \Exception
	 */
	public function getTags(string $type = 'all', ?bool $critical = null, bool $includeNoScript = true): string {
		$assets = static::$assets[ $type ] ?? [];

		if ($type !== 'all') {
			// Assets of the specified type are split into critical and non-critical assets. Merge them, but keep the keys
			$assets = array_merge($assets['critical'], $assets['nonCritical']);
		}

		$assetTags = $this->createLayerTag();

		// If the assets array is empty, return an empty string
		if (empty($assets) && empty($assetTags)) {
			return '';
		}

		// Sort the assets by priority
		$assets = $this->sortAssetsByPriority($assets);

		$hasLazyloadCss = false;
		foreach ($assets as $asset => $assetOptions) {
			// Check if the asset is a CSS file
			if (!$assetOptions['critical'] && pathinfo($asset, PATHINFO_EXTENSION) === 'css') {
				$hasLazyloadCss = true;
			}

			// Skip the asset if the criticality does not match the specified criticality
			if ($critical !== null && $assetOptions['critical'] !== $critical) {
				continue;
			}

			// Get the asset path
			$assetPath = $this->getAssetPath($asset, $assetOptions);

			// Skip the asset if the asset path is null
			if ($assetPath === null) {
				continue;
			}

			// Generate an HTML tag for the asset and append it to the asset tags string
			$assetTags .= $this->createAssetTag($assetPath, $assetOptions);
		}

		// If the includeNoScript parameter is true, append the result of the getNoScriptTags method to the asset tags string
		if ($includeNoScript && $critical !== true) {
			$assetTags .= $this->getNoScriptTags();
		}

		$nonce = $this
			->getView()
			->getRequest()
			->getAttribute('cspScriptNonce')
		;

		if ($nonce) {
			$nonce = ' nonce="' . $nonce . '"';
		}

		// If there is at least one CSS tag, append the JavaScript code
		if ($hasLazyloadCss && $assetTags) {
			$assetTags .= '<script' . $nonce . '>
				[...document.querySelectorAll(\'link[data-lazyload]\')].map(e=>{!performance.getEntriesByType("resource")'
				. '.some(r=>r.name.includes(e.href))?e.addEventListener("load",e=>{e.target.rel="stylesheet"}):e.rel="stylesheet"});'
				. '</script>' . PHP_EOL
			;
		}


		return $assetTags;
	}


	/**
	 * Generates a string of HTML tags for non-JavaScript assets, wrapped in a <noscript> tag.
	 *
	 * @return string A string of HTML tags for non-JavaScript assets, wrapped in a <noscript> tag.
	 * @throws \Exception
	 */
	public function getNoScriptTags(): string {
		$assets = array_merge(static::$assets['all'], static::$noScriptAssets);

		if (empty($assets)) {
			return '';
		}

		// Sort the assets by priority
		$assets = $this->sortAssetsByPriority($assets);

		$assetTags = '';
		// Iterate over each asset
		foreach ($assets as $asset => $assetOptions) {
			// Skip JavaScript assets
			if (in_array(pathinfo($asset, PATHINFO_EXTENSION), ['woff', 'woff2', 'ttf', 'js'])) {
				continue;
			}

			// Skip critical assets since they will not be lazy loaded
			if ($assetOptions['critical'] !== false) {
				continue;
			}

			// Get the asset path
			$assetPath = $this->getAssetPath($asset, $assetOptions);

			// Skip the asset if the asset path is null
			if ($assetPath === null) {
				continue;
			}

			// Generate an HTML tag for the asset and append it to the asset tags string
			$assetTags .= $this->createAssetTag($assetPath, $assetOptions, false);
		}

		if ($assetTags) {
			// Return the asset tags string, wrapped in a <noscript> tag
			return '<noscript>' . $assetTags . '</noscript>';
		}

		return '';
	}


	/**
	 * Returns a string containing a style tag with the contents of the provided CSS file(s).
	 *
	 * @param array|string $asset
	 * @param array $options
	 * @return string
	 * @throws \Exception
	 */
	public function inlineStyles(array|string $asset, array $options = []): string {
		// If the provided asset is an array, use it as is. Otherwise, create an array with the asset as the key
		// and an array of options as the value.
		$assets = is_array($asset) ? $asset : [$asset];
		$output = '';

		foreach ($assets as $fileName) {
			$extension = pathinfo($fileName, PATHINFO_EXTENSION);

			if ($extension !== 'css') {
				continue;
			}

			$assetPath = $this->getAssetPath($fileName, ['localPath' => true] + $options);
			if (!$assetPath) {
				continue;
			}

			$output .= file_get_contents($assetPath);
		}

		if (!$output) {
			return '';
		}

		if (is_array($options['strReplace'] ?? null)) {
			foreach ($options['strReplace'] as $search => $replace) {
				$output = str_replace($search, $replace, $output);
			}
		}

		$nonce = $this
			->getView()
			->getRequest()
			->getAttribute('cspStyleNonce')
		;

		return '<style' . ($nonce ? ' nonce="' . $nonce . '"' : '') . '>' . $output . '</style>';
	}


	/**
	 * Adds a JavaScript module to the `jsModules` array.
	 * The module can be minified based on the provided or default configuration.
	 *
	 * The minified option defaults to true for production environments, false for development environments.
	 *
	 * Possible options for the module:
	 * - `minified`: A boolean indicating whether the module is minified. Defaults to the opposite of the debug configuration.
	 * - `as`: A string indicating the name to use for the module in the import map. If not provided, the module name will be used.
	 * - `fallback`: A string indicating a fallback for the module. This is used if the module cannot be loaded.
	 *
	 * @param array|string $module The module to add. This can be either a string representing the module,
	 *  or an array with the module as the key and an array of options as the value.
	 * @param bool|null $minified (optional) Whether the module is minified. Defaults to the opposite of the debug configuration.
	 * @return void
	 */
	public function addJsModule(array|string $module, ?bool $minified = null): void {
		// If minified is not set, default to the opposite of the debug configuration
		$minified ??= $this->getAutoMinify();

		// If module is not an array, convert it to an array
		$modules = is_array($module) ? $module : [$module => ['minified' => $minified]];

		// Iterate over each module
		foreach ($modules as $moduleName => $moduleOptions) {
			// If the key is not a string, use the value as the module name
			if (!is_string($moduleName)) {
				$moduleName = $moduleOptions;
				$moduleOptions = ['minified' => $minified];
			}

			// If the module is not already in the jsModules array, add it
			if (array_key_exists($moduleName, static::$jsModules)) {
				continue;
			}

			// Otherwise, use the default minified value
			static::$jsModules[ $moduleName ] = $moduleOptions + ['minified' => $minified];
		}
	}


	/**
	 * Removes a JavaScript module from the `jsModules` array.
	 * This method checks if the provided module name exists in the `jsModules` array. If it does, the method removes it.
	 *
	 * @param string $module The name of the JavaScript module to remove.
	 * @return void
	 */
	public function removeJsModule(string $module): void {
		// If the module is in the jsModules array, remove it
		if (array_key_exists($module, static::$jsModules)) {
			unset(static::$jsModules[ $module ]);
		}
	}


	/**
	 * Get all JavaScript modules.
	 *
	 * @return array
	 */
	public function getJsModules(): array {
		return static::$jsModules;
	}


	/**
	 * Creates an import map for JavaScript modules and returns it as a string.
	 *
	 * This method initializes an import map with an empty 'imports' array. It then iterates over each JavaScript module
	 * stored in the `jsModules` property of the class. For each module, it retrieves the asset path using the `getAssetPath`
	 * method and adds it to the import map.
	 *
	 * If the `includeScriptTag` parameter is true, the method wraps the import map in a script tag of type "importmap" and
	 * returns it as a string. The script tag includes a nonce attribute for Content Security Policy (CSP), which is retrieved
	 * from the request attributes.
	 *
	 * If the `includeScriptTag` parameter is false, the method returns the import map as a JSON string.
	 *
	 * @param bool $includeScriptTag Determines whether to wrap the import map in a script tag. Defaults to true.
	 * @return string The import map as a string. If `includeScriptTag` is true, the import map is wrapped in a script tag.
	 *  Otherwise, the import map is returned as a JSON string.
	 * @throws \Exception
	 */
	public function createImportMap(bool $includeScriptTag = true): string {
		// Initialize an import map with an empty 'imports' array
		$importMap = ['imports' => []];

		// Iterate over each JavaScript module
		foreach (static::$jsModules as $moduleName => $moduleOptions) {
			// Remove the .js extension from the module name
			$cleanModuleName = pathinfo($moduleName, PATHINFO_FILENAME);

			// If the module is minified, remove the ".min" part from the filename
			if (str_ends_with($cleanModuleName, '.min')) {
				$cleanModuleName = substr($cleanModuleName, 0, -4);
			}

			// Files that are deeper than one level must have that nested prepended to the module name
			if (substr_count($moduleName, '/') > 0) {
				$parts = explode('/', $moduleName);

				$folder = $parts[0];
				if (in_array($parts[0], ['Modules', 'Controller'])) {
					// Remove the first part of the path
					array_shift($parts);
				}

				// Remove the last part of the path
				array_pop($parts);

				$cleanModuleName = ($parts ? implode('/', $parts) . '/' : '') . $cleanModuleName;

				if ($folder === 'Controller') {
					$cleanModuleName .= 'Controller';
				}
			}

			if (isset($moduleOptions['as'])) {
				$cleanModuleName = $moduleOptions['as'];
			}

			// Add the module to the import map
			$assetPath = $this->getAssetPath($moduleName, $moduleOptions);

			if (!$assetPath && isset($moduleOptions['fallback'])) {
				$assetPath = $this->getAssetPath($moduleOptions['fallback'], $moduleOptions);
			}
			$importMap['imports'][ $cleanModuleName ] = $assetPath;
		}

		// If includeScriptTag is true, wrap the import map in a script tag
		if ($includeScriptTag) {
			$nonce = $this
				->getView()
				->getRequest()
				->getAttribute('cspScriptNonce')
			;

			if ($nonce) {
				$nonce = ' nonce="' . $nonce . '"';
			}

			if (!empty($importMap['imports'])) {
				return '<script type="importmap"' . $nonce . '>' . json_encode($importMap) . '</script>' . PHP_EOL;
			}

			return '';
		}


		// Otherwise, return the import map as a JSON string
		return json_encode($importMap);
	}


	/**
	 * Returns all final assets.
	 * This method retrieves all assets from the 'all' assets array and generates a path for each asset using the getAssetPath method.
	 *
	 * @return array
	 * @throws \Exception
	 */
	public function getFinalAssets(): array {
		$finalAssets = [];

		// Iterate over each asset in 'all'
		foreach (static::$assets['all'] as $fileName => $assetOptions) {
			// Retrieve the full path of the asset
			$assetPath = $this->getAssetPath($fileName, $assetOptions);

			// If the asset path is not null, add it to the final assets array
			if ($assetPath !== null) {
				$finalAssets[ $fileName ] = ['path' => $assetPath] + $assetOptions;
				ksort($finalAssets[ $fileName ]);
			}
		}

		return $this->sortAssetsByPriority($finalAssets);
	}


	/**
	 * Returns the script nonce from the request attributes.
	 *
	 * @return string|null
	 */
	public function getScriptNonce(): ?string {
		return $this
			->getView()
			->getRequest()
			->getAttribute('cspScriptNonce')
		;
	}


	/**
	 * Returns the style nonce from the request attributes.
	 *
	 * @return string|null
	 */
	public function getStyleNonce(): ?string {
		return $this
			->getView()
			->getRequest()
			->getAttribute('cspStyleNonce')
		;
	}


	/**
	 * Returns all remembered assets.
	 *
	 * @return array
	 */
	public function getAssets(): array {
		return static::$assets;
	}


	/**
	 * Returns all remembered assets for the noscript tag.
	 *
	 * @return array
	 */
	public function getNoScriptAssets(): array {
		return static::$noScriptAssets;
	}


	/**
	 * Clears all remembered assets.
	 * Useful if you want to reset for a new batch of assets,
	 * noscript tags or import maps.
	 *
	 * @return void
	 */
	public function clearAssets(): void {
		static::$assets = static::$assetDefaults;
		static::$checkedAssets = [];
		static::$jsModules = [];
		static::$noScriptAssets = [];
		static::$contentStyleBlocks = [];
	}


	/**
	 * Registers a content-specific (S)CSS block that will be compiled with others.
	 *
	 * @param string $contentId Unique identifier for this content block
	 * @param string $scssContent The SCSS/CSS content
	 * @param int $priority Optional priority for ordering. Higher = later. Default: 10.
	 * @return void
	 */
	public function addContentStyleBlock(string $contentId, string $scssContent, int $priority = 10): void {
		static::$contentStyleBlocks[ $contentId ] = [
			'content' => $scssContent,
			'priority' => $priority,
		];
	}


	/**
	 * Removes a content style block.
	 *
	 * @param string $contentId The content block identifier
	 * @return void
	 */
	public function removeContentStyleBlock(string $contentId): void {
		if (isset(static::$contentStyleBlocks[ $contentId ])) {
			unset(static::$contentStyleBlocks[ $contentId ]);
		}
	}


	/**
	 * Clears all content style blocks.
	 *
	 * @return void
	 */
	public function clearContentStyleBlocks(): void {
		static::$contentStyleBlocks = [];
	}


	/**
	 * Returns all registered content style blocks.
	 *
	 * @return array
	 */
	public function getContentStyleBlocks(): array {
		return static::$contentStyleBlocks;
	}


	/**
	 * Adds the dynamic CSS file to the assets.
	 *
	 * @param int $pageId The current page ID (for caching purposes)
	 * @return void
	 * @throws Exception
	 */
	public function addDynamicContentsStylesheet(int $pageId): void {
		$cssFileName = $this->compileContentStyles($pageId);

		if ($cssFileName) {
			$this->add($cssFileName);
		}
	}


	/**
	 * Compiles and caches all registered content style blocks.
	 * Returns the asset path if successful, null if no blocks registered.
	 *
	 * @param int $pageId The current page ID (for caching purposes)
	 * @return string|null CSS asset filename or null
	 */
	protected function compileContentStyles(int $pageId): ?string {
		if (empty(static::$contentStyleBlocks)) {
			return null;
		}

		// Sort blocks by priority
		$blocks = static::$contentStyleBlocks;
		uasort($blocks, function ($a, $b) {
			return $a['priority'] <=> $b['priority'];
		});

		// Combine content
		$combined = '';
		foreach ($blocks as $contentId => $block) {
			$combined .= $contentId . ' {' . PHP_EOL;
			$combined .= $block['content'] . PHP_EOL;
			$combined .= '}' . PHP_EOL;
		}

		$assetsPath = Configure::read('App.paths.assets.Frontend.customer');
		if (!$assetsPath) {
			return null;
		}

		// Check if file exists
		$fileName = 'page_' . $pageId . '_' . hash('xxh64', $combined);
		$scssPath = $assetsPath . 'scss/_dynamic/';

		// Create the font directory if it doesn't exist
		if (!is_dir($scssPath)) {
			mkdir($scssPath, 0755, true);
		}

		if (!file_exists($scssPath . $fileName . '.scss')) {
			$this->unlinkDynamicAssets($pageId);
			if (!file_put_contents($scssPath . $fileName . '.scss', $combined)) {
				return null;
			}
		}

		$cssPath = $assetsPath . 'css/_dynamic/';
		if (file_exists($cssPath . $fileName . '.css')) {
			return '_dynamic/' . $fileName . '.css';
		}

		/** @var class-string<\Awyiss\Utility\Design\ScssCompiler> $compilerClass */
		$compilerClass = App::className('ScssCompiler', 'Utility/Design');
		try {
			$scssFile = new SplFileInfo($scssPath . $fileName . '.scss');
			/** @var \Awyiss\Middleware\DesignMiddleware $designMiddleware */
			$designMiddleware = Router::getRequest()->getAttribute('design');
			$compilerClass::compileScss($scssFile, $assetsPath, $designMiddleware?->getDesignVariables() ?? [], false);
		}
		catch (Exception | SassException) {
			return null;
		}

		return '_dynamic/' . $fileName . '.css';
	}


	/**
	 * Sets the HTTP2 preload headers for all assets.
	 *
	 * @return void
	 * @throws \Exception
	 */
	public function afterLayout(): void {
		// Get the response object from the view
		$response = $this->getView()->getResponse();

		$header = [];

		// Get all assets and add them to the HTTP2 header
		foreach ($this->getFinalAssets() as $fileName => $assetOptions) {
			$extension = pathinfo($fileName, PATHINFO_EXTENSION);

			// Set the asType based on the extension
			$asType = match ($extension) {
				'css' => 'style',
				'js' => 'script',
				'woff', 'woff2', 'ttf' => 'font',
				default => 'fetch',
			};

			// Add the asset path to the Link header
			$header[] = 'Link: <' . $assetOptions['path'] . '>; rel=preload; as=' . $asType . '; nopush';
		}

		if (!$header) {
			return;
		}

		// Add the Link header to the response
		$response = $response->withHeader('Link', implode(', ', $header));

		// Set the response in the view
		$this->getView()->setResponse($response);
	}


	/**
	 * Sorts the given assets array by priority.
	 *
	 * The higher the priority, the earlier the asset will be loaded.
	 *
	 * @param array $assets The assets array to sort. This is an associative array where the keys are asset names
	 *  and the values are arrays of options for each asset.
	 * @return array The sorted assets array.
	 */
	protected function sortAssetsByPriority(array $assets): array {
		uasort($assets, function ($a, $b) {
			return $b['priority'] <=> $a['priority'];
		});


		return $assets;
	}


	/**
	 * Minifies the asset file located at the given source path and saves the minified content to the target path.
	 * This method uses the `MatthiasMullie\Minify` library to minify CSS and JavaScript files. The type of minifier
	 *  used depends on the type of the asset file.
	 *
	 * If the type is 'css', a Minify\CSS minifier is used. If the type is 'js', a Minify\JS minifier is used. If the type
	 *  is neither 'css' nor 'js', no minification is performed.
	 * The method first creates a new instance of the appropriate minifier for the asset type, passing the source path
	 *  to the minifier's constructor.
	 *
	 * If the minifier instance is not null, the method calls the minifier's minify method, passing the target path.
	 *  The minify method minifies the asset file and saves the minified content to the target path.
	 *
	 * @param string $sourcePath The path to the asset file to minify. This should be a full filesystem path.
	 * @param string $targetPath The path where the minified asset content should be saved. This should be a full filesystem path.
	 * @param string $type The type of the asset file. This should be either 'css' or 'js'.
	 * @return void
	 * @throws \Exception
	 */
	protected function minifyAsset(string $sourcePath, string $targetPath, string $type): void {
		// Create a new minifier instance based on the asset type
		$minifier = match ($type) {
			'css' => new Css($sourcePath),
			'js' => new Minify\JS($sourcePath),
			default => null,
		};

		// If the minifier instance is not null, minify the asset and save the minified content to the target path
		if ($minifier !== null) {
			try {
				if ($type === 'css') {
					$minifier->setMaxImportSize(0);
				}

				$minifier->minify($targetPath);
				chmod($targetPath, 0764);
			}
			catch (Exception $ex) {
				// Write the debug log
				Log::error('Cannot minify asset `' . $sourcePath . '`: ' . $ex->getMessage());

				// If debug is enabled, throw the exception.
				if (Configure::read('debug')) {
					throw $ex;
				}

				// Otherwise show a short message.
				echo 'Cannot minify asset `' . $sourcePath . '`';
				exit;
			}
		}
	}


	/**
	 * @param array $options
	 * @param array $attributes
	 * @param bool $minified
	 * @param bool $critical
	 * @param int $priority
	 * @return array<string, {attributes: array, minified: bool, critical: bool, priority: int, realm: string}>
	 */
	protected function buildOptions(array $options, array $attributes, bool $minified, bool $critical, int $priority): array {
		// Merge the options with a default options array to ensure all keys are present
		$options = array_merge(
			['minified' => $minified, 'critical' => $critical, 'attributes' => $attributes, 'priority' => $priority],
			$options
		);

		if (!is_array($options['attributes'])) {
			$options['attributes'] = [];
		}

		if (isset($options['attributes']['includeTimestamp'])) {
			$options['includeTimestamp'] = $options['attributes']['includeTimestamp'];
			unset($options['attributes']['includeTimestamp']);
		}

		if (isset($options['attributes']['realm'])) {
			$options['realm'] = $options['attributes']['realm'];
			unset($options['attributes']['realm']);
		}

		// Put all options that are attributes into the attributes array
		foreach ($options as $option => $value) {
			if (in_array($option, ['attributes', 'minified', 'critical', 'priority', 'includeTimestamp', 'realm'])) {
				continue;
			}

			$options['attributes'][ $option ] = $value;
			unset($options[ $option ]);
		}

		return $options;
	}


	/**
	 * @param array $options
	 * @return string
	 */
	protected function generateAttributesString(array $options): string {
		$additionalAttributes = '';

		if (empty($options['attributes']) || !is_array($options['attributes'])) {
			return '';
		}

		foreach ($options['attributes'] as $attributeName => $attributeValue) {
			if (!is_string($attributeValue)) {
				$attributeValue = match (true) {
					is_bool($attributeValue) => $attributeValue ? 'true' : 'false',
					is_int($attributeValue) => (string)$attributeValue,
					default => '',
				};
			}

			if ($attributeValue === '') {
				continue;
			}

			$additionalAttributes .= ' ' . $attributeName . '="' . htmlspecialchars($attributeValue, ENT_QUOTES, 'UTF-8') . '"';
		}

		return $additionalAttributes;
	}


	/**
	 * @param string $fileName
	 * @param mixed $extension
	 * @param string $assetPath
	 * @return string
	 * @throws \Exception
	 */
	protected function getMinifiedPath(string $fileName, mixed $extension, string $assetPath): string {
		$minifiedPath = $fileName . $extension;

		/*
		 * If the minified file does not exist,
		 * or if the modification time of the minified file is older than the modification time of the asset file,
		 * minify the asset file.
		 */
		if (!file_exists($minifiedPath) || filemtime($minifiedPath) < filemtime($assetPath)) {
			$this->minifyAsset($assetPath, $minifiedPath, $extension);
		}

		return $minifiedPath;
	}


	/**
	 * Unlinks all dynamic assets for the given page ID to avoid accumulation of unused files.
	 *
	 * @param int $pageId
	 * @return void
	 */
	protected function unlinkDynamicAssets(int $pageId): void {
		$assetsPath = Configure::read('App.paths.assets.Frontend.customer');
		$fileName = 'page_' . $pageId . '_*';
		$paths = [
			$assetsPath . 'scss/_dynamic/' . $fileName . '.scss',
			$assetsPath . 'css/_dynamic/' . $fileName . '.css',
			$assetsPath . 'css/_dynamic/' . $fileName . '.css.map',
		];

		foreach ($paths as $pathPattern) {
			$files = glob($pathPattern);
			foreach ($files ?: [] as $file) {
				unlink($file);
			}
		}
	}
}
