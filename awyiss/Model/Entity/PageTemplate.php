<?php declare(strict_types=1);


namespace Awyiss\Model\Entity;


use Awyiss\Model\Entity;
use Awyiss\Model\Enum\PageRoleEnumInterface;
use Cake\Utility\Text;


/**
 * PageTemplate Entity
 *
 * @property int $id
 * @property int|null $pageRoleId
 * @property string|null $title
 * @property string|null $fileName
 * @property int $systemOrder
 * @property bool $active
 * @property bool $deleted
 * @property int|null $createdBy
 * @property \Cake\I18n\DateTime|null $createdOn
 * @property int|null $changedBy
 * @property \Cake\I18n\DateTime|null $changedOn
 * @property int|null $deletedBy
 * @property \Cake\I18n\DateTime|null $deletedOn
 * @property \Awyiss\Model\Entity\PageRole $pageRole
 * @property \Awyiss\Model\Entity\ContentArea[] $contentAreas
 * @property \Awyiss\Model\Entity\PageTemplateContentArea[] $_joinData
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
		'used_for_pages' => 'usedForPages',
	];


	/**
	 * Make sure the filename is always lowercase, underscored and free of special characters
	 *
	 * @param string|null $as_fileName
	 * @return string|null
	 * @see \Awyiss\Model\Entity\PageTemplate::$filename
	 */
	protected function _setFileName(?string $as_fileName): ?string {
		if ($as_fileName === null) {
			return null;
		}

		$ls_fileName = Text::slug($as_fileName, ['replacement' => '_']);


		return mb_strtolower($ls_fileName);
	}


	/**
	 * @param mixed $ax_pageRoleId
	 * @return \Awyiss\Model\Enum\PageRoleEnumInterface|int|null
	 */
	protected function _setPageRoleId(mixed $ax_pageRoleId): PageRoleEnumInterface|int|null {
		if (is_string($ax_pageRoleId)) {
			return (int)$ax_pageRoleId;
		}

		return $ax_pageRoleId;
	}
}
