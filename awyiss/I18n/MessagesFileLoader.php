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
		$la_folders = $this->translationsFolders();
		$ls_extension = $this->_extension;

		$ls_fileName = $this->_name;
		$ls_subfolder = null;
		$li_strpos = strpos($ls_fileName, DS);
		if ($li_strpos !== false) {
			$ls_subfolder = substr($ls_fileName, 0, $li_strpos + 1);
			$ls_fileName = substr($ls_fileName, $li_strpos + 1);
		}

		$ls_parserName = ucfirst($ls_extension);
		$ls_parserClass = App::className($ls_parserName, 'I18n\Parser', 'FileParser');

		if (!$ls_parserClass) {
			throw new RuntimeException(sprintf('Could not find class %s', "{$ls_parserName}FileParser"));
		}

		$lo_package = new Package('default');
		$lo_parser = new $ls_parserClass();

		foreach ($la_folders as $ls_folder) {
			$ls_path = $ls_folder . $ls_subfolder . $ls_fileName . '.' . $ls_extension;
			if (is_file($ls_path)) {
				$la_messages = $lo_parser->parse($ls_path);
				if ($la_messages) {
					$lo_package->addMessages($la_messages);
				}
			}
		}

		return $lo_package;
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
		$la_locale = Locale::parseLocale($this->_locale) + ['region' => null];

		$la_folders = [
			$la_locale['language'],
			// gettext compatible paths, see https://www.php.net/manual/en/function.gettext.php
			$la_locale['language'] . DIRECTORY_SEPARATOR . 'LC_MESSAGES',
		];
		if ($la_locale['region']) {
			$ls_languageRegion = implode('_', [$la_locale['language'], $la_locale['region']]);
			$la_folders[] = $ls_languageRegion;
			// gettext compatible paths, see https://www.php.net/manual/en/function.gettext.php
			$la_folders[] = $ls_languageRegion . DIRECTORY_SEPARATOR . 'LC_MESSAGES';
		}

		$la_searchPaths = [];

		$la_localePaths = App::path('locales');
		if (!$la_localePaths && defined('ROOT')) {
			$la_localePaths[] = ROOT . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'locales' . DIRECTORY_SEPARATOR;
		}
		if ($this->_plugin && Plugin::isLoaded($this->_plugin)) {
			$la_localePaths[] = App::path('locales', $this->_plugin)[0];
		}

		foreach (array_reverse($la_localePaths) as $ls_path) {
			foreach ($la_folders as $ls_folder) {
				$la_searchPaths[] = $ls_path . $ls_folder . DIRECTORY_SEPARATOR;
			}
		}

		return $la_searchPaths;
	}
}
