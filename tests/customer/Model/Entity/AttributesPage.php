<?php declare(strict_types=1);


namespace Customer\Model\Entity;


use Awyiss\Model\Entity;


/**
 * AttributesPage Entity
 *
 * @property int $id
 * @property int $pageId
 * @property \Cake\I18n\Date $date
 * @property string|null $teaser
 * @property string|null $text
 */
class AttributesPage extends Entity {
	/**
	 * @inheritDoc
	 */
	protected static array $fieldMap = [
		'page_id' => 'pageId',
	];


	/**
	 * @inheritDoc
	 */
	protected array $_accessible = [
		'pageId' => true,
		'date' => true,
		'teaser' => true,
		'text' => true,
		'page' => true,
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
