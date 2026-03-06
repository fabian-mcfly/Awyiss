<?php declare(strict_types=1);


namespace Awyiss\Model\Entity;


use Awyiss\Model\Entity;


/**
 * FormConditionalRecipient Entity
 *
 * @property int $id
 * @property int $formId
 * @property string $type
 * @property string $field
 * @property \Awyiss\Model\Enum\ComparisonOperator $operator
 * @property string|null $value
 * @property string $recipient
 * @property int $systemOrder
 * @property int|null $createdBy
 * @property \Cake\I18n\DateTime|null $createdOn
 * @property int|null $changedBy
 * @property \Cake\I18n\DateTime|null $changedOn
 */
class FormConditionalRecipient extends Entity {
	/**
	 * @inheritDoc
	 */
	protected array $_accessible = [ // phpcs:ignore
		'formId' => true,
		'type' => true,
		'field' => true,
		'operator' => true,
		'value' => true,
		'recipient' => true,
		'systemOrder' => true,
	];
	/**
	 * @inheritDoc
	 */
	protected array $defaultValues = [
		'formId' => null, // Since sqlite sets '0' as default
	];
}
