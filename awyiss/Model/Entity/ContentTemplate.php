<?php declare(strict_types=1);


namespace Awyiss\Model\Entity;


use Awyiss\Model\Entity;
use Cake\Utility\Text;


/**
 * ContentTemplate Entity
 *
 * @property int $id
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
 * @property \Awyiss\Model\Entity\Content[] $contents
 * @property \Awyiss\Model\Entity\ContentTemplateElement[] $contentTemplateElements
 * @property \Awyiss\Model\Entity\ContentArea[] $contentAreas
 */
class ContentTemplate extends Entity {
	/**
	 * @inheritDoc
	 */
	protected array $_accessible = [
		'title' => true,
		'fileName' => true,
		'systemOrder' => true,
		'active' => true,
		'contentTemplateElements' => true,
		'contentAreas' => true,
	];
	/**
	 * @inheritDoc
	 */
	protected static array $fieldMap = [
		'file_name' => 'fileName',
		'content_template_elements' => 'contentTemplateElements',
		'content_areas' => 'contentAreas',
		'system_order' => 'systemOrder',
		'created_by' => 'createdBy',
		'created_on' => 'createdOn',
		'changed_by' => 'changedBy',
		'changed_on' => 'changedOn',
		'deleted_by' => 'deletedBy',
		'deleted_on' => 'deletedOn',
		'used_for_contents' => 'usedForContents',
	];


	/**
	 * Make sure the filename is always lowercase, underscored and free of special characters
	 *
	 * @param string|null $fileName
	 * @return string|null
	 * @see \Awyiss\Model\Entity\ContentTemplate::$filename
	 */
	protected function _setFileName(?string $fileName): ?string {
		if ($fileName === null) {
			return null;
		}

		$ls_fileName = Text::slug($fileName, ['replacement' => '_']);


		return mb_strtolower($ls_fileName);
	}
}
