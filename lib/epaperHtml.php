<?php
/**
 * Class wraps functions for HTML output in the plugin
 * @copyright (c) 2013, 1000°DIGITAL Leipzig GmbH
 * @author Karsten Lemme <karsten.lemme@1000grad.de>
 */
class EpaperHtml 
{

    /**
     * used in renderEpaperForm
     * output for translation
     */
    private function _agbLink ()
    {
        return __('I have read the <a href="http://www.1000grad.de/upload/Dokumente/agb/terms_of_use_1000grad_ePaper_API_WP_Plugin.pdf">terms of use</a> and I agree.','1000grad-epaper');    
    }
    
    /**
     * used in renderEpaperForm
     * output for translation
     */
    private function _newsletterText ()
    {
       return __("I want to receive newsletters from 1000°DIGITAL GmbH.",'1000grad-epaper');
    }
    
    
    
    /**
     * used in epaperFeedbackForm
     * output for translation
     */
    private function _opinionFeedback ()
    {
        return __('Your opinion is important! We develop our software continuously and the focus of our efforts, you as a user of our software.'.
                  '<br />Please send us your comments, questions and suggestions. We will contact you immediately.','1000grad-epaper');    
    }
    
    /**
     * output for translation
     */
    public function logo1000Grad ()
    {
        echo "<img align=right alt=1000grad-logo hspace=20 src=" . plugin_dir_url("1000grad-epaper/1000grad_logo.png") 
                . "1000grad_logo.png>";
    }
    /**
     * output for translation
     */
    public function introText ()
    {
            _e("The new ePaper PlugIn aims to support Wordpress users in creating and adding ePaper publications to the WordPress blog.". 
            "Creating interactive FLASH and HTML5 based ePapers has never been easier. Upload your pdf file and create your interactive ".
            "multimedia publication in a few steps. Each publication is optimized for web and mobile (iOS and Android) display and is ".
            "equipped with an automatic device recognition. Test this new beta service and get one publication channel for free!",
            '1000grad-epaper');
    }

    
    /**
     * used in renderEpaperForm
     * output for translation
     */
    private function _epaperFormSubmitBtnText ()
    {
        return __("send request",'1000grad-epaper');
    }    

    /**
     * @todo action für das formular eintragen
     */
    public function renderEpaperForm ($epaperOptions)
    {
        echo " 
            <form action=\"\" method=\"post\">
                <label for=\"apikey_email\">Email: </label>
                <input type=\"text\" name=\"apikey_email\" id=\"apikey_email\" value=\"" . $epaperOptions['email'] . "\" size=\"35\" /><br />
                <input type=\"checkbox\" name=\"agb\"  id=\"agb\" value=\"yes\"> "
                . $this->_agbLink() . "<br />
                <input type=\"checkbox\" name=\"newsletter\" value=\"yes\" checked> "
                . $this->_newsletterText() . "<br />
                <input type=\"submit\" name=\"on\" id=\"on\" value=\"" . $this->_epaperFormSubmitBtnText()  . "\" class=\"button\" />
                <input type=\"hidden\" name=\"page\" value=\"epaper_apikey\" />
            </form>    
            ";
    }  
    
    /**
     * print contact data
     */
    public function printEpaperContact ()
    {
        echo "<p>";
        $this->logo1000Grad();
        echo  "</p>
                <b>1000°DIGITAL GmbH</b>
                <p>Lampestr. 2
                <br />D-04107 Leipzig
                <br />Support: +49 341 96382-63
                <br />Fax: +49 341 96382-22</p>
                <p><a href=mailto:info@epaper-apps.1000grad.com>info@epaper-apps.1000grad.com</a>
                <br /><a href=http://epaper-apps.1000grad.com/>http://epaper-apps.1000grad.com/</a>
                <br />http://www.1000grad.com/epaper/
                </p>   
                ";
    }
    
    /**
     * 
     */
    public function divClassWrap ()
    {
        echo ("<div class=\"wrap\">");
    }
    
    /**
     * 
     */
    public function h1 ($expr)
    {
        echo ("<h1>" . $expr . "</h1>");
    }
    
    public function h2 ($expr) 
    {
        echo ("<h2>" . $expr . "</h2>");
    }
    
    public function h3 ($expr)
    {
        echo ("<h3>" . $expr . "</h3>");
    }
    
    public function hr ()
    {
        echo "<hr>";
    }
    
    /**
     * 
     */
    public function divPostboxContainer ()
    {
         echo ("<div class=\"postbox-container\" style=\"width:70%;\">");
    }
    
    public function divPostboxContainer95pc ()
    {
         echo ("<div class=\"postbox-container\" style=\"width:95%;\">");
    }
    
    /**
     * 
     */
    public function divMetaboxHolder ()
    {
        echo ("<div class=\"metabox-holder\">");
    }
    
    /**
     * 
     */
    public function divUiSortable ()
    {
        echo ("<div class=\"ui-sortable meta-box-sortables\">");
    } 
    
    /**
     * 
     */
    public function divPostbox ()
    {
        echo ("<div class=\"postbox\">");
    }
    
    public function divClassInside ()
    {
        echo ("<div class=\"inside\">");
    }
    
    public function closeDiv () 
    {
        echo ("</div>");
    }
    
    public function close3Div () 
    {
        echo ("</div>
               </div>
               </div>            
            ");
    }    
    
    public function associatedDivs ($expr)
    {
        echo ("<div class=\"ui-sortable meta-box-sortables\">
               <div class=\"postbox\">
               <h3>" . $expr . "</h3>
               <div class=\"inside\">");

    }
    
    public function registerDoneBtn () 
    {
        echo ("
                <form action=\"?page=epaper_channels\" method=\"post\" />
                    <input type=\"submit\" name=\"weiter\" id=\"weiter\" value=\"start\" class=\"button\" />
                </form>
              ");
    }
        
    /**
     * @todo action kontrollieren
     * @param array $epaperOptions
     */    
    public function renderConfirmEmailRegisterForm ($epaperOptions) 
    {
        echo ("            
        <form action=\"\" method=\"get\">
            <input type=\"hidden\" name=\"email\" id=\"email\" value=\"" . $epaperOptions['email'] . "\" />
            <label for=\"code\">" .  __("1000° ePaper WP-Key",'1000grad-epaper') . ":</label>
            <input type=\"text\" name=\"code\" id=\"code\" value=\"" .  "\" size=\"25\" />
            <i>" . __("1000° ePaper WP-Key",'1000grad-epaper') . "</i><br />
            <input type=\"submit\" name=\"on\" id=\"on\" value=\"Eingeben\" class=\"button\" />
            <input type=\"hidden\" name=\"page\" value=\"epaper_apikey\" />      
            <br />
        </form>            
        ");
    }
    
    public function divEpaperform () 
    {
        echo ("<div class=\"epaperform\">");
    }
    
    public function divMetaboxPrefs () 
    {
        echo ("<div class=\"metabox-prefs\">");
    }
    
    public function br ()
    {
        echo "<br />";
    }
    
    public function brClearAll ()
    {
        echo "<br clear=all />";
    }
    
    public function divClassUpdateNag()
    {
        echo "<div class=\"update-nag\">";
    }


    /**
     * used in eaperWidgetControll in wpOptions
     * @param array $epaperOptions
     */
    public function widgetControlHTML ($widgetName) 
    {
        echo '<p>
                <label for="ePaper-WidgetTitle">Title: </label>
                <input type="text" id="ePaperWidgetTitle" name="ePaperWidgetTitle" value="' . $widgetName . '" />
                <input type="hidden" id="ePaperSubmit" name="ePaperSubmit" value="1" />
              </p>';        
    }
    
    /**
     *@todo action überprüfen
     * Feedback Form
     * used in epaperSetting epaper.php
     */
    public function epaperFeedbackForm () 
    {
        $this->logo1000Grad();
        echo "
            <form action=\"\" method=\"post\">
                <label for=\"text\">" . 
                $this->_opinionFeedback()    . "</label><br />
                <textarea name=\"text\" id=\"epaper_wordpressapi\" value=\"\" rows=\"5\" cols=\"75\"></textarea><br />
                <input type=\"submit\" name=\"feedback\" id=\"feedback\" value=\"". __("send feedback",'1000grad-epaper') . "\"  
                    class=\"button\" />
                <input type=\"hidden\" name=\"page\" value=\"feedback_send\" />
            </form>            
            ";
    }
    
    public function epaperApiSettingsForm ($epaperOptions) 
    {
        echo ("
            <form action=\"\" method=\"get\">
                <label for=\"epaper_wordpressapi\">ePaper Wordpress API: </label>
                <input type=\"text\" name=\"epaper_wordpressapi\" id=\"epaper_wordpressapi\" value=\"" 
                . $epaperOptions['wordpressapi'] . "\" size=\"55\" /><br />
                <label for=\"epaper_url\">ePaper API URL: </label>
                <input type=\"text\" name=\"epaper_url\" id=\"epaper_url\" value=\"" 
                . $epaperOptions['url'] . "\" size=\"55\" /><br />
                <label for=\"epaper_apikey\">ePaper API Key: </label>
                <input type=\"text\" name=\"epaper_apikey\" id=\"epaper_apikey\" value=\"" 
                . $epaperOptions['apikey'] . "\" size=\"55\" /><br />    
                <input type=\"submit\" name=\"epaper-settings-save\" id=\"epaper-settings-save\" value=\"Save\" class=\"button\" />
                <input type=\"hidden\" name=\"page\" value=\"epaper_settings\" />
            </form>
            ");        
    }
    
    /**
     * used in epaperChannelDetails()
     */
    public function epaperPublishedButtonForm () 
    {
        echo "<br clear=all />  
            <form action=\"?page=epaper_channels\" method=\"post\" />
                <input type=\"submit\" name=\"weiter\" id=\"weiter\" value=\"". __('next','1000grad-epaper') . 
                "\" class=\"button\" />
            </form>        
            "; 
    }
    
    /**
     * used in epaperChannelDetailsForm()
     * @param type $id
     * @param type $epaperId
     * @param type $name
     */
    public function channelDetailsForm($id, $epaperId , $name) 
    {
        // ['de','Deutsch'],['en','Englisch'],['da','Dänisch'],['nl','Niederländisch'],['fi','Finnisch'],['es','Spanisch'],['cs','Tschechisch'],['fr','Französisch'],['it','Italienisch'],['ru','Russisch'],['hr','Kroatisch'],['pl','Polnisch'],['pt','Portugiesisch'],['tr','Türkisch'],['bg','Bulgarisch'],['ja','Japanisch'],['el','Griechisch'],['hu','Ungarisch'],['sk','Slowakisch'],['sl','Slowenisch'],['ro','Rumänisch'],['zh_Hans','Vereinfachtes Chinesisch'],['zh_Hant','Traditionelles Chinesisch']];
        echo ("  
            <form action=\"\" method=\"post\" /><br />
                <label for=\"title\">" . __('Title:','1000grad-epaper') . "</label>
                <input type=\"text\" name=\"title\" id=\"title\" value=\"" . $name . "\" size=\"50\" /><br />
                <label for=\"lang\">" . __('Language:','1000grad-epaper') . "</label>
                        <select name=\"lang\" size=\"1\">
                        <option value=de>deutsch</option>
                        <option value=en>english</option>
                        <option value=es>espanol</option>
                        <option value=fr>francais</option>
                        <option value=it>italiano</option>
                    </select>
                <input type=\"hidden\" name=\"id\" value=\"" . $id . "\" />
                <input type=\"hidden\" name=\"id_epaper\" value=\"" . $epaperId . "\" />
                <input type=\"hidden\" name=\"modus\" value=\"channeldetails\" /><br />            
                <input type=\"submit\" name=\"modusss\" id=\"modus\" value=\"". __('save','1000grad-epaper') . 
                "\" class=\"button\" />
            </form>        
            "); 
    }
    
    /**
     * @todo action prüfen
     * used in epaperChannelUploadForm
     */
    public function pdfUploadForm ($id, $epaperId)
    {
        echo ("
            <form action=\"\" method=\"post\" enctype=\"multipart/form-data\">" . 
                __('Upload a new PDF','1000grad-epaper') . "<br /> 
                <input type=\"file\" name=\"uploadfile\" id=\"epaper-upload\"><br />
                <input type=\"hidden\" name=\"id\" value=\"" .  $id . "\" />
                <input type=\"hidden\" name=\"id_epaper\" value=\"" . $epaperId . "\" />
                <input type=\"hidden\" name=\"modus\" value=\"upload\" />
                <input type=\"submit\" name=\"epaper-upload\" value=\"" .  __("upload",'1000grad-epaper') . "\" />
             </form>            
            ");
    }
    
    /**
     * @todo action prüfen
     * used in epaperChannelUploadForm
     */
    public function pdfDeleteForm ($id, $epaperId)
    {
        echo ("
            <form action=\"\" method=\"post\">
                <input type=\"hidden\" name=\"id\" value=\"" .  $id . "\" />
                <input type=\"hidden\" name=\"id_epaper\" value=\"" . $epaperId . "\" />
                <input type=\"hidden\" name=\"modus\" value=\"empty\" />
                <input type=\"submit\" name=\"epaper-delete\" value=\"" .  __("clear channel",'1000grad-epaper') . "\" />
             </form>
             ");
    }
    
    /**
     * @todo action prüfen
     * used in epaperChannels() 
     */
    public function epaperChannelUpgradeForm()
    {
        echo ("
            <form action=\"\" method=\"post\" 
                <label for=\"epaper_code\">" . __("Unlock more channels via activation code:",'1000grad-epaper') . "</label>
                <input type=\"text\" name=\"epaper_code\" id=\"epaper_code\" value=\"\" size=\"30\" />
                <input type=\"submit\" name=\"epaper-more-channels\" id=\"epaper-more-channels\" value=\"Upgrade\" class=\"button\" />
                <input type=\"hidden\" name=\"modus\" value=\"channelupgrade\" />
             </form>
             ");        
    }

        
    
    /**
     * not used yet
     * @todo action prüfen
     * used in epaperEditList (epaper.php)
     */
    public function editEpaperListForm ($clientinfo)
    {
        echo ("
            <form action=\"\" method=\"post\">
                <label for=\"title\">" . __('Title:','1000grad-epaper') . "</label>
                <b><input type=\"text\" name=\"title\" id=\"title\" value=\"" . htmlspecialchars($clientinfo->title) . "\" 
                    size=\"55\" /></b>
                <input type=\"submit\" name=\"modus\" id=\"modus\" value=\"save\" class=\"button\" />
                <input type=\"hidden\" name=\"id\" value=\"" . $clientinfo->id . "\" />
            

            ");
    }   
}
