<?php declare(strict_types=1);


namespace Awyiss\Model\Entity;


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
 * @property int|null $created_by
 * @property \Cake\I18n\FrozenTime|null $created_on
 * @property int|null $changed_by
 * @property \Cake\I18n\FrozenTime|null $changed_on
 * @property int|null $deleted_by
 * @property \Cake\I18n\FrozenTime|null $deleted_on
 */
class PageRole extends \Awyiss\Model\Entity {
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
	 * @noinspection PhpUnused
	 */
	protected function _setIdentifier (string $as_identifier): string {
		$ls_identifier = \Cake\Utility\Text::slug($as_identifier, ['replacement' => '_']);

		return mb_strtolower($ls_identifier);
	}
}
