<?php declare(strict_types=1);


namespace Awyiss\Authentication\Identifier;


use ArrayAccess;
use Authentication\Identifier\AbstractIdentifier;
use Authentication\Identifier\Resolver\ResolverAwareTrait;


/**
 * An identifier that uses both a remote and a local resolver to retrieve resp. store data
 */
class ProxyIdentifier extends AbstractIdentifier {
	use ResolverAwareTrait;


	/**
	 * Field name for the username credential
	 *
	 * @var string
	 */
	public const string CREDENTIAL_USERNAME = 'username';
	/**
	 * Field name for the password credential
	 *
	 * @var string
	 */
	public const string CREDENTIAL_PASSWORD = 'password';


	/**
	 * @inheritDoc
	 */
	protected array $_defaultConfig = [ // phpcs:ignore
		'fields' => [
			self::CREDENTIAL_USERNAME => 'username',
			self::CREDENTIAL_PASSWORD => 'password',

		],
		'remoteResolver' => null,
		'localResolver' => 'Authentication.Orm',
	];


	/**
	 * Identifies a user or service by the passed credentials using the `remoteResolver` setting.
	 *
	 * @param array $credentials Authentication credentials
	 * @return ArrayAccess|array|null
	 */
	public function identify(array $credentials): ArrayAccess|array|null {
		$this->setConfig('resolver', $this->getConfig('remoteResolver'));


		return $this->getResolver()->find($credentials);
	}


	/**
	 * Identifies a user or service by the passed credentials using the `localResolver` setting.
	 *
	 * @param array $credentials Authentication credentials
	 * @return ArrayAccess|array|null
	 */
	public function reidentify(array $credentials): ArrayAccess|array|null {
		$this->setConfig('resolver', $this->getConfig('localResolver'));


		return $this->getResolver()->find($credentials);
	}
}
