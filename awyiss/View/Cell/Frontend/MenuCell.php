<?php declare(strict_types=1);


namespace Awyiss\View\Cell\Frontend;


use Awyiss\Model\Entity\MenuEntry;
use Awyiss\Utility\Menu\Menu;
use Awyiss\Utility\Menu\MenuRenderer;
use Cake\Collection\CollectionInterface;
use Cake\Datasource\FactoryLocator;
use Cake\View\Cell;


/**
 * Menu cell
 */
class MenuCell extends Cell {
	/**
	 * @param string $identifier
	 * @param string $languageShortcode
	 * @return void
	 * @throws \ReflectionException
	 */
	public function display(string $identifier, string $languageShortcode, array $options = []): void {
		$la_options = $options + [
			'includeWrapper' => true,
			'viewVars' => [],
		];

		// Set the template for the view
		$this->viewBuilder()->setTemplatePath('Frontend/cell/Menu');

		$lo_menuEntries = $this->getMenuEntries($identifier, $languageShortcode);

		$lo_menu = new Menu($lo_menuEntries->toArray());
		$lo_renderer = new MenuRenderer($lo_menu);

		$this->set([
			'identifier' => $identifier,
			'menuEntries' => $lo_menuEntries,
			'menu' => $lo_menu,
			'renderer' => $lo_renderer,
			'includeWrapper' => !!$la_options['includeWrapper'],
			...$la_options['viewVars'],
		]);
	}


	/**
	 * @param string $identifier
	 * @param string $languageShortcode
	 * @return \Cake\Collection\CollectionInterface
	 */
	protected function getMenuEntries(string $identifier, string $languageShortcode): CollectionInterface {
		$lo_menuEntriesTable = FactoryLocator::get('Table')->get('MenuEntries');
		$lo_menuEntries = $lo_menuEntriesTable->find('active')->find('threaded')->where([
			'language_shortcode' => $languageShortcode,
			'Menus.identifier' => $identifier,
		])->contain([
			'Menus',
		])->all();

		return $lo_menuEntries->filter(function (MenuEntry $content) {
			return $content->parentId === null;
		})->compile();
	}
}
