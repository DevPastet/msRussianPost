<?php
/**
 * Created by PhpStorm.
 * User: mvoevodskiy
 * Date: 13.06.15
 * Time: 1:33
 */

if (!class_exists('msDeliveryInterface')) {
    require_once dirname(dirname(dirname(__FILE__))) . '/model/minishop2/msdeliveryhandler.class.php';
}

class msRussianPostHandler extends msDeliveryHandler implements msDeliveryInterface {

    public $localServiceName = 'msRussianPost';
    /** @var msRussianPost $localService */
    public $localService = null;


    public function getLocalService() {
        if (!$this->localService) {
            $srvName = $this->localServiceName;
            $srvNameLC = strtolower($srvName);
            if (!$this->localService = $this->modx->getService($srvNameLC, $srvName, $this->modx->getOption($srvNameLC . '_core_path', null, $this->modx->getOption('core_path') . 'components/' . $srvNameLC) . '/model/' . $srvNameLC. '/', array())) {
                $this->modx->log(xPDO::LOG_LEVEL_ERROR, 'Could not load ' . $srvName . ' class!');
                return false;
            }
        }
        return $this->localService;
    }


    /** @inheritdoc} */
    public function getCost(msOrderInterface $order, msDelivery $delivery, $cart_cost = 0) {

        if ($this->getLocalService()) {
            return $this->localService->getCost($order, $delivery, $cart_cost);
        } else {
            return $cart_cost;
        }

    }

    public function getTime() {

        if ($this->getLocalService()) {
            return $this->localService->getTime();
        } else {
            return '';
        }
    }


}

