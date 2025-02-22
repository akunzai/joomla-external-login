<?php
$finder = PhpCsFixer\Finder::create()
	->in([__DIR__ . '/src']);

$config = new PhpCsFixer\Config();
return $config
	->setParallelConfig(PhpCsFixer\Runner\Parallel\ParallelConfigFactory::detect())
	->setRules([
		'@PSR12' => true,
		'@PHP81Migration' => true
	])
	->setFinder($finder);
