<?php declare(strict_types=1);


namespace Awyiss\Configuration;


use ArrayObject;
use Cake\Utility\Inflector;
use RuntimeException;


/**
 * A class to collect multiple ConfigOptions instances or nested ConfigOptionsCollection instances
 */
class ConfigOptionCollection extends ArrayObject {
	/**
	 * @var NULL|\Awyiss\Configuration\ConfigOptionCollection
	 */
	protected ?ConfigOptionCollection $parentConfigOptionsCollections = NULL;
	/**
	 * @var string
	 */
	protected string $name;


	/**
	 * Construct a new ConfigOptionsCollection
	 *
	 * @param string $as_name
	 *
	 * @noinspection PhpMissingParentConstructorInspection
	 */
	public function __construct (string $as_name) {
		$this->name = Inflector::underscore($as_name);
	}


	/**
	 * Adds a ConfigOption or a set of elements, containing nested ConfigOptions or ConfigOptionsCollection to this collection
	 *
	 * @param array<int|string, \Awyiss\Configuration\ConfigOptionCollection|\Awyiss\Configuration\ConfigOption|array>|\Awyiss\Configuration\ConfigOption $ax_configOption
	 *
	 * @return $this
	 */
	public function add (array|ConfigOption $ax_configOption): static {
		/*
		 * If the provided value for `$ax_configOption` is an instance of `ConfigOption`,
		 * add it to the current collection
		 */
		if ($ax_configOption instanceof ConfigOption) {
			$ls_name = $ax_configOption->getName();

			//We cannot have the same name more than once inside this collection
			if ($this->offsetExists($ls_name)) {
				throw new RuntimeException(sprintf('The name `%s` is already in use.', $ls_name));
			}

			$this->offsetSet($ls_name, $ax_configOption);

			return $this;
		}

		//Traverse the provided array
		foreach ($ax_configOption as $lx_key => $lx_configOption) {
			//If the key is a string, add a new sub-collection with that given name, containing everything in $lx_configOption
			if (is_string($lx_key)) {
				$lo_collection = new ConfigOptionCollection($lx_key);
				$lo_collection->add($lx_configOption);

				$this->addCollection($lo_collection);

				continue;
			}

			//If the current value is an instance of ConfigOptionsCollection, add it as a new sub-collection
			if ($lx_configOption instanceof ConfigOptionCollection) {
				$this->addCollection($lx_configOption);
			}
			//If the current value is an instance of ConfigOption, add it as is
			elseif ($lx_configOption instanceof ConfigOption) {
				$this->add($lx_configOption);
			}
			/*
			 * Otherwise the current value is most likely an array. So we try creating an instance of ConfigOption with it
			 * and then add that instance to the collection.
			 * */
			else {
				$this->add(new ConfigOption($lx_configOption));
			}
		}

		return $this;
	}


	/**
	 * Adds the given ConfigOptionsCollection as a sub-collection to the current one.
	 * If the name of the ConfigOptionsCollection already exists in the current one, a `RuntimeException` is thrown.
	 *
	 * @param \Awyiss\Configuration\ConfigOptionCollection $ao_configOptionsCollection
	 *
	 * @return $this
	 *
	 * @throws \RuntimeException
	 */
	public function addCollection (ConfigOptionCollection $ao_configOptionsCollection): static {
		$ls_name = $ao_configOptionsCollection->getName();

		if ($this->offsetExists($ls_name)) {
			throw new RuntimeException(sprintf('The name `%s` is already in use.', $ls_name));
		}

		$ao_configOptionsCollection->setParentCollection($this);

		$this->offsetSet($ls_name, $ao_configOptionsCollection);

		return $this;
	}


	/**
	 * When adding a sub-collection, set the current collection as a parent in the sub-collection.
	 *
	 * This allows not only traversing from the uppermost level into the depths of all collections, but also
	 * from the deepest one. Useful to the surface to retreive all names of the parent collections to form an identifier
	 *
	 * @param \Awyiss\Configuration\ConfigOptionCollection $ao_configOptionsCollection
	 *
	 * @return $this
	 */
	public function setParentCollection (ConfigOptionCollection $ao_configOptionsCollection): static {
		$this->parentConfigOptionsCollections = $ao_configOptionsCollection;

		return $this;
	}


	/**
	 * @return string
	 */
	public function getName (): string {
		return $this->name;
	}
}