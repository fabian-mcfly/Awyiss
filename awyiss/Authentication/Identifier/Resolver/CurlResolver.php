<?php declare(strict_types=1);


namespace Awyiss\Authentication\Identifier\Resolver;


class CurlResolver implements \Authentication\Identifier\Resolver\ResolverInterface {
	use \Cake\Core\InstanceConfigTrait;
	use \Cake\ORM\Locator\LocatorAwareTrait;


	public const TYPE_GET = 'GET';
	public const TYPE_POST = 'POST';
	public const ACCEPT_JSON = 'application/json';
	protected array $_defaultConfig = [
		'url' => NULL,
		'requestType' => self::TYPE_GET,
		'requestData' => [],
		'acceptType' => self::ACCEPT_JSON,
		'modifyResult' => NULL,
	];


	public function __construct (array $config = []) {
		$this->setConfig($config);
	}


	/**
	 * {@inheritDoc}
	 *
	 * @throws \Exception
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 * @noinspection PhpUndefinedFunctionInspection
	 */
	public function find (array $credentials, string $type = self::TYPE_AND) {
		$lx_url = $this->_config['url'] ?? NULL;
		if (is_callable($lx_url)) {
			$lx_url = $lx_url($credentials);
		}

		if (empty($lx_url)) {
			throw new \Exception(__('::resolver_missing_url'));
		}

		$lo_curl_handle = curl_init();

		switch ($this->_config['requestType']) {
			case self::TYPE_GET:

				break;
			case self::TYPE_POST:
				$lx_requestData = $this->_config['requestData'];
				if (is_callable($lx_requestData)) {
					$lx_requestData = $lx_requestData($credentials);
				}

				curl_setopt($lo_curl_handle, CURLOPT_POST, TRUE);
				curl_setopt($lo_curl_handle, CURLOPT_POSTFIELDS, $lx_requestData);
				break;
			default:
				throw new \Exception(__('::resolver_unknown_request_type'));
		}

		curl_setopt($lo_curl_handle, CURLOPT_HTTPHEADER, ['Accept: ' . $this->_config['acceptType']]);
		curl_setopt($lo_curl_handle, CURLOPT_URL, $lx_url);
		curl_setopt($lo_curl_handle, CURLOPT_RETURNTRANSFER, 1);
		$lx_result = curl_exec($lo_curl_handle);
		curl_close($lo_curl_handle);

		if ($this->_config['acceptType'] === self::ACCEPT_JSON) {
			$lx_result = json_decode($lx_result, TRUE);
		}

		if (is_callable($this->_config['modifyResult'])) {
			$lx_result = $this->_config['modifyResult']($lx_result);
		}

		if ($lx_result) {
			return $lx_result;
		}

		return NULL;
	}
}