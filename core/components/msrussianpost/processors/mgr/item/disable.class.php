<?php

/**
 * Disable an Item
 */
class msRussianPostItemDisableProcessor extends modObjectProcessor {
	public $objectType = 'msRussianPostItem';
	public $classKey = 'msRussianPostItem';
	public $languageTopics = array('msrussianpost');
	//public $permission = 'save';


	/**
	 * @return array|string
	 */
	public function process() {
		if (!$this->checkPermissions()) {
			return $this->failure($this->modx->lexicon('access_denied'));
		}

		$ids = $this->modx->fromJSON($this->getProperty('ids'));
		if (empty($ids)) {
			return $this->failure($this->modx->lexicon('msrussianpost_item_err_ns'));
		}

		foreach ($ids as $id) {
			/** @var msRussianPostItem $object */
			if (!$object = $this->modx->getObject($this->classKey, $id)) {
				return $this->failure($this->modx->lexicon('msrussianpost_item_err_nf'));
			}

			$object->set('active', false);
			$object->save();
		}

		return $this->success();
	}

}

return 'msRussianPostItemDisableProcessor';
