<?php declare(strict_types=1);


namespace Awyiss\Authentication\Identifier;


use Authentication\Identifier\AbstractIdentifier;
use Authentication\Identifier\Resolver\ResolverAwareTrait;


class ProxyIdentifier extends AbstractIdentifier {
	use ResolverAwareTrait;


	/**
	 * @var array
	 */
	protected $_defaultConfig = [
		'fields' => [
			self::CREDENTIAL_USERNAME => 'username',
			self::CREDENTIAL_PASSWORD => 'password',

		],
		'remoteResolver' => NULL,
		'localResolver' => 'Authentication.Orm',
	];


	/**
	 * {@inheritDoc}
	 */
	public function identify (array $credentials): \ArrayAccess|array|null {
		$this->setConfig('resolver', $this->getConfig('remoteResolver'));

		return $this->getResolver()->find($credentials);
	}


	public function reidentify ($credentials): \ArrayAccess|array|null {
		$this->setConfig('resolver', $this->getConfig('localResolver'));

		return $this->getResolver()->find($credentials);
	}
}