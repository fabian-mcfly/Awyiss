<?php declare(strict_types=1);


namespace Awyiss\Model\Entity;


use Awyiss\Model\Entity;


/**
 * ContentArea Entity
 *
 * @property int $id
 * @property string|null $identifier
 * @property string|null $title
 * @property bool $active
 * @property bool $deleted
 * @property int|null $createdBy
 * @property \Cake\I18n\DateTime|null $createdOn
 * @property int|null $changedBy
 * @property \Cake\I18n\DateTime|null $changedOn
 * @property int|null $deletedBy
 * @property \Cake\I18n\DateTime|null $deletedOn
 * @property \Awyiss\Model\Entity\PageTemplate[] $pageTemplates
 * @property \Awyiss\Model\Entity\ContentTemplate[] $contentTemplates
 * @property \Awyiss\Model\Entity\ContentTemplateContentArea|\Awyiss\Model\Entity\PageTemplateContentArea $_joinData
 * @property \Awyiss\Model\Entity\ContentArea[] $contentAreas
 */
class ContentArea extends Entity {
	/**
	 * @inheritDoc
	 */
	protected static array $fieldMap = [
		'page_templates' => 'pageTemplates',
		'content_templates' => 'contentTemplates',
	];


	/**
	 * @inheritDoc
	 */
	protected array $_accessible = [
		'identifier' => true,
		'title' => true,
		'active' => true,
		'pageTemplates' => true,
		'contentTemplateContentAreas' => true,
	];
}
