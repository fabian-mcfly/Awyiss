<?php declare(strict_types=1);


namespace Awyiss\Utility\Menu;


use Awyiss\Routing\Router;
use Awyiss\Utility\Inflector;
use Cake\Core\InstanceConfigTrait;
use Cake\Utility\Text;
use Cake\View\StringTemplate;
use InvalidArgumentException;


/**
 * Class MenuRenderer
 *
 * Renders a Menu into a viewable format, like HTML, with customizable HTML parts.
 */
class MenuRenderer {
	use InstanceConfigTrait;


	/**
	 * @var array
	 * @noinspection HtmlUnknownAttribute
	 */
	protected array $_defaultConfig = [ // phpcs:ignore
		'activeOnly' => true,
		'escapeTitle' => true,
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
			'noLink' => '<span class="Level{{level}}{{active}} MenuItem-{{identifier}}"{{attributes}}>{{title}}</span>' . PHP_EOL,
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
	 * @param string $list
	 * @return string
	 */
	public function render(string $menuIdentifier = '', string $list = ''): string {
		$this->identifier = $menuIdentifier ?: $this->menu->getConfig('identifier') ?: 'Default';

		if (empty($list)) {
			$list = $this->renderList();
		}

		if (empty($list)) {
			return '';
		}

		$data = [
			'list' => $list,
			'identifier' => $this->identifier,
			'menuConfig' => $this->menu->getConfig(),
		];


		return $this->format('menu', $data);
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

		$maxLevel = $maxLevel ?? $this->getConfig('maxLevel');

		$content = '';
		foreach ($items as $item) {
			$content .= $this->renderItem($item, $level, $maxLevel);
		}

		if (empty($content)) {
			return '';
		}

		$data = [
			'content' => $content,
			'items' => $items,
			'level' => $level,
			'menuConfig' => $this->menu->getConfig(),
			'menu' => $this->menu,
		];

		if ($level === 1) {
			if (empty($this->identifier)) {
				$this->identifier = $this->getConfig('identifier') ?: 'Default';
			}

			$data['identifier'] = ' Menu-' . $this->identifier;
		}

		return $this->format('list', $data);
	}


	/**
	 * Sets the current route.
	 *
	 * @param string $currentRoute
	 * @return void
	 */
	public function setCurrentRoute(string $currentRoute): void {
		$this->currentRoute = rtrim($currentRoute, '/') . '/';
	}


	/**
	 * Recursively renders a menu item and its children as HTML.
	 *
	 * @param \Awyiss\Utility\Menu\MenuItem $item The menu item to render.
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

		$childrenContent = '';
		$items = $item->getChildren();
		if ($items) {
			$childrenContent .= $this->renderList($items, $level + 1, $maxLevel);
		}

		$identifier = $item->getIdentifier() ?? $item->getTitle();

		if (!isset($this->currentRoute)) {
			$this->currentRoute = '';
		}

		$data = [
			'active' => $item->isCurrentRoute($this->currentRoute) || $item->hasCurrentRoute($this->currentRoute) ? ' Active' : '',
			'children' => $childrenContent,
			'identifier' => !is_string($identifier) ? $identifier : Inflector::camelize(Text::slug($identifier, '_')),
			'level' => $level,
			'title' => $this->escapeTitle($item->getTitle()),
			'item' => $item,
		];

		$link = $item->getLink();
		if ($link && $item->isAccessible()) {
			$url = $link->getUrl();
			$url = $this->optimizeUrl($url);

			$data += [
				'attributes' => $this->templates->formatAttributes($link->getAttributes()),
				'url' => $url,
			];

			$link = $this->format('link', $data);
		}
		else {
			$link = $this->format('noLink', $data);
		}

		$data = [
			'active' => $data['active'],
			'children' => $childrenContent,
			'hasSubmenu' => $childrenContent !== '' ? ' HasSubmenu' : '',
			'identifier' => $data['identifier'],
			'level' => $level,
			'link' => $link,
			'title' => $data['title'],
			'item' => $item,
		];


		return $this->format('item', $data);
	}


	/**
	 * Formats a given type (list, item, link) using either a custom formatter or a template.
	 *
	 * @param string $type The type of content to format.
	 * @param array $data The data to use in the formatter or template.
	 * @return string The formatted content.
	 */
	protected function format(string $type, array &$data): string {
		$formatter = $this->getConfig('formatters.' . $type);

		if (is_callable($formatter)) {
			return $formatter($data, $this->templates, $this->menu);
		}


		return $this->templates->format($type, $data);
	}


	/**
	 * @param string|null $title
	 * @return string|null
	 */
	protected function escapeTitle(?string $title): ?string {
		if (!$title) {
			return null;
		}

		$escape = $this->getConfig('escapeTitle');

		if (is_bool($escape)) {
			return $escape ? h($title) : $title;
		}

		// If it's a callable, call it with the title
		if (is_callable($escape)) {
			return $escape($title);
		}

		throw new InvalidArgumentException(
			sprintf('The escapeTitle configuration must be a boolean or callable, `%s` given.', gettype($escape))
		);
	}


	/**
	 * @param string|null $url
	 * @return string|null
	 */
	protected function optimizeUrl(?string $url): ?string {
		if (!$url) {
			return null;
		}

		$requestTarget = Router::getRequest()?->getRequestTarget();

		// If the request and the link are for the homepage, set the link to '/'
		if ($requestTarget === '/' && $url === $this->currentRoute) {
			$url = Router::url('/', true);
		}

		/*
		 * If the link is the current route and contains a '#', set the link to '#'
		 * so that it doesn't redirect to the current route again.
		 */
		if (str_contains($url, '#')) {
			$parts = explode('#', $url);
			$parts[0] = '/' . trim($parts[0], '/');

			if ($parts[0] === $this->currentRoute) {
				$url = '#' . $parts[1];
			}
		}

		return $url;
	}
}
