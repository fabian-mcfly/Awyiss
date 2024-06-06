<?php declare(strict_types=1);


namespace Awyiss\Model\Table;


use Awyiss\Model\Entity\MediaCompositeAssignment;
use Awyiss\Model\Table;
use Awyiss\ORM\RulesChecker;
use Cake\ORM\RulesChecker as BaseRulesChecker;
use Cake\Validation\Validator;


/**
 * MediaCompositeAssignments Model
 *
 * @method \Awyiss\Model\Entity\MediaCompositeAssignment newDefaultEntity(array $additionalData = [], array $options = [])
 * @property \Awyiss\Model\Table\MediaCompositesTable&\Awyiss\ORM\Association\BelongsTo $MediaComposites
 */
class MediaCompositeAssignmentsTable extends Table {
	/**
	 * @inheritDoc
	 */
	public const ATTRIBUTABLE = false;
	/**
	 * @inheritDoc
	 */
	public const TABLE = 'media_composite_assignments';


	/**
	 * @inheritDoc
	 */
	public function initializeAssociations(): void {
		$this->hasMany('MediaAssignments', [
			'bindingKey' => 'media_composite_id',
			'cascadeCallbacks' => true,
			'dependent' => true,
			'foreignKey' => 'media_composite_id',
			'saveStrategy' => 'replace',
		]);

		$this->belongsTo('MediaComposites', [
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


		$validator->notEmptyString('mediaCompositeId');
		$validator->add('mediaCompositeId', [
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


		return $validator;
	}


	/**
	 * Returns a RulesChecker object after modifying the one that was supplied.
	 *
	 * @param \Awyiss\ORM\RulesChecker|\Cake\ORM\RulesChecker $rules The rules object to be modified.
	 * @return \Awyiss\ORM\RulesChecker
	 * @throws \ReflectionException
	 */
	public function buildRules(RulesChecker|BaseRulesChecker $rules): RulesChecker {
		static $la_assignableModels;

		if (!isset($la_assignableModels)) {
			$la_assignableModels = $this->MediaComposites->getAssignableModels(true, false);
		}

		$rules->add($rules->existsIn('mediaCompositeId', 'MediaComposites'), 'mediaCompositeExists', [
			'errorField' => 'mediaCompositeId',
			'message' => __df($this->getI18nDomain(), 'validation', 'error_media_composite_exists'),
		]);

		$rules->add(function (MediaCompositeAssignment $entity) use ($la_assignableModels) {
			if (!isset($la_assignableModels[ $entity->scope ])) {
				return false;
			}

			if ($entity->foreignKey && $la_assignableModels[ $entity->scope ]['entityLevel'] === false) {
				return __df($this->getI18nDomain(), 'validation', 'error_assignment_allows_entity_level');
			}

			if (!$entity->foreignKey && $la_assignableModels[ $entity->scope ]['modelLevel'] === false) {
				return __df($this->getI18nDomain(), 'validation', 'error_assignment_allows_model_level');
			}

			$la_possibleEntities = $la_assignableModels[ $entity->scope ]['entities'];

			if ($entity->foreignKey && !isset($la_possibleEntities[ $entity->foreignKey ])) {
				return __df($this->getI18nDomain(), 'validation', 'error_assignment_invalid_entity');
			}

			return true;
		}, 'foreignKeyExists', [
			'errorField' => 'foreignKey',
			'message' => __df($this->getI18nDomain(), 'validation', 'error_foreign_key_exists'),
		]);

		$rules->add(
			$rules->isUnique(['mediaCompositeId', 'scope', 'foreignKey'], ['allowMultipleNulls' => false]),
			'mediaCompositeUniqueForScope',
			[
				'errorField' => 'foreignKey',
				'message' => __df($this->getI18nDomain(), 'validation', 'error_media_composite_unique_for_scope'),
			]
		);

		return $rules;
	}
}
