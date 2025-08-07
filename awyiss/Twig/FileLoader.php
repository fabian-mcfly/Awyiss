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
	public const MAIN_NAMESPACE = '__main__';
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
		$la_paths = !is_array($paths) ? [$paths] : $paths;

		$this->paths[ $namespace ] = [];
		foreach ($la_paths as $ls_path) {
			$this->addPath($ls_path, $namespace);
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
		$ls_path = rtrim($path, '/\\') . DS;

		$ls_checkPath = $this->isAbsolutePath($ls_path) ? $ls_path : $this->rootPath . $ls_path;
		if (!is_dir($ls_checkPath)) {
			throw new LoaderError(sprintf('The "%s" directory does not exist ("%s").', $path, $ls_checkPath));
		}

		if (!isset($this->paths[ $namespace ])) {
			$this->paths[ $namespace ] = [$ls_path];
		}
		else {
			$li_key = array_search($ls_path, $this->paths[ $namespace ]);

			if ($prepend) {
				if ($li_key !== false) {
					unset($this->paths[ $namespace ][ $li_key ]);
				}

				array_unshift($this->paths[ $namespace ], $ls_path);
			}
			elseif ($li_key === false) {
				$this->paths[ $namespace ][] = $ls_path;
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

		$ls_name = $name;
		if (str_ends_with($ls_name, '.twig')) {
			$ls_name = substr($ls_name, 0, -5);
		}

		// Keep the name as is, in case no Plugin was found
		[$ls_plugin, $ls_pluginTemplateName] = pluginSplit($ls_name);
		$ls_pluginTemplateName = str_replace('/', DS, $ls_pluginTemplateName);

		if ($ls_plugin) {
			$ls_templatePath = Plugin::templatePath($ls_plugin);
			$ls_path = $this->checkExtensions($ls_templatePath . $ls_pluginTemplateName);
			if ($ls_path !== null) {
				return $ls_path;
			}

			throw new LoaderError(sprintf("Could not find template `%s` in plugin `%s` in these paths:\n\n" . "- `%s`\n", $name, $ls_plugin, $ls_templatePath));
		}

		//Make sure the given filename is valid and inside the set paths
		$this->validateName($ls_name);

		[$ls_namespace, $ls_shortname] = $this->parseName($ls_name);

		//If the file is in a namespace but no path for that namespace exists, we can't continue
		if (!isset($this->paths[ $ls_namespace ])) {
			throw new LoaderError(sprintf('There are no registered paths for namespace "%s".', $ls_namespace));
		}

		//Traverse all paths and if one contains the file we're looking for, return the full path of it.
		foreach ($this->paths[ $ls_namespace ] as $ls_templatePath) {
			$ls_path = $this->checkExtensions($ls_templatePath . $ls_shortname);
			if ($ls_path !== null) {
				return $ls_path;
			}
		}

		$ls_error = sprintf("Could not find template `%s` in these paths:\n", $ls_shortname);
		foreach ($this->paths[ $ls_namespace ] as $ls_templatePath) {
			$ls_error .= sprintf("- `%s`\n", $ls_templatePath);
		}
		throw new LoaderError($ls_error);
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

		$li_pos = strpos($name, DS);
		if ($li_pos === false) {
			throw new LoaderError(sprintf('Malformed namespaced template name "%s" (expecting "@namespace%stemplate_name").', $name, DS));
		}

		$ls_namespace = substr($name, 1, $li_pos - 1);
		$ls_shortname = substr($name, $li_pos + 1);

		return [$ls_namespace, $ls_shortname];
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

		$ls_name = ltrim($name, '/');
		$la_parts = explode('/', $ls_name);
		$li_level = 0;
		foreach ($la_parts as $ls_part) {
			if ($ls_part === '..') {
				--$li_level;
			}
			elseif ($ls_part !== '.') {
				++$li_level;
			}

			if ($li_level < 0) {
				throw new LoaderError(sprintf('Looks like you try to load a template outside configured directories (%s).', $name));
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
	 * $this->isAbsolutePath('/usr/local/bin');           // true
	 * $this->isAbsolutePath('C:\Windows\System32');      // true
	 * $this->isAbsolutePath('file:///tmp/file.txt');     // true
	 * $this->isAbsolutePath('relative/path/file.txt');   // false
	 * $this->isAbsolutePath('./local/file.txt');         // false
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
