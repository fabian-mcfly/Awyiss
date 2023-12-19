<?php declare(strict_types=1);


namespace Awyiss\Model\Table;


use Awyiss\Model\Entity\Content;
use Cake\Collection\CollectionInterface;
use Cake\Database\Schema\TableSchemaInterface;
use Cake\Datasource\EntityInterface;
use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\Validation\Validator;


/**
 * Contents Model
 *
 * @property \Awyiss\Model\Table\ContentTemplatesTable&\Cake\ORM\Association\BelongsTo $ContentTemplates
 * @property \Awyiss\Model\Table\ContentsTable&\Cake\ORM\Association\BelongsTo $Parent
 * @property \Awyiss\Model\Table\PagesTable&\Cake\ORM\Association\BelongsTo $Pages
 * @property \Awyiss\Model\Table\ContentsTable&\Cake\ORM\Association\HasMany $Children
 *
 * @method Content newDefaultEntity(array $aa_additionalData = [])
 * @method Content patchEntity(EntityInterface $ao_entity, array $aa_data, array $aa_options = [])
 * @method CollectionInterface|NULL getChildren(EntityInterface $ao_entity, array $aa_options = [], int $ai_currentLevel = 0)
 * @method CollectionInterface|NULL getDirectChildren(EntityInterface $ao_entity)
 * @method Content getParent(EntityInterface $ao_entity)
 * @method CollectionInterface|NULL getParents(EntityInterface $ao_entity, array $aa_options = [], int $ai_currentLevel = 0)
 */
class ContentsTable extends \Awyiss\Model\Table {
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
			'relatedColumns' => ['pages_id', 'template_position'],
		],
		'systemOrder' => [
			'relatedColumns' => ['pages_id', 'template_position', 'parent_id'],
		],
	];


	/**
	 * @inheritDoc
	 */
	public function initialize (array $aa_config): void {
		parent::initialize($aa_config);

		$this->addBehavior('Nested', $this->getConfig('nested', []));

		$this->setTable('contents');
		$this->setPrimaryKey('id');

		$this->belongsTo('ContentTemplates', [
			'foreignKey' => 'content_templates_id',
			'joinType' => 'INNER',
		]);

		$this->belongsTo('Pages', [
			'foreignKey' => 'pages_id',
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
			'className' => 'Contents',
			'foreignKey' => 'id',
		]);
	}


	/**
	 * @inheritDoc
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

		$ao_validator->integer('content_templates_id')->requirePresence('content_templates_id', 'create')->notEmptyString('content_templates_id');

		$ao_validator->numeric('columnwidth')->notEmptyString('columnwidth');

		$ao_validator->scalar('css_class')->maxLength('css_class', 255)->allowEmptyString('css_class');

		$ao_validator->integer('duplicate_of')->allowEmptyString('duplicate_of');

		$ao_validator->isArray('data')->maxLength('data', 4294967295)->allowEmptyArray('data');

		$ao_validator->integer('system_order')->requirePresence('system_order')->notEmptyString('system_order');

		$ao_validator->boolean('active')->notEmptyString('active');

		$ao_validator->boolean('deleted')->notEmptyString('deleted');

		$ao_validator->integer('parent_id')->allowEmptyString('parent_id');

		$ao_validator->integer('pages_id')->requirePresence('pages_id')->notEmptyString('pages_id');

		return $ao_validator;
	}


	/**
	 * @inheritDoc
	 *
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function buildRules (RulesChecker $ao_rules): RulesChecker {
		$ao_rules->add($ao_rules->existsIn(['content_templates_id'], 'ContentTemplates'), ['errorField' => 'content_templates_id']);

		$ao_rules->add($ao_rules->existsIn(['parent_id'], 'Parent'), ['errorField' => 'parent_id']);

		$ao_rules->add($ao_rules->existsIn(['pages_id'], 'Pages'), ['errorField' => 'pages_id']);

		$ao_rules->add($ao_rules->existsIn(['duplicate_of'], 'Duplicate'), ['errorField' => 'duplicate_of']);

		$ao_rules->add(function(Content $ao_entity) {
			$lo_contentTemplate = $this->ContentTemplates->get($ao_entity->content_templates_id);

			$la_data = $ao_entity->extract($this->getSchema()->columns(), TRUE);

			/** @var \Awyiss\Validation\Validator $lo_validator */
			$lo_validator = new $this->_validatorClass();
			$lo_validator->setI18nDomain($this->getAlias());

			foreach ($lo_contentTemplate->available_elements AS $la_element) {
				if (($la_element['required'] ?? NULL) === TRUE) {
					$lo_validator->requirePresence($la_element['name'])->notEmptyString($la_element['name']);
				}
			}

			$la_errors = $lo_validator->validate($la_data, $ao_entity->isNew());
			$ao_entity->setErrors($la_errors);

			return empty($la_errors);
		}, 'requiredTemplateFields');

		return $ao_rules;
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


	/**
	 * @noinspection PhpUnused
	 */
	public function groupByTemplatePosition (Query|CollectionInterface $ax_data): CollectionInterface {
		$lo_data = is_a($ax_data, Query::class) ? $ax_data->all() : $ax_data;

		foreach ($lo_data->groupBy('template_position') as $ls_templatePosition => $la_contents) {
			$lo_data->$ls_templatePosition = new \Cake\Collection\Collection($la_contents);
		}

		return $lo_data;
	}


	/**
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


	public function nestedByTemplatePosition (Query $ao_query): CollectionInterface {
		return $ao_query->find('threaded')->all()->groupBy('template_position')->map(function($aa_contents) {
			$lo_contents = (new \Cake\Collection\Collection($aa_contents))->listNested();

			/** @var Content $lo_content */
			foreach ($lo_contents as $lo_content) {
				$lo_content->setVirtual(['level']);
				/** @noinspection PhpPossiblePolymorphicInvocationInspection */
				$lo_content->level = $lo_contents->getDepth();
			}

			return $lo_contents;
		});
	}

	/*$validator
            ->add('role', 'validRole', [
                'rule' => 'isValidRole',
                'message' => __('You need to provide a valid role'),
                'provider' => 'table',
            ]);
        return $validator;
    }

    public function isValidRole($value, array $context): bool
    {
        return in_array($value, ['admin', 'editor', 'author'], true);
    }
	*/
}
