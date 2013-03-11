<?php
require_once("epaperHtml.php");

class EpaperWpOptions 
{
    private $epaperOptions = array();
    private $apikey;
    private $isRegistered;
    
    public function __construct() 
    {
        $this->epaperOptions = get_option("plugin_epaper_options");
        $this->apikey = $this->epaperOptions['apikey'];
        $this->_isRegistered();
        //hm, scheint nicht so ganz zu funktionieren:
        //$this->epaperTextDomain();
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
     * Uebersetzungsmodul, native language of this plugin is english. Further translations as needed.
     */
    public function epaperTextDomain() 
    {
        load_plugin_textdomain('1000grad-epaper', false, '1000grad-epaper/lang');
    }
   
    /**
     * enable epaper preview for posts & pages if there is a apikey
     * @return boolean
     */
    public function addEpaperMetaBox ()    
    {
      $EPaperClass = new Epaper();

        if ($this->isRegistered === true)  {  
            add_meta_box('epaper_editorbox', '1000°ePaper', array($EPaperClass, 'epaperMetaBox'), 'post', 'side', 'high');
            add_meta_box('epaper_editorbox', '1000°ePaper', array($EPaperClass, 'epaperMetaBox'), 'page', 'side', 'high');
            return true;
        }
        
        return false;
    } 
    
    /**
     * Initialisierung von Scripten und Styles
     * @param type $posts
     * @return type
     */
    public function conditionally_add_scripts_and_styles ($posts) 
    {
        if (!empty($posts)) {        
            wp_enqueue_script( 'jquery' );      
            wp_enqueue_script('js_colorbox_min', plugins_url('1000grad-epaper/colorbox/jquery.colorbox-min.js'));
//          wp_enqueue_script('js_colorbox', plugins_url('1000grad-epaper/colorbox/jquery.colorbox.js'));
            wp_enqueue_script('colorbox-epaper', plugins_url('1000grad-epaper/colorbox-epaper.js'));
            wp_enqueue_style('style_colorbox', plugins_url('1000grad-epaper/colorbox/colorbox.css'));
            return $posts;
        }
        return $posts;
    }
  
 
     /**
     * Widget 1 Funktion
     */  
    public function epaperWidget ($args) 
    {
        extract($args);
        $url = $this->epaperOptions['channelurl1'];
        if (isset($url) && ($url != "")) {
            $name = $this->epaperOptions['widgetname1'];
            if ($name == "") $name = "ePaper"; 
            echo $before_widget;
            echo $before_title . $name;
            echo $after_title;
            $html="<a class='iframe' href='";
            $html.= $url."'>";
            $html.= '<img src="';
            $html.= $url.'epaper/epaper-ani.gif" alt="epaper preview gif" border="0" width=100% /></a>';
            echo $html;
            echo $after_widget;
        }
    }
    
     /**
     * Widget 2 Funktion
     */
    public function epaperWidget2 ($args) 
    {
        extract($args);
        $url = $this->epaperOptions['channelurl2'];
        if (isset($url) && ($url != "")) {
            $name = $this->epaperOptions['widgetname2'];
            if ($name == "") $name = "ePaper";
            echo $before_widget;
            echo $before_title . $name;
            echo $after_title;
            $html = "<a class='iframe cboxElement' href='";
            $html .= $url . "'";
            $html .= '> <img src="';
            $html .= $url . 'epaper/epaper-ani.gif" alt="epaper preview gif" border="0" width=100% /> </a>';
 //         $html .='<script>    jQuery(".iframe").colorbox({iframe:true, width:"90%", height:"95%"}); </script>';
            echo $html;
            echo $after_widget;
        }
    }
    
     /**
     * Widget 1
     */
    public function epaperWidgetControl () 
    {
        if (isset($_POST['ePaperSubmit'])) {
            $this->epaperOptions['widgetname1'] = htmlspecialchars($_POST['ePaperWidgetTitle']);
            update_option("plugin_epaper_options", $this->epaperOptions);
        }
        $html = new EpaperHtml();
        $html->widgetControlHTML($this->epaperOptions['widgetname1']); 
    }

     /**
     * Widget 2
     */
    public function epaperWidgetControl2 () 
    {
        if (isset($_POST['ePaperSubmit'])) {
            $this->epaperOptions['widgetname2'] = htmlspecialchars($_POST['ePaperWidgetTitle']);
            update_option("plugin_epaper_options", $this->epaperOptions);
        }
        $html = new EpaperHtml();
        $html->widgetControlHTML($this->epaperOptions['widgetname2']);
    }   
    

     /**
     * Initialisierung der Colorbox und Menupunkte
     */   
    public function epaperIntegrationMenu() 
    {        
                $EPaperClass = new Epaper();
                //@TODO Methode ePaper Channels muss in dieser Klasse noch zur Verfügung gestellt werden

        add_menu_page('ePaper', '1000°ePaper', 'upload_files', 'epaper_channels', 
                array(&$EPaperClass,'epaperChannels'),plugin_dir_url("1000grad-epaper/1000grad_icon.png")  
                . "1000grad_icon.png"
                );
//        add_menu_page('ePaper', '1000°ePaper', 10, 'epaper_channels',                        array(&$this,'epaperChannels'),plugin_dir_url("1000grad-epaper/1000grad_icon.png")                         . "1000grad_icon.png"                      );

        if ($this->isRegistered === false) add_submenu_page('epaper_channels', 
                'ePaper '.__('Registration','1000grad-epaper'), __('Registration','1000grad-epaper'), 'upload_files', 
                'epaper_apikey', array(&$EPaperClass,'epaperApikey'));
        
            add_options_page( '1000°ePaper', '1000°ePaper', 'upload_files','epaper_settings', array(&$EPaperClass,'epaperSettings'));
            
                //@TODO Colorbox integration

            wp_enqueue_script( 'jquery' );      
            wp_enqueue_script('js_colorbox_min', plugins_url('1000grad-epaper/colorbox/jquery.colorbox-min.js'));
            wp_enqueue_script('colorbox-epaper', plugins_url('1000grad-epaper/colorbox-epaper.js'));
            wp_enqueue_style('style_colorbox', plugins_url('1000grad-epaper/colorbox/colorbox.css'));

    }
    
     /**
     * Shortcode fuer Beitraege 
     */ 
    public function epaperShortcode($atts) 
    {
        extract(shortcode_atts(array('k' => "",
                                     'url' => "",
                                     'id' => "",
                                     'class' => "",
                                     'nr' => "",), 
                               $atts));
    
        if ($nr == "") $nr = 1;                
        if ($url == "") $url = $this->epaperOptions['channelurl' . $nr];    
        $html = "<a class='iframe' href='";
        $html .= $url."'";
        $html .= '> <img class="';
        if ($class == "")          
            $html .= "alignright";
        else 
            $html .= $class;
        $html .= '" src="';
        $html .= $url . '/epaper/epaper-ani.gif" alt="epaper preview gif" border="0" /> </a>';    
        return $html;
    }
   
    /**
     * shall return options settings
     * @return array
     */
    public function getEpaperOptions ()
    {
        return $this->epaperOptions;
    }
    
    /**
     * shall return the apikey
     * @return stirng
     */
    public function getApikey () 
    {
        return $this->apikey;
    }    
    
    /**
     * shall return if version is registered
     * @return boolean
     */
    public function getIsRegistered () 
    {
        return $this->isRegistered;
    }
    
}
