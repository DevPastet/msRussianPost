<?php

/**
 * Class msRussianPostMainController
 */
abstract class msRussianPostMainController extends modExtraManagerController {
	/** @var msRussianPost $msRussianPost */
	public $msRussianPost;


	/**
	 * @return void
	 */
	public function initialize() {
		$corePath = $this->modx->getOption('msrussianpost_core_path', null, $this->modx->getOption('core_path') . 'components/msrussianpost/');
		require_once $corePath . 'model/msrussianpost/msrussianpost.class.php';

		$this->msRussianPost = new msRussianPost($this->modx);
		$this->addCss($this->msRussianPost->config['cssUrl'] . 'mgr/main.css');
		$this->addJavascript($this->msRussianPost->config['jsUrl'] . 'mgr/msrussianpost.js');
		$this->addHtml('
		<script type="text/javascript">
			msRussianPost.config = ' . $this->modx->toJSON($this->msRussianPost->config) . ';
			msRussianPost.config.connector_url = "' . $this->msRussianPost->config['connectorUrl'] . '";
		</script>
		');

		parent::initialize();
	}


	/**
	 * @return array
	 */
	public function getLanguageTopics() {
		return array('msrussianpost:default');
	}


	/**
	 * @return bool
	 */
	public function checkPermissions() {
		return true;
	}
}


/**
 * Class IndexManagerController
 */
class IndexManagerController extends msRussianPostMainController {

	/**
	 * @return string
	 */
	public static function getDefaultController() {
		return 'home';
	}
}