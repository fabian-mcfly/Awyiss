<?php declare(strict_types=1);


namespace Awyiss\Model\Entity;


use Awyiss\Model\Entity;


/**
 * ContentTemplateContentArea Entity
 *
 * @property int $id
 * @property int $contentTemplateId
 * @property int $contentAreaId
 * @property int $pageTemplateId
 * @property ContentTemplate $contentTemplate
 * @property ContentArea $contentArea
 * @property PageTemplate $pageTemplate
 */
class ContentTemplateContentArea extends Entity {
	/**
	 * @inheritDoc
	 */
	protected array $_accessible = [
		'contentTemplateId' => TRUE,
		'contentAreaId' => TRUE,
		'pageTemplateId' => TRUE,
	];

	/**
	* @inheritDoc
	*/
	protected static array $fieldMap = [
		'content_template_id' => 'contentTemplateId',
		'content_area_id' => 'contentAreaId',
		'page_template_id' => 'pageTemplateId',
		'content_template' => 'contentTemplate',
		'content_area' => 'contentArea',
		'page_template' => 'pageTemplate',
	];
	/** @var array|string[] */
	protected array $_virtual = [];
}
