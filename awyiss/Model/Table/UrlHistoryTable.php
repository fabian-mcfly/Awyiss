<?php declare(strict_types=1);


namespace Awyiss\Model\Table;


use Awyiss\Model\Entity\UrlHistory;
use Awyiss\Model\Table;
use Awyiss\ORM\RulesChecker;
use Cake\ORM\RulesChecker as BaseRulesChecker;
use Cake\Validation\Validator;


/**
 * UrlHistory Model
 *
 * @property \Awyiss\Model\Table\PagesTable&\Awyiss\ORM\Association\BelongsTo $Pages
 * @method \Awyiss\Model\Entity\UrlHistory newDefaultEntity(array $additionalData = [], array $options = [])
 */
class UrlHistoryTable extends Table {
	/**
	 * @inheritDoc
	 */
	public const ATTRIBUTABLE = true;
	/**
	 * @inheritDoc
	 */
	public const TABLE = 'url_history';


	/**
	 * @inheritDoc
	 */
	public function initializeAssociations(): void {
		$this->belongsTo('Pages', [
			'finder' => [
				'all' => [
					'skipPageRoleCheck' => true,
				],
			],
			'foreignKey' => 'page_id',
		]);
	}


	/**
	 * Returns the default validator object.
	 *
	 * @param \Cake\Validation\Validator $validator The validator that can be modified to
	 * add some rules to it.
	 * @return \Cake\Validation\Validator
	 */
	public function validationDefault(Validator $validator): Validator {
		parent::validationDefault($validator);

		$validator->notEmptyString('url');
		$validator->add('url', [
			'isScalar' => ['rule' => 'isScalar'],
			'maxLength' => ['rule' => ['maxLength', 1024]],
			'notBlank' => ['rule' => 'notBlank'],
		]);

		$validator->notEmptyString('pageId');
		$validator->add('pageId', [
			'isInteger' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 11]],
		]);

		$validator->notEmptyString('status');
		$validator->add('status', [
			'isInteger' => ['rule' => 'isInteger'],
			'exactLength' => [
				'message' => __df($this->getI18nDomain(), 'validation', 'error_exact_length', 3),
				'rule' => function ($status) {
					return strlen($status) == 3;
				},
			],
		]);

		return $validator;
	}


	/**
	 * Returns a RulesChecker object after modifying the one that was supplied.
	 *
	 * @param BaseRulesChecker $rules The rules object to be modified.
	 * @param \Awyiss\ORM\RulesChecker|BaseRulesChecker $rules The rules object to be modified.
	 */
	public function buildRules(RulesChecker|BaseRulesChecker $rules): RulesChecker {
		$rules->add($rules->existsIn(['pageId'], 'Pages'), 'validPageId', [
			'errorField' => 'pageId',
			'message' => __df($this->getI18nDomain(), 'validation', 'error_valid_page_id'),
		]);

		$rules->add(function (UrlHistory $entity) {
			return in_array($entity->status, [301, 302, 307, 308], true);
		}, 'validStatus', [
			'errorField' => 'status',
			'message' => __df($this->getI18nDomain(), 'validation', 'error_valid_status'),
		]);

		return $rules;
	}
}
