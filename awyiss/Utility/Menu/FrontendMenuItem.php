<?php declare(strict_types=1);


namespace Awyiss\Utility\Menu;


use Awyiss\Model\Entity\MenuEntry;
use Cake\I18n\DateTime;


/**
 * Class representing a menu item for the frontend.
 *
 * Overrides the isAccessible and isVisible methods to always return true,
 * unless explicitly set otherwise.
 */
class FrontendMenuItem extends MenuItem {
	/**
	 * @param \Awyiss\Model\Entity\MenuEntry $entity
	 * @param array $config
	 * @param int $level
	 * @throws \ReflectionException
	 * @noinspection DuplicatedCode
	 */
	public function __construct(
		MenuEntry $entity,
		array $config = [],
		int $level = 1
	) {
		$lb_active = $entity->active;
		// If the item is active, but not published, it is not active
		if ($lb_active) {
			$ld_now = new DateTime();

			if (
				($entity->publicationStart && $entity->publicationStart > $ld_now) ||
				($entity->publicationEnd && $entity->publicationEnd < $ld_now)
			) {
				$lb_active = false;
			}
		}

		$this->access = $this->convertAccess($entity);
		$this->active = $lb_active;
		$this->identifier = $entity->id;
		$this->level = $level;
		$this->link = $this->convertLink($entity);
		$this->title = $this->convertTitle($entity);

		if (isset($entity->children)) {
			$this->setChildren($entity->children, $config);
		}

		if (!empty($config['identity'])) {
			$this->setIdentity($config['identity']);
		}
		/**
		 * Make sure to not set the identity in the config to avoid confusion
		 *
		 * @noinspection PhpVariableNamingConventionInspection
		 */
		unset($config['identity']);
		$this->setConfig($config);
	}


	/**
	 * @inheritDoc
	 */
	public function isAccessible(): ?bool {
		// Frontend menu items are always accessible, if not explicitly set otherwise
		return $this->accessible ?? true;
	}


	/**
	 * @inheritDoc
	 */
	public function isVisible(): ?bool {
		// Frontend menu items are always visible, if not explicitly set otherwise
		return $this->visible ?? true;
	}
}
