<?php declare(strict_types=1);


namespace Awyiss\Utility\Menu;


use Awyiss\Routing\Router;


/**
 * Class representing one menu link.
 */
class MenuItemLink {
	/**
	 * @var array|string|null
	 */
	protected array|string|null $url = null;
	/**
	 * @var string|null
	 */
	protected ?string $target = null;
	/**
	 * @var string|null
	 */
	protected ?string $rel = null;


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
		$la_attributes = [];
		if ($this->target) {
			$la_attributes['target'] = $this->target;
		}
		if ($this->rel) {
			$la_attributes['rel'] = $this->rel;
		}

		return $la_attributes;
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

		$ls_url = $this->url;
		if (!str_contains($this->url, '//')) {
			$ls_url = Router::url($ls_url);

			// Always ensure the URL ends with a slash if it doesn't contain a query string
			if (!str_contains($ls_url, '?') && !str_ends_with($ls_url, '/')) {
				$ls_url .= '/';
			}
		}

		return $ls_url;
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

		if (isset($link->target)) {
			$this->target = $link->target;
		}

		if (isset($link->rel)) {
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

		$la_parts = explode('::', $link);

		$ls_controller = array_shift($la_parts);
		$ls_action = array_shift($la_parts);

		if (empty($la_parts)) {
			$this->url = ['controller' => $ls_controller, 'action' => $ls_action];

			return;
		}

		$la_params = [];
		foreach ($la_parts as $lx_value) {
			$la_innerParts = explode(':', $lx_value);
			$la_params[ $la_innerParts[0] ] = $la_innerParts[1] ?? null;
		}
		$la_params = array_filter($la_params, function ($value) {
			return $value !== null;
		});

		$this->url = ['controller' => $ls_controller, 'action' => $ls_action] + $la_params;
	}
}
