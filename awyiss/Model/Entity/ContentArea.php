<?php declare(strict_types=1);


namespace Awyiss\Model\Entity;


use Awyiss\Model\Entity;
use Cake\I18n\DateTime;


/**
 * ContentArea Entity
 *
 * @property int $id
 * @property string $identifier
 * @property string|null $title
 * @property bool $active
 * @property bool $deleted
 * @property int|null $createdBy
 * @property DateTime|null $createdOn
 * @property int|null $changedBy
 * @property DateTime|null $changedOn
 * @property int|null $deletedBy
 * @property DateTime|null $deletedOn
 * @property PageTemplate[] $pageTemplates
 * @property PageTemplateContentArea[] $_joinData
 * @property ContentTemplateContentArea[] $contentTemplateContentAreas
 */
class ContentArea extends Entity {
	/**
	 * @inheritDoc
	 */
	protected array $_accessible = [
		'identifier' => TRUE,
		'title' => TRUE,
		'active' => TRUE,
		'pageTemplates' => TRUE,
		'contentTemplateContentAreas' => TRUE,
	];

	/**
	* @inheritDoc
	*/
	protected static array $fieldMap = [
		'created_by' => 'createdBy',
		'created_on' => 'createdOn',
		'changed_by' => 'changedBy',
		'changed_on' => 'changedOn',
		'deleted_by' => 'deletedBy',
		'deleted_on' => 'deletedOn',
		'page_templates' => 'pageTemplates',
		'content_template_content_areas' => 'contentTemplateContentAreas',
	];
}
