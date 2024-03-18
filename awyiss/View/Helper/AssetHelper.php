<?php declare(strict_types=1);


namespace Awyiss\View\Helper;


use Awyiss\Awyiss;
use Cake\Core\Configure;
use Cake\Log\Log;
use Cake\Routing\Router;
use Cake\View\Helper;
use Exception;
use MatthiasMullie\Minify;


/**
 * AssetHelper is a class that extends the Helper class.
 * This class is used to manage and manipulate assets in a web application. It provides methods for adding assets, generating HTML tags for assets, minifying assets, and more.
 * The class maintains an array of assets, which can be of various types (e.g., CSS, JavaScript, fonts). Each asset can have several properties, such as whether it is minified or
 * critical, and its priority. The class also provides methods for sorting assets by priority and for generating fallback content for users who have JavaScript disabled in their
 * browser.
 *
 * @extends Helper
 */
class AssetHelper extends Helper {
	/**
	 * @var array $assets An associative array of assets. The keys are the asset names, and the values are arrays of options for each asset.
	 */
	protected array $assets = [
		'all' => [],
		'css' => [
			'critical' => [],
			'nonCritical' => [],
		],
		'font' => [],
		'js' => [
			'critical' => [],
			'nonCritical' => [],
		],
		'unknown' => [],
	];
	/**
	 * @var array $checkedAssets An associative array of checked assets. The keys are the asset names, and the values are the asset paths.
	 */
	protected array $checkedAssets = [];
	/**
	 * @var string $realm The realm of the application. This is used to determine the base path for assets.
	 */
	protected string $realm;
	/**
	 * @var array $realmFolders An associative array of realm folders. The keys are the realm names, and the values are arrays of folder paths for each realm.
	 */
	protected array $realmFolders;


	/**
	 * @inheritDoc
	 */
	public function initialize(array $config): void {
		$this->realm = Awyiss::getRealm();

		$this->realmFolders = Configure::read('App.paths.assets');
	}


	/**
	 * Adds an asset to the assets array.
	 * This method allows you to add an asset to the assets array. Each asset can have several properties:
	 * - minified: A boolean indicating whether the asset is minified.
	 * - critical: A boolean indicating whether the asset is critical.
	 * - priority: An integer indicating the priority of the asset. Higher numbers indicate higher priority.
	 * If the asset is already in the array, it will not be added again.
	 *
	 * @param array|string $asset The asset to add. This can be either a string representing the asset, or an array with the asset as the key and an array of options as the value.
	 * @param bool $critical (optional) Whether the asset is critical. Defaults to false.
	 * @param bool|null $minified (optional) Whether the asset is minified. Defaults to false.
	 * @param int $priority (optional) The priority of the asset. Defaults to 1.
	 * @return void
	 */
	public function add(array|string $asset, bool $critical = false, ?bool $minified = null, int $priority = 10): void {
		$lb_minified = $minified;

		// Set minified to the default value if it's not set
		if ($lb_minified === null) {
			$lb_minified = !Configure::read('debug', false);
		}

		// If the asset is an array, extract the asset and options from the array
		$la_assets = is_array($asset) ? $asset : [$asset => ['minified' => $lb_minified, 'critical' => $critical, 'priority' => $priority]];

		foreach ($la_assets as $lx_key => $lx_value) {
			if (is_string($lx_key)) {
				$ls_filename = $lx_key;

				// If the asset is already in the array, skip it
				if (in_array($ls_filename, $this->assets['all'])) {
					continue;
				}

				// If the asset is an array, extract the options from the array
				$la_options = is_array($lx_value) ? $lx_value : ['minified' => false, 'critical' => false, 'priority' => 1];

				// Set the minified option to the default value if it's not set
				if (!isset($la_options['minified']) || !is_bool($la_options['minified'])) {
					$la_options['minified'] = false;
				}

				// Set the critical option to the default value if it's not set
				if (!isset($la_options['critical']) || !is_bool($la_options['critical'])) {
					$la_options['critical'] = false;
				}

				// Set the priority option to the default value if it's not set
				if (!isset($la_options['priority']) || !is_int($la_options['priority'])) {
					$la_options['priority'] = 10;
				}
			}
			else {
				$ls_filename = $lx_value;

				// If the asset is already in the array, skip it
				if (in_array($ls_filename, $this->assets['all'])) {
					continue;
				}

				$la_options = [
					'minified' => $lb_minified,
					'critical' => $critical,
					'priority' => $priority,
				];
			}

			$ls_key = $la_options['critical'] ? 'critical' : 'nonCritical';
			$ls_extension = pathinfo($ls_filename, PATHINFO_EXTENSION);

			// If the extension is not recognized, try to determine it from the url, if it's an url
			if (empty($ls_extension)) {
				// fonts.googleapis.com is a special case, as it's not a file, but a URL
				if (str_contains($ls_filename, '//fonts.googleapis.com')) {
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

			// If the asset is not already in the array, add it
			$this->assets['all'][ $ls_filename ] = $la_options;
			$this->assets[ $ls_extension ][ $ls_key ][ $ls_filename ] = $la_options;
		}
	}


	/**
	 * Generates an HTML tag for the given asset.
	 * This method generates an HTML tag for the asset located at the given path. The type of tag generated depends on the type of the asset.
	 * If the asset is a font (woff, woff2, ttf), and the lazyLoad parameter is true, a <link> tag with rel="preload" is generated.
	 * If the asset is a JavaScript file, a <script> tag is generated. If the 'critical' option of the asset is true, the tag is generated without the async attribute.
	 * If the asset is a CSS file, a <link> tag with rel="stylesheet" is generated. If the 'critical' option of the asset is true, the tag is generated without the onload
	 * attribute. The method returns the generated tag as a string.
	 *
	 * @param string $assetPath The path to the asset. This should be a full URL.
	 * @param array $options An array of options for the asset. This should include a 'critical' key with a boolean value indicating whether the asset is critical.
	 * @param bool $lazyLoad (optional) Whether to generate a lazy loading tag for the asset. Defaults to true.
	 * @return string An HTML tag for the asset.
	 */
	public function createAssetTag(string $assetPath, array $options, bool $lazyLoad = true): string {
		// Get the extension of the asset
		$ls_extension = pathinfo($assetPath, PATHINFO_EXTENSION);

		// If the extension is not recognized, try to determine it from the url, if it's an url
		if ($ls_extension === 'woff' || $ls_extension === 'woff2' || $ls_extension === 'ttf') {
			if ($lazyLoad) {
				// Generate a <link> tag with rel="preload" for the font
				/** @noinspection HtmlUnknownTarget */
				return sprintf('<link rel="preload" href="%s" as="font" type="font/%s" crossorigin>', $assetPath, $ls_extension) . PHP_EOL;
			}


			return '';
		}

		// If the asset is critical, generate a <script> tag for JavaScript files and a <link> tag with rel="stylesheet" for CSS files
		if ($options['critical']) {
			if ($ls_extension === 'js') {
				return '<script src="' . $assetPath . '"></script>' . PHP_EOL;
			}


			return '<link rel="stylesheet" type="text/css" href="' . $assetPath . '"/>' . PHP_EOL;
		}

		// If the asset is a JavaScript file, generate a <script> tag with the async attribute
		if ($ls_extension === 'js') {
			return '<script async src="' . $assetPath . '"></script>' . PHP_EOL;
		}

		// If the asset is a CSS file and lazy loading is enabled, generate a <link> tag with rel="preload" and an onload attribute
		if ($lazyLoad) {
			return '<link rel="preload" href="' . $assetPath . '" as="style" onload="this.onload=null;this.rel=\'stylesheet\'">' . PHP_EOL;
		}


		// If none of the above conditions are met, generate a <link> tag with rel="stylesheet" for the asset
		return '<link rel="stylesheet" type="text/css" href="' . $assetPath . '"/>' . PHP_EOL;
	}


	/**
	 * Adds an asset to the assets array.
	 * This method allows you to add an asset to the assets array. Each asset can have several properties:
	 * - minified: A boolean indicating whether the asset is minified.
	 * - critical: A boolean indicating whether the asset is critical.
	 * - priority: An integer indicating the priority of the asset. Higher numbers indicate higher priority.
	 * If the asset is already in the array, it will not be added again.
	 *
	 * @param array|string $asset The asset to add. This can be either a string representing the asset, or an array with the asset as the key and an array of options as the value.
	 * @param bool $critical (optional) Whether the asset is critical. Defaults to false.
	 * @param bool|null $minified (optional) Whether the asset is minified. Defaults to false.
	 * @param int $priority (optional) The priority of the asset. Defaults to 1.
	 * @return void
	 * @throws \Exception
	 */
	public function getAssetPath(string $asset, array $options): ?string {
		// If the asset has already been checked, return the asset path
		if (isset($this->checkedAssets[ $asset ])) {
			return $this->checkedAssets[ $asset ];
		}

		// If the asset is a full URL, return it
		if (preg_match('/^((http|https|ftp):\\/\\/|\\/\\/)/', $asset)) {
			$this->checkedAssets[ $asset ] = $asset;


			return $asset;
		}

		// If the asset is already a full URL, return it
		$ls_subPath = $ls_extension = pathinfo($asset, PATHINFO_EXTENSION);

		// If the extension is a font type, set the extension to 'font'
		if ($ls_extension === 'woff' || $ls_extension === 'woff2' || $ls_extension === 'ttf') {
			$ls_subPath = 'font';
		}

		foreach ($this->realmFolders[ $this->realm ] as $ls_folder) {
			$ls_assetPath = $ls_folder . $ls_subPath . '/' . $asset;
			if (!file_exists($ls_assetPath)) {
				continue;
			}

			// Convert the filesystem path to a path relative to the application's base path
			$ls_relativePath = str_replace(realpath(ROOT), '', realpath($ls_assetPath));

			// Check if the file is minified and append ".min" to the filename before the extension if it is
			$ls_filename = substr($ls_relativePath, 0, -strlen($ls_extension));

			// If the file should be minified, append "min" to the filename before the extension
			if ($options['minified'] ?? false) {
				$ls_filename .= 'min.';

				$ls_minifiedPath = ROOT . $ls_filename . $ls_extension;

				// If the minified file does not exist, create it
				if (!file_exists($ls_minifiedPath)) {
					$this->minifyAsset($ls_assetPath, $ls_minifiedPath, $ls_extension);
				}

				// Get the file modification time
				$li_modTime = filemtime($ls_minifiedPath);
			}
			else {
				// Get the file modification time
				$li_modTime = filemtime($ls_assetPath);
			}


			// Generate a URL for the asset using CakePHP's Router and ppend the modification time to the filename
			return $this->checkedAssets[ $asset ] = Router::url($ls_filename . $li_modTime . '.' . $ls_extension, true);
		}

		// If the asset is not found, return null
		$this->checkedAssets[ $asset ] = null;


		return null;
	}


	/**
	 * Generates a string of HTML tags for the specified type of assets.
	 * This method retrieves the assets of the specified type from the assets array. If the assets array is empty, it returns an empty string.
	 * The assets are then sorted by priority using the sortAssetsByPriority method. The method then iterates over each asset. If the 'critical' option of the asset does not match
	 * the specified criticality, the asset is skipped. For each valid asset, the method retrieves the asset path using the getAssetPath method. If the asset path is null, the
	 * asset is skipped. For each valid asset with a valid path, the method generates an HTML tag using the createAssetTag method. The generated tag is appended to the asset tags
	 * string. If the includeNoScript parameter is true, the method appends the result of the getNoScriptTags method (which generates a string of HTML tags for non-JavaScript
	 * assets, wrapped in a <noscript> tag) to the asset tags string. Finally, the method returns the asset tags string.
	 *
	 * @param string $type The type of assets to generate tags for. Defaults to 'all'.
	 * @param bool|null $critical (optional) Whether to generate tags for critical assets. If null, tags are generated for all assets. Defaults to null.
	 * @param bool $includeNoScript (optional) Whether to include a <noscript> tag with tags for non-JavaScript assets. Defaults to true.
	 * @return string A string of HTML tags for the specified type of assets.
	 * @throws \Exception
	 */
	public function getTags(string $type = 'all', ?bool $critical = null, bool $includeNoScript = true): string {
		$la_assets = $this->assets[ $type ] ?? [];

		// If the assets array is empty, return an empty string
		if (empty($la_assets)) {
			return '';
		}

		// Sort the assets by priority
		$la_assets = $this->sortAssetsByPriority($la_assets);

		$ls_assetTags = '';
		foreach ($la_assets as $ls_asset => $la_options) {
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
		if ($includeNoScript) {
			$ls_assetTags .= $this->getNoScriptTags($type);
		}


		return $ls_assetTags;
	}


	/**
	 * Generates a string of HTML tags for non-JavaScript assets, wrapped in a <noscript> tag.
	 * This method is used to generate fallback content for users who have JavaScript disabled in their browser. It generates a string of HTML tags for all non-JavaScript assets,
	 * and wraps this string in a <noscript> tag. The generated tags are sorted by priority, with higher priority assets appearing first.
	 * The method first retrieves the assets of the specified type from the assets array. If the assets array is empty, it returns an empty string.
	 * The method then sorts the assets by priority using the sortAssetsByPriority method. It then iterates over each asset. If the asset is a JavaScript file, it is skipped.
	 * For each non-JavaScript asset, the method retrieves the asset path using the getAssetPath method. If the asset path is null, the asset is skipped.
	 * For each valid non-JavaScript asset, the method generates an HTML tag using the createAssetTag method, with the third parameter (lazyLoad) set to false. The generated tag
	 * is appended to the asset tags string.
	 * Finally, the method returns the asset tags string, wrapped in a <noscript> tag.
	 *
	 * @param string $type The type of assets to generate tags for. Defaults to 'all'.
	 * @return string A string of HTML tags for non-JavaScript assets, wrapped in a <noscript> tag.
	 * @throws \Exception
	 */
	public function getNoScriptTags(string $type = 'all'): string {
		$la_assets = $this->assets[ $type ] ?? [];

		if (empty($la_assets)) {
			return '';
		}

		// Sort the assets by priority
		$la_assets = $this->sortAssetsByPriority($la_assets);

		$ls_assetTags = '';
		// Iterate over each asset
		foreach ($la_assets as $ls_asset => $la_options) {
			// Skip JavaScript assets
			if (pathinfo($ls_asset, PATHINFO_EXTENSION) === 'js') {
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


		// Return the asset tags string, wrapped in a <noscript> tag
		return '<noscript>' . $ls_assetTags . '</noscript>';
	}


	/**
	 * Sorts the given assets array by priority.
	 * This method sorts the given assets array in descending order of priority. The priority of an asset is determined by the 'priority' key in the array of options for each
	 * asset. Higher numbers indicate higher priority. The method uses the uasort function to sort the array. The comparison function passed to uasort compares the 'priority'
	 * values of two assets and returns a negative number, zero, or a positive number depending on whether the first asset's priority is less than, equal to, or greater than the
	 * second asset's priority.
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
			'css' => new Minify\CSS($sourcePath),
			'js' => new Minify\JS($sourcePath),
			default => null,
		};

		// If the minifier instance is not null, minify the asset and save the minified content to the target path
		if ($lo_minifier !== null) {
			try {
				$lo_minifier->minify($targetPath);
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
}
