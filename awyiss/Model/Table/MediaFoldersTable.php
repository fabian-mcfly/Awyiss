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
 * @method \Awyiss\Model\Entity\MediaFolder newDefaultEntity(array $aa_additionalData = [], array $aa_options = [])
 * @method \Cake\Collection\CollectionInterface|null getNestedChildren(MediaFolder $ao_entity, array $aa_options = [], int $ai_currentLevel = 0)
 * @method \Cake\Collection\CollectionInterface|null getChildren(MediaFolder $ao_entity, array $aa_options = [])
 * @method \Awyiss\Model\Entity\MediaFolder getParent(MediaFolder $ao_entity, array $aa_options = [])
 * @method \Cake\Collection\CollectionInterface|null getParents(MediaFolder $ao_entity, array $aa_options = [], int $ai_currentLevel = 0)
 * @method \Cake\Collection\CollectionInterface getPossibleParents(\Awyiss\Model\Entity $ao_entity, \Cake\Collection\CollectionInterface $ao_threadedEntities)
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
		$this->hasMany('Media', [
			'cascadeCallbacks' => true,
			'dependent' => true,
		]);

		$this->belongsTo('Languages', [
			'bindingKey' => 'shortcode',
			'conditions' => ['realm' => Awyiss::REALM_FRONTEND],
			'foreignKey' => 'language_shortcode',
			'joinType' => 'INNER',
		]);
	}


	/**
	 * Returns the default validator object.
	 *
	 * @param Validator $ao_validator The validator that can be modified to
	 * add some rules to it.
	 * @return Validator
	 */
	public function validationDefault(Validator $ao_validator): Validator {
		parent::validationDefault($ao_validator);


		$ao_validator->requirePresence([
			'languageShortcode',
			'path',
			'title',
		], 'create');


		$ao_validator->add('id', [
			'isInteger' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 11]],
		]);


		$ao_validator->add('parentId', [
			'isInteger' => ['rule' => 'isInteger'],
			'maxLength' => ['rule' => ['maxLength', 11]],
		]);


		$ao_validator->add('languageShortcode', [
			'isScalar' => ['rule' => 'isScalar'],
			'ascii' => ['rule' => 'ascii'],
			'exactLength' => [
				'rule' => function ($as_shortcode) {
					return strlen($as_shortcode) == 2;
				},
			],
		]);


		$ao_validator->notEmptyString('path');
		$ao_validator->add('path', [
			'isScalar' => ['rule' => 'isScalar'],
			'maxLength' => ['rule' => ['maxLength', 1024]],
			'notBlank' => ['rule' => 'notBlank'],
		]);


		$ao_validator->notEmptyString('title');
		$ao_validator->add('title', [
			'isScalar' => ['rule' => 'isScalar'],
			'maxLength' => ['rule' => ['maxLength', 100]],
			'notBlank' => ['rule' => 'notBlank'],
		]);


		$ao_validator->add('systemOrder', [
			'isInteger' => ['rule' => 'isInteger'],
		]);


		$ao_validator->add('hidden', [
			'boolean' => ['rule' => 'boolean'],
		]);


		$ao_validator->add('active', [
			'boolean' => ['rule' => 'boolean'],
		]);


		$ao_validator->add('parentsActive', [
			'boolean' => ['rule' => 'boolean'],
		]);


		$ao_validator->add('deleted', [
			'boolean' => ['rule' => 'boolean'],
		]);


		return $ao_validator;
	}


	/**
	 * Returns a RulesChecker object after modifying the one that was supplied.
	 *
	 * @param RulesChecker|BaseRulesChecker $ao_rules The rules object to be modified.
	 * @return RulesChecker
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function buildRules(RulesChecker|BaseRulesChecker $ao_rules): RulesChecker {
		$ao_rules->add(
			$ao_rules->existsIn('languageShortcode', 'Languages'),
			'languageExists',
			[
				'errorField' => 'languageShortcode',
				'message' => __dfx($this->getI18nDomain(), 'validation', 'media_folders', 'error_language_exists'),
			]
		);


		$ao_rules->add(
			function (MediaFolder $ao_entity, array $aa_options) use ($ao_rules): bool|string {
				if ($ao_entity->get('id') === 1 && $aa_options['isCopy'] === false) {
					if ($ao_entity->get('languageShortcode') !== null) {
						return __d($this->getI18nDomain(), 'error_root_language_shortcode_unchanged');
					}

					if ($ao_entity->get('title') !== 'Media') {
						return __d($this->getI18nDomain(), 'error_root_title_unchanged');
					}

					if ($ao_entity->get('parentId') !== null) {
						return __d($this->getI18nDomain(), 'error_root_parent_id_unchanged');
					}

					if ($ao_entity->get('path') !== 'media') {
						return __d($this->getI18nDomain(), 'error_root_path_unchanged');
					}
				}

				return true;
			},
			'rootUnchanged',
			[
				'errorField' => '_general',
			]
		);


		$ao_rules->add(
			function (MediaFolder $ao_entity/*, array $aa_options*/) use ($ao_rules): bool|string {
				return $ao_entity->get('parentId') !== 1;
			},
			'notNestedUnderRoot',
			[
				'errorField' => 'parentId',
				'message' => __d($this->getI18nDomain(), 'error_not_nested_under_root'),
			]
		);


		$ao_rules->addDelete(
			function (MediaFolder $ao_entity/*, array $aa_options*/): bool {
				return $ao_entity->id !== 1;
			},
			'notRootDeletion',
			[
				'errorField' => '_general',
				'message' => __d($this->getI18nDomain(), 'error_not_root_deletion'),
			]
		);


		return $ao_rules;
	}


	/**
	 * @inheritDoc
	 */
	protected function initializeSchema(TableSchemaInterface $ao_schema): void {
		parent::initializeSchema($ao_schema);

		$ao_schema->setColumnType('meta_data', 'json');
	}


	/**
	 * @param \Cake\ORM\Query\SelectQuery $ao_query
	 * @param array $aa_options
	 * @return \Cake\ORM\Query\SelectQuery
	 * @noinspection PhpUnused
	 */
	public function findActive(SelectQuery $ao_query): SelectQuery {
		$ao_query->where([
			'active' => true,
			'parents_active' => true,
		]);


		return $ao_query;
	}
}
