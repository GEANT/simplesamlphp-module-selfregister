<?php

$config = SimpleSAML_Configuration::getInstance();
$session = SimpleSAML_Session::getSessionFromRequest();
$uregconf = SimpleSAML_Configuration::getConfig('module_selfregister.php');
/* Get a reference to our authentication source. */
$asId = $uregconf->getString('auth');

$links = array();


	$links[] = array(
		'href' => SimpleSAML_Module::getModuleURL('selfregister/newUser.php'),
		'text' => '{selfregister:selfregister:link_newuser}',
	);

	$links[] = array(
		'href' => SimpleSAML_Module::getModuleURL('selfregister/lostPassword.php'),
		'text' => '{selfregister:selfregister:link_lostpw}',
	);


	$as = new SimpleSAML_Auth_Simple($asId);
	if ($as->isAuthenticated()) {
		$links[] = array(
			'href' => SimpleSAML_Module::getModuleURL('selfregister/reviewUser.php'),
			'text' => '{selfregister:selfregister:link_review}',
		);
		$links[] = array(
			'href' => SimpleSAML_Module::getModuleURL('selfregister/changePassword.php'),
			'text' => '{selfregister:selfregister:link_changepw}',
		);
		$links[] = array(
			'href' => SimpleSAML_Module::getModuleURL('selfregister/delUser.php'),
			'text' => '{selfregister:selfregister:link_deluser}',
		);
		$links[] = array(
			'href' => $as->getLogoutURL(),
			'text' => '{status:logout}',
		);
	}
	else {
		$links[] = array(
			'href' => SimpleSAML_Module::getModuleURL('selfregister/reviewUser.php'),
			'text' => '{selfregister:selfregister:link_enter}',
		);
	}

$html = new SimpleSAML_XHTML_Template(
		$config,
		'selfregister:index.tpl.php',
		'selfregister:selfregister');
$html->data['source'] = $asId;
$html->data['links'] = $links;

if(array_key_exists('status', $_GET) && $_GET['status'] == 'deleted') {
	$html->data['userMessage'] = 'message_userdel';
}


$html->show();

?>
