<?php declare(strict_types=1);


namespace Awyiss\I18n;


use Awyiss\Core\App;
use Cake\I18n\Package;
use RuntimeException;


/**
 * A generic translations package factory that will load translations files
 * based on the file extension and the package name.
 *
 * This class is a callable, so it can be used as a package loader argument.
 */
class MessagesFileLoader extends \Cake\I18n\MessagesFileLoader {
	/**
	 * Loads the translation file and parses it. Returns an instance of a translations
	 * package containing the messages loaded from the file.
	 *
	 * @return \Cake\I18n\Package|false
	 * @throws \RuntimeException if no file parser class could be found for the specified
	 * file extension.
	 */
	public function __invoke (): Package|false {
		$la_folders = $this->translationsFolders();
		$ls_extension = $this->_extension;

		$ls_fileName = $this->_name;
		$ls_subfolder = NULL;
		$li_strpos = strpos($ls_fileName, DS);
		if ($li_strpos !== FALSE) {
			$ls_subfolder = substr($ls_fileName, 0, $li_strpos + 1);
			$ls_fileName = substr($ls_fileName, $li_strpos + 1);
		}

		$ls_parserName = ucfirst($ls_extension);
		$ls_parserClass = App::className($ls_parserName, 'I18n\Parser', 'FileParser');

		if ( ! $ls_parserClass) {
			throw new RuntimeException(sprintf('Could not find class %s', "{$ls_parserName}FileParser"));
		}

		$lo_package = new Package('default');
		$lo_parser = new $ls_parserClass();

		foreach (array_reverse($la_folders) as $ls_folder) {
			$ls_path = $ls_folder . $ls_subfolder . $ls_fileName . '.' . $ls_extension;
			if (is_file($ls_path)) {
				$ls_filePath = $ls_path;
				$la_messages = $lo_parser->parse($ls_filePath);
				if ($la_messages) {
					$lo_package->addMessages($la_messages);
				}
			}
		}

		return $lo_package;
	}
}
