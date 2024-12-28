<?php

declare(strict_types=1);

require_once './vendor/autoload.php';

use Nextcloud\CodingStandard\Config as Base;
use PhpCsFixer\Runner\Parallel\ParallelConfig;

class Config extends Base {
	public function getRules(array $rules = []): array
	{
		return [
			...parent::getRules(), // Nextcloud Standard Rules
			'@PSR12' => true,
			'@PhpCsFixer' => true,
			'global_namespace_import' => [
				'import_classes' => true,
			],
			'trailing_comma_in_multiline' => ['elements' => ['arguments', 'arrays', 'match', 'parameters']],
		];
	}
}

$config = new Config();
$config
	->setParallelConfig(new ParallelConfig(8))
	->setRiskyAllowed(true)
	->getFinder()
	->ignoreVCSIgnored(true)
	->notPath('dev-environment')
	->notPath('build')
	->notPath('l10n')
	->notPath('src')
	->notPath('vendor')
	->in(__DIR__);
return $config;
