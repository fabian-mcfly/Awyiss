<?php declare(strict_types=1);


namespace Awyiss\Test\TestSuite\Bake;


use Cake\TestSuite\StringCompareTrait;


/**
 * Trait BakeTestTrait
 * Provides a convenient way for bake-tests
 * to clean up generated files.
 */
trait BakeTestTrait {
	use StringCompareTrait;

	/**
	 * @var string
	 */
	protected $generatedFile = '';
	/**
	 * @var list<string>
	 */
	protected $generatedFiles = [];


	/**
	 * @inheritDoc
	 */
	public function tearDown(): void {
		parent::tearDown();

		if ($this->generatedFile && file_exists($this->generatedFile)) {
			unlink($this->generatedFile);
			$this->generatedFile = '';
		}

		if (count($this->generatedFiles)) {
			foreach ($this->generatedFiles as $file) {
				if (file_exists($file)) {
					unlink($file);
				}
			}

			$this->generatedFiles = [];
		}
	}
}
