<?php declare(strict_types=1);


namespace Customer\Model\Entity;


use Awyiss\Model\Entity;


/**
 * AttributesGlobalContent Entity
 *
 * @property int $id
 * @property int $globalContentId
 * @property string|null $teaser
 * @property string|null $freeText
 * @property string|null $freeTextInactive
 */
class AttributesGlobalContent extends Entity {
	/**
	 * @inheritDoc
	 */
	protected array $_accessible = [ // phpcs:ignore
		'globalContentId' => true,
		'teaser' => true,
		'freeText' => true,
		'freeTextInactive' => true,
		'globalContent' => true,
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
