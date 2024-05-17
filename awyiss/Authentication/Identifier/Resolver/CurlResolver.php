<?php declare(strict_types=1);


namespace Awyiss\Authentication\Identifier\Resolver;


use ArrayAccess;
use Authentication\Identifier\Resolver\ResolverInterface;
use Cake\Core\InstanceConfigTrait;
use Cake\ORM\Locator\LocatorAwareTrait;
use Exception;


/**
 * Resolves an identity by makeing a curl-based request to a specific remote URL
 */
class CurlResolver implements ResolverInterface {
	use InstanceConfigTrait;
	use LocatorAwareTrait;


	/**
	 * Request type 'get'
	 */
	final public const TYPE_GET = 'GET';
	/**
	 * Request type 'post'
	 */
	final public const TYPE_POST = 'POST';
	/**
	 * Accept content type 'application/json'
	 */
	final public const ACCEPT_JSON = 'application/json';
	/**
	 * Default configuration.
	 * - `acceptType` The content type to use for the request
	 * - `modifyResult` A callable that modifies the result after fetching it from the remote URL
	 * - `requestData` An array or callable of additional request data to sent to the remote URL
	 * - `requestType` The type of request. Either `CurlResolver::TYPE_GET` or `CurlResolver::TYPE_POST`
	 * - `url` The URL to call
	 *
	 * @var array
	 */
	protected array $_defaultConfig = [
		'acceptType' => self::ACCEPT_JSON,
		'modifyResult' => null,
		'requestData' => [],
		'requestType' => self::TYPE_GET,
		'url' => null,
	];


	/**
	 * @param array $config
	 */
	public function __construct(array $config = []) {
		$this->setConfig($config);
	}


	/**
	 * Create a cURL request to the URL in the config and returns the data provided by the remote URL.
	 *
	 * @param array $credentials Find conditions.
	 * @param string $type Condition type. Can be `AND` or `OR`.
	 * @return \ArrayAccess|array||null
	 * @throws Exception
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function find(array $credentials, string $type = self::TYPE_AND): array|ArrayAccess|null {
		$lx_url = $this->_config['url'] ?? null;
		if (is_callable($lx_url)) {
			$lx_url = $lx_url($credentials);
		}

		if (empty($lx_url)) {
			throw new Exception(__d('authenticator', 'curl_resolver_missing_url'));
		}

		$lo_curlHandle = curl_init();

		switch ($this->_config['requestType']) {
			case self::TYPE_GET:
				break;
			case self::TYPE_POST:
				$lx_requestData = $this->_config['requestData'];
				if (is_callable($lx_requestData)) {
					$lx_requestData = $lx_requestData($credentials);
				}

				curl_setopt($lo_curlHandle, CURLOPT_POST, true);
				curl_setopt($lo_curlHandle, CURLOPT_POSTFIELDS, $lx_requestData);
				break;
			default:
				throw new Exception(__d('authenticator', 'curl_resolver_unknown_request_type'));
		}

		curl_setopt($lo_curlHandle, CURLOPT_HTTPHEADER, ['Accept: ' . $this->_config['acceptType']]);
		curl_setopt($lo_curlHandle, CURLOPT_URL, $lx_url);
		curl_setopt($lo_curlHandle, CURLOPT_RETURNTRANSFER, 1);
		$lx_result = curl_exec($lo_curlHandle);
		curl_close($lo_curlHandle);

		if ($this->_config['acceptType'] === self::ACCEPT_JSON) {
			$lx_result = json_decode($lx_result, true);
		}

		if (is_callable($this->_config['modifyResult'])) {
			$lx_result = $this->_config['modifyResult']($lx_result);
		}

		if ($lx_result) {
			return $lx_result;
		}


		return null;
	}
}
