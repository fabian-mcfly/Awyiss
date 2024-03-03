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
 * @property \Awyiss\Model\Entity\MenuEntry[] $allMenuEntries
 */
class Menu extends Entity {
	/**
	 * @inheritDoc
	 */
	protected array $_accessible = [
		'title' => true,
		'identifier' => true,
		'active' => true,
	];
	/**
	 * @inheritDoc
	 */
	protected static array $fieldMap = [
		'created_by' => 'createdBy',
		'created_on' => 'createdOn',
		'changed_by' => 'changedBy',
		'changed_on' => 'changedOn',
		'deleted_by' => 'deletedBy',
		'deleted_on' => 'deletedOn',
		'menu_entries' => 'menuEntries',
		'all_menu_entries' => 'allMenuEntries',
	];


	/**
	 * Make sure the identifier is always lowercase, underscored and free of special characters
	 *
	 * @param string|null $as_identifier
	 * @return string|null
	 * @see \Awyiss\Model\Entity\Menu::$identifier
	 */
	protected function _setIdentifier(?string $as_identifier): ?string {
		if ($as_identifier === null) {
			return null;
		}

		$ls_identifier = Text::slug($as_identifier, ['replacement' => '_']);


		return mb_strtolower($ls_identifier);
	}
}
