<?php /** @noinspection PhpDeprecationInspection */

declare(strict_types=1);


namespace Awyiss\Controller\Component;


use Awyiss\Controller\AppController;
use Cake\Datasource\Exception\PageOutOfBoundsException;
use Cake\Datasource\ResultSetInterface;
use Cake\Http\Exception\NotFoundException;


/**
 * This component is used to handle automatic model data pagination. The primary way to use this
 * component is to call the `paginate()`-method. There is a convenience wrapper on Controller as well.
 *
 * ### Configuring pagination
 *
 * You configure pagination when calling paginate(). See that method for more details.
 *
 * @link https://book.cakephp.org/4/en/controllers/components/pagination.html
 * @mixin \Cake\Datasource\Paginator
 * @method AppController getController()
 *
 * @todo add a method to retrieve the page a certain entity is on
 */
class PaginatorComponent extends \Cake\Controller\Component\PaginatorComponent {
	/**
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function initialize (array $aa_config): void {
		$this->setConfig($aa_config + $this->getController()->paginate + [
			'page' => 1,
			'limit' => 5,
			'maxLimit' => 99999,
			'allowedParameters' => ['limit', 'sort', 'page', 'direction'],
		], NULL, FALSE);
	}


	/**
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function paginate (object $ao_object, array $aa_settings = []): ResultSetInterface {
		$lo_request = $this->getController()->getRequest();

		try {
			$la_params = $lo_request->getParam('parts', []);
			array_walk($la_params, function(&$ax_value) {
				$ax_value = str_replace('-', '_', $ax_value);
			});

			$lo_results = $this->_paginator->paginate($ao_object, $la_params, $aa_settings);

			$this->_setPagingParams();
		}
		catch (PageOutOfBoundsException $ex) {
			//try {
			//	$lo_results = $this->_paginator->paginate($ao_object, ['page' => 1] + $la_params, $aa_settings);
			//
			//	$this->_setPagingParams();
			//}
			//catch (\Cake\Datasource\Exception\PageOutOfBoundsException $ex) {
			$this->_setPagingParams();

			throw new NotFoundException(NULL, NULL, $ex);
			//}
		}

		return $lo_results;
	}
}
