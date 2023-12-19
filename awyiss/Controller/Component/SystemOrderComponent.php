<?php declare(strict_types=1);


namespace Awyiss\Controller\Component;


use Cake\Controller\Component;
use Cake\Datasource\EntityInterface;
use Cake\ORM\ResultSet;
use Cake\Utility\Inflector;


/**
 * @method \Awyiss\Controller\AppController getController()
 */
class SystemOrderComponent extends Component {
	protected $_defaultConfig = [
		'autoload' => ['add', 'edit'], //can be a boolean value or an array containing all action names for which the records should get autoloaded
		'records' => NULL,
		'tableName' => NULL,
	];


	public function startup () {
		$ls_tableName = $this->getConfig('tableName');
		if ( ! $ls_tableName) {
			$ls_tableName = $this->getController()->defaultTable ?? $this->getController()->getName();
			$this->setConfig('tableName', $ls_tableName);
		}
	}


	public function beforeRender (): void {
		$lo_controller = $this->getController();
		$lo_view = $lo_controller->viewBuilder();

		if (!$this->getConfig('tableName')) {
			return;
		}

		/** @var \Cake\ORM\Table $lo_table */
		$lo_table = $lo_controller->{$this->getConfig('tableName')} ?? NULL;

		if (!$lo_table) {
			return;
		}

		if ($lo_table->hasBehavior('SystemOrder') && $lo_table->getBehavior('SystemOrder')->getConfig('enabled')) {
			$ls_action = $lo_controller->getRequest()->getParam('action');
			$lx_autoload = $this->getConfig('autoload');
			if ($lx_autoload === TRUE || (is_array($lx_autoload) && in_array($ls_action, $lx_autoload))) {
				$ls_varName = 'ao_' . (Inflector::variable(Inflector::singularize($lo_controller->getName())));
				if ($lo_entity = $lo_view->getVar($ls_varName)) {
					$this->getRecords($lo_entity);
					$this->ensurePossibleSystemOrder($lo_entity);
				}
			}
		}

		if ( ! $lo_view->getVar('ao_systemOrderRecords') && ($lo_records = $this->getConfig('records'))) {
			$lo_view->setVar('ao_systemOrderRecords', $lo_records);
		}
	}


	public function getRecords (EntityInterface $ao_entity): ?ResultSet {
		$lo_controller = $this->getController();
		/** @var \Cake\ORM\Table $lo_table */
		$lo_table = $lo_controller->{$this->getConfig('tableName')};

		if (!$lo_table) {
			return NULL;
		}

		if ( ! $lo_table->hasBehavior('SystemOrder') || !$lo_table->getBehavior('SystemOrder')->getConfig('enabled')) {
			return NULL;
		}

		/** @noinspection PhpUndefinedMethodInspection */
		$lo_systemOrderQuery = $lo_table->addSystemOrderQueryConditions(NULL, $ao_entity);
		$lo_systemOrderRecords = $lo_systemOrderQuery->all();

		$this->setConfig('records', $lo_systemOrderRecords);

		return $lo_systemOrderRecords;
	}


	/** @noinspection PhpPossiblePolymorphicInvocationInspection */
	public function ensurePossibleSystemOrder (EntityInterface $ao_entity, ?ResultSet $ao_records = NULL): void {
		$lo_records = $ao_records ?? $this->getConfig('records') ?? $this->getRecords($ao_entity);

		$li_highestSystemOrder = $lo_records->max('system_order')?->system_order ?? 1;
		if (is_null($ao_entity->system_order) || $ao_entity->system_order > $li_highestSystemOrder || $ao_entity->system_order === 0) {
			$ao_entity->system_order = $li_highestSystemOrder + 1;
		}
	}
}