<?php
/**
 * Class wraps functions for communiction with the 1000° channel API
 * @copyright (c) 2013, 1000°DIGITAL Leipzig GmbH
 * @author Karsten Lemme <karsten.lemme@1000grad.de>
 */
class EpaperChannelApi 
{
    private $channelApiClient;
    
    private $apikey;
    private $epaperOptions;
    private $isRegistered;
    
    public function __construct() 
    {
        $this->epaperOptions = get_option("plugin_epaper_options");
        $this->apikey = isset($this->epaperOptions['apikey'])?$this->epaperOptions['apikey']:NULL;
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
    public function epaperChannelApiConnect() 
    {               
        $wsdl = $this->epaperOptions['url'] . "channels-wsdl/";
        try {
            $this->channelApiClient = new SoapClient($wsdl , array());
            return true;
		} catch (SoapFault $e) { 
            _e("Error: Could not connect to API.",'1000grad-epaper');
            echo $e->getMessage(); 
            return false;
        }
    } 
    
     /**
     * Api Version
     */
    public function getChannelApiVersion ()
    {
        $this->epaperChannelApiConnect();
        try {
            $version = $this->channelApiClient->getVersion();
            return $version;            
		} catch (SoapFault $e) {
            _e('Error with Channel API Handling, please register your plugin!','1000grad-epaper')
            . $e->getMessage(); 
            return false;         
        }
    }
    
     /**
     * Api Funktionen
     */
    public function getChannelApiFunctions() 
    {  
        $this->epaperChannelApiConnect();
        try {
            $functions = $this->channelApiClient->__getFunctions();
            return $functions;            
		} catch (SoapFault $e) {
            _e('Error with Channel API Handling, please register your plugin!','1000grad-epaper') . $e->getMessage();
            return false;
        }
    }
    
     /**
     * Abfrage der Kanal Liste 
     */
    public function getChannelsList ($apikey) 
    {
        $this->epaperChannelApiConnect();
        try {
            $list = $this->channelApiClient->channelsGetList($apikey);
            return $list;            
		} catch (SoapFault $e) {
            _e("Error with API Key Authentification.",'1000grad-epaper');
            echo $e->getMessage(); 
            return false;
        }
    }
    
     /**
     * ePaper Loeschen aus einem Kanal
     */
    public function removeEpaperFromChannel ($apikey, $id) 
    {
        $this->epaperChannelApiConnect();
        try {
            $this->channelApiClient->channelsRemoveEpaperFromChannel($apikey,$id);
            return true;            
		} catch (SoapFault $e) {
            echo "<br />";
            _e("Error: could not remove ePaper.",'1000grad-epaper');
            echo $e->getMessage(); 
            return false;
        }
    }
    
     /**
     * Publikation eines ePaper in einen Kanal
     */
    public function publishEpaperToChannel ($apikey, $epaperId, $id) 
    {
        $this->epaperChannelApiConnect();
        try {
            $res = $this->channelApiClient->channelsPublishEpaperToChannel($apikey, $epaperId, $id);
            return $res;            
		} catch (SoapFault $e) {
            echo "<br />";
            _e("Error while Channelizing.",'1000grad-epaper');
            echo $e->getMessage(); 
            return false;
        }
    }
    
     /**
     * Kanal Infos
     */
    public function getChannelInfo($apikey, $channelId) 
    {
        $this->epaperChannelApiConnect();
        try {
            $res = $this->channelApiClient->channelsGetChannelInfo($apikey, $channelId);
            return $res;            
		} catch (SoapFault $e) {
            echo "<br />";
            _e("Error with ePaper Channel.",'1000grad-epaper');
            echo $e->getMessage(); 
            return false;
        }
    }  
    
    
     /**
     * Kanal Name
     */
    public function setChannelTitle($apikey, $iChannelId, $sTitle) 
    {
        $this->epaperChannelApiConnect();
        try {
            $res = $this->channelApiClient->channelsSetChannelTitle($apikey, $iChannelId, $sTitle);
            return $res;            
		} catch (SoapFault $e) {
            echo "<br />";
            _e("Error with ePaper Channel.",'1000grad-epaper');
            echo $e->getMessage(); 
            return false;
        }
    }    
    
    
    
    
    
    
}