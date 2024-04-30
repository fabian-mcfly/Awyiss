<?php declare(strict_types=1);


namespace Awyiss\Configuration;


use Awyiss\Awyiss;
use Awyiss\Configuration\ConfigOptions\Trait\TableFieldsTrait;


/**
 * Provides all configuration options for the generic datatables scope
 */
abstract class AbstractGenericConfigOptions extends AbstractConfigOptions {
	use TableFieldsTrait;


	/**
	 * @var string|null
	 */
	protected ?string $dynamicScope = null;


	/**
	 * Set the scope and initialize the config options
	 *
	 * @noinspection PhpMissingParentConstructorInspection
	 */
	public function __construct(string $as_scope) {
		$this->dynamicScope = ConfigOptionsProvider::sanitizeScope($as_scope);

		foreach (Awyiss::getRealms() as $ls_realm) {
			$this->realms[ $ls_realm ] = new ConfigOptionCollection();
		}

		$this->initializeConfigOptions();
	}


	/**
	 * @return string|null
	 */
	public function getDynamicScope(): ?string {
		return $this->dynamicScope;
	}


	/**
	 * @param string|null $as_pageRole
	 * @return $this
	 */
	public function setDynamicScope(?string $as_pageRole): static {
		$this->dynamicScope = $as_pageRole;


		return $this;
	}
}
