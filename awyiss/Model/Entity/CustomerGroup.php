<?php declare(strict_types=1);


namespace Awyiss\Model\Entity;


use Awyiss\Model\Entity;


/**
 * CustomerGroup Entity
 *
 * @property int $id
 * @property string $title
 * @property bool $active
 * @property bool $deleted
 * @property int|null $createdBy
 * @property \Cake\I18n\DateTime|null $createdOn
 * @property int|null $changedBy
 * @property \Cake\I18n\DateTime|null $changedOn
 * @property int|null $deletedBy
 * @property \Cake\I18n\DateTime|null $deletedOn
 * @property \Awyiss\Model\Entity\Customer[] $customers
 */
class CustomerGroup extends Entity {
	/**
	 * @inheritDoc
	 */
	protected array $_accessible = [ // phpcs:ignore
		'title' => true,
		'active' => true,
		'customers' => false,
	];
}
