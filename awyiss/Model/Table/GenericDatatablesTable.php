<?php declare(strict_types=1);


namespace Awyiss\Model\Table;


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
abstract class GenericDatatablesTable extends Table {
	/**
	 * @inheritDoc
	 */
	public function __construct(array $aa_config = []) {
		$ls_scope = Inflector::camelize($this->getTable());

		if (LocalConfig::read('translatable', null, $ls_scope)) {
			$this->translate['fields'][] = 'title';
		}

		if (LocalConfig::read('nest.enabled', null, $ls_scope)) {
			$this->systemOrder['relatedColumns'][] = 'parentId';
		}

		if (LocalConfig::read('splitIntoLanguages', null, $ls_scope)) {
			$this->nest['relatedColumns'][] = 'languageShortcode';
			$this->systemOrder['relatedColumns'][] = 'languageShortcode';
		}

		parent::__construct($aa_config);
	}

	/**
	 * @inheritDoc
	 */
	public function initialize(array $aa_config): void {
		parent::initialize($aa_config);

		$ls_scope = Inflector::camelize($this->getTable());
		if (!LocalConfig::read('translatable', null, $ls_scope) && $this->hasAttributes()) {
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
	 * @param \Cake\Validation\Validator $ao_validator The validator that can be modified to
	 * add some rules to it.
	 * @return \Cake\Validation\Validator
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function validationDefault(Validator $ao_validator): Validator {
		parent::validationDefault($ao_validator);


		$ls_scope = Inflector::camelize($this->getTable());


		$ao_validator->add('id', [
			'isInteger' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 11]],
		]);


		$ao_validator->add('parentId', [
			'isInteger' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 11]],
		]);


		if (LocalConfig::read('splitIntoLanguages', null, $ls_scope)) {
			$ao_validator->notEmptyString('languageShortcode');
			$ao_validator->add('languageShortcode', [
				'isScalar' => ['rule' => 'isScalar'],
				'ascii' => ['rule' => 'ascii'],
				'exactLength' => [
					'rule' => function ($as_shortcode) {
						return strlen($as_shortcode) == 2;
					},
				],
			]);
		}


		$ao_validator->notEmptyString('title');
		$ao_validator->add('title', [
			'isScalar' => ['rule' => 'isScalar'],
			'maxLength' => ['rule' => ['maxLength', 255]],
			'notBlank' => ['rule' => 'notBlank'],
		]);


		$ao_validator->add('systemOrder', [
			'isInteger' => ['rule' => 'isInteger'],
		]);


		$ao_validator->add('active', [
			'boolean' => ['rule' => 'boolean'],
		]);


		$ao_validator->add('deleted', [
			'boolean' => ['rule' => 'boolean'],
		]);


		return $ao_validator;
	}


	/**
	 * Returns a RulesChecker object after modifying the one that was supplied.
	 *
	 * @param \Awyiss\ORM\RulesChecker|\Cake\ORM\RulesChecker $ao_rules The rules object to be modified.
	 * @return \Awyiss\ORM\RulesChecker
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function buildRules(RulesChecker|BaseRulesChecker $ao_rules): RulesChecker {
		$ls_scope = Inflector::camelize($this->getTable());

		if (LocalConfig::read('splitIntoLanguages', null, $ls_scope)) {
			$ao_rules->add(
				$ao_rules->existsIn('languageShortcode', 'Languages'),
				'languageExists',
				[
					'errorField' => 'languageShortcode',
					'message' => __dfx($this->getI18nDomain(), 'validation', 'pages', 'error_language_exists'),
				]
			);
		}


		return $ao_rules;
	}
}
