<?php declare(strict_types=1);


namespace Awyiss\Model\Entity;


use Awyiss\Model\Entity;
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
 * @property int|null $createdBy
 * @property \Cake\I18n\DateTime|null $createdOn
 * @property int|null $changedBy
 * @property \Cake\I18n\DateTime|null $changedOn
 * @property int|null $deletedBy
 * @property \Cake\I18n\DateTime|null $deletedOn
 * @property Content[] $contents
 * @property ContentTemplateElement[] $contentTemplateElements
 * @property ContentArea[] $contentAreas
 */
class ContentTemplate extends Entity {
	/**
	 * @inheritDoc
	 */
	protected array $_accessible = [
		'title' => true,
		'filename' => true,
		'systemOrder' => true,
		'active' => true,
		'contentTemplateElements' => true,
		'contentAreas' => true,
	];
	/**
	 * @inheritDoc
	 */
	protected static array $fieldMap = [
		'content_template_elements' => 'contentTemplateElements',
		'content_areas' => 'contentAreas',
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
	 * @see \Awyiss\Model\Entity\ContentTemplate::$filename
	 */
	protected function _setFilename(string $as_filename): string {
		$ls_filename = Text::slug($as_filename, ['replacement' => '_']);


		return mb_strtolower($ls_filename);
	}
}
