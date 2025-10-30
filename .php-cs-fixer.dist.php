<?php

use PhpCsFixer\Config;
use PhpCsFixer\Finder;
use PhpCsFixer\Runner\Parallel\ParallelConfigFactory;

$finder = Finder::create()
	->in([__DIR__ . '/src']);

$config = new Config();
return $config
	->setParallelConfig(ParallelConfigFactory::detect())
	->setRules([
		'@PSR12' => true,
		'@PHP81Migration' => true,

		// PHPDoc rules
		'phpdoc_align' => ['align' => 'left'],
		'phpdoc_annotation_without_dot' => true,
		'phpdoc_indent' => true,
		'phpdoc_inline_tag_normalizer' => true,
		'phpdoc_no_access' => true,
		'phpdoc_no_empty_return' => true,
		'phpdoc_no_package' => true,
		'phpdoc_no_useless_inheritdoc' => true,
		'phpdoc_order' => true,
		'phpdoc_return_self_reference' => true,
		'phpdoc_scalar' => true,
		'phpdoc_separation' => true,
		'phpdoc_single_line_var_spacing' => true,
		'phpdoc_summary' => true,
		'phpdoc_trim' => true,
		'phpdoc_trim_consecutive_blank_line_separation' => true,
		'phpdoc_types' => true,
		'phpdoc_types_order' => ['null_adjustment' => 'always_last', 'sort_algorithm' => 'none'],
		'phpdoc_var_annotation_correct_order' => true,
		'phpdoc_var_without_name' => true,

		// Additional documentation rules
		'no_blank_lines_after_phpdoc' => true,
		'no_empty_phpdoc' => true,
		'no_superfluous_phpdoc_tags' => [
			'allow_mixed' => true,
			'remove_inheritdoc' => false,
		],

		// Type hints
		'native_function_type_declaration_casing' => true,
	])
	->setFinder($finder);
