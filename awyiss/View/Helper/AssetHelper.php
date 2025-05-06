<?php declare(strict_types=1);


namespace Awyiss\View\Helper;


use Awyiss\Awyiss;
use Awyiss\Utility\Minify\Css;
use Cake\Core\Configure;
use Cake\Log\Log;
use Cake\Routing\Router;
use Cake\View\Helper;
use Exception;
use InvalidArgumentException;
use MatthiasMullie\Minify;


/**
 * AssetHelper is a class that extends the Helper class.
 * This class is used to manage and manipulate assets in a web application.
 * It provides methods for adding assets, generating HTML tags for assets, minifying assets, and more.
 *
 * The class maintains an array of assets, which can be of various types (e.g., CSS, JavaScript, Fonts).
 * Each asset can have several properties, such as whether it is minified or critical, and its priority.
 * The class also provides methods for sorting assets by priority and for generating fallback content
 * for users who have JavaScript disabled in their browser.
 *
 * @extends \Cake\View\Helper
 */
class AssetHelper extends Helper {
	/**
	 * @var array $assets An associative array of assets. The keys are the asset names, and the values are arrays of options for each asset.
	 */
	protected static array $assets = [
		'all' => [],
		'css' => [
			'critical' => [],
			'nonCritical' => [],
		],
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
	 * @var array $checkedAssets An associative array of checked assets. The keys are the asset names, and the values are the asset paths.
	 */
	protected static array $checkedAssets = [];
	/**
	 * @var bool $autoMinify A boolean indicating whether assets should be minified automatically. Defaults to true.
	 */
	protected static bool $autoMinify = true;
	/**
	 * @var array $jsModules An array of JavaScript modules included in an import map.
	 */
	protected static array $jsModules = [];
	/**
	 * @var array $noScriptAssets An array of assets to include in a <noscript> tag.
	 */
	protected static array $noScriptAssets = [];
	/**
	 * @var string $realm The realm of the application. This is used to determine the base path for assets.
	 */
	protected static string $realm;
	/**
	 * @var array $realmFolders An associative array of realm folders. The keys are the realm names, and the values are arrays of folder paths for each realm.
	 */
	protected static array $realmFolders;
	/**
	 * @var \Cake\View\Helper|null
	 */
	protected static array $assetDefaults = [
		'all' => [],
		'css' => [
			'critical' => [],
			'nonCritical' => [],
		],
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
	 *
	 * The minified option defaults to true for production environments, false for development environments.
	 *
	 * @param array|string $asset The asset to add. This can be either a string representing the asset, or an array with the asset as the key and an array of options as the value.
	 * @param array $attributes
	 * @param bool $critical (optional) Whether the asset is critical. Defaults to false.
	 * @param bool|null $minified (optional) Whether the asset is minified. Defaults to false.
	 * @param int $priority (optional) The priority of the asset. Defaults to 10.
	 * @return void
	 */
	public function add(array|string $asset, array $attributes = [], bool $critical = false, ?bool $minified = null, int $priority = 10): void {
		// Determines if the asset is minified based on the provided value or the application's debug configuration
		$lb_minified = $minified ?? $this->getAutoMinify();

		// If the provided asset is an array, use it as is. Otherwise, create an array with the asset as the key and an array of options as the value.
		$la_assets = is_array($asset) ? $asset : [$asset];

		// Iterate over each asset
		foreach ($la_assets as $lx_key => $lx_value) {
			// If the key is a string, use it as the filename. Otherwise, use the value as the filename.
			$ls_fileName = is_string($lx_key) ? $lx_key : $lx_value;

			// Get the extension of the filename. If the filename is a Google Fonts URL, set the extension to 'css'.
			$ls_extension = pathinfo($ls_fileName, PATHINFO_EXTENSION) ?: (str_contains($ls_fileName, '//fonts.googleapis.com') ? 'css' : '');

			// If the extension is a font type, set the extension to 'font'.
			$ls_extension = in_array($ls_extension, ['woff', 'woff2', 'ttf']) ? 'font' : $ls_extension;

			if (!in_array($ls_extension, ['css', 'js', 'font'])) {
				Log::warning('Unknown asset type: ' . $ls_extension);

				// If debug is enabled, throw the exception.
				if (Configure::read('debug')) {
					throw new InvalidArgumentException(sprintf('Unknown asset type: `%s`', $ls_extension));
				}

				continue;
			}

			// If the filename is already in the 'all' assets array, skip this iteration
			if (array_key_exists($ls_fileName, static::$assets['all'])) {
				continue;
			}

			// If the value is an array, use it as the options. Otherwise, create an array of options with the provided values.
			$la_options = $this->buildOptions(is_array($lx_value) ? $lx_value : [], $attributes, $lb_minified, $critical, $priority);

			// Add the asset to the 'all' assets array
			static::$assets['all'][ $ls_fileName ] = $la_options;

			// If the asset is critical, set the key to 'critical'. Otherwise, set it to 'nonCritical'.
			$ls_key = $la_options['critical'] ? 'critical' : 'nonCritical';

			// Add the asset to the appropriate assets array based on its extension and criticality
			static::$assets[ $ls_extension ][ $ls_key ][ $ls_fileName ] = $la_options;
		}
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
	 */
	public function addNoScriptAsset(array|string $asset, array $attributes = [], ?bool $minified = null, int $priority = 10): void {
		// Determines if the asset is minified based on the provided value or the application's debug configuration
		$lb_minified = $minified ?? $this->getAutoMinify();

		// If the provided asset is an array, use it as is. Otherwise, create an array with the asset as the key and an array of options as the value.
		$la_assets = is_array($asset) ? $asset : [$asset];

		foreach ($la_assets as $lx_key => $lx_value) {
			// If the key is a string, use it as the filename. Otherwise, use the value as the filename.
			$ls_fileName = is_string($lx_key) ? $lx_key : $lx_value;

			// Get the extension of the filename. If the filename is a Google Fonts URL, set the extension to 'css'.
			$ls_extension = pathinfo($ls_fileName, PATHINFO_EXTENSION) ?: (str_contains($ls_fileName, '//fonts.googleapis.com') ? 'css' : '');

			// If the extension is a font type, set the extension to 'font'.
			$ls_extension = in_array($ls_extension, ['woff', 'woff2', 'ttf']) ? 'font' : $ls_extension;

			if (!in_array($ls_extension, ['css', 'js', 'font'])) {
				Log::warning('Unknown asset type: ' . $ls_extension);

				// If debug is enabled, throw the exception.
				if (Configure::read('debug')) {
					throw new InvalidArgumentException(sprintf('Unknown asset type: `%s`', $ls_extension));
				}

				continue;
			}

			if ($ls_extension !== 'css') {
				Log::warning('NoScript assets must be CSS files.');

				continue;
			}

			// If the filename is already in the 'all' assets array, skip this iteration
			if (array_key_exists($ls_fileName, static::$noScriptAssets)) {
				continue;
			}

			// If the value is an array, use it as the options. Otherwise, create an array of options with the provided values.
			$la_options = $this->buildOptions(is_array($lx_value) ? $lx_value : [], $attributes, $lb_minified, false, $priority);

			static::$noScriptAssets[ $ls_fileName ] = $la_options;
		}
	}


	/**
	 * Removes an asset from the assets array.
	 * This method removes an asset from the assets array. It first checks if the asset is an array or a string.
	 * If the asset is a string, it is converted to an array. The method then iterates over each asset in the array.
	 * For each asset, it determines the extension of the asset. If the extension is not recognized, it tries to determine it from the URL, if it's a URL.
	 * If the asset is a font file (with extension 'woff', 'woff2', or 'ttf'), the extension is set to 'font'.
	 * The method then removes the asset from the 'all', 'critical', and 'nonCritical' arrays in the assets array, using the asset's extension and name.
	 *
	 * @param array|string $asset The asset to remove. This can be either a string representing the asset, or an array of assets.
	 * @return void
	 */
	public function remove(array|string $asset): void {
		$la_assets = is_array($asset) ? $asset : [$asset];

		foreach ($la_assets as $ls_asset) {
			$ls_extension = pathinfo($ls_asset, PATHINFO_EXTENSION);

			// If the extension is not recognized, try to determine it from the url, if it's an url
			if (empty($ls_extension)) {
				// fonts.googleapis.com is a special case, as it's not a file, but a URL
				if (str_contains($ls_asset, '//fonts.googleapis.com')) {
					$ls_extension = 'css';
				}
			}

			// Special case for font files
			switch ($ls_extension) {
				case 'woff':
				case 'woff2':
				case 'ttf':
					$ls_extension = 'font';
			}

			// Remove the asset from the assets array
			unset(static::$assets['all'][ $ls_asset ]);
			unset(static::$assets[ $ls_extension ]['critical'][ $ls_asset ]);
			unset(static::$assets[ $ls_extension ]['nonCritical'][ $ls_asset ]);
			unset(static::$noScriptAssets[ $ls_asset ]);
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
	 * @param array $options An array of options for the asset. This should include a 'critical' key with a boolean value indicating whether the asset is critical.
	 * @param bool $lazyLoad (optional) Whether to generate a lazy loading tag for the asset. Defaults to true.
	 * @return string An HTML tag for the asset.
	 */
	public function createAssetTag(string $assetPath, array $options, bool $lazyLoad = true): string {
		// Get the extension of the asset
		$ls_extension = pathinfo($assetPath, PATHINFO_EXTENSION);

		// Generate the additional attributes string
		$ls_additionalAttributes = $this->generateAttributesString($options);

		// Get the nonce from the request attributes
		$ls_nonce = '';
		if ($ls_extension === 'js') {
			$ls_nonce = $this->getView()->getRequest()->getAttribute('cspScriptNonce');
		}
		elseif ($ls_extension === 'css') {
			$ls_nonce = $this->getView()->getRequest()->getAttribute('cspStyleNonce');
		}

		if ($ls_nonce) {
			$ls_nonce = ' nonce="' . $ls_nonce . '"';
		}

		// If the extension is a font type, generate a <link> tag with rel="preload" for the font
		if ($ls_extension === 'woff' || $ls_extension === 'woff2' || $ls_extension === 'ttf') {
			// Generate a <link> tag with rel="preload" for the font
			return '<link' . $ls_nonce . ' rel="preload" href="' . $assetPath . '" as="font" type="font/' . $ls_extension . '" crossorigin' . $ls_additionalAttributes . '>' . PHP_EOL;
		}

		// If the asset is critical, generate a <script> tag for JavaScript files and a <link> tag with rel="stylesheet" for CSS files
		if ($options['critical']) {
			if ($ls_extension === 'js') {
				return '<script' . $ls_nonce . ' defer src="' . $assetPath . '"' . $ls_additionalAttributes . '></script>' . PHP_EOL;
			}

			return '<link' . $ls_nonce . ' rel="stylesheet" type="text/css" href="' . $assetPath . '"' . $ls_additionalAttributes . '>' . PHP_EOL;
		}

		// If the asset is a JavaScript file, generate a <script> tag
		if ($ls_extension === 'js') {
			if ($lazyLoad) {
				return '<script' . $ls_nonce . ' async src="' . $assetPath . '"' . $ls_additionalAttributes . '></script>' . PHP_EOL;
			}

			return '<script' . $ls_nonce . ' defer src="' . $assetPath . '"' . $ls_additionalAttributes . '></script>' . PHP_EOL;
		}

		// If the asset is a CSS file and lazy loading is enabled, generate a <link> tag with rel="preload"
		if ($lazyLoad) {
			return '<link' . $ls_nonce . ' rel="preload" href="' . $assetPath . '" as="style"' . $ls_additionalAttributes . ' data-lazyload="true">' . PHP_EOL;
		}

		// If none of the above conditions are met, generate a <link> tag with rel="stylesheet" for the asset
		return '<link' . $ls_nonce . ' rel="stylesheet" type="text/css" href="' . $assetPath . '"' . $ls_additionalAttributes . '>' . PHP_EOL;
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
		// If the asset has already been checked, return the asset path
		if (isset(static::$checkedAssets[ $asset ])) {
			return static::$checkedAssets[ $asset ];
		}

		// If the asset is a full URL, return it
		if (preg_match('/^((http|https|ftp):\\/\\/|\\/\\/)/', $asset)) {
			static::$checkedAssets[ $asset ] = $asset;

			return $asset;
		}

		// If the asset is already a full URL, return it
		$ls_subPath = $ls_extension = pathinfo($asset, PATHINFO_EXTENSION);

		// If the extension is a font type, set the extension to 'font'
		if ($ls_extension === 'woff' || $ls_extension === 'woff2' || $ls_extension === 'ttf') {
			$ls_subPath = 'font';
		}

		$ls_realm = $options['realm'] ?? static::$realm;

		foreach (static::$realmFolders[ $ls_realm ] as $ls_key => $ls_folder) {
			$lb_minified = $options['minified'] ?? false;

			$ls_assetPath = $ls_folder . $ls_subPath . '/' . $asset;
			if (!file_exists($ls_assetPath)) {
				continue;
			}

			if (str_ends_with($ls_assetPath, '.min.' . $ls_extension)) {
				$lb_minified = false;
			}

			// Convert the filesystem path to a path relative to the application's base path
			$ls_relativePath = str_replace(realpath(ROOT), '', realpath($ls_assetPath));

			// Check if the file is minified and append ".min" to the filename before the extension if it is
			$ls_fileName = substr($ls_relativePath, 0, -strlen($ls_extension));

			// If the file should be minified, append "min" to the filename before the extension
			if ($lb_minified) {
				$ls_fileName .= 'min.';

				$ls_minifiedPath = $this->getMinifiedPath($ls_fileName, $ls_extension, $ls_assetPath);

				// Get the file modification time
				$li_modTime = filemtime($ls_minifiedPath);

				if ($options['localPath'] ?? false) {
					return $ls_minifiedPath;
				}
			}
			else {
				// Get the file modification time
				$li_modTime = filemtime($ls_assetPath);

				if ($options['localPath'] ?? false) {
					return $ls_assetPath;
				}
			}

			if ($ls_key === 'customer' && str_starts_with($ls_fileName, DS . CUSTOM_DIR . DS)) {
				$ls_fileName = substr($ls_fileName, strlen(DS . CUSTOM_DIR));
			}

			if ($options['includeTimestamp'] ?? true) {
				// Append the modification time to the filename
				$ls_fileName .= $li_modTime . '.';
			}

			// Generate a URL for the asset using CakePHP's Router and ppend the modification time to the filename
			return static::$checkedAssets[ $asset ] = Router::url($ls_fileName . $ls_extension, true);
		}

		// If the asset is not found, return null
		static::$checkedAssets[ $asset ] = null;


		return null;
	}


	/**
	 * Generates HTML tags for assets based on type for a given criticality.
	 * Optionally includes a <noscript> tag with tags for non-JavaScript assets.
	 *
	 * @param string $type The type of assets to generate tags for. Defaults to 'all'.
	 * @param bool|null $critical (optional) Whether to generate tags for critical assets. If null, tags are generated for all assets. Defaults to null.
	 * @param bool $includeNoScript (optional) Whether to include a <noscript> tag with tags for non-JavaScript assets. Defaults to true.
	 * @return string A string of HTML tags for the specified type of assets.
	 * @throws \Exception
	 */
	public function getTags(string $type = 'all', ?bool $critical = null, bool $includeNoScript = true): string {
		$la_assets = static::$assets[ $type ] ?? [];

		if ($type !== 'all') {
			// Assets of the specified type are split into critical and non-critical assets. Merge them, but keep the keys
			$la_assets = array_merge($la_assets['critical'], $la_assets['nonCritical']);
		}

		// If the assets array is empty, return an empty string
		if (empty($la_assets)) {
			return '';
		}

		// Sort the assets by priority
		$la_assets = $this->sortAssetsByPriority($la_assets);

		$ls_assetTags = '';
		$lb_hasLazyloadCss = false;
		foreach ($la_assets as $ls_asset => $la_options) {
			// Check if the asset is a CSS file
			if (!$la_options['critical'] && pathinfo($ls_asset, PATHINFO_EXTENSION) === 'css') {
				$lb_hasLazyloadCss = true;
			}

			// Skip the asset if the criticality does not match the specified criticality
			if ($critical !== null && $la_options['critical'] !== $critical) {
				continue;
			}

			// Get the asset path
			$ls_assetPath = $this->getAssetPath($ls_asset, $la_options);

			// Skip the asset if the asset path is null
			if ($ls_assetPath === null) {
				continue;
			}

			// Generate an HTML tag for the asset and append it to the asset tags string
			$ls_assetTags .= $this->createAssetTag($ls_assetPath, $la_options);
		}

		// If the includeNoScript parameter is true, append the result of the getNoScriptTags method to the asset tags string
		if ($includeNoScript && $critical !== true) {
			$ls_assetTags .= $this->getNoScriptTags();
		}

		$ls_nonce = $this->getView()->getRequest()->getAttribute('cspScriptNonce');

		if ($ls_nonce) {
			$ls_nonce = ' nonce="' . $ls_nonce . '"';
		}

		// If there is at least one CSS tag, append the JavaScript code
		if ($lb_hasLazyloadCss && $ls_assetTags) {
			$ls_assetTags .= '<script' . $ls_nonce . '>
				[...document.querySelectorAll(\'link[data-lazyload]\')].map(e=>{!performance.getEntriesByType("resource").some(r=>r.name.includes(e.href))?e.addEventListener("load",e=>{e.target.rel="stylesheet"}):e.rel="stylesheet"});
			</script>' . PHP_EOL;
		}


		return $ls_assetTags;
	}


	/**
	 * Generates a string of HTML tags for non-JavaScript assets, wrapped in a <noscript> tag.
	 *
	 * @return string A string of HTML tags for non-JavaScript assets, wrapped in a <noscript> tag.
	 * @throws \Exception
	 */
	public function getNoScriptTags(): string {
		$la_assets = array_merge(static::$assets['all'], static::$noScriptAssets);

		if (empty($la_assets)) {
			return '';
		}

		// Sort the assets by priority
		$la_assets = $this->sortAssetsByPriority($la_assets);

		$ls_assetTags = '';
		// Iterate over each asset
		foreach ($la_assets as $ls_asset => $la_options) {
			// Skip JavaScript assets
			if (in_array(pathinfo($ls_asset, PATHINFO_EXTENSION), ['woff', 'woff2', 'ttf', 'js'])) {
				continue;
			}

			// Skip critical assets since they will not be lazy loaded
			if ($la_options['critical'] !== false) {
				continue;
			}

			// Get the asset path
			$ls_assetPath = $this->getAssetPath($ls_asset, $la_options);

			// Skip the asset if the asset path is null
			if ($ls_assetPath === null) {
				continue;
			}

			// Generate an HTML tag for the asset and append it to the asset tags string
			$ls_assetTags .= $this->createAssetTag($ls_assetPath, $la_options, false);
		}

		if ($ls_assetTags) {
			// Return the asset tags string, wrapped in a <noscript> tag
			return '<noscript>' . $ls_assetTags . '</noscript>';
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
		// If the provided asset is an array, use it as is. Otherwise, create an array with the asset as the key and an array of options as the value.
		$la_assets = is_array($asset) ? $asset : [$asset];
		$ls_output = '';

		foreach ($la_assets as $ls_fileName) {
			$ls_extension = pathinfo($ls_fileName, PATHINFO_EXTENSION);

			if ($ls_extension !== 'css') {
				continue;
			}

			$ls_assetPath = $this->getAssetPath($ls_fileName, ['localPath' => true] + $options);
			if (!$ls_assetPath) {
				continue;
			}

			$ls_output .= file_get_contents($ls_assetPath);
		}

		if (!$ls_output) {
			return '';
		}

		if (is_array($options['strReplace'] ?? null)) {
			foreach ($options['strReplace'] as $ls_search => $ls_replace) {
				$ls_output = str_replace($ls_search, $ls_replace, $ls_output);
			}
		}

		$ls_nonce = $this->getView()->getRequest()->getAttribute('cspStyleNonce');

		return '<style' . ($ls_nonce ? ' nonce="' . $ls_nonce . '"' : '') . '>' . $ls_output . '</style>';
	}


	/**
	 * Adds a JavaScript module to the `jsModules` array.
	 * The module can be minified based on the provided or default configuration.
	 *
	 * The minified option defaults to true for production environments, false for development environments.
	 *
	 * @param array|string $module The module to add. This can be either a string representing the module, or an array with the module as the key and an array of options as the
	 * 	value.
	 * @param bool|null $minified (optional) Whether the module is minified. Defaults to the opposite of the debug configuration.
	 * @return void
	 */
	public function addJsModule(array|string $module, ?bool $minified = null): void {
		// If minified is not set, default to the opposite of the debug configuration
		$lb_minified = $minified ?? $this->getAutoMinify();

		// If module is not an array, convert it to an array
		$la_modules = is_array($module) ? $module : [$module => ['minified' => $lb_minified]];

		// Iterate over each module
		foreach ($la_modules as $ls_moduleName => $la_moduleOptions) {
			// If the key is not a string, use the value as the module name
			if (!is_string($ls_moduleName)) {
				$ls_moduleName = $la_moduleOptions;
				$la_moduleOptions = ['minified' => $lb_minified];
			}

			// If the module is not already in the jsModules array, add it
			if (array_key_exists($ls_moduleName, static::$jsModules)) {
				continue;
			}

			// If module options is an array and contains a 'minified' key, use the provided options
			if (is_array($la_moduleOptions) && array_key_exists('minified', $la_moduleOptions)) {
				static::$jsModules[ $ls_moduleName ] = $la_moduleOptions;
				continue;
			}

			// Otherwise, use the default minified value
			static::$jsModules[ $ls_moduleName ] = ['minified' => $lb_minified];
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
	 * 	Otherwise, the import map is returned as a JSON string.
	 * @throws \Exception
	 */
	public function createImportMap(bool $includeScriptTag = true): string {
		// Initialize an import map with an empty 'imports' array
		$la_importMap = ['imports' => []];

		// Iterate over each JavaScript module
		foreach (static::$jsModules as $ls_moduleName => $la_options) {
			// Remove the .js extension from the module name
			$ls_cleanModuleName = pathinfo($ls_moduleName, PATHINFO_FILENAME);

			// If the module is minified, remove the ".min" part from the filename
			if (str_ends_with($ls_cleanModuleName, '.min')) {
				$ls_cleanModuleName = substr($ls_cleanModuleName, 0, -4);
			}

			// Files that are deeper than one level must have that nested prepended to the module name
			if (substr_count($ls_moduleName, '/') > 0) {
				$la_parts = explode('/', $ls_moduleName);

				$ls_folder = $la_parts[0];
				if (in_array($la_parts[0], ['Modules', 'Controller'])) {
					// Remove the first part of the path
					array_shift($la_parts);
				}

				// Remove the last part of the path
				array_pop($la_parts);

				$ls_cleanModuleName = ($la_parts ? implode('/', $la_parts) . '/' : '') . $ls_cleanModuleName;

				if ($ls_folder === 'Controller') {
					$ls_cleanModuleName .= 'Controller';
				}
			}

			// Add the module to the import map
			$la_importMap['imports'][ $ls_cleanModuleName ] = $this->getAssetPath($ls_moduleName, $la_options);
		}

		// If includeScriptTag is true, wrap the import map in a script tag
		if ($includeScriptTag) {
			$ls_nonce = $this->getView()->getRequest()->getAttribute('cspScriptNonce');

			if ($ls_nonce) {
				$ls_nonce = ' nonce="' . $ls_nonce . '"';
			}

			if (!empty($la_importMap['imports'])) {
				return '<script type="importmap"' . $ls_nonce . '>' . json_encode($la_importMap) . '</script>' . PHP_EOL;
			}

			return '';
		}


		// Otherwise, return the import map as a JSON string
		return json_encode($la_importMap);
	}


	/**
	 * Returns all final assets.
	 * This method retrieves all assets from the 'all' assets array and generates a path for each asset using the getAssetPath method.
	 *
	 * @return array
	 * @throws \Exception
	 */
	public function getFinalAssets(): array {
		$la_finalAssets = [];

		// Iterate over each asset in 'all'
		foreach (static::$assets['all'] as $ls_fileName => $la_options) {
			// Retrieve the full path of the asset
			$ls_assetPath = $this->getAssetPath($ls_fileName, $la_options);

			// If the asset path is not null, add it to the final assets array
			if ($ls_assetPath !== null) {
				$la_finalAssets[ $ls_fileName ] = ['path' => $ls_assetPath] + $la_options;
				ksort($la_finalAssets[ $ls_fileName ]);
			}
		}

		return $this->sortAssetsByPriority($la_finalAssets);
	}


	/**
	 * Returns the script nonce from the request attributes.
	 *
	 * @return string|null
	 */
	public function getScriptNonce(): ?string {
		return $this->getView()->getRequest()->getAttribute('cspScriptNonce');
	}


	/**
	 * Returns the style nonce from the request attributes.
	 *
	 * @return string|null
	 */
	public function getStyleNonce(): ?string {
		return $this->getView()->getRequest()->getAttribute('cspStyleNonce');
	}


	/**
	 * Returns all remembered assets.
	 *
	 * @return bool
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
	 * @return bool
	 */
	public function clearAssets(): void {
		static::$assets = static::$assetDefaults;
		static::$checkedAssets = [];
		static::$jsModules = [];
		static::$noScriptAssets = [];
	}


	/**
	 * Sets the HTTP2 preload headers for all assets.
	 *
	 * @return void
	 * @throws \Exception
	 */
	public function afterLayout(): void {
		// Get the response object from the view
		$lo_response = $this->getView()->getResponse();

		$la_header = [];

		// Get all assets and add them to the HTTP2 header
		foreach ($this->getFinalAssets() as $ls_fileName => $la_options) {
			$ls_extension = pathinfo($ls_fileName, PATHINFO_EXTENSION);

			// Set the asType based on the extension
			$ls_asType = match ($ls_extension) {
				'css' => 'style',
				'js' => 'script',
				'woff', 'woff2', 'ttf' => 'font',
				default => 'fetch',
			};

			// Add the asset path to the Link header
			$la_header[] = 'Link: <' . $la_options['path'] . '>; rel=preload; as=' . $ls_asType . '; nopush';
		}

		if (!$la_header) {
			return;
		}

		// Add the Link header to the response
		$lo_response = $lo_response->withHeader('Link', implode(', ', $la_header));

		// Set the response in the view
		$this->getView()->setResponse($lo_response);
	}


	/**
	 * Sorts the given assets array by priority.
	 *
	 * The higher the priority, the earlier the asset will be loaded.
	 *
	 * @param array $assets The assets array to sort. This is an associative array where the keys are asset names and the values are arrays of options for each asset.
	 * @return array The sorted assets array.
	 */
	protected function sortAssetsByPriority(array $assets): array {
		$la_assets = $assets;

		uasort($la_assets, function ($a, $b) {
			return $b['priority'] <=> $a['priority'];
		});


		return $la_assets;
	}


	/**
	 * Minifies the asset file located at the given source path and saves the minified content to the target path.
	 * This method uses the MatthiasMullie\Minify library to minify CSS and JavaScript files. The type of minifier used depends on the type of the asset file.
	 * If the type is 'css', a Minify\CSS minifier is used. If the type is 'js', a Minify\JS minifier is used. If the type is neither 'css' nor 'js', no minification is performed.
	 * The method first creates a new instance of the appropriate minifier for the asset type, passing the source path to the minifier's constructor.
	 * If the minifier instance is not null, the method calls the minifier's minify method, passing the target path. The minify method minifies the asset file and saves the
	 * minified content to the target path.
	 *
	 * @param string $sourcePath The path to the asset file to minify. This should be a full filesystem path.
	 * @param string $targetPath The path where the minified asset content should be saved. This should be a full filesystem path.
	 * @param string $type The type of the asset file. This should be either 'css' or 'js'.
	 * @return void
	 * @throws \Exception
	 */
	protected function minifyAsset(string $sourcePath, string $targetPath, string $type): void {
		// Create a new minifier instance based on the asset type
		$lo_minifier = match ($type) {
			'css' => new Css($sourcePath),
			'js' => new Minify\JS($sourcePath),
			default => null,
		};

		// If the minifier instance is not null, minify the asset and save the minified content to the target path
		if ($lo_minifier !== null) {
			try {
				if ($type === 'css') {
					$lo_minifier->setMaxImportSize(0);
				}

				$lo_minifier->minify($targetPath);
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
		$la_options = array_merge(['minified' => $minified, 'critical' => $critical, 'attributes' => $attributes, 'priority' => $priority], $options);

		if (!is_array($la_options['attributes'])) {
			$la_options['attributes'] = [];
		}

		if (isset($la_options['attributes']['includeTimestamp'])) {
			$la_options['includeTimestamp'] = $la_options['attributes']['includeTimestamp'];
			unset($la_options['attributes']['includeTimestamp']);
		}

		if (isset($la_options['attributes']['realm'])) {
			$la_options['realm'] = $la_options['attributes']['realm'];
			unset($la_options['attributes']['realm']);
		}

		// Put all options that are attributes into the attributes array
		foreach ($la_options as $ls_option => $lx_value) {
			if (in_array($ls_option, ['attributes', 'minified', 'critical', 'priority', 'includeTimestamp', 'realm'])) {
				continue;
			}

			$la_options['attributes'][ $ls_option ] = $lx_value;
			unset($la_options[ $ls_option ]);
		}

		return $la_options;
	}


	/**
	 * @param array $options
	 * @return string
	 */
	protected function generateAttributesString(array $options): string {
		$ls_additionalAttributes = '';

		if (empty($options['attributes']) || !is_array($options['attributes'])) {
			return '';
		}

		foreach ($options['attributes'] as $ls_attributeName => $lx_attributeValue) {
			if (!is_string($lx_attributeValue)) {
				$lx_attributeValue = match (true) {
					is_bool($lx_attributeValue) => $lx_attributeValue ? 'true' : 'false',
					is_int($lx_attributeValue) => (string)$lx_attributeValue,
					default => '',
				};
			}

			if ($lx_attributeValue === '') {
				continue;
			}

			$ls_additionalAttributes .= ' ' . $ls_attributeName . '="' . htmlspecialchars($lx_attributeValue, ENT_QUOTES, 'UTF-8') . '"';
		}

		return $ls_additionalAttributes;
	}


	/**
	 * @param string $fileName
	 * @param mixed $extension
	 * @param string $assetPath
	 * @return string
	 * @throws \Exception
	 */
	protected function getMinifiedPath(string $fileName, mixed $extension, string $assetPath): string {
		$ls_minifiedPath = ROOT . $fileName . $extension;

		/*
		 * If the minified file does not exist,
		 * or if the modification time of the minified file is older than the modification time of the asset file,
		 * minify the asset file.
		 */
		if (!file_exists($ls_minifiedPath) || filemtime($ls_minifiedPath) < filemtime($assetPath)) {
			$this->minifyAsset($assetPath, $ls_minifiedPath, $extension);
		}

		return $ls_minifiedPath;
	}
}
