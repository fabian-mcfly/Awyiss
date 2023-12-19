<?php

declare(strict_types=1);


namespace Awyiss\Model;


use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\ORM\Association;
use Cake\ORM\Query;


/**
 * Class Table
 *
 * @package Awyiss\Model
 */
abstract class Table extends \Cake\ORM\Table {
	private array $la_auditData = [];
	private array $la_defaultConfig = [
		'setTimeOnNew' => TRUE,
		'setTimeOnUpdate' => TRUE,
		'saveAudit' => TRUE,
	];
	private ?bool $lb_hasAttributes = NULL;
	private string $ls_attributesTable;


	/**
	 * Initialize method
	 *
	 * @param array $aa_config The configuration for the Table.
	 *
	 * @return void
	 */
	public function initialize (array $aa_config): void {
		parent::initialize($aa_config);

		$this->_validatorClass = \Awyiss\Validation\Validator::class;

		if (substr($this->getTable(), 0, 11) != '_attributes' && $this->getTable() != 'attri') { //$this->getTable() != 'attri' <- ?? Whatever CakePHP does, this is needed
			$this->ls_attributesTable = '_attributes_' . $this->getTable();
			if (is_null($this->lb_hasAttributes)) {
				$this->lb_hasAttributes = count($this->getConnection()
						->execute('SHOW TABLES LIKE \'' . $this->ls_attributesTable . '\'')
						->fetchAll('assoc')) == 1;

				if ($this->lb_hasAttributes) {
					$lo_assoc = $this->hasOne('Attributes')
						->setClassName(\Awyiss\Model\Table\Attributes::class)
						->setForeignKey('parent_id')
						->setProperty('attributes')
						->setDependent(TRUE);
					$lo_assoc->setTable($this->ls_attributesTable);
				}
			}
		}
	}


	/**
	 * @param null|string $as_type
	 * @param array $aa_options
	 *
	 * @return \Cake\ORM\Query
	 */
	public function find (?string $as_type = NULL, array $aa_options = []): Query {
		$lo_query = $this->query();
		$lo_query->select();

		$ls_type = $as_type;
		if (is_null($as_type)) {
			if (defined('IS_BACKEND') && IS_BACKEND) {
				$ls_type = 'all';
			}
			else {
				$ls_type = 'withAttributes';
			}
		}

		return $this->callFinder($ls_type, $lo_query, $aa_options);
	}


	/**
	 * @param \Cake\ORM\Query $ao_query
	 * @param array $aa_options
	 *
	 * @return \Cake\ORM\Query
	 */
	public function findWithAttributes (Query $ao_query, array $aa_options): Query {
		if ($this->lb_hasAttributes) {
			$ao_query->contain('Attributes');
			//dd($ao_query->order(['background_color DESC NULLS FIRST']));
		}

		return $ao_query;
	}


	/**
	 * @param \Cake\Event\EventInterface $ao_event
	 * @param \Cake\ORM\Query $ao_query
	 * @param \ArrayObject $ao_options
	 * @param $ab_primary
	 */
	public function beforeFind (EventInterface $ao_event, Query $ao_query, \ArrayObject $ao_options, $ab_primary) {
		$lo_schema = $this->getSchema();
		if ($lo_schema->getColumn('deleted')) {
			$ls_column = 'deleted';
			$lb_addCondition = TRUE;

			$ao_query->traverseExpressions(function($expression) use (&$lb_addCondition, $ls_column) {
				if ( ! $lb_addCondition) {
					return;
				}

				if ($expression instanceof \Cake\Database\Expression\IdentifierExpression && $expression->getIdentifier() === $ls_column) {
					$lb_addCondition = FALSE;

					return;
				}

				if (($expression instanceof \Cake\Database\Expression\ComparisonExpression || $expression instanceof \Cake\Database\Expression\BetweenExpression) && $expression->getField() === $ls_column) {
					$lb_addCondition = FALSE;
				}
			});

			$la_options = $ao_query->getOptions();

			if ($lb_addCondition && empty($la_options['skipDeletedCondition'])) {
				$ao_query->andWhere([$ls_column => 0]);
			}
		}
	}


	/**
	 * @param \Cake\Event\EventInterface $ao_event
	 * @param \Cake\Datasource\EntityInterface $ao_entity
	 * @param \ArrayObject $aa_options
	 *
	 * @return bool
	 */
	public function beforeSave (EventInterface $ao_event, EntityInterface $ao_entity, \ArrayObject $aa_options) {
		$lb_isNew = $ao_entity->isNew();
		$la_options = \Cake\Utility\Hash::merge($this->la_defaultConfig, $aa_options);

		if (empty($ao_entity->deleted)) {
			$lo_schema = $this->getSchema();

			$li_identityId = NULL;
			$lo_identity = \Cake\Routing\Router::getRequest()->getAttribute('identity');
			if ($lo_identity) {
				$li_identityId = $lo_identity->id;
				if ($lo_identity instanceof \Awyiss\Model\Entity\UsersExternal) {
					$li_identityId *= -1;
				}
			}

			if ($lb_isNew && $lo_schema->getColumn('created_on') && $la_options['setTimeOnNew']) {
				$ao_entity->set('created_on', \Cake\I18n\Time::now());
				if ($lo_schema->getColumn('created_by')) {
					$ao_entity->set('created_by', $li_identityId);
				}
			}
			elseif ( ! $lb_isNew && $lo_schema->getColumn('changed_on') && $la_options['setTimeOnUpdate']) {
				$ao_entity->set('changed_on', \Cake\I18n\Time::now());
				if ($lo_schema->getColumn('changed_by')) {
					$ao_entity->set('changed_by', $li_identityId);
				}
			}
		}

		$this->la_auditData = [];
		if ( ! $lb_isNew && static::class != 'Awyiss\Model\Table\AuditTable' && $la_options['saveAudit']) {
			//Existing entities make their way into the audit database with the old and new data
			$la_oldData = $ao_entity->getOriginalValues();
			$la_newData = $ao_entity->extract(array_keys($la_oldData));

			$li_createdBy = NULL;
			$lo_identity = \Cake\Routing\Router::getRequest()->getAttribute('identity');
			if ($lo_identity) {
				$li_createdBy = $lo_identity->id;
				if ($lo_identity instanceof \Awyiss\Model\Entity\UsersExternal) {
					$li_createdBy *= -1;
				}
			}

			$this->la_auditData = [
				'type' => ! empty($ao_entity->deleted) ? 'd' : 'u',
				'model' => '\\' . static::class,
				'parent_id' => $ao_entity->get('id'),
				'data_old' => $la_oldData,
				'data_new' => $la_newData,
				'created_on' => new \Cake\I18n\Time(),
				'created_by' => $li_createdBy,
			];
		}

		return TRUE;
	}


	/**
	 * @param \Cake\Event\EventInterface $ao_event
	 * @param \Cake\Datasource\EntityInterface $ao_entity
	 * @param \ArrayObject $aa_options
	 */
	public function afterSave (EventInterface $ao_event, EntityInterface $ao_entity, \ArrayObject $aa_options) {
		if ($this->la_auditData) {
			$lo_auditModel = \Cake\ORM\TableRegistry::getTableLocator()->get('Audit');
			$lo_audit = $lo_auditModel->newEntity($this->la_auditData);

			$lo_auditModel->save($lo_audit);
			$this->la_auditData = [];
		}
	}


	/**
	 * @param \Cake\Event\EventInterface $ao_event
	 * @param \Cake\Datasource\EntityInterface $ao_entity
	 * @param \ArrayObject $aa_options
	 *
	 * @return bool
	 */
	public function beforeDelete (EventInterface $ao_event, EntityInterface $ao_entity, \ArrayObject $aa_options): bool {
		$lo_schema = $this->getSchema();
		if ($lo_schema->getColumn('deleted') && $lo_schema->getColumn('deleted_on')) {
			if ( ! $this->softDelete($ao_entity, $aa_options->getArrayCopy())) {
				throw new \RuntimeException();
			}

			$ao_event->stopPropagation();

			/** @var \Cake\ORM\Table $table */
			$table = $ao_event->getSubject();
			$table->dispatchEvent('Model.afterDelete', [
				'entity' => $ao_entity,
				'options' => $aa_options,
			]);
		}

		return TRUE;
	}


	/**
	 * @param mixed $conditions
	 *
	 * @return int
	 */
	/*public function deleteAll ($conditions): int {
		return $this->_table->updateAll([
			'deleted_on' => new \Cake\I18n\Time(),
			'deleted' => 1,
		], $conditions);

		return parent::deleteAll($conditions);
	}*/


	/**
	 * @param \Cake\Datasource\EntityInterface $ao_entity
	 * @param array $aa_options
	 *
	 * @return bool
	 */
	public function softDelete (EntityInterface $ao_entity, array $aa_options = []): bool {
		$lo_schema = $this->getSchema();
		$ls_primaryKey = $lo_schema->getPrimaryKey();

		foreach ($ls_primaryKey AS $ls_field) {
			if ( ! $ao_entity->has($ls_field)) {
				throw new \RuntimeException();
			}
		}

		foreach ($this->associations() AS $association) {
			if ($this->_isRecursable($association)) {
				$association->cascadeDelete($ao_entity, ['_primary' => FALSE] + $aa_options);
			}
		}

		$li_identityId = NULL;
		$lo_identity = \Cake\Routing\Router::getRequest()->getAttribute('identity');
		if ($lo_identity) {
			$li_identityId = $lo_identity->id;
			if ($lo_identity instanceof \Awyiss\Model\Entity\UsersExternal) {
				$li_identityId *= -1;
			}
		}

		$ao_entity->set([
			'deleted_on' => new \Cake\I18n\Time(),
			'deleted' => 1,
		], ['guard' => FALSE]);

		if ($lo_schema->getColumn('deleted_by')) {
			$ao_entity->set('deleted_by', $li_identityId);
		}

		if ($this->save($ao_entity, $aa_options)) {
			return TRUE;
		}

		return FALSE;
	}


	/**
	 * @param \Cake\ORM\Association $ao_association
	 *
	 * @return bool
	 */
	protected function _isRecursable (Association $ao_association): bool {
		if ($ao_association->isOwningSide($this) && $ao_association->getDependent() && $ao_association->getCascadeCallbacks()) {
			return TRUE;
		}

		return FALSE;
	}
}