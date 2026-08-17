<?php declare(strict_types=1);


namespace Awyiss\Utility\Menu;


use Awyiss\Routing\Router;


/**
 * Class representing one menu link.
 */
class MenuItemLink {
	/**
	 * @var string|null
	 */
	protected ?string $rel = null;
	/**
	 * @var string|null
	 */
	protected ?string $target = null;
	/**
	 * @var array|string|null
	 */
	protected array|string|null $url = null;


	/**
	 * @param object|string $link
	 * @param bool $external
	 */
	public function __construct(object|string $link, bool $external = false) {
		if ($external) {
			$this->target = '_blank';
			$this->rel = 'noopener noreferrer';
		}

		if (!is_string($link)) {
			$this->setObjectLink($link);
		}
		else {
			$this->setStringLink($link);
		}
	}


	/**
	 * Returns the attributes of the menu item as an associative array.
	 *
	 * @return array
	 */
	public function getAttributes(): array {
		$attributes = [];
		if ($this->target) {
			$attributes['target'] = $this->target;
		}
		if ($this->rel) {
			$attributes['rel'] = $this->rel;
		}

		return $attributes;
	}


	/**
	 * Returns the rel attribute of the menu item.
	 *
	 * @return string|null
	 */
	public function getRel(): ?string {
		return $this->rel;
	}


	/**
	 * Sets the rel attribute of the menu item.
	 *
	 * @param string|null $rel The rel attribute to set, e.g. 'noopener noreferrer'.
	 * @param bool $overwrite If true, the existing rel attribute will be overwritten, otherwise it will be appended.
	 */
	public function setRel(?string $rel, bool $overwrite = false): static {
		if ($overwrite) {
			$this->rel = '';
		}

		$this->rel .= ' ' . $rel;
		$this->rel = trim($this->rel);

		return $this;
	}


	/**
	 * Returns the target of the menu item.
	 *
	 * @return string|null
	 */
	public function getTarget(): ?string {
		return $this->target;
	}


	/**
	 * Sets the target of the menu item.
	 *
	 * @param string|null $target The target to set, e.g. '_blank'.
	 */
	public function setTarget(?string $target): static {
		$this->target = $target;

		return $this;
	}


	/**
	 * Returns the URL of the menu item.
	 *
	 * @param bool $returnCompiled If true, the URL will be returned as a compiled route.
	 * @return array|string|null
	 */
	public function getUrl(bool $returnCompiled = true): array|string|null {
		if (!$returnCompiled || !$this->url) {
			return $this->url;
		}

		if (is_array($this->url)) {
			return Router::url($this->url);
		}

		$url = $this->url;
		if (!str_contains($this->url, '//')) {
			// If the URL is relative but does not start with a slash, add one.
			if (!str_starts_with($url, '/') && !empty(Router::getRequest()->getAttribute('base'))) {
				$url = '/' . $url;
			}

			$url = Router::url($url);

			$lastSegment = substr($url, strrpos($url, '/'));
			// Always ensure the URL ends with a slash if it doesn't contain a query string, unless it ends in a file extension
			if (
				!str_contains($url, '?')
				&& !str_contains($url, '#')
				&& !str_ends_with($url, '/')
				&& !str_contains($lastSegment, '.')
			) {
				$url .= '/';
			}
		}

		return $url;
	}


	/**
	 * Converts the link of the menu item from an object.
	 *
	 * @param object $link
	 * @return void
	 */
	protected function setObjectLink(object $link): void {
		if (isset($link->url)) {
			if (is_string($link->url)) {
				$this->setStringLink($link->url);
			}
			elseif (!is_array($link->url)) {
				$this->url = (array)$link->url;
			}
		}

		if (isset($link->attributes?->target)) {
			$this->target = $link->attributes->target;
		}
		if (isset($link->target)) {
			$this->target = $link->target;
		}

		if (isset($link->attributes?->rel)) {
			$this->rel = $link->attributes->rel;
		}
		elseif (isset($link->rel)) {
			$this->rel = $link->rel;
		}
	}


	/**
	 * Converts the link of the menu item from a string.
	 *
	 * @param string $link The link to convert from.
	 * @return void
	 */
	protected function setStringLink(string $link): void {
		if (!str_contains($link, '::')) {
			$this->url = $link;

			return;
		}

		$parts = explode('::', $link);

		$controller = array_shift($parts);
		$action = array_shift($parts);

		if (empty($parts)) {
			$this->url = ['controller' => $controller, 'action' => $action];

			return;
		}

		$params = [];
		foreach ($parts as $value) {
			$innerParts = explode(':', $value);
			$params[ $innerParts[0] ] = $innerParts[1] ?? null;
		}
		$params = array_filter($params, function ($value) {
			return $value !== null;
		});

		$this->url = ['controller' => $controller, 'action' => $action] + $params;
	}
}
