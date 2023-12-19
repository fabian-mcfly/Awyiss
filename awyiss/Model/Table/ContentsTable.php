<?php declare(strict_types=1);


namespace Awyiss\Model\Table;


use Awyiss\Model\Entity\Content;
use Awyiss\Model\Entity\Page;
use Awyiss\Model\Table;
use Cake\Collection\Collection;
use Cake\Collection\CollectionInterface;
use Cake\Database\Schema\TableSchemaInterface;
use Cake\Datasource\EntityInterface;
use Cake\Datasource\Exception\InvalidPrimaryKeyException;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\Datasource\FactoryLocator;
use Cake\Http\Exception\ForbiddenException;
use Cake\ORM\Exception\MissingTableClassException;
use Cake\ORM\Query;
use Awyiss\ORM\RulesChecker;
use Cake\Validation\Validator;
use Exception;
use RuntimeException;


/**
 * Contents Model
 *
 * @property \Awyiss\Model\Table\ContentTemplatesTable&\Cake\ORM\Association\BelongsTo $ContentTemplates
 * @property \Awyiss\Model\Table\ContentsTable&\Cake\ORM\Association\BelongsTo $Parent
 * @property \Awyiss\Model\Table\PagesTable&\Cake\ORM\Association\BelongsTo $Pages
 * @property \Awyiss\Model\Table\ContentsTable&\Cake\ORM\Association\HasMany $Children
 *
 * @method Content newDefaultEntity(array $aa_additionalData = [])
 * @method CollectionInterface|NULL getChildren(EntityInterface $ao_entity, array $aa_options = [], int $ai_currentLevel = 0)
 * @method CollectionInterface|NULL getDirectChildren(EntityInterface $ao_entity)
 * @method Content getParent(EntityInterface $ao_entity)
 * @method CollectionInterface|NULL getParents(EntityInterface $ao_entity, array $aa_options = [], int $ai_currentLevel = 0)
 */
class ContentsTable extends Table {
	/**
	 * @inheritDoc
	 */
	protected array $_defaultConfig = [
		'nested' => [
			'children' => [
				'associationName' => 'Children',
			],
			'parent' => [
				'associationName' => 'Parent',
			],
			'relatedColumns' => ['page_id', 'template_position'],
		],
		'systemOrder' => [
			'relatedColumns' => ['page_id', 'template_position', 'parent_id'],
		],
	];
	public const TABLE = 'contents';


	/**
	 * @inheritDoc
	 */
	public function initialize (array $aa_config): void {
		parent::initialize($aa_config);

		$this->addBehavior('Nested', $this->getConfig('nested', []));

		$this->setTable(static::TABLE);
		$this->setPrimaryKey('id');

		$this->belongsTo('ContentTemplates', [
			'joinType' => 'INNER',
		]);

		$this->belongsTo('Pages', [
			'joinType' => 'INNER',
		]);

		$this->belongsTo('Parent', [
			'className' => 'Contents',
			'foreignKey' => 'parent_id',
		]);

		$this->hasMany('Children', [
			'className' => 'Contents',
			'foreignKey' => 'parent_id',
		]);

		$this->hasMany('Duplicate', [
			'bindingKey' => 'duplicate_of',
			'className' => 'Contents',
			'foreignKey' => 'id',
		]);
	}


	/**
	 * Returns the default validator object.
	 *
	 * @param \Cake\Validation\Validator $ao_validator The validator that can be modified to
	 * add some rules to it.
	 *
	 * @return \Cake\Validation\Validator
	 *
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function validationDefault (Validator $ao_validator): Validator {
		$ao_validator->scalar('title')->maxLength('title', 255)->allowEmptyString('title');

		$ao_validator->scalar('subtitle')->maxLength('subtitle', 255)->allowEmptyString('subtitle');

		$ao_validator->scalar('text')->allowEmptyString('text');

		$ao_validator->scalar('link')->maxLength('link', 255)->allowEmptyString('link');

		$ao_validator->dateTime('publishdate_start')->allowEmptyDateTime('publishdate_start');

		$ao_validator->dateTime('publishdate_end')->allowEmptyDateTime('publishdate_end');

		$ao_validator->scalar('template_position')->maxLength('template_position', 100)->allowEmptyString('template_position');

		$ao_validator->integer('content_template_id')->requirePresence('content_template_id', 'create');

		$ao_validator->numeric('columnwidth')->notEmptyString('columnwidth');

		$ao_validator->scalar('css_class')->maxLength('css_class', 255)->allowEmptyString('css_class');

		$ao_validator->integer('duplicate_of')->allowEmptyString('duplicate_of');

		$ao_validator->isArray('data')->maxLength('data', 4294967295)->allowEmptyArray('data');

		$ao_validator->integer('system_order')->requirePresence('system_order')->notEmptyString('system_order');

		$ao_validator->boolean('active')->notEmptyString('active');

		$ao_validator->boolean('deleted')->notEmptyString('deleted');

		$ao_validator->integer('parent_id')->allowEmptyString('parent_id');

		$ao_validator->integer('page_id')->requirePresence('page_id')->notEmptyString('page_id');

		return $ao_validator;
	}


	/**
	 * Returns a RulesChecker object after modifying the one that was supplied.
	 *
	 * @param \Awyiss\ORM\RulesChecker|\Cake\ORM\RulesChecker $ao_rules The rules object to be modified.
	 *
	 * @return \Awyiss\ORM\RulesChecker
	 *
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function buildRules (RulesChecker|\Cake\ORM\RulesChecker $ao_rules): RulesChecker {
		$ao_rules->add($ao_rules->existsIn(['content_template_id'], 'ContentTemplates', ['authorization' => ['skip' => TRUE]]), ['errorField' => 'content_template_id']);

		$ao_rules->add($ao_rules->existsIn(['parent_id'], 'Parent'), ['errorField' => 'parent_id']);

		/**
		 * The ExistsIn rule also ensures that the user has access to the scope (page role) for the page with `page_id`,
		 * since it uses the Pages association, and therefore it's assigned access policy.
		 *
		 * For this to properly work, it's neccessary to set up the association for the right page resp. page role,
		 * using either `forPage()` or `forPageRole()`
		 *
		 * @see \Awyiss\Model\Table\ContentsTable::forPage()
		 * @see \Awyiss\Model\Table\ContentsTable::forPageRole()
		 */
		$ao_rules->add($ao_rules->existsIn(['page_id'], 'Pages'), ['errorField' => 'page_id']);

		$ao_rules->add($ao_rules->existsIn(['duplicate_of'], 'Duplicate'), ['errorField' => 'duplicate_of']);

		$ao_rules->add(function(Content $ao_entity/*, array $aa_options*/) {
			//Retreive the page and the assigned page template, as well as the content template of the current entity
			try {
				/** @var \Awyiss\Model\Table\ContentsTable $lo_table */
				$lo_table = FactoryLocator::get('Table')->get('Contents');
				/** @var Page $lo_page */
				$lo_page = $lo_table->Pages->get($ao_entity->page_id, [
					'authorization' => ['skip' => TRUE],
					'contain' => [
						'PageTemplates' => [
							'finder' => ['all' => ['authorization' => ['skip' => TRUE]]],
						],
					]
				]);

				$lo_contentTemplate = $lo_table->ContentTemplates->get($ao_entity->content_template_id, [
					'authorization' => ['skip' => TRUE],
				]);
			}
			catch (RecordNotFoundException|InvalidPrimaryKeyException) {
				return FALSE;
			}

			//All template positions for the content template of the current entity, inside the page template of the current entity's page
			$la_availableTemplatePositions = $lo_contentTemplate->assigned_template_positions[ $lo_page->page_template_id ] ?? [];

			//Return TRUE or FALSE, depending on whether the provided template_position is valid
			return ! empty($ao_entity->template_position) && in_array($ao_entity->template_position, $la_availableTemplatePositions);
		}, [
			'errorField' => 'template_position',
			'message' => __('::invalid_template_position'),
		]);

		$ao_rules->add(function(Content $ao_entity) {
			//Retreive tthe content template of the current entity
			try {
				/** @var \Awyiss\Model\Entity\ContentTemplate $lo_contentTemplate */
				$lo_contentTemplate = $this->ContentTemplates->get($ao_entity->content_template_id, [
					'authorization' => ['skip' => TRUE],
				]);
			}
			catch (RecordNotFoundException|InvalidPrimaryKeyException) {
				return FALSE;
			}

			$la_data = $ao_entity->extract($this->getSchema()->columns());

			/**
			 * Instantiate a new validator
			 *
			 * @var \Awyiss\Validation\Validator $lo_validator
			 */
			$lo_validator = new $this->_validatorClass();
			$lo_validator->setI18nDomain($this->getAlias());

			//Traverse all elements that are available inside the content template
			foreach ($lo_contentTemplate->available_elements as $la_element) {
				if (($la_element['required'] ?? NULL) === TRUE) {
					//If the element is marked as required, add a requirePresence check and do not allow an empty string as value
					$lo_validator->requirePresence($la_element['name'])->notEmptyString($la_element['name']);
				}
			}

			//Validate the entity using the
			$la_errors = $lo_validator->validate($la_data, $ao_entity->isNew());
			$ao_entity->setErrors($la_errors);

			return empty($la_errors);
		}, 'requiredTemplateFields');

		return $ao_rules;
	}


	/**
	 * Groups the result of a query or the elements of a collection by their `template-position`-value and returns
	 * a new collection
	 *
	 * @noinspection PhpUnused
	 */
	public function groupByTemplatePosition (Query|CollectionInterface $ax_data): CollectionInterface {
		$lo_data = is_a($ax_data, Query::class) ? $ax_data->all() : $ax_data;

		foreach ($lo_data->groupBy('template_position') as $ls_templatePosition => $la_contents) {
			$lo_data->$ls_templatePosition = new Collection($la_contents);
		}

		return $lo_data;
	}


	/**
	 * Creates a threaded list of contents from a query, adding the `level`-property to each content and returns
	 * a collection
	 *
	 * @noinspection PhpUnused
	 */
	public function listNested (Query $ao_query): CollectionInterface {
		$lo_contents = $ao_query->find('threaded')->all()->listNested();

		/** @var Content $lo_content */
		foreach ($lo_contents as $lo_content) {
			$lo_content->setVirtual(['level']);
			/** @noinspection PhpPossiblePolymorphicInvocationInspection */
			$lo_content->level = $lo_contents->getDepth();
		}

		return $lo_contents;
	}


	/**
	 * Groups the result of a query by their `template-position`-value and returns a new collection with all
	 * contents nested and an added `level`-property.
	 *
	 * @param \Cake\ORM\Query $ao_query
	 *
	 * @return \Cake\Collection\CollectionInterface
	 */
	public function nestedByTemplatePosition (Query $ao_query): CollectionInterface {
		return $ao_query->find('threaded')->all()->groupBy('template_position')->map(function($aa_contents) {
			$lo_contents = (new Collection($aa_contents))->listNested();

			/** @var Content $lo_content */
			foreach ($lo_contents as $lo_content) {
				$lo_content->setVirtual(['level']);
				/** @noinspection PhpPossiblePolymorphicInvocationInspection */
				$lo_content->level = $lo_contents->getDepth();
			}

			return $lo_contents;
		});
	}


	/**
	 * Sets this table to use the 'Pages'-association for a specific page.
	 *
	 * @param int $ai_page_id
	 *
	 * @return \Awyiss\Model\Entity\Page
	 *
	 * @throws ForbiddenException
	 * @throws InvalidPrimaryKeyException
	 * @throws MissingTableClassException
	 * @throws RecordNotFoundException
	 * @throws Exception
	 * @throws RuntimeException
	 */
	public function forPage (int $ai_page_id): Page {
		/** @var Page $lo_page */
		$lo_page = $this->Pages->get($ai_page_id, [
			'authorization' => [
				//'failSilently' => FALSE,
				'skip' => TRUE,
			],
			'contain' => [
				'PageRoles' => [
					'finder' => ['all' => ['authorization' => ['skip' => TRUE]]],
				],
				'PageTemplates' => [
					'finder' => ['all' => ['authorization' => ['skip' => TRUE]]],
				],
			],
		]);

		try {
			$this->forPageRole($lo_page->page_role->identifier);
		}
		catch (ForbiddenException) {
			throw new ForbiddenException(sprintf('Access to page id `%s` is forbidden', $ai_page_id));
		}

		return $lo_page;
	}


	/**
	 * Sets this table to run the access check of the 'Pages'-association with a specific page role.
	 *
	 * @param string $as_identifier
	 *
	 * @return void
	 *
	 * @throws ForbiddenException
	 * @throws MissingTableClassException
	 * @throws Exception
	 * @throws RuntimeException
	 */
	public function forPageRole (string $as_identifier): void {
		//Remember the currently used foreign key for the Pages association
		$ls_foreignKey = $this->Pages->getForeignKey();

		$ls_constant = 'PAGEROLE_' . strtoupper($as_identifier);
		if (!defined($ls_constant)) {
			throw new RuntimeException(sprintf('Cannot use `%s` for page role `%s`', static::class, $as_identifier));
		}

		/** @var \Awyiss\ORM\Locator\TableLocator $lo_tableLocator */
		$lo_tableLocator = FactoryLocator::get('Table');
		$lo_newTable = $lo_tableLocator->get($as_identifier);

		//Set a new table object in the BelongsTo relation, using the page role identifier
		$this->Pages->setTarget($lo_newTable)->setForeignKey($ls_foreignKey);

		if ($lo_newTable->hasBehavior('Authorization')) {
			/** @var \Awyiss\Model\Behavior\AuthorizationBehavior $lo_authorization */
			$lo_authorization = $lo_newTable->getBehavior('Authorization');

			//TODO: think about switching from 'read' to something like 'contents', so read access to a page doesn't automatically allow modifying contents
			if ( ! $lo_authorization->isAccessible('read')) {
				throw new ForbiddenException(sprintf('Access to page role `%s` is forbidden', $as_identifier));
			}
		}
	}


	/**
	 * @inheritDoc
	 *
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	protected function _initializeSchema (TableSchemaInterface $ao_schema): TableSchemaInterface {
		$ao_schema->setColumnType('data', 'json');

		return $ao_schema;
	}
}
