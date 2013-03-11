<?php
/**
 * Class wraps functions for communiction with the 1000° WordPress API
 * @copyright (c) 2013, 1000°DIGITAL Leipzig GmbH
 * @author Karsten Lemme <karsten.lemme@1000grad.de>
 */
class EpaperApikeyApi 
{
    private $apikeyApiClient;
    private $ApikeyApiWsdl;
    private $apikey;
    private $epaperOptions;
    private $isRegistered;
    
    public function __construct() 
    {
        $this->epaperOptions = get_option("plugin_epaper_options");
        $this->apikey = $this->epaperOptions['apikey'];
        $this->ApikeyApiWsdl = $this->epaperOptions['wordpressapi'];
        $this->_isRegistered();

    }
    
    /**
     *  Shall validate if plugin is registered
     */
    private function _isRegistered ()
    {
        if (isset($this->apikey) && ($this->apikey != "")) {
            $this->isRegistered = true;
        } else {
            $this->isRegistered = false;
        }        
    }
    
     /**
     * Connect
     */
    public function epaperApikeyApiConnect() 
    {
        $wsdl = $this->ApikeyApiWsdl;      
        try {
            $this->apikeyApiClient = new SoapClient($wsdl , array());
            return true;            
		} catch (SoapFault $e) {
            _e("Error: Could not connect to API.",'1000grad-epaper');
            echo $e->getMessage(); 
        }
          return false;
    }
    
     /**
     * Versionsabfrage
     */
    public function getApikeyApiVersion() 
    {  
        $this->epaperApikeyApiConnect();
        try {
            $version = $this->apikeyApiClient->getVersion();
            return $version;            
		} catch (SoapFault $e) {
            _e("Error with Apikey API.",'1000grad-epaper');
            echo $e->getMessage(); 
        }
        return false;
    }
    
     /**
     * Funktionsabfrage
     */
    public function getApikeyApiFunctions() 
    {  
        $this->epaperApikeyApiConnect();
        try {
            $functions = $this->apikeyApiClient->__getFunctions();
            return $functions;            
		} catch (SoapFault $e) {
            _e("Error with Apikey API.",'1000grad-epaper');
            echo $e->getMessage(); 
        }
        return false;
    }
    
     /**
     * Registrierungsprozess schickt Daten an den ePaper Server, der verschickt dann Bestaetigungsmail
     */
    public function getRegistrationCodeByEmail ($email, $text, $wordpress, $phpupload,
                    $phptime, $wordpresscode, $agb, $newsletter, $version_wordpress ,$version_php, $language)
    {
        $this->epaperApikeyApiConnect();
        try {
            $res = $this->apikeyApiClient->getRegistrationCodeByEmail($email, $text, $wordpress, $phpupload,
                                   $phptime, $wordpresscode, $agb, $newsletter, $version_wordpress ,$version_php, $language);
		} catch (SoapFault $e) {
            echo '<br /><b>Error '.$e->getMessage().'</b>'; 
                if ($e->getMessage()=="(605) no valid email adress") _e("<br />Email adress is not valid.",'1000grad-epaper');       
                if ($e->getMessage()=="(606) email already exists") _e("<br />Email adress is already registered.",'1000grad-epaper');       
            _e("<br /><b>Your Registration was not successful! Please try again.</b>",'1000grad-epaper');             
        return false;        
        }
            _e("<br /><b>Please have a look into your email inbox for confirmation code!</b>",'1000grad-epaper');
            return $res;            

    }
    
     /**
     * Abschluss des Registrierungsprozesses, Code wird eingegeben und APikey kommt
     */
    public function sendCodeGetApikey($email, $code) 
    {
        $this->epaperApikeyApiConnect();
        try {
            $res = $this->apikeyApiClient->sendCodeGetApikey($email, $code);
            return $res;            
		} catch (SoapFault $e) {
            echo "<br />";        
            _e("Error with API.",'1000grad-epaper');
            echo $e->getMessage(); 
        }
        return false;
    }
    
     /**
     * Feedback Formular
     */
    public function sendFeedback($email, $text, $more, $adminUrl, $phpupload, $phptime, $wpVersion, $phpVersion, $language)
    {
        $this->epaperApikeyApiConnect();
        try {
            $res = $this->apikeyApiClient->sendFeedback($email, $text, $more, $adminUrl, $phpupload, $phptime, 
                                                        $wpVersion, $phpVersion, $language);
            return $res;            
		} catch (SoapFault $e) {
            echo "<br />";        
            _e("Error with API.",'1000grad-epaper');
            echo "<br />";        
            echo $e->getMessage(); 
        }
        return false;
    }
    
     /**
     * Test Funktion zum Addieren weiterer Kanaele
     */
    public function sendCodeGetMoreChannels($email, $code) 
    {
        $this->epaperApikeyApiConnect();
        try {
            $res = $this->apikeyApiClient->sendCodeGetMoreChannels($email, $code);
            return $res;            
		} catch (SoapFault $e) {
//            echo '<div class="update-nag">';
            echo "<br />";        
            _e("Error with API.",'1000grad-epaper');
            echo "<br />";        
            $msg = $e->getMessage();
            echo $msg;        
            echo "<br />";        
//            echo "</div>";
            return $msg;
        }
    }   
}

