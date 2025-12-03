<?php declare(strict_types=1);


namespace Awyiss\Model\Table;


use Awyiss\Model\Entity\MediaElementAssignment;
use Awyiss\Model\Table;
use Awyiss\ORM\RulesChecker;
use Cake\ORM\RulesChecker as BaseRulesChecker;
use Cake\Validation\Validator;


/**
 * MediaElementAssignments Model
 *
 * @method \Awyiss\Model\Entity\MediaElementAssignment newDefaultEntity(array $additionalData = [], array $options = [])
 * @property \Awyiss\Model\Table\MediaElementsTable&\Awyiss\ORM\Association\BelongsTo $MediaElements
 * @noinspection PhpUnnecessaryFullyQualifiedNameInspection
 */
class MediaElementAssignmentsTable extends Table {
	/**
	 * @inheritDoc
	 */
	public const bool ATTRIBUTABLE = false;
	/**
	 * @inheritDoc
	 */
	public const string TABLE = 'media_element_assignments';


	/**
	 * @inheritDoc
	 */
	public function initializeAssociations(): void {
		$this->hasMany('MediaAssignments', [
			'bindingKey' => ['media_element_id', 'scope'],
			'cascadeCallbacks' => true,
			'dependent' => true,
			'foreignKey' => ['media_element_id', 'scope'],
			'saveStrategy' => 'replace',
		]);

		$this->belongsTo('MediaElements', [
			'joinType' => 'INNER',
		]);
	}


	/**
	 * @inheritDoc
	 * @noinspection DuplicatedCode
	 */
	public function validationDefault(Validator $validator): Validator {
		parent::validationDefault($validator);

		$validator->requirePresence([
			'scope',
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
		static $assignableModels;

		if (!isset($assignableModels)) {
			$assignableModels = $this->MediaElements->getAssignableModels(true, false);
		}

		$rules->add($rules->existsIn('mediaElementId', 'MediaElements'), 'mediaElementExists', [
			'errorField' => 'mediaElementId',
			'message' => __df($this->getI18nDomain(), 'validation', 'error_media_element_exists'),
		]);

		$rules->add(function (MediaElementAssignment $entity) use ($assignableModels) {
			if (!isset($assignableModels[ $entity->scope ])) {
				return false;
			}

			if ($entity->foreignKey && $assignableModels[ $entity->scope ]['entityLevel'] === false) {
				return __df($this->getI18nDomain(), 'validation', 'error_assignment_allows_entity_level');
			}

			if (!$entity->foreignKey && $assignableModels[ $entity->scope ]['modelLevel'] === false) {
				return __df($this->getI18nDomain(), 'validation', 'error_assignment_allows_model_level');
			}

			$possibleEntities = $assignableModels[ $entity->scope ]['entities'];

			if ($entity->foreignKey && !isset($possibleEntities[ $entity->foreignKey ])) {
				return __df($this->getI18nDomain(), 'validation', 'error_assignment_invalid_entity');
			}

			return true;
		}, 'foreignKeyExists', [
			'errorField' => 'foreignKey',
			'message' => __df($this->getI18nDomain(), 'validation', 'error_foreign_key_exists'),
		]);

		$rules->add(
			$rules->isUnique(['mediaElementId', 'scope', 'foreignKey'], ['allowMultipleNulls' => false]),
			'mediaElementUniqueForScope',
			[
				'errorField' => 'foreignKey',
				'message' => __df($this->getI18nDomain(), 'validation', 'error_media_element_unique_for_scope'),
			]
		);

		return $rules;
	}
}
