<?php

require_once (Mage::getBaseDir('lib') . DS . 'GoogleAuthenticator' . DS . 'PHPGangsta' . DS . 'GoogleAuthenticator.php');

class HE_TwoFactorAuth_Model_Validate_Google extends HE_TwoFactorAuth_Model_Validate
{

    public function __construct()
    {
        $this->_shouldLog = Mage::helper('he_twofactorauth')->shouldLog();
    }

    /*
     * HOTP - counter based
     * TOTP - time based
     */
    public function getToken($username, $tokentype = "TOTP")
    {
        $token = $this->setUser($username, $tokentype);
        if ($this->_shouldLog) {
            Mage::log("token = " . var_export($token, true));
        }

        $user = Mage::getModel('admin/user')->loadByUsername($username);
        $user->setTwofactorauthToken($token);
        //$user->save(); //password gets messed up after saving?!
    }


    public function isValid() {
        return true; 
    }

    /* 
     * generates and returns a new shared secret
     */
    public function generateSecret() { 
        $ga = new PHPGangsta_GoogleAuthenticator();
        $secret = $ga->createSecret();
        return $secret;
    }


    /* 
     * generates and returns QR code URL from google
     */
    public function generateQRCodeUrl($secret, $username) { 
        if ((empty($secret)) || (empty($username))) { return; }

        $ga = new PHPGangsta_GoogleAuthenticator();
        $url = $ga->getQRCodeGoogleUrl($username, $secret);
        return $url;
    }


    /*
     * verifies the code using TOTP
     */

    public function validateCode($code) {
        if (empty($code)) { return; }
        Mage::log("Google - validateCode: " . $code, 0, "two_factor_auth.log");

        // get user's shared secret
        $user            = Mage::getSingleton('admin/session')->getUser();
        $admin_user      = Mage::getModel('admin/user')->load($user->getId());

        $ga = new PHPGangsta_GoogleAuthenticator();
        $secret = Mage::helper('core')->decrypt($admin_user->twofactor_google_secret);
        return $ga->verifyCode($secret, $code, 1);
    }



    /*
     * abstract function in GoogleAuthenticator, needs to be defined here TODO
     */
    function getDataBad($username,$index=NULL) // this was causing problems, not sure why...
    {
        $user = Mage::getModel('admin/user')->loadByUsername($username);
        return $user->getTwofactorauthToken() == null ? false : $user->getTwofactorauthToken();
    }

    /*
     * abstract function in GoogleAuthenticator, needs to be defined here
     */
    function putData($username, $data)
    {
        $user = Mage::getModel('admin/user')->loadByUsername($username);
        $user->setTwofactorauthToken("test");
        $user->save();
    }

    /*
     * abstract function in GoogleAuthenticator, needs to be defined here
     */
    function getUsers()
    {
    }
}