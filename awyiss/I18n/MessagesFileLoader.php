<?php declare(strict_types=1);


namespace Awyiss\I18n;


use Awyiss\Core\App;
use Cake\Core\Plugin;
use Cake\I18n\MessagesFileLoader as BaseMessagesFileLoader;
use Cake\I18n\Package;
use Locale;
use RuntimeException;


/**
 * @inheritDoc
 */
class MessagesFileLoader extends BaseMessagesFileLoader {
	/**
	 * Reimplemented to traverse all translation folders in
	 * reverse order to load translations: first Awyiss, then customer translations.
	 * This allows customer translations to override Awyiss ones.
	 *
	 * @inheritDoc
	 */
	public function __invoke(): Package|false {
		$folders = $this->translationsFolders();
		$extension = $this->_extension;

		$fileName = $this->_name;
		$subfolder = null;
		$strpos = strpos($fileName, DS);
		if ($strpos !== false) {
			$subfolder = substr($fileName, 0, $strpos + 1);
			$fileName = substr($fileName, $strpos + 1);
		}

		$parserName = ucfirst($extension);
		$parserClass = App::className($parserName, 'I18n\Parser', 'FileParser');

		if (!$parserClass) {
			throw new RuntimeException(sprintf('Could not find class %s', "{$parserName}FileParser"));
		}

		$package = new Package('default');
		$parser = new $parserClass();

		foreach ($folders as $folder) {
			$path = $folder . $subfolder . $fileName . '.' . $extension;
			if (is_file($path)) {
				$messages = $parser->parse($path);
				if ($messages) {
					$package->addMessages($messages);
				}
			}
		}

		return $package;
	}


	/**
	 * Re-implemented to change the order of folders.
	 * First Awyiss folders are traversed, then customer folders.
	 *
	 * In each folder, the following the locales are ordered from the least specific to the most specific:
	 * - language (e.g. "en")
	 * - language/LC_MESSAGES (e.g. "en/LC_MESSAGES")
	 * - language_REGION (e.g. "en_US")
	 * - language_REGION/LC_MESSAGES (e.g. "en_US/LC_MESSAGES")
	 *
	 * @inheritDoc
	 */
	public function translationsFolders(): array {
		$locale = Locale::parseLocale($this->_locale) + ['region' => null];

		$folders = [
			$locale['language'],
			// gettext compatible paths, see https://www.php.net/manual/en/function.gettext.php
			$locale['language'] . DIRECTORY_SEPARATOR . 'LC_MESSAGES',
		];
		if ($locale['region']) {
			$languageRegion = implode('_', [$locale['language'], $locale['region']]);
			$folders[] = $languageRegion;
			// gettext compatible paths, see https://www.php.net/manual/en/function.gettext.php
			$folders[] = $languageRegion . DIRECTORY_SEPARATOR . 'LC_MESSAGES';
		}

		$searchPaths = [];

		$localePaths = App::path('locales');
		if (!$localePaths && defined('ROOT')) {
			$localePaths[] = ROOT . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'locales' . DIRECTORY_SEPARATOR;
		}
		if ($this->_plugin && Plugin::isLoaded($this->_plugin)) {
			$localePaths[] = App::path('locales', $this->_plugin)[0];
		}

		foreach (array_reverse($localePaths) as $path) {
			foreach ($folders as $folder) {
				$searchPaths[] = $path . $folder . DIRECTORY_SEPARATOR;
			}
		}

		return $searchPaths;
	}
}
