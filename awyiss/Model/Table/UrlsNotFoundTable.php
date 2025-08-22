<?php declare(strict_types=1);


namespace Awyiss\Model\Table;


use Awyiss\Model\Table;
use Cake\Validation\Validator;


/**
 * UrlsNotFound Model
 *
 * @method \Awyiss\Model\Entity\UrlsNotFound newDefaultEntity(array $additionalData = [], array $options = [])
 * @noinspection PhpFullyQualifiedNameUsageInspection
 */
class UrlsNotFoundTable extends Table {
	/**
	 * @inheritDoc
	 */
	public const TABLE = 'urls_not_found';


	/**
	 * @inheritDoc
	 * @noinspection DuplicatedCode
	 */
	public function validationDefault(Validator $validator): Validator {
		parent::validationDefault($validator);

		$validator->requirePresence([
			'url',
		], 'create');

		$validator->notEmptyString('url');
		$validator->add('url', [
			'isScalar' => ['rule' => 'isScalar'],
			'notBoolean' => ['rule' => 'notBoolean'],
			'maxLength' => ['rule' => ['maxLength', 2048]],
			'notBlank' => ['rule' => 'notBlank'],
		]);

		$validator->allowEmptyString('referrer');
		$validator->add('referrer', [
			'isScalar' => ['rule' => 'isScalar'],
			'notBoolean' => ['rule' => 'notBoolean'],
			'maxLength' => ['rule' => ['maxLength', 2048]],
		]);

		$validator->add('isRobot', [
			'boolean' => ['rule' => 'boolean'],
		]);

		return $validator;
	}
}
