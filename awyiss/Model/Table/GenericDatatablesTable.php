<?php declare(strict_types=1);


namespace Awyiss\Model\Table;


use Awyiss\Annotation\MediaElementAssignable;
use Awyiss\Awyiss;
use Awyiss\Core\LocalConfig;
use Awyiss\Model\Entity;
use Awyiss\Model\Table;
use Awyiss\ORM\RulesChecker;
use Awyiss\Utility\Inflector;
use Cake\ORM\RulesChecker as BaseRulesChecker;
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
		$scope = Inflector::camelize($this->getTable());

		$this->nestable = LocalConfig::read('nest.enabled', false, $scope);
		if ($this->nestable) {
			$this->systemOrder['relatedColumns'][] = 'parentId';
		}

		$this->splitIntoLanguages = LocalConfig::read('splitIntoLanguages', true, $scope);
		if ($this->splitIntoLanguages) {
			$this->nest['relatedColumns'][] = 'languageShortcode';
			$this->systemOrder['relatedColumns'][] = 'languageShortcode';
		}

		$this->translatable = LocalConfig::read('translatable', false, $scope);
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

		/**
		 * If the records are not translatable,
		 * unset possible translatable attribute fields.
		 */
		if (!$this->translatable && $this->hasAttributes()) {
			$attributesTable = $this->getAttributesTable();
			if ($attributesTable->hasBehavior('Translate')) {
				/** @noinspection PhpRedundantOptionalArgumentInspection */
				$attributesTable->getBehavior('Translate')->setConfig('fields', null);
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
			'foreignKey' => 'languageShortcode',
		]);
	}


	/**
	 * @inheritDoc
	 * @noinspection DuplicatedCode
	 */
	public function validationDefault(Validator $validator): Validator {
		parent::validationDefault($validator);

		$validator->requirePresence(['title'], 'create');

		$validator->add('id', [
			'isInteger' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 11]],
		]);


		$validator->add('parentId', [
			'isInteger' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 11]],
		]);


		if ($this->splitIntoLanguages) {
			$validator->requirePresence(['languageShortcode'], 'create');

			$validator->notEmptyString('languageShortcode');
			$validator->add('languageShortcode', [
				'isScalar' => ['rule' => 'isScalar'],
				'notBoolean' => ['rule' => 'notBoolean'],
				'ascii' => ['rule' => 'ascii'],
				'exactLength' => [
					'message' => __df($this->getI18nDomain(), 'Validation', 'error_exact_length', 2),
					'rule' => function (string $shortcode): bool {
						return strlen($shortcode) == 2;
					},
				],
			]);
		}


		$validator->notEmptyString('title');
		$validator->add('title', [
			'isScalar' => ['rule' => 'isScalar'],
			'notBoolean' => ['rule' => 'notBoolean'],
			'maxLength' => ['rule' => ['maxLength', 255]],
			'notBlank' => ['rule' => 'notBlank'],
		]);


		$validator->add('systemOrder', [
			'isInteger' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 11]],
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
				function (Entity $entity, array $options) use ($rules): bool|string {
					// When split into languages, the languageShortcode must be set
					/** @noinspection PhpPossiblePolymorphicInvocationInspection */
					if (!$entity->languageShortcode) {
						return false;
					}

					$exists = $rules->existsIn('languageShortcode', 'Languages');

					return $exists($entity, $options);
				},
				'languageExists',
				[
					'errorField' => 'languageShortcode',
					'message' => __df($this->getI18nDomain(), 'Validation', 'error_language_exists'),
				]
			);
		}


		return $rules;
	}
}
