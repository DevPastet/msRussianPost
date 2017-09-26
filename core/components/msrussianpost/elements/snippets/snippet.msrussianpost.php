<?php
/** @var array $scriptProperties */

// Do your snippet code here. This demo grabs 5 items from our custom table.
$tpl = $modx->getOption('tpl', $scriptProperties, 'tpl.msRussianPost.delivery');
$sending = $modx->getOption('sending', $scriptProperties, 'Ценная посылка');
$weight = $modx->getOption('weight', $scriptProperties, '1');
$cost = $modx->getOption('cost', $scriptProperties, '0');
$to = $modx->getOption('to', $scriptProperties, 'Москва');
$weight_in_kg = $modx->getOption('weightInKg', $scriptProperties,
	$modx->getOption('ms2_delivery_weight_in_kg', null, true));
$toPlaceholder = $modx->getOption('toPlaceholder', $scriptProperties, false);

/** @var msRussianPost $msRussianPost */
if (!$msRussianPost = $modx->getService('msrussianpost', 'msRussianPost', $modx->getOption('msrussianpost_core_path', null, $modx->getOption('core_path') . 'components/msrussianpost/') . 'model/msrussianpost/', $scriptProperties)) {
	return 'Could not load msRussianPost class!';
}

$sendingData = $msRussianPost->getSendingData($sending, $weight, $to, $cost);
$output = $modx->getChunk($tpl, $sendingData);

// Output
if (!empty($toPlaceholder)) {
	// If using a placeholder, output nothing and set output to specified placeholder
	$modx->setPlaceholder($toPlaceholder, $output);

	return '';
}
// By default just return output
return $output;
