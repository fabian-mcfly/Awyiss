<?php declare(strict_types=1);


use Migrations\AbstractSeed;


/**
 * MediaResizedImagesCustomSeed seed.
 */
class MediaResizedImagesCustomSeed extends AbstractSeed {
	/**
	 * @inheritDoc
	 */
	public function run(): void {
		$la_data = [
			[
				'id' => 1,
				'media_id' => 4,
				'name' => 'logo-awyiss-[w1024].webp',
				'path' => '_resized/dummypath/logo-awyiss-[w1024].webp',
				'width' => 1024,
				'height' => null,
				'real_width' => 1024,
				'real_height' => null,
				'strategy' => 1,
				'status' => 1,
			],
			[
				'id' => 2,
				'media_id' => 4,
				'name' => 'logo-awyiss-[w768].webp',
				'path' => '_resized/dummypath/logo-awyiss-[w768].webp',
				'width' => 768,
				'height' => null,
				'real_width' => 768,
				'real_height' => 640,
				'strategy' => 1,
				'status' => 1,
			],
			[
				'id' => 3,
				'media_id' => 4,
				'name' => 'logo-awyiss-[w2560].webp',
				'path' => '_resized/dummypath/logo-awyiss-[w2560].webp',
				'width' => 2560,
				'height' => null,
				'real_width' => 2560,
				'real_height' => 1440,
				'strategy' => 1,
				'status' => 1,
			],
			[
				'id' => 4,
				'media_id' => 4,
				'name' => 'logo-awyiss-[w640].webp',
				'path' => '_resized/dummypath/logo-awyiss-[w640].webp',
				'width' => 640,
				'height' => null,
				'real_width' => 640,
				'real_height' => 360,
				'strategy' => 1,
				'status' => 1,
			],
			[
				'id' => 5,
				'media_id' => 4,
				'name' => 'logo-awyiss-[w480].webp',
				'path' => '_resized/dummypath/logo-awyiss-[w480].webp',
				'width' => 480,
				'height' => null,
				'real_width' => 480,
				'real_height' => 270,
				'strategy' => 1,
				'status' => 1,
			],
			[
				'id' => 6,
				'media_id' => 4,
				'name' => 'logo-awyiss-[w320].webp',
				'path' => '_resized/dummypath/logo-awyiss-[w320].webp',
				'width' => 320,
				'height' => null,
				'real_width' => 320,
				'real_height' => null,
				'strategy' => 1,
				'status' => 1,
			],
			[
				'id' => 7,
				'media_id' => 4,
				'name' => 'logo-awyiss-[w1440].webp',
				'path' => '_resized/dummypath/logo-awyiss-[w1440].webp',
				'width' => 1440,
				'height' => null,
				'real_width' => 1440,
				'real_height' => 810,
				'strategy' => 1,
				'status' => 1,
			],
			[
				'id' => 8,
				'media_id' => 4,
				'name' => 'logo-awyiss-[w576].webp',
				'path' => '_resized/dummypath/logo-awyiss-[w576].webp',
				'width' => 576,
				'height' => null,
				'real_width' => 576,
				'real_height' => 324,
				'strategy' => 1,
				'status' => 1,
			],
		];

		$lo_table = $this->table('media_resized_images');
		$lo_table->truncate();
		$lo_table->insert($la_data)->save();
	}
}
