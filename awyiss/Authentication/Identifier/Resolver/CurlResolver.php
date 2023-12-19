<?php declare(strict_types=1);


namespace Awyiss\Authentication\Identifier\Resolver;


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
		'modifyResult' => NULL,
		'requestData' => [],
		'requestType' => self::TYPE_GET,
		'url' => NULL,
	];


	final public const TYPE_GET = 'GET';
	final public const TYPE_POST = 'POST';
	final public const ACCEPT_JSON = 'application/json';


	/**
	 * @param array $aa_config
	 */
	public function __construct (array $aa_config = []) {
		$this->setConfig($aa_config);
	}


	/**
	 * Create a cURL request to the URL in the config and returns the data provided by the remote URL.
	 *
	 * @param array $aa_credentials Find conditions.
	 * @param string $as_type Condition type. Can be `AND` or `OR`.
	 *
	 * @return mixed
	 * @throws \Exception
	 *
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function find (array $aa_credentials, string $as_type = self::TYPE_AND): mixed {
		$lx_url = $this->_config['url'] ?? NULL;
		if (is_callable($lx_url)) {
			$lx_url = $lx_url($aa_credentials);
		}

		if (empty($lx_url)) {
			throw new Exception(__('::resolver_missing_url'));
		}

		$lo_curl_handle = curl_init();

		switch ($this->_config['requestType']) {
			case self::TYPE_GET:

				break;
			case self::TYPE_POST:
				$lx_requestData = $this->_config['requestData'];
				if (is_callable($lx_requestData)) {
					$lx_requestData = $lx_requestData($aa_credentials);
				}

				curl_setopt($lo_curl_handle, CURLOPT_POST, TRUE);
				curl_setopt($lo_curl_handle, CURLOPT_POSTFIELDS, $lx_requestData);
				break;
			default:
				throw new Exception(__('::resolver_unknown_request_type'));
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