<?php declare(strict_types=1);


namespace Awyiss\Model\Table;


use Awyiss\Model\Entity\UrlHistory;
use Awyiss\Model\Table;
use Awyiss\ORM\RulesChecker;
use Cake\Datasource\FactoryLocator;
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
	 * @var array
	 */
	protected array $availableScopes = [
		'pages',
		'media',
	];


	/**
	 * @inheritDoc
	 */
	public function initializeAssociations(): void {
		$this->belongsTo('Pages', [
			'conditions' => [
				'UrlHistory.scope' => 'pages',
			],
			'finder' => [
				'all' => [
					'skipPageRoleCheck' => true,
				],
			],
			'foreignKey' => 'foreign_key',
		]);

		$this->belongsTo('Media', [
			'conditions' => [
				'UrlHistory.scope' => 'media',
			],
			'foreignKey' => 'foreign_key',
		]);
	}


	/**
	 * @return array<string>
	 */
	public function getAvailableScopes(): array {
		return $this->availableScopes;
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

		$validator->requirePresence([
			'url',
			'scope',
			'status',
		], 'create');

		$validator->requirePresence([
			'target',
		], fn ($context) => empty($context['data']['scope']));

		$validator->requirePresence([
			'foreignKey',
		], fn ($context) => !empty($context['data']['scope']));

		$validator->notEmptyString('url');
		$validator->add('url', [
			'isScalar' => ['rule' => 'isScalar'],
			'notBoolean' => ['rule' => 'notBoolean'],
			'maxLength' => ['rule' => ['maxLength', 1024]],
			'notBlank' => ['rule' => 'notBlank'],
		]);

		$validator->notEmptyString('target', null, fn ($context) => empty($context['data']['scope']));
		$validator->add('target', [
			'isScalar' => ['rule' => 'isScalar'],
			'notBoolean' => ['rule' => 'notBoolean'],
			'maxLength' => ['rule' => ['maxLength', 1024]],
			'notBlank' => ['rule' => 'notBlank'],
		]);

		$validator->notEmptyString('foreignKey', null, fn ($context) => !empty($context['data']['scope']));
		$validator->add('foreignKey', [
			'isInteger' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 11]],
		]);

		$validator->notEmptyString('status');
		$validator->add('status', [
			'inList' => [
				'rule' => [
					'inList',
					[
						301,
						302,
						307,
						308,
					],
				],
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
		$lo_rules = $rules;
		$rules->add(function (UrlHistory $entity, array $options) use ($lo_rules) {
			$lo_tableLocator = FactoryLocator::get('Table');
			if ($entity->scope === 'pages') {
				/** @var \Awyiss\Model\Table\PagesTable $lo_table */
				$lo_table = $lo_tableLocator->get('Pages');
				$lo_existsIn = $lo_rules->existsIn(['foreignKey'], $lo_table, [
					'finder' => [
						'all' => [
							'skipPageRoleCheck' => true,
						],
					],
				]);

				return $lo_existsIn($entity, $options);
			}

			if ($entity->scope === 'media') {
				/** @var \Awyiss\Model\Table\MediaTable $lo_table */
				$lo_table = $lo_tableLocator->get('Media');
				$lo_existsIn = $lo_rules->existsIn(['foreignKey'], $lo_table);

				return $lo_existsIn($entity, $options);
			}

			return empty($entity->foreignKey);
		}, 'validForeignKey', [
			'errorField' => 'foreignKey',
			'message' => __df($this->getI18nDomain(), 'validation', 'error_valid_foreign_key'),
		]);

		$rules->add(function (UrlHistory $entity, array $options) use ($lo_rules) {
			if (empty($entity->scope)) {
				return true;
			}

			return !empty($entity->target);
		}, 'validTarget', [
			'errorField' => 'target',
			'message' => __df($this->getI18nDomain(), 'validation', 'error_valid_target'),
		]);

		return $rules;
	}
}
