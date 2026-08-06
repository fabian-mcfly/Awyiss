<?php

/** @noinspection PhpIllegalPsrClassPathInspection */


declare(strict_types=1); // phpcs:ignore


use Migrations\BaseMigration;


/**
 * Change name and path columns in media_resized_images table to allow for longer values.
 *
 * Since name and path in the media table are limited to 100 resp. 1024 characters,
 * we need to expand the name and path columns in the media_resized_images table to avoid truncation of values,
 * since file names have their sizes and resize methods appened (`-[w800h600]`), which can exceed the current limits.
 */
class ExpandMediaResizedImagesNameAndPath extends BaseMigration {
	/**
	 * Change Method.
	 *
	 * @return void
	 */
	public function change(): void {
		$table = $this->table('media_resized_images');
		$table->changeColumn('name', 'string', [
			'limit' => 200,
			'default' => null,
			'null' => false,
		]);
		$table->changeColumn('path', 'string', [
			'limit' => 1224,
			'default' => null,
			'null' => false,
		]);
		$table->update();
	}
}

