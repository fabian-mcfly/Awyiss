<?php declare(strict_types=1);


namespace Awyiss\Model\Entity;


use Awyiss\Model\Entity;
use Cake\Utility\Text;


/**
 * PageTemplate Entity
 *
 * @property int $id
 * @property int $pageRoleId
 * @property string $title
 * @property string $fileName
 * @property int $systemOrder
 * @property bool $active
 * @property bool $deleted
 * @property int|null $createdBy
 * @property \Cake\I18n\DateTime|null $createdOn
 * @property int|null $changedBy
 * @property \Cake\I18n\DateTime|null $changedOn
 * @property int|null $deletedBy
 * @property \Cake\I18n\DateTime|null $deletedOn
 * @property PageRole $pageRole
 * @property ContentArea[] $contentAreas
 * @property PageTemplateContentArea[] $_joinData
 */
class PageTemplate extends Entity {
	/**
	 * @inheritDoc
	 */
	protected array $_accessible = [
		'pageRoleId' => true,
		'title' => true,
		'fileName' => true,
		'systemOrder' => true,
		'active' => true,
		'contentAreas' => true,
	];
	/**
	 * @inheritDoc
	 */
	protected static array $fieldMap = [
		'page_role_id' => 'pageRoleId',
		'file_name' => 'fileName',
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
	 * @see \Awyiss\Model\Entity\PageTemplate::$filename
	 */
	protected function _setFileName(string $as_fileName): string {
		$ls_fileName = Text::slug($as_fileName, ['replacement' => '_']);


		return mb_strtolower($ls_fileName);
	}
}
