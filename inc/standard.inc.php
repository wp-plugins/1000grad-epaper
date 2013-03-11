<?php
echo "QQ-standard.inc.php-QQ";
exit;
function myAutoloader ($className) 
{
	if(file_exists('lib/'.$className.'.php')) {
		include_once('lib/'.$className.'.php');
	} else {
		trigger_error('class not found ' . $className);
	}    
}

spl_autoload_register('myAutoloader');

    function epaperApikey() 
    {
        global $testapikey;  
        global $apiKey;  
        $this->epaperApikeyConnect();
        global $wp_version;
        $epaper_options = get_option("plugin_epaper_options");

        if (!is_array( $epaper_options )) { 
            $epaper_options = array(
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

        if (isset($_POST['on'])) { 
            $email = $_POST['apikey_email'];
            $wordpress = $_POST['apikey_wordpress'];
            $text = $_POST['apikey_text'];
            $wordpresscode = $_POST['apikey_wordpresscode'];
            $phpupload = $_POST['apikey_phpupload'];
            $phptime = $_POST['apikey_phptime'];
            $newsletter = $_POST['newsletter'];
            $language = __("en",'1000grad-epaper');
            $agb = $_POST['agb'];
            $version_wordpress = $wp_version;
            $version_php = phpversion();
            _e("Acquiring data.",'1000grad-epaper');
            _e("<br />Please have a look into your email inbox for confirmation code.",'1000grad-epaper');             
    		$epaper_options['email'] = $email; 
    		$epaper_options['text'] = $text; 
    		update_option("plugin_epaper_options", $epaper_options);  
            try {
                $email=json_decode($testapikey->getRegistrationCodeByEmail(
                    $email,$text,$wordpress,$phpupload,$phptime,$wordpresscode,$agb,$newsletter,$version_wordpress,$version_php,$language));
            } catch (SoapFault $e) {
                echo '<br /><b>Error '.$e->getMessage().'</b>'; 
                if ($e->getMessage()=="(605) no valid email adress") _e("<br />Email adress is not valid.",'1000grad-epaper');
            }


        }
        global $wp_version;
    	$max_upload = (int)(ini_get('upload_max_filesize'));
    	$max_post = (int)(ini_get('post_max_size'));
        $memory_limit = (int)(ini_get('memory_limit'));
        $max_execution_time= (int)(ini_get('max_execution_time'));
        $max_input_time= (int)(ini_get('max_input_time'));
        $upload_mb = min($max_upload, $max_post, $memory_limit);
        $uptime_mb = min($max_execution_time,$max_input_time);
?>
  <div class="wrap"> 
<h1>1000°ePaper</h1>
  <div class="postbox-container" style="width:70%;">
  <div class="metabox-holder">
    
      <?php
        if (extension_loaded('curl')) echo "";
          else {
            _e("<br /><b>Error, there is NO CURL installed at your wordpress system!</b>",'1000grad-epaper');
            _e("<br />Please install it first!<br><code>apt-get install php5-curl</code>",'1000grad-epaper');
            exit();
          }
?>

  
<div class="ui-sortable meta-box-sortables">    <div class="postbox">    <h3>
    <?php _e("Request 1000°ePaper Key",'1000grad-epaper'); ?>
        </h3>    <div class="inside">
  
  
  
  <div class="epaperform">
    <div class="metabox-prefs">
      <form action="" method="post">
        <label for="apikey_email">Email: </label>
        <input type="text" name="apikey_email" id="apikey_email" value="<?php echo ($epaper_options['email']); ?>" size="35" />

                <br /><input type="checkbox" name="agb" value="yes"> 
          <?php _e("I have read the <a href=http://www.1000grad.de/upload/Dokumente/agb/terms_of_use_1000grad_ePaper_API_WP_Plugin.pdf>terms of use</a> and I agree.",'1000grad-epaper'); ?>
                <br /><input type="checkbox" name="newsletter" value="yes" checked>
    <?php _e("I want to receive newsletters from 1000°DIGITAL GmbH.",'1000grad-epaper'); ?>
        
        <br />
        <input type="hidden" name="apikey_wordpress" id="apikey_wordpress" value="<?php print admin_url(); ?>" />
        <input type="hidden" name="apikey_phpupload" id="apikey_phpupload" value="<?php print $upload_mb; ?>"  />
        <input type="hidden" name="apikey_phptime" id="apikey_phptime" value="<?php print $uptime_mb; ?>"   />
        <input type="submit" name="on" id="on" value="<?php _e("send request",'1000grad-epaper'); ?>" class="button" />
        <input type="hidden" name="page" value="epaper_apikey" />      
</form>    
     
    </div>
    </div>

    </div></div></div>
<div class="ui-sortable meta-box-sortables">    <div class="postbox">    <h3>
              <?php _e("Registration Code",'1000grad-epaper'); ?>
            </h3>    <div class="inside">

    <?php
    
if (isset($_GET['email'])) { 
  $email=$_GET['email'];
  $code=$_GET['code'];
  
  _e("<p>Confirmation code was entered:",'1000grad-epaper');
    echo " ";
    echo $code;
	try {
	$result=json_decode($testapikey->SendCodeGetApikey($email,$code));
    } catch (SoapFault $e) {echo '<br /><b>API-Fehler. '.$e->getMessage().'</b>'; return false;}

  _e("<p>Your new settings are: <br /><b><pre>",'1000grad-epaper');
  print_r($result);
  echo "</pre></b>";
//	  $epaper_options['code'] = $code;
	  $epaper_options['url']=$result->apiurl;
	  $epaper_options['apikey']=$result->apikey;		
		update_option("plugin_epaper_options", $epaper_options);  
  _e("<p>Now you can use this ePaper Plugin!.",'1000grad-epaper');
?>
	<form action="?page=epaper_channels" method="post" />
        <input type="submit" name="weiter" id="weiter" value="start" class="button" />
<?php

}
else {
    # Eingabe des Codes
    if (($epaper_options['email']>"")) {

?>
  <div class="epaperform">
    <div class="metabox-prefs">
      <form action="" method="get">
        <input type="hidden" name="email" id="email" value="<?php echo ($epaper_options['email']); ?>"  />
        <label for="code">
        <?php     _e("1000° ePaper WP-Key",'1000grad-epaper'); ?>
            :</label>
        <input type="text" name="code" id="code" value="<?php #echo ($epaper_options['code']); ?>" size="25" />
		<i>
        <?php                                 _e("1000° ePaper WP-Key",'1000grad-epaper'); ?>
                </i>
        <br />
        <input type="submit" name="on" id="on" value="Eingeben" class="button" />
        <input type="hidden" name="page" value="epaper_apikey" />      
        <br />
      </form>


    </div>
    </div>
	<br />
<?php
    } else _e('Info: Your Information will be transmitted to the 1000Grad Company. You will receive an email for confirmation.','1000grad-epaper');
        
  echo "</div>";
  echo "</div>";
  echo "</div>";
?>
<div class="ui-sortable meta-box-sortables">    <div class="postbox">    <h3>Kontakt</h3>    <div class="inside">
<?php  
  $this->epaperContact(); 
  echo "</div>";
  echo "</div>";
  echo "</div>";

    }
  echo "</div>";
  echo "</div>";
  echo "</div>";
}