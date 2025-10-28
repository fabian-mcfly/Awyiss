<?php declare(strict_types=1);


use Migrations\AbstractSeed;


/**
 * FormEntries seed.
 */
class FormEntriesCustomSeed extends AbstractSeed {
	/**
	 * @inheritDoc
	 */
	public function run(): void {
		$la_data = [
			[
				'id' => 1,
				'form_id' => 1,
				'page_id' => 1,
				'language_shortcode' => 'en',
				'subject' => 'Subject',
				'subject_confirmation' => 'Confirmation Subject',
				'body' => 'eJxLyk+pBAAEGgGv',
				'body_confirmation' => 'eJxLzs9LyyzKTSzJzM9TSMpPqQQAPm8G2A==',
				'data' => 'eJyrVirLL8pLzE1VslLyTaxQ0lHKS0zOgAmUFpekFuUm5uUBxVNzEzNzgIIppbm5lQ4p+UBunl5JTopSLQAcbhd4',
				'ip_hash' => 'f0fdb4c3f58e3e3f8e77162d893d3055',
				'post_hash' => '9bb58f26192e4ba00f01e2e7b136bbd8',
				'identifier' => 'aa43b23308dd6bdff9edb15deb2b3b41',
				'deleted' => 0,
				'created_on' => '2020-02-02 02:02:02',
				'deleted_by' => null,
				'deleted_on' => null,
			],
			[
				'id' => 2,
				'form_id' => 1,
				'page_id' => 1,
				'language_shortcode' => 'en',
				'subject' => 'Subject',
				'subject_confirmation' => 'Confirmation Subject',
				'body' => 'eJxLyk+pBAAEGgGv',
				'body_confirmation' => 'eJxLzs9LyyzKTSzJzM9TSMpPqQQAPm8G2A==',
				'data' => 'eJxFjkFqwzAQRe8ya1HqIDuOVl2ULnqDUoqYSONGIGmMJIea4Lt3HGiznPf48/8NKGGI1nGeQknYAmcweYlRQV3KlVYwN/BLSqt93GN/0J56AqMVTMPp3A16BPM5qtOXgqMfeq31AAbcUhsnEHZ47vXUHcF03bYpaKFF+iuS5tpsxiQE3vmSJRDxgV6ZhMwXzv+R+2pR9INpjvTiWUB+cveyRLXi9558Yz5j2cMlXNGtFp2juZEX90FVhJ24JBs85RamQGUfzbmha7uc5Y1IWb39AlKUYY4=',
				'ip_hash' => 'f528764d624db129b32c21fbca0cb8d6',
				'post_hash' => '9bb58f26192e4ba00f01e2e7b136bbd8',
				'identifier' => '915c372723fe959f267987d352681425',
				'deleted' => 0,
				'created_on' => '2020-02-02 02:02:02',
				'deleted_by' => null,
				'deleted_on' => null,
			],
			[
				'id' => 3,
				'form_id' => 1,
				'page_id' => 1,
				'language_shortcode' => 'en',
				'subject' => 'Subject',
				'subject_confirmation' => 'Confirmation Subject',
				'body' => 'eJxLyk+pBAAEGgGv',
				'body_confirmation' => 'eJxLzs9LyyzKTSzJzM9TSMpPqQQAPm8G2A==',
				'data' => 'eJxFjkFqwzAQRe8ya1FiIzuOVl2ULnqDUopQpHEjkDRGkkNM8N07Smm7nPf48/8dMBoftKU0+xxN9ZRApTUEAWXNV9xA3cGtMW765+4bmIZeOhwQlBQwj6dzN8oJ1MckTp8Cjm4cpJQjKLBrqRSBWX8Y5NwdQXXdvguovgb8beLqUnUykQm80SVxIJh/9ELIZLlQ+os8ZrPCm4lLwGdHDNKTfZRFLMV8teQr0dnkFs7+auymjbW4VHTs3rGw0DPlqL3DVP3sMbfRlKqxtcmF37Dk1fs3lolhwA==',
				'ip_hash' => 'f528764d624db129b32c21fbca0cb8d6',
				'post_hash' => '9bb58f26192e4ba00f01e2e7b136bbd8',
				'identifier' => '915c3724723345akn53dc7d352681425',
				'deleted' => 0,
				'created_on' => '2020-02-02 02:02:02',
				'deleted_by' => null,
				'deleted_on' => null,
			],
		];

		$lo_table = $this->table('form_entries');
		$lo_table->insert($la_data)->save();
	}
}
