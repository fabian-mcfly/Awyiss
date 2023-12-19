<?php declare(strict_types=1);


namespace Awyiss\Model\Entity;


use Awyiss\Model\Entity;
use Cake\I18n\FrozenTime;
use Cake\Utility\Inflector;
use Cake\Utility\Text;


/**
 * PageRole Entity
 *
 * @property int $id
 * @property string $title
 * @property string $identifier
 * @property bool $includeInLinklist
 * @property int $systemOrder
 * @property bool $active
 * @property bool $deleted
 * @property int|NULL $createdBy
 * @property FrozenTime|NULL $createdOn
 * @property int|NULL $changedBy
 * @property FrozenTime|NULL $changedOn
 * @property int|NULL $deletedBy
 * @property FrozenTime|NULL $deletedOn
 */
class PageRole extends Entity {
	/**
	 * @inheritDoc
	 */
	protected array $_accessible = [
		'title' => TRUE,
		'identifier' => TRUE,
		'includeInLinklist' => TRUE,
		'systemOrder' => TRUE,
		'active' => TRUE,
	];
	/**
	 * @inheritDoc
	 */
	protected static array $fieldMap = [
		'include_in_linklist' => 'includeInLinklist',
		'system_order' => 'systemOrder',
		'created_by' => 'createdBy',
		'created_on' => 'createdOn',
		'changed_by' => 'changedBy',
		'changed_on' => 'changedOn',
		'deleted_by' => 'deletedBy',
		'deleted_on' => 'deletedOn',
	];


	/**
	 * Make sure the identifier is always lowercase, underscored and free of special characters
	 *
	 * @noinspection PhpUnused
	 */
	protected function _setIdentifier (string $as_identifier): string {
		$ls_identifier = preg_replace('/\d/', '', $as_identifier);

		$ls_identifier = Text::slug($ls_identifier, ['replacement' => '_']);

		$ls_identifier = Inflector::singularize($ls_identifier);

		return strtolower($ls_identifier);
	}
}
