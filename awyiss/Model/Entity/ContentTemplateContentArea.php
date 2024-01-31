<?php declare(strict_types=1);


namespace Awyiss\Model\Entity;


use Awyiss\Model\Entity;


/**
 * ContentTemplateContentArea Entity
 *
 * @property int $id
 * @property int|null $contentTemplateId
 * @property int|null $contentAreaId
 * @property int|null $pageTemplateId
 * @property \Awyiss\Model\Entity\ContentTemplate $contentTemplate
 * @property \Awyiss\Model\Entity\ContentArea $contentArea
 * @property \Awyiss\Model\Entity\PageTemplate $pageTemplate
 */
class ContentTemplateContentArea extends Entity {
	/**
	 * @inheritDoc
	 */
	protected array $_accessible = [
		'contentTemplateId' => true,
		'contentAreaId' => true,
		'pageTemplateId' => true,
	];
	/**
	 * @var array|array<string>
	 */
	protected array $_virtual = [];
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
}
