<?php

class sspmod_selfregister_Util {

	public static function genFieldView($viewAttr){
		$hookfile = SimpleSAML_Module::getModuleDir('selfregister') . '/hooks/hook_attributes.php';
		include_once($hookfile);
		return genFieldView($viewAttr);
	}


	public static function checkLoggedAndSameAuth() {
		$session = SimpleSAML_Session::getInstance();
		if($session->isAuthenticated()) {
			$uregconf = SimpleSAML_Configuration::getConfig('module_selfregister.php');
			/* Get a reference to our authentication source. */
			$asId = $uregconf->getString('auth');
			if($session->getAuthority() == $asId) {
				return new SimpleSAML_Auth_Simple($asId);
			}
		}
		return false;
	}


	public static function processInput($fieldValues, $expectedValues){
		$hookfile = SimpleSAML_Module::getModuleDir('selfregister') . '/hooks/hook_attributes.php';
		include_once($hookfile);
		return processInput($fieldValues, $expectedValues);
	}


	public static function filterAsAttributes($asAttributes, $reviewAttr){
		$hookfile = SimpleSAML_Module::getModuleDir('selfregister') . '/hooks/hook_attributes.php';
		include_once($hookfile);
		return filterAsAttributes($asAttributes, $reviewAttr);
	}

	public static function validatePassword($fieldValues){
		if($fieldValues['pw1'] == $fieldValues['pw2']){
			return $fieldValues['pw1'];
		}else{
			throw new sspmod_selfregister_Error_UserException('err_retype_pw');
		}
	}

}

?>
