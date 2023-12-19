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

	protected $_defaultConfig = [
		'templates' => [
			'menu' => '<nav id="Menu-{{identifier}}">' . PHP_EOL . '{{list}}</nav>' . PHP_EOL,
			'list' => '<ul class="Level{{level}}">' . PHP_EOL . '{{content}}</ul>' . PHP_EOL,
			'item' => '<li class="Level{{level}}">' . PHP_EOL . '{{link}}{{children}}</li>' . PHP_EOL,
			'link' => '<a href="{{url}}" class="Level{{level}}" {{attributes}}>{{title}}</a>' . PHP_EOL,
			'noLink' => '<span class="Level{{level}}">{{title}}</span>' . PHP_EOL,
		],
		'formatters' => [
			'list' => NULL,
			'item' => NULL,
			'link' => NULL,
			'noLink' => NULL,
		],
		'maxLevel' => PHP_INT_MAX,
	];
	protected Menu $menu;
	protected StringTemplate $templates;


	/**
	 * Constructor
	 *
	 * @param Menu $menu Menu object to render.
	 * @param array $config Configuration options for rendering.
	 */
	public function __construct (Menu $menu, array $config = []) {
		$this->menu = $menu;

		$this->setConfig($config);

		$this->templates = new StringTemplate();
		$this->templates->add($this->getConfig('templates'));
	}


	public function render (string $menuIdentifier = '', string $list = ''): string {
		if (empty($list)) {
			$list = $this->renderList();
		}

		if (empty($list)) {
			return '';
		}

		$data = [
			'list' => $list,
			'identifier' => $menuIdentifier ?: $this->menu->getConfig('identifier') ?: 'Default',
			'menuConfig' => $this->menu->getConfig(),
		];

		return $this->format('menu', $data);
	}


	/**
	 * Renders the menu as HTML.
	 *
	 * @param string|null $identifier Identifier for a specific item to render its nested list. Null to render the whole menu.
	 *
	 * @return string Rendered HTML.
	 */
	public function renderList (Menu|MenuItem|string|null $items = NULL, int $level = 1, ?int $maxLevel = NULL): string {
		if (is_string($items)) {
			$items = [$this->menu->getItem($items)];
		}
		elseif ($items instanceof MenuItem) {
			$items = [$items];
		}
		elseif ($items instanceof Menu) {
			$items = $items->getItems();
		}
		else {
			$items = $this->menu->getItems();
		}

		if ($maxLevel === NULL) {
			$maxLevel = $this->getConfig('maxLevel');
		}

		$content = '';
		foreach ($items as $item) {
			$content .= $this->renderItem($item, 1, $maxLevel);
		}

		if (empty($content)) {
			return '';
		}

		$data = [
			'content' => $content,
			'items' => $items,
			'level' => $level,
			'menuConfig' => $this->menu->getConfig(),
		];

		return $this->format('list', $data);
	}


	/**
	 * Recursively renders a menu item and its children as HTML.
	 *
	 * @param MenuItem $item The menu item to render.
	 * @param int $level The current depth level.
	 * @param int $maxLevel The maximum depth level to render.
	 *
	 * @return string Rendered HTML for the menu item.
	 */
	protected function renderItem (MenuItem $item, int $level = 1, int $maxLevel = 1): string {
		if ($level > $maxLevel) {
			return '';
		}

		if ( ! $item->isVisible()) {
			return '';
		}

		$childrenContent = '';
		if ($items = $item->getChildren()) {
			$childrenContent .= $this->renderList($items, $level + 1, $maxLevel);
		}

		$data = ['title' => $item->getTitle(), 'level' => $level];

		if (($link = $item->getLink()) && $item->isAccessible()) {
			$data += [
				'attributes' => $this->templates->formatAttributes($link->attributes),
				'url' => $link->url,
			];

			$link = $this->format('link', $data);
		}
		else {
			$link = $this->format('noLink', $data);
		}

		$data = [
			'link' => $link,
			'level' => $level,
			'children' => $childrenContent,
		];

		return $this->format('item', $data);
	}


	/**
	 * Formats a given type (list, item, link) using either a custom formatter or a template.
	 *
	 * @param string $type The type of content to format.
	 * @param array $data The data to use in the formatter or template.
	 *
	 * @return string The formatted content.
	 */
	protected function format (string $type, array $data): string {
		$formatter = $this->getConfig('formatters.' . $type);

		if (is_callable($formatter)) {
			return $formatter($data, $this->menu);
		}

		return $this->templates->format($type, $data);
	}
}
