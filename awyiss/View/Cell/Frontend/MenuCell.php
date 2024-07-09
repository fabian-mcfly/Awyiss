<?php declare(strict_types=1);


namespace Awyiss\View\Cell\Frontend;


use Awyiss\Model\Entity\MenuEntry;
use Awyiss\Utility\Inflector;
use Awyiss\Utility\Menu\Menu;
use Awyiss\Utility\Menu\MenuRenderer;
use Cake\Collection\Collection;
use Cake\Collection\CollectionInterface;
use Cake\Datasource\FactoryLocator;
use Cake\Utility\Text;
use Cake\View\Cell;
use Cake\View\StringTemplate;


/**
 * Menu cell
 */
class MenuCell extends Cell {
	/**
	 * Options for the menu renderer
	 *
	 * @var array $rendererOptions
	 */
	protected array $rendererOptions = [
		'formatters' => [],
		'templates' => [
			'item' => '<li class="Level{{level}}{{active}}{{hasSubmenu}} {{identifier}}" id="MenuItem{{id}}">' . PHP_EOL . '{{submenuTrigger}}{{link}}{{children}}</li>' . PHP_EOL,
			'link' => '<a href="{{url}}" class="Level{{level}}{{active}} {{identifier}}"{{attributes}}>{{title}}</a>' . PHP_EOL,
			'noLink' => '<span class="Level{{level}}{{active}} {{identifier}}">{{title}}</span>' . PHP_EOL,
		],
	];


	/**
	 * Set the formatters if they are not set
	 *
	 * @return void
	 */
	public function initialize(): void {
		$this->rendererOptions['formatters']['item'] ??= $this->renderItem(...);
		$this->rendererOptions['formatters']['link'] ??= $this->renderContent(...);
		$this->rendererOptions['formatters']['noLink'] ??= $this->renderContent(...);
	}



	/**
	 * @param string $identifier
	 * @param string $languageShortcode
	 * @param array $options
	 * @return void
	 * @throws \ReflectionException
	 */
	public function display(string $identifier, string $languageShortcode, array $options = []): void {
		$la_options = $options + [
			'includeWrapper' => true,
			'currentRoute' => $this->request->getRequestTarget(),
			'viewVars' => [],
		];

		// Set the template for the view
		$this->viewBuilder()->setTemplatePath('Frontend/cell/Menu');

		$lo_menuEntries = $this->getMenuEntries($identifier, $languageShortcode);

		$lo_menu = new Menu($lo_menuEntries->toArray());
		$lo_renderer = new MenuRenderer($lo_menu, $this->rendererOptions);

		$lo_renderer->setCurrentRoute($la_options['currentRoute']);
		$lo_renderer->setConfig('identifier', Inflector::ucparts($identifier, false));

		$this->set([
			'identifier' => $identifier,
			'menuEntries' => $lo_menuEntries,
			'menu' => $lo_menu,
			'renderer' => $lo_renderer,
			'currentRoute' => $la_options['currentRoute'],
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
		$lo_menusTable = FactoryLocator::get('Table')->get('Menus');
		/** @var \Awyiss\Model\Entity\Menu $lo_menu */
		$lo_menu = $lo_menusTable->find('active')->find('published')->where([
			'identifier' => $identifier,
		])->first();

		if (!$lo_menu) {
			return new Collection([]);
		}

		$lo_menuEntriesTable = FactoryLocator::get('Table')->get('MenuEntries');
		$lo_menuEntries = $lo_menuEntriesTable->find('active')->find('published')->find('threaded')->where([
			'menu_id' => $lo_menu->id,
			'language_shortcode' => $languageShortcode,
		])->all();

		return $lo_menuEntries->filter(function (MenuEntry $content) {
			return $content->parentId === null;
		})->compile();
	}


	/**
	 * @param array $data
	 * @param \Cake\View\StringTemplate $template
	 * @return string
	 */
	public function renderItem(array $data, StringTemplate $template): string {
		$la_data = $data;

		$la_data['id'] = $data['item']->getEntity()->id;
		$la_data['identifier'] = Inflector::ucparts(Text::slug($data['title']), false);

		if (!empty($la_data['children'])) {
			$la_data['submenuTrigger'] = '<input type="checkbox" id="SubmenuTrigger-' . $la_data['id'] .  '" class="SubmenuTrigger" />' . PHP_EOL .
				'<label for="SubmenuTrigger-' . $la_data['id'] .  '" class="SubmenuTrigger-Label"></label>';
		}

		return $template->format('item', $la_data);
	}


	/**
	 * @param array $data
	 * @param \Cake\View\StringTemplate $template
	 * @return string
	 */
	public function renderContent(array $data, StringTemplate $template): string {
		$la_data = $data;

		if (isset($data['url'])) {
			$ls_template = 'link';
		}
		else {
			$ls_template = 'noLink';
		}

		$la_data['identifier'] = Inflector::ucparts(Text::slug($data['title']), false);

		return $template->format($ls_template, $la_data);
	}
}
