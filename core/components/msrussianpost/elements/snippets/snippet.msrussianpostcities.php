<?php
/** @var array $scriptProperties */
/** @var msRussianPost $msRussianPost */
if (!$msRussianPost = $modx->getService('msrussianpost', 'msRussianPost', $modx->getOption('msrussianpost_core_path', null, $modx->getOption('core_path') . 'components/msrussianpost/') . 'model/msrussianpost/', $scriptProperties)) {
	return 'Could not load msRussianPost class!';
}

// Do your snippet code here. This demo grabs 5 items from our custom table.
$tpl = $modx->getOption('tpl', $scriptProperties, 'option');
$sortby = $modx->getOption('sortby', $scriptProperties, 'name');
$sortdir = $modx->getOption('sortbir', $scriptProperties, 'ASC');
$limit = $modx->getOption('limit', $scriptProperties, 5);
$outputSeparator = $modx->getOption('outputSeparator', $scriptProperties, "\n");
$toPlaceholder = $modx->getOption('toPlaceholder', $scriptProperties, false);

$output = '';
$cities = array();
$citiesRaw = $msRussianPost->getCities();
foreach ($citiesRaw as $city) {
    $cities[] = $city['label'];
}
sort($cities);
foreach ($cities as $city) {
    $attr = ($city==$_SESSION['minishop2']['order']['city']) ? ' selected' : '';
    $output .= $modx->getChunk($tpl, array('city' => $city, 'attr'=>$attr));
}

//$output = print_r($cities,1);

if (!empty($toPlaceholder)) {
    // If using a placeholder, output nothing and set output to specified placeholder
    $modx->setPlaceholder($toPlaceholder, $output);

    return '';
}
// By default just return output
return $output;
