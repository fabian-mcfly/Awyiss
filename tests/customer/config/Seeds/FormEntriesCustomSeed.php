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
		$data = [
			[
				'id' => 1,
				'formId' => 1,
				'pageId' => 1,
				'languageShortcode' => 'en',
				'subject' => 'Subject',
				'subjectConfirmation' => 'Confirmation Subject',
				'body' => 'eJxLyk+pBAAEGgGv',
				'bodyConfirmation' => 'eJxLzs9LyyzKTSzJzM9TSMpPqQQAPm8G2A==',
				'data' => 'eJyrVirLL8pLzE1VslLyTaxQ0lHKS0zOgAmUFpekFuUm5uUBxVNzEzNzgIIppbm5lQ4p+UBunl5JTopSLQAcbhd4',
				'ipHash' => 'f0fdb4c3f58e3e3f8e77162d893d3055',
				'postHash' => '9bb58f26192e4ba00f01e2e7b136bbd8',
				'identifier' => 'aa43b23308dd6bdff9edb15deb2b3b41',
				'deleted' => 0,
				'createdOn' => '2020-02-02 02:02:02',
				'deletedBy' => null,
				'deletedOn' => null,
			],
			[
				'id' => 2,
				'formId' => 1,
				'pageId' => 1,
				'languageShortcode' => 'en',
				'subject' => 'Subject',
				'subjectConfirmation' => 'Confirmation Subject',
				'body' => 'eJxLyk+pBAAEGgGv',
				'bodyConfirmation' => 'eJxLzs9LyyzKTSzJzM9TSMpPqQQAPm8G2A==',
				'data' => 'eJxVj09LAzEQxc8t9DssOS9iSna79aQoQj148SQikiazNpBkliRbXMp+dydp8c/x/d7Mmzen1XLBwElj79H3JjiZDHp2U/nR2jqbcQxHmIicSC2YHp2bXn4Y65q10NAACVFXrG+3e96KjuRbV1fbd2Ib3TZCiJYYU2NM6Fim6+tG9HxDlPOZsudyLplk4e99KhXTs3QZsic8eFawlb/0AeEMhwP6f8vlszwCX9INFm41EvFXKnfIAw5ilJ8l5RFxL8MlKJijVNOdUjAk0Nl+hXj2PnoMbqfBJ9MbCOUr9EmqdPEHCtzlHb5azt8uLmJV',
				'ipHash' => 'f528764d624db129b32c21fbca0cb8d6',
				'postHash' => '9bb58f26192e4ba00f01e2e7b136bbd8',
				'identifier' => '915c372723fe959f267987d352681425',
				'deleted' => 0,
				'createdOn' => '2020-02-02 02:02:02',
				'deletedBy' => null,
				'deletedOn' => null,
			],
			[
				'id' => 3,
				'formId' => 1,
				'pageId' => 1,
				'languageShortcode' => 'en',
				'subject' => 'Subject',
				'subjectConfirmation' => 'Confirmation Subject',
				'body' => 'eJxLyk+pBAAEGgGv',
				'bodyConfirmation' => 'eJxLzs9LyyzKTSzJzM9TSMpPqQQAPm8G2A==',
				'data' => 'eJxVj8FqwzAQRM8J5B+MzqbURnacnBJaCumhl55KKUWR1o1A0hpJDjXB/96VEmh7nDe7s7OX1XLBwAptHtD12lsRNTq2LdxoTJnMMPozTEQupBZMjdZOr5nVCbKuqbmCBkjwsmB9uzlWLe9IvndlsfkgtlZtwzlviTE5hoiWJVrfN7yv1kSraqbwOd+LOhr4W4BahfgibILsGU+OZWzEL31EuMLhhO7fcn4tjcC3sIOBnUIi7k6mDmnAQgjiK6c8IR6FvwV5fRZy2ksJQwSV7DcIV++zR28PClzUvQafv0IXhYw3f6DAQ9qpVsv5B3qYYoc=',
				'ipHash' => 'f528764d624db129b32c21fbca0cb8d6',
				'postHash' => '9bb58f26192e4ba00f01e2e7b136bbd8',
				'identifier' => '915c3724723345akn53dc7d352681425',
				'deleted' => 0,
				'createdOn' => '2020-02-02 02:02:02',
				'deletedBy' => null,
				'deletedOn' => null,
			],
		];

		$table = $this->table('form_entries');
		$table->insert($data)->save();
	}
}
