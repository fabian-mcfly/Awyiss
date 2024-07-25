<?php declare(strict_types=1);


namespace Awyiss\Model\Table;


use Awyiss\Model\Table;
use Cake\Validation\Validator;


/**
 * PagesNotFound Model
 *
 * @method \Awyiss\Model\Entity\PagesNotFound newDefaultEntity(array $additionalData = [], array $options = [])
 * @noinspection PhpFullyQualifiedNameUsageInspection
 */
class PagesNotFoundTable extends Table {
	/**
	 * @inheritDoc
	 */
	public const ATTRIBUTABLE = true;
	/**
	 * @inheritDoc
	 */
	public const TABLE = 'pages_not_found';


	/**
	 * Returns the default validator object.
	 *
	 * @param \Cake\Validation\Validator $validator The validator that can be modified to
	 * add some rules to it.
	 * @return \Cake\Validation\Validator
	 */
	public function validationDefault(Validator $validator): Validator {
		parent::validationDefault($validator);

		$validator
			->scalar('slug')
			->maxLength('slug', 2048)
			->requirePresence('slug', 'create')
			->notEmptyString('slug');

		$validator
			->scalar('referrer')
			->maxLength('referrer', 2048)
			->allowEmptyString('referrer');

		$validator
			->boolean('isRobot')
			->allowEmptyString('isRobot');

		$validator
			->dateTime('createdOn')
			->allowEmptyDateTime('createdOn');

		return $validator;
	}
}
