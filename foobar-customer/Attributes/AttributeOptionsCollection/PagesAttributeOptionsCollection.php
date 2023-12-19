<?php declare(strict_types=1);


namespace FoobarCustomer\Attributes\AttributeOptionsCollection ;


use Awyiss\Attributes\AttributeOptionsCollection;
use Awyiss\Middleware\LocaleMiddleware;
use Cake\Datasource\EntityInterface;
use Cake\I18n\DateTime;


class PagesAttributeOptionsCollection extends AttributeOptionsCollection {
	protected static string $scope = 'Pages';


	public function initializeAttributeOptions (): void {

		$this->add([
			'testdate2' => [
				'disabled' => function(EntityInterface $ao_entity, array $aa_currentOptions = []) {
					$lo_date = new DateTime('now', LocaleMiddleware::getLanguage()->timezone);
					$lo_date = $lo_date->minute(0)->second(0);

					for ($i = 0; $i <= 5; $i++) {
						$lo_date = $lo_date->modify('+2 days');
						$la_options[] = $lo_date->format('Y-m-d H:i');
					}

					return $la_options;
				},
				'options' => function(EntityInterface $ao_entity, array $aa_currentOptions = []) {
					$lo_date = new DateTime('now', LocaleMiddleware::getLanguage()->timezone);
					$lo_date = $lo_date->minute(0)->second(0);

					$la_options = [$lo_date->format('Y-m-d H:i') => $lo_date->nice()];
					for ($i = 0; $i < 9; $i++) {
						$lo_date = $lo_date->modify('+1 day');
						$la_options[ $lo_date->format('Y-m-d H:i') ] = $lo_date->nice();
					}

					return $la_options;
				},
				'toScalar' => function(DateTime $ao_dateTime) {
					$lo_dateTime = $ao_dateTime->setTimezone(LocaleMiddleware::getLanguage()->timezone)->minute(0)->second(0);

					return $lo_dateTime->format('Y-m-d H:i');
				},
				/*'validate' => function() {
					dd(func_get_args());
				},*/
				'value' => function(EntityInterface $ao_entity, array &$aa_currentOptions = []) {
					if ( ! empty($ao_entity->testdate2)) {
						$lo_dateTime = $ao_entity->testdate2->setTimezone(LocaleMiddleware::getLanguage()->timezone)->minute(0)->second(0);

						return $lo_dateTime->format('Y-m-d H:i');
					}

					return NULL;
				},
			],
			/*'testcheck' => [
				'value' => 555,
			]*/
		]);
	}
}
