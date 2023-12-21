<?php declare(strict_types=1);


namespace Awyiss\Authentication\Identifier;


use ArrayAccess;
use Authentication\Identifier\AbstractIdentifier;
use Authentication\Identifier\Resolver\ResolverAwareTrait;


/**
 * An identifier that uses both a remote and a local resolver to retreive resp. store data
 */
class ProxyIdentifier extends AbstractIdentifier {
	use ResolverAwareTrait;


	/**
	 * @inheritDoc
	 */
	protected array $_defaultConfig = [
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
	 * @param array $aa_credentials Authentication credentials
	 * @return ArrayAccess|array|null
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function identify(array $aa_credentials): ArrayAccess|array|null {
		$this->setConfig('resolver', $this->getConfig('remoteResolver'));


		return $this->getResolver()->find($aa_credentials);
	}


	/**
	 * Identifies a user or service by the passed credentials using the `localResolver` setting.
	 *
	 * @param array $aa_credentials Authentication credentials
	 * @return ArrayAccess|array|null
	 * @noinspection PhpParameterNameChangedDuringInheritanceInspection
	 */
	public function reidentify(array $aa_credentials): ArrayAccess|array|null {
		$this->setConfig('resolver', $this->getConfig('localResolver'));

		dump($this->getConfig('localResolver'));


		return $this->getResolver()->find($aa_credentials);
	}
}
