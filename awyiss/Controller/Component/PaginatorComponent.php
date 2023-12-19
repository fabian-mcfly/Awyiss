<?php declare(strict_types=1);


namespace Awyiss\Controller\Component;


/**
 * This component is used to handle automatic model data pagination. The primary way to use this
 * component is to call the paginate() method. There is a convenience wrapper on Controller as well.
 *
 * ### Configuring pagination
 *
 * You configure pagination when calling paginate(). See that method for more details.
 *
 * @link https://book.cakephp.org/4/en/controllers/components/pagination.html
 * @mixin \Cake\Datasource\Paginator
 * @method \Awyiss\Controller\AppController getController()
 */
class PaginatorComponent extends \Cake\Controller\Component\PaginatorComponent {
	/**
	 * Default pagination aa_settings.
	 *
	 * When calling paginate() these aa_settings will be merged with the configuration
	 * you provide.
	 *
	 * - `maxLimit` - The maximum limit users can choose to view. Defaults to 100
	 * - `limit` - The initial number of items per page. Defaults to 20.
	 * - `page` - The starting page, defaults to 1.
	 * - `allowedParameters` - A list of parameters users are allowed to set using request
	 *   parameters. Modifying this list will allow users to have more influence
	 *   over pagination, be careful with what you permit.
	 *
	 * @var array
	 */
	protected $_defaultConfig = [
		'page' => 1,
		'limit' => 20,
		'maxLimit' => 100,
		'allowedParameters' => ['limit', 'sort', 'page', 'direction'],
	];


	public function paginate (object $ao_object, array $aa_settings = []): \Cake\Datasource\ResultSetInterface {
		$request = $this->_registry->getController()->getRequest();

		try {
			$la_params = $request->getParam('parts', []);
			array_walk($la_params, function(&$ax_value) {
				$ax_value = str_replace('-', '_', $ax_value);
			});

			$results = $this->_paginator->paginate($ao_object, $la_params, $aa_settings);

			$this->_setPagingParams();
		}
		catch (\Cake\Datasource\Exception\PageOutOfBoundsException $ex) {
			$this->_setPagingParams();

			throw new \Cake\Http\Exception\NotFoundException(NULL, NULL, $ex);
		}

		return $results;
	}


	/**
	 * Set paging params to request instance.
	 *
	 * @return void
	 */
	protected function _setPagingParams (): void {
		$controller = $this->getController();
		$request = $controller->getRequest();
		$paging = $this->_paginator->getPagingParams() + (array) $request->getAttribute('paging', []);

		$controller->setRequest($request->withAttribute('paging', $paging));
	}
}
