<?php declare(strict_types=1);


namespace Awyiss\Model\Table;


use Awyiss\Core\App;
use Awyiss\Model\Entity\Datatable;
use Awyiss\Model\Table;
use Awyiss\ORM\RulesChecker;
use Cake\Collection\CollectionInterface;
use Cake\Datasource\ResultSetInterface;
use Cake\ORM\RulesChecker as BaseRulesChecker;
use Cake\Validation\Validator;


/**
 * Datatables Model
 *
 * @method \Awyiss\Model\Entity\Datatable newDefaultEntity(array $additionalData = [], array $options = [])
 * @noinspection PhpUnnecessaryFullyQualifiedNameInspection
 */
class DatatablesTable extends Table {
	/**
	 * @inheritDoc
	 */
	public const bool ATTRIBUTABLE = false;
	/**
	 * @inheritDoc
	 */
	public const string TABLE = 'datatables';


	/**
	 * @var \Cake\Datasource\ResultSetInterface
	 */
	protected static ResultSetInterface $cachedDatatables;


	/**
	 * @var array<int, string> A list of identifiers a datatable can't have, since they're used by the system
	 * or because they are template folder names
	 */
	protected array $blocklistedIdentifiers = [
		'Cells',
		'ContentAreas',
		'Emails',
		'Elements',
		'Forms',
		'GenericPages',
		'Layouts',
	];
	/**
	 * @inheritDoc
	 */
	protected array $translate = [
		'fields' => ['title'],
	];


	/**
	 * @inheritDoc
	 * @noinspection DuplicatedCode
	 */
	public function validationDefault(Validator $validator): Validator {
		parent::validationDefault($validator);


		$validator->requirePresence([
			'title',
			'identifier',
		], 'create');


		$validator->add('id', [
			'isInteger' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 11]],
		]);


		$validator->notEmptyString('title');
		$validator->add('title', [
			'isScalar' => ['rule' => 'isScalar'],
			'notBoolean' => ['rule' => 'notBoolean'],
			'maxLength' => ['rule' => ['maxLength', 100]],
			'notBlank' => ['rule' => 'notBlank'],
		]);


		$validator->notEmptyString('identifier');
		$validator->add('identifier', [
			'isScalar' => ['rule' => 'isScalar'],
			'notBoolean' => ['rule' => 'notBoolean'],
			'maxLength' => ['rule' => ['maxLength', 100]],
			'notBlank' => ['rule' => 'notBlank'],
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
		$rules->add(
			function (Datatable $entity, array $options) use ($rules): bool|string {
				if (
					($options['isCopy'] ?? false) === false
					&& $entity->hasOriginal('identifier')
					&& $entity->get('identifier') !== $entity->getOriginal('identifier')
				) {
					return __df($this->getI18nDomain(), 'Validation', 'error_identifier_unchanged');
				}

				/** @var class-string<\Awyiss\Model\Enum\PageRoleEnumInterface> $pageRoleEnum */
				$pageRoleEnum = App::className('PageRole', 'Model/Enum');

				if (
					$entity->isDirty('identifier')
					&& (
						str_starts_with($entity->identifier, 'Attributes')
						|| in_array($entity->identifier, $this->blocklistedIdentifiers)
						|| App::className($entity->identifier, 'Controller/Backend', 'Controller')
						|| $pageRoleEnum::tryFromName($entity->identifier)
					)
				) {
					return __df($this->getI18nDomain(), 'Validation', 'error_identifier_allowed');
				}

				$isUnique = $rules->isUnique(['identifier'], [
					'errorField' => '_dummy',
				]);
				$isUnique = $isUnique($entity, $options);

				if (!$isUnique) {
					return __df($this->getI18nDomain(), 'Validation', 'error_identifier_unique');
				}


				return true;
			},
			'validIdentifier',
			[
				'errorField' => 'identifier',
			]
		);


		return $rules;
	}


	/**
	 * @return \Cake\Collection\CollectionInterface
	 */
	public function findAllAndCache(): CollectionInterface {
		if (!isset(static::$cachedDatatables)) {
			/**
			 * @uses \Awyiss\Model\Behavior\MediaAssignmentBehavior::findMediaAssignments()
			 * @uses \Awyiss\Model\Behavior\MediaElementAssignmentBehavior::findMediaElementAssignments()
			 * @uses \Awyiss\Model\Table::findTranslations()
			 */
			static::$cachedDatatables = $this
				->find('translations')
				->find('mediaAssignments')
				->find('mediaElementAssignments')
				->all()
			;
		}


		return static::$cachedDatatables->compile();
	}
}
