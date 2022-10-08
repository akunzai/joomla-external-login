<?php
$finder = PhpCsFixer\Finder::create()
	->in([__DIR__ . '/src']);

$config = new PhpCsFixer\Config();
return $config->setRules([
	'@PSR12' => true,
	'@PHP80Migration' => true,
	'@PHP81Migration' => true
])->setFinder($finder);
