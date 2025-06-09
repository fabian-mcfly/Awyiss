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
	protected static array $fieldMap = [
		'consent_id' => 'consentId',
		'accept_type' => 'acceptType',
		'accepted_categories' => 'acceptedCategories',
		'rejected_categories' => 'rejectedCategories',
	];


	/**
	 * @inheritDoc
	 */
	protected array $_accessible = [
		'consentId' => true,
		'acceptType' => true,
		'acceptedCategories' => true,
		'rejectedCategories' => true,
	];
}
