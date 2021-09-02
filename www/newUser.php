<?php

$config = SimpleSAML_Configuration::getInstance();
$uregconf = SimpleSAML_Configuration::getConfig('module_selfregister.php');
$tokenLifetime = $uregconf->getInteger('mailtoken.lifetime');
$viewAttr = $uregconf->getArray('attributes');
$formFields = $uregconf->getArray('formFields');
$systemName = array('%SNAME%' => $uregconf->getString('system.name') );
$session = \SimpleSAML\Session::getSessionFromRequest();
$state = $session->getDataOfType("\\SimpleSAML\\Auth\\State");
$state = array_pop($state);
$state = unserialize($state);
if (!$state) {
    // Retrieve URN
    try {
        $metadata = \SimpleSAML\Metadata\MetaDataStorageHandler::getMetadataHandler()
            ->getMetaData($_REQUEST['urn'] ?? null, 'saml20-sp-remote');
    }
    catch (\SimpleSAML\Error\MetadataNotFound $e) {
        // Fallback bad URN
        $metadata = \SimpleSAML\Metadata\MetaDataStorageHandler::getMetadataHandler()
            ->getMetaData(null, 'saml20-sp-remote');
    }
    $state['SPMetadata'] = $metadata;
    \SimpleSAML\Auth\State::saveState($state, 'selfregistrer:registration');
} else {
    $metadata = $state['SPMetadata'];
}

if (array_key_exists('emailreg', $_REQUEST)) {
    // Stage 2: User have submitted e-mail adress for registration
    try {
        $email = filter_input(INPUT_POST, 'emailreg', FILTER_VALIDATE_EMAIL);
        if (!$email) {
            $rawValue = isset($_REQUEST['emailreg']) ? $_REQUEST['emailreg'] : null;
            if (!$rawValue) {
                throw new sspmod_selfregister_Error_UserException(
                    'void_value',
                    'mail',
                    '',
                    'Validation of user input failed.'
                    . ' Field:' . 'mail'
                    . ' is empty'
                );
            } else {
                throw new sspmod_selfregister_Error_UserException(
                    'illegale_value',
                    'mail',
                    $rawValue,
                    'Validation of user input failed.'
                    . ' Field:' . 'mail'
                    . ' Value:' . $rawValue
                );
            }
        }

        $store = sspmod_selfregister_Storage_UserCatalogue::instantiateStorage();
        if ($store->isRegistered('mail', $email)) {
            $html = new SimpleSAML_XHTML_Template(
                $config,
                'selfregister:step5_mailUsed.tpl.php',
                'selfregister:selfregister'
            );
            $html->data['systemName'] = $systemName;
            $html->data['connectionLink'] = $metadata['AssertionConsumerService'];

            $html->show();
        } else {
            $tg = new SimpleSAML_Auth_TimeLimitedToken($tokenLifetime);
            $tg->addVerificationData($email);
            $newToken = $tg->generate_token();

            $url = SimpleSAML_Utilities::selfURL();
            $registerurl = SimpleSAML_Utilities::addURLparameter(
                $url,
                array(
                    'email' => $email,
                    'token' => $newToken,
                    'urn' => $metadata['entityid']
                )
            );

            $mailt = new SimpleSAML_XHTML_Template(
                $config,
                'selfregister:mail1_token.tpl.php',
                'selfregister:selfregister'
            );
            $mailt->data['email'] = $email;
            $mailt->data['registerurl'] = SimpleSAML_Utilities::addURLparameter($registerurl,
                [$mailt->getTranslator()->getLanguage()->getLanguageParameterName() => strtolower($mailt->getTranslator()->getLanguage()->getLanguage())]);
            $mailt->data['systemName'] = $systemName;

            $mailer = new sspmod_selfregister_XHTML_Mailer(
                $email,
                $uregconf->getString('mail.subject'),
                $uregconf->getString('mail.from'),
                null,
                $uregconf->getString('mail.replyto')
            );
            $mailer->setTemplate($mailt);
            $mailer->send();

            $html = new SimpleSAML_XHTML_Template(
                $config,
                'selfregister:step2_sent.tpl.php',
                'selfregister:selfregister'
            );
            $html->data['systemName'] = $systemName;
            $html->show();
        }
    } catch (sspmod_selfregister_Error_UserException $e) {
        $et = new SimpleSAML_XHTML_Template(
            $config,
            'selfregister:step1_email.tpl.php',
            'selfregister:selfregister'
        );
        $et->data['email'] = $_POST['emailreg'];
        $et->data['systemName'] = $systemName;
        $et->data['connectionLink'] = $metadata['AssertionConsumerService'];

        $error = $et->t(
            '{mob:register:'.$e->getMesgId().'}',
            $e->getTrVars()
        );
        $et->data['error'] = htmlspecialchars($error);

        $et->show();
    }
} elseif (array_key_exists('token', $_GET)) {
    // Stage 3: User access page from url in e-mail

    try {
        $email = filter_input(
            INPUT_GET,
            'email',
            FILTER_VALIDATE_EMAIL
        );
        if (!$email) {
            throw new SimpleSAML_Error_Exception(
                'E-mail parameter in request is lost'
            );
        }

        $tg = new SimpleSAML_Auth_TimeLimitedToken($tokenLifetime);
        $tg->addVerificationData($email);
        $token = $_REQUEST['token'];
        if (!$tg->validate_token($token)) {
            throw new sspmod_selfregister_Error_UserException('invalid_token');
        }

        $formGen = new sspmod_selfregister_XHTML_Form($formFields, 'newUser.php');

        $showFields = sspmod_selfregister_Util::genFieldView($viewAttr);
        $formGen->fieldsToShow($showFields);
        $formGen->setReadOnly('mail');

        $hidden = array(
            'emailconfirmed' => $email,
            'token' => $token);
        $formGen->addHiddenData($hidden);
        $formGen->setValues(
            array(
                'mail' => $email
            )
        );

        $formGen->setSubmitter('submit_change');
        $formHtml = $formGen->genFormHtml();

        $html = new SimpleSAML_XHTML_Template(
            $config,
            'selfregister:step3_register.tpl.php',
            'selfregister:selfregister'
        );
        $html->data['formHtml'] = $formHtml;
        $html->data['emailconfirmed'] = $email;
        $html->data['token'] = $token;
        $html->data['connectionLink'] = $metadata['AssertionConsumerService'];
        $html->data['urn'] = $metadata['entityid'];

        $html->show();
    } catch (sspmod_selfregister_Error_UserException $e) {
        // Invalid token
        $terr = new SimpleSAML_XHTML_Template(
            $config,
            'selfregister:step1_email.tpl.php',
            'selfregister:selfregister'
        );

        $error = $terr->t(
            '{mob:register:'.$e->getMesgId().'}',
            $e->getTrVars()
        );
        $terr->data['error'] = htmlspecialchars($error);
        $terr->data['systemName'] = $systemName;
        $terr->data['connectionLink'] = $metadata['AssertionConsumerService'];
        $terr->show();
    }
} elseif (array_key_exists('sender', $_POST)) {
    try {
         // Add or update user object
         $listValidate = sspmod_selfregister_Util::genFieldView($viewAttr);
         $validator = new sspmod_selfregister_Registration_Validation(
             $formFields,
             $listValidate
         );
         $validValues = $validator->validateInput();

        $userInfo = sspmod_selfregister_Util::processInput($validValues, $viewAttr);

         $store = sspmod_selfregister_Storage_UserCatalogue::instantiateStorage();

         $store->addUser($userInfo);

         $html = new SimpleSAML_XHTML_Template(
             $config,
             'selfregister:step4_complete.tpl.php',
             'selfregister:selfregister'
         );

         $html->data['systemName'] = $systemName;
         $html->data['redirecturl'] = $metadata['AssertionConsumerService'];
         $html->show();
    } catch (sspmod_selfregister_Error_UserException $e) {
         // Some user error detected
         $formGen = new sspmod_selfregister_XHTML_Form($formFields, 'newUser.php');

         $showFields = sspmod_selfregister_Util::genFieldView($viewAttr);
         $formGen->fieldsToShow($showFields);
         $formGen->setReadOnly('mail');

         $values = $validator->getRawInput();

         $hidden = array();
         $values['mail'] = $hidden['emailconfirmed'] = $_REQUEST['emailconfirmed'];
         $hidden['token'] = $_REQUEST['token'];
         $formGen->addHiddenData($hidden);
         $values['pw1'] = '';
         $values['pw2'] = '';

         $formGen->setValues($values);
         $formGen->setSubmitter('submit_change');
         $formHtml = $formGen->genFormHtml();

         $html = new SimpleSAML_XHTML_Template(
             $config,
             'selfregister:step3_register.tpl.php',
             'selfregister:selfregister'
         );
         $html->data['formHtml'] = $formHtml;

        $html->data['emailconfirmed'] = $hidden['emailconfirmed'];
        $html->data['token'] = $hidden['token'];
        $html->data['uid'] = $_REQUEST['uid'];
        $html->data['givenname'] = $_REQUEST['givenName'];
        $html->data['sn'] = $_REQUEST['sn'];
        $html->data['urn'] = $metadata['entityid'];
        $html->data['connectionLink'] = $metadata['AssertionConsumerService'];

        $error = $html->t(
            '{mob:register:'.$e->getMesgId().'}',
            $e->getTrVars()
        );

        $html->data['error'] = htmlspecialchars($error);
        $html->show();
    }
} else {
    // Stage 1: New user clean access
    $html = new SimpleSAML_XHTML_Template(
        $config,
        'selfregister:step1_email.tpl.php',
        'selfregister:selfregister'
    );
    $html->data['systemName'] = $systemName;
    $html->data['connectionLink'] = $metadata['AssertionConsumerService'];

    $logged_and_same_auth = sspmod_selfregister_Util::checkLoggedAndSameAuth();
    if ($logged_and_same_auth) {
        $html->data['logouturl'] = $logged_and_same_auth->getLogoutURL();
    }
    $html->show();
}
