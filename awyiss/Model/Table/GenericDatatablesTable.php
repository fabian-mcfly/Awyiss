<?php declare(strict_types=1);


namespace Awyiss\Model\Table;


use Awyiss\Annotation\MediaElementAssignable;
use Awyiss\Awyiss;
use Awyiss\Core\LocalConfig;
use Awyiss\Model\Table;
use Awyiss\ORM\RulesChecker;
use Cake\ORM\RulesChecker as BaseRulesChecker;
use Cake\Utility\Inflector;
use Cake\Validation\Validator;


/**
 * Base class for generic datatables that provides a default validation
 * and rules based on the config settings for the extending datatable
 */
#[MediaElementAssignable(MediaElementAssignable::ENTITY_LEVEL)]
abstract class GenericDatatablesTable extends Table {
	/**
	 * @var bool $nestable Whether the records are nestable or not.
	 */
	protected bool $nestable;
	/**
	 * @var bool $splitIntoLanguages Whether the records are split into languages or not.
	 */
	protected bool $splitIntoLanguages;
	/**
	 * @var bool $translatable Whether the records are translatable or not.
	 */
	protected bool $translatable;
	/**
	 * @inheritDoc
	 */
	protected array $translate = [
		'realm' => Awyiss::REALM_FRONTEND,
	];


	/**
	 * @inheritDoc
	 */
	public function __construct(array $config = []) {
		$ls_scope = Inflector::camelize($this->getTable());

		$this->nestable = LocalConfig::read('nest.enabled', false, $ls_scope);
		if ($this->nestable) {
			$this->systemOrder['relatedColumns'][] = 'parentId';
		}

		$this->splitIntoLanguages = LocalConfig::read('splitIntoLanguages', true, $ls_scope);
		if ($this->splitIntoLanguages) {
			$this->nest['relatedColumns'][] = 'languageShortcode';
			$this->systemOrder['relatedColumns'][] = 'languageShortcode';
		}

		$this->translatable = LocalConfig::read('translatable', false, $ls_scope);
		if ($this->translatable) {
			$this->translate['fields'][] = 'title';
		}

		parent::__construct($config);
	}

	/**
	 * @inheritDoc
	 */
	public function initialize(array $config): void {
		parent::initialize($config);

		if (!$this->translatable && $this->hasAttributes()) {
			$lo_attributesTable = $this->getAttributesTable();
			if ($lo_attributesTable->hasBehavior('Translate')) {
				$lo_attributesTable->getBehavior('Translate')->setConfig('fields');
			}
		}
	}


	/**
	 * @inheritDoc
	 */
	public function initializeAssociations(): void {
		$this->belongsTo('Languages', [
			'bindingKey' => 'shortcode',
			'conditions' => ['realm' => Awyiss::REALM_FRONTEND],
			'foreignKey' => 'language_shortcode',
			'joinType' => 'INNER',
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


		$validator->add('id', [
			'isInteger' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 11]],
		]);


		$validator->add('parentId', [
			'isInteger' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 11]],
		]);


		if ($this->splitIntoLanguages) {
			$validator->notEmptyString('languageShortcode');
			$validator->add('languageShortcode', [
				'isScalar' => ['rule' => 'isScalar'],
				'ascii' => ['rule' => 'ascii'],
				'exactLength' => [
					'rule' => function ($shortcode) {
						return strlen($shortcode) == 2;
					},
				],
			]);
		}


		$validator->notEmptyString('title');
		$validator->add('title', [
			'isScalar' => ['rule' => 'isScalar'],
			'maxLength' => ['rule' => ['maxLength', 255]],
			'notBlank' => ['rule' => 'notBlank'],
		]);


		$validator->add('systemOrder', [
			'isInteger' => ['rule' => 'isInteger'],
		]);


		$validator->add('active', [
			'boolean' => ['rule' => 'boolean'],
		]);


		$validator->add('deleted', [
			'boolean' => ['rule' => 'boolean'],
		]);


		return $validator;
	}


	/**
	 * Returns a RulesChecker object after modifying the one that was supplied.
	 *
	 * @param \Awyiss\ORM\RulesChecker|\Cake\ORM\RulesChecker $rules The rules object to be modified.
	 * @return \Awyiss\ORM\RulesChecker
	 */
	public function buildRules(RulesChecker|BaseRulesChecker $rules): RulesChecker {
		if ($this->splitIntoLanguages) {
			$rules->add(
				$rules->existsIn('languageShortcode', 'Languages'),
				'languageExists',
				[
					'errorField' => 'languageShortcode',
					'message' => __df($this->getI18nDomain(), 'validation', 'error_language_exists'),
				]
			);
		}


		return $rules;
	}
}
