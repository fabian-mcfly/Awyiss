<?php declare(strict_types=1);


namespace Awyiss\Twig;


use Awyiss\Core\App;
use Cake\Core\Plugin;
use Cake\TwigView\Twig\FileLoader as BaseFileLoader;
use Twig\Error\LoaderError;


/**
 * {@inheritDoc}
 *
 * This variation allows setting/adding/prepending paths that `findTemplate` will use.
 */
class FileLoader extends BaseFileLoader {
	/**
	 * Identifier for main namespace paths
	 */
	public const string MAIN_NAMESPACE = '__main__';
	/**
	 * @var array
	 */
	protected array $paths = [];
	/**
	 * @var string
	 */
	protected string $rootPath = ROOT . DS;


	/**
	 * @param array $extensions
	 * @noinspection PhpMissingParentConstructorInspection
	 */
	public function __construct(array $extensions) {
		$this->extensions = $extensions;
		$this->paths[ static::MAIN_NAMESPACE ] = App::path('templates');
	}


	/**
	 * Returns the paths to the templates.
	 *
	 * @param string $namespace
	 * @return array
	 */
	public function getPaths(string $namespace = self::MAIN_NAMESPACE): array {
		return $this->paths[ $namespace ] ?? [];
	}


	/**
	 * Sets the paths and removes existing ones for a given namespace
	 *
	 * @param array|string $paths
	 * @param string $namespace
	 * @throws LoaderError
	 */
	public function setPaths(array|string $paths, string $namespace = self::MAIN_NAMESPACE): void {
		$paths = !is_array($paths) ? [$paths] : $paths;

		$this->paths[ $namespace ] = [];
		foreach ($paths as $path) {
			$this->addPath($path, $namespace);
		}
	}


	/**
	 * Returns the path namespaces.
	 * The main namespace is always defined.
	 *
	 * @return array
	 */
	public function getNamespaces(): array {
		return array_keys($this->paths);
	}


	/**
	 * Appends a new path, if it doesn't already exist in current set of paths
	 *
	 * @param string $path
	 * @param string $namespace
	 * @param bool $prepend
	 * @return void
	 * @throws LoaderError
	 */
	public function addPath(string $path, string $namespace = self::MAIN_NAMESPACE, bool $prepend = false): void {
		$path = rtrim($path, '/\\') . DS;

		$checkPath = $this->isAbsolutePath($path) ? $path : $this->rootPath . $path;
		if (!is_dir($checkPath)) {
			throw new LoaderError(sprintf('The "%s" directory does not exist ("%s").', $path, $checkPath));
		}

		if (!isset($this->paths[ $namespace ])) {
			$this->paths[ $namespace ] = [$path];
		}
		else {
			$key = array_search($path, $this->paths[ $namespace ]);

			if ($prepend) {
				if ($key !== false) {
					unset($this->paths[ $namespace ][ $key ]);
				}

				array_unshift($this->paths[ $namespace ], $path);
			}
			elseif ($key === false) {
				$this->paths[ $namespace ][] = $path;
			}
		}
	}


	/**
	 * Prepends a new path to the current set of paths.
	 *
	 * If it already exists, the existing one will be removed to not have duplicates
	 *
	 * @param string $path
	 * @param string $namespace
	 * @return void
	 * @throws LoaderError
	 */
	public function prependPath(string $path, string $namespace = self::MAIN_NAMESPACE): void {
		$this->addPath($path, $namespace, true);
	}


	/**
	 * Find templates with the given name in any of the current set of paths.
	 *
	 * @param string $name Template name
	 * @return string
	 * @throws LoaderError
	 */
	public function findTemplate(string $name): string {
		if (file_exists($name)) {
			return $name;
		}

		$originalName = $name;
		if (str_ends_with($name, '.twig')) {
			$name = substr($name, 0, -5);
		}

		// Keep the name as is, in case no Plugin was found
		[$plugin, $pluginTemplateName] = pluginSplit($name);
		$pluginTemplateName = str_replace('/', DS, $pluginTemplateName);

		if ($plugin) {
			$templatePath = Plugin::templatePath($plugin);
			$path = $this->checkExtensions($templatePath . $pluginTemplateName);
			if ($path !== null) {
				return $path;
			}

			throw new LoaderError(sprintf("Could not find template `%s` in plugin `%s` in these paths:\n\n" . "- `%s`\n", $originalName, $plugin, $templatePath));
		}

		//Make sure the given filename is valid and inside the set paths
		$this->validateName($name);

		[$namespace, $shortname] = $this->parseName($name);

		//If the file is in a namespace but no path for that namespace exists, we can't continue
		if (!isset($this->paths[ $namespace ])) {
			throw new LoaderError(sprintf('There are no registered paths for namespace "%s".', $namespace));
		}

		//Traverse all paths and if one contains the file we're looking for, return the full path of it.
		foreach ($this->paths[ $namespace ] as $templatePath) {
			$path = $this->checkExtensions($templatePath . $shortname);
			if ($path !== null) {
				return $path;
			}
		}

		$error = sprintf("Could not find template `%s` in these paths:\n", $shortname);
		foreach ($this->paths[ $namespace ] as $templatePath) {
			$error .= sprintf("- `%s`\n", $templatePath);
		}
		throw new LoaderError($error);
	}


	/**
	 * If the provided name starts with an '@', the file is one inside a specific namespace.
	 * Separates the namespace from the name and returns both values.
	 *
	 * If there's no namespace in the name, return the default one.
	 *
	 * @param string $name
	 * @return array|array<string>
	 * @throws LoaderError
	 */
	protected function parseName(string $name): array {
		if (!str_starts_with($name, '@')) {
			return [self::MAIN_NAMESPACE, $name];
		}

		$pos = strpos($name, DS);
		if ($pos === false) {
			throw new LoaderError(sprintf('Malformed namespaced template name "%s" (expecting "@namespace%stemplate_name").', $name, DS));
		}

		$namespace = substr($name, 1, $pos - 1);
		$shortname = substr($name, $pos + 1);

		return [$namespace, $shortname];
	}


	/**
	 * Validate the name of the file.
	 * - it must not contain a NUL byte
	 * - it must not try to reach a file or a directory outside the configured paths
	 *
	 * @throws LoaderError
	 */
	protected function validateName(string $name): void {
		if (str_contains($name, "\0")) {
			throw new LoaderError('A template name cannot contain NUL bytes.');
		}

		$originalName = $name;
		$name = ltrim($name, '/');
		$parts = explode('/', $name);
		$level = 0;
		foreach ($parts as $part) {
			if ($part === '..') {
				--$level;
			}
			elseif ($part !== '.') {
				++$level;
			}

			if ($level < 0) {
				throw new LoaderError(sprintf('Looks like you try to load a template outside configured directories (%s).', $originalName));
			}
		}
	}


	/**
	 * Determines if a file path is absolute or relative by examining
	 * various platform-specific and protocol-based absolute path patterns.
	 *
	 * Recognized absolute path formats:
	 * - Unix/Linux absolute paths: `/path/to/file` or `\path\to\file`
	 * - Windows absolute paths: `C:\path\to\file` or `D:/path/to/file`
	 * - URL schemes: `file://`, `http://`, `https://`, `ftp://`, etc.
	 *
	 * ```
	 * $this->isAbsolutePath('/usr/local/bin'); // true
	 * $this->isAbsolutePath('C:\Windows\System32'); // true
	 * $this->isAbsolutePath('file:///tmp/file.txt'); // true
	 * $this->isAbsolutePath('relative/path/file.txt'); // false
	 * $this->isAbsolutePath('./local/file.txt'); // false
	 * ```
	 *
	 * @param string $file
	 * @return bool
	 */
	protected function isAbsolutePath(string $file): bool {
		return strspn($file, '/\\', 0, 1) ||
			   (strlen($file) > 3 && ctype_alpha($file[0]) && $file[1] === ':' && strspn($file, '/\\', 2, 1)) ||
			   parse_url($file, PHP_URL_SCHEME) !== null;
	}
}
