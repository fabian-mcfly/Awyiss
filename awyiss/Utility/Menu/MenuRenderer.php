<?php declare(strict_types=1);


namespace Awyiss\Utility\Menu;


use Awyiss\Utility\Inflector;
use Cake\Core\InstanceConfigTrait;
use Cake\Utility\Text;
use Cake\View\StringTemplate;


/**
 * Class MenuRenderer
 *
 * Renders a Menu into a viewable format, like HTML, with customizable HTML parts.
 */
class MenuRenderer {
	use InstanceConfigTrait;


	/**
	 * @var array
	 */
	protected array $_defaultConfig = [
		'activeOnly' => true,
		'formatters' => [
			'menu' => null,
			'list' => null,
			'item' => null,
			'link' => null,
			'noLink' => null,
		],
		'maxLevel' => PHP_INT_MAX,
		'templates' => [
			'menu' => '<nav id="Menu-{{identifier}}">' . PHP_EOL . '{{list}}</nav>' . PHP_EOL,
			'list' => '<ul class="Level{{level}}{{identifier}}">' . PHP_EOL . '{{content}}</ul>' . PHP_EOL,
			'item' => '<li class="Level{{level}}{{active}}{{hasSubmenu}} MenuItem-{{identifier}}">' . PHP_EOL . '{{link}}{{children}}</li>' . PHP_EOL,
			'link' => '<a href="{{url}}" class="Level{{level}}{{active}} MenuItem-{{identifier}}"{{attributes}}>{{title}}</a>' . PHP_EOL,
			'noLink' => '<span class="Level{{level}}{{active}}">{{title}}</span>' . PHP_EOL,
		],
	];
	/**
	 * @var string The current route.
	 */
	protected string $currentRoute;
	/**
	 * @var string The identifier of the menu to render.
	 */
	protected string $identifier;
	/**
	 * @var \Awyiss\Utility\Menu\Menu
	 */
	protected Menu $menu;
	/**
	 * @var \Cake\View\StringTemplate
	 */
	protected StringTemplate $templates;


	/**
	 * Constructor
	 *
	 * @param Menu $menu Menu object to render.
	 * @param array $config Configuration options for rendering.
	 */
	public function __construct(Menu $menu, array $config = []) {
		$this->menu = $menu;

		$this->setConfig($config);

		$this->templates = new StringTemplate();
		$this->templates->add($this->getConfig('templates'));
	}


	/**
	 * @param string $menuIdentifier
	 * @param string $ls_list
	 * @return string
	 */
	public function render(string $menuIdentifier = '', string $list = ''): string {
		$this->identifier = $menuIdentifier ?: $this->menu->getConfig('identifier') ?: 'Default';

		$ls_list = $list;
		if (empty($ls_list)) {
			$ls_list = $this->renderList();
		}

		if (empty($ls_list)) {
			return '';
		}

		$la_data = [
			'list' => $ls_list,
			'identifier' => $this->identifier,
			'menuConfig' => $this->menu->getConfig(),
		];


		return $this->format('menu', $la_data);
	}


	/**
	 * Renders the menu as HTML.
	 *
	 * @param \Awyiss\Utility\Menu\Menu|\Awyiss\Utility\Menu\MenuItem|string|null $items
	 * @param int $level
	 * @param int|null $maxLevel
	 * @return string
	 */
	public function renderList(Menu|MenuItem|string|null $items = null, int $level = 1, ?int $maxLevel = null): string {
		if (is_string($items)) {
			$lo_items = [$this->menu->getItem($items)];
		}
		elseif ($items instanceof MenuItem) {
			$lo_items = [$items];
		}
		elseif ($items instanceof Menu) {
			$lo_items = $items->getItems();
		}
		else {
			$lo_items = $this->menu->getItems();
		}

		$li_maxLevel = $maxLevel ?? $this->getConfig('maxLevel');

		$ls_content = '';
		foreach ($lo_items as $lo_item) {
			$ls_content .= $this->renderItem($lo_item, $level, $li_maxLevel);
		}

		if (empty($ls_content)) {
			return '';
		}

		$la_data = [
			'content' => $ls_content,
			'items' => $lo_items,
			'level' => $level,
			'menuConfig' => $this->menu->getConfig(),
			'menu' => $this->menu,
		];

		if ($level === 1) {
			if (empty($this->identifier)) {
				$this->identifier = $this->getConfig('identifier') ?: 'Default';
			}

			$la_data['identifier'] = ' Menu-' . $this->identifier;
		}

		return $this->format('list', $la_data);
	}


	/**
	 * Sets the current route.
	 *
	 * @param string $currentRoute
	 * @return void
	 */
	public function setCurrentRoute(string $currentRoute): void {
		$this->currentRoute = rtrim($currentRoute, '/');
	}


	/**
	 * Recursively renders a menu item and its children as HTML.
	 *
	 * @param MenuItem $item The menu item to render.
	 * @param int $level The current depth level.
	 * @param int $maxLevel The maximum depth level to render.
	 * @return string Rendered HTML for the menu item.
	 */
	protected function renderItem(MenuItem $item, int $level = 1, int $maxLevel = 1): string {
		if ($level > $maxLevel) {
			return '';
		}

		if (!$item->isVisible()) {
			return '';
		}

		if ($this->getConfig('activeOnly') && !$item->getActive()) {
			return '';
		}

		$ls_childrenContent = '';
		$lo_items = $item->getChildren();
		if ($lo_items) {
			$ls_childrenContent .= $this->renderList($lo_items, $level + 1, $maxLevel);
		}

		$lx_identifier = $item->getIdentifier() ?? $item->getTitle();

		if (!isset($this->currentRoute)) {
			$this->currentRoute = '';
		}

		$la_data = [
			'active' => $item->isCurrentRoute($this->currentRoute) || $item->hasCurrentRoute($this->currentRoute) ? ' Active' : '',
			'children' => $ls_childrenContent,
			'identifier' => !is_string($lx_identifier) ? $lx_identifier : Inflector::camelize(Text::slug($lx_identifier, '_')),
			'level' => $level,
			'title' => $item->getTitle(),
			'item' => $item,
		];

		$lo_link = $item->getLink();
		if ($lo_link && $item->isAccessible()) {
			$la_data += [
				'attributes' => $this->templates->formatAttributes($lo_link->attributes),
				'url' => $lo_link->url,
			];

			$ls_link = $this->format('link', $la_data);
		}
		else {
			$ls_link = $this->format('noLink', $la_data);
		}

		$la_data = [
			'active' => $la_data['active'],
			'children' => $ls_childrenContent,
			'hasSubmenu' => $ls_childrenContent !== '' ? ' HasSubmenu' : '',
			'identifier' => $la_data['identifier'],
			'level' => $level,
			'link' => $ls_link,
			'title' => $la_data['title'],
			'item' => $item,
		];


		return $this->format('item', $la_data);
	}


	/**
	 * Formats a given type (list, item, link) using either a custom formatter or a template.
	 *
	 * @param string $type The type of content to format.
	 * @param array $data The data to use in the formatter or template.
	 * @return string The formatted content.
	 */
	protected function format(string $type, array $data): string {
		$lc_formatter = $this->getConfig('formatters.' . $type);

		if (is_callable($lc_formatter)) {
			return $lc_formatter($data, $this->templates, $this->menu);
		}


		return $this->templates->format($type, $data);
	}
}
