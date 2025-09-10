<?php declare(strict_types=1);


namespace Customer\Model\Entity;


use Awyiss\Model\Entity;


/**
 * AttributesCar Entity
 *
 * @property int $id
 * @property int $carId
 * @property string|null $freeText
 * @property array|null $inputList
 * @property array|null $inputKeyValueList
 * @property array|null $dummyPw
 * @property string $dropdownSelect
 */
class AttributesCar extends Entity {
	/**
	 * @inheritDoc
	 */
	protected static array $fieldMap = [
		'car_id' => 'carId',
		'free_text' => 'freeText',
		'dropdown_select' => 'dropdownSelect',
		'input_list' => 'inputList',
		'input_key_value_list' => 'inputKeyValueList',
		'dummy_pw' => 'dummyPw',
	];


	/**
	 * @inheritDoc
	 */
	protected array $_accessible = [
		'carId' => true,
		'freeText' => true,
		'dropdownSelect' => true,
		'inputList' => true,
		'inputKeyValueList' => true,
		'dummyPw' => true,
		'car' => true,
	];
	/**
	 * Entity to be passed to the validation of attributes
	 */
	protected ?Entity $entity = null;


	/**
	 * @return \Awyiss\Model\Entity|null $entity Entity to be passed to the validation of attributes
	 */
	public function getEntity(): ?Entity {
		return $this->entity;
	}


	/**
	 * @param \Awyiss\Model\Entity|null $entity Entity to be passed to the validation of attributes
	 * @return $this
	 */
	public function setEntity(?Entity $entity = null): static {
		$this->entity = $entity;

		return $this;
	}
}
