<?php declare(strict_types=1);


namespace Awyiss\Utility\Design;


use Cake\I18n\DateTime;
use SplFileInfo;


class ScssFilesCollection {
	/**
	 * @var array<\SplFileInfo> The files.
	 */
	protected array $files = [];
	/**
	 * @var string The folder path
	 */
	protected string $folderPath;
	/**
	 * @var \Cake\I18n\DateTime|null The last modified time.
	 */
	protected ?DateTime $lastModified = null;
	/**
	 * @var array<\SplFileInfo> The main files.
	 */
	protected array $mainFiles = [];


	/**
	 * @param string $folderPath The folder path
	 */
	public function __construct(string $folderPath) {
		$this->folderPath = $folderPath;
	}


	/**
	 * Adds a file to the files array and updates the lastModified property if necessary.
	 *
	 * @param \SplFileInfo $file The file to add.
	 */
	public function addFile(SplFileInfo $file): void {
		$this->files[] = $file;

		// If the file does not start with an underscore, add it to the main files array.
		if ($file->getBasename()[0] !== '_') {
			$this->mainFiles[] = $file;
		}

		// Create a new Time object with the file modification time and compare it with the current lastModified time.
		// If it's later, update lastModified.
		$lo_fileMTime = new DateTime('@' . $file->getMTime());
		if (!$this->lastModified || $lo_fileMTime->greaterThan($this->lastModified)) {
			$this->lastModified = $lo_fileMTime;
		}
	}


	/**
	 * Gets the files.
	 *
	 * @return array<\SplFileInfo> The files.
	 */
	public function getFiles(): array {
		return $this->files;
	}


	/**
	 * Gets the folder path
	 *
	 * @return string The folder path
	 */
	public function getFolderPath(): string {
		return $this->folderPath;
	}


	/**
	 * Gets the last modified time.
	 *
	 * @return \Cake\I18n\DateTime|null The last modified time.
	 */
	public function getLastModified(): ?DateTime {
		return $this->lastModified;
	}


	/**
	 * Gets the main files.
	 *
	 * @return array<\SplFileInfo> The main files.
	 */
	public function getMainFiles(): array {
		return $this->mainFiles;
	}
}
