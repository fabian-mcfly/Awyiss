<?php declare(strict_types=1);


namespace Awyiss\Model\Behavior;


use Cake\Datasource\EntityInterface;
use Cake\Event\Event;
use Cake\Event\EventInterface;
use Cake\ORM\Behavior;


class AuditBehavior extends Behavior {
	use \Cake\ORM\Locator\LocatorAwareTrait;


	/**
	 * Default configuration
	 *
	 * These are merged with user-provided configuration when the behavior is used.
	 *
	 * @var array
	 */
	protected $_defaultConfig = [
		'enabled' => TRUE,
		'ignoredColumns' => ['created_on', 'created_by', 'changed_on', 'changed_by', 'deleted_on', 'deleted_by'],
		'skipAuditBehavior' => FALSE,
	];


	public function afterSave (EventInterface $ao_event, EntityInterface $ao_entity, \ArrayObject $ao_options): bool {
		if ( ! $this->getConfig('enabled') || $ao_entity->isNew()) {
			return TRUE;
		}

		$la_options = \Cake\Utility\Hash::merge($this->getConfig(), $ao_options);

		if ($la_options['skipAuditBehavior'] === TRUE) {
			return TRUE;
		}

		//Existing entities make their way into the audit database with the old and new data
		$la_oldData = $ao_entity->getOriginalValues();
		$la_newData = $ao_entity->extract(array_keys($la_oldData));
		$la_diff = \Cake\Utility\Hash::diff($la_oldData, $la_newData);
		$la_diff = array_diff_key($la_diff, array_flip($this->getConfig('ignoredColumns')));

		/*$li_createdBy = NULL;
		$lo_identity = \Cake\Routing\Router::getRequest()->getAttribute('identity');
		if ($lo_identity) {
			$li_createdBy = $lo_identity->id;
			if ($lo_identity instanceof \Awyiss\Model\Entity\UsersExternal) {
				$li_createdBy *= -1;
			}
		}*/

		$la_auditData = [
			'type' => ! empty($ao_entity->deleted) ? 'd' : 'u',
			'model' => '\\' . $ao_event->getSubject()::class,
			'parent_id' => $ao_entity->get('id'),
			'data_old' => $la_oldData,
			'data_new' => $la_newData,
			'diff' => $la_diff,
			'created_on' => new \Cake\I18n\FrozenTime(),
			//'created_by' => $li_createdBy,
		];

		$lo_auditModel = $this->getTableLocator()->get('Audit');
		$lo_audit = $lo_auditModel->newEntity($la_auditData);

		$lo_event = new Event('Behavior.Audit.beforeSave', $this->table(), [
			'entity' => $ao_entity,
			'identityColumn' => 'created_by',
		]);
		$this->table()->getEventManager()->dispatch($lo_event);

		$lo_auditModel->save($lo_audit);

		return TRUE;
	}
}