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

		$la_options = $options + [
			'includeWrapper' => true,
			'currentRoute' => Router::url($this->request->getRequestTarget()),
			'viewVars' => [],
		];

		if ($la_options['currentRoute'] === '/') {
			/** @var \Awyiss\Model\Entity\Page $lo_currentPage */
			$lo_currentPage = $this->request->getAttribute('currentPage');
			if ($lo_currentPage) {
				$la_options['currentRoute'] = '/' . $lo_currentPage->languageShortcode . '/' . $lo_currentPage->slug . '/';
			}
		}

		$this->options = $la_options;

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

		/** @var class-string<\Awyiss\Utility\Menu\FrontendMenu> $ls_menuClass */
		$ls_menuClass = App::className('FrontendMenu', 'Utility/Menu');
		/** @var class-string<\Awyiss\Utility\Menu\FrontendMenuItem> $ls_menuItemClass */
		$ls_menuItemClass = App::className('FrontendMenuItem', 'Utility/Menu');

		/** @see \Awyiss\Utility\Menu\FrontendMenu::__construct() */
		$lo_menu = new $ls_menuClass($lo_menuEntries->toArray(), [
			'active' => $lb_active,
			'identifier' => $lo_menuRecord?->identifier ? Inflector::ucparts($lo_menuRecord->identifier, false) : null,
			'menuClass' => $ls_menuClass,
			'menuItemClass' => $ls_menuItemClass,
		]);

		/** @var class-string<\Awyiss\Utility\Menu\MenuRenderer> $ls_menuRendererClass */
		$ls_menuRendererClass = App::className('MenuRenderer', 'Utility/Menu');
		/** @see \Awyiss\Utility\Menu\MenuRenderer::__construct() */
		$lo_renderer = new $ls_menuRendererClass($lo_menu, $this->rendererOptions);

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
	 * @return \Awyiss\Model\Entity\Menu|null
	 */
	protected function getMenu(string $identifier): ?MenuEntity {
		/** @var \Awyiss\Model\Table\MenusTable $lo_menusTable */
		$lo_menusTable = FactoryLocator::get('Table')->get('Menus');

		if ($this->isPreview()) {
			$lo_query = $lo_menusTable->find('all');
		}
		else {
			/**
			 * @uses \Awyiss\Model\Table::findActive()
			 * @uses \Awyiss\Model\Behavior\PublicationDataBehavior::findPublished()
			 */
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
			/**
			 * @uses \Awyiss\Model\Table::findActive()
			 * @uses \Awyiss\Model\Behavior\PublicationDataBehavior::findPublished()
			 */
			$lo_query = $lo_menuEntriesTable->find('active')->find('published');
		}

		$lo_menuEntries = $lo_query->find('threaded')->where([
			'menu_id' => $menu->id,
			'language_shortcode' => $languageShortcode,
		])->all();

		return $lo_menuEntries->filter(function (MenuEntry $menuEntry) {
			return $menuEntry->parentId === null;
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
		$la_data['isPreview'] = $lb_isPreview ? ' ' . FrontendView::getPreviewModeElementClass() : '';

		return $template->format('list', $la_data);
	}


	/**
	 * @param array $data
	 * @param \Cake\View\StringTemplate $template
	 * @return string
	 */
	public function renderItem(array $data, StringTemplate $template): string {
		$la_data = $data;

		$la_data['id'] = $data['item']->identifier;
		$la_data['identifier'] = Inflector::ucparts(Text::slug($data['title']), false);

		if (!empty($la_data['children'])) {
			$la_data['submenuTrigger'] = '<input type="checkbox" id="SubmenuTrigger-' . $la_data['id'] .  '" class="SubmenuTrigger" />' . PHP_EOL .
				'<label for="SubmenuTrigger-' . $la_data['id'] .  '" class="SubmenuTrigger-Label"></label>';
		}

		$la_data['isPreview'] = '';
		if ($this->isPreview() && !$data['item']->active) {
			$la_data['isPreview'] = ' ' . FrontendView::getPreviewModeElementClass();
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
		if (!empty($la_data['url'])) {
			$ls_template = 'link';

			if (isset($this->options['currentRoute']) && str_starts_with($la_data['url'], $this->options['currentRoute'] . '#') && Router::getRequest()->getPath() === '/') {
				$la_data['url'] = substr($la_data['url'], strlen($this->options['currentRoute']));
			}
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
