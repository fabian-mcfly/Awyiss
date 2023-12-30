<?php declare(strict_types=1);


namespace Awyiss\Utilities\Menu;


use Cake\Core\InstanceConfigTrait;
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
			'list' => null,
			'item' => null,
			'link' => null,
			'noLink' => null,
		],
		'maxLevel' => PHP_INT_MAX,
		'templates' => [
			'menu' => '<nav id="Menu-{{identifier}}">' . PHP_EOL . '{{list}}</nav>' . PHP_EOL,
			'list' => '<ul class="Level{{level}}">' . PHP_EOL . '{{content}}</ul>' . PHP_EOL,
			'item' => '<li class="Level{{level}}">' . PHP_EOL . '{{link}}{{children}}</li>' . PHP_EOL,
			'link' => '<a href="{{url}}" class="Level{{level}}"{{attributes}}>{{title}}</a>' . PHP_EOL,
			'noLink' => '<span class="Level{{level}}">{{title}}</span>' . PHP_EOL,
		],
	];
	/**
	 * @var \Awyiss\Utilities\Menu\Menu
	 */
	protected Menu $menu;
	/**
	 * @var \Cake\View\StringTemplate
	 */
	protected StringTemplate $templates;


	/**
	 * Constructor
	 *
	 * @param Menu $ao_menu Menu object to render.
	 * @param array $aa_config Configuration options for rendering.
	 */
	public function __construct(Menu $ao_menu, array $aa_config = []) {
		$this->menu = $ao_menu;

		$this->setConfig($aa_config);

		$this->templates = new StringTemplate();
		$this->templates->add($this->getConfig('templates'));
	}


	/**
	 * @param string $as_menuIdentifier
	 * @param string $ls_list
	 * @return string
	 */
	public function render(string $as_menuIdentifier = '', string $as_list = ''): string {
		$ls_list = $as_list;
		if (empty($ls_list)) {
			$ls_list = $this->renderList();
		}

		if (empty($ls_list)) {
			return '';
		}

		$la_data = [
			'list' => $ls_list,
			'identifier' => $as_menuIdentifier ?: $this->menu->getConfig('identifier') ?: 'Default',
			'menuConfig' => $this->menu->getConfig(),
		];


		return $this->format('menu', $la_data);
	}


	/**
	 * Renders the menu as HTML.
	 *
	 * @param \Awyiss\Utilities\Menu\Menu|\Awyiss\Utilities\Menu\MenuItem|string|null $ax_items
	 * @param int $level
	 * @param int|null $maxLevel
	 * @return string
	 */
	public function renderList(Menu|MenuItem|string|null $ax_items = null, int $level = 1, ?int $ai_maxLevel = null): string {
		if (is_string($ax_items)) {
			$lo_items = [$this->menu->getItem($ax_items)];
		}
		elseif ($ax_items instanceof MenuItem) {
			$lo_items = [$ax_items];
		}
		elseif ($ax_items instanceof Menu) {
			$lo_items = $ax_items->getItems();
		}
		else {
			$lo_items = $this->menu->getItems();
		}

		$li_maxLevel = $ai_maxLevel ?? $this->getConfig('maxLevel');

		$ls_content = '';
		foreach ($lo_items as $lo_item) {
			$ls_content .= $this->renderItem($lo_item, 1, $li_maxLevel);
		}

		if (empty($ls_content)) {
			return '';
		}

		$la_data = [
			'content' => $ls_content,
			'items' => $lo_items,
			'level' => $level,
			'menuConfig' => $this->menu->getConfig(),
		];


		return $this->format('list', $la_data);
	}


	/**
	 * Recursively renders a menu item and its children as HTML.
	 *
	 * @param MenuItem $item The menu item to render.
	 * @param int $level The current depth level.
	 * @param int $ai_maxLevel The maximum depth level to render.
	 * @return string Rendered HTML for the menu item.
	 */
	protected function renderItem(MenuItem $ao_item, int $ai_level = 1, int $ai_maxLevel = 1): string {
		if ($ai_level > $ai_maxLevel) {
			return '';
		}

		if (!$ao_item->isVisible()) {
			return '';
		}

		if ($this->getConfig('activeOnly') && !$ao_item->getActive()) {
			return '';
		}

		$ls_childrenContent = '';
		$lo_items = $ao_item->getChildren();
		if ($lo_items) {
			$ls_childrenContent .= $this->renderList($lo_items, $ai_level + 1, $ai_maxLevel);
		}

		$la_data = ['title' => $ao_item->getTitle(), 'level' => $ai_level];

		$lo_link = $ao_item->getLink();
		if ($lo_link && $ao_item->isAccessible()) {
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
			'link' => $ls_link,
			'level' => $ai_level,
			'children' => $ls_childrenContent,
		];


		return $this->format('item', $la_data);
	}


	/**
	 * Formats a given type (list, item, link) using either a custom formatter or a template.
	 *
	 * @param string $as_type The type of content to format.
	 * @param array $aa_data The data to use in the formatter or template.
	 * @return string The formatted content.
	 */
	protected function format(string $as_type, array $aa_data): string {
		$lc_formatter = $this->getConfig('formatters.' . $as_type);

		if (is_callable($lc_formatter)) {
			return $lc_formatter($aa_data, $this->menu);
		}


		return $this->templates->format($as_type, $aa_data);
	}
}
