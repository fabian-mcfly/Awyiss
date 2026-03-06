<?php declare(strict_types=1);


namespace Awyiss\Model\Table;


use Awyiss\Model\Table;
use Awyiss\ORM\RulesChecker;
use Awyiss\Utility\Content\BackendColumnSystem;
use Cake\ORM\RulesChecker as BaseRulesChecker;
use Cake\Validation\Validator;


/**
 * MediaElementSelectors Model
 *
 * @method \Awyiss\Model\Entity\MediaElementSelector newDefaultEntity(array $additionalData = [], array $options = [])
 * @property \Awyiss\Model\Table\MediaAssignmentsTable&\Awyiss\ORM\Association\HasMany $MediaAssignments
 * @property \Awyiss\Model\Table\MediaElementsTable&\Awyiss\ORM\Association\BelongsTo $MediaElements
 * @property \Awyiss\Model\Table\MediaSelectorsTable&\Awyiss\ORM\Association\BelongsTo $MediaSelectors
 * @noinspection PhpFullyQualifiedNameUsageInspection
 */
class MediaElementSelectorsTable extends Table {
	/**
	 * @inheritDoc
	 */
	public const bool ATTRIBUTABLE = false;
	/**
	 * @inheritDoc
	 */
	public const string TABLE = 'media_element_selectors';


	/**
	 * @inheritDoc
	 */
	protected array $audit = [
		'enabled' => false,
	];
	/**
	 * @var array The column widths
	 */
	protected array $columnSpans;
	/**
	 * @inheritDoc
	 */
	protected array $translate = [
		'fields' => ['title'],
	];


	/**
	 * @inheritDoc
	 */
	public function initialize(array $config): void {
		parent::initialize($config);

		$this->columnSpans = BackendColumnSystem::getColumnWidths();
	}


	/**
	 * @inheritDoc
	 */
	public function initializeAssociations(): void {
		$this->hasMany('MediaAssignments', [
			'bindingKey' => [
				'mediaElementId',
				'identifier',
			],
			'cascadeCallbacks' => true,
			'dependent' => true,
			'foreignKey' => [
				'mediaElementId',
				'mediaElementSelectorIdentifier',
			],
			'propertyName' => 'mediaAssignments',
			'saveStrategy' => 'replace',
		]);

		$this->belongsTo('MediaElements', [
			'foreignKey' => 'mediaElementId',
			'joinType' => 'INNER',
			'propertyName' => 'mediaElement',
		]);

		$this->belongsTo('MediaSelectors', [
			'foreignKey' => 'mediaSelectorId',
			'joinType' => 'INNER',
			'propertyName' => 'mediaSelector',
		]);
	}


	/**
	 * @inheritDoc
	 * @noinspection DuplicatedCode
	 */
	public function validationDefault(Validator $validator): Validator {
		parent::validationDefault($validator);


		$validator->requirePresence([
			'identifier',
		], 'create');


		$validator->add('id', [
			'isInteger' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 11]],
		]);


		$validator->notEmptyString('mediaElementId');
		$validator->add('mediaElementId', [
			'isInteger' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 11]],
		]);


		$validator->notEmptyString('mediaSelectorId');
		$validator->add('mediaSelectorId', [
			'isInteger' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 11]],
		]);


		$validator->notEmptyString('identifier');
		$validator->add('identifier', [
			'isScalar' => ['rule' => 'isScalar'],
			'notBoolean' => ['rule' => 'notBoolean'],
			'maxLength' => ['rule' => ['maxLength', 50]],
			'notBlank' => ['rule' => 'notBlank'],
		]);


		$validator->allowEmptyString('title');
		$validator->add('title', [
			'isScalar' => ['rule' => 'isScalar'],
			'notBoolean' => ['rule' => 'notBoolean'],
			'maxLength' => ['rule' => ['maxLength', 100]],
			'notBlank' => ['rule' => 'notBlank'],
		]);


		$validator->add('columnSpan', [
			'inList' => [
				'rule' => [
					'inList',
					array_keys($this->columnSpans),
				],
			],
		]);


		$validator->add('required', [
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
		$rules->add($rules->existsIn('mediaElementId', 'MediaElements'), 'mediaElementExists', [
			'errorField' => 'mediaElementId',
			'message' => __df($this->getI18nDomain(), 'Validation', 'error_media_element_exists'),
		]);

		$rules->add($rules->existsIn('mediaSelectorId', 'MediaSelectors'), 'mediaSelectorExists', [
			'errorField' => 'mediaSelectorId',
			'message' => __df($this->getI18nDomain(), 'Validation', 'error_media_selectors_exists'),
		]);

		$rules->add(
			$rules->isUnique(['mediaElementId', 'identifier']),
			'identifierUniqueForMediaElement',
			[
				'errorField' => 'identifier',
				'message' => __df($this->getI18nDomain(), 'Validation', 'error_identifier_unique_for_media_element'),
			]
		);

		return $rules;
	}


	/**
	 * @return array
	 */
	public function getColumnSpans(): array {
		return $this->columnSpans;
	}
}
