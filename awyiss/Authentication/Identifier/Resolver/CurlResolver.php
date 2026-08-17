<?php declare(strict_types=1);


namespace Awyiss\Authentication\Identifier\Resolver;


use ArrayAccess;
use Authentication\Identifier\Resolver\ResolverInterface;
use Cake\Core\InstanceConfigTrait;
use Cake\ORM\Locator\LocatorAwareTrait;
use Exception;


/**
 * Resolves an identity by making a curl-based request to a specific remote URL
 */
class CurlResolver implements ResolverInterface {
	use InstanceConfigTrait;
	use LocatorAwareTrait;


	/**
	 * Accept content type 'application/json'
	 */
	final public const string ACCEPT_JSON = 'application/json';
	/**
	 * Request type 'get'
	 */
	final public const string TYPE_GET = 'GET';
	/**
	 * Request type 'post'
	 */
	final public const string TYPE_POST = 'POST';


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
	protected array $_defaultConfig = [ // phpcs:ignore
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
	 * @return \ArrayAccess|array|null
	 * @throws Exception
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function find(array $credentials, string $type = self::TYPE_AND): array|ArrayAccess|null {
		$url = $this->_config['url'] ?? null;
		if (is_callable($url)) {
			$url = $url($credentials);
		}

		if (empty($url)) {
			throw new Exception(__d('Authenticator', 'curl_resolver_missing_url'));
		}

		$curlHandle = curl_init();

		switch ($this->_config['requestType']) {
			case self::TYPE_GET:
				break;
			case self::TYPE_POST:
				$requestData = $this->_config['requestData'];
				if (is_callable($requestData)) {
					$requestData = $requestData($credentials);
				}

				curl_setopt($curlHandle, CURLOPT_POST, true);
				curl_setopt($curlHandle, CURLOPT_POSTFIELDS, $requestData);
				break;
			default:
				throw new Exception(__d('Authenticator', 'curl_resolver_unknown_request_type'));
		}

		curl_setopt($curlHandle, CURLOPT_HTTPHEADER, ['Accept: ' . $this->_config['acceptType']]);
		curl_setopt($curlHandle, CURLOPT_URL, $url);
		curl_setopt($curlHandle, CURLOPT_RETURNTRANSFER, 1);
		$result = curl_exec($curlHandle);
		curl_close($curlHandle);

		if ($this->_config['acceptType'] === self::ACCEPT_JSON) {
			$result = json_decode($result, true);
		}

		if (is_callable($this->_config['modifyResult'])) {
			$result = $this->_config['modifyResult']($result);
		}

		return $result ?: null;
	}
}
