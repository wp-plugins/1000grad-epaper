<?php
/*
Plugin Name: 1000°ePaper
Plugin URI: http://epaper-apps.1000grad.com/
Description: Easily create browsable ePapers within Wordpress! Convert your PDFs to online documents by using the 1000° ePaper service. Embed it via widget or shortcode.  1000°ePaper is an electronic publishing service that allows you to quickly and easily create native page flipping electronic publications such as e-Books, e-Catalogs, e-Brochures, e-Presentations and much more.
Version: 1.4.2
Author: 1000°DIGITAL Leipzig GmbH
Author URI: http://www.1000grad.de
License:

  Copyright (C) 2013 1000grad Digital GmbH (info@1000grad.de)

  This program is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License, version 2, as 
  published by the Free Software Foundation.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program; if not, write to the Free Software
  Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA

Using the awesome plugin boilerplate by Tom MsFarlin: https://github.com/tommcfarlin/WordPress-Plugin-Boilerplate

*/
/*error_reporting(0);*/

require_once("lib/epaperApi.php");
require_once("lib/epaperApikeyApi.php");
require_once("lib/epaperChannelApi.php");

class TG_Epaper_WP_Plugin {
    
    static $sPluginVersion = "1.4.2";
    
    private $aEpaperOptions = array();  
    
    private $bKeyRefreshed = false;
    
    private $bIsRegistered; //true|false
    
    private $sBasePluginPath = '1000grad-epaper/';
    private $sTemplatePath = 'views/';
    private $sMainTemplate = 'adminpage_epaper_template';
    private $sDefaultTitle = '1000°ePaper';
    private $ePaperSettingsFormTemplate = 'epaper_settings_form';
    private $sDefaultPreviewImage = 'epaper/epaper-ani.gif';
    private $sAgbAcceptIndex = 'agb_accepted';
    private $sEpaperOptionIndex = 'plugin_epaper_options';
    private $sWidgetClassIndex = 'widget_epaperwidgetclass';
    
    //Channel
    private $oChannelApi = NULL;
    //Account
    private $oAccountApi = NULL;
    //Epaper
    private $oEpaperApi  = NULL;
    
    private $sDefaultLang = 'en';
    
    private $sEpaperOptionsChannelConfig = "channel_config";
    private $sEpaperOptionsChannelDefaultUrl = "epaper_default_url";
    
    private $defaultFallback = "http://www.1kcloud.com/s3WQw4m/";
    private $sDefaultAccountApiUrl = "http://epaperplugin.1kcloud.com/api/v2/wordpress-wsdl";
    
    private $sPage = NULL;
    
    private $oView = NULL;
    private $aTemplateVars = array();
    private $bUseMainTemplate = true;
    private $sTemplate = NULL;

    //initializes the plugin by setting localization, filters, and administration functions
    function __construct($bRegisterActions = true) {

        if(!defined('TGE_PLUGIN_ACCOUNT_API_URI')){
            define( 'TGE_PLUGIN_ACCOUNT_API_URI', $this->sDefaultAccountApiUrl);
        }
        
        $this->load_epaper_options();
        $this->oView = new stdClass();
        
        $this->sPage = isset($_GET['page'])?$_GET['page']:NULL;
        $this->sDefaultLang = $this->getBlogDefaultLanguage();
        
        //Epaper API
        $this->oChannelApi = new EpaperChannelApi();
        $this->oAccountApi = new EpaperApikeyApi();
        $this->oEpaperApi  = new EpaperApi();
   
        ini_set('max_execution_time', 120);
        ini_set("soap.wsdl_cache_enabled", 1);
        ini_set("soap.wsdl_cache_ttl", 86400);  
        
        $this->is_registered();
        
        if($bRegisterActions == true):
            //ajax-action
            add_action( 'wp_ajax_nopriv_epaper_ajax', array( $this, 'fetchAjaxRequest' ) );
            add_action( 'wp_ajax_epaper_ajax', array( $this, 'fetchAjaxRequest' ) );

            //load plugin translations
            add_action( 'init', array( $this, 'plugin_textdomain' ) );     
            
            //load styles and scripts
            add_action( 'init', array( $this, 'action_admin_init_register_styles_and_scripts' ) ); 
            add_filter('the_posts', array( $this,'filter_posts_conditionally_add_scripts_and_styles'));   

            //custom actions            
            add_action('admin_menu', array( $this,'action_epaper_integration_menu'));    
            add_shortcode('ePaper', array( $this,'shortcode_epaper'));

            if($this->is_registered() == true):
                add_action( 'widgets_init', create_function('', 'return register_widget("EpaperWidgetClass");') );
                add_filter('mce_external_plugins', array ($this,'addScriptToTinymce' ) );
		add_filter('mce_buttons', array ($this,'registerTgTinyButton' ) );
                add_action('init', array($this, 'updatePlugin'));
            endif;

            add_action('add_meta_boxes', array( $this, 'action_add_metabox_epaper' ) );
            // drop a warning on each page of the admin when 1000grad-epaper hasn't been configured
            //add_action( 'admin_notices', array( $this, 'showRegistrationInfo' ) );
            
        endif;
        
        //register_uninstall_hook(__FILE__, array('TG_Epaper_WP_Plugin', 'uninstallPlugin'));
    }
    
    public static function uninstallPlugin(){
        if ( !defined( 'WP_UNINSTALL_PLUGIN' ) ) 
            exit();
        
        var_dump('uninstall');
        exit;
    }
    
    public function checkSoapIsActivated(){
        if(extension_loaded('soap') === false):
            $this->showWarning(__("<b>The 1000°ePaper plugin requires SOAP extension for PHP (php_soap).<br/><br/>Please ask your system administrator to activate it.</b>","1000grad-epaper"));
            return false;
        endif;
        
        return true;
    }    
    
    //returns current plugin-version
    public static function getPluginVersion(){
        return self::$sPluginVersion;
    }
    
    //update plugin
    public function updatePlugin(){
        if(!isset($this->aEpaperOptions['update_infos']) || (((time() - $this->aEpaperOptions['update_infos']) / 86400) > 30)):
            $this->oAccountApi->updatePluginInfos();
            $this->aEpaperOptions['update_infos'] = time();
            update_option($this->sEpaperOptionIndex, $this->aEpaperOptions);
        endif;
    }

    public function get($sVar){
        return $this->{$sVar};
    }
    
    public function set($sVar, $sValue){
        $this->{$sVar} = $sValue;
        return true;
    }
    
    //registers plugin-button in tinymce-editor
    function registerTgTinyButton($aButtons) {
            array_push($aButtons, "|", "tg_tiny_button");
            return $aButtons;
    }

    //adds js-script to tinymce-editor
    function addScriptToTinymce($aPluginArray) {
            $aPluginArray['tg_tiny_button'] = plugins_url($this->sBasePluginPath.'/js/tg_tinymce.js');
            return $aPluginArray;
    }

    //initialize default plugin-configuration
    private function load_epaper_options(){
        $this->aEpaperOptions = get_option($this->sEpaperOptionIndex);
        if($this->aEpaperOptions == false):
            $this->aEpaperOptions = array(
            'wordpressapi' => TGE_PLUGIN_ACCOUNT_API_URI);        
            update_option($this->sEpaperOptionIndex, $this->aEpaperOptions);
        endif;
    }
    
    //returns registered status
    public function is_registered(){
        $this->apikey = isset($this->aEpaperOptions['apikey'])?$this->aEpaperOptions['apikey']:NULL;
        if (!empty($this->apikey)) {
            $this->bIsRegistered = true;
        } else {
            $this->bIsRegistered = false;
        }
        
        return $this->bIsRegistered;        
    }
    
    //loads plugin translation file
    public function plugin_textdomain() {
        load_plugin_textdomain('1000grad-epaper', false, '1000grad-epaper/lang');
    }
    
    //registers (the pathes of) admin-specific styles and scripts
    public function action_admin_init_register_styles_and_scripts() {            
        wp_register_style('style_colorbox',             plugins_url($this->sBasePluginPath.'colorbox/colorbox.css'));
        wp_register_style('tg_styles',                  plugins_url($this->sBasePluginPath.'css/tg_styles.css'));
        wp_register_script('jquery_migrate',            plugins_url($this->sBasePluginPath.'js/jquery_migrate.js'));
        wp_register_script('jquery2',                   plugins_url($this->sBasePluginPath.'js/jquery.2.0.3.js'));
        wp_register_script('tg_script_js',              plugins_url($this->sBasePluginPath.'js/tg_script.js'), array('jquery')); // benötigt jquery
        wp_register_script('js_colorbox_min',           plugins_url($this->sBasePluginPath.'colorbox/jquery.colorbox-min.js'), array('jquery'));
        wp_register_script('colorbox-epaper',         plugins_url($this->sBasePluginPath.'js/colorbox-epaper.js'), array('jquery'));      
        wp_register_script('uploadify_js',              plugins_url($this->sBasePluginPath.'js/uploadify/jquery.uploadify.js'), array('jquery2'));  
    }

    //init scripts and styles
    public function filter_posts_conditionally_add_scripts_and_styles ($posts) 
    {
        if (!empty($posts)) {     
            wp_enqueue_style('tg_styles');     
            wp_enqueue_script('jquery'); 
            wp_enqueue_script('js_colorbox_min', plugins_url('1000grad-epaper/colorbox/jquery.colorbox-min.js'), array('jquery'));
            wp_enqueue_script('colorbox-epaper', plugins_url('1000grad-epaper/js/colorbox-epaper.js'), array('jquery'));
            wp_enqueue_style('style_colorbox', plugins_url('1000grad-epaper/colorbox/colorbox.css'));
            return $posts;
        }
        return $posts;
    }


    //registers and enqueues plugin-specific styles.
    public function action_enqueue_scripts_for_all_adminpages() 
    {
        switch($this->sPage):
            case 'epaper_channels':
                wp_enqueue_script('jquery2');  
                wp_enqueue_script('jquery_migrate');
                wp_enqueue_script('uploadify_js');        
            default:
                wp_enqueue_style('style_colorbox');
                wp_enqueue_style('uploadify');
                wp_enqueue_style('tg_styles');       
                wp_enqueue_script('js_colorbox_min');
                wp_enqueue_script('colorbox-epaper');    
                wp_enqueue_script('tg_script_js');
                wp_localize_script( 'tg_script_js', 'TGELocalData', array(
                        'ajaxurl'       => admin_url( 'admin-ajax.php' ),
                        'tge_nonce'     => wp_create_nonce( 'epaper_ajax-nonce' ),
                )); 
                break;
        endswitch;
    }
    
    //integrate epaper-plugin to main-menu
    public function action_epaper_integration_menu() 
    {                  
        add_action( 'admin_enqueue_scripts', array($this,'action_enqueue_scripts_for_all_adminpages' ));
        
        add_menu_page(
            'ePaper', 
            '1000°ePaper', 
            'upload_files', 
            'epaper_channels', 
            array($this, 'adminpage_epaper_channels'), 
            plugins_url($this->sBasePluginPath."img/1000grad_icon.png")
        );
        
        if ($this->bIsRegistered === false):   
            add_submenu_page(
                'epaper_channels', 
                'ePaper '.__('Registration','1000grad-epaper'), 
                __('Registration','1000grad-epaper'), 
                'upload_files', 
                'epaper_apikey', 
                array($this, 'adminpage_epaper_apikey')
            );
        else:
            add_submenu_page(
                'epaper_channels', // parent
                'ePaper '.__('Manage Subscription','1000grad-epaper'), 
                __('Manage Subscription','1000grad-epaper'), 
                'upload_files', 
                'epaper_subscription', 
                array($this, 'adminpage_epaper_subscription')
            );            
        endif;
        
        add_options_page( '1000°ePaper', '1000°ePaper', 'upload_files','epaper_settings', array($this,'adminpage_epaper_settings'));            
    }
    
    //add metabox to page|post-editor
    public function action_add_metabox_epaper()    
    {
        if ($this->bIsRegistered === true)  {  
            add_meta_box('epaper_editorbox', '1000°ePaper', array($this, 'meta_box_epaper'), 'post', 'side', 'high');
            add_meta_box('epaper_editorbox', '1000°ePaper', array($this, 'meta_box_epaper'), 'page', 'side', 'high');
            return true;
        }
        
        return false;
    } 
    
    //shows registration-info, if isRegistered == false
    public function showRegistrationInfo()
    {        
        if ($this->bIsRegistered === false && !isset($_POST['registration_key_requested']) && (!isset($_GET['email']) && !isset($_GET['code']) )) {
            
            $sMessage = sprintf("%s <br/><br/> %s", __( "1000°ePaper is not registered yet.", '1000grad-epaper' ), sprintf(__( "Please %sregister your installation%s.", '1000grad-epaper' ), 
                    "<a href='admin.php?page=epaper_apikey'>", "</a>"));
            
            $this->showInfo($sMessage);
       }
    }
    
    //shortcode function of plugin
    public function shortcode_epaper($aArgs) 
    {
        if ($this->bIsRegistered === true)  {
            $iChannel = (isset($aArgs['nr']) && !empty($aArgs['nr']))?$aArgs['nr']:1;
            $iPage = (isset($aArgs['page']) && !empty($aArgs['page']))?$aArgs['page']:1;
            $oChannels = $this->getChannels();
            $oChannel = isset($oChannels->channels[($iChannel-1)])?$oChannels->channels[($iChannel-1)]:NULL;
            if($oChannel == NULL) return false;

            $sClass = (isset($aArgs['class']) && !empty($aArgs['class']))?$aArgs['class']:'alignleft';
            $sLink = $this->getEpaperLink($oChannel, NULL, array('class' => $sClass, 'page' => $iPage));

            $this->bUseMainTemplate = false;
            $this->oView->class = $sClass;
            $this->oView->link = $sLink;

            ob_start();
            $this->showContent();
            $sShortcodeContent = ob_get_contents();
            ob_end_clean();

            return $sShortcodeContent;
        }
        
        return false;
    }
    
    //registration-adminpage
    public function adminpage_epaper_apikey()
    {
        if(!$this->checkSoapIsActivated()) return false;
        
        if(isset($_POST['register_account'])):
            $this->sendRegistrationEmail();
            $sEmail = (isset($_POST['apikey_email']) && !empty($_POST['apikey_email']))?$_POST['apikey_email']:NULL;
            if($sEmail !== NULL):
                $this->aEpaperOptions['email'] = $sEmail;
                update_option($this->sEpaperOptionIndex, $this->aEpaperOptions);
            endif;
        endif;
        
        $this->oView->registration_error = false;
        $this->oView->email_submitted = true;
        $this->oView->message = NULL;
        $this->oView->registration_code_entered = false;
        
        switch(isset($_GET['code'])):
            case true:
                $this->oView->code = trim(htmlspecialchars($_GET['code']));  
                $this->oView->email = trim(htmlspecialchars($_GET['email']));
                $this->oView->registration_code_entered = true;

                try {
                    $oResult = ($this->oAccountApi->sendCodeGetApikey($this->oView->email, $this->oView->code));
                } catch (SoapFault $e) {
                    $this->showWarning("error on receiving apikey. ".$e->getMessage());
                    die();
                }
                
                if ($oResult == false):
                    $this->showWarning(sprintf('%s %s', __("ePaper Registration fault.", '1000grad-epaper'), __("Please type in the confirmation code you was receiving via email.", '1000grad-epaper')));
                else:
                    $oResult = json_decode($oResult);
                    $this->aEpaperOptions['email'] = $this->oView->email;
                    $this->aEpaperOptions['url'] = $oResult->apiurl;
                    $this->aEpaperOptions['apikey'] = $oResult->apikey;
                    $this->aEpaperOptions['apikey_as'] = $oResult->apikey_as;
                    update_option($this->sEpaperOptionIndex, $this->aEpaperOptions);
                    $this->bIsRegistered = true;
                    $this->showInfo(sprintf('%s', __("<p>Now you can use this ePaper Plugin!</p>", '1000grad-epaper')));
                endif;
                
                $this->oView->registration_error = ($oResult==false)?true:false;
                
                break;
            
            case false:
                if(isset($this->aEpaperOptions['email']) && $this->aEpaperOptions['email'] != ""):
                    $this->oView->email_submitted = true;
                else:
                    $this->oView->email_submitted = false;
                endif;
                
                break;
        endswitch;

        $this->aTemplateVars = array('TITLE' => __("1000°ePaper Registration","1000grad-epaper"));
        $this->sTemplate = 'adminpage_epaper_apikey';
        $this->bUseMainTemplate = true;
        $this->showContent();          
    }
    
    //registration-email
    private function sendRegistrationEmail(){
        $sEmail = trim(htmlspecialchars($_POST['apikey_email']));
        $bAgb = isset($_POST['agb'])?true:false;
        $bNewsletter = isset($_POST['newsletter'])?true:false;
        
        $sLanguage = __("en",'1000grad-epaper');
        
        global $wp_version;
        $sWordpressVersion = $wp_version;
        $sPhpVersion = phpversion();

        $sAdminUrl = admin_url();
        $sSubject = "wordpress";
        $sWordpressCode = "";
        
        $aEpaperOptions = array(
            'email' => $sEmail, 
            'text' => $sSubject,
            'agb' => ($bAgb === true)?'yes':'no',
            'wordpressapi' => TGE_PLUGIN_ACCOUNT_API_URI,
            'newsletter' => ($bNewsletter === true)?'yes':'no' );
        
        update_option($this->sEpaperOptionIndex, $aEpaperOptions);  

        try {
            $sResponseMessage = $this->oAccountApi->getRegistrationCodeByEmail($sEmail, $sSubject, $sAdminUrl, NULL,
                                   NULL, $sWordpressCode, ($bAgb === true)?'yes':'no', ($bNewsletter === true)?'yes':'no', $sWordpressVersion ,$sPhpVersion, $sLanguage);
           
            if(isset($sResponseMessage['info'])):
                $sMessage = $sResponseMessage['info'];
                $this->showInfo($sMessage);
            elseif(isset($sResponseMessage['error'])):
                $sMessage = $sResponseMessage['error'];
                $this->showWarning($sMessage);
            endif;
            
        } catch (SoapFault $e) {
            $this->showWarning("error on receiving apikey. ".$e->getMessage());
            die();
        }
    }    
    
    //subscription-adminpage
    public function adminpage_epaper_subscription()
    {
        if(!$this->checkSoapIsActivated()) return false;
        $this->showRegistrationInfo();
        
        try {
            $sLanguage = substr(get_bloginfo ( 'language' ), 0, 2);
            $sPPButton = $this->oAccountApi->getPPButtonCode(($sLanguage != NULL && $sLanguage != false)?$sLanguage:'en');
        }catch(SoapFault $e){
            $this->showWarning("error while connecting to account-api ".$e->getMessage());
            die();
        }

        if(is_object($sPPButton) && get_class($sPPButton) == 'WP_Error' && $this->bKeyRefreshed == false):
            $this->bKeyRefreshed = true;
            $this->oAccountApi->refreshKeys();
            $this->load_epaper_options();
            $this->adminpage_epaper_subscription();
            return false;
        endif;
        $this->oView->button_code = (is_string($sPPButton))?json_decode($sPPButton):array();
        $this->aTemplateVars = array('TITLE' => sprintf('%s - %s', $this->sDefaultTitle, __("Manage Your Subscription",'1000grad-epaper')));
        $this->showContent();             
    }     
    
    //settings-adminpage
    public function adminpage_epaper_settings()
    {   
        if(!$this->checkSoapIsActivated()) return false;
        $this->showRegistrationInfo();

        $this->oView->feedback_sent = false;
        $this->aTemplateVars = array('TITLE' => sprintf('%s - %s',$this->sDefaultTitle, 'Settings'));
        
        global $tge_plugin_data;
        $tge_plugin_data = get_plugin_data(__FILE__);
            
        //save settings 
        if (isset($_GET['epaper-settings-save'])) $this->saveEpaperSettings(); 
        if (isset($_POST['feedback'])):
           $this->saveEpaperFeedback();
        endif;
        
        $this->sTemplate = "adminpage_epaper_settings";
        
        $this->showContent();          
    }
    
    //action of feedback-form
    private function saveEpaperFeedback(){
        $sText = $_POST['text'];
        
        global $wp_version;

        $sLanguage = __("en",'1000grad-epaper');
        $sWordpressVersion = $wp_version;
        $sPhpVersion = phpversion();
        $sPluginVersion = $this->getPluginVersion();
        $this->oView->feedback_sent = $this->oAccountApi->sendFeedback($this->aEpaperOptions['email'], $sText, NULL, admin_url(), NULL, 
            NULL,$sWordpressVersion, $sPhpVersion, $sLanguage, $sPluginVersion
        );  
    }
    
    //saving epaper-settings
    private function saveEpaperSettings(){
        $this->aEpaperOptions['url'] = htmlspecialchars($_GET['epaper_url']);
        $this->aEpaperOptions['wordpressapi'] = htmlspecialchars($_GET['epaper_wordpressapi']);
        $this->aEpaperOptions['apikey'] = htmlspecialchars($_GET['epaper_apikey']); 
        $this->aEpaperOptions['apikey_as'] = htmlspecialchars($_GET['apikey_as']); 
        update_option($this->sEpaperOptionIndex, $this->aEpaperOptions);  
    }
    
    //returns object of user-channels
    public function getChannels($bUpdate = NULL){
        if(isset($this->aEpaperOptions[$this->sEpaperOptionsChannelConfig]) && $bUpdate == false) return json_decode($this->aEpaperOptions[$this->sEpaperOptionsChannelConfig]);
        $oChannels = $this->getChannelConfigObject();
        $this->aEpaperOptions[$this->sEpaperOptionsChannelConfig] = json_encode($oChannels);
        update_option($this->sEpaperOptionIndex, $this->aEpaperOptions);
        return $oChannels;
    }
    
    //returns info-object of user-channel
    public function getChannelInfos($iChannelId = NULL, $bForceRefresh = false){
        if($iChannelId == NULL) return false;
        
        if($bForceRefresh):
            $oChannel = json_decode($this->oChannelApi->getChannelInfo($this->aEpaperOptions['apikey'], $iChannelId));
            return $oChannel;
        else:
            $oChannelConfig = $this->getChannelConfigObject()->channels;
            foreach($oChannelConfig as $iIndex => $oChannel):
                if($oChannel->id == $iChannelId) return $oChannel;
            endforeach;
        endif;
        
        return false;
    }
    
    //returns info-object of user-epaper
    public function getEpaperInfos($iEpaperId = NULL, $bForceRefresh = false){
        if($iEpaperId == NULL) return false;
        
        if($bForceRefresh):
            $oEpaper = json_decode($this->oEpaperApi->returnEpaperInfos($this->aEpaperOptions['apikey'], $iEpaperId));
            return $oEpaper;
        else:
            $oChannelConfig = $this->getChannelConfigObject()->channels;
            foreach($oChannelConfig as $iIndex => $oChannel):
                if($oChannel->epaperInfo->id == $iEpaperId) return $oChannel->epaperInfo;
            endforeach;
        endif;
        
        return false;        
    }
    
    //channel-list
    public function adminpage_epaper_channels(){
        if (!$this->checkSoapIsActivated())
            return false;
        
        if (isset($_POST['agb'])):
            $this->aEpaperOptions[$this->sAgbAcceptIndex] = true;
            update_option($this->sEpaperOptionIndex, $this->aEpaperOptions);
        endif;
            
        $this->showRegistrationInfo();
        $this->clearAllChannelPreviewImages();
        $this->oView->bAgbWasAccepted = $this->agbWasAccepted();
        $this->oView->sAdminUrl = get_admin_url();
        if($this->bIsRegistered == true) $this->oView->channelobject = $this->getChannelConfigObject(true);
        $this->aTemplateVars = array('TITLE' => $this->sDefaultTitle);
        $this->bUseMainTemplate = true;
        $this->sTemplate = 'adminpage_epaper_channels';
        $this->showContent();
    }
    
    //backend warning-box
    public function showWarning($sMessage){
        $this->aTemplateVars = array('MESSAGE' => $sMessage);
        $this->bUseMainTemplate = false;
        $this->sTemplate = 'adminpage_epaper_warning';
        $this->showContent();
    }
    
    //backend info-box
    public function showInfo($sMessage){
        $this->aTemplateVars = array('MESSAGE' => $sMessage);
        $this->bUseMainTemplate = false;
        $this->sTemplate = 'adminpage_epaper_info';
        $this->showContent();        
    }    
    
    //metabox
    public function meta_box_epaper()
    {
        $this->oView->channelobject = $this->getChannels();
        $this->bUseMainTemplate = false;
        $this->showContent();    
    }  
    
    //returns default-link of empty channel
    public function getEpaperDefaultLink(){
        return isset($this->aEpaperOptions[$this->sEpaperOptionsChannelDefaultUrl]->url)?$this->aEpaperOptions[$this->sEpaperOptionsChannelDefaultUrl]->url:$this->defaultFallback;
    }
    
    //returns link to epaper
    public function getEpaperLink($channel = NULL, $epaper = NULL, $aConfig = array()){
        //$epaper can be integer or object (epaper_id | oEpaper)
        //$channel can be integer or object (channel_id | oChannel
        //link == 0 => overlay, link == 1 => extern
        $iDefault = 0;
        if($channel == NULL) return false;
        
        $oChannelInfo = is_object($channel)?$channel:$this->getChannelInfos($channel);
        $epaper = ($epaper == NULL)?$oChannelInfo->id_epaper:$epaper;
        $oEpaperInfo = is_object($epaper)?$epaper:$this->getEpaperInfos($epaper);
        
        if(isset($oEpaperInfo->settings->add_export_info)):
            $oLinksettings = json_decode($oEpaperInfo->settings->add_export_info);
            $iLinkType = isset($oLinksettings->linktype)?$oLinksettings->linktype:$iDefault;
        else:
            $iLinkType = 0;
        endif;
        
        if($oEpaperInfo == NULL){
            $sEpaperLink = $this->getEpaperDefaultLink();
            $sClass = 'class="ePaper"';
            $sImageSrc = sprintf('%s%s',$this->getEpaperDefaultLink(), $this->sDefaultPreviewImage);
        }else{
            $iPage = isset($aConfig['page'])?$aConfig['page']:NULL;
            $sEpaperLink = sprintf('%s%s', $oChannelInfo->url, ($iPage != NULL)?sprintf('#%u', $iPage):NULL);
            $sClass = ($iLinkType == 0)?'class="ePaper"':NULL;
            $sImageSrc = sprintf('%s%s', $oChannelInfo->url, $this->sDefaultPreviewImage);
        }

        $aImageSrc = $this->getChannelPreviewImage($oChannelInfo->id, $sImageSrc);
        $sParameter = is_user_logged_in()?sprintf('%s%u',"?rnd=",rand(1000,9999)):NULL;
        
        return sprintf('<a href="%s" %s target="_blank">
            <img class="tg_preview_image" src="%s%s"/>    
        </a>', $sEpaperLink, $sClass, $aImageSrc['url'], $sParameter);

    }
    
    //returns path|url of preview-image
    public function getChannelPreviewImage($iChannelId = NULL, $sImageSrc = NULL){
        if($iChannelId == NULL) return false;
        $aUploadUrl = wp_upload_dir();
        $sFilename = sprintf('epaper_preview_%u.gif', $iChannelId);
        $sFilePath = sprintf("%s/%s", $aUploadUrl['basedir'], $sFilename);
        $sFileUrl = sprintf("%s/%s", $aUploadUrl['baseurl'], $sFilename);
        
        if($sImageSrc == NULL):
            $sImageSrc = sprintf('%s%s',$this->getChannelInfos($iChannelId)->url, $this->sDefaultPreviewImage);
        endif;

        if(!file_exists($sFilePath)):
                $sImage = @file_get_contents($sImageSrc);
                $bFileExist = @file_put_contents($sFilePath, $sImage);
                if($bFileExist == false):
                    $sFileUrl = $sImageSrc;                    
                endif;
        endif;
        
        return array('path' => $sFilePath, 'url' => $sFileUrl);
    }

    //clears cached preview-image of specified channel
    public function clearChannelPreviewImage($iChannelId){
        $aFile = $this->getChannelPreviewImage($iChannelId);
        if(file_exists($aFile['path'])) unlink($aFile['path']);
    }
    
    //clears cached preview-image of all channels
    public function clearAllChannelPreviewImages(){
        if($this->bIsRegistered == true):
            foreach($this->getChannelConfigObject()->channels as $oChannel):
                $this->clearChannelPreviewImage($oChannel->id);
            endforeach;
        endif;
    }
    
    //ajax action
    function fetchAjaxRequest() {
        $this->refreshChannelConfigObject();
        
        $sActionOption = isset($_POST['ajax_option'])?$_POST['ajax_option']:NULL;
        if($sActionOption == NULL && isset($_GET['ajax_option'])) $sActionOption = $_GET['ajax_option'];

        switch($sActionOption):
            case 'startRendering':
                  $iPdfId = $_POST['pdfId'];
                  $sOldEpaperId = $_POST['oldEpaperId'];
                  $iChannelId = $_POST['channel_id'];

                  $sFilename = isset($_POST['filename'])?$_POST['filename']:'1000°ePaper';
                  $sDocumentName = str_replace(".pdf", "", $sFilename);
                  
                  ob_start();
                    if($sOldEpaperId != ''):
                        try {
                              $this->oChannelApi->removeEpaperFromChannel($this->aEpaperOptions['apikey'], $iChannelId);
                              $this->oEpaperApi->epaperDelete($this->aEpaperOptions['apikey'], $sOldEpaperId);
                          } catch (SoapFault $e) {
                              //
                          }
                    endif;
                  ob_end_clean();
                  
                  $iNewEpaperId = $this->oEpaperApi->epaperCreateFromPdf($this->aEpaperOptions['apikey'], $iPdfId);
                  
                  $aExtraInfo = array('linktype' => 0);
                  $this->oEpaperApi->epaperSetVar($this->aEpaperOptions['apikey'], $iNewEpaperId, "is_pdf_download", 0);
                  $this->oEpaperApi->epaperSetVar($this->aEpaperOptions['apikey'], $iNewEpaperId, "pdf_name", $sDocumentName);
                  $this->oEpaperApi->epaperSetVar($this->aEpaperOptions['apikey'], $iNewEpaperId, "title", $sDocumentName);
                  $this->oEpaperApi->epaperSetVar($this->aEpaperOptions['apikey'], $iNewEpaperId, 'add_export_info', json_encode($aExtraInfo));
                  foreach($this->getChannels()->channels as $iChannel => $aChannelConfig):
                      if($aChannelConfig->id == $iChannelId):
                            $this->oChannelApi->setChannelTitle($this->aEpaperOptions['apikey'], $iChannelId, sprintf('ePaper Channel #%u', ($iChannel+1)));
                      endif;
                  endforeach;
                  
                  $this->oEpaperApi->epaperStartRenderprocess($this->aEpaperOptions['apikey'], $iNewEpaperId);
                  echo $iNewEpaperId;
                break;
            //renderstatus
            case 'renderstatus':
                    $sMethod = isset($_POST['epaperId'])?'$_POST':'$_GET';
                    $iEpaperId = ($sMethod == '$_GET')?$_GET['epaperId']:$_POST['epaperId'];
                    
                    switch($sMethod):
                        case '$_POST':
                            $oInfos = json_decode($this->oEpaperApi->returnEpaperInfos($this->aEpaperOptions['apikey'],$iEpaperId));
                            $sJson = json_encode(array(
                                'render_percent' => $oInfos->renderprocess->percent,
                                'render_pages_text' => sprintf('(%s %u/%u)', __('page','1000grad-epaper'), $oInfos->renderprocess->current_page, $oInfos->pages)
                                ));
                            echo $sJson;
                            break;
                        
                        case '$_GET':
                            $bRenderReady = false;
                            while($bRenderReady == false):
                                $oInfos = json_decode($this->oEpaperApi->returnEpaperInfos($this->aEpaperOptions['apikey'],$iEpaperId));
                                $sJson = json_encode(array(
                                    'render_percent' => $oInfos->renderprocess->percent,
                                    'render_pages_text' => sprintf('(%s %u/%u)', __('page','1000grad-epaper'), $oInfos->renderprocess->current_page, $oInfos->pages)
                                ));
                                
                                header('Content-Type: text/event-stream');
                                header('Cache-Control: no-cache');
                                echo "data: ".$sJson;
                                echo "\n\n";
                                ob_end_flush();
                                flush();
                                sleep(1);
                                $bRenderReady = ($oInfos->renderprocess->percent == 100)?true:false;
                            endwhile;
                            
                            break;
                        
                    endswitch;
                    
                break;
            //publishstatus
            case 'publishstatus':
                    $sMethod = isset($_POST['epaperId'])?'$_POST':'$_GET';
                    $iEpaperId = ($sMethod == '$_GET')?$_GET['epaperId']:$_POST['epaperId'];
                    $iChannelId = ($sMethod == '$_GET')?$_GET['channelId']:$_POST['channelId'];
                    $sOutput = NULL;
                    
                    switch($sMethod):
                        case '$_POST':
                            $oInfos = json_decode($this->oEpaperApi->returnEpaperInfos($this->aEpaperOptions['apikey'],$iEpaperId));
                            $oChannelInfo = $this->getChannelInfos($iChannelId);
                            if($oInfos->published == 0 && $oInfos->status == 'ready' && $oChannelInfo->id_epaper == ''):
                                $this->oChannelApi->publishEpaperToChannel($this->aEpaperOptions['apikey'],$iEpaperId, $iChannelId);
                                $sOutput = 0;
                            elseif( ($oInfos->published == 0 && $oChannelInfo->status != '' && $oInfos->status == 'do_publish_to_channel') || $oInfos->published == 1):
                                $sOutput = 50;
                            elseif($oChannelInfo->status == ''):
                                $sOutput = 100; 
                            endif;
                            echo $sOutput;
                            
                            break;
                        
                        case '$_GET':
                            $bPublishReady = false;
                            while($bPublishReady == false):
                                $oInfos = json_decode($this->oEpaperApi->returnEpaperInfos($this->aEpaperOptions['apikey'],$iEpaperId));
                                $oChannelInfo = $this->getChannelInfos($iChannelId);

                                if($oInfos->published == 0 && $oInfos->status == 'ready' && $oChannelInfo->id_epaper == ''):
                                    $this->oChannelApi->publishEpaperToChannel($this->aEpaperOptions['apikey'],$iEpaperId, $iChannelId);
                                    $sOutput = 0;
                                elseif( ($oInfos->published == 0 && $oChannelInfo->status != '' && $oInfos->status == 'do_publish_to_channel') || $oInfos->published == 1):
                                    $sOutput = 50;
                                elseif($oChannelInfo->status == ''):
                                    $sOutput = 100; 
                                    $bPublishReady = true;
                                endif;
                                
                                header('Content-Type: text/event-stream');
                                header('Cache-Control: no-cache');
                                echo "data: ".$sOutput;
                                echo "\n\n";
                                flush();
                                
                                sleep(1);
                                
                            endwhile;
                            
                            break;
                    endswitch;
                    
                break;
            //reload channellist
            case 'loadChannelList':
                    $this->adminpage_epaper_channels();
                break;
            //tinymce
            case 'tg_tiny_mce_button':
                    $this->oView->channelobject = $this->getChannels();
                    $this->bUseMainTemplate = false;
                    $this->sTemplate = 'tiny_mce_box';
                    $this->showContent();
                break;
            //clear Channel
            case 'clearChannel':
                    $iChannelId = isset($_POST['channel'])?$_POST['channel']:NULL;
                    if($iChannelId != NULL):
                        $oChannelInfo = $this->getChannelInfos($iChannelId);
                         try {
                            $this->oChannelApi->removeEpaperFromChannel($this->aEpaperOptions['apikey'], $iChannelId);
                            $this->oEpaperApi->epaperDelete($this->aEpaperOptions['apikey'], $oChannelInfo->id_epaper);
                        } catch (SoapFault $e) {
                            $this->showWarning("error while clearing channel. ".$e->getMessage());
                        }
                    endif;
                    $this->adminpage_epaper_channels();
                break;
            //save epaper-settings    
            case 'setEpaperSettings':
                    $iEpaperId = isset($_POST['epaper_id'])?$_POST['epaper_id']:NULL;
                    $iChannelId = isset($_POST['channel_id'])?$_POST['channel_id']:NULL;
                    $bPublish = (isset($_POST['do_publish']) && $_POST['do_publish'] == 'false')?false:true;
                    $aExtraInfos = array();
                    $aEpaperSettings = $this->getEpaperSettings();
                    if($iEpaperId == NULL || $iChannelId == NULL) return false;
                    
                    foreach($_POST['data'] as $iIndex => $aConfig):
                        $aFieldSettings = (isset($aEpaperSettings[$aConfig['name']]))?$aEpaperSettings[$aConfig['name']]:NULL;
                        switch($aFieldSettings['save_option']):
                            case 'extra_infos':
                                    $aExtraInfos[$aConfig['name']] = $this->escapeString($aConfig['value']);
                                break;
                            case 'channel_title':
                                    $sTitle = $aConfig['value'];
                                    if($iChannelId != NULL) $this->oChannelApi->setChannelTitle($this->aEpaperOptions['apikey'], $iChannelId, $this->escapeString($sTitle));
                                break;
                            default:
                                $this->oEpaperApi->epaperSetVar($this->aEpaperOptions['apikey'], $iEpaperId, $aConfig['name'], $this->escapeString($aConfig['value']));
                                break;
                        endswitch;
                    endforeach;
                    
                    if(count($aExtraInfos) > 0):
                        $this->oEpaperApi->epaperSetVar($this->aEpaperOptions['apikey'], $iEpaperId, 'add_export_info', json_encode($aExtraInfos));
                    endif;

                    if($bPublish) $this->oChannelApi->publishEpaperToChannel($this->aEpaperOptions['apikey'],$iEpaperId, $iChannelId);
                break;
            //reset plugin    
            case 'deleteAccount':
                    delete_option($this->sWidgetClassIndex);
                    delete_option($this->sEpaperOptionIndex);
                break;
            
            case 'translateUploadErrorMessage':
                    /*
                    2100 	Pdf konnte nicht analysiert werden
                    2101 	Pdf ist kein valides PDF Dokument
                    2102 	Pdf enthält keine Seiten
                    2103 	Pdf ist verschlüsselt und kann nicht verarbeitet werden
                    2104 	Pdf enthält mehr Seiten als erlaubt 
                    */
                    $sErrorCode = isset($_POST['errorCode'])?$_POST['errorCode']:NULL;
                    switch($sErrorCode):
                        case '2100':
                            _e('Upload-Error: Pdf could not be analyzed', '1000grad-epaper');
                        break;

                        case '2101':
                            _e('Upload-Error: Pdf is not a valid PDF document', '1000grad-epaper');
                            break;

                        case '2102':
                            _e('Upload-Error: Pdf contains no content', '1000grad-epaper');
                            break;

                        case '2103':
                            _e('Upload-Error: Pdf is encrypted and can not be processed', '1000grad-epaper');
                            break;

                        case '2104':
                            _e('Upload-Error: Pdf contains more pages than allowed', '1000grad-epaper');
                            break;
                        default:
                            _e('Upload-Error: undefined', '1000grad-epaper');
                            break;
                    endswitch;
                
                break;
             case 'acceptAgb':
                    $this->aEpaperOptions[$this->sAgbAcceptIndex] = true;
                    update_option($this->sEpaperOptionIndex, $this->aEpaperOptions);
                 break;
            
             default:
                die('Ajax-Action not found!');
                break;
            
        endswitch;
        
        die();
    }
    
    //available epaper-settings
    public function getEpaperSettings(){
        return array(
            'dependency' => array('pdf_name' => array('is_pdf_download' => 1), 'title' => array('linktype' => 1)),
            'channel_title' => array('publish' => 0, 'save_option' => 'channel_title', 'type' => 'input', 'translation' => __('Channel-title','1000grad-epaper'), 'helptext' => __('internal channel name (for administration)', '1000grad-epaper')),
            'linktype' => array('publish' => 0, 'save_option' => 'extra_infos', 'type' => 'select', 'translation' => __('Link-type','1000grad-epaper'), 'helptext' => __('open the ePaper in an overlayer box or in a new window/tab', '1000grad-epaper'), 'values' => array(0 => __('overlay', '1000grad-epaper'), 1 => __('extern', '1000grad-epaper')) ),
            'title' =>  array('publish' => 1 ,'save_option' => 'epaper_config', 'type' => 'input', 'translation' => __('Tab-Title','1000grad-epaper'), 'helptext' => __('title of the browser-tab, when opening the ePaper','1000grad-epaper')),
            'is_pdf_download' => array('publish' => 1, 'save_option' => 'epaper_config', 'type' => 'select', 'translation' => __('PDF Download','1000grad-epaper'), 'helptext' => __('allow user to download this ePaper as PDF (a download link is shown inside the ePaper)','1000grad-epaper'),'values' => array(0 => __('no','1000grad-epaper'), 1 => __('yes','1000grad-epaper'))),
            'pdf_name' => array('publish' => 1, 'save_option' => 'epaper_config', 'type' => 'input', 'translation' => __('PDF Filename','1000grad-epaper'), 'helptext' => __('filename of the pdf, when downloading','1000grad-epaper')),
            'language'  => array('publish' => 1, 'save_option' => 'epaper_config', 'type' => 'select','translation' => __('ePaper Language','1000grad-epaper'), 'helptext' => __('set the language of the epaper-navigation','1000grad-epaper'), 'values' => $this->getAvailableLanguages()));
    }
    
    //returns epaper-settings form
    public function getEpaperSettingsForm($oEpaperInfos = NULL, $oChannelInfos = NULL){
        $oEpaperInfos = isset($oEpaperInfos->settings)?$oEpaperInfos->settings:NULL;
        $aExtraInfos = json_decode($oEpaperInfos->add_export_info);
        $aEpaperSettings = $this->getEpaperSettings();
        
        $aDependency = isset($aEpaperSettings['dependency'])?$aEpaperSettings['dependency']:array();
        unset($aEpaperSettings['dependency']);
        
        foreach($aEpaperSettings as $sIndex => $aConfig):
            switch($aConfig['save_option']):
                case 'channel_title':
                        $aEpaperSettings[$sIndex]['default'] = isset($oChannelInfos->title)?$oChannelInfos->title:NULL;
                    break;
                
                case 'extra_infos':
                        $aEpaperSettings[$sIndex]['default'] = isset($aExtraInfos->{$sIndex})?$aExtraInfos->{$sIndex}:NULL;
                    break;
                
                case 'epaper_config':
                        $aEpaperSettings[$sIndex]['default'] = isset($oEpaperInfos->{$sIndex})?$oEpaperInfos->{$sIndex}:NULL;
                    break;
            endswitch;
        endforeach;
        
        ob_start();
        include sprintf('%s%s.php',$this->sTemplatePath,$this->ePaperSettingsFormTemplate);
        $sEpaperSettingsForm = ob_get_contents();
        ob_end_clean();
        
        return $sEpaperSettingsForm;
    }
    
    //content function
    public function showContent(){
        
        $aCallers = debug_backtrace();
        $sTemplate = ($this->sTemplate == NULL && isset($aCallers[1]['function']))?$aCallers[1]['function']:$this->sTemplate;

        ob_start();
        include sprintf('%s%s.php', $this->sTemplatePath, $this->sMainTemplate);
        $sMainTemplate = ob_get_contents();
        ob_end_clean();
        
        ob_start();
        include sprintf('%s%s.php',$this->sTemplatePath,$sTemplate);
        $sContent = ob_get_contents();
        ob_end_clean();
        
        if($this->bUseMainTemplate == true):
            $sContent = str_replace("%CONTENT%", $sContent, $sMainTemplate);
        endif;

        if(count($this->aTemplateVars) > 0):
            foreach($this->aTemplateVars as $sVar => $sValue):
                $sContent = str_replace("%".$sVar."%", $sValue, $sContent);
            endforeach;
        endif;        
        
        echo $sContent;
    }
    
    //returns available languages of epaper-player
    public function getAvailableLanguages(){
        return array(
            "de" => __('german','1000grad-epaper'), 
            "en" => __('english','1000grad-epaper'),
            "es" => __('espanol','1000grad-epaper'),
            "fr" => __('francais','1000grad-epaper'),
            "it" => __('italiano','1000grad-epaper'),
            'da' => __('danish','1000grad-epaper'),
            'nl' => __('dutch','1000grad-epaper'),
            'fi' => __('finnish','1000grad-epaper'),
            'cs' => __('czech','1000grad-epaper'),
            'ru' => __('russian','1000grad-epaper'),
            'hr' => __('croatian','1000grad-epaper'),
            'ro' => __('Romanian', '1000grad-epaper'),
            'sk' => __('Slovak', '1000grad-epaper'),
            'sl' => __('Slovenian', '1000grad-epaper'),
            'hu' => __('Hungarian', '1000grad-epaper'),
            'pl' => __('polish','1000grad-epaper'),
            'pt' => __('portuguese','1000grad-epaper'),
            'tr' => __('turkish','1000grad-epaper'),
            'bg' => __('bulgarian','1000grad-epaper'),
            'ja' => __('japanese','1000grad-epaper'),
            'el' => __('greek','1000grad-epaper'),
            'zh_Hans' => __('simplified chinese','1000grad-epaper'),
            'zh_Hant' => __('traditional chinese','1000grad-epaper')       
        );
    }
    
    //returns blog-language
    private function getBlogDefaultLanguage(){
        $sLangCode = get_bloginfo('language'); //en_EN
        $aLangCode = explode("_", $sLangCode);
        return isset($aLangCode[0])?$aLangCode[0]:false;
    }
    
    //returns true if channel exists
    public function channelExists($iChannelId){
        $oChannelInfo = json_decode($this->oChannelApi->getChannelInfo($this->aEpaperOptions['apikey'], $iChannelId));
        return !empty($oChannelInfo);
    }
    
    //returns channel-object of user-account
    public function getChannelConfigObject($bForceRefresh = false){
        
        if(!isset($this->aEpaperOptions[$this->sEpaperOptionsChannelConfig]) || $bForceRefresh):
            
            $oChannels = json_decode($this->oChannelApi->getChannelsList($this->aEpaperOptions['apikey']));
            foreach($oChannels->channels as $iIndex => $oChannel):
                $oEpaperInfo = $this->getEpaperInfos($oChannel->id_epaper, true);
                $oChannels->channels[$iIndex]->epaperInfo = $oEpaperInfo;
            endforeach;
        
            $this->aEpaperOptions[$this->sEpaperOptionsChannelConfig] = json_encode($oChannels);
            
            $this->aEpaperOptions[$this->sEpaperOptionsChannelDefaultUrl] = $this->oAccountApi->getDefaultEpaperUrl();
            
            update_option($this->sEpaperOptionIndex, $this->aEpaperOptions);
            
        else:
            return json_decode($this->aEpaperOptions[$this->sEpaperOptionsChannelConfig]);
        endif;
        
        return $oChannels;
    }
    
    //refresh cached channel-object of user-account
    private function refreshChannelConfigObject(){
        $this->getChannelConfigObject(true);
        return true;
    }
    
    //escape input-strings
    public function escapeString($sStr){
        return htmlentities(stripslashes($sStr),ENT_QUOTES);
    }
    
    private function agbWasAccepted(){
        return (isset($this->aEpaperOptions[$this->sAgbAcceptIndex]))?true:false;
    }
    
}

//sidebar-widget
class EpaperWidgetClass extends WP_Widget {
    
    private $oEpaper = NULL;
    private $sEpaperOptionIndex = 'plugin_epaper_options';
        
    function EpaperWidgetClass() {
        parent::WP_Widget(false, $name = '1000°ePaper', array(
            'description' => __('display a 1000°ePaper','1000grad-epaper')
        ));
        
        $this->aEpaperOptions = get_option($this->sEpaperOptionIndex);        
        $this->oEpaper = new TG_Epaper_WP_Plugin(false);

    }
    
    //widget in frontend
    function widget($aArgs, $aSettings ) {
        if($this->oEpaper->is_registered() == true):
            $sTitle = apply_filters('widget_title', $aSettings['title'] );
            $iChannelId = $aSettings['channel_id'];
            $iPage = $aSettings['first_page'];
            $sLink = $this->oEpaper->getEpaperLink($iChannelId, NULL, array('page' => $iPage));
            
            $oParams = new stdClass();
            $oParams->title = $sTitle;
            $oParams->link = $sLink;
            $oParams->before_widget = $aArgs['before_widget'];
            $oParams->before_title = $aArgs['before_title'];
            $oParams->after_title = $aArgs['after_title'];
            $oParams->after_widget = $aArgs['after_widget'];
            
            $this->oEpaper->set('oView', $oParams);
            $this->oEpaper->set('bUseMainTemplate', false);
            $this->oEpaper->set('sTemplate', 'epaper_widget_frontend');
            $this->oEpaper->showContent();
        endif;
    }
    
    //save widget
    function update($aNewSettings, $aOldSettings) {
        $aSettings = $aOldSettings;
        $aSettings['title'] = $aNewSettings['title'];
        $aSettings['channel_id'] = $aNewSettings['channel_id'];
        $aSettings['first_page'] = $aNewSettings['first_page'.$aNewSettings['channel_id']];
        $oChannel = $this->oEpaper->getChannelInfos($aNewSettings['channel_id']);
        $aSettings['channel_url'] = $oChannel->url;
        return $aSettings;
    }
    
    //widget in backend
    function form($aSettings) {
        if($this->oEpaper->is_registered() == true):
            $oParams = new stdClass();
            $oParams->channels = $this->oEpaper->getChannels()->channels;
            $oParams->widget = $this;
            $oParams->widget_instance = $aSettings;
            
            if(isset($oParams->widget_instance['channel_id']) && !empty($oParams->widget_instance['channel_id'])):
                $bExists = $this->oEpaper->channelExists($oParams->widget_instance['channel_id']);
                if($bExists === false):
                    $oParams->widget_instance['channel_id'] = NULL;
                    $oParams->widget_instance['first_page'] = 1;
                endif;
            endif;
            
            $aDefaults = array( 'title' => '1000°ePaper' );
            $aSettings = wp_parse_args( (array) $aSettings, $aDefaults ); 

            $this->oEpaper->set('oView', $oParams);
            $this->oEpaper->set('bUseMainTemplate', false);
            $this->oEpaper->set('sTemplate', 'epaper_widget_backend');
            
            $this->oEpaper->showContent();
        endif;
    }
}

//initialize plugin
new TG_Epaper_WP_Plugin();