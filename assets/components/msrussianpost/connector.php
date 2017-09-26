<?php
/** @noinspection PhpIncludeInspection */
require_once dirname(dirname(dirname(dirname(__FILE__)))) . '/config.core.php';
/** @noinspection PhpIncludeInspection */
require_once MODX_CORE_PATH . 'config/' . MODX_CONFIG_KEY . '.inc.php';
/** @noinspection PhpIncludeInspection */
require_once MODX_CONNECTORS_PATH . 'index.php';
/** @var msRussianPost $msRussianPost */
$msRussianPost = $modx->getService('msrussianpost', 'msRussianPost', $modx->getOption('msrussianpost_core_path', null, $modx->getOption('core_path') . 'components/msrussianpost/') . 'model/msrussianpost/');
$modx->lexicon->load('msrussianpost:default');

// handle request
$corePath = $modx->getOption('msrussianpost_core_path', null, $modx->getOption('core_path') . 'components/msrussianpost/');
$path = $modx->getOption('processorsPath', $msRussianPost->config, $corePath . 'processors/');
$modx->request->handleRequest(array(
	'processors_path' => $path,
	'location' => '',
));