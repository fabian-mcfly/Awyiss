<?php declare(strict_types=1);


namespace Awyiss\Model\Entity;


use Awyiss\Model\Entity;
use Cake\I18n\FrozenTime;
use Cake\Utility\Text;


/**
 * ContentTemplate Entity
 *
 * @property int $id
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
 * @property Content[] $contents
 * @property ContentTemplateElement[] $contentTemplateElements
 * @property ContentTemplateContentArea[] $contentTemplateContentAreas
 */
class ContentTemplate extends Entity {
	/**
	 * @inheritDoc
	 */
	protected array $_accessible = [
		'title' => TRUE,
		'filename' => TRUE,
		'systemOrder' => TRUE,
		'active' => TRUE,
		'contentTemplateElements' => TRUE,
		'contentTemplateContentAreas' => TRUE,
	];
	/**
	 * @inheritDoc
	 */
	protected static array $fieldMap = [
		'content_template_elements' => 'contentTemplateElements',
		'content_template_content_areas' => 'contentTemplateContentAreas',
		'system_order' => 'systemOrder',
		'created_by' => 'createdBy',
		'created_on' => 'createdOn',
		'changed_by' => 'changedBy',
		'changed_on' => 'changedOn',
		'deleted_by' => 'deletedBy',
		'deleted_on' => 'deletedOn',
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
