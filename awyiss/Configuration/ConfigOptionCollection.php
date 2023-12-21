<?php declare(strict_types=1);


namespace Awyiss\Configuration;


use ArrayIterator;
use Cake\Utility\Inflector;
use RuntimeException;


/**
 * A class to collect multiple ConfigOptions instances or nested ConfigOptionsCollection instances
 */
class ConfigOptionCollection extends ArrayIterator {
	/**
	 * @var string
	 */
	protected string $identifier;


	/**
	 * Construct a new ConfigOptionsCollection
	 *
	 * @param string|null $as_identifier
	 * @noinspection PhpMissingParentConstructorInspection
	 */
	public function __construct(?string $as_identifier = null) {
		if ($as_identifier) {
			$this->identifier = ConfigOptionsProvider::sanitizeIdentifier($as_identifier);
		}
	}


	/**
	 * Adds a ConfigOption or a set of elements, containing nested ConfigOptions or ConfigOptionsCollection to this collection
	 *
	 * @param ConfigOption|array<int|string, ConfigOptionCollection|ConfigOption|array> $ax_configOption
	 * @return $this
	 */
	public function add(array|ConfigOption $ax_configOption): static {
		/*
		 * If the provided value for `$ax_configOption` is an instance of `ConfigOption`,
		 * add it to the current collection
		 */
		if ($ax_configOption instanceof ConfigOption) {
			$ls_identifier = $ax_configOption->getIdentifier();

			//We cannot have the same identifier more than once inside this collection
			if ($this->offsetExists($ls_identifier)) {
				throw new RuntimeException(sprintf('The identifier `%s` is already in use.', $ls_identifier));
			}

			$this->offsetSet($ls_identifier, $ax_configOption);


			return $this;
		}

		//Traverse the provided array
		foreach ($ax_configOption as $lx_key => $lx_configOption) {
			//If the key is a string, add a new sub-collection with that given identifier, containing everything in $lx_configOption
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
	 * If the identifier of the ConfigOptionsCollection already exists in the current one, a `RuntimeException` is thrown.
	 *
	 * @param ConfigOptionCollection $ao_configOptionsCollection
	 * @return $this
	 * @throws RuntimeException
	 */
	public function addCollection(ConfigOptionCollection $ao_configOptionsCollection): static {
		$ls_identifier = $ao_configOptionsCollection->getIdentifier();

		if ($this->offsetExists($ls_identifier)) {
			$lx_offset = $this->offsetGet($ls_identifier);
			if ($lx_offset instanceof ConfigOptionCollection) {
				foreach ($ao_configOptionsCollection->getArrayCopy() as $lx_configOptions) {
					if ($lx_configOptions instanceof ConfigOptionCollection) {
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

		$this->offsetSet($ls_identifier, $ao_configOptionsCollection);


		return $this;
	}


	/**
	 * @return string
	 */
	public function getIdentifier(): string {
		return $this->identifier;
	}


	/**
	 * @param string ...$aa_pathParts
	 * @return array
	 */
	public function getConfigOptions(string ...$aa_pathParts): array {
		$la_configOptions = [];

		foreach ($this as $lo_configOption) {
			if ($lo_configOption instanceof ConfigOptionCollection) {
				$la_pathParts = $aa_pathParts;
				$la_pathParts[] = Inflector::variable($lo_configOption->getIdentifier());
				$la_configOptions += $lo_configOption->getConfigOptions(...$la_pathParts);
			}
			else {
				$ls_key = '';
				if (!empty($aa_pathParts)) {
					$ls_key .= implode('.', $aa_pathParts) . '.';
				}
				$ls_key .= Inflector::variable($lo_configOption->getIdentifier());

				$la_configOptions[ $ls_key ] = $lo_configOption;
			}
		}


		return $la_configOptions;
	}
}
