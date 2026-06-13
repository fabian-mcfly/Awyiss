<?php declare(strict_types=1);


namespace Awyiss\View\Cell\Frontend;


use Awyiss\Core\App;
use Awyiss\Model\Entity\Menu as MenuEntity;
use Awyiss\Model\Entity\MenuEntry;
use Awyiss\Routing\Router;
use Awyiss\Utility\DebugTimer;
use Awyiss\Utility\Inflector;
use Awyiss\View\Cell\Frontend\Trait\PreviewTrait;
use Awyiss\View\Cell\Frontend\Trait\RenderTrimmedTrait;
use Awyiss\View\FrontendView;
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
	use RenderTrimmedTrait;


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
	 * Options for the display method
	 *
	 * @var array $options
	 */
	protected array $options = [];


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
	 * @param \Awyiss\View\FrontendView $view
	 * @param array $options
	 * @return void
	 */
	public function display(string $identifier, string $languageShortcode, FrontendView $view, array $options = []): void {
		DebugTimer::start('MenuCell::display', sprintf('MenuCell::display: Rendering menu "%s" for language "%s"', $identifier, $languageShortcode));

		$this->View = $view;

		$options += [
			'includeWrapper' => true,
			'currentRoute' => Router::url($this->request->getRequestTarget()),
			'viewVars' => [],
		];

		if ($options['currentRoute'] === '/') {
			/** @var \Awyiss\Model\Entity\Page $currentPage */
			$currentPage = $this->request->getAttribute('currentPage');
			if ($currentPage) {
				$options['currentRoute'] = '/' . $currentPage->languageShortcode . '/' . $currentPage->slug . '/';
			}
		}

		$this->options = $options;

		// Set the template for the view
		$this->viewBuilder()->setTemplatePath('Frontend/cell/Menu');

		$menuRecord = $this->getMenu($identifier);

		// If no menu is found or the user has no access, do not render the menu
		if (!$menuRecord) {
			DebugTimer::stop('MenuCell::display');

			return;
		}

		$menuEntries = $this->getMenuEntries($menuRecord, $languageShortcode);

		$active = $menuRecord->active;
		$now = new DateTime();
		if (
			($menuRecord->publicationStart && $menuRecord->publicationStart > $now) ||
			($menuRecord->publicationEnd && $menuRecord->publicationEnd < $now)
		) {
			$active = false;
		}

		/** @var class-string<\Awyiss\Utility\Menu\FrontendMenu> $menuClass */
		$menuClass = App::className('FrontendMenu', 'Utility/Menu');
		/** @var class-string<\Awyiss\Utility\Menu\FrontendMenuItem> $menuItemClass */
		$menuItemClass = App::className('FrontendMenuItem', 'Utility/Menu');

		/** @see \Awyiss\Utility\Menu\FrontendMenu::__construct() */
		$menu = new $menuClass($menuEntries->toArray(), [
			'active' => $active,
			'identifier' => $menuRecord->identifier ? Inflector::ucparts(Inflector::underscore($menuRecord->identifier), false) : null,
			'menuClass' => $menuClass,
			'menuItemClass' => $menuItemClass,
		]);

		/** @var class-string<\Awyiss\Utility\Menu\MenuRenderer> $menuRendererClass */
		$menuRendererClass = App::className('MenuRenderer', 'Utility/Menu');
		/** @see \Awyiss\Utility\Menu\MenuRenderer::__construct() */
		$renderer = new $menuRendererClass($menu, $this->rendererOptions);

		$renderer->setCurrentRoute($options['currentRoute']);
		$renderer->setConfig('identifier', Inflector::ucparts(Inflector::underscore($identifier), false));

		$this->set([
			'identifier' => $identifier,
			'menuEntries' => $menuEntries,
			'menu' => $menu,
			'menuRecord' => $menuRecord,
			'renderer' => $renderer,
			'currentRoute' => $options['currentRoute'],
			'includeWrapper' => !!$options['includeWrapper'],
			...$options['viewVars'],
		]);

		DebugTimer::stop('MenuCell::display');
	}


	/**
	 * @param string $identifier
	 * @return \Awyiss\Model\Entity\Menu|null
	 */
	protected function getMenu(string $identifier): ?MenuEntity {
		DebugTimer::start('MenuCell::getMenu', sprintf('MenuCell::getMenu: Loading menu "%s"', $identifier));

		/** @var \Awyiss\Model\Table\MenusTable $menusTable */
		$menusTable = FactoryLocator::get('Table')->get('Menus');

		if ($this->isPreview()) {
			$query = $menusTable->find('all');
		}
		else {
			/**
			 * @uses \Awyiss\Model\Behavior\CustomerGroupAccessSettingBehavior::findAccessible()
			 * @uses \Awyiss\Model\Table::findActive()
			 * @uses \Awyiss\Model\Behavior\PublicationDataBehavior::findPublished()
			 */
			$query = $menusTable
				->find('accessible')
				->find('active')
				->find('published');
		}

		/**
		 * Load the menu
		 * A `contains` for the menu entries does not save any queries,
		 * as the menu entries are loaded with translations and publication data,
		 * requiring a more complex query.
		 *
		 * @var \Awyiss\Model\Entity\Menu $menu
		 */
		$menu = $query->where([
			'identifier' => $identifier,
		])->first();

		DebugTimer::stop('MenuCell::getMenu');

		return $menu;
	}


	/**
	 * @param MenuEntity $menu
	 * @param string $languageShortcode
	 * @return \Cake\Collection\CollectionInterface
	 */
	protected function getMenuEntries(MenuEntity $menu, string $languageShortcode): CollectionInterface {
		DebugTimer::start('MenuCell::getMenuEntries', sprintf('MenuCell::getMenuEntries: Loading menu entries for menu "%s" and language "%s"', $menu->identifier, $languageShortcode));

		/** @var \Awyiss\Model\Table\MenuEntriesTable $menuEntriesTable */
		$menuEntriesTable = FactoryLocator::get('Table')->get('MenuEntries');

		if ($this->isPreview()) {
			$query = $menuEntriesTable->find('all');
		}
		else {
			/**
			 * @uses \Awyiss\Model\Behavior\CustomerGroupAccessSettingBehavior::findAccessible()
			 * @uses \Awyiss\Model\Table::findActive()
			 * @uses \Awyiss\Model\Behavior\PublicationDataBehavior::findPublished()
			 */
			$query = $menuEntriesTable
				->find('accessible')
				->find('active')
				->find('published');
		}

		$menuEntries = $query->find('threaded')->where([
			'menuId' => $menu->id,
			'languageShortcode' => $languageShortcode,
		])->all();

		$menuEntries = $menuEntries->filter(function (MenuEntry $menuEntry) {
			return $menuEntry->parentId === null;
		})->compile();

		DebugTimer::stop('MenuCell::getMenuEntries');

		return $menuEntries;
	}


	/**
	 * @param array $data
	 * @param \Cake\View\StringTemplate $template
	 * @return string
	 */
	public function renderList(array $data, StringTemplate $template): string {
		DebugTimer::start('MenuCell::renderList', sprintf('MenuCell::renderList: Rendering menu list at level %d', $data['level']));

		$isPreview = $data['level'] === 1 && $this->isPreview() && ($data['menuConfig']['active'] ?? true) === false;
		$data['isPreview'] = $isPreview ? ' ' . FrontendView::getPreviewModeElementClass() : '';

		$list = $template->format('list', $data);

		DebugTimer::stop('MenuCell::renderList');

		return $list;
	}


	/**
	 * @param array $data
	 * @param \Cake\View\StringTemplate $template
	 * @return string
	 */
	public function renderItem(array $data, StringTemplate $template): string {
		DebugTimer::start('MenuCell::renderItem', sprintf('MenuCell::renderItem: Rendering menu item "%s" at level %d', $data['title'], $data['level']));

		$data['id'] = $data['item']->identifier;
		$data['identifier'] = Inflector::ucparts(Inflector::underscore(Text::slug($data['title'])), FALSE);

		if (!empty($data['children'])) {
			$data['submenuTrigger'] = '<input type="checkbox" id="SubmenuTrigger-' . $data['id'] .  '" class="SubmenuTrigger" />' . PHP_EOL .
				'<label for="SubmenuTrigger-' . $data['id'] .  '" class="SubmenuTrigger-Label" tabindex="0">' . __('submenu_trigger') . '</label>';
		}

		$data['isPreview'] = '';
		if ($this->isPreview() && !$data['item']->active) {
			$data['isPreview'] = ' ' . FrontendView::getPreviewModeElementClass();
		}

		$item = $template->format('item', $data);

		DebugTimer::stop('MenuCell::renderItem');

		return $item;
	}


	/**
	 * @param array $data
	 * @param \Cake\View\StringTemplate $template
	 * @return string
	 */
	public function renderContent(array $data, StringTemplate $template): string {
		DebugTimer::start('MenuCell::renderContent', sprintf('MenuCell::renderContent: Rendering menu content "%s"', $data['title']));

		$data['tabindex'] = '';
		if (!empty($data['url'])) {
			$templateName = 'link';

			if (isset($this->options['currentRoute']) && str_starts_with($data['url'], $this->options['currentRoute'] . '#') && Router::getRequest()->getPath() === '/') {
				$data['url'] = substr($data['url'], strlen($this->options['currentRoute']));
			}
		}
		else {
			$templateName = 'noLink';
			if (!empty($data['children'])) {
				$data['tabindex'] = ' tabindex="0"';
			}
		}

		$data['identifier'] = Inflector::ucparts(Inflector::underscore(Text::slug($data['title'])), false);

		$content = $template->format($templateName, $data);

		DebugTimer::stop('MenuCell::renderContent');

		return $content;
	}
}
