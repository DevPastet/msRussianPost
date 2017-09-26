<?php

$settings = array();

$tmp = array(
    'msrussianpost_from_index' => array(
		'xtype' => 'textfield',
		'value' => '190000',
		'area' => 'msrussianpost_main',
	),
    'msrussianpost_default_weight' => array(
		'xtype' => 'textfield',
		'value' => '1',
		'area' => 'msrussianpost_main',
	),
    'ms2_delivery_weight_in_kg' => array(
		'xtype' => 'combo-boolean',
		'value' => true,
		'area' => 'ms2_delivery',
        'namespace' => 'minishop2'
	),
    'msrussianpost_cache_ttl' => array(
		'xtype' => 'textfield',
		'value' => 604800,
		'area' => 'msrussianpost_main',
	),
    'msrussianpost_response_to_modx_log' => array(
		'xtype' => 'combo-boolean',
		'value' => false,
		'area' => 'msrussianpost_main',
	),
	'msrussianpost_frontend_js' => array(
		'value' => '[[+jsUrl]]web/default.js'
		,'xtype' => 'textfield'
		,'area' => 'msrussianpost_main'
	),
	'msrussianpost_round_quantity' => array(
		'value' => '10'
		,'xtype' => 'textfield'
		,'area' => 'msrussianpost_main'
	),
	'msrussianpost_round_dir' => array(
		'value' => '0'
		,'xtype' => 'textfield'
		,'area' => 'msrussianpost_main'
	),
	'msrussianpost_return_time' => array(
		'xtype' => 'combo-boolean',
		'value' => true,
		'area' => 'msrussianpost_main',
	),


);

foreach ($tmp as $k => $v) {
	/* @var modSystemSetting $setting */
	$setting = $modx->newObject('modSystemSetting');
	$setting->fromArray(array_merge(
		array(
			'key' => $k,
			'namespace' => PKG_NAME_LOWER,
		), $v
	), '', true, true);

	$settings[] = $setting;
}

unset($tmp);
return $settings;
