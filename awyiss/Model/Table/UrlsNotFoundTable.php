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

		$validator
			->scalar('url')
			->maxLength('url', 2048)
			->requirePresence('url', 'create')
			->notEmptyString('url');

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
