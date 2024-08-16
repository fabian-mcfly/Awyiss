<?php declare(strict_types=1);


namespace Awyiss\Model\Table;


use Awyiss\Awyiss;
use Awyiss\Model\Entity\MediaFolder;
use Awyiss\Model\Table;
use Awyiss\ORM\RulesChecker;
use Cake\Database\Schema\TableSchemaInterface;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\RulesChecker as BaseRulesChecker;
use Cake\Validation\Validator;


/**
 * MediaFolders Model
 *
 * @property \Awyiss\Model\Table\LanguagesTable&\Awyiss\ORM\Association\BelongsTo $Languages
 * @property \Awyiss\Model\Table\MediaFoldersTable&\Awyiss\ORM\Association\BelongsTo $ParentMediaFolders
 * @property \Awyiss\Model\Table\MediaFoldersTable&\Awyiss\ORM\Association\HasMany $ChildMediaFolders
 * @property \Awyiss\Model\Table\MediaTable&\Awyiss\ORM\Association\HasMany $Media
 * @method \Awyiss\Model\Entity\MediaFolder newDefaultEntity(array $additionalData = [], array $options = [])
 * @method \Cake\Collection\CollectionInterface|null getNestedChildren(MediaFolder $entity, array $options = [], int $currentLevel = 0)
 * @method \Cake\Collection\CollectionInterface|null getChildren(MediaFolder $entity, array $options = [])
 * @method \Awyiss\Model\Entity\MediaFolder getParent(MediaFolder $entity, array $options = [])
 * @method \Cake\Collection\CollectionInterface|null getParents(MediaFolder $entity, array $options = [], int $currentLevel = 0)
 * @method \Cake\Collection\CollectionInterface getPossibleParents(\Awyiss\Model\Entity $entity, \Cake\Collection\CollectionInterface $threadedEntities)
 * @noinspection PhpFullyQualifiedNameUsageInspection
 */
class MediaFoldersTable extends Table {
	/**
	 * @inheritDoc
	 */
	public const TABLE = 'media_folders';


	/**
	 * @inheritDoc
	 */
	protected array $nest = [
		'enabled' => true,
		'relatedColumns' => ['languageShortcode'],
	];
	/**
	 * @inheritDoc
	 */
	protected array $systemOrder = [
		'relatedColumns' => ['languageShortcode', 'parentId'],
	];


	/**
	 * @inheritDoc
	 */
	public function initializeAssociations(): void {
		$this->belongsTo('Languages', [
			'bindingKey' => 'shortcode',
			'conditions' => ['realm' => Awyiss::REALM_FRONTEND],
			'foreignKey' => 'language_shortcode',
		]);

		$this->hasMany('Media', [
			'cascadeCallbacks' => true,
			'dependent' => true,
		]);

		$this->hasMany('MediaAssignments', [
			'cascadeCallbacks' => true,
			'dependent' => true,
			'saveStrategy' => 'replace',
		]);
	}


	/**
	 * Returns the default validator object.
	 *
	 * @param Validator $validator The validator that can be modified to
	 * add some rules to it.
	 * @return Validator
	 */
	public function validationDefault(Validator $validator): Validator {
		parent::validationDefault($validator);


		$validator->requirePresence([
			'languageShortcode',
			'path',
			'title',
		], 'create');


		$validator->add('id', [
			'isInteger' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 11]],
		]);


		$validator->add('parentId', [
			'isInteger' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 11]],
		]);


		$validator->add('languageShortcode', [
			'isScalar' => ['rule' => 'isScalar'],
			'ascii' => ['rule' => 'ascii'],
			'exactLength' => [
				'message' => __df($this->getI18nDomain(), 'validation', 'error_exact_length', 2),
				'rule' => function ($shortcode) {
					return strlen($shortcode) == 2;
				},
			],
		]);


		$validator->notEmptyString('path');
		$validator->add('path', [
			'isScalar' => ['rule' => 'isScalar'],
			'maxLength' => ['rule' => ['maxLength', 1024]],
			'notBlank' => ['rule' => 'notBlank'],
		]);


		$validator->notEmptyString('title');
		$validator->add('title', [
			'isScalar' => ['rule' => 'isScalar'],
			'maxLength' => ['rule' => ['maxLength', 100]],
			'notBlank' => ['rule' => 'notBlank'],
		]);


		$validator->add('systemOrder', [
			'isInteger' => ['rule' => 'isInteger'],
		]);


		$validator->add('hidden', [
			'boolean' => ['rule' => 'boolean'],
		]);


		$validator->add('active', [
			'boolean' => ['rule' => 'boolean'],
		]);


		$validator->add('parentsActive', [
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
			$rules->existsIn('languageShortcode', 'Languages'),
			'languageExists',
			[
				'errorField' => 'languageShortcode',
				'message' => __df($this->getI18nDomain(), 'validation', 'error_language_exists'),
			]
		);


		$rules->add(
			function (MediaFolder $entity, array $options): bool|string {
				if ($entity->get('id') === 1 && $options['isCopy'] === false) {
					if ($entity->get('languageShortcode') !== null) {
						return __df($this->getI18nDomain(), 'validation', 'error_root_language_shortcode_unchanged');
					}

					if ($entity->get('title') !== 'Media') {
						return __df($this->getI18nDomain(), 'validation', 'error_root_title_unchanged');
					}

					if ($entity->get('parentId') !== null) {
						return __df($this->getI18nDomain(), 'validation', 'error_root_parent_id_unchanged');
					}

					if ($entity->get('path') !== 'media') {
						return __df($this->getI18nDomain(), 'validation', 'error_root_path_unchanged');
					}
				}

				return true;
			},
			'rootUnchanged',
			[
				'errorField' => '_general',
			]
		);


		$rules->add(
			function (MediaFolder $entity/*, array $options*/): bool|string {
				return $entity->get('parentId') !== 1;
			},
			'notNestedUnderRoot',
			[
				'errorField' => 'parentId',
				'message' => __df($this->getI18nDomain(), 'validation', 'error_not_nested_under_root'),
			]
		);


		$rules->addDelete(
			function (MediaFolder $entity/*, array $options*/): bool {
				return $entity->id !== 1;
			},
			'notRootDeletion',
			[
				'errorField' => '_general',
				'message' => __df($this->getI18nDomain(), 'validation', 'error_not_root_deletion'),
			]
		);


		return $rules;
	}


	/**
	 * @inheritDoc
	 */
	protected function initializeSchema(TableSchemaInterface $schema): void {
		parent::initializeSchema($schema);

		$schema->setColumnType('meta_data', 'json');
	}


	/**
	 * @param \Cake\ORM\Query\SelectQuery $query
	 * @param array $options
	 * @return \Cake\ORM\Query\SelectQuery
	 * @noinspection PhpUnused
	 */
	public function findActive(SelectQuery $query): SelectQuery {
		$query->where([
			'active' => true,
			'parents_active' => true,
		]);


		return $query;
	}
}
