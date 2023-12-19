<?php declare(strict_types=1);


namespace Awyiss\Controller\Component;


use Cake\Controller\Component;
use Cake\Event\Event;
use Cake\Event\EventInterface;
use Cake\Http\Response;


/**
 * @method \Awyiss\Controller\AppController getController()
 */
class EventTriggerComponent extends Component {
	protected $_defaultConfig = [
		'enabled' => TRUE,
	];


	public function enable () {
		$this->setConfig('enabled', TRUE);
	}


	public function disable () {
		$this->setConfig('enabled', FALSE);
	}


	/**
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function initialize (array $aa_config): void {
		$this->dispatchEvent('initialize', NULL, $aa_config);
	}


	/**
	 * Is called before the controller’s beforeFilter method, but after the controller’s initialize() method.
	 *
	 * @noinspection PhpUnused
	 */
	public function beforeFilter (EventInterface $ao_event) {
		$this->dispatchEvent('beforeFilter', $ao_event);
	}


	/**
	 * Is called after the controller’s beforeFilter method but before the controller executes the current action handler.
	 */
	public function startup (EventInterface $ao_event) {
		$this->dispatchEvent('startup', $ao_event);
	}


	/**
	 * Is called after the controller executes the requested action’s logic, but before the controller renders views and layout.
	 */
	public function beforeRender (EventInterface $ao_event) {
		$this->dispatchEvent('beforeRender', $ao_event);
	}


	/**
	 * Is called before output is sent to the browser.
	 *
	 * @noinspection PhpUnused
	 */
	public function afterFilter (EventInterface $ao_event) {
		$this->dispatchEvent('afterFilter', $ao_event);
	}


	/**
	 * Is invoked when the controller’s redirect method is called but before any further action.
	 * If this method returns false the controller will not continue on to redirect the request.
	 * The $lx_url, and $ao_response parameters allow you to inspect and modify the location or any other headers in the response.
	 *
	 * @noinspection PhpUnused
	 */
	public function beforeRedirect (EventInterface $ao_event, mixed $lx_url, Response $ao_response) {
		$this->dispatchEvent('beforeRedirect', $ao_event, $lx_url, $ao_response);
	}


	protected function dispatchEvent (string $as_name, ?EventInterface $ao_event, ...$aa_arguments) {
		if ( ! $this->getConfig('enabled')) {
			return;
		}

		$lo_controller = $this->getController();
		$ls_controllerName = \Cake\Utility\Inflector::singularize($lo_controller->getName());

		$lo_event = new Event('Controller.' . $ls_controllerName . '.' . $as_name, $lo_controller, ($ao_event?->getData() ?? []) + $aa_arguments);
		$lo_controller->getEventManager()->dispatch($lo_event);

		if ($lo_event->isStopped()) {
			$ao_event->stopPropagation();
			$ao_event->setResult($lo_event->getResult());
		}
	}
}