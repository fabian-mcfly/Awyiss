<?php declare(strict_types=1);


namespace Awyiss\Model\Table;


use Awyiss\Model\Table;
use Awyiss\ORM\RulesChecker;
use Awyiss\Utility\Content\BootstrapColumnSystem;
use Cake\ORM\RulesChecker as BaseRulesChecker;
use Cake\Validation\Validator;


/**
 * MediaCompositeSelectors Model
 *
 * @method \Awyiss\Model\Entity\MediaCompositeSelector newDefaultEntity(array $additionalData = [], array $options = [])
 * @property \Awyiss\Model\Table\MediaAssignmentsTable&\Awyiss\ORM\Association\HasMany $MediaAssignments
 * @property \Awyiss\Model\Table\MediaCompositesTable&\Awyiss\ORM\Association\BelongsTo $MediaComposites
 * @property \Awyiss\Model\Table\MediaSelectorsTable&\Awyiss\ORM\Association\BelongsTo $MediaSelectors
 * @noinspection PhpFullyQualifiedNameUsageInspection
 */
class MediaCompositeSelectorsTable extends Table {
	/**
	 * @inheritDoc
	 */
	public const ATTRIBUTABLE = false;
	/**
	 * @inheritDoc
	 */
	public const TABLE = 'media_composite_selectors';


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

		$this->columnSpans = BootstrapColumnSystem::getColumnWidths();
	}


	/**
	 * @inheritDoc
	 */
	public function initializeAssociations(): void {
		$this->hasMany('MediaAssignments', [
			'bindingKey' => [
				'media_composite_id',
				'media_composite_selector_identifier',
			],
			'cascadeCallbacks' => true,
			'dependent' => true,
			'foreignKey' => [
				'media_composite_id',
				'identifier',
			],
			'saveStrategy' => 'replace',
		]);

		$this->belongsTo('MediaComposites', [
			'joinType' => 'INNER',
		]);

		$this->belongsTo('MediaSelectors', [
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


		$validator->requirePresence([
			'identifier',
		], 'create');


		$validator->add('id', [
			'isInteger' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 11]],
		]);


		$validator->notEmptyString('mediaCompositeId');
		$validator->add('mediaCompositeId', [
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
			'maxLength' => ['rule' => ['maxLength', 50]],
			'notBlank' => ['rule' => 'notBlank'],
		]);


		$validator->allowEmptyString('title');
		$validator->add('title', [
			'isScalar' => ['rule' => 'isScalar'],
			'maxLength' => ['rule' => ['maxLength', 100]],
			'notBlank' => ['rule' => 'notBlank'],
		]);


		$validator->add('columSpan', [
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
	 * @param \Cake\ORM\RulesChecker $rules The rules object to be modified.
	 * @param \Awyiss\ORM\RulesChecker|\Cake\ORM\RulesChecker $rules The rules object to be modified.
	 */
	public function buildRules(RulesChecker|BaseRulesChecker $rules): RulesChecker {
		$rules->add($rules->existsIn('mediaCompositeId', 'MediaComposites'), 'mediaCompositeExists', [
			'errorField' => 'mediaCompositeId',
			'message' => __df($this->getI18nDomain(), 'validation', 'error_media_composite_exists'),
		]);

		$rules->add($rules->existsIn('mediaSelectorId', 'MediaSelectors'), 'mediaSelectorExists', [
			'errorField' => 'mediaSelectorId',
			'message' => __df($this->getI18nDomain(), 'validation', 'error_media_selectors_exists'),
		]);

		$rules->add(
			$rules->isUnique(['mediaCompositeId', 'identifier']),
			'identifierUniqueForMediaComposite',
			[
				'errorField' => 'identifier',
				'message' => __df($this->getI18nDomain(), 'validation', 'error_identifier_unique_for_media_composite'),
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
