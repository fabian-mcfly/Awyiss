<?php declare(strict_types=1);


namespace Awyiss\Command;


use Cake\Command\I18nExtractCommand as BaseI18nExtractCommand;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Utility\Filesystem;


/**
 * Language string extractor
 */
class I18nExtractCommand extends BaseI18nExtractCommand {
	/**
	 * {@inheritDoc}
	 *
	 * Added
	 * - '__df' => ['domain', 'fallback', 'context', 'singular'],
	 * - '__dfx' => ['domain', 'fallback', 'context', 'singular'],
	 */
	protected function _extractTokens(Arguments $args, ConsoleIo $io): void {
		/** @var \Cake\Command\Helper\ProgressHelper $lo_progress */
		$lo_progress = $io->helper('progress');
		$lo_progress->init(['total' => count($this->_files)]);
		$lb_isVerbose = $args->getOption('verbose');

		$la_functions = [
			'__' => ['singular'],
			'__n' => ['singular', 'plural'],
			'__d' => ['domain', 'singular'],
			'__df' => ['domain', 'fallback', 'singular'],
			'__dfx' => ['domain', 'fallback', 'context', 'singular'],
			'__dn' => ['domain', 'singular', 'plural'],
			'__x' => ['context', 'singular'],
			'__xn' => ['context', 'singular', 'plural'],
			'__dx' => ['domain', 'context', 'singular'],
			'__dxn' => ['domain', 'context', 'singular', 'plural'],
		];
		$ls_pattern = '/(' . implode('|', array_keys($la_functions)) . ')\s*\(/';

		foreach ($this->_files as $ls_filePath) {
			$this->_file = $ls_filePath;
			if ($lb_isVerbose) {
				$io->verbose(sprintf('Processing %s...', $ls_filePath));
			}

			$ls_code = file_get_contents($ls_filePath);

			if (str_ends_with($ls_filePath, '.twig')) {
				$ls_code = str_replace(['{{', '{%'], '<?php', $ls_code);
				$ls_code = str_replace(['}}', '%}'], '?>', $ls_code);
			}

			if (preg_match($ls_pattern, $ls_code) === 1) {
				$la_allTokens = token_get_all($ls_code);

				$this->_tokens = [];
				foreach ($la_allTokens as $lx_token) {
					if (!is_array($lx_token) || ($lx_token[0] !== T_WHITESPACE && $lx_token[0] !== T_INLINE_HTML)) {
						$this->_tokens[] = $lx_token;
					}
				}
				unset($la_allTokens);

				foreach ($la_functions as $ls_functionName => $la_map) {
					$this->_parse($io, $ls_functionName, $la_map);
				}
			}

			if (!$lb_isVerbose) {
				$lo_progress->increment();
				$lo_progress->draw();
			}
		}
	}


	/**
	 * Search files that may contain translatable strings
	 *
	 * @return void
	 */
	protected function _searchFiles(): void {
		$ls_pattern = false;
		if (!empty($this->_exclude)) {
			$la_exclude = [];
			foreach ($this->_exclude as $ls_exclude) {
				if (DIRECTORY_SEPARATOR !== '\\' && !str_starts_with($ls_exclude, DIRECTORY_SEPARATOR)) {
					$ls_exclude = DIRECTORY_SEPARATOR . $ls_exclude;
				}
				$la_exclude[] = preg_quote($ls_exclude, '/');
			}
			$ls_pattern = '/' . implode('|', $la_exclude) . '/';
		}

		foreach ($this->_paths as $ls_path) {
			$ls_path = realpath($ls_path);
			if ($ls_path === false) {
				continue;
			}
			$ls_path .= DIRECTORY_SEPARATOR;
			/** @noinspection PhpInternalEntityUsedInspection */
			$lo_filesystem = new Filesystem();
			$lo_files = $lo_filesystem->findRecursive($ls_path, '/\.(php|twig)$/');
			$la_files = array_keys(iterator_to_array($lo_files));
			sort($la_files);
			if (!empty($ls_pattern)) {
				$la_files = preg_grep($ls_pattern, $la_files, PREG_GREP_INVERT);
				$la_files = array_values($la_files);
			}
			$this->_files = array_merge($this->_files, $la_files);
		}
		$this->_files = array_unique($this->_files);
	}
}
