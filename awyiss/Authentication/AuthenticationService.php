<?php


namespace Awyiss\Authentication;


use Awyiss\Authentication\Identifier\IdentifierCollection;
use Psr\Http\Message\ServerRequestInterface;


class AuthenticationService extends \Authentication\AuthenticationService {
	/**
	 * {@inheritDoc}
	 *
	 * @uses \Awyiss\Authentication\Identifier\IdentifierCollection
	 */
	public function identifiers (): IdentifierCollection {
		if ($this->_identifiers === NULL) {
			$this->_identifiers = new IdentifierCollection($this->getConfig('identifiers'));
		}

		return $this->_identifiers;
	}


	/**
	 * {@inheritDoc}
	 */
	public function getLoginRedirect (ServerRequestInterface $ao_request): ?string {
		$redirectParam = $this->getConfig('queryParam');
		$params = $ao_request->getQueryParams();
		if (empty($redirectParam) || ! isset($params[ $redirectParam ]) || strlen($params[ $redirectParam ]) === 0) {
			return NULL;
		}

		$parsed = parse_url($params[ $redirectParam ]);
		if ($parsed === FALSE) {
			return $params[ $redirectParam ];
		}
		if ( ! empty($parsed['host']) || ! empty($parsed['scheme'])) {
			return NULL;
		}
		$parsed += ['path' => '/', 'query' => ''];
		/** @psalm-suppress PossiblyUndefinedArrayOffset */
		if (strlen($parsed['path']) && $parsed['path'][0] !== '/') {
			$parsed['path'] = "/{$parsed['path']}";
		}
		/** @psalm-suppress PossiblyUndefinedArrayOffset */
		if ($parsed['query']) {
			$parsed['query'] = "?{$parsed['query']}";
		}

		return $parsed['path'] . $parsed['query'];
	}
}