<?php declare(strict_types=1);


namespace Awyiss\Model\Entity;


use Awyiss\Model\Entity;
use Cake\I18n\FrozenTime;
use Cake\Utility\Text;


/**
 * PageTemplate Entity
 *
 * @property int $id
 * @property int $pageRoleId
 * @property string $title
 * @property string $filename
 * @property int $systemOrder
 * @property bool $active
 * @property bool $deleted
 * @property int|NULL $createdBy
 * @property FrozenTime|NULL $createdOn
 * @property int|NULL $changedBy
 * @property FrozenTime|NULL $changedOn
 * @property int|NULL $deletedBy
 * @property FrozenTime|NULL $deletedOn
 * @property PageRole $pageRole
 * @property ContentArea[] $contentAreas
 * @property PageTemplateContentArea[] $_joinData
 */
class PageTemplate extends Entity {
	/**
	 * @inheritDoc
	 */
	protected array $_accessible = [
		'pageRoleId' => TRUE,
		'title' => TRUE,
		'filename' => TRUE,
		'systemOrder' => TRUE,
		'active' => TRUE,
		'contentAreas' => TRUE,
	];
	/**
	 * @inheritDoc
	 */
	protected static array $fieldMap = [
		'page_role_id' => 'pageRoleId',
		'system_order' => 'systemOrder',
		'created_by' => 'createdBy',
		'created_on' => 'createdOn',
		'changed_by' => 'changedBy',
		'changed_on' => 'changedOn',
		'deleted_by' => 'deletedBy',
		'deleted_on' => 'deletedOn',
		'page_role' => 'pageRole',
		'content_areas' => 'contentAreas',
	];


	/**
	 * Make sure the filename is always lowercase, underscored and free of special characters
	 *
	 * @noinspection PhpUnused
	 */
	protected function _setFilename (string $as_filename): string {
		$ls_filename = Text::slug($as_filename, ['replacement' => '_']);

		return mb_strtolower($ls_filename);
	}
}
