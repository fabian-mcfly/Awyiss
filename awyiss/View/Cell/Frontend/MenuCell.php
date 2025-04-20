<?php declare(strict_types=1);


namespace Awyiss\View\Cell\Frontend;


use Awyiss\Awyiss;
use Awyiss\Core\App;
use Awyiss\Model\Entity\Menu as MenuEntity;
use Awyiss\Model\Entity\MenuEntry;
use Awyiss\Utility\Inflector;
use Awyiss\View\Cell\Frontend\Trait\PreviewTrait;
use Cake\Collection\Collection;
use Cake\Collection\CollectionInterface;
use Cake\Datasource\FactoryLocator;
use Cake\I18n\DateTime;
use Cake\Utility\Text;
use Cake\View\Cell;
use Cake\View\StringTemplate;


/**
 * Menu cell
 */
class MenuCell extends Cell {
	use PreviewTrait;


	/**
	 * Options for the menu renderer
	 *
	 * @var array $rendererOptions
	 * @noinspection HtmlUnknownAttribute
	 */
	protected array $rendererOptions = [
		'formatters' => [],
		'templates' => [
			'list' => '<ul class="Level{{level}}{{identifier}}{{isPreview}}">' . PHP_EOL . '{{content}}</ul>' . PHP_EOL,
			'item' => '<li class="Level{{level}}{{active}}{{hasSubmenu}}{{isPreview}} {{identifier}}" id="MenuItem{{id}}">' . PHP_EOL . '{{submenuTrigger}}{{link}}{{children}}</li>' . PHP_EOL,
			'link' => '<a href="{{url}}" class="Level{{level}}{{active}} {{identifier}}"{{attributes}}>{{title}}</a>' . PHP_EOL,
			'noLink' => '<span class="Level{{level}}{{active}} {{identifier}}"{{tabindex}}>{{title}}</span>' . PHP_EOL,
		],
	];


	/**
	 * Set the formatters if they are not set
	 *
	 * @return void
	 */
	public function initialize(): void {
		//$this->rendererOptions['formatters']['menu'] ??= $this->renderMenu(...);
		$this->rendererOptions['formatters']['list'] ??= $this->renderList(...);
		$this->rendererOptions['formatters']['item'] ??= $this->renderItem(...);
		$this->rendererOptions['formatters']['link'] ??= $this->renderContent(...);
		$this->rendererOptions['formatters']['noLink'] ??= $this->renderContent(...);

		if ($this->isPreview()) {
			$this->rendererOptions['activeOnly'] = false;
		}
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

		if ($la_options['currentRoute'] === '/') {
			/** @var \Awyiss\Model\Entity\Page $lo_currentPage */
			$lo_currentPage = $this->request->getAttribute('currentPage');
			if ($lo_currentPage) {
				$la_options['currentRoute'] = '/' . $lo_currentPage->languageShortcode . '/' . $lo_currentPage->slug . '/';
			}
		}

		// Set the template for the view
		$this->viewBuilder()->setTemplatePath('Frontend/cell/Menu');

		$lo_menuRecord = $this->getMenu($identifier);
		$lo_menuEntries = $lo_menuRecord ? $this->getMenuEntries($lo_menuRecord, $languageShortcode) : new Collection([]);

		$lb_active = false;
		if ($lo_menuRecord) {
			$lb_active = $lo_menuRecord->active;

			$ld_now = new DateTime();
			if (
				($lo_menuRecord->publicationStart && $lo_menuRecord->publicationStart > $ld_now) ||
				($lo_menuRecord->publicationEnd && $lo_menuRecord->publicationEnd < $ld_now)
			) {
				$lb_active = false;
			}
		}

		/** @var class-string<\Awyiss\Utility\Menu\Menu> $ls_className */
		$ls_className = App::className('Menu', 'Utility/Menu');

		$lo_menu = new $ls_className($lo_menuEntries->toArray(), [
			'active' => $lb_active,
		]);

		/** @var class-string<\Awyiss\Utility\Menu\MenuRenderer> $ls_className */
		$ls_className = App::className('MenuRenderer', 'Utility/Menu');

		$lo_renderer = new $ls_className($lo_menu, $this->rendererOptions);

		$lo_renderer->setCurrentRoute($la_options['currentRoute']);
		$lo_renderer->setConfig('identifier', Inflector::ucparts($identifier, false));

		$this->set([
			'identifier' => $identifier,
			'menuEntries' => $lo_menuEntries,
			'menu' => $lo_menu,
			'menuRecord' => $lo_menuRecord,
			'renderer' => $lo_renderer,
			'currentRoute' => $la_options['currentRoute'],
			'includeWrapper' => !!$la_options['includeWrapper'],
			...$la_options['viewVars'],
		]);
	}


	/**
	 * @param string $identifier
	 * @return MenuEntity
	 */
	protected function getMenu(string $identifier): ?MenuEntity {
		/** @var \Awyiss\Model\Table\MenusTable $lo_menusTable */
		$lo_menusTable = FactoryLocator::get('Table')->get('Menus');

		if ($this->isPreview()) {
			$lo_query = $lo_menusTable->find('all');
		}
		else {
			$lo_query = $lo_menusTable->find('active')->find('published');
		}

		/**
		 * Load the menu
		 * A `contains` for the menu entries does not save any queries,
		 * as the menu entries are loaded with translations and publication data,
		 * requiring a more complex query.
		 *
		 * @var MenuEntity $lo_menu
		 */
		$lo_menu = $lo_query->where([
			'identifier' => $identifier,
		])->first();

		return $lo_menu;
	}


	/**
	 * @param MenuEntity $menu
	 * @param string $languageShortcode
	 * @return \Cake\Collection\CollectionInterface
	 */
	protected function getMenuEntries(MenuEntity $menu, string $languageShortcode): CollectionInterface {
		/** @var \Awyiss\Model\Table\MenuEntriesTable $lo_menuEntriesTable */
		$lo_menuEntriesTable = FactoryLocator::get('Table')->get('MenuEntries');

		if ($this->isPreview()) {
			$lo_query = $lo_menuEntriesTable->find('all');
		}
		else {
			$lo_query = $lo_menuEntriesTable->find('active')->find('published');
		}

		$lo_menuEntries = $lo_query->find('threaded')->where([
			'menu_id' => $menu->id,
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
	public function renderList(array $data, StringTemplate $template): string {
		$la_data = $data;

		$lb_isPreview = $data['level'] === 1 && $this->isPreview() && ($la_data['menuConfig']['active'] ?? true) === false;
		$la_data['isPreview'] = $lb_isPreview ? ' ' . Awyiss::PREVIEW_MODE_ELEMENT_CLASSNAME : '';

		return $template->format('list', $la_data);
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

		$la_data['isPreview'] = '';
		if ($this->isPreview()) {
			$lo_entity = $data['item']->getEntity();
			$lb_active = $lo_entity->active ?? true;

			$ld_now = new DateTime();
			// If the item is active, but not published, it is not active
			if (
				$lb_active &&
				(
					($lo_entity->publicationStart && $lo_entity->publicationStart > $ld_now) ||
					($lo_entity->publicationEnd && $lo_entity->publicationEnd < $ld_now)
				)
			) {
				$lb_active = false;
			}

			if (!$lb_active) {
				$la_data['isPreview'] = ' ' . Awyiss::PREVIEW_MODE_ELEMENT_CLASSNAME;
			}
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

		$la_data['tabindex'] = '';
		if (isset($data['url'])) {
			$ls_template = 'link';
		}
		else {
			$ls_template = 'noLink';
			if (!empty($la_data['children'])) {
				$la_data['tabindex'] = ' tabindex="0"';
			}
		}

		$la_data['identifier'] = Inflector::ucparts(Text::slug($data['title']), false);

		return $template->format($ls_template, $la_data);
	}
}
