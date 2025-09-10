<?php declare(strict_types=1);


namespace Customer\Model\Table;


use Awyiss\Model\Table;
use Awyiss\ORM\RulesChecker;
use Cake\ORM\RulesChecker as BaseRulesChecker;
use Cake\Validation\Validator;


/**
 * AttributesWidgets Model
 *
 * @method \Customer\Model\Entity\AttributesWidget newDefaultEntity(array $additionalData = [], array $options = [])
 */
class AttributesWidgetsTable extends Table {
	/**
	 * @inheritDoc
	 */
	public const bool ATTRIBUTABLE = false;
	/**
	 * @inheritDoc
	 */
	public const string TABLE = 'attributes_widgets';


	/**
	 * @inheritDoc
	 */
	public function initialize(array $config): void {
		parent::initialize($config);

		$this->belongsTo('Widgets', [
			'foreignKey' => 'widgetId',
			'joinType' => 'INNER',
		]);
	}


	/**
	 * @inheritDoc
	 * @noinspection DuplicatedCode
	 */
	public function validationDefault(Validator $validator): Validator {
		parent::validationDefault($validator);

		$validator
			->integer('widgetId')
			->notEmptyString('widgetId');

		$validator
			->scalar('teaser')
			->allowEmptyString('teaser');

		$validator
			->scalar('free_text')
			->allowEmptyString('free_text');

		$validator
			->scalar('free_text_inactive')
			->allowEmptyString('free_text_inactive');

		return $validator;
	}


	/**
	 * Returns a RulesChecker object after modifying the one that was supplied.
	 *
	 * @param \Awyiss\ORM\RulesChecker|\Cake\ORM\RulesChecker $rules The rules object to be modified.
	 * @return \Awyiss\ORM\RulesChecker
	 */
	public function buildRules(RulesChecker|BaseRulesChecker $rules): RulesChecker {
		$rules->add($rules->existsIn(['widgetId'], 'Widget'), 'validWidgetId', ['errorField' => 'widgetId']);

		return $rules;
	}
}
