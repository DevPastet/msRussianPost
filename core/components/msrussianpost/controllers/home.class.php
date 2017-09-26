<?php

/**
 * The home manager controller for msRussianPost.
 *
 */
class msRussianPostHomeManagerController extends msRussianPostMainController {
	/* @var msRussianPost $msRussianPost */
	public $msRussianPost;


	/**
	 * @param array $scriptProperties
	 */
	public function process(array $scriptProperties = array()) {
	}


	/**
	 * @return null|string
	 */
	public function getPageTitle() {
		return $this->modx->lexicon('msrussianpost');
	}


	/**
	 * @return void
	 */
	public function loadCustomCssJs() {
		$this->addCss($this->msRussianPost->config['cssUrl'] . 'mgr/main.css');
		$this->addCss($this->msRussianPost->config['cssUrl'] . 'mgr/bootstrap.buttons.css');
		$this->addJavascript($this->msRussianPost->config['jsUrl'] . 'mgr/misc/utils.js');
		$this->addJavascript($this->msRussianPost->config['jsUrl'] . 'mgr/widgets/items.grid.js');
		$this->addJavascript($this->msRussianPost->config['jsUrl'] . 'mgr/widgets/items.windows.js');
		$this->addJavascript($this->msRussianPost->config['jsUrl'] . 'mgr/widgets/home.panel.js');
		$this->addJavascript($this->msRussianPost->config['jsUrl'] . 'mgr/sections/home.js');
		$this->addHtml('<script type="text/javascript">
		Ext.onReady(function() {
			MODx.load({ xtype: "msrussianpost-page-home"});
		});
		</script>');
	}


	/**
	 * @return string
	 */
	public function getTemplateFile() {
		return $this->msRussianPost->config['templatesPath'] . 'home.tpl';
	}
}