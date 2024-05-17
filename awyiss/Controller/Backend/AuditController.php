<?php declare(strict_types=1);


namespace Awyiss\Controller\Backend;


use Awyiss\Annotation\NoDirectAccess;
use Awyiss\Controller\BackendController as Controller;
use Awyiss\Routing\Router;
use Cake\Http\Exception\RedirectException;
use Cake\ORM\Query\SelectQuery;
use Cake\Utility\Inflector;


/**
 * Audit Controller
 *
 * @property \Awyiss\Model\Table\AuditTable $Audit
 */
class AuditController extends Controller {
	/**
	 * @inheritDoc
	 */
	#[NoDirectAccess]
	public function getOverviewQuery(): ?SelectQuery {
		return $this->Audit->find()->where($this->getOverviewWhere());
	}


	/**
	 * This method handles the info action for the AuditController. It fetches the record
	 * based on the provided id and scope from the request parameters. If the id or scope
	 * is not provided, it redirects to the Dashboard index.
	 *
	 * If the request is an AJAX request, it returns a JSON response with the audit information
	 * of the record. The audit information includes the createdBy, createdOn, changedBy,
	 * changedOn, created, and changed fields. If the createdBy or changedBy fields are empty,
	 * they are set to 'System' if the corresponding createdBy or changedBy fields are empty,
	 * otherwise they are set to 'Unknown'.
	 *
	 * @return void
	 * @throws \Cake\Http\Exception\RedirectException If the id or scope is not provided in the request parameters
	 */
	#[NoDirectAccess]
	public function info(): void {
		$la_parts = $this->request->getParam('parts');
		$li_id = $la_parts['id'] ?? null;
		$ls_scope = $la_parts['scope'] ?? null;

		if ($li_id === null || $ls_scope === null) {
			throw new RedirectException(Router::url(['controller' => 'Dashboard', 'action' => 'index']));
		}

		/** @noinspection PhpPossiblePolymorphicInvocationInspection */
		$lo_record = $this->fetchTable(Inflector::camelize($ls_scope))->findById($li_id)->find('withAuditUsers')->first($li_id);
		if (!$lo_record) {
			throw new RedirectException(Router::url(['controller' => 'Dashboard', 'action' => 'index']));
		}

		// If the request is an AJAX request, return a JSON response
		if ($this->request->accepts('application/json')) {
			$this->viewBuilder()->setOption('serialize', ['createdBy', 'createdOn', 'changedBy', 'changedOn', 'created', 'changed']);

			// Get the createdBy and changedBy users
			$ls_createdByUser = $lo_record->get('createdByUser');
			$ls_changedByUser = $lo_record->get('changedByUser');

			// If createdBy is empty, set it to 'System' if createdBy is empty, otherwise to "Unknown"
			if (empty($ls_createdByUser)) {
				$ls_createdByUser = $lo_record->get('createdBy') ? __('user_unknown') : __('user_system');
			}

			// If changedBy is empty, set it to 'System' if createdBy is empty, otherwise to "Unknown"
			if (empty($ls_changedByUser)) {
				$ls_changedByUser = $lo_record->get('changedBy') ? __('user_unknown') : __('user_system');
			}

			// Set the data to be serialized
			$this->set([
				'createdBy' => $ls_createdByUser,
				'createdOn' => $lo_record->get('createdOn')?->nice(),
				'changedBy' => $ls_changedByUser,
				'changedOn' => $lo_record->get('changedOn')?->nice(),
				'created' => __('created_info_label'),
				'changed' => __('changed_info_label'),
			]);

			// Set the view class to JSON
			$this->viewBuilder()->setClassName('Json');


			return;
		}

		$this->set([
			'record' => $lo_record,
			'scope' => $ls_scope,
		]);
	}
}
