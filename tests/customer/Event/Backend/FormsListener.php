<?php declare(strict_types=1);


namespace Customer\Event\Backend;


use Awyiss\Event\Backend\FormsListener as BaseFormsListener;


/**
 * Event listeners for the Forms scope of the backend
 */
class FormsListener extends BaseFormsListener {
	/**
	 * @inheritDoc
	 */
	public function implementedEvents(): array {
		return array_merge(parent::implementedEvents(), [
			'Model.Forms.weirdEvent' => 'afterCopy',
		]);
	}
}
