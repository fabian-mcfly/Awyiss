<?php declare(strict_types=1);


namespace Awyiss\Command;


use Cake\Command\I18nExtractCommand as BaseI18nExtractCommand;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Utility\Fs\Finder;


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

		foreach ($this->_files as $file) {
			$this->_file = $file;
			if ($isVerbose) {
				$io->verbose(sprintf('Processing %s...', $file));
			}

			$code = file_get_contents($file);

			if (str_ends_with($file, '.twig')) {
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

			$this->extractFileReflection($file, $code);

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
			$files = (new Finder())
				->in($path)
				->name('*.php')
				->name('*.twig')
				->files()
			;
			foreach ($files as $file) {
				$this->_files[] = $file->getPathname();
			}
		}
		$this->_files = array_unique($this->_files);
		sort($this->_files);
		if ($pattern) {
			$this->_files = preg_grep($pattern, $this->_files, PREG_GREP_INVERT) ?: [];
			$this->_files = array_values($this->_files);
		}
		$this->_files = array_unique($this->_files);
	}
}
