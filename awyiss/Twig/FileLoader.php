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
	 * @param array $aa_extensions
	 * @noinspection PhpMissingParentConstructorInspection
	 */
	public function __construct(array $aa_extensions) {
		$this->extensions = $aa_extensions;
		$this->paths[ static::MAIN_NAMESPACE ] = App::path('templates');
	}


	/**
	 * Returns the paths to the templates.
	 *
	 * @noinspection PhpUnused
	 */
	public function getPaths(string $as_namespace = self::MAIN_NAMESPACE): array {
		return $this->paths[ $as_namespace ] ?? [];
	}


	/**
	 * Sets the paths and removes existing ones for a given namespace
	 *
	 * @param array|string $ax_paths
	 * @param string $as_namespace
	 * @throws LoaderError
	 */
	public function setPaths(array|string $ax_paths, string $as_namespace = self::MAIN_NAMESPACE): void {
		$la_paths = !is_array($ax_paths) ? [$ax_paths] : $ax_paths;

		$this->paths[ $as_namespace ] = [];
		foreach ($la_paths as $ls_path) {
			$this->addPath($ls_path, $as_namespace);
		}
	}


	/**
	 * Returns the path namespaces.
	 *
	 * The main namespace is always defined.
	 *
	 * @noinspection PhpUnused
	 */
	public function getNamespaces(): array {
		return array_keys($this->paths);
	}


	/**
	 * Appends a new path, if it doesn't already exist in current set of paths
	 *
	 * @param string $as_path
	 * @param string $as_namespace
	 * @param bool $ab_prepend
	 * @return void
	 * @throws LoaderError
	 */
	public function addPath(string $as_path, string $as_namespace = self::MAIN_NAMESPACE, bool $ab_prepend = false): void {
		$ls_checkPath = $this->isAbsolutePath($as_path) ? $as_path : $this->rootPath . $as_path;
		if (!is_dir($ls_checkPath)) {
			throw new LoaderError(sprintf('The "%s" directory does not exist ("%s").', $as_path, $ls_checkPath));
		}

		$ls_path = rtrim($as_path, '/\\') . DS;

		if (!isset($this->paths[ $as_namespace ])) {
			$this->paths[ $as_namespace ] = [$ls_path];
		}
		else {
			$li_key = array_search($ls_path, $this->paths[ $as_namespace ]);

			if ($ab_prepend) {
				if ($li_key !== false) {
					unset($this->paths[ $as_namespace ][ $li_key ]);
				}

				array_unshift($this->paths[ $as_namespace ], $ls_path);
			}
			elseif ($li_key === false) {
				$this->paths[ $as_namespace ][] = $ls_path;
			}
		}
	}


	/**
	 * Prepends a new path to the current set of paths.
	 *
	 * If it already exists, the existing one will be removed to not have duplicates
	 *
	 * @param string $as_path
	 * @param string $as_namespace
	 * @return void
	 * @throws LoaderError
	 * @noinspection PhpUnused
	 */
	public function prependPath(string $as_path, string $as_namespace = self::MAIN_NAMESPACE): void {
		$this->addPath($as_path, $as_namespace, true);
	}


	/**
	 * Find templates with the given name in any of the current set of paths.
	 *
	 * @param string $as_name Template name
	 * @return string
	 * @throws LoaderError
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function findTemplate(string $as_name): string {
		if (file_exists($as_name)) {
			return $as_name;
		}

		$ls_name = $as_name;
		if (str_ends_with($ls_name, '.twig')) {
			$ls_name = substr($ls_name, 0, -5);
		}

		[$ls_plugin, $ls_name] = pluginSplit($ls_name);
		$ls_name = str_replace('/', DS, $ls_name);

		if ($ls_plugin !== null) {
			$ls_templatePath = Plugin::templatePath($ls_plugin);
			$ls_path = $this->checkExtensions($ls_templatePath . $ls_name);
			if ($ls_path !== null) {
				return $ls_path;
			}

			throw new LoaderError(sprintf("Could not find template `%s` in plugin `%s` in these paths:\n\n" . "- `%s`\n", $as_name, $ls_plugin, $ls_templatePath));
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
	 * @param string $as_name
	 * @return array|array<string>
	 * @throws LoaderError
	 */
	private function parseName(string $as_name): array {
		if (isset($as_name[0]) && $as_name[0] == '@') {
			$li_pos = strpos($as_name, '/');
			if ($li_pos === false) {
				throw new LoaderError(sprintf('Malformed namespaced template name "%s" (expecting "@namespace/template_name").', $as_name));
			}

			$ls_namespace = substr($as_name, 1, $li_pos - 1);
			$ls_shortname = substr($as_name, $li_pos + 1);


			return [$ls_namespace, $ls_shortname];
		}


		return [self::MAIN_NAMESPACE, $as_name];
	}


	/**
	 * Validate the name of the file.
	 * - it must not contain a NUL byte
	 * - it must not try to reach a file or a directory outside the configured paths
	 *
	 * @throws LoaderError
	 */
	private function validateName(string $as_name): void {
		if (str_contains($as_name, "\0")) {
			throw new LoaderError('A template name cannot contain NUL bytes.');
		}

		$ls_name = ltrim($as_name, '/');
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
				throw new LoaderError(sprintf('Looks like you try to load a template outside configured directories (%s).', $as_name));
			}
		}
	}


	/**
	 * @param string $as_file
	 * @return bool
	 */
	private function isAbsolutePath(string $as_file): bool {
		return strspn($as_file, '/\\', 0, 1) ||
			   (strlen($as_file) > 3 && ctype_alpha($as_file[0]) && $as_file[1] === ':' && strspn($as_file, '/\\', 2, 1)) ||
			   parse_url($as_file, PHP_URL_SCHEME) !== null;
	}
}
