<?php
class sspmod_selfregister_Storage_SqlMod implements iUserCatalogue {

	private $attributes = NULL;
	public $userIdAttr = NULL;
	private $dbh = NULL;


	/**
	 * Construct
	 *
	 * @param array $authSourceconfig Configuration array for the selected authsource
	 * @param array $writeConfig Configuration array for the selected catalogue backend
	 * @param array $attributes The user attributes to be saved
	 */
	public function __construct($authSourceConfig, $writeConfig, $attributes, $hashAlgo) {
		$asc = SimpleSAML_Configuration::loadFromArray($authSourceConfig);
	
		try {
			$this->dbh = new PDO($asc->getString('dsn'), $asc->getString('username'),  $asc->getString('password'));
		} catch (PDOException $e) {
			throw new Exception($e->getMessage());
		} 

		$driver = explode(':', $asc->getString('dsn'), 2);
		$driver = strtolower($driver[0]);

		/* Driver specific initialization. */
		switch ($driver) {
			case 'mysql':
				/* Use UTF-8. */
				$this->dbh->exec("SET NAMES utf8");
				$this->dbh->exec("SET CHARACTER SET utf8;");
				break;
			case 'pgsql':
				/* Use UTF-8. */
				$this->dbh->exec("SET NAMES 'UTF8'");
				break;
		}
		$this->dbh->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING );

		$this->attributes = $attributes;
		$this->hashAlgo = $hashAlgo;
		$this->salt = bin2hex(SimpleSAML_Utilities::generateRandomBytes(64));
		$wc = SimpleSAML_Configuration::loadFromArray($writeConfig);
		$this->userIdAttr = $wc->getString('user.id.param');
		$this->userIdAutoCreate = $wc->getBoolean('user.id.autocreate', false);
		//$this->userIdAutoCreateLength = $wc->getInteger('user.id.autocreate.length', 9);
	}

	public function addUser($entry){
		SimpleSAML_Logger::debug('entry var: ' . var_export($entry, 1));	
		if ($this->isRegistered('email', $entry['email'])) {
			throw new sspmod_selfregister_Error_UserException('email_taken');

		} elseif($this->isRegistered('userid', $entry['username'])) {
			throw new sspmod_selfregister_Error_UserException('uid_taken');

		} else {

			if ($this->userIdAutoCreate) {
			    $userid = $this->createUniqueUserId();
			} else {
			    $userid = $entry['username'];
			}
			$sth = $this->dbh->prepare("
				INSERT INTO users
				(userid, email, password, salt, firstname, lastname, created, updated)
				VALUES
				(?, ?, ?, ?, ?, ?, now(), now())
			");
			$sth->execute(array(
				$userid, strtolower($entry['email']), $this->hash_pass($entry['userPassword']), $this->salt, $entry['firstname'], $entry['lastname']
			));
		}
	}

	public function createUniqueUserId() {
		$userid = $this->createUserId();
		while ($this->isRegistered($this->userIdAttr, $userid)) {
			$userid = $this->createUserId($string);
		}
		return $userid;
	}

	private function createUserId() {
		// Ideas cf. http://stackoverflow.com/questions/2799495/generate-unique-random-alphanumeric-characters-that-are-7-characters-long

		// Avoid chars that might be mistaken by a human (no [oO0il1]).
		// Also is easily configurable wrt string length.
		// Duplicates w/ deleted accounts are more likely over time, though.
		// (All non-transient SAML identifiers require non-reassignment: subject-id, pairwise-id, persistent NameID, etc.)
		//return substr( str_shuffle('abcdefghjkmnpqrstuvwxyz23456789'), 0, $this->userIdAutoCreateLength); // configurable length

		// Makes duplicates w/ deleted accounts less likely, but can't rule those out completely.
		// Not easily configureable wrt string length.
		//return base_convert(mt_rand(100, 999) . intval(microtime(true) * 10), 10, 36); // 9 chars

		// Time-based, so no duplicate ids even with deleted accounts.
		return uniqid(); // 13 chars
	}

	private function hash_pass($plainPassword) {
		$salt = $this->salt;

		if(!in_array($this->hashAlgo, hash_algos())) {
			throw new Exception ('Hash algorithm ' . $this->hashAlgo . ' not supported');
		}
		
		$hash =  hash($this->hashAlgo, ($salt.$plainPassword));

		return $hash;
	}

	public function delUser($userid) {
		$sth = $this->dbh->prepare("
			DELETE FROM users
			WHERE userid = :userid
		");
		$sth->execute(array(':userid' => $userid));
		return ($sth->rowCount() > 0);
	}

	public function changeUserPassword($userid, $newPlainPassword) {
		if (!$this->isRegistered($this->userIdAttr, $userid)) {
			throw new sspmod_selfregister_Error_UserException('userid_not_found', $userid);
		}
		$sth = $this->dbh->prepare("
			UPDATE users
			SET
				password = :password,
				salt = :salt,
				updated  = now()
			WHERE
				userid = :userid 
		");
		return $sth->execute(array(
			':password' => $this->hash_pass($newPlainPassword),
			':userid' => $userid,
			':salt' => $this->salt,
		));
	}

	public function updateUser($userid, $userInfo) {
		if (!$this->isRegistered($this->userIdAttr, $userid)) {
			throw new sspmod_selfregister_Error_UserException('userid_not_found', $userid);
		}

		$sth = $this->dbh->prepare("
			UPDATE users
			SET
				firstname = :firstname,
				lastname = :lastname,
				email = :mail,
				updated = now()
			WHERE
				userid = :userid 
		");
		return $sth->execute(array(
			':firstname' => $userInfo['firstname'], 
			':lastname' => $userInfo['lastname'], 
			':mail' => $userInfo['email'], 
			':userid' => $userid
		));
	}

	public function isRegistered($searchKeyName, $value) {
		$user = $this->findAndGetUser($searchKeyName, $value);
		return isset($user[$this->userIdAttr]);
	}

	public function isRegistered2($attribute, $value) {
		// less crappy findAndGetUser		


	}


	public function findAndGetUser($searchKeyName, $value) {
		$keyName = '';
		if (preg_match('/^e?mail$/i', $searchKeyName)) {
			$keyName = 'email';
		} elseif ($searchKeyName == $this->userIdAttr) {
			$keyName = $searchKeyName;
		}

		if (empty($keyName)) {
			throw new Exception('Unknown attribute');
		}

		$sth = $this->dbh->prepare(sprintf("
			SELECT firstname, lastname, email, userid
			FROM users
			WHERE %s = :value
		", $keyName));
		$sth->execute(array(':value' => $value));
		return $sth->fetch(PDO::FETCH_ASSOC);
	}

}

?>
