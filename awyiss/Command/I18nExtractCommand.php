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
		/** @var \Cake\Command\Helper\ProgressHelper $progress */
		$progress = $io->helper('progress');
		$progress->init(['total' => count($this->_files)]);
		$isVerbose = $args->getOption('verbose');

		$functions = [
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
		$pattern = '/(' . implode('|', array_keys($functions)) . ')\s*\(/';

		foreach ($this->_files as $filePath) {
			$this->_file = $filePath;
			if ($isVerbose) {
				$io->verbose(sprintf('Processing %s...', $filePath));
			}

			$code = file_get_contents($filePath);

			if (str_ends_with($filePath, '.twig')) {
				$code = str_replace(['{{', '{%'], '<?php', $code);
				$code = str_replace(['}}', '%}'], '?>', $code);
			}

			if (preg_match($pattern, $code) === 1) {
				$allTokens = token_get_all($code);

				$this->_tokens = [];
				foreach ($allTokens as $token) {
					if (!is_array($token) || ($token[0] !== T_WHITESPACE && $token[0] !== T_INLINE_HTML)) {
						$this->_tokens[] = $token;
					}
				}
				unset($allTokens);

				foreach ($functions as $functionName => $map) {
					$this->_parse($io, $functionName, $map);
				}
			}

			if (!$isVerbose) {
				$progress->increment();
				$progress->draw();
			}
		}
	}


	/**
	 * Search files that may contain translatable strings
	 *
	 * @return void
	 */
	protected function _searchFiles(): void {
		$pattern = false;
		if (!empty($this->_exclude)) {
			$excludes = [];
			foreach ($this->_exclude as $exclude) {
				if (DIRECTORY_SEPARATOR !== '\\' && !str_starts_with($exclude, DIRECTORY_SEPARATOR)) {
					$exclude = DIRECTORY_SEPARATOR . $exclude;
				}
				$excludes[] = preg_quote($exclude, '/');
			}
			$pattern = '/' . implode('|', $excludes) . '/';
		}

		foreach ($this->_paths as $path) {
			$path = realpath($path);
			if ($path === false) {
				continue;
			}
			$path .= DIRECTORY_SEPARATOR;
			/** @noinspection PhpInternalEntityUsedInspection */
			$filesystem = new Filesystem();
			$files = $filesystem->findRecursive($path, '/\.(php|twig)$/');
			$files = array_keys(iterator_to_array($files));
			sort($files);
			if (!empty($pattern)) {
				$files = preg_grep($pattern, $files, PREG_GREP_INVERT);
				$files = array_values($files);
			}
			$this->_files = array_merge($this->_files, $files);
		}
		$this->_files = array_unique($this->_files);
	}
}
