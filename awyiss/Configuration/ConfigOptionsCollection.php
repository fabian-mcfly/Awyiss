<?php declare(strict_types=1);


namespace Awyiss\Configuration;


use ArrayIterator;
use Awyiss\Utility\Inflector;
use RuntimeException;


/**
 * A class to collect multiple ConfigOptions instances or nested ConfigOptionsCollection instances
 */
class ConfigOptionsCollection extends ArrayIterator {
	/**
	 * @var string
	 */
	protected string $identifier;


	/**
	 * Construct a new ConfigOptionsCollection
	 *
	 * @param string|null $identifier
	 * @noinspection PhpMissingParentConstructorInspection
	 */
	public function __construct(?string $identifier = null) {
		if ($identifier) {
			$this->identifier = ConfigOptionsProvider::sanitizeIdentifier($identifier);
		}
	}


	/**
	 * Adds a ConfigOption or a set of elements, containing nested ConfigOptions or ConfigOptionsCollection to this collection
	 *
	 * @param ConfigOption|array<int|string, ConfigOptionsCollection|ConfigOption|array> $configOption
	 * @return $this
	 */
	public function add(array|ConfigOption $configOption): static {
		/*
		 * If the provided value for `$configOption` is an instance of `ConfigOption`,
		 * add it to the current collection
		 */
		if ($configOption instanceof ConfigOption) {
			$ls_identifier = $configOption->getIdentifier();

			//We cannot have the same identifier more than once inside this collection
			if ($this->offsetExists($ls_identifier)) {
				throw new RuntimeException(sprintf('The identifier `%s` is already in use.', $ls_identifier));
			}

			$this->offsetSet($ls_identifier, $configOption);


			return $this;
		}

		//Traverse the provided array
		foreach ($configOption as $lx_key => $lx_configOption) {
			//If the key is a string, add a new sub-collection with that given identifier, containing everything in $lx_configOption
			if (is_string($lx_key)) {
				$lo_collection = new ConfigOptionsCollection($lx_key);
				$lo_collection->add($lx_configOption);

				$this->addCollection($lo_collection);

				continue;
			}

			//If the current value is an instance of ConfigOptionsCollection, add it as a new sub-collection
			if ($lx_configOption instanceof ConfigOptionsCollection) {
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
				$this->add(new ConfigOption(...$lx_configOption));
			}
		}


		return $this;
	}


	/**
	 * Adds the given ConfigOptionsCollection as a sub-collection to the current one.
	 * If the identifier of the ConfigOptionsCollection already exists in the current one, a `RuntimeException` is thrown.
	 *
	 * @param ConfigOptionsCollection $configOptionsCollection
	 * @return $this
	 * @throws RuntimeException
	 */
	public function addCollection(ConfigOptionsCollection $configOptionsCollection): static {
		$ls_identifier = $configOptionsCollection->getIdentifier();

		if ($this->offsetExists($ls_identifier)) {
			$lx_offset = $this->offsetGet($ls_identifier);
			if ($lx_offset instanceof ConfigOptionsCollection) {
				foreach ($configOptionsCollection->getArrayCopy() as $lx_configOptions) {
					if ($lx_configOptions instanceof ConfigOptionsCollection) {
						$lx_offset->addCollection($lx_configOptions);
					}
					else {
						$lx_offset->add($lx_configOptions);
					}
				}


				return $this;
			}

			throw new RuntimeException(sprintf('The identifier `%s` is already in use.', $ls_identifier));
		}

		$this->offsetSet($ls_identifier, $configOptionsCollection);


		return $this;
	}


	/**
	 * @return string
	 */
	public function getIdentifier(): string {
		return $this->identifier;
	}


	/**
	 * Get all ConfigOptions from this collection and all nested collections
	 *
	 * All provided path parts will be concatenated with a dot to form the final key
	 *
	 * @param string ...$pathParts
	 * @return array
	 */
	public function getConfigOptions(string ...$pathParts): array {
		$la_configOptions = [];

		foreach ($this as $lo_configOption) {
			if ($lo_configOption instanceof ConfigOptionsCollection) {
				$la_pathParts = $pathParts;
				$la_pathParts[] = Inflector::variable($lo_configOption->getIdentifier());
				$la_configOptions += $lo_configOption->getConfigOptions(...$la_pathParts);
			}
			else {
				$ls_key = '';
				if (!empty($pathParts)) {
					$ls_key .= implode('.', $pathParts) . '.';
				}
				$ls_key .= Inflector::variable($lo_configOption->getIdentifier());

				$la_configOptions[ $ls_key ] = $lo_configOption;
			}
		}


		return $la_configOptions;
	}


	/**
	 * @return array
	 */
	public function toArray(): array {
		$la_result = [];
		foreach ($this as $ls_key => $lx_item) {
			$la_result[ $ls_key ] = $lx_item instanceof static ? $lx_item->toArray() : $lx_item;
		}


		return $la_result;
	}
}
