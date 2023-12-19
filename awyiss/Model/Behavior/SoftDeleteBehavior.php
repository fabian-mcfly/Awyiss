<?php declare(strict_types=1);


namespace Awyiss\Model\Behavior;


use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\ORM\Association;
use Cake\ORM\Behavior;
use Cake\ORM\Query;


class SoftDeleteBehavior extends Behavior {
	/**
	 * Default configuration
	 *
	 * These are merged with user-provided configuration when the behavior is used.
	 *
	 * @var array
	 */
	protected $_defaultConfig = [
		'enabled' => TRUE,
		'includeDeleted' => FALSE,
		'skipSoftDeleteBehavior' => FALSE,
	];


	/** @noinspection PhpParameterNameChangedDuringInheritanceInspection */
	public function initialize (array $aa_config): void {
		parent::initialize($aa_config);

		if ( ! $this->table()->getSchema()->getColumn('deleted')) {
			$this->setConfig('enabled', FALSE);
		}
	}


	/**
	 * @param \Cake\Event\EventInterface $ao_event
	 * @param \Cake\ORM\Query $ao_query
	 * @param \ArrayObject $ao_options
	 * @param $ab_primary
	 *
	 * @return bool
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function beforeFind (EventInterface $ao_event, Query $ao_query, \ArrayObject $ao_options, $ab_primary): bool {
		if ( ! $this->getConfig('enabled')) {
			return TRUE;
		}

		$la_options = \Cake\Utility\Hash::merge($this->getConfig(), $ao_options);

		if (empty($la_options['includeDeleted']) || $la_options['includeDeleted'] !== TRUE) {
			$ao_query->where([
				$this->table()->getAlias() . '.deleted' => 0,
			]);
		}

		return TRUE;
	}


	/**
	 * @param \Cake\Event\EventInterface $ao_event
	 * @param \Cake\Datasource\EntityInterface $ao_entity
	 * @param \ArrayObject $ao_options
	 *
	 * @return bool
	 *
	 * @noinspection PhpUnused
	 */
	public function beforeDelete (EventInterface $ao_event, EntityInterface $ao_entity, \ArrayObject $ao_options): bool {
		if ( ! $this->getConfig('enabled')) {
			return TRUE;
		}

		$lo_table = $this->table();
		$la_options = \Cake\Utility\Hash::merge($this->getConfig(), $ao_options);

		if ($la_options['skipSoftDeleteBehavior'] === TRUE) {
			return TRUE;
		}

		/**
		 * @noinspection PhpUndefinedMethodInspection
		 */
		if ( ! $lo_table->softDelete($ao_entity, $ao_options)) {
			throw new \RuntimeException();
		}

		$ao_event->stopPropagation();

		$lo_table->dispatchEvent('Model.afterDelete', [
			'entity' => $ao_entity,
			'options' => $ao_options,
		]);

		return TRUE;
	}


	/**
	 * @param \Cake\Datasource\EntityInterface $ao_entity
	 * @param \ArrayObject|array $ax_options
	 *
	 * @return bool
	 *
	 * @noinspection PhpUnused
	 */
	public function softDelete (EntityInterface $ao_entity, \ArrayObject|array $ax_options = []): bool {
		$lo_table = $this->table();

		$lo_options = is_array($ax_options) ? new \ArrayObject($ax_options) : $ax_options;

		$lo_table->dispatchEvent('Model.beforeSoftDelete', [
			'entity' => $ao_entity,
			'options' => $lo_options,
		]);

		$lo_schema = $this->table()->getSchema();
		$ls_primaryKey = $lo_schema->getPrimaryKey();

		foreach ($ls_primaryKey as $ls_field) {
			if ( ! $ao_entity->has($ls_field)) {
				throw new \RuntimeException();
			}
		}

		foreach ($lo_table->associations() as $association) {
			if ($this->isRecursable($association)) {
				$association->cascadeDelete($ao_entity, ['_primary' => FALSE] + $lo_options->getArrayCopy());
			}
		}

		/**
		 * @noinspection PhpPossiblePolymorphicInvocationInspection
		 */
		$ao_entity->deleted = TRUE;

		$lo_clonedEntity = clone $ao_entity;

		if ($lo_table->save($ao_entity, $lo_options->getArrayCopy() + ['skipSystemOrderBehavior' => TRUE])) {
			$lo_table->dispatchEvent('Model.afterSoftDelete', [
				'entity' => $lo_clonedEntity,
				'options' => $lo_options,
			]);

			return TRUE;
		}

		return FALSE;
	}


	/**
	 * @param \Cake\ORM\Association $ao_association
	 *
	 * @return bool
	 */
	protected function isRecursable (Association $ao_association): bool {
		$lo_table = $this->table();

		if ($ao_association->isOwningSide($lo_table) && $ao_association->getDependent() && $ao_association->getCascadeCallbacks()) {
			return TRUE;
		}

		return FALSE;
	}
}