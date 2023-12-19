<?php declare(strict_types=1);


namespace Awyiss\Model\Entity;


use Awyiss\Model\Entity;
use Cake\Utility\Text;


/**
 * PageRole Entity
 *
 * @property int $id
 * @property string $title
 * @property string $identifier
 * @property bool $include_in_linklist
 * @property int $system_order
 * @property bool $active
 * @property bool $deleted
 * @property int|NULL $created_by
 * @property \Cake\I18n\FrozenTime|NULL $created_on
 * @property int|NULL $changed_by
 * @property \Cake\I18n\FrozenTime|NULL $changed_on
 * @property int|NULL $deleted_by
 * @property \Cake\I18n\FrozenTime|NULL $deleted_on
 */
class PageRole extends Entity {
	/**
	 * @inheritDoc
	 */
	protected $_accessible = [
		'title' => TRUE,
		'identifier' => TRUE,
		'include_in_linklist' => TRUE,
		'system_order' => TRUE,
		'active' => TRUE,
	];


	/**
	 * Make sure the identifier is always lowercase, underscored and free of special characters
	 *
	 * @noinspection PhpUnused
	 */
	protected function _setIdentifier (string $as_identifier): string {
		$ls_identifier = Text::slug($as_identifier, ['replacement' => '_']);

		return mb_strtolower($ls_identifier);
	}
}
