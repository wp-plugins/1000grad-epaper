<?php
/**
 * Class wraps functions for communiction with the 1000° epaper API
 * @copyright (c) 2013, 1000°DIGITAL Leipzig GmbH
 * @author Karsten Lemme <karsten.lemme@1000grad.de>
 */
class EpaperApi 
{
    //set vars for epaper api
    private $epaperApiWsdl;
    private $epaperApiClient;
    
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
    public function epaperApiConnect()
    {

        $wsdl = $this->epaperOptions['url'] . "epaper-wsdl/";	
        try {
            $this->epaperApiClient = new SoapClient($wsdl , array());
            return true;
		} catch (SoapFault $e) {
            echo 'Fehler beim Connect'. $e->getMessage(); 
            return false;           
        }	      
    }
    
     /**
     * Infos ueber ePaper
     */
    public function returnEpaperInfos ($apikey, $id) 
    {
        $res=$this->epaperApiConnect();
        if ( is_wp_error($res) )
            return $res;        
        try {
            return $this->epaperApiClient->epaperGetInfos($apikey,$id);
        } catch (SoapFault $e) {
            return  new WP_Error('ePaper read fault (1)', $e->getMessage() ); 
        }
    }
    
    /**
     * ePaper List
     */
    public function returnEpaperList ($apikey)
    {
        $this->epaperApiConnect();
        try {
            $epaperList = $this->epaperApiClient->epaperGetList($apikey);
		} catch (SoapFault $e) {
            _e("ePaper read fault (2).",'1000grad-epaper');
            echo $e->getMessage(); 
            return false;            
        }
        return $epaperList;
    }
    
     /**
     * API Version
     */
    public function getEpaperApiVersion() 
    {  
        $this->epaperApiConnect();
        try {
            $version = $this->epaperApiClient->getVersion();
            return $version;            
		} catch (SoapFault $e) {
            _e('Error with API Handling, please register your plugin!','1000grad-epaper')
            . $e->getMessage(); 
            return false;         
        }
    }
    
     /**
     * API Funktionen
     */
    public function getEpaperApiFunctions() 
    {  
        $this->epaperApiConnect();
        try {
            $functions = $this->epaperApiClient->__getFunctions();
            return $functions;            
		} catch (SoapFault $e) {
            _e('Error with API Handling, please register your plugin!','1000grad-epaper') . $e->getMessage();
            return false;
        }
    }

     /**
     * Client Info
     */
    public function getEpaperApiClientInfos($apikey) 
    {  
        $this->epaperApiConnect();
        try {
            $clientinfos = $this->epaperApiClient->clientGetInfos($apikey);
            return $clientinfos;            
		} catch (SoapFault $e) {
            _e('Error with API Handling, please register your plugin!','1000grad-epaper') . $e->getMessage();
            return false;
        }
    }
    
     /**
     * Loeschen von einem ePaper
     */
    public function epaperDelete ($apikey, $epaperId) 
    {
        $this->epaperApiConnect();
        try {
            $this->epaperApiClient->epaperDelete($apikey, $epaperId);
            return true;
        } catch (SoapFault $e) {
            _e("ePaper deletion fault.",'1000grad-epaper');
            echo $e->getMessage(); 
            return false;
        }   
    }
    
    /**
    * Publizierung des pdf
    */
    public function epaperCreateFromPdf($apikey,$pdfId) 
    {
        $this->epaperApiConnect();
        try {
            $temp= $this->epaperApiClient->epaperCreateFromPdf($apikey, $pdfId);
//            return true;
            return $temp;
            
        } catch (SoapFault $e) {
            _e("ePaper creation fault.",'1000grad-epaper');
            echo $e->getMessage(); 
            return false;
        } 
    }
    
     /**
     * Rendering Prozess zur Publikation starten
     */
    public function epaperStartRenderprocess($apikey,$uploadId)
    {
        $this->epaperApiConnect();
        try {
            $this->epaperApiClient->epaperStartRenderprocess($apikey,$uploadId);
            return true;
        } catch (SoapFault $e) {
            _e("Error: Could not start render process.",'1000grad-epaper');
            echo $e->getMessage(); 
            return false;
        } 
    }
    
     /**
     * Setzen von ePaper Variablen
     */
    public function epaperSetVar($apikey, $uploadId , $key, $value)
    {
        $this->epaperApiConnect();
        try {
            $this->epaperApiClient->epaperSetVar($apikey, $uploadId , $key, $value);
            return true;
        } catch (SoapFault $e) {
            _e("Error: Could not set attribute.",'1000grad-epaper');
            echo $e->getMessage(); 
            return false;
        } 
    }

      /**
     * Abfragen von ePaper Variablen
     */
     public function epaperGetInfos($apikey, $uploadId)
    {
        $this->epaperApiConnect();
        try {
            echo "<pre>";
            print_r(json_decode($this->epaperApiClient->epaperGetInfos($apikey, $uploadId)));
            echo "<pre>";
            return true;
        } catch (SoapFault $e) {
            _e("Error: Could not set attribute.",'1000grad-epaper');
            echo $e->getMessage(); 
            return false;
        } 
    }

   /**
    * Verschieben bzw. Umbenennen von einem ePaper
    */
    public function epaperMove($apikey, $uploadId , $key, $value)
    {
        $this->epaperApiConnect();
        try {
            $this->epaperApiClient->epaperMove($apikey, $uploadId , $key, $value);
            return true;
        } catch (SoapFault $e) {
            _e("Error: Could not set attribute.",'1000grad-epaper');
            echo $e->getMessage(); 
            return false;
        } 
    } 
    
}
