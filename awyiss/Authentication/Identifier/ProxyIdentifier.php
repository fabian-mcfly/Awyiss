<?php

declare(strict_types=1);


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
		'resolver' => NULL,
		'localResolver' => 'Authentication.Orm',
	];


	/**
	 * {@inheritDoc}
	 */
	public function identify (array $credentials) {
		$identity = $this->getResolver()->find($credentials);

		return $identity;
	}


	public function reidentify ($credentials) {
		$this->setConfig('resolver', $this->getConfig('localResolver'));
		$identity = $this->getResolver()->find($credentials);

		return $identity;
	}
}