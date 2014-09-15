<?php

$config = SimpleSAML_Configuration::getInstance();
$uregconf = SimpleSAML_Configuration::getConfig('module_selfregister.php');
$tokenLifetime = $uregconf->getInteger('mailtoken.lifetime');
$viewAttr = $uregconf->getArray('attributes');
$formFields = $uregconf->getArray('formFields');
$store = sspmod_selfregister_Storage_UserCatalogue::instantiateStorage();


if (array_key_exists('emailreg', $_REQUEST)) {
	// Stage 2: User have submitted e-mail adress for password recovery
	try {
		$email = filter_input(INPUT_POST, 'emailreg', FILTER_VALIDATE_EMAIL);
		if(!$email){
			$rawValue = isset($_REQUEST['emailreg'])?$_REQUEST['emailreg']:NULL;
			if(!$rawValue){
				throw new sspmod_selfregister_Error_UserException(
					'void_value',
					'mail',
					'',
					'Validation of user input failed.'
					.' Field:'.'mail'
					.' is empty');
			}else{
				throw new sspmod_selfregister_Error_UserException(
					'illegale_value',
					'mail',
					$rawValue,
					'Validation of user input failed.'
					.' Field:'.'mail'
					.' Value:'.$rawValue);
			}
		}

		if(!$store->isRegistered('mail', $email) ) {
			throw new sspmod_selfregister_Error_UserException(
				'email_not_found',
				$email,
				'',
				'Try to reset password, but mail address not found: '.$email
			);
		}

		$tg = new SimpleSAML_Auth_TimeLimitedToken($tokenLifetime);
		$tg->addVerificationData($email);
		$newToken = $tg->generate_token();

		$url = SimpleSAML_Utilities::selfURL();

		$registerurl = SimpleSAML_Utilities::addURLparameter(
			$url,
			array(
				'email' => $email,
				'token' => $newToken));

		$mailt = new SimpleSAML_XHTML_Template(
			$config,
			'selfregister:lostPasswordMail_token.tpl.php',
			'selfregister:selfregister');

		$mailt->data['registerurl'] = $registerurl;
		$systemName = array('%SNAME%' => $uregconf->getString('system.name') );
		$mailt->data['systemName'] = $systemName;

		$mailer = new sspmod_selfregister_XHTML_Mailer(
			$email,
			$uregconf->getString('mail.subject'),
			$uregconf->getString('mail.from'),
			NULL,
			$uregconf->getString('mail.replyto'));
		$mailer->setTemplate($mailt);
		$mailer->send();

		$html = new SimpleSAML_XHTML_Template(
			$config,
			'selfregister:lostPassword_sent.tpl.php',
			'selfregister:selfregister');
		$html->show();
	}catch(sspmod_selfregister_Error_UserException $e){
		$et = new SimpleSAML_XHTML_Template(
			$config,
			'selfregister:lostPassword_email.tpl.php',
			'selfregister:selfregister');
		$et->data['email'] = $_POST['emailreg'];

		$error = $et->t(
			$e->getMesgId(),
			$e->getTrVars()
		);
		$et->data['error'] = htmlspecialchars($error);

		$et->show();
	}

} elseif(array_key_exists('token', $_GET)) {
	// Stage 3: User access page from url in e-mail
	try{
		$email = filter_input(
			INPUT_GET,
			'email',
			FILTER_VALIDATE_EMAIL);
		if(!$email)
			throw new SimpleSAML_Error_Exception(
				'E-mail parameter in request is lost');

		$tg = new SimpleSAML_Auth_TimeLimitedToken($tokenLifetime);
		$tg->addVerificationData($email);
		$token = $_REQUEST['token'];
		if (!$tg->validate_token($token))
			throw new sspmod_selfregister_Error_UserException('invalid_token');

		$formGen = new sspmod_selfregister_XHTML_Form($formFields, 'lostPassword.php');

		$showFields = array('pw1', 'pw2');
		$formGen->fieldsToShow($showFields);

		$userValues = $store->findAndGetUser('mail', $email);

		$hidden = array(
			'emailconfirmed' => $email,
			'token' => $token);
		$formGen->addHiddenData($hidden);
		$formGen->setSubmitter('submit_change');
		$formHtml = $formGen->genFormHtml();

		$html = new SimpleSAML_XHTML_Template(
			$config,
			'selfregister:lostPassword_changePassword.tpl.php',
			'selfregister:selfregister');
		$html->data['formHtml'] = $formHtml;
		$html->data['uid'] = $userValues[$store->userIdAttr];
		$html->show();
	} catch(sspmod_selfregister_Error_UserException $e) {
		// Invalid token
		$terr = new SimpleSAML_XHTML_Template(
			$config,
			'selfregister:lostPassword_email.tpl.php',
			'selfregister:selfregister');

		$error = $terr->t(
			$e->getMesgId(),
			$e->getTrVars()
		);
		$terr->data['error'] = htmlspecialchar($error);

		$terr->show();
	}

  } elseif(array_key_exists('sender', $_POST)) {
	  try {
		  // Add or update user object
		  $listValidate = array('pw1', 'pw2');
		  $validator = new sspmod_selfregister_Registration_Validation(
			  $formFields,
			  $listValidate);


		  $email = filter_input(
			  INPUT_POST,
			  'emailconfirmed',
			  FILTER_VALIDATE_EMAIL);
		  if(!$email)
			  throw new SimpleSAML_Error_Exception(
				  'E-mail parameter in request is lost');

		  $tg = new SimpleSAML_Auth_TimeLimitedToken($tokenLifetime);
		  $tg->addVerificationData($email);
		  $token = $_REQUEST['token'];
		  if (!$tg->validate_token($token))
			  throw new sspmod_selfregister_Error_UserException('invalid_token');

		  $userValues = $store->findAndGetUser('mail', $email);
		  $validValues = $validator->validateInput();
		  $newPw = sspmod_selfregister_Util::validatePassword($validValues);
		  $store->changeUserPassword($userValues[$store->userIdAttr], $newPw);

		  $html = new SimpleSAML_XHTML_Template(
			  $config,
			  'selfregister:lostPassword_complete.tpl.php',
			  'selfregister:selfregister');
		  $html->show();
	  } catch(sspmod_selfregister_Error_UserException $e) {
		  // Some user error detected
		  $formGen = new sspmod_selfregister_XHTML_Form($formFields, 'lostPassword.php');

		  $showFields = array('pw1', 'pw2');
		  $formGen->fieldsToShow($showFields);

		  $hidden = array();
		  $hidden['emailconfirmed'] = $_REQUEST['emailconfirmed'];
		  $hidden['token'] = $_REQUEST['token'];
		  $formGen->addHiddenData($hidden);

		  $formGen->setValues(array($store->userIdAttr => $_REQUEST[$store->userIdAttr]));
		  $formGen->setSubmitter('submit_change');
		  $formHtml = $formGen->genFormHtml();

		  $html = new SimpleSAML_XHTML_Template(
			  $config,
			  'selfregister:lostPassword_changePassword.tpl.php',
			  'selfregister:selfregister');
		  $html->data['formHtml'] = $formHtml;
		  $html->data['uid'] = $userValues[$store->userIdAttr];

		  $error = $html->t(
			  $e->getMesgId(),
			  $e->getTrVars()
		  );

		  $html->data['error'] = htmlspecialchars($error);
		  $html->show();
	  }
	} else {
	// Stage 1: User access page to enter mail address for pasword recovery
	$html = new SimpleSAML_XHTML_Template(
		$config,
		'selfregister:lostPassword_email.tpl.php',
		'selfregister:selfregister');
	$html->show();
}

?>