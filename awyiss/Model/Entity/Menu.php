<?php declare(strict_types=1);


namespace Awyiss\Model\Entity;


use Awyiss\Model\Entity;
use Cake\Utility\Text;


/**
 * Menu Entity
 *
 * @property int $id
 * @property string|null $title
 * @property string|null $identifier
 * @property bool $active
 * @property bool $deleted
 * @property int|null $createdBy
 * @property \Cake\I18n\DateTime|null $createdOn
 * @property int|null $changedBy
 * @property \Cake\I18n\DateTime|null $changedOn
 * @property int|null $deletedBy
 * @property \Cake\I18n\DateTime|null $deletedOn
 * @property \Awyiss\Model\Entity\MenuEntry[] $menuEntries
 * @property \Awyiss\Model\Entity\CustomerGroupAccessSetting $customerGroupAccessSettings
 * @property \Awyiss\Model\Entity\CustomerGroupAssignment[] $customerGroupAssignments
 */
class Menu extends Entity {
	/**
	 * @inheritDoc
	 */
	protected static array $fieldMap = [
		'menu_entries' => 'menuEntries',
	];


	/**
	 * @inheritDoc
	 */
	protected array $_accessible = [ // phpcs:ignore
		'title' => true,
		'identifier' => true,
		'active' => true,
	];


	/**
	 * Make sure the identifier is always lowercase, underscored and free of special characters
	 *
	 * @param string|null $identifier
	 * @return string|null
	 * @see \Awyiss\Model\Entity\Menu::$identifier
	 */
	protected function _setIdentifier(?string $identifier): ?string {
		if ($identifier === null) {
			return null;
		}

		$identifier = Text::slug($identifier, ['replacement' => '_']);


		return mb_strtolower($identifier);
	}
}
