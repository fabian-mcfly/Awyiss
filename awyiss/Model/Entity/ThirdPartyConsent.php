<?php declare(strict_types=1);


namespace Awyiss\Model\Entity;


use Awyiss\Model\Entity;


/**
 * ThirdPartyConsent Entity
 *
 * @property int $id
 * @property string $consentId
 * @property string $acceptType
 * @property array $acceptedCategories
 * @property array $rejectedCategories
 * @property \Cake\I18n\DateTime $createdOn
 */
class ThirdPartyConsent extends Entity {
	/**
	 * @inheritDoc
	 */
	protected array $_accessible = [ // phpcs:ignore
		'consentId' => true,
		'acceptType' => true,
		'acceptedCategories' => true,
		'rejectedCategories' => true,
	];
}
