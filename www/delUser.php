<?php

// Configuration
$config = SimpleSAML_Configuration::getInstance();
$uregconf = SimpleSAML_Configuration::getConfig('module_selfregister.php');
$store = sspmod_selfregister_Storage_UserCatalogue::instantiateStorage();

/* Get a reference to our authentication source. */
$asId = $uregconf->getString('auth');
$as = new SimpleSAML_Auth_Simple($asId);
/* Require the usr to be authentcated. */
$as->requireAuth();

/* Retrieve attributes of the user. */
$attributes = $as->getAttributes();
$session = SimpleSAML_Session::getSessionFromRequest();
$data = $session->getData('selfregister:updated', 'attributes');
if ($data !== NULL) {
	$attributes = $data;
}

$formFields = $uregconf->getArray('formFields');
$reviewAttr = $uregconf->getArray('attributes');

$showFields = array();

foreach ($formFields as $name => $field) {
	if(array_key_exists('show',$field['layout']) && $field['layout']['show']) {
		$showFields[] = $name;
	}
}
$readOnlyFields = $showFields;

$formGen = new sspmod_selfregister_XHTML_Form($formFields, 'delUser.php');
$formGen->fieldsToShow($showFields);
$formGen->setReadOnly($readOnlyFields);

$html = new SimpleSAML_XHTML_Template(
	$config,
	'selfregister:deluser.tpl.php',
	'selfregister:selfregister');


if(array_key_exists('sender', $_POST)) {
	try{
		// Delete user object

		$store->delUser($attributes[$store->userIdAttr][0]);

		// Now when a User delete himself sucesfully, System log out him.
		// In the future when admin delete a user a msg will be showed
		// $html->data['userMessage'] = 'message_userdel';
		$as->logout(SimpleSAML_Module::getModuleURL('selfregister/index.php?status=deleted'));

	}catch(sspmod_selfregister_Error_UserException $e){
		// Some user error detected

		$error = $html->t(
			$e->getMesgId(),
			$e->getTrVars()
		);

		$html->data['error'] = htmlspecialchars($error);
	}
}elseif(array_key_exists('logout', $_GET)) {
	$as->logout(SimpleSAML_Module::getModuleURL('selfregister/index.php'));
} else {
	// The GET access this endpoint
	$values = sspmod_selfregister_Util::filterAsAttributes($attributes, $reviewAttr);
}

$formGen->setValues($values);
$formGen->setSubmitter('submit_delete');
$formHtml = $formGen->genFormHtml();
$html->data['formHtml'] = $formHtml;

$html->data['givenname'] = $values["givenName"];
$html->data['sn'] = $values["sn"];
$html->data['mail'] = $values["mail"];

$html->data['uid'] = $attributes[$store->userIdAttr][0];
$html->show();

?>