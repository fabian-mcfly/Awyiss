<?php declare(strict_types=1);


namespace Customer\Model\Entity;


use Awyiss\Model\Entity;


/**
 * AttributesContent Entity
 *
 * @property int $id
 * @property int $contentId
 * @property string|null $teaser
 * @property string|null $freeText
 * @property string|null $backgroundColor
 */
class AttributesContent extends Entity {
	/**
	 * @inheritDoc
	 */
	protected array $_accessible = [
		'contentId' => true,
		'teaser' => true,
		'freeText' => true,
		'backgroundColor' => true,
		'content' => true,
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
