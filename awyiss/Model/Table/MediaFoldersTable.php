<?php declare(strict_types=1);


namespace Awyiss\Model\Table;


use ArrayObject;
use Awyiss\Awyiss;
use Awyiss\Middleware\LocaleMiddleware;
use Awyiss\Model\Entity\MediaFolder;
use Awyiss\Model\Table;
use Awyiss\ORM\RulesChecker;
use Cake\Database\Expression\QueryExpression;
use Cake\Database\Schema\TableSchemaInterface;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
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
 * @method \Cake\Collection\CollectionInterface|null getNestedChildren(EntityInterface $ao_entity, array $aa_options = [], int $ai_currentLevel = 0)
 * @method \Cake\Collection\CollectionInterface|null getChildren(EntityInterface $ao_entity, array $aa_options = [])
 * @method \Awyiss\Model\Entity\MediaFolder getParent(EntityInterface $ao_entity, array $aa_options = [])
 * @method \Cake\Collection\CollectionInterface|null getParents(EntityInterface $ao_entity, array $aa_options = [], int $ai_currentLevel = 0)
 * @noinspection PhpUnnecessaryFullyQualifiedNameInspection
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
			function (MediaFolder $ao_entity/*, array $aa_options*/) use ($ao_rules): bool|string {
				if ($ao_entity->get('id') === 1) {
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
	 * Before saving a media folder, make sure its path is unique.
	 *
	 * @param EventInterface $ao_event
	 * @param \Awyiss\Model\Entity\MediaFolder $ao_entity
	 * @param ArrayObject $ao_options
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function beforeSave(EventInterface $ao_event, EntityInterface $ao_entity, ArrayObject $ao_options): void {
		$ls_field = $this->getSchema()->getColumn('path');
		$li_length = $ls_field ? $ls_field['length'] : 0;

		if (empty($ao_entity->path)) {
			//Make sure the path is set. Use the title if it's empty.
			$ao_entity->set('path', $ao_entity->title);
		}

		if (
			!$ao_entity->isDirty('path') &&
			!$ao_entity->isDirty('languageShortcode') &&
			!$ao_entity->isDirty('parentId') &&
			!$ao_entity->isDirty('deleted')
		) {
			//If neither the path, the language nor the parent id have changed, skip the path logic
			return;
		}

		$ls_prePath = '';
		if (!empty($ao_entity->parentId)) {
			/** @var \Awyiss\Model\Entity\MediaFolder $lo_parentMediaFolder */
			$lo_parentMediaFolder = $this->get($ao_entity->parentId);
			//If there's a parent media folder, add its path the one of the current media folder
			$ls_prePath = trim($lo_parentMediaFolder->path, '/') . '/';
		}

		$la_parts = explode('/', $ao_entity->path);
		$ls_path = array_pop($la_parts);
		$ls_path = $ls_prePath . rtrim($ls_path, '-');

		$ls_originalPath = $ao_entity->hasOriginal('path') ? $ao_entity->getOriginal('path') : $ao_entity->path;

		if (!str_starts_with($ls_path, 'media/') && $ls_path !== 'media') {
			$ls_path = 'media/' . $ls_path;
		}

		//When the path has changed
		if ($ls_path != $ls_originalPath) {
			$ls_field = $this->getAlias() . '.path';

			$la_conditions = [
				$ls_field => $ls_path,
			];


			$ls_primaryKey = $this->getPrimaryKey();
			$li_id = $ao_entity->get($ls_primaryKey);
			if ($li_id) {
				$la_conditions['NOT'] = [$this->getAlias() . '.' . $ls_primaryKey => $li_id];
			}

			/**
			 * `$la_conditions` holds an array of query conditions that are used to find media folders with the same
			 * path
			 *
			 * ```
			 * [
			 *    "MediaFolders.path" => "new/path/of/the/current/mediafolder"
			 *    "language_shortcode" => "de"
			 *    "NOT" => [
			 *        "MediaFolders.id" => 1234
			 *    ]
			 * ]
			 * ```
			 */

			$li_i = 1;
			$ls_suffix = '';

			//As long as a media folder with the same path exists, append an increasing number to the path and try again
			while ($this->exists($la_conditions)) {
				$li_i++;
				$ls_suffix = '-' . $li_i;

				if ($li_length && (mb_strlen($ls_path . $ls_suffix) > $li_length)) {
					$ls_path = mb_substr($ls_path, 0, $li_length - mb_strlen($ls_suffix));
				}

				$la_conditions[ $ls_field ] = $ls_path . $ls_suffix;
			}

			//Append the suffix, if it's not empty
			if ($ls_suffix) {
				$ls_path .= $ls_suffix;
			}
		}

		$ao_entity->set('path', $ls_path, ['setter' => false]);
		if (!$ao_entity->isNew() && $ls_path === $ls_originalPath) {
			$ao_entity->setDirty('path', false);
		}

		//dd($ao_entity);
	}


	/**
	 * @param EventInterface $ao_event
	 * @param \Awyiss\Model\Entity\MediaFolder $ao_entity
	 * @param ArrayObject $ao_options
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function afterSave(EventInterface $ao_event, EntityInterface $ao_entity, ArrayObject $ao_options): void {
		$ls_originalPath = $ao_entity->hasOriginal('path') ? $ao_entity->getOriginal('path') : null;


		$lb_makeDir = false;
		if ($ao_entity->isNew()) {
			$lb_makeDir = true;
		}
		elseif ($ls_originalPath && $ao_entity->path != $ls_originalPath) {
			foreach ([$this->getTable(), 'media'] as $ls_table) {
				$this->rebuildDatabasePath($ls_table, $ao_entity, $ls_originalPath);
			}

			if (is_dir(WWW_ROOT . str_replace('/', DS, $ls_originalPath))) {
				rename(
					WWW_ROOT . str_replace('/', DS, $ls_originalPath),
					WWW_ROOT . str_replace('/', DS, $ao_entity->path)
				);
			}
			else {
				$lb_makeDir = true;
			}
		}
		elseif (!is_dir(WWW_ROOT . str_replace('/', DS, $ao_entity->path))) {
			$lb_makeDir = true;
		}

		if ($lb_makeDir) {
			mkdir(WWW_ROOT . str_replace('/', DS, $ao_entity->path));
		}
	}


	/**
	 * @param \Cake\Event\EventInterface $ao_event
	 * @param \Awyiss\Model\Entity\MediaFolder $ao_entity
	 * @param \ArrayObject $ao_options
	 * @return void
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function beforeSoftDelete(EventInterface $ao_event, EntityInterface $ao_entity, ArrayObject $ao_options): void {
		if ($ao_options['_primary'] ?? null === true) {
			$ao_entity->path = substr_replace($ao_entity->path, '/_deleted_', strrpos($ao_entity->path, '/'), 1);
		}
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
	 * @param string|null $languageShortcode
	 * @param \Awyiss\Model\Entity\MediaFolder|null $entity
	 * @param bool $includeGlobal
	 * @return \Cake\ORM\Query\SelectQuery
	 * @throws \Exception
	 */
	public function findForCurrentLanguage(SelectQuery $ao_query, ?string $languageShortcode = null, ?MediaFolder $entity = null, bool $includeGlobal = true): SelectQuery {
		$ls_languageShortcode = $languageShortcode;

		if ($entity) {
			$ls_languageShortcode = $entity->languageShortcode;
		}

		if ($includeGlobal) {
			return $ao_query->where([
				'OR' => [
					'language_shortcode' => $ls_languageShortcode ?? LocaleMiddleware::getLanguage()->shortcode,
					'language_shortcode IS' => null,
				],
			]);
		}


		return $ao_query->where([
			'language_shortcode' . ($ls_languageShortcode ? '' : ' IS') => $ls_languageShortcode,
		]);
	}


	/**
	 * @param string $as_table
	 * @param \Awyiss\Model\Entity\MediaFolder $ao_entity
	 * @param mixed $ls_originalPath
	 * @return void
	 */
	protected function rebuildDatabasePath(string $as_table, MediaFolder $ao_entity, mixed $ls_originalPath): void {
		$lo_query = $this->updateQuery();

		/**
		 * UPDATE media_folders SET path = (CONCAT('newpath', substr(path, '8'))) WHERE path LIKE 'oldpath/%'
		 *
		 * @noinspection PhpUndefinedMethodInspection
		 */
		$lo_query->update($as_table)->set('path', $lo_query->newExpr($lo_query->func()->concat([
			$ao_entity->path,
			$lo_query->func()->substr([
				'path' => 'identifier',
				mb_strlen($ls_originalPath) + 1,
			], [
				null,
				'integer',
			]),
		])))->where(function (QueryExpression $ao_expression/*, Query $ao_query*/) use ($ls_originalPath) {
			return $ao_expression->like('path', $ls_originalPath . '/%');
		})->execute();
	}
}
