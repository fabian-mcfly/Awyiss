<?php declare(strict_types=1);


namespace Awyiss\Model\Entity;


use Awyiss\Model\Entity;


/**
 * PageTemplateContentArea Entity
 *
 * @property int $id
 * @property int $pageTemplateId
 * @property int $contentAreaId
 * @property int $systemOrder
 * @property PageTemplate $pageTemplate
 * @property ContentArea $contentArea
 */
class PageTemplateContentArea extends Entity {
	/**
	 * @inheritDoc
	 */
	protected array $_accessible = [
		'pageTemplateId' => TRUE,
		'contentAreaId' => TRUE,
		'systemOrder' => TRUE,
	];
	/** @var array|string[] */
	protected array $_virtual = [];
	/**
	 * @inheritDoc
	 */
	protected static array $fieldMap = [
		'page_template_id' => 'pageTemplateId',
		'page_template' => 'pageTemplate',
		'content_area_id' => 'contentAreaId',
		'content_area' => 'contentArea',
		'system_order' => 'systemOrder',
	];
}
