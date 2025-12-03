<?php declare(strict_types=1);


namespace Awyiss\Test\TestCase\Utility\Design;


use Awyiss\Test\TestSuite\TestCase;
use Awyiss\Utility\Design\ScssFilesCollection;
use Cake\Core\Configure;
use SplFileInfo;


/**
 * Test case for ScssFilesCollection
 *
 * @see \Awyiss\Utility\Design\ScssFilesCollection
 */
class ScssFilesCollectionTest extends TestCase {
	/**
	 * @return void
	 * @see \Awyiss\Utility\Design\ScssFilesCollection::__construct()
	 */
	public function testConstructor(): void {
		$folder = Configure::read('App.paths.assets.Frontend.customer');

		// Create a new ScssFilesCollection object.
		$filesCollection = new ScssFilesCollection($folder);

		$this->assertSame($folder, $filesCollection->getFolderPath());
		$this->assertNull($filesCollection->getLastModified());
		$this->assertEmpty($filesCollection->getFiles());
		$this->assertEmpty($filesCollection->getMainFiles());
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Design\ScssFilesCollection::addFile()
	 */
	public function testAddFile(): void {
		$folder = Configure::read('App.paths.assets.Frontend.customer');

		// Create a new ScssFilesCollection object.
		$filesCollection = new ScssFilesCollection($folder);

		// Create a new file object.
		$file = new SplFileInfo($folder . 'scss/test.scss');

		// Add the file to the collection.
		$filesCollection->addFile($file);

		// Assert that the file was added correctly.
		$this->assertCount(1, $filesCollection->getFiles());
		$this->assertCount(1, $filesCollection->getMainFiles());
		$this->assertSame($file, $filesCollection->getFiles()[0]);
		$this->assertSame($file, $filesCollection->getMainFiles()[0]);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Design\ScssFilesCollection::addFile()
	 */
	public function testPartialAddFile(): void {
		$folder = Configure::read('App.paths.assets.Frontend.customer');

		// Create a new ScssFilesCollection object.
		$filesCollection = new ScssFilesCollection($folder);

		// Create a new file object.
		$file = new SplFileInfo($folder . 'scss/_variables.scss');

		// Add the file to the collection.
		$filesCollection->addFile($file);

		// Assert that the file was added correctly.
		$this->assertCount(1, $filesCollection->getFiles());
		$this->assertCount(0, $filesCollection->getMainFiles());
		$this->assertSame($file, $filesCollection->getFiles()[0]);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Design\ScssFilesCollection::getLastModified()
	 */
	public function testGetLastModified(): void {
		$folder = Configure::read('App.paths.assets.Frontend.customer');

		// Create a new ScssFilesCollection object.
		$filesCollection = new ScssFilesCollection($folder);

		// Create a new file object.
		$file = new SplFileInfo($folder . 'scss/test.scss');

		// Add the file to the collection.
		$filesCollection->addFile($file);

		// Get the last modified time of the collection.
		$lastModified = $filesCollection->getLastModified();

		// Assert that the last modified time is not null and is greater than 0.
		$this->assertNotNull($lastModified);
		$this->assertSame(
			$file->getMTime(),
			$lastModified->getTimestamp(),
			'Last modified time should match the file modification time.'
		);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Design\ScssFilesCollection::getLastModified()
	 */
	public function testGetLastModifiedForMultipleFiles(): void {
		$folder = Configure::read('App.paths.assets.Frontend.customer');

		// Create a new ScssFilesCollection object.
		$filesCollection = new ScssFilesCollection($folder);

		// Create a new file object.
		$file = new SplFileInfo($folder . 'scss/test.scss');
		touch($file->getPathname(), $file->getMTime() - 10);

		$partial = new SplFileInfo($folder . 'scss/_variables.scss');
		// Touch the partial file to ensure it has a different modification time.
		touch($partial->getPathname(), $file->getMTime() + 10);

		// Add the files to the collection.
		$filesCollection->addFile($file);
		$filesCollection->addFile($partial);

		// Get the last modified time of the collection.
		$lastModified = $filesCollection->getLastModified();

		// Assert that the last modified time is the same as the newer file's modification time.
		$this->assertNotNull($lastModified);
		$this->assertSame(
			$partial->getMTime(),
			$lastModified->getTimestamp(),
			'Last modified time should match the file modification time.'
		);
	}


	/**
	 * @return void
	 * @see \Awyiss\Utility\Design\ScssFilesCollection::getLastModified()
	 */
	public function testGetLastModifiedForMultipleFilesInverted(): void {
		$folder = Configure::read('App.paths.assets.Frontend.customer');

		// Create a new ScssFilesCollection object.
		$filesCollection = new ScssFilesCollection($folder);

		// Create a new file object.
		$file = new SplFileInfo($folder . 'scss/test.scss');
		touch($file->getPathname(), $file->getMTime() - 10);

		$partial = new SplFileInfo($folder . 'scss/_variables.scss');
		// Touch the partial file to ensure it has a different modification time.
		touch($partial->getPathname(), $file->getMTime() + 10);

		// Add the files to the collection but add the newer one first
		$filesCollection->addFile($partial);
		$filesCollection->addFile($file);

		// Get the last modified time of the collection.
		$lastModified = $filesCollection->getLastModified();

		// Assert that the last modified time is the same as the newer file's modification time.
		$this->assertNotNull($lastModified);
		$this->assertSame(
			$partial->getMTime(),
			$lastModified->getTimestamp(),
			'Last modified time should match the file modification time.'
		);
	}
}
