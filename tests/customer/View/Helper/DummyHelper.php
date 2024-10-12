<?php declare(strict_types=1);


namespace Customer\View\Helper;


use Cake\View\Helper;


/**
 * Dummy Helper
 *
 * @extends \Cake\View\Helper
 */
class DummyHelper extends Helper {
	/**
	 * @return string
	 */
	public function dummyMethod(): string {
		return 'dummy';
	}


	/**
	 * @return string
	 */
	public function dummyHtmlMethod(): string {
		return '<span>dummy</span>';
	}
}
