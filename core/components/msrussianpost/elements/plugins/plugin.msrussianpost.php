<?php

switch ($modx->event->name) {
    case 'OnHandleRequest':
        if (isset($_POST['msdelivery_action'])) {
            $action = $_POST['msdelivery_action'];
            $response = '';
            $deliveryHandler = null;

            /** @var minishop2 $ms2 */
            if ($ms2 = $modx->getService('minishop2') and $ms2->initialize() and $orderData = $ms2->order->get()) {

                $delivery = $modx->getObject('msDelivery', $orderData['delivery']);
                $deliveryHandlerClass = $delivery->get('class');
                $deliveryHandlerClassLC = strtolower($deliveryHandlerClass);

                if (!$deliveryHandlerClass) {
                    return;
                }

                if ($modx->loadClass($deliveryHandlerClass,$ms2->config['customPath'].'delivery/',false, true)) {
                    $deliveryHandler = new $deliveryHandlerClass ($delivery);
                }

                if ($deliveryHandler) {

                    switch ($action) {

                        case 'delivery/gettime':
                            if (method_exists($deliveryHandler, 'getTime')) {
                                $response = $deliveryHandler->getTime();
                            } else {
                                $modx->log(xPDO::LOG_LEVEL_ERROR, 'Method getTime() not exists in class '. $deliveryHandlerClass);
                            }
                            break;

                    }
                } else {
                    $modx->log(xPDO::LOG_LEVEL_ERROR, 'Class '.$deliveryHandlerClass. ' not exists');
                }

            } else {
                $modx->log(xPDO::LOG_LEVEL_ERROR, 'Problem with getting minishop2 service or order data is false');
            }

            exit($response);
        }
        break;

}