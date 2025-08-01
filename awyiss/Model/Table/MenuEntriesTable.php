<?php declare(strict_types=1);


namespace Awyiss\Model\Table;


use Awyiss\Awyiss;
use Awyiss\Model\Table;
use Awyiss\ORM\RulesChecker;
use Cake\ORM\RulesChecker as BaseRulesChecker;
use Cake\Validation\Validator;


/**
 * MenuEntries Model
 *
 * @property \Awyiss\Model\Table\MenuEntriesTable&\Awyiss\ORM\Association\BelongsTo $ParentMenuEntries
 * @property \Awyiss\Model\Table\MenuEntriesTable&\Awyiss\ORM\Association\HasMany $ChildMenuEntries
 * @property \Awyiss\Model\Table\MenusTable&\Awyiss\ORM\Association\BelongsTo $Menus
 * @method \Awyiss\Model\Entity\MenuEntry newDefaultEntity(array $additionalData = [], array $options = [])
 * @method \Cake\Collection\CollectionInterface|null getNestedChildren(\Cake\Datasource\EntityInterface $entity, array $options = [], int $currentLevel = 0)
 * @method \Cake\Collection\CollectionInterface|null getChildren(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \Awyiss\Model\Entity\MenuEntry getParent(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \Cake\Collection\CollectionInterface|null getParents(\Cake\Datasource\EntityInterface $entity, array $options = [], int $currentLevel = 0)
 * @method \Cake\Collection\CollectionInterface getPossibleParents(\Awyiss\Model\Entity $entity, \Cake\Collection\CollectionInterface $threadedEntities)
 * @noinspection PhpFullyQualifiedNameUsageInspection
 */
class MenuEntriesTable extends Table {
	/**
	 * @inheritDoc
	 */
	public const TABLE = 'menu_entries';


	/**
	 * @inheritDoc
	 */
	protected array $categories = [
		'allowAggregation' => false,
		'associationName' => 'Menus',
		'enabled' => true,
		'identifier' => 'menu',
	];
	/**
	 * @inheritDoc
	 */
	protected array $nest = [
		'enabled' => true,
		'relatedColumns' => ['languageShortcode', 'menuId'],
	];
	/**
	 * @inheritDoc
	 */
	protected array $systemOrder = [
		'relatedColumns' => ['languageShortcode', 'menuId', 'parentId'],
	];


	/**
	 * @inheritDoc
	 */
	public function initializeAssociations(): void {
		$this->belongsTo('Menus');

		$this->belongsTo('Languages', [
			'bindingKey' => 'shortcode',
			'conditions' => ['realm' => Awyiss::REALM_FRONTEND],
			'foreignKey' => 'language_shortcode',
		]);
	}


	/**
	 * Returns the default validator object.
	 *
	 * @param \Awyiss\Validation\Validator $validator The validator that can be modified to
	 * add some rules to it.
	 * @return \Awyiss\Validation\Validator
	 * @noinspection DuplicatedCode
	 */
	public function validationDefault(Validator $validator): Validator {
		parent::validationDefault($validator);


		$validator->requirePresence([
			'menuId',
			'languageShortcode',
			'title',
		], 'create');


		$validator->add('id', [
			'isInteger' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 11]],
		]);


		$validator->notEmptyString('menuId');
		$validator->add('menuId', [
			'isInteger' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 11]],
		]);


		$validator->notEmptyString('languageShortcode');
		$validator->add('languageShortcode', [
			'isScalar' => ['rule' => 'isScalar'],
			'notBoolean' => ['rule' => 'notBoolean'],
			'ascii' => ['rule' => 'ascii'],
			'exactLength' => [
				'message' => __df($this->getI18nDomain(), 'validation', 'error_exact_length', 2),
				'rule' => function (string $shortcode): bool {
					return strlen($shortcode) == 2;
				},
			],
		]);


		$validator->add('parentId', [
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


		$validator->add('link', [
			'isScalar' => ['rule' => 'isScalar'],
			'notBoolean' => ['rule' => 'notBoolean'],
			'maxLength' => ['rule' => ['maxLength', 255]],
			'notBlank' => ['rule' => 'notBlank'],
		]);


		$validator->add('external', [
			'boolean' => ['rule' => 'boolean'],
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
	 * @param RulesChecker|BaseRulesChecker $rules The rules object to be modified.
	 * @return RulesChecker
	 */
	public function buildRules(RulesChecker|BaseRulesChecker $rules): RulesChecker {
		$rules->add(
			$rules->existsIn(
				'language_shortcode',
				'Languages',
			),
			'languageExists',
			[
				'errorField' => 'languageShortcode',
				'message' => __df($this->getI18nDomain(), 'validation', 'error_language_exists'),
			]
		);


		return $rules;
	}


	/**
	 * @return void
	 */
	public function disableCascadeCallbacks(): void {
		$ls_associationName = $this->getBehavior('Nest')->getConfig('children.associationName');
		$this->{$ls_associationName}->setDependent(false)->setCascadeCallbacks(false);
	}


	/**
	 * @return void
	 */
	public function enableCascadeCallbacks(): void {
		$ls_associationName = $this->getBehavior('Nest')->getConfig('children.associationName');
		$this->{$ls_associationName}->setDependent(true)->setCascadeCallbacks(true);
	}
}
