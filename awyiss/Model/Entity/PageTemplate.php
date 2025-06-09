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
 * @property \Awyiss\Model\Entity\ContentTemplateContentArea[] $contentTemplateContentAreas
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
		'contentTemplateContentAreas' => true,
	];
	/**
	 * @inheritDoc
	 */
	protected static array $fieldMap = [
		'page_role_id' => 'pageRoleId',
		'file_name' => 'fileName',
		'system_order' => 'systemOrder',
		'page_role' => 'pageRole',
		'content_areas' => 'contentAreas',
		'content_template_content_areas' => 'contentTemplateContentAreas',
		'used_for_pages' => 'usedForPages',
	];


	/**
	 * Make sure the filename is always lowercase, underscored and free of special characters
	 *
	 * @param string|null $fileName
	 * @return string|null
	 * @see \Awyiss\Model\Entity\PageTemplate::$filename
	 */
	protected function _setFileName(?string $fileName): ?string {
		if ($fileName === null) {
			return null;
		}

		$ls_fileName = Text::slug($fileName, ['replacement' => '_']);


		return mb_strtolower($ls_fileName);
	}


	/**
	 * @param mixed $pageRoleId
	 * @return \Awyiss\Model\Enum\PageRoleEnumInterface|int|null
	 */
	protected function _setPageRoleId(mixed $pageRoleId): PageRoleEnumInterface|int|null {
		if (is_string($pageRoleId)) {
			return (int)$pageRoleId;
		}

		return $pageRoleId;
	}
}
