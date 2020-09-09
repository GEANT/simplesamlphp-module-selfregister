<?php

interface iUserCatalogue
{

    public function addUser($userInfo);
    public function updateUser($userId, $userInfo);
    public function changeUserPassword($userId, $newPlainPassword);
    public function isRegistered($searchKeyName, $value);
    //public function isValidPassword($userId, $plainPassword);
    // Exception for no or several users found
    public function findAndGetUser($searchKeyName, $value);
    public function delUser($userId);
}



/**
 * User catalogue object factory
 *
 * This class is aware of configuration files and will use them.
 */
class sspmod_selfregister_Storage_UserCatalogue
{

    public static function instantiateStorage()
    {
        $selStorage = self::getStorageSelection();
        if ($selStorage == 'LdapMod') {
            return self::instantiateLdapStorage();
        } elseif ($selStorage == 'SQL') {
            return self::instantiateSqlStorage();
        } elseif ($selStorage == 'AwsSimpleDb') {
        }
    }


    private static function getStorageSelection()
    {
        //FIXME all private functions call this - can  this be added once at the top?
        $rc = SimpleSAML_Configuration::getConfig('module_selfregister.php');
        $storeSel = $rc->getString('storage.backend');
        return $storeSel;
    }


    // Needed for SQL
    private static function getAuthSourceSelection()
    {
            $rc = SimpleSAML_Configuration::getConfig('module_selfregister.php');
            $authSource = $rc->getString('auth');
            return $authSource;
    }


    public static function getSelectedStorageConfig()
    {
        // FIXME: In config file. Use same name for conf backend array as storage.backend value
        $selStorage = self::getStorageSelection();
        $selfRegConf = SimpleSAML_Configuration::getConfig('module_selfregister.php');
        if ($selStorage == 'LdapMod') {
            return $selfRegConf->getArray('ldap');
        } elseif ($selStorage == 'SQL') {
            return $selfRegConf->getArray($selAuthSource);
        } elseif ($selStorage == 'AwsSimpleDb') {
        }
    }



    private static function instantiateLdapStorage()
    {
        $selfRegConf = SimpleSAML_Configuration::getConfig('module_selfregister.php');
        $writeConf = $selfRegConf->getArray('ldap');

        $auth = $selfRegConf->getString('auth');
        $authsources = SimpleSAML_Configuration::getConfig('authsources.php');
        $authConf = $authsources->getArray($auth);

        $attributes = $selfRegConf->getArray('attributes');
        $ldap = new sspmod_selfregister_Storage_LdapMod($authConf, $writeConf, $attributes);

        return $ldap;
    }

    private static function instantiateSqlStorage()
    {
            $authsources = SimpleSAML_Configuration::getConfig('authsources.php');
            $selAuthSource = self::getAuthSourceSelection();
            $authConf = $authsources->getArray($selAuthSource);
            $selfRegConf = SimpleSAML_Configuration::getConfig('module_selfregister.php');
            $attributes = $selfRegConf->getArray('attributes');
            $writeConf = $selfRegConf->getArray('sql');
            $hashAlgo = $selfRegConf->getString('hash.algo');
            $sql = new sspmod_selfregister_Storage_SqlMod($authConf, $writeConf, $attributes, $hashAlgo);
            return $sql;
    }
}
