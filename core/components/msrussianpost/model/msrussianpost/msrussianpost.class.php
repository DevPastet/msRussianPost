<?php

/**
 * The base class for msRussianPost.
 */
class msRussianPost {

    const ROUND_DIR_MATH = 1;
    const ROUND_DIR_DECR = 2;
    const ROUND_DIR_INCR = 3;

	/* @var modX $modx */
	public $modx;
    /** @var minishop2 ms2 */
    public $ms2;
    /** @var postCalcRussianPost $postCalc */
    public $postCalc = null;
    public $deliveryHandler = 'msRussianPostHandler';
    public $initialized = false;


    /**
	 * @param modX $modx
	 * @param array $config
	 */
	function __construct(modX &$modx, array $config = array()) {
		$this->modx =& $modx;

		$corePath = $this->modx->getOption('msrussianpost_core_path', $config, $this->modx->getOption('core_path') . 'components/msrussianpost/');
		$assetsUrl = $this->modx->getOption('msrussianpost_assets_url', $config, $this->modx->getOption('assets_url') . 'components/msrussianpost/');
		$connectorUrl = $assetsUrl . 'connector.php';
        $defaultWeight = $this->modx->getOption('msrussianpost_default_weight', null, 1);

		$this->config = array_merge(array(
			'assetsUrl' => $assetsUrl,
			'cssUrl' => $assetsUrl . 'css/',
			'jsUrl' => $assetsUrl . 'js/',
			'imagesUrl' => $assetsUrl . 'images/',
			'connectorUrl' => $connectorUrl,

			'corePath' => $corePath,
			'modelPath' => $corePath . 'model/',
			'chunksPath' => $corePath . 'elements/chunks/',
			'templatesPath' => $corePath . 'elements/templates/',
			'chunkSuffix' => '.chunk.tpl',
			'snippetsPath' => $corePath . 'elements/snippets/',
			'processorsPath' => $corePath . 'processors/',

            'name_prefix' => $this->modx->getOption('msrussianpost_name_prefix'),
            'weight_in_kg' => $this->modx->getOption('ms2_delivery_weight_in_kg'),
            'spam_modx_log' => $this->modx->getOption('msrussianpost_response_to_modx_log', null, false),
            'defaultWeight' => $defaultWeight,
            'returnTime' => $this->modx->getOption('msrussianpost_return_time', null, true),

		), $config);

		$this->modx->addPackage('msrussianpost', $this->config['modelPath']);
		$this->modx->lexicon->load('msrussianpost:default');
        $this->initialize();

    }

    public function initialize() {
        if (!$this->initialized) {

            $this->ms2 = $this->modx->getService('minishop2');
            if (!$this->ms2->initialized) {
                $this->ms2->initialize($this->modx->context->get('key'), array('json_response' => true));
            }

            $deliveryDpIds = array();
//            $deliveryDp = array();
            $deliveryIds = array();
            /** @var msDelivery[] $deliveries */
            $deliveries = $this->modx->getCollection('msDelivery', array('class' => $this->deliveryHandler));
            foreach ($deliveries as $d) {
                $deliveryIds[] = $d->get('id');
                $p = $d->get('properties');
                if (isset($p['tariffId']) and in_array($p['tariffId'], $this->dpTariffs)) {
                    $deliveryDpIds[] = $d->get('id');
                }
            }

            $this->modx->regClientScript('<script type="text/javascript">msRussianPostConfig = ' . $this->modx->toJSON(array(
                    'deliveries' => $deliveryIds,
                    'deliveriesDP' => $deliveryDpIds,
                )) . '</script>', true);

            if ($js = trim($this->modx->getOption('msrussianpost_frontend_js'))) {
                if (!empty($js) && preg_match('/\.js/i', $js)) {
                    $this->modx->regClientScript(str_replace('[[+jsUrl]]', $this->config['jsUrl'], $js));
                }
            }
            $this->initialized = true;
        }
    }

    public function deliveryOwner() {

        $orderData = $this->ms2->order->get();
        if ($delivery = $this->modx->getObject('msDelivery', $orderData['delivery'])) {
            if ($delivery->get('class') == $this->deliveryHandler) return true;
        }

        return false;
    }




    public function getCost(msOrderInterface $order, msDelivery $delivery, $cart_cost = 0) {

        if ($delivery->get('class') !== $this->deliveryHandler) return '';

        $sendingName = $this->getSendingName($delivery);
        $cart = $this->ms2->cart->status();
        $orderData = $order->get();

        if ($orderData['city']) {
            $sendingData = $this->getSendingData($sendingName, $cart['total_weight'], $orderData['city'], $cart_cost);
        } else {
            $sendingData = array('cost' => 0, 'weightTotal' => 0, 'weightSingle' => 1);
        }
        $sendingData['cost'] = $sendingData['cost'] * ceil($sendingData['weightTotal'] / $sendingData['weightSingle']);



        $deliveryCost = $sendingData['cost'];
        $weightCost = $delivery->get('weight_price') * $cart['total_weight'];
        $additionalCost = $delivery->get('price');
        if (strpos($additionalCost, '%') !== false) {
            $percent = floatval(trim(str_replace('%', '', $additionalCost)));
            $additionalCost = (float) $deliveryCost * $percent / 100;
        }

        /**
         * Begin compability with msDelline2
         */

        $_SESSION['minishop2']['order']['delivery_notify'][$delivery->get('id')] = $this->modx->lexicon(
            'msrussianpost_time',
            array(
                'city' => $orderData['city'],
                'time' => $sendingData['time']
            )
        );

        /**
         * End compability with msDelline2
         */

        $totalAddCost = $deliveryCost + $weightCost + $additionalCost;

        if (
            $roundDir = $this->modx->getOption('msrussianpost_round_dir')
            and $roundQuantity = $this->modx->getOption('msrussianpost_round_quantity')
        ) {
            $roundCost = $totalAddCost / $roundQuantity;
            switch ($roundDir) {
                case self::ROUND_DIR_MATH:
                    $roundCost = round($roundCost);
                    break;

                case self::ROUND_DIR_DECR:
                    $roundCost = floor($roundCost);
                    break;

                case self::ROUND_DIR_INCR:
                    $roundCost = ceil($roundCost);
                    break;

                default:
                    break;
            }
            $totalAddCost = $roundCost * $roundQuantity;
        }

        return $cart_cost + $totalAddCost;
    }


    public function getTime() {

        $orderData = $this->ms2->order->get();
        /** @var msDelivery $delivery */
        if ($orderData['city'] and $delivery = $this->modx->getObject('msDelivery', $orderData['delivery'])) {
            $result = '';
            if ($delivery->get('class') !== $this->deliveryHandler) return false;

            $sendingName = $this->getSendingName($delivery);
            $cart = $this->ms2->cart->status();

            $sendingData = $this->getSendingData($sendingName, $cart['total_weight'], $orderData['city']);

            if ($sendingData['time'] != -1 ) {
                if ($this->config['returnTime']) {
                    $result = $this->modx->lexicon(
                        'msrussianpost_time',
                        array('time' => $sendingData['time'], 'city' => $orderData['city'])
                    );
                }
            } else {
                $result = $this->modx->lexicon('msrussianpost_time_nf', array('city' => $orderData['city']));
            }
            return $result;
        } else {
            return $this->modx->lexicon('msrussianpost_time_na');
        }

    }


    /**
     * @param msDelivery $delivery
     * @return string
     */
    public function getSendingName($delivery) {
        $props = $delivery->get('properties');
        if (isset($props['sendingName'])) return $props['sendingName'];
        else return '';
    }


//    public function getSendingName($fullname = '') {
//        return trim(str_replace($this->config['name_prefix'], '', $fullname));
//    }


    public function getSendingData($sendingName, $weight, $to, $valuation = 0) {

        $cost = 0;
        $time = 0;
        $weightSingle = 0;
        $maxCost = 0;
        $maxTime = 0;
        $maxWeight = 0;
        $foundSending = false;
        if (!$this->postCalc) $this->getPostCalc();

        if($this->config['weight_in_kg']) $weight = $weight * 1000;
        $weight = $weight ? $weight : $this->config['defaultWeight'];

        if (!$valuation) {
            $cart = $this->ms2->cart->status();
            $valuation = $cart['total_cost'];
        }

        $response = $this->postCalc->request($to, $weight, '', $valuation);

        if ($this->config['spam_modx_log']) {
            $this->modx->log(1, 'Sending name: '.$sendingName);
            $this->modx->log(1, 'To: '.$to);
            $this->modx->log(1, 'Response from PostCalc: ' . print_r($response, 1));
        }

        foreach ($response['Отправления'] as $sending) {
            if ($sendingName == $sending['Название']) {
                if (isset($sending['НетРасчета'])) {
                    $this->modx->log(xPDO::LOG_LEVEL_ERROR, 'To city: '.$to . ' ' . $sending['НетРасчета']);
                }
                $cost = $sending['Доставка'];
                $time = $sending['СрокДоставки'];
                $weightSingle = $sending['ПредельныйВес'];
                $foundSending = true;
            }
            if ($maxTime < $sending['СрокДоставки']) $maxTime = $sending['СрокДоставки'];
            if ($maxCost < $sending['Доставка']) {
                $maxCost = $sending['Доставка'];
                $maxWeight = $sending['ПредельныйВес'];
            }
        }
        if (!$weightSingle) $weightSingle = $maxWeight;
        if (!$time) $time = -1; //$maxTime;
        if (!$cost) $cost = 0; //$maxCost;

        /** Калькулятор возвращает стоимость уже с учетом количества мест */
//        $this->modx->log(1, "cost: $cost");
//        $count = (int) ($weight / $weightSingle);
//        if ($weight % $weightSingle) $count++;
//        $cost = $cost * $count;
//        $this->modx->log(1, "cost: $cost, count: $count, weight: $weight, weightSingle: $weightSingle");

        if (!$foundSending) {
            $this->modx->log(
                xPDO::LOG_LEVEL_ERROR,
                $this->modx->lexicon('msrussianpost_err_type', array('type' => $sendingName)
                )
            );
        }

        return array('time' => $time, 'cost' => $cost, 'weightSingle' => $weightSingle, 'weightTotal' => $weight);

    }

    public function getCities($limit = 0) {
        if (!$this->postCalc) $this->getPostCalc();
        return json_decode($this->postCalc->autocomplete('', $limit),1);

    }

    /**
     * @return null
     */
    public function getPostCalc()
    {
        if (!$this->postCalc) {
            $this->postCalc = $this->modx->getService('postcalcrussianpost', 'postCalcRussianPost', $this->config['corePath'].'libs/');
        }
        return $this->postCalc;
    }

}