<?php
/**
 * 
 */
//script to hold config information
//require_once("inc/standard.inc.php");
//hold wp functions 
require_once("EpaperWpOptions.php");
//contains functions for html output
require_once("epaperHtml.php");
//functions for com with channel API
require_once("epaperChannelApi.php");
//functions for com with epaper API
require_once("epaperApi.php");
//functions for com with 1000° wp API (Apikey API)
require_once("epaperApikeyApi.php");
require_once ("ProgressBar.class.php");

/**
 * Class contains the mainfunctions for plugin control
 */
class Epaper 
{
    private $apikey;
    private $epaperOptions;
    private $isRegistered;
    
    public function __construct() 
    {
        $this->epaperOptions = get_option("plugin_epaper_options");
        $this->apikey = $this->epaperOptions['apikey'];
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
            if (isset($_GET["page"])) if ($_GET["page"]=="epaper_apikey") return;
            echo '<br /><div class="update-nag">';
                        echo "<img align=right alt=1000grad-logo hspace=20 src=" . plugin_dir_url("1000grad-epaper/1000grad_logo.png") 
                . "1000grad_logo.png>";
            echo "1000°ePaper<br />"; 
            _e('This Installation is not registered yet.<br />Please <a href=admin.php?page=epaper_apikey>register your installation</a>','1000grad-epaper'); 
            echo '</div>';
        }        
    }
    
    public function pluginRegistered ()
    {
            if ($this->isRegistered === false) {
//            echo '<br /><div class="update-nag">';
//            echo "1000°ePaper<br />"; 
//            _e('This Installation is not registered yet.<br />Please <a href=admin.php?page=epaper_channels>register your installation</a>','1000grad-epaper'); 
//            echo '</div>';
//            echo '</div>';
//            echo '</div>';
//            echo '</div>';
            return false;            
        }
//        echo "pluginRegistered";
        return true;
    }

    /**
     * vorerst nicht verwendet
     */
    function epaperEditPost($id) 
    {
        $html = new EpaperHtml();
        $expr = __('Post ePaper','1000grad-epaper');
        $html->h3($expr);
        $epaperApi = new EpaperApi();
        $editinfo = $epaperApi->returnEpaperInfos($this->apikey, $id);
        echo '<p><h2>'. $editinfo->filename . '</h2>';   
        $info=json_decode($editinfo);
        echo 'Element ID '.$info->id;
        $my_post = array(
            'post_title' => 'ePaper '.$info->title,
            'post_content' => __('This is my new ePaper','1000grad-epaper').' <b>'.$info->title
                .'</b>: <a title="ePaper" href="/wordpress/wp-content/uploads/ePaper/'
                .$id.'/" target="_blank"><img class="alignright" src="/wordpress/wp-content/uploads/ePaper/'
                .$id.'/epaper/preview.jpg" alt="ePaper preview" /></a>',
                'post_status' => 'publish',
            );

        $postid = wp_insert_post($my_post);      
        echo __('<br />was posted (without Category)','1000grad-epaper').'<br /><b><a href=post.php?post='
            . $postid .'&action=edit>'. __('Edit this Post','1000grad-epaper')  .'</a></b>';
	}

    /**
     * vorerst nicht verwendet
     * zeigt alle hochgeladenen ePaper unabh. von Kanaelen
     */
    function epaperEditList()  
    {
        $html = new EpaperHtml();
        $expr1 = '1000°ePaper';
        $html->h1($expr1);
        $expr2 = __('List of uploaded ePapers','1000grad-epaper');
        $html->h2($expr2);        
        $epaperApi = new EpaperApi();
        $clientlist = json_decode($epaperApi->returnEpaperList($this->apikey));
        if (count($clientlist)=="0") {
            _e('No ePaper existent','1000grad-epaper');
            _e('<br />Please <a href=?page=epaper_upload>upload a PDF File.</a>','1000grad-epaper');
        }  
        foreach ($clientlist as $clientpaper ) {          
            $clientabfrage = $epaperApi->returnEpaperInfos($this->apikey,$clientpaper->id);
            $clientinfo = json_decode($clientabfrage);
            $html->hr();
        //    $html->editEpaperListForm($clientinfo);
        //    print_r($clientpaper);
            
            // dieser Teil der Funktion ist noch nicht überarbeitet 
        echo '<img align=left src='.$clientinfo->web_folder.'source/thumbs/page_1.jpg?rnd='.rand(1000, 9999).'>';
        if ($clientinfo->settings->logo=='1') echo '<img src='.$clientinfo->web_folder.'source/logo.png?rnd='.rand(1000, 9999).'>';
//        if ($clientinfo->title=='')	_e('<font color=red>no title set</font>','1000grad-epaper');
        if ($clientinfo->settings->is_pdf_download<>'0')	echo '<font color=red size=+2>x</font>';
        else echo '<font color=green size=+2>x</font>';
        echo '<br /><font color=grey>File: '.$clientpaper->filename.'';
        echo ' (ID: '.$clientpaper->id.')</font>';
//        echo "<pre>";
//            print_r($clientinfo);
//        echo "</pre>";
        echo __('<br />Number of Pages:','1000grad-epaper').' '.$clientpaper->pages;
        if ($clientinfo->status<>'ready')	echo '<br /><font color=red size=+2>Status: '.$clientinfo->status.'</font>';
        else echo '<br />Status: ready';
//        echo '<br /><b><a href=?page=epaper_edit&modus=check&id='.$clientpaper->id.'>'.__('edit','1000grad-epaper').'</a> / ';
//        echo '    <a href=?page=epaper_channels&modus=channelizing&id='.$clientpaper->id.'&channelid=1>'.__('canalize','1000grad-epaper').'</a> / ';
//        echo '    <a href=?page=epaper_edit&modus=delete&id='.$clientpaper->id.'>'.__('delete','1000grad-epaper').'</a> / ';
        echo '</b>';
        echo '</form>';

        $html->brClearAll();
        }
        $html->hr();       
    }  
    
    /**
     * done
     * @rturn boolean
     */
    function epaperChannelConnect() 
    {
        $html = new EpaperHtml();
        if ($this->isRegistered == false){
            $html->divUiSortable();
            $html->divClassUpdateNag();
            $html->divClassInside();
            $html->br();
            _e('This Installation is not registered yet.<br />Please <a href=admin.php?page=epaper_apikey>
                register your installation</a>','1000grad-epaper');
            $html->closeDiv();
            $html->closeDiv();
            $html->closeDiv();
            return false;
        } 
        
        if ($this->epaperOptions['url'] == "") {
            $html->divUiSortable();
            $html->divClassUpdateNag();
            $html->divClassInside();
            $html->br();
            _e('This Installation is not correctly registered.<br />Please <a href=options-general.php?page=epaper_settings>
                check your installation</a>','1000grad-epaper');
            $html->closeDiv();
            $html->closeDiv();
            $html->closeDiv();
            return false;            
        }
        
        $channelApi = new EpaperChannelApi();
        $channelApi->epaperChannelApiConnect();
        return true;
    }

    
    /**
     * API test
     */
    function epaperSettingsTest($testchannel,$apiKey) 
    {
        echo 'API Version: '. $testchannel->getVersion();
        echo '<p>API Key: '. $apiKey;
        $clientinfo=($testchannel->__getFunctions());
        echo __('<br />Number of API Commands:','1000grad-epaper').' '.count($clientinfo);
    }
    
    
    /**
     * Test von einigen Wordpress Einstellungen
     */
    function epaperTestWordpress() 
    {
        global $wp_version;
        $versionwordpress = $wp_version;
        $versionphp = phpversion();
        $max_upload = (int)(ini_get('upload_max_filesize'));
        $max_post = (int)(ini_get('post_max_size'));
        $memory_limit = (int)(ini_get('memory_limit'));
    	$max_execution_time= (int)(ini_get('max_execution_time'));
    	$max_input_time= (int)(ini_get('max_input_time'));
    	$upload_mb = min($max_upload, $max_post, $memory_limit);
    	$uptime_mb = min($max_execution_time,$max_input_time);
        echo "<b>";
        _e('max. PHP-Upload of this Wordpress:','1000grad-epaper');
        echo ' '.$upload_mb.' MByte</b> (upload: '.$max_upload.'MB, Post:'.$max_post.'MB, Speicher:'.$memory_limit.'MB)';
        echo "<br />";
        _e('Wordpress & PHP-Version:','1000grad-epaper');
        echo ' '.$versionwordpress.' / '.$versionphp;
        echo "<br />";
        _e('max. PHP-Time for this Wordpress:','1000grad-epaper');
        echo ' '.$uptime_mb.' sec. (execution: '.$max_execution_time.'s, Input:'.$max_input_time.'s)';
        echo "<br />";
        _e('curl for this Wordpress:','1000grad-epaper');
        if (extension_loaded('curl')) echo " OK. ";
        else _e("<br /><b>Error, there is NO CURL installed at your wordpress system!</b>",'1000grad-epaper');
        echo "<br />";
        _e("local plugin settings:",'1000grad-epaper');
        echo "<br />";
        echo "<pre>";
        $epaper_options = get_option("plugin_epaper_options");
        print_r($epaper_options);
        echo "</pre>";
    }

    
    /**
     * Anzeige der einzelnen Funktionen
     */
    function epaperTestDebug($functions) 
    {
//        echo "<pre>";    
//        print_r($functions);
//        echo "</pre>";    
    } 
    
    /**
     * API Test
     */
    function epaperTestEpaperApi() 
    {
        if ($this->isRegistered === true) {
            $epaperApi = new EpaperApi();
            $version = $epaperApi->getEpaperApiVersion();
            $functions = $epaperApi->getEpaperApiFunctions();
            $clientinfos = $epaperApi->getEpaperApiClientInfos($this->apikey);
            echo 'API Version: ' . $version;
            echo __('<br />Number of API Commands:','1000grad-epaper').' '.count($functions);
            $this->epaperTestDebug($functions);
            if ($clientinfos !== false) {
                _e('<br />Api key is valid','1000grad-epaper');
                return true;
            } else {
                _e('<br /><b>Error with API Key Authentification','1000grad-epaper');
                return false;
            }
        }
    } 

    /**
     * API Test
     */
    function epaperTestChannelApi() 
    {
        if ($this->isRegistered === true) {
            $channelApi = new EpaperChannelApi();
            echo 'API Version: '. $channelApi->getChannelApiVersion();
            $functions = $channelApi->getChannelApiFunctions();
            echo __('<br />Number of API Commands:','1000grad-epaper').' '.count($functions);
            $this->epaperTestDebug($functions);

        }
        return false;
    }
    
    /**
     * API Test
     */
    function epaperTestApikeyApi() 
    {
        if ($this->isRegistered === true) {
            $apikeyApi = new EpaperApikeyApi();
            echo 'API Version: '. $apikeyApi->getApikeyApiVersion();
            $clientinfo = $apikeyApi->getApikeyApiFunctions();
            echo __('<br />Number of API Commands:','1000grad-epaper').' '.count($clientinfo);	
            $this->epaperTestDebug($clientinfo);

        }
        return false;
    }

    /**
     * Anzeige der ePaper Lizenzen
     * @return boolean
     */
    function epaperLicense() 
    {        
        if ($this->isRegistered === true) {	
            $epaperApi = new EpaperApi();
            $clientinfos = $epaperApi->getEpaperApiClientInfos($this->apikey);
            $clientinfo = json_decode($clientinfos);
            echo 'name: '.$clientinfo->name;
            echo ' ('.$clientinfo->firstname.' '.$clientinfo->lastname.') ';
            echo '<br />short name: '.$clientinfo->short_name;
            echo '<br />customer id: '.$clientinfo->customer_id;
            echo '<br />email: '.$clientinfo->email;
            echo '<br /><b>channel count: '.$clientinfo->channels_count.'</b>';
            echo '<br />memory count: '.round($clientinfo->disk_usage / 1024 / 1024 ).' MByte';
            echo '<br />created ePapers: '.$clientinfo->count_created;
            echo '<br />published ePapers: '.$clientinfo->count_published;
            
            $channelApi = new EpaperChannelApi();	
            $clientchannels = json_decode($channelApi->getChannelsList($this->apikey));
            echo '<br />ePapers channels: '.$clientchannels->count;
            foreach ( $clientchannels->channels as $channelt ) {
                echo "<br />ID: ".$channelt->id;
                echo " - ".$channelt->time_created;
                echo " - ".$channelt->time_modified;
                echo " - ".$channelt->expiry_time;
                echo " ";
                if ($channelt->id_epaper!="") {
                    echo " - ".$channelt->id_epaper;
                } else _e('not used','1000grad-epaper');
            }
            return true;
        }
        return false;
    }

    /**
     * Einstellungsseite zeigt momentane Settings und testet Wordpress & die APIs
     * @global type $wp_version
     */
    function epaperSettings() 
    {
//        $epaper_options = get_option("plugin_epaper_options");
        if (!is_array( $this->epaperOptions)) { 
            $this->epaperOptions = array(
                'url' => '',
                'wordpressapi' => 'http://epaperplugin.1kcloud.com/api/v2/wordpress-wsdl/',
                'apikey' => '', 
            );
        } 						
        if (isset($_GET['epaper-settings-save'])) { 	
            $this->epaperOptions['url'] = htmlspecialchars($_GET['epaper_url']);
            $this->epaperOptions['wordpressapi'] = htmlspecialchars($_GET['epaper_wordpressapi']);
            $this->epaperOptions['apikey'] = htmlspecialchars($_GET['epaper_apikey']); 
            update_option("plugin_epaper_options", $this->epaperOptions);  
        }
       	if (isset($_GET['epaper-more-channels'])) {    
            echo "upgrade not in settings - tbo";	
        }
        
        $html = new EpaperHtml();
        $html->divClassWrap();
        $expr = '1000°ePaper';
        $html->h1($expr);
        $html->divPostboxContainer();
        $html->divMetaboxHolder();
        $html->divUiSortable();
        $html->divPostbox();
        $expr2 = __("Feedback",'1000grad-epaper'); 
        $html->h3($expr2);
        $html->divClassInside();
               
        if (isset($_POST['feedback'])) { 
            $text = $_POST['text'];        
            global $wp_version;
            $max_upload = (int)(ini_get('upload_max_filesize'));
            $max_post = (int)(ini_get('post_max_size'));
            $memory_limit = (int)(ini_get('memory_limit'));
            $max_execution_time= (int)(ini_get('max_execution_time'));
            $max_input_time= (int)(ini_get('max_input_time'));
            $phpupload = min($max_upload, $max_post, $memory_limit);
            $phptime = min($max_execution_time,$max_input_time);
            $language = __("en",'1000grad-epaper');
            $version_wordpress = $wp_version;
            $version_php = phpversion();
            $more="kein curl installiert";
            if (extension_loaded('curl')) {
                $more = "curl ist installiert";
            }
            $apikeyApi = new EpaperApikeyApi();
            $res = $apikeyApi->sendFeedback($this->epaperOptions['email'], $text, $more, admin_url(), $phpupload, 
                                $phptime,$version_wordpress, $version_php, $language);
            if ($res === false) {
                _e("<b>Error: could not send data.</b>",'1000grad-epaper');
            } else {
            _e("<br>Your feedback comment was sent to the 1000°ePaper Support Team. Thank you for contacting us.",'1000grad-epaper');
            echo "<br /><br /><i>".$text."</i>";
            }
        } else {
            $html->epaperFeedbackForm();
        }
        $html->close3Div();
        $html->divUiSortable();
        $html->divPostbox();
        $expr3 = __("Contact",'1000grad-epaper');
        $html->h3($expr3);
        $html->divClassInside();
        $html->printEpaperContact();
        _e('<a href="http://www.1000grad.de/upload/Dokumente/agb/terms_of_use_1000grad_ePaper_API_WP_Plugin.pdf">terms of use</a>',
            '1000grad-epaper');
        $html->close3Div();
        $html->divUiSortable();
        $html->divPostbox();
        $expr4 = __("Settings",'1000grad-epaper');
        $html->h3($expr4);
        $html->divClassInside();        
        if (!extension_loaded('curl')) {  
            _e("<br /><b>Error, there is NO CURL installed at your wordpress system!</b>",'1000grad-epaper');
            _e("<br />Please install it first!<br><code>apt-get install php5-curl</code>",'1000grad-epaper');
            exit();
        }
        $html->epaperApiSettingsForm($this->epaperOptions);
        $html->close3Div();
        
        $expr5 = 'Infos';
        $html->associatedDivs($expr5);
        $this->epaperTestWordpress();
        $html->close3Div();
        
        $expr6 = 'ePaper Wordpress API';
        $html->associatedDivs($expr6);
        $this->epaperTestApikeyApi();
        $html->close3Div();
        
        $expr7 = 'ePaper User API';
        $html->associatedDivs($expr7);
        $this->epaperTestEpaperApi();
        $html->close3Div();
        
        $html->associatedDivs('ePaper Channel API');
        $this->epaperTestChannelApi();
        $html->close3Div();
        
        $html->associatedDivs('ePaper API License');
        $this->epaperLicense();
        $html->close3Div();
        
        $html->closeDiv();
        $html->closeDiv();
        $html->br();
        $html->closeDiv();    
    }

    /**
     * Registrierungsprozess fuer neue User
     */
    function epaperApikey() 
    {         
        if (!is_array($this->epaperOptions)) { 
            $this->epaperOptions = array(
                'url' => '', 	
                'email' => '', 	
                'wordpressapi' => 'http://epaperplugin.1kcloud.com/api/v2/wordpress-wsdl/',                    
                'text' => '', 	
                'urlapikey' => '', 	
                'apikey' => '', 
                'code' => '', 
                'newsletter' => '',                     
            );
        }
        // # schritt 2: email adresse wird übermittelt
        if (isset($_POST['on'])) {             
            $email = trim(htmlspecialchars($_POST['apikey_email']));
            if (isset ($_POST['agb'])) $agb="yes"; else $agb="";
            if (isset ($_POST['newsletter'])) $newsletter="yes"; else $newsletter="";
            $language = __("en",'1000grad-epaper');
            // wordpress infos
            global $wp_version;
            $version_wordpress = $wp_version;
            $version_php = phpversion();
        $max_upload = (int)(ini_get('upload_max_filesize'));
    	$max_post = (int)(ini_get('post_max_size'));
        $memory_limit = (int)(ini_get('memory_limit'));
        $max_execution_time= (int)(ini_get('max_execution_time'));
        $max_input_time= (int)(ini_get('max_input_time'));
        $upload_mb = min($max_upload, $max_post, $memory_limit);
        $uptime_mb = min($max_execution_time,$max_input_time); 
        $wordpress = admin_url();
        $text = "wordpress";
        $wordpresscode="";
            
            _e("Acquiring data.",'1000grad-epaper');
    		$this->epaperOptions['email'] = $email; 
    		$this->epaperOptions['text'] = $text; 
    		$this->epaperOptions['agb'] = $agb; 
    		$this->epaperOptions['newsletter'] = $newsletter; 
    		update_option("plugin_epaper_options", $this->epaperOptions);  
            
            $apikeyApi = new EpaperApikeyApi();
            $res = json_decode($apikeyApi->getRegistrationCodeByEmail($email, $text, $wordpress, $upload_mb,
                                   $uptime_mb, $wordpresscode, $agb, $newsletter, $version_wordpress ,$version_php, $language));
        }
        // # schritt 1: formular wird angezeigt

        $html = new EpaperHtml();
        $html->divClassWrap();
        $out = __("1000°ePaper Registration",'1000grad-epaper');
        $html->h1($out);
        $html->divPostboxContainer();
        $html->divMetaboxHolder();
        if (!extension_loaded('curl')) {
            _e("<br /><b>Error, there is NO CURL installed at your wordpress system!</b>",'1000grad-epaper');
            _e("<br />Please install it first!<br><code>apt-get install php5-curl</code>",'1000grad-epaper');
            exit();
        }
    // 3
    // --    
        $out = __("Request 1000°ePaper Key",'1000grad-epaper');
        $html->associatedDivs($out);
        $html->renderEpaperForm ($this->epaperOptions);
        $html->close3Div();

    // --    
        $expr = __("Registration Code",'1000grad-epaper');
        $html->associatedDivs($expr);
        // # schritt 3: code aus der email wird angeklickt.
        if (isset($_GET['email'])) { 
            $this->epaperApikeyGetByCode();
        } else {       
        // # schritt 3b: code aus der email kann eingetippt werden
            if ($this->epaperOptions['email'] != "") {
                $html->divEpaperform();
                $html->divMetaboxPrefs();
                $html->renderConfirmEmailRegisterForm($this->epaperOptions);
                $html->closeDiv();
                $html->closeDiv();
                $html->br();
           } else {
                _e('Info: Your Information will be transmitted to the 1000Grad Company. You will receive an email for confirmation.','1000grad-epaper');
                $html->close3Div();
                $expr = 'Kontakt';
                $html->associatedDivs($expr);
                $html->printEpaperContact();
            }
        }            
            $html->close3Div();
            $html->close3Div();
    }

    
        /**
     * Test Funktion zum Erhalten des Apikeys
     * @return boolean
     */
    public function epaperApikeyGetByCode() 
    {
//      $email = $this->epaperOptions['email'];
        
            $email = trim(htmlspecialchars($_GET['email']));
            $code = trim(htmlspecialchars($_GET['code']));  
            $echo = __("Confirmation code was entered:",'1000grad-epaper');
            echo $echo . " " . $code;

            $apikeyApi = new EpaperApikeyApi();         
            $result = ($apikeyApi->sendCodeGetApikey($email, $code));
        if ($result==false) {
            echo "<br />";
            _e("ePaper Registration fault.",'1000grad-epaper');
            echo "<br />";
            _e("Please type in the confirmation code you was receiving via email.",'1000grad-epaper');
            echo "<br />";
            return false;            
        }
        $result=  json_decode($result);
            _e("<p>Your new settings are: <br /><b><pre>",'1000grad-epaper');
            print_r($result);
            echo "</pre></b>";
            $this->epaperOptions['url']= $result->apiurl;
            $this->epaperOptions['apikey']= $result->apikey;		
            update_option("plugin_epaper_options", $this->epaperOptions);  
            _e("<p>Now you can use this ePaper Plugin!</p>",'1000grad-epaper');
            $html = new EpaperHtml();
            $html->registerDoneBtn();     
    }

    
    
    /**
     * wird ausgefuerht, wenn jemand ein PDF hochlaedt
     * @return boolean
     */
    public function epaperChannelUpload() 
    {
//        echo "ss";
        $html = new EpaperHtml();
        $upload = $_FILES['uploadfile'];
        $id = $_POST['id'];
        $id_epaper = $_POST['id_epaper'];
        $epaperApi = new EpaperApi();            
        if ($id_epaper!="") {
            $channelApi = new EpaperChannelApi();
            if ($channelApi->removeEpaperFromChannel($this->apikey, $id)) { 
                   _e("<br />Free this channel. <b>OK</b>",'1000grad-epaper');
            } else {
                   _e("<br /><b>Error with that channel!</b>",'1000grad-epaper');
            }
        
//        $epaperApi = new EpaperApi();            
        if ($epaperApi->epaperDelete($this->apikey, $id_epaper)) { 
            _e("<br />Delete previous ePaper.<b>OK</b>",'1000grad-epaper');
        } else {
            _e("<br /><b>Error while deleting that!</b>",'1000grad-epaper');

        }        
                }
      _e("<h1>ePaper Upload</h1>File uploaded to your wordpress:",'1000grad-epaper');
        echo ' <b>'.$upload['name'].'</b> ('.round($upload['size']/1024).'kByte) ';
        if ($upload['error']=='0') {
           echo "<b>OK</b>";
        } else {
            _e("<b>Error!</b>",'1000grad-epaper');
            echo " ";
            echo $upload['error'];
            echo "<hr>";
            _e("<br />Maybe file is larger than your wordpress php settings.",'1000grad-epaper');
            _e("<br />You can rise up these limits by creating file <b>wp-admin/.htaccess</b>",'1000grad-epaper');
            echo ("<pre>
                    php_value upload_max_filesize 100M
                    php_value post_max_size 100M
                    php_value max_execution_time 200
                    php_value max_input_time 200
                </pre><br />");
            _e("or look for hints at",'1000grad-epaper');
            echo "<a href=\"http://php.net/manual/en/features.file-upload.php\">php.net</a><hr>";
            $this->epaperTestWordpress();
            return false;
        }
        $file = $upload['tmp_name'];
        if (!file_exists($file)) _e("uploaded File doesnt exists. Maybe a problem in the php config.",'1000grad-epaper');
        _e("<br />File uploaded to 1000°ePaper server.",'1000grad-epaper');
        echo ' <b>'. $file .'</b> ('.round(filesize($file)/1024).' kByte) ';    
        //$this->epaperOptions = get_option("plugin_epaper_options");
        $uploadUrl = $this->epaperOptions['url'] . "pdf-upload/";
        $uploadName = urlencode($upload['name']);
        if (!isset($uploadName) || $uploadName =="") $uploadName="upload.pdf"; 
        // bugfixing for https://bugs.php.net/bug.php?id=46696 - no filename on upload due to some curl problems in earlier versions)
        move_uploaded_file($file, dirname($file) . "/" . $uploadName . ".pdf");    
        $postParams = array(
                'file'      => "@" . dirname($file). '/' .$uploadName . '.pdf',
                'apikey'    => $this->apikey,
            );
    
        if (extension_loaded('curl')) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
            curl_setopt($ch, CURLOPT_FAILONERROR, 1);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_URL, $uploadUrl);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postParams);
            if (!$response = curl_exec($ch)) {
                _e('<br />Error message from you wordpress server:','1000grad-epaper');
                echo '<br />curl error: '.curl_error($ch);
            }
            unlink(dirname($file). '/' . $uploadName . '.pdf');   
            curl_close($ch);
            $result = json_decode($response, true);
            if (!$result) {
                echo '<br />Error: Could not decode response!';
                echo "<pre>";
                print_r($response);
                echo "</pre>";
                return false;
            } elseif (!$result['success']) {
                echo '<p><b>';
                _e('Error message from 1000°ePaper server:','1000grad-epaper');
                echo ' '.$result['errors']['errorDesc'].'</b>';
                return false;
            } else {
                echo " <b>OK</b>";
            }
            $pdfId = $result['pdfId'];
            _e('<br />Upload was sucessful','1000grad-epaper');
        } else {
            _e("<br /><b>Error, there is NO CURL installed at your wordpress system!</b>",'1000grad-epaper');
            return false; 
        }    
        $uploadId = $epaperApi->epaperCreateFromPdf($this->apikey, $pdfId);
        if ($epaperApi->epaperStartRenderprocess($this->apikey,$uploadId)) {
            echo ", ";
            _e('ePaper Rendering was started.','1000grad-epaper');
        } else {
            _e('<br />ePaper rendering couldnt start <b>error</b>.','1000grad-epaper');
        }
        echo "<br />";
        $bar = new ProgressBar();
        $bar->setAutohide(true);
        $bar->setSleepOnFinish(1);
        $bar->setForegroundColor('#333333');
        $elements = 10; //total number of elements to process
        $bar->setBarLength(400);
        for($j=5;$j>2;$j++) {
            $infoj = $epaperApi->returnEpaperInfos($this->apikey, $uploadId);
            $info = json_decode($infoj, true);
//        echo "<pre>";
//        print_r($info);
//        echo "</pre>";       
            echo $info['status']. " ";
            $status = $info['status'];
            if ($status=="ready") $j=0;
            echo $info['renderprocess']['current_page'] . "/" . $info['pages'];
            $bar->initialize($elements); //print the empty bar
            $bar->setMessage($status);
            for($i=0; $i<$elements; $i++){
            sleep(1); 
            $bar->increase(); //calls the bar with every processed element
            } 
        }
        $html->brClearAll();          
        echo "<p>";
        $this->epaperChannelDetailsForm($id,$uploadId,$upload['name']);
        echo "</p>";
    }

    /**
     * leert den ePaper Kanal, inkl. Löschen des zugehoerigen ePapers
     */
    public function epaperChannelEmpty() 
    {
        $id = $_POST['id'];
        $epaperId = $_POST['id_epaper'];
        if ($epaperId != "") {
            $channelApi = new EpaperChannelApi();
                _e("<br />Please wait...",'1000grad-epaper');
                ob_flush();
		flush();
            if ($channelApi->removeEpaperFromChannel($this->apikey, $id)) { 
                _e("<br />Free this channel. <b>OK</b>",'1000grad-epaper');
            } else {
                   _e("<br /><b>Error with that channel!</b>",'1000grad-epaper');
            }
            $epaperApi = new EpaperApi();
            if ($epaperApi->epaperDelete($this->apikey, $epaperId)) {                 
               _e("<br />Delete previous ePaper.<b>OK</b>",'1000grad-epaper');
            } else {
            _e("<br /><b>Error while deleting that!</b>",'1000grad-epaper');
            }
        }
        else 
            _e("<br /><b>Error with that ID!</b>",'1000grad-epaper');
    }
    
    /**
     * nach dem Hochladen werden einige Einstellungen gesetzt. Starten der Publikation
     * @return boolean
     */
    public function epaperChannelDetails() 
    {
        $html = new EpaperHtml();
        $uploadid = $_POST['id_epaper'];
        $id = $_POST['id'];
        $title = $_POST['title'];
        $lang = $_POST['lang'];
        _e('<br />Name:','1000grad-epaper');
        echo ' ' . ($title);
        
        $epaperApi = new EpaperApi();
        if ($epaperApi->epaperSetVar($this->apikey, $uploadid, 'title', html_entity_decode($title))=='1') {
            echo ' <b>OK</b>';
        } else {
            _e("<br /><b>Error while setting title!</b>",'1000grad-epaper');
        }
        if ($epaperApi->epaperSetVar($this->apikey, $uploadid, 'language', html_entity_decode($lang))=='1') {
            echo '/ ';
            _e('language:','1000grad-epaper');
            echo "<b>OK</b>";
        } else {
            _e('problems while setting ePaper details.','1000grad-epaper');
        }        
        if (!$epaperApi->epaperSetVar($this->apikey,$uploadid, 'is_pdf_download', '0')) {
            _e('problems while setting ePaper details.','1000grad-epaper');
            return false;
        }
        if (!$epaperApi->epaperSetVar($this->apikey,$uploadid, 'is_search', '1')) {
            _e('problems while setting ePaper details.','1000grad-epaper');
            return false;
        }
        if (!$epaperApi->epaperSetVar($this->apikey,$uploadid, 'use_ipad', '1')) {
            _e('problems while setting ePaper details.','1000grad-epaper');
            return false;
        }
        if (!$epaperApi->epaperMove($this->apikey,$uploadid, html_entity_decode($title), '1')) {
            _e('problems while setting ePaper details.','1000grad-epaper');
            return false;
        }

//        print_r($epaperApi->epaperGetInfos($this->apikey,$uploadid));       
        $channelApi = new EpaperChannelApi();
        $fileup = $channelApi->publishEpaperToChannel($this->apikey, $uploadid, $id);
        if ($fileup === false) {
            _e('<br /><b>Error while Channelizing!</b>','1000grad-epaper');
            print_r($fileup);
        } elseif($fileup == '1') {
            _e('<br />Publishing started.','1000grad-epaper');
        } else {
            _e('<br />Unable to start publishing.','1000grad-epaper');
        }
        $html->br();
        
        $bar = new ProgressBar();
        $bar->setAutohide(true);
        $bar->setSleepOnFinish(1);
        $bar->setForegroundColor('#333333');
        $elements = 10; //total number of elements to process
        $bar->setBarLength(400);
        for($j=5; $j>2 ;$j++) {
            $infoj = $epaperApi->returnEpaperInfos($this->apikey, $uploadid);
            $info = json_decode($infoj, true);
            $status=$info['status'];
            if ($status=="ready") $j = 0;
            $bar->initialize($elements); //print the empty bar
            $bar->setMessage($status);
            for($i=0 ; $i < $elements ; $i++) {
                sleep(1); // simulate a time consuming process
                $bar->increase(); //calls the bar with every processed element
            } 
        }
        echo "<p>";
        _e("Your ePaper is now ready to use.",'1000grad-epaper');
        echo "</p>";
        $html->br();
        $html->epaperPublishedButtonForm();     
}

    /**
     * Formular waehrend des Hochladens
     */
    public function epaperChannelDetailsForm($id, $epaperId, $name) 
    {
        $name = preg_replace("/^(.+)\.pdf$/i","\\1", $name);
        $html = new EpaperHtml;
        $html->divEpaperform();
        $html->divMetaboxPrefs();
        $html->channelDetailsForm($id, $epaperId, $name);
        $html->closeDiv();
        $html->closeDiv();
    }

    /**
     * Formular zum Hochladen der pdfs in der Gesamtuebersicht
     */
    public function epaperChannelUploadForm($id, $epaperId) 
    {
        $html = new EpaperHtml;
        $html->divEpaperform();
        $html->divMetaboxPrefs();
        $html->pdfUploadForm($id, $epaperId);
        if ($epaperId != "") { 
            $html->pdfDeleteForm($id, $epaperId);
        }
        $html->closeDiv();
        $html->closeDiv();
    }
    

    /**
     * einen neuen Beitrag posten, in dem das ePaper verlinkt ist
     */
    public function epaperChannelPostPost() 
    {
        $html = new EpaperHtml();
        $h3=__("Post ePaper Channel",'1000grad-epaper');
        $html->h3($h3);       
//        $channelid = $_POST["channelid"];
//        if ($channelid == "") $channelid = $_GET["channelid"];
//             $channelApi = new EpaperChannelApi();
//        if (!$channelinfo = $channelApi->getChannelInfo($this->apikey, $channelid)) {
//        _e("ePaper channel read fault.",'1000grad-epaper');
//            return false;
//        }
//        $info=json_decode($channelinfo);

        if (isset($channelurl)) {
                $channelurl = $_GET["url"];
         $textout = "[ePaper url=".$channelurl."]" . "
             ";  
        } else 
         $textout = "[ePaper]" . "
                ";      
        // please mention the class alignment possibility
        $textout .=  __(
                "This is my new ePaper, brought to you by the 1000°ePaper service.\n".
                "Even you can <a href=http://epaper-apps.1000grad.com>share your ePapers</a>\n".
                "with this wordpress plugin! Get your first ePaper Channel  FOR FREE during beta stage."
                ,'1000grad-epaper');        
        echo "<pre>".$textout."</pre>";
        $my_post = array(
            'post_title' => 'ePaper ',
//            'post_title' => 'ePaper '.$info->title,
            'post_content' => $textout,
            'post_status' => 'publish',
        );        
        $postid = wp_insert_post($my_post);
        _e("<br />was posted (without category)<br />",'1000grad-epaper');
        _e("<br /><b>Hint:</b> You can add the 'class' option for further layoutstyles. F.i. <b>[ePaper class=alignleft]</b> to align your ePaper to the left.",'1000grad-epaper');
        echo "<p>";
        echo '<b><a href=post.php?post=' . $postid . '&action=edit>';
        _e('Further edit this post','1000grad-epaper');
        echo '</a></b>';
	}

    /**
     * Zentralfunktion des Plugins mit Verteilung in Unterfunktionen
     */
    public function epaperChannels() 
    {   
        if (isset($_REQUEST['modus']))    $modus = $_REQUEST['modus'];
                                     else $modus="";
        $html = new EpaperHtml();
        
        switch ($modus) {
            case 'upload':
                $this->epaperChannelUpload();
                break;
            case 'postpost':
                $this->epaperChannelPostPost();
                break;
            case 'channeldetails':
                $this->epaperChannelDetails();
                break;
            case 'channelupgrade':
                $this->epaperChannelUpgrade();
                break;
            case 'empty':
                $this->epaperChannelEmpty();
                break;
            case 'list':
                $this->epaperEditList();
                break;
        }

        if ($modus=="" || $modus=="channelupgrade" || $modus=="empty") {
            $html->divClassWrap();
            $html->h1('1000°ePaper');
            $html->divPostboxContainer95pc();
            $html->divMetaboxHolder();
            $html->divUiSortable();
            $html->divPostbox();
            $html->divClassInside();
            $html->logo1000Grad();
            $html->introText();

            if (!extension_loaded('curl')) {
                _e("<br /><b>Error, there is NO CURL installed at your wordpress system!</b>",'1000grad-epaper');
            }
            if ($this->isRegistered) {
                $channelApi = new EpaperChannelApi();
                $channels = json_decode($channelApi->getChannelsList($this->apikey));
                //print_r($channels);
                //$channelcount = count($channels->channels);
                $channelnr = 0;
                $html->close3Div();
                foreach ( $channels->channels as $channelz ) {
                    $channelnr = $channelnr + 1;
                    $channelinfo = json_decode($channelApi->getChannelInfo($this->apikey, $channelz->id));
                    $this->epaperChannelShow($channelinfo, $channelnr);
                }
                $channelnr = $channelnr + 1;
                $expr = __("add another ePaper Channel",'1000grad-epaper');
                $html->associatedDivs($expr);
                echo "<table width=100%>";
                echo "<tr><td width=30% align=center valign=middle>";
                echo "<img src=" . plugin_dir_url("1000grad-epaper/1000grad_logo.png") . "1000grad_logo.png></td><td width=30%>";
                $html->epaperChannelUpgradeForm();
                echo "</td><td width=30%>";
                _e("You can get more 1000°ePaper channels for your account for instance via PayPal. Coming soon.",'1000grad-epaper');
                _e("<br>Please <a href=options-general.php?page=epaper_settings>give us feedback about this plugin</a>",'1000grad-epaper');
                echo "</td></tr></table>";
                $html->close3Div();            
            } else  { $this->pluginRegistered(); $html->close3Div(); }
            $html->close3Div();
        }
    }

    /**
     * Test Funktion zum Addieren von weiteren ePaper Kanaelen
     * @return boolean
     */
    public function epaperChannelUpgrade() 
    {
//        echo "upgrade";
        $email = $this->epaperOptions['email'];
        $code = $_POST['epaper_code'];
        $apikeyApi = new EpaperApikeyApi();
        $succ = json_decode($apikeyApi->sendCodeGetMoreChannels($email,$code));
        
        switch ($succ) {
            case 'ok':
                _e("Channel upgrade was successful!",'1000grad-epaper');
                break;
            case '(610) code failed':
                _e("Channel upgrade failed. Code not valid or used before!",'1000grad-epaper');
                break;
            case '(605) no valid email adress':
                _e("Channel upgrade failed. There is no email given!",'1000grad-epaper');
                break;
            case '(604) no name':
                _e("Channel upgrade failed. There is no name given!",'1000grad-epaper');
                break;
            case '(612) Email not registered':
                _e("Channel upgrade failed. Email address is not valid!",'1000grad-epaper');
                break;
            default:
                _e("Channel upgrade was not successful!",'1000grad-epaper');
                return false;                
        }        
    }

    /**
     * Anzeige in der Zentralansicht von EINEM ePaper Kanal
     * @param type $channelinfo
     * @param type $channelnr
     */
    public function epaperChannelShow($channelinfo, $channelnr) 
    {
        $html = new EpaperHtml();
        $expr = __("ePaper Channel",'1000grad-epaper') . " #" . $channelnr . " " . $channelinfo->title . " ";
        $html->associatedDivs($expr);
        echo "<table width=100%>";
        echo "<tr><td width=30% align=center valign=middle>";
        if (!isset($channelinfo->id_epaper)) {
            echo __("no ePaper given",'1000grad-epaper');
        } else {
            $htmlf = "<a class='iframe cboxElement' href='";
            $htmlf .= $channelinfo->url . "'";
            $htmlf .= '> <img border=2 width=200 src="';
            $htmlf .= $channelinfo->url . '/epaper/epaper-ani.gif?rnd=' . rand(1000, 9999) 
                   . '" alt="epaper preview gif" border="0" hspace=20 /> </a>';
            echo $htmlf;
        }
        echo "</td><td  width=30% valign=top>";
        $this->epaperChannelUploadForm($channelinfo->id, $channelinfo->id_epaper);
        echo "</td><td  width=30% valign=top>";
        if (!isset($channelinfo->id_epaper)) 
        { 
            _e("According to your wordpress settings the maximum upload size is limited to",'1000grad-epaper');
            $max_upload = (int)(ini_get('upload_max_filesize'));
            $max_post = (int)(ini_get('post_max_size'));
            $memory_limit = (int)(ini_get('memory_limit'));
            $upload_mb = min($max_upload, $max_post, $memory_limit);
            echo " " . $upload_mb . " MB";      
        } else {        
            _e("Embed this ePaper:",'1000grad-epaper');
            echo "<ul>";                     
            if ($channelnr == "1") echo "<li><b><a href=widgets.php>via Widget</a></b>";                        
            echo "<li>";          
            _e("wordpress shortcode:",'1000grad-epaper');            
            if ($channelnr == "1") echo "<br /><b>[ePaper]</b>";
            else echo "<br /><b>[ePaper nr=" . $channelnr . "]</b></li>";            
             echo "<li>";          
            _e("or",'1000grad-epaper');
            echo " [ePaper url=" . $channelinfo->url . "]</li>";
            echo "<li>";
//            print_r($channelinfo);
            if ($channelnr == "1")
              echo " <a href=?page=epaper_channels&modus=postpost" . "><b>";
            else 
              echo " <a href=?page=epaper_channels&modus=postpost&url=" . htmlentities ($channelinfo->url) . "><b>";
                
            _e("Create a new post with this ePaper",'1000grad-epaper');
            echo "</b></a></li>";
            _e("<li>HTML code for advanced users:",'1000grad-epaper');
            echo "<p><small><code>" . htmlentities("<a href=".$channelinfo->url . "><img class=alignright src=" . $channelinfo->url 
                . "epaper/preview.jpg></a>") . "</code></small></li>";
            echo "</ul>";
       }    
       $url = $channelinfo->url;
       $this->epaperOptions = get_option("plugin_epaper_options");
       $this->epaperOptions['channelurl' . $channelnr] = $url;
       $this->epaperOptions['channeltitle'.$channelnr] = $channelinfo->title;
       update_option("plugin_epaper_options", $this->epaperOptions);
       echo "</td></tr></table>";
       $html->close3Div();
    }

    /**
     * Anzeige in der Zentralansicht von allen ePaper Kanaelen
     */
    public function epaperMetaBox() 
    {
        $html = new EpaperHtml();
        $channelApi = new EpaperChannelApi();        
        $channels = json_decode($channelApi->getChannelsList($this->apikey));   
        $channelcount = count($channels->channels);
        if ($channelcount == "0") {
            $expr = __('no ePaper Channel existing!','1000grad-epaper');
            $html->h2($expr);
        }
        _e("insert this shortcode into editor:",'1000grad-epaper');
        $channelnr = 0;
        foreach ($channels->channels as $channell ) {
            $channelnr = $channelnr + 1;
            $channelinfo = json_decode($channelApi->getChannelInfo($this->apikey, $channell->id));          
            $this->epaperChannelShowBox($channelinfo, $channelnr);
        }
        $html->brClearAll();
        $html->hr();
        _e("<br /><b>Hint:</b> You can add the 'class' option for further layoutstyles. F.i. <b>[ePaper class=alignleft]</b> to align your ePaper to the left.",'1000grad-epaper');

  }

    /**
     * Box, die beim Editieren und Posten von Beitraegen angezeigt wird. 
     * @param type $channelinfo
     * @param type $channelnr
     */
    public function epaperChannelShowBox($channelinfo, $channelnr) 
    {
        echo "<br clear=all><hr>";
        $url = $channelinfo->url;
        if ($channelnr=="1") echo "<b>[ePaper]</b>";
        else echo "<b>[ePaper url=".$url."]</b>";
//        else echo "<b>[ePaper nr=".$channelnr."]</b>";
        if (!isset($channelinfo->id_epaper)) { 
            echo __("<br />Channel empty<br />please upload a file",'1000grad-epaper');
        } else {
            echo "" . $channelinfo->title . "<br />";
            $htmlf = "<a class='iframe cboxElement' href='";
            $htmlf .= $url."'";
            $htmlf .= '> <img src="';
            $htmlf .= $url . 'epaper/epaper-ani.gif?rnd=' . rand(1000, 9999) 
                   . '" alt="epaper preview gif" border="0" /></a>';
            echo $htmlf;
        }
    }
}
