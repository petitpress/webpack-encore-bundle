<?php

$finder = PhpCsFixer\Finder::create()
	->in(__DIR__ . '/src')
	->in(__DIR__ . '/tests')
	->exclude('temp')
;

return (new PhpCsFixer\Config())
	->setUsingCache(false)
	->setIndent("\t")
	->setRules([
		'@PSR2' => true,
		'array_syntax' => ['syntax' => 'short'],
		'trailing_comma_in_multiline' => true,
		'constant_case' => [
			'case' => 'upper',
		],
		'declare_strict_types' => true,
		'phpdoc_align' => true,
		'blank_line_after_opening_tag' => true,
		'blank_line_before_statement' => [
			'statements' => ['break', 'continue', 'declare', 'return'],
		],
		'blank_line_after_namespace' => true,
		'single_blank_line_before_namespace' => true,
		'return_type_declaration' => [
			'space_before' => 'none',
		],
		'ordered_imports' => [
			'sort_algorithm' => 'length',
		],
		'no_unused_imports' => true,
		'single_line_after_imports' => true,
		'no_leading_import_slash' => true,
	])
	->setRiskyAllowed(true)
	->setFinder($finder)
;
