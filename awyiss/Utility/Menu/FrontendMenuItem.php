<?php declare(strict_types=1);


namespace Awyiss\Utility\Menu;


use Awyiss\Authorization\IdentityGroupPermissionInterface;
use Awyiss\Authorization\IdentityPermissionsInterface;
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
	 * @var \Awyiss\Model\Entity\MenuEntry
	 */
	protected MenuEntry $menuEntry;


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
		$this->menuEntry = $entity;

		$active = $entity->active;
		// If the item is active, but not published, it is not active
		if ($active) {
			$now = new DateTime();

			if (
				(
					$entity->publicationStart
					&& $entity->publicationStart > $now
				)
				|| (
					$entity->publicationEnd
					&& $entity->publicationEnd < $now
				)
			) {
				$active = false;
			}
		}

		$this->access = $this->convertAccess($entity);
		$this->active = $active;
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
		 */
		unset($config['identity']);
		$this->setConfig($config);
	}


	/**
	 * @inheritDoc
	 */
	public function isAccessibleBy(IdentityGroupPermissionInterface|IdentityPermissionsInterface|null $identity = null): ?bool {
		return $this->menuEntry->isAccessibleBy($identity);
	}


	/**
	 * @inheritDoc
	 */
	public function isAccessible(): ?bool {
		// Frontend menu items are always accessible, if not explicitly set otherwise
		return $this->accessible ?? empty($this->access);
	}


	/**
	 * @inheritDoc
	 */
	public function isVisible(): ?bool {
		// Frontend menu items are always visible, if not explicitly set otherwise
		return $this->visible ?? $this->isAccessible();
	}
}
