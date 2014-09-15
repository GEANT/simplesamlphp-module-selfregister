<?php
require_once 'UserCatalogue.php';

class sspmod_selfregister_Storage_LdapMod extends SimpleSAML_Auth_LDAP implements iUserCatalogue {

	private $adminDn = NULL;
	private $adminPw = NULL;
	private $searchDn = NULL;
	private $searchPw = NULL;
	private $searchBase = NULL;
	private $dnPattern = NULL;
	public $userIdAttr = NULL;
	private $attributes = NULL;
	private $objectClass = NULL;
	private $pswEncrypt = NULL;


	/**
	 * Construct
	 *
	 * @param array $authSourceconfig Configuration array for the selected authsource
	 * @param array $ldapWriteConfig Configuration array for the selected catalogue backend
	 * @param array $attributes The user attributes to be saved
	 */
	public function __construct($authSourceConfig, $ldapWriteConfig, $attributes) {

		$asc = SimpleSAML_Configuration::loadFromArray($authSourceConfig);
		parent::__construct(
			$asc->getString('hostname'),
			$asc->getBoolean('enable_tls', FALSE),
			$asc->getBoolean('debug', FALSE),
			$asc->getInteger('timeout', 0)
		);

		$this->searchBase = $asc->getString('search.base');
		$this->dnPattern = $asc->getString('dnpattern');
		$this->searchDn = $asc->getString('search.username', NULL);
		$this->searchPw = $asc->getString('search.password', NULL);

		$lwc = SimpleSAML_Configuration::loadFromArray($ldapWriteConfig);
		$this->adminDn = $lwc->getString('admin.dn');
		$this->adminPw = $lwc->getString('admin.pw');
		$this->objectClass = $lwc->getArray('objectClass');
		$this->userIdAttr = $lwc->getString('user.id.param', 'uid');
		$this->pswEncrypt = $lwc->getString('psw.encrypt', 'sha1');

		$this->attributes = $attributes;
	}


	public function addUser($userInfo){
		$rdn = $userInfo[$this->userIdAttr];
		$dn = $this->makeDn($rdn);
		$entry = $this->makeNewEntry($userInfo);
		$this->adminBindLdap();
		// FIXME: Use errorcode from ldap_add instead
		if($this->searchfordn($this->searchBase, $this->userIdAttr, $rdn, TRUE) ){
			throw new sspmod_selfregister_Error_UserException('uid_taken');
		}else{
			$this->addObject($dn, $entry);
		}
	}


	private function makeNewEntry($userInfo){
		$entry = array();
		$entry['objectClass'] = $this->objectClass;

		foreach($this->attributes as $attrName => $fieldName){
			switch ($attrName){
			case "userPassword":
				$entry[$attrName] = $this->encrypt_pass($userInfo[$attrName]);
				break;
			default:
				$entry[$attrName] = $userInfo[$attrName];
			}
		}
		return $entry;
	}



	private function encrypt_pass($plainPassword) {
		if($this->pswEncrypt == 'sha1') {
			$pw = $this->ssha1_crypt($plainPassword);
		}
		else if($this->pswEncrypt == 'md5') {
			$pw = $this->smd5_crypt($plainPassword);
		}
		else {
			$pw = $plainPassword;
		}
		return $pw;
	}



	// Make salted md5 hash of password
	private function smd5_crypt ($plainPassword) {
		$salt = '';
		while(strlen($salt)<8) $salt.=chr(rand(64,126));
		$smd5 = md5($plainPassword.$salt, TRUE);
		$return = "{SMD5}".base64_encode($smd5.$salt);
		return $return;
	}


	// Make salted sha1 hash of password
	private function ssha1_crypt ($plainPassword) {
		$salt = '';
		while(strlen($salt)<8) $salt.=chr(rand(64,126));
		$ssha1 = sha1($plainPassword.$salt, TRUE);
		$return = "{SSHA}".base64_encode($ssha1.$salt);
		return $return;
	}



	public function delUser($userId) {
		$dn = $this->makeDn($userId);
		$this->adminBindLdap();
		$this->deleteObject($dn);
	}



	public function changeUserPassword($userId, $newPlainPassword) {
		$pwHash = $this->encrypt_pass($newPlainPassword);
		$entry = array('userPassword' => $pwHash);
		$this->updateUser($userId, $entry);
	}




	public function updateUser($userId, $userInfo) {
		$dn = $this->makeDn($userId);
		$this->adminBindLdap();
		if($this->searchfordn($this->searchBase, $this->userIdAttr, $userId, TRUE) ){
			// User found in the catalog
			$this->replaceAttribute($dn, $userInfo);
		}else{
			// User not found
			throw new sspmod_selfregister_Error_UserException('uid_not_found', $userId);
		}
	}



	public function isRegistered($searchKeyName, $value){
		// FIXME: Bind as search or admin user to make sure we have rights for searching
		return (bool)$this->searchfordn($this->searchBase, $searchKeyName, $value, TRUE);
	}


	public function findAndGetUser($keyName, $value) {
		$userObjectDn = $this->searchfordn($this->searchBase, $keyName, $value);
		$userObject = $this->getAttributes($userObjectDn);

		//For simplicity, this only return first value of mutivalued attributes
		$user = array();
		foreach ($userObject as $attrName => $values) {
			if ($attrName == 'objectClass') {
			} else {
				$user[$attrName] = $values[0];
			}
		}
		return $user;
	}


	public function isValidPassword($userId, $plainPassword) {
		$dn = $this->makeDn($userId);
		return $this->bind($dn, $plainPassword);
	}


	private function makeDn($rdn){
		$dn = str_replace('%username%', $rdn, $this->dnPattern);
		return $dn;
	}


	/*
	private function makeDn($userinfo){
		$searchEnable = $this->lc->getBoolean('search.enable', TRUE);
		if(!$searchEnable) {
			$rdn = $userinfo[$this->userIdAttr];
			if(is_array($rdn)) {
				$rdn = $rdn[0];
			}
			$dn = str_replace('%username%', $rdn, $this->dnPattern);
		}
		else {
			$hookfile = SimpleSAML_Module::getModuleDir('selfregister').'/hooks/hook_attributes.php';
			include_once($hookfile);
			$dn = get_dn_hook($this->lc, $this->rc, $userinfo);
		}
		return $dn;
	}
	*/



	private function addObject($dn, $entry) {
		$result = ldap_add($this->ldap, $dn, $entry);
		if (!$result) {
			$error_msg = ldap_error($this->ldap);
			if($error_msg == 'Invalid syntax') {
				throw new sspmod_selfregister_Error_UserException('ldap_add_invalid_syntax');
			}
			else if($error_msg == 'Already exists') {
				throw new sspmod_selfregister_Error_UserException('id_taken');
			}
			else {
				throw new Exception($error_msg);
			}
		}
	}


	private function deleteObject($dn){
		$result = ldap_delete($this->ldap, $dn);
		// FIXME: Check returncode and make userExeption for no such object --fixed
		if (!$result) {
			$error_msg = ldap_error($this->ldap);
			if($error_msg == 'No such object') {
				throw new sspmod_selfregister_Error_UserException('user_not_exists');
			}
			else{
				throw new Exception($error_msg);
			}
		}
	}


	private function replaceAttribute($dn, $entry){
		$result = @ldap_mod_replace($this->ldap, $dn, $entry);
		if (!$result) {
			$error_msg = ldap_error($this->ldap);
			if($error_msg == 'No such object') {
				throw new sspmod_selfregister_Error_UserException('user_not_exists');
			}
			else if($error_msg == 'Naming violation') {
				throw new sspmod_selfregister_Error_UserException('id_violation');
			}
			else {
				throw new Exception($error_msg);
			}
		}
	}


	// FIXME: Deprecated, used LDAP:searchfordn instead
	private function searchForFirstDn($base, $keyName, $value) {
		$value = $this->ldap_escape($value, true);
		$filter = "($keyName=$value*)";
		$res = ldap_search($this->ldap, $base, $filter);
		if ($res) {
			if (ldap_count_entries($this->ldap, $res) > 0) {
				$entry = ldap_first_entry($this->ldap, $res);
				// FIXME: This is undefined when no object is found
				$dn = ldap_get_dn($this->ldap, $entry);
			}
		}
		return $dn;
	}



	private function searchOrAdminBindLdap() {
		if(!empty($this->searchDn) && !empty($this->searchPw)) {
			$result = $this->bind($this->searchDn, $this->searchPw);
		}
		if(!$result) {
			$result = $this->adminBindLdap();
		}
	}


	private function adminBindLdap() {
		$result = $this->bind($this->adminDn, $this->adminPw);
	}



	// FIXME: similar function in LDAP.php. Maybe use that.
	private function ldap_escape($str, $for_dn = false) {
		// see:
		// RFC2254
		// http://msdn.microsoft.com/en-us/library/ms675768(VS.85).aspx
		// http://www-03.ibm.com/systems/i/software/ldap/underdn.html

		if ($for_dn) {
			$metaChars = array(',','=', '+', '<','>',';', '\\', '"', '#');
		}
		else {
			$metaChars = array('*', '(', ')', '\\', chr(0));
		}
		$quotedMetaChars = array();
		foreach ($metaChars as $key => $value) {
			$quotedMetaChars[$key] = '\\'.str_pad(dechex(ord($value)), 2, '0');
		}
		$str = str_replace($metaChars,$quotedMetaChars,$str); //replace them
		return $str;
	}

}

?>
