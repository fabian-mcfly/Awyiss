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
	public const bool ATTRIBUTABLE = false;
	/**
	 * @inheritDoc
	 */
	public const string TABLE = 'media_assignments';


	/**
	 * @inheritDoc
	 */
	protected array $systemOrder = [
		'relatedColumns' => ['mediaElementId', 'mediaElementSelectorIdentifier', 'scope', 'foreignKey'],
	];


	/**
	 * @inheritDoc
	 */
	public function initializeAssociations(): void {
		$this->belongsTo('MediaElements', [
			'foreignKey' => 'mediaElementId',
			'joinType' => 'INNER',
			'propertyName' => 'mediaElement',
		]);

		$this->belongsTo('MediaElementAssignments', [
			'bindingKey' => 'mediaElementId',
			'foreignKey' => 'mediaElementId',
			'joinType' => 'INNER',
			'propertyName' => 'mediaElementAssignment',
		]);

		$this->belongsTo('MediaElementSelectors', [
			'bindingKey' => [
				'mediaElementId',
				'identifier',
			],
			'foreignKey' => [
				'mediaElementId',
				'mediaElementSelectorIdentifier',
			],
			'joinType' => 'INNER',
			'propertyName' => 'mediaElementSelector',
		]);

		$this->belongsTo('Media', [
			'foreignKey' => 'mediaId',
		]);

		$this->belongsTo('MediaFolders', [
			'foreignKey' => 'mediaFolderId',
			'propertyName' => 'mediaFolder',
		]);
	}


	/**
	 * @inheritDoc
	 * @noinspection DuplicatedCode
	 */
	public function validationDefault(Validator $validator): Validator {
		parent::validationDefault($validator);

		$validator->requirePresence([
			'mediaElementId',
			'mediaElementSelectorIdentifier',
			'scope',
		], 'create');

		$validator->requirePresence([
			'mediaId',
		], function (array $context): bool {
			return empty($context['data']['mediaFolderId']) && $context['newRecord'];
		});

		$validator->requirePresence([
			'mediaFolderId',
		], function (array $context): bool {
			return empty($context['data']['mediaId']) && $context['newRecord'];
		});

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
			'notBoolean' => ['rule' => 'notBoolean'],
			'maxLength' => ['rule' => ['maxLength', 50]],
			'notBlank' => ['rule' => 'notBlank'],
		]);


		$validator->notEmptyString('mediaId', null, function ($context) {
			return empty($context['data']['mediaFolderId']);
		});
		$validator->add('mediaId', [
			'isInteger' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 11]],
		]);


		$validator->notEmptyString('mediaFolderId', null, function ($context) {
			return empty($context['data']['mediaId']);
		});
		$validator->add('mediaFolderId', [
			'isInteger' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 11]],
		]);


		$validator->notEmptyString('scope');
		$validator->add('scope', [
			'isScalar' => ['rule' => 'isScalar'],
			'notBoolean' => ['rule' => 'notBoolean'],
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
			'maxLength' => ['rule' => ['maxLength', 11]],
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
			'message' => __df($this->getI18nDomain(), 'Validation', 'error_media_element_exists'),
		]);

		$rules->add($rules->existsIn('mediaId', 'Media'), 'mediaExists', [
			'errorField' => 'mediaId',
			'message' => __df($this->getI18nDomain(), 'Validation', 'error_media_exists'),
		]);

		$rules->add($rules->existsIn('mediaFolderId', 'MediaFolders'), 'mediaFolderExists', [
			'errorField' => 'mediaFolderId',
			'message' => __df($this->getI18nDomain(), 'Validation', 'error_media_folder_exists'),
		]);

		return $rules;
	}
}
