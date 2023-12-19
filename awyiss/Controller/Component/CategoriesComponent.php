<?php declare(strict_types=1);


namespace Awyiss\Controller\Component;


use Cake\Collection\CollectionInterface;
use Cake\Controller\Component;
use Cake\Datasource\EntityInterface;
use Cake\Datasource\ResultSetInterface;
use Cake\ORM\Query;
use Cake\Utility\Inflector;


/**
 * @method \Awyiss\Controller\AppController getController()
 */
class CategoriesComponent extends Component {
	protected $_defaultConfig = [
		'aggregationKey' => 'all',
		'allowAggregation' => TRUE,
		'allowUnassigned' => FALSE,
		'associationName' => NULL,
		'combinator' => [
			'id',
			'title',
			NULL,
		],
		'defaultVal' => NULL,
		'enabled' => NULL,
		'foreignKey' => NULL,
		'name' => 'category',
		'paginate' => TRUE,
		'selectedCategory' => NULL,
		'tableName' => NULL,
		'unassignedKey' => 'unassigned',
		'verifySelection' => TRUE,
	];


	public function startup () {
		if ( ! $this->getConfig('name')) {
			throw new \RuntimeException(sprintf('`%s` is missing the name attribute.', static::class));
		}

		if ( ! $this->getConfig('enabled') || ! $this->getConfig('associationName')) {
			return;
		}

		$ls_name = $this->getConfig('name');
		$ls_uriKey = Inflector::dasherize($ls_name);
		$ls_tableName = $this->getConfig('tableName');
		if ( ! $ls_tableName) {
			$ls_tableName = $this->getController()->defaultTable ?? $this->getController()->getName();
			$this->setConfig('tableName', $ls_tableName);
		}

		$lo_request = $this->getController()->getRequest();
		$lo_session = $lo_request->getSession();
		$ls_sessionIdentifier = 'categories.' . ($lo_request->getParam('lang') ?? 'global') . '.' . Inflector::underscore($ls_tableName) . '.' . Inflector::underscore($ls_name) . '_id';

		$lo_categories = $this->getCategories($ls_tableName);
		$la_categories = $lo_categories->combine(...$this->getConfig('combinator'))->toArray();
		$this->setConfig('categories.raw', $lo_categories);
		$this->setConfig('categories.simple', $la_categories);

		if ($lx_category_id = $lo_request->getParam($ls_uriKey)) {
			if ($lo_session->started()) {
				$lo_session->write($ls_sessionIdentifier, $lx_category_id);
			}
		}
		elseif ( ! $lo_session->started() || ! ($lx_category_id = $lo_session->read($ls_sessionIdentifier))) {
			if (($lx_category_id = $this->getConfig('defaultVal')) === NULL) {
				$lx_category_id = $this->getConfig('allowAggregation') ? $this->getConfig('aggregationKey') : array_key_first($la_categories);
			}

			if ($lo_session->started()) {
				$lo_session->write($ls_sessionIdentifier, $lx_category_id);
			}
		}

		if ($this->getConfig('verifySelection') && ! array_key_exists($lx_category_id, $la_categories)) {
			if ((!$this->getConfig('allowUnassigned') || $lx_category_id != $this->getConfig('unassignedKey')) &&
				($lx_category_id = $this->getConfig('defaultVal')) === NULL) {
				$lx_category_id = $this->getConfig('allowAggregation') ? $this->getConfig('aggregationKey') : array_key_first($la_categories);
			}

			if ($lo_session->started()) {
				$lo_session->write($ls_sessionIdentifier, $lx_category_id);
			}
		}

		$this->setConfig('selectedCategory', $lx_category_id);

		$la_categorization = $lo_request->getAttribute('categorization', []);
		if ( ! is_array($la_categorization)) {
			$la_categorization = [];
		}
		$la_categorization[ $ls_name ] = $this->getConfig();

		$lo_request = $lo_request->withAttribute('categorization', $la_categorization);
		$this->getController()->setRequest($lo_request);
	}


	public function beforeRender (): void {
		$lo_view = $this->getController()->viewBuilder();

		$ls_dashedName = Inflector::dasherize($this->getConfig('name'));
		$ls_variableNameSingular = Inflector::variable($ls_dashedName);
		$ls_variableNamePlural = Inflector::variable(Inflector::pluralize($ls_dashedName));

		if ( ! $lo_view->getVar('aa_' . $ls_variableNamePlural)) {
			$lo_view->setVar('aa_' . $ls_variableNamePlural, $this->getConfig('categories'));
		}

		if ( ! $lo_view->getVar('ax_selected' . ucfirst($ls_variableNameSingular))) {
			$lo_view->setVar('ax_selected' . ucfirst($ls_variableNameSingular), $this->getConfig('selectedCategory'));
		}
	}


	public function getCategories (?string $as_tableName = NULL, ?string $as_associationName = NULL): ?ResultSetInterface {
		$ls_tableName = $as_tableName ?? $this->getConfig('tableName', $this->getController()->defaultTable ?? $this->getController()->getName());
		if ( ! $ls_tableName) {
			$ls_tableName = $this->getController()->defaultTable ?? $this->getController()->getName();
		}

		$lo_table = $this->getController()->$ls_tableName;
		if ( ! $lo_table) {
			return NULL;
		}

		$ls_associationName = $as_associationName ?? $this->getConfig('associationName');
		if ( ! $ls_associationName) {
			return NULL;
		}

		$lo_association = $lo_table->$ls_associationName;
		if ( ! $lo_association) {
			return NULL;
		}

		if (!$this->getConfig('foreignKey')) {
			$this->setConfig('foreignKey', $lo_association->getForeignKey());
		}

		return $lo_association->find()->all();
	}


	public function filterQuery (Query $ao_query, $ax_selectedCategory = NULL, ?string $as_column = NULL): Query {
		$ls_column = $as_column;

		$lo_table = $ao_query->getRepository();

		$ls_associationName = $this->getConfig('associationName');
		if ( ! $ls_associationName) {
			return $ao_query;
		}


		$lo_association = $lo_table->getAssociation($ls_associationName);


		if (empty($ls_column)) {
			$ls_column = $lo_association->getForeignKey();
		}

		$lx_selectedCategory = $ax_selectedCategory ?? $this->getConfig('selectedCategory');
		if ($lx_selectedCategory !== NULL && $lx_selectedCategory !== $this->getConfig('aggregationKey')) {
			if ($lo_association instanceof \Cake\ORM\Association\HasMany
				|| $lo_association instanceof \Cake\ORM\Association\BelongsToMany) {

				if ($lx_selectedCategory == $this->getConfig('unassignedKey')) {
					$lo_junction = $lo_association->junction();
					$ls_column = $lo_junction->getPrimaryKey() . ' IS';
					$ao_query->leftJoinWith($ls_associationName)->where([$lo_junction->getAlias() . '.' . $ls_column => NULL]);
				}
				else {
					$ao_query->matching($ls_associationName, function($ao_query) use ($lo_association, $lx_selectedCategory) {
						$lo_junction = $lo_association->junction();
						$ls_column = $lo_junction->associations()->get($lo_association->getName())->getForeignKey();

						return $ao_query->where([$lo_junction->getAlias() . '.' . $ls_column => $lx_selectedCategory]);
					});
				}
			}
			else {
				//dd($ls_column, $lx_selectedCategory);
				$ao_query->where([$ls_column => $lx_selectedCategory]);
			}
		}

		return $ao_query;
	}


	public function groupQuery (Query $ao_query, array $aa_associationOptions = [], ?string $as_associationName = NULL, ?string $as_column = NULL): Query|CollectionInterface {
		$ls_column = $as_column;
		$ls_associationName = $as_associationName ?? $this->getConfig('associationName');
		$la_associationOptions = $aa_associationOptions;

		if (empty($ls_column)) {
			if ( ! $ls_associationName) {
				return $ao_query;
			}

			$lo_table = $ao_query->getRepository();

			$lo_association = $lo_table->getAssociation($ls_associationName);
			$ls_column = $lo_association->getForeignKey();

			$lo_schema = $lo_association->getSchema();
			if ($lo_schema->getColumn('system_order')) {
				if ($lo_association instanceof \Cake\ORM\Association\HasMany
				|| $lo_association instanceof \Cake\ORM\Association\BelongsToMany) {
					if ( ! array_key_exists('sort', $la_associationOptions)) {
						$la_associationOptions['sort'] = [$lo_association->getAlias() . '.system_order' => 'ASC'];
					}
				}
				else {
					//Remember existing orders
					$lo_order = $ao_query->clause('order');

					$ao_query->orderAsc($lo_association->getAlias() . '.system_order', TRUE);

					if (!empty($lo_order)) {
						//Re-add remembered orders
						$lo_order->traverse(function($ao_clause) use ($ao_query) {
							$ao_query->order($ao_clause);
						});
					}
				}
			}
		}

		$ao_query->contain([$ls_associationName => $la_associationOptions]);

		if ($this->getConfig('paginate')) {
			$lo_result = $this->getController()->paginate($ao_query);
			return $lo_result->groupBy($ls_column);
		}

		/** @var CollectionInterface $ao_query */
		return $ao_query->groupBy($ls_column);
	}


	public function ensurePossibleCategorySelection (EntityInterface $ao_entity, ?ResultSetInterface $ao_records = NULL, string $as_column = NULL): void {
		$lo_records = $ao_records ?? $this->getConfig('categories.raw') ?? $this->getCategories();
		if (!$lo_records) {
			throw new \RuntimeException(sprintf('Method `ensurePossibleCategory` in `%s` Component requires a valid set of records to ensure a possible category selection', static::class));
		}

		$ls_column = $as_column ?? $this->getConfig('foreignKey');
		if ( ! $ls_column) {
			throw new \RuntimeException(sprintf('Method `ensurePossibleCategory` in `%s` Component requires a valid column to ensure a possible category selection', static::class));
		}

		if (is_null($ao_entity->$ls_column) || !$lo_records->firstMatch(['id' => $ao_entity->$ls_column])) {
			$ao_entity->$ls_column = $lo_records->first()->id;
		}
	}
}