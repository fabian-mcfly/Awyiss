<?php

declare(strict_types=1);


namespace Awyiss\View;


/**
 * Adds string template functionality to any class by providing methods to
 * load and parse string templates.
 *
 * This trait requires the implementing class to provide a `config()`
 * method for reading/updating templates. An implementation of this method
 * is provided by `Cake\Core\InstanceConfigTrait`
 */
trait StringTemplateTrait {
	/**
	 * Returns the templater instance.
	 *
	 * @return \Cake\View\StringTemplate
	 */
	public function templater (): StringTemplate {
		if ($this->_templater === NULL) {
			/** @var class-string<\Cake\View\StringTemplate> $class */
			$class = $this->getConfig('templateClass') ?: StringTemplate::class;
			$this->_templater = new $class();

			$templates = $this->getConfig('templates');
			if ($templates) {
				if (is_string($templates)) {
					$this->_templater->add($this->_defaultConfig['templates']);
					$this->_templater->load($templates);
				}
				else {
					$this->_templater->add($templates);
				}
			}
		}

		return $this->_templater;
	}
}