<?php declare(strict_types=1);


namespace Awyiss\View\Cell\Frontend;


use Awyiss\Core\App;
use Awyiss\Model\Entity\Menu as MenuEntity;
use Awyiss\Model\Entity\MenuEntry;
use Awyiss\Routing\Router;
use Awyiss\Utility\Inflector;
use Awyiss\View\Cell\Frontend\Trait\PreviewTrait;
use Awyiss\View\Cell\Frontend\Trait\RenderTrimmedTrait;
use Awyiss\View\FrontendView;
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
		$menuEntries = $menuRecord ? $this->getMenuEntries($menuRecord, $languageShortcode) : new Collection([]);

		$active = false;
		if ($menuRecord) {
			$active = $menuRecord->active;

			$now = new DateTime();
			if (
				($menuRecord->publicationStart && $menuRecord->publicationStart > $now) ||
				($menuRecord->publicationEnd && $menuRecord->publicationEnd < $now)
			) {
				$active = false;
			}
		}

		/** @var class-string<\Awyiss\Utility\Menu\FrontendMenu> $menuClass */
		$menuClass = App::className('FrontendMenu', 'Utility/Menu');
		/** @var class-string<\Awyiss\Utility\Menu\FrontendMenuItem> $menuItemClass */
		$menuItemClass = App::className('FrontendMenuItem', 'Utility/Menu');

		/** @see \Awyiss\Utility\Menu\FrontendMenu::__construct() */
		$menu = new $menuClass($menuEntries->toArray(), [
			'active' => $active,
			'identifier' => $menuRecord?->identifier ? Inflector::ucparts($menuRecord->identifier, false) : null,
			'menuClass' => $menuClass,
			'menuItemClass' => $menuItemClass,
		]);

		/** @var class-string<\Awyiss\Utility\Menu\MenuRenderer> $menuRendererClass */
		$menuRendererClass = App::className('MenuRenderer', 'Utility/Menu');
		/** @see \Awyiss\Utility\Menu\MenuRenderer::__construct() */
		$renderer = new $menuRendererClass($menu, $this->rendererOptions);

		$renderer->setCurrentRoute($options['currentRoute']);
		$renderer->setConfig('identifier', Inflector::ucparts($identifier, false));

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
	}


	/**
	 * @param string $identifier
	 * @return \Awyiss\Model\Entity\Menu|null
	 */
	protected function getMenu(string $identifier): ?MenuEntity {
		/** @var \Awyiss\Model\Table\MenusTable $menusTable */
		$menusTable = FactoryLocator::get('Table')->get('Menus');

		if ($this->isPreview()) {
			$query = $menusTable->find('all');
		}
		else {
			/**
			 * @uses \Awyiss\Model\Table::findActive()
			 * @uses \Awyiss\Model\Behavior\PublicationDataBehavior::findPublished()
			 */
			$query = $menusTable->find('active')->find('published');
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

		return $menu;
	}


	/**
	 * @param MenuEntity $menu
	 * @param string $languageShortcode
	 * @return \Cake\Collection\CollectionInterface
	 */
	protected function getMenuEntries(MenuEntity $menu, string $languageShortcode): CollectionInterface {
		/** @var \Awyiss\Model\Table\MenuEntriesTable $menuEntriesTable */
		$menuEntriesTable = FactoryLocator::get('Table')->get('MenuEntries');

		if ($this->isPreview()) {
			$query = $menuEntriesTable->find('all');
		}
		else {
			/**
			 * @uses \Awyiss\Model\Table::findActive()
			 * @uses \Awyiss\Model\Behavior\PublicationDataBehavior::findPublished()
			 */
			$query = $menuEntriesTable->find('active')->find('published');
		}

		$menuEntries = $query->find('threaded')->where([
			'menu_id' => $menu->id,
			'language_shortcode' => $languageShortcode,
		])->all();

		return $menuEntries->filter(function (MenuEntry $menuEntry) {
			return $menuEntry->parentId === null;
		})->compile();
	}


	/**
	 * @param array $data
	 * @param \Cake\View\StringTemplate $template
	 * @return string
	 */
	public function renderList(array $data, StringTemplate $template): string {
		$isPreview = $data['level'] === 1 && $this->isPreview() && ($data['menuConfig']['active'] ?? true) === false;
		$data['isPreview'] = $isPreview ? ' ' . FrontendView::getPreviewModeElementClass() : '';

		return $template->format('list', $data);
	}


	/**
	 * @param array $data
	 * @param \Cake\View\StringTemplate $template
	 * @return string
	 */
	public function renderItem(array $data, StringTemplate $template): string {
		$data['id'] = $data['item']->identifier;
		$data['identifier'] = Inflector::ucparts(Text::slug($data['title']), false);

		if (!empty($data['children'])) {
			$data['submenuTrigger'] = '<input type="checkbox" id="SubmenuTrigger-' . $data['id'] .  '" class="SubmenuTrigger" />' . PHP_EOL .
				'<label for="SubmenuTrigger-' . $data['id'] .  '" class="SubmenuTrigger-Label" tabindex="0">' . __('submenu_trigger') . '</label>';
		}

		$data['isPreview'] = '';
		if ($this->isPreview() && !$data['item']->active) {
			$data['isPreview'] = ' ' . FrontendView::getPreviewModeElementClass();
		}

		return $template->format('item', $data);
	}


	/**
	 * @param array $data
	 * @param \Cake\View\StringTemplate $template
	 * @return string
	 */
	public function renderContent(array $data, StringTemplate $template): string {
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

		$data['identifier'] = Inflector::ucparts(Text::slug($data['title']), false);

		return $template->format($templateName, $data);
	}
}
