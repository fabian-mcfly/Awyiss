<?php declare(strict_types=1);


namespace Awyiss\Model\Table;


use Awyiss\Model\Table;
use Awyiss\ORM\RulesChecker;
use Cake\ORM\RulesChecker as BaseRulesChecker;
use Cake\Validation\Validator;


/**
 * MediaAssignments Model
 *
 * @method \Awyiss\Model\Entity\MediaAssignment newDefaultEntity(array $additionalData = [], array $options = [])
 * @property \Awyiss\Model\Table\MediaElementsTable&\Awyiss\ORM\Association\BelongsTo $MediaElements
 * @property \Awyiss\Model\Table\MediaElementAssignmentsTable&\Awyiss\ORM\Association\BelongsTo $MediaElementAssignments
 * @property \Awyiss\Model\Table\MediaTable&\Awyiss\ORM\Association\BelongsTo $Media
 * @noinspection PhpFullyQualifiedNameUsageInspection
 */
class MediaAssignmentsTable extends Table {
	/**
	 * @inheritDoc
	 */
	public const ATTRIBUTABLE = true;
	/**
	 * @inheritDoc
	 */
	public const TABLE = 'media_assignments';


	/**
	 * @inheritDoc
	 */
	protected array $audit = [
		'enabled' => false,
	];
	/**
	 * @inheritDoc
	 */
	protected array $systemOrder = [
		'relatedColumns' => ['media_element_id', 'media_element_selector_identifier', 'scope', 'foreign_key'],
	];


	/**
	 * @inheritDoc
	 */
	public function initializeAssociations(): void {
		$this->belongsTo('MediaElements', [
			'foreignKey' => 'media_element_id',
			'joinType' => 'INNER',
		]);

		$this->belongsTo('MediaElementAssignments', [
			'bindingKey' => [
				'media_element_id',
			],
			'foreignKey' => [
				'media_element_id',
			],
			'joinType' => 'INNER',
		]);

		$this->belongsTo('MediaElementSelectors', [
			'bindingKey' => [
				'media_element_id',
				'identifier',
			],
			'foreignKey' => [
				'media_element_id',
				'media_element_selector_identifier',
			],
			'joinType' => 'INNER',
		]);

		$this->belongsTo('Media', [
			'foreignKey' => 'media_id',
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


		$validator->notEmptyString('mediaElementId');
		$validator->add('mediaElementId', [
			'isInteger' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 11]],
		]);


		$validator->notEmptyString('mediaElementSelectorIdentifier');
		$validator->add('mediaElementSelectorIdentifier', [
			'isScalar' => ['rule' => 'isScalar'],
			'maxLength' => ['rule' => ['maxLength', 50]],
			'notBlank' => ['rule' => 'notBlank'],
		]);


		$validator->notEmptyString('mediaId');
		$validator->add('mediaId', [
			'isInteger' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 11]],
		]);


		$validator->notEmptyString('scope');
		$validator->add('scope', [
			'isScalar' => ['rule' => 'isScalar'],
			'maxLength' => ['rule' => ['maxLength', 50]],
			'notBlank' => ['rule' => 'notBlank'],
		]);


		$validator->allowEmptyString('foreignKey');
		$validator->add('foreignKey', [
			'isInteger' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 11]],
		]);


		$validator->add('systemOrder', [
			'isInteger' => ['rule' => 'isInteger'],
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
		// TODO: rework to use media_element_assignments for the entity's table
		$rules->add($rules->existsIn('mediaElementId', 'MediaElements'), 'mediaElementExists', [
			'errorField' => 'mediaElementId',
			'message' => __df($this->getI18nDomain(), 'validation', 'error_media_element_exists'),
		]);

		$rules->add($rules->existsIn('mediaId', 'Media'), 'mediaExists', [
			'errorField' => 'mediaId',
			'message' => __df($this->getI18nDomain(), 'validation', 'error_media_exists'),
		]);

		return $rules;
	}
}
