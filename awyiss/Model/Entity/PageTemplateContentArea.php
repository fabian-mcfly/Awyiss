<?php declare(strict_types=1);


namespace Awyiss\Model\Entity;


use Awyiss\Model\Entity;


/**
 * PageTemplateContentArea Entity
 *
 * @property int $id
 * @property int|null $pageTemplateId
 * @property int|null $contentAreaId
 * @property int $systemOrder
 * @property \Awyiss\Model\Entity\PageTemplate $pageTemplate
 * @property \Awyiss\Model\Entity\ContentArea $contentArea
 */
class PageTemplateContentArea extends Entity {
	/**
	 * @inheritDoc
	 */
	protected array $_accessible = [ // phpcs:ignore
		'pageTemplateId' => true,
		'contentAreaId' => true,
		'systemOrder' => true,
	];
	/**
	 * @var array<string>
	 */
	protected array $_virtual = []; // phpcs:ignore
}
