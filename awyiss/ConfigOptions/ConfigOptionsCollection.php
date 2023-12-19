<?php declare(strict_types=1);


namespace Awyiss\ConfigOptions;


class ConfigOptionsCollection extends \ArrayObject {
	protected ?ConfigOptionsCollection $parentConfigOptionsCollections = NULL;
	protected string $name;


	/**
	 * @noinspection PhpMissingParentConstructorInspection
	 */
	public function __construct (string $as_name) {
		$this->name = $as_name;
	}


	public function add (array|ConfigOption $ax_configOptions): self {
		if ($ax_configOptions instanceof ConfigOption) {
			$ls_name = $ax_configOptions->getName();

			if ($this->offsetExists($ls_name)) {
				throw new \RuntimeException(sprintf('The name `%s` is already in use.', $ls_name));
			}

			$this->offsetSet($ls_name, $ax_configOptions);

			return $this;
		}


		foreach ($ax_configOptions as $lx_key => $lx_configOption) {
			if (is_string($lx_key)) {
				$lo_collection = new ConfigOptionsCollection($lx_key);
				$lo_collection->add($lx_configOption);

				$this->addCollection($lo_collection);

				continue;
			}

			if ($lx_configOption instanceof ConfigOptionsCollection) {
				$this->addCollection($lx_configOption);
			}
			elseif ($lx_configOption instanceof ConfigOption) {
				$this->add($lx_configOption);
			}
			else {
				$this->add(new ConfigOption($lx_configOption));
			}
		}

		return $this;
	}


	public function addCollection (ConfigOptionsCollection $ao_configOptionsCollection): self {
		$ls_name = $ao_configOptionsCollection->getName();

		if ($this->offsetExists($ls_name)) {
			throw new \RuntimeException(sprintf('The name `%s` is already in use.', $ls_name));
		}

		$ao_configOptionsCollection->setParentCollection($this);

		$this->offsetSet($ls_name, $ao_configOptionsCollection);

		return $this;
	}


	public function setParentCollection (ConfigOptionsCollection $ao_configOptionsCollection): self {
		$this->parentConfigOptionsCollections = $ao_configOptionsCollection;

		return $this;
	}


	public function getName (): string {
		return $this->name;
	}
}