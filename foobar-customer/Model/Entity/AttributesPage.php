<?php declare(strict_types=1);


namespace FoobarCustomer\Model\Entity;


use Awyiss\Model\Entity;


/**
 * AttributesPage Entity
 *
 * @property int $id
 * @property int $pageId
 * @property \Cake\I18n\DateTime|null $testdate
 * @property \Cake\I18n\DateTime|null $testdate2
 * @property \Cake\I18n\Date|null $onlydate
 * @property \Cake\I18n\Time|null $onlytime
 */
class AttributesPage extends Entity {
	/**
	 * @inheritDoc
	 */
	protected array $_accessible = [
		'pageId' => true,
		'testdate' => true,
		'testdate2' => true,
		'onlydate' => true,
		'onlytime' => true,
		'page' => true,
	];
	protected ?Entity $entity = NULL;

	/**
	* @inheritDoc
	*/
	protected static array $fieldMap = [
		'page_id' => 'pageId',
	];


	public function setEntity (?Entity $ao_entity = NULL): static {
		$this->entity = $ao_entity;

		return $this;
	}

	public function getEntity (): ?Entity {
		return $this->entity;
	}
}
