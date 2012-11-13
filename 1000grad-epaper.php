<?php
/*
Plugin Name: 1000°ePaper
Plugin URI: http://www.1000grad-epaper.de/loesungen/wp-plugin
Description: Create browsable ePapers easily from within Wordpress! Konvertieren Sie Ihre PDF in ein blätterbares Web-Dokument und binden Sie es mit einem Widget ein! Auch auf Android, iPad & Co. macht Ihr ePaper in der automatischen HTML5-Darstellung einen sehr guten Eindruck.
Version: 1.0.5
Author: 1000°DIGITAL Leipzig GmbH
Author URI: http://www.1000grad-epaper.de/
*/

//echo "<pre>";
ini_set("soap.wsdl_cache_enabled", 1);
ini_set("soap.wsdl_cache_ttl", 86400);
//error_reporting(E_ALL);
ini_set('display_errors','On'); 
//echo "</pre>";
 
 // License 
 // Our plugin is compatible with the GNU General Public License v2, or any later version.

$myEpaperClass = new epaper();
add_action('init', array($myEpaperClass,'epaperTextDomain'), 1);
add_action('admin_menu', array($myEpaperClass,'epaperIntegrationMenu'));
add_shortcode('ePaper', array($myEpaperClass,'epaperShortcode'));
wp_register_sidebar_widget('1000grad-ePaper','1000°ePaper',  array($myEpaperClass,'epaperWidget'),array(    'description' => 'Shows the first ePaper Channel' ));
wp_register_widget_control('1000grad-ePaper','1000grad-ePaper', array($myEpaperClass,'epaperWidgetControl') );
//wp_register_sidebar_widget('1000grad-ePaper-2','1000°ePaper #2',  array($myEpaperClass,'epaperWidget2'),array(    'description' => 'Shows the second ePaper Channel' ));
//wp_register_widget_control('1000grad-ePaper-2','1000grad-ePaper #2', array($myEpaperClass,'epaperWidgetControl2'));
add_action( 'add_meta_boxes', array( $myEpaperClass, 'addEpaperMetaBox' ) );
add_filter('the_posts', array($myEpaperClass,'conditionally_add_scripts_and_styles')); 

// doesnt work for the moment :-(
//add_action('wp_head', array($myEpaperClass,'colorboxinlineheader')); 
add_action('wp_footer', array($myEpaperClass,'colorboxinline')); 

class epaper {

  function colorboxinlineheader() {
      echo '<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.8.2/jquery.min.js"></script>';
  }
    
  function colorboxinline() {
    echo '<script>jQuery(".iframe").colorbox({iframe:true, width:"80%", height:"95%"}); </script>';
    $code= '<script type="text/javascript">
      $(document).ready(function(){
      $(".iframe").colorbox({iframe:true, width:"80%", height:"95%"});
      $(".ajax").colorbox();
      $(".callbacks").colorbox({
        onOpen:function(){ alert("onOpen: colorbox is about to open"); },
        onLoad:function(){ alert("onLoad: colorbox has started to load the targeted content"); },
        onComplete:function(){ alert("onComplete: colorbox has displayed the loaded content); },
        onCleanup:function(){ alert("onCleanup: colorbox has begun the close process"); },
        onClosed:function(){ alert("onClosed: colorbox has completely closed"); }
    });

$("#click").click(function(){
  $("#click").css({"background-color":"#f00", "color":"#fff", "cursor":"inherit"}).text("Open this window again and this message will still be here.");
  return false;
});      
  });
  </script>
  ';
// echo $code;
  }

  function addEpaperMetaBox() {
       $epaper_options = get_option("plugin_epaper_options");
       $apikey=$epaper_options['apikey'];
       if($apikey>"")  {  
    add_meta_box(  'epaper_editorbox', '1000°ePaper', array(&$this, 'epaperMetaBox'), 'post', 'side', 'high'    );
    add_meta_box(  'epaper_editorbox', '1000°ePaper', array(&$this, 'epaperMetaBox'), 'page', 'side', 'high'    );
       }
  }



  function conditionally_add_scripts_and_styles($posts) {
    if (empty($posts))
      return $posts;

    $shortcode_found = false; // use this flag to see if styles and scripts need to be enqueued

    foreach ($posts as $post) {
      if (stripos($post->post_content, 'ePaper')) {
        $shortcode_found = true; // bingo!
        break;
      }
    }

 //   if ($shortcode_found)
        {
  //    wp_enqueue_script('js_colorbox_min', plugins_url('1000grad-epaper/colorbox/jquery.colorbox-min.js'));
      wp_enqueue_script('js_colorbox', plugins_url('1000grad-epaper/colorbox/jquery.colorbox.js'));
      wp_enqueue_style('style_colorbox', plugins_url('1000grad-epaper/colorbox/colorbox.css'));         
#      wp_enqueue_script('yo', 'yo');
#      wp_head('yo',  'yo');
         }

    return $posts;
  }

function epaperWidget($args) {
    extract($args);
       $epaper_options = get_option("plugin_epaper_options");
       $url=$epaper_options['channelurl1'];
       if($url>"")  {
       $name=$epaper_options['widgetname1'];
       if ($name=="") $name="ePaper";
    echo $before_widget;
    echo $before_title .$name;
    echo $after_title;
    $html="<a class='iframe' href='";
    $html.=$url."'>";
    $html.='<img src="';
    $html.=$url.'epaper/epaper-ani.gif" alt="epaper preview gif" border="0" width=100% /></a>';
   // $html.='<script>    jQuery(".iframe").colorbox({iframe:true, width:"80%", height:"95%"}); </script>';
//    $html.="</p>";
    echo $html;
    echo $after_widget;
           }
}


function epaperWidget2($args) {
    extract($args);
       $epaper_options = get_option("plugin_epaper_options");
       $url=$epaper_options['channelurl2'];
        if($url>"")  {
       $name=$epaper_options['widgetname2'];
       if ($name=="") $name="ePaper";
    echo $before_widget;
    echo $before_title              .$name;
    echo $after_title;

    $html="<a class='iframe cboxElement' href='";
    $html.=$url."'";
    $html.='> <img src="';
    $html.=$url.'epaper/epaper-ani.gif" alt="epaper preview gif" border="0" width=100% /> </a>';
 //   $html.='<script>    jQuery(".iframe").colorbox({iframe:true, width:"90%", height:"95%"}); </script>';
  echo $html;
       echo $after_widget;
        }
}


function epaperWidgetControl() {
  $options = get_option("plugin_epaper_options");
   if ($_POST['ePaperSubmit'])
  {
    $options['widgetname1'] = htmlspecialchars($_POST['ePaperWidgetTitle']);
    update_option("plugin_epaper_options", $options);
  }
?>
  <p>
    <label for="ePaper-WidgetTitle">Title: </label>
    <input type="text" id="ePaperWidgetTitle" name="ePaperWidgetTitle" value="<?php echo $options['widgetname1'];?>" />
    <input type="hidden" id="ePaperSubmit" name="ePaperSubmit" value="1" />
  </p>
<?php
}

function epaperWidgetControl2() {
  $options = get_option("plugin_epaper_options");
   if ($_POST['ePaperSubmit'])
  {
    $options['widgetname2'] = htmlspecialchars($_POST['ePaperWidgetTitle']);
    update_option("plugin_epaper_options", $options);
  }

?>
  <p>
    <label for="ePaper-WidgetTitle">Title: </label>
    <input type="text" id="ePaperWidgetTitle" name="ePaperWidgetTitle" value="<?php echo $options['widgetname2'];?>" />
    <input type="hidden" id="ePaperSubmit" name="ePaperSubmit" value="1" />
  </p>
<?php
}

function epaperTextDomain() {
	load_plugin_textdomain('1000grad-epaper', false, '1000grad-epaper/lang');
}

function epaperIntegrationMenu() {
    $epaper_options = get_option("plugin_epaper_options");
    add_menu_page('ePaper', '1000°ePaper', 10, 'epaper_channels', 'epaper::epaperChannels',plugin_dir_url("1000grad-epaper/1000grad_icon.png")."1000grad_icon.png");
//    add_menu_page('ePaper', '1000°ePaper', 10, 'epaper_channels', 'epaper::epaperChannels',plugins_url('1000grad_icon.png', __FILE__));
//    add_submenu_page('epaper_channels', 'ePaper '.__('Channels','1000grad-epaper'), __('Channels','1000grad-epaper'), 10, 'epaper_channels', 'epaper::epaperChannels');
//    add_submenu_page('epaper_channels', 'ePaper '.__('List','1000grad-epaper'), __('List','1000grad-epaper'), 10, 'epaper_list', 'epaper::epaperList');
////  add_submenu_page('epaper_channels', 'ePaper'.__('Channels','1000grad-epaper'), __('Channels old','1000grad-epaper'), 10, 'epaper_channels', 'epaper::epaperchannels');
//   if (!($epaper_options['apikey'])=="") add_submenu_page('epaper_channels', 'ePaper '.__('Edit','1000grad-epaper'), __('List/Edit','1000grad-epaper'), 10, 'epaper_edit', 'epaper::epaperEdit');
////   if (!($epaper_options['apikey'])=="") add_submenu_page('epaper_channels', 'ePaper '.__('Upload','1000grad-epaper'), __('Upload','1000grad-epaper'), 10, 'epaper_upload', 'epaper::epaperUpload');
   if (($epaper_options['apikey'])=="") add_submenu_page('epaper_channels', 'ePaper '.__('Registration','1000grad-epaper'), __('Registration','1000grad-epaper'), 10, 'epaper_apikey', 'epaper::epaperApikey');
//                                        add_submenu_page('epaper_channels', 'ePaper Settings', __('Settings','1000grad-epaper'), 10, 'epaper_settings', 'epaper::epaperSettings');
 //  	add_management_page( 'ePaper Settings', 'ePaper Settings', 'epaper_settings','epaper_settings', 'epaperSettings');
   	add_options_page( '1000°ePaper', '1000°ePaper', 10,'epaper_settings', 'epaper::epaperSettings');
}

  function epaperShortcode($atts) {
    extract(shortcode_atts(array(
        'k' => "",
        'url' => "",
        'id' => "",
        'nr' => "",
        ), $atts));
    if ($nr=="") $nr=1;
       $epaper_options = get_option("plugin_epaper_options");
    if ($url=="") 
      $url=$epaper_options['channelurl'.$nr];
    
    $html="<a class='iframe' href='";
    $html.=$url."'";
    $html.='> <img class="alignright" src="';
    $html.=$url.'/epaper/epaper-ani.gif" alt="epaper preview gif" border="0" /> </a>';
    //$html.='<script>    jQuery(".iframe").colorbox({iframe:true, width:"80%", height:"95%"}); </script>';
    
       return $html;
  }

function epaperEditPost($id,$test,$apiKey) {
	e_('<h3>Poste ePaper</h3>','1000grad-epaper');
		try {
		$editinfo = $test->epaperGetInfos($apiKey,$id);
		} catch (SoapFault $e) {echo 'Fehler beim Zugriff auf ePaper'.$e->getMessage(); return false;}
	echo '<p><h2>'.$editinfo->filename.'</h2>';
    $info=json_decode($editinfo);

      echo 'Element ID '.$info->id;
  $my_post = array(
     'post_title' => 'ePaper '.$info->title,
     'post_content' => __('This is my new ePaper','1000grad-epaper').' <b>'.$info->title.'</b>: <a title="ePaper" href="/wordpress/wp-content/uploads/ePaper/'.$id.'/" target="_blank"><img class="alignright" src="/wordpress/wp-content/uploads/ePaper/'.$id.'/epaper/preview.jpg" alt="ePaper preview" /></a>',
     'post_status' => 'publish',
  );

  $postid=wp_insert_post( $my_post );
      echo __('<br />was posted (without Category)','1000grad-epaper').'<br /><b><a href=post.php?post='.$postid.'&action=edit>'.__('Edit this Post','1000grad-epaper')  .'</a></b>';

	}


function epaperEditList($test,$apiKey)  {
        echo "<h1>1000°ePaper</h1>";
	_e('<h2>List of uploaded ePapers</h2>','1000grad-epaper');
	$clientlist=json_decode($test->ePaperGetList($apiKey));
        if (count($clientlist)=="0") {
            _e('No ePaper existent','1000grad-epaper');
            _e('<br />Please <a href=?page=epaper_upload>upload a PDF File.</a>','1000grad-epaper');
        }
        
    echo "<pre>";
    print_r($clientlist);
    echo "</pre>";
        
	foreach ( $clientlist as $clientpaper ) {
            		try {
		$clientabfrage = $test->ePaperGetInfos($apiKey,$clientpaper->id);
		} catch (SoapFault $e) {echo 'Fehler beim Zugriff auf ePaper'.$e->getMessage(); return false;}
                
    echo "<pre>";
    print_r($clientabfrage);
    echo "</pre>";

	$clientinfo=json_decode($clientabfrage);
?>
        <hr><form action="" method="post">
        <label for="title"><?php _e('Title:','1000grad-epaper') ?></label><b><input type="text" name="title" id="title" value="<?php echo htmlspecialchars($clientinfo->title); ?>" size="55" /></b>
        <input type="submit" name="modus" id="modus" value="save" class="button" />
        <input type="hidden" name="id" value="<?php echo $clientinfo->id; ?>" />
   
<?php
    echo "<pre>";
    print_r($clientinfo);
    echo "</pre>";

      $uploaddir=wp_upload_dir();
      echo '<img align=right src='.$clientinfo->web_folder.'source/thumbs/page_1.jpg?rnd='.rand(1000, 9999).'>';
       if ($clientinfo->settings->logo=='1') echo '<img src='.$clientinfo->web_folder.'source/logo.png?rnd='.rand(1000, 9999).'>';

    if ($clientinfo->title=='')	_e('<font color=red>no title set</font>','1000grad-epaper');
	echo '<br /><font color=grey>File: '.$clientpaper->filename.'';
	echo ' (ID: '.$clientpaper->id.')</font>';
	echo __('<br />Number of Pages:','1000grad-epaper').' '.$clientinfo->pages;
    if ($clientinfo->status<>'ready')	echo '<br /><font color=red size=+2>Status: '.$clientinfo->status.'</font>';
     else echo '<br />Status: ready';
	echo '<br /><b><a href=?page=epaper_edit&modus=check&id='.$clientpaper->id.'>'.__('edit','1000grad-epaper').'</a> / ';
    // #deaktivierte Download-Funktion
    //	echo '    <a href=?page=epaper_edit&modus=render&id='.$clientpaper->id.'>render</a> / ';
//    	echo '    <a href=?page=epaper_edit&modus=publish&id='.$clientpaper->id.'>publizieren</a> / ';
//    	echo '    <a href=?page=epaper_edit&modus=download&id='.$clientpaper->id.'>'.__('download','1000grad-epaper').'</a> / ';
	echo '    <a href=?page=epaper_channels&modus=channelizing&id='.$clientpaper->id.'&channelid=1>'.__('canalize','1000grad-epaper').'</a> / ';
	echo '    <a href=?page=epaper_edit&modus=delete&id='.$clientpaper->id.'>'.__('delete','1000grad-epaper').'</a> / ';

    echo '</b>';

?>
        </form>
  <br clear=all>
<?php

}
echo "<hr>";
	}


function epaperEditCheck($id,$test,$apiKey) {
    		try {
		$file = $test->epaperGetInfos($apiKey,$id);
		} catch (SoapFault $e) {echo 'Error beim Zugriff auf ePaper '.$id.'.<br />Fehler: '.$e->getMessage(); return false;}

	$editinfo=json_decode($file);
//	echo '<p><h2>'.$editinfo->filename.'</h2>';
//    if ($editinfo->zip_url>'')	echo '<br />Export: <a href='.$editinfo->zip_url.'>ZIP download</a>';
//    else echo '<br />Export nicht m&ouml;glich, Datei noch nicht published';
//	echo ' ('.$editinfo->pages.' Seiten)';
//    echo '<br /><a href=?page=epaper_publish&id='.$editinfo->id.'>publizieren</a> <b>(1 License wird verbraucht!)</b>';

echo '<div class="epaper-settings-panel">';
_e('<h1>ePaper Settings</h1>','1000grad-epaper');
?>
  <div class="epaperform">
    <div class="metabox-prefs">
     <form action="?page=epaper_edit&id=<?php echo $id; ?>" method="post" enctype="multipart/form-data">
        <br /><label for="title"><?php _e('Title:','1000grad-epaper'); ?></label><input type="text" name="title" id="title" value="<?php echo htmlspecialchars($editinfo->title); ?>" size="20" />
		<i>e.g. "Ausgabe 1" </i>
        <br /><label for="lang"><?php _e('Language:','1000grad-epaper'); ?></label><input type="text" name="lang" id="lang" value="<?php echo ($editinfo->settings->language); ?>" size="10" />
		<i>e.g. "de", "en" </i>
	<br /><label for="logo">Logo: </label><input type="file" name="logo" id="logo">
<?php
    if ($editinfo->settings->logo=='1')	echo __('(existing)','1000grad-epaper').'<img src='.$editinfo->web_folder.'/source/logo.png?rnd='.rand(5, 15).'>';
    else echo '<font color=red>'.__('(choose file)','1000grad-epaper').'</font> max. 250x60 Pixel!';

?>
        <br />
        <input type="submit" name="modus" id="modus" value="save" class="button" />
      </form>
    </div>
  </div>
</div>
<?php
        }

function epaperEdit() {
  global $test;  global $apiKey;  epaper::epaperConnect();
	$id=$_REQUEST['id']; // sollten noch mehr Sicherheitsabfragen rein (nur Zahlen und Buchstaben!)
#	if ($id=='') $id=$_POST['id'];
	$modus=$_REQUEST['modus']; // sollten noch mehr Sicherheitsabfragen rein (nur Zahlen und Buchstaben!)

  if ($modus=='save') {
  epaper::epaperEditSave($id,$test,$apiKey);
  epaper::epaperEditList($test,$apiKey);
  return false;
  }
  
    if ($modus=='download') {
  epaper::epaperEditDownload($id,$test,$apiKey);
  return false;
  }


  if ($modus=='delete') {
      epaper::epaperEditDelete($id,$test,$apiKey);
      epaper::epaperEditList($test,$apiKey);
      return false;
  }
  if ($modus=='publish') {
      epaper::epaperEditPublish($id,$test,$apiKey);
      epaper::epaperEditList($test,$apiKey);
      return false;
  }
  if ($modus=='render') {
      epaper::epaperEditRender($id,$test,$apiKey);
      epaper::epaperEditList($test,$apiKey);
      return false;
  }

  if ($id=='') {epaper::epaperEditList($test,$apiKey); return false;}

if ($modus=='check') epaper::epaperEditCheck($id,$test,$apiKey);

	}

function epaperList() {
  global $test;  global $apiKey;  epaper::epaperConnect();
	$id=$_REQUEST['id']; // sollten noch mehr Sicherheitsabfragen rein (nur Zahlen und Buchstaben!)
#	if ($id=='') $id=$_POST['id'];
	$modus=$_REQUEST['modus']; // sollten noch mehr Sicherheitsabfragen rein (nur Zahlen und Buchstaben!)
  if ($modus=='save') {
  epaper::epaperEditSave($id,$test,$apiKey);
  epaper::epaperEditList($test,$apiKey);
  return false;
  }

  if ($id=='') {epaper::epaperEditList($test,$apiKey); return false;}

	}
        

function epaperApikeyConnect() {
  global $testapikey;
  global $apiKey;
	$epaper_options = get_option("plugin_epaper_options");
	$Wsdl = $epaper_options['wordpressapi'];
	$apiKey=$epaper_options['apikey'];
        if ($epaper_options['wordpressapi']=="") $Wsdl="http://epaperplugin.1kcloud.com/api/v2/wordpress-wsdl/"; 

//            if ($epaper_options['apikey']=="") {
//            echo '<div class="update-nag">';
//            echo "1000°ePaper ApikeyConnect<br />"; _e('This Installation is not registered yet.<br />Please <a href=admin.php?page=epaper_channels>register your installation</a>','1000grad-epaper'); 
//            echo '</div>';
//            return false;}
	try {
		$testapikey = new SoapClient($Wsdl , array());
		} catch (SoapFault $e) {echo 'Fehler beim Connect'.$e->getMessage(); return false;}
    return true;
    }


function epaperConnect() {
  global $test;
  global $apiKey;
	$epaper_options = get_option("plugin_epaper_options");
        if ($epaper_options['apikey']=="") {
            echo '<div class="update-nag">';
            echo "1000°ePaper<br />"; _e('This Installation is not registered yet.<br />Please <a href=admin.php?page=epaper_channels>register your installation</a>','1000grad-epaper'); 
            echo '</div>';
            return false;}
        $Wsdl = $epaper_options['url']."epaper-wsdl/";
	$apiKey=$epaper_options['apikey'];		
	try {
		$test = new SoapClient($Wsdl , array());
		} catch (SoapFault $e) {echo 'Fehler beim Connect'.$e->getMessage(); return false;}	
    return true;
    }

    
function epaperChannelConnect() {
  global $channel;
  global $apiKey;
	$epaper_options = get_option("plugin_epaper_options");
        if ($epaper_options['apikey']==""){
            echo '<div class="ui-sortable meta-box-sortables"><div class="update-nag"><div class="inside">';
            echo "<br />"; _e('This Installation is not registered yet.<br />Please <a href=admin.php?page=epaper_apikey>register your installation</a>','1000grad-epaper'); 
            echo '</div></div></div>';
            return false;}
        if ($epaper_options['url']==""){
            echo '<div class="ui-sortable meta-box-sortables"><div class="update-nag"><div class="inside">';
            echo "<br />"; _e('This Installation is not correctly registered.<br />Please <a href=options-general.php?page=epaper_settings>check your installation</a>','1000grad-epaper'); 
            echo '</div></div></div>';    
            return false;
        }            
        $Wsdl = $epaper_options['url']."channels-wsdl/";
	$apiKey=$epaper_options['apikey'];
        try {
		$channel = new SoapClient($Wsdl , array());
		} catch (SoapFault $e) { echo 'Fehler beim Connect'.$e->getMessage(); return false;}
    return true;
    }

    
function epaperSettingsTest($testchannel,$apiKey) {
	echo 'API Version: '. $testchannel->getVersion();
	echo '<p>API Key: '. $apiKey;
      $clientinfo=($testchannel->__getFunctions());
	echo __('<br />Number of API Commands:','1000grad-epaper').' '.count($clientinfo);
}

function epaperTestWordpress() {
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
        
        //echo ' '.$uptime_mb.' sec. (execution: '.$max_execution_time.'s, Input:'.$max_input_time.'s)';
        echo "<br />";
  _e("local plugin settings:",'1000grad-epaper');
        echo "<br />";
        echo "<pre>";
        $epaper_options = get_option("plugin_epaper_options");
        print_r($epaper_options);
        echo "</pre>";
}

function epaperTestApi() {
  global $test;  global $apiKey;  epaper::epaperConnect();
  if ($apiKey>'') {
		try {
      $version=$test->getVersion();
    } catch (SoapFault $e) {_e('<br />Error with API Handling, please register your plugin!','1000grad-epaper').$e->getMessage(); return false;}
	echo 'API Version: '. $version;
  
  	try {
      $clientinfo=($test->__getFunctions());
    } catch (SoapFault $e) {_e('<br />Error with API Handling, please register your plugin!','1000grad-epaper').$e->getMessage(); return false;}
	
      
	echo __('<br />Number of API Commands:','1000grad-epaper').' '.count($clientinfo);

	try {
      $clientinfos=$test->clientGetInfos($apiKey);
    } catch (SoapFault $e) {_e('<br /><b>Error with API Key Authentification','1000grad-epaper').$e->getMessage().'</b>'; return false;}
    echo "<br />Api Key ist valide";
    return true;
} 
}

function epaperTestChannelApi() {
  global $channel;  global $apiKey;  epaper::epaperChannelConnect();
if ($apiKey>'') {
	echo 'API Version: '. $channel->getVersion();

          $clientinfo=($channel->__getFunctions());
	echo __('<br />Number of API Commands:','1000grad-epaper').' '.count($clientinfo);
        return true;
	
}
}
function epaperTestApikeyApi() {
  global $testapikey;  global $apiKey;  epaper::epaperApikeyConnect();
  if ($apiKey>'') {
	echo 'API Version: '. $testapikey->getVersion();
      $clientinfo=($testapikey->__getFunctions());
	echo __('<br />Number of API Commands:','1000grad-epaper').' '.count($clientinfo);
	
    return true;
  }
}

function epaperContact() {
    
      echo "<p><a href=http://www.1000grad-epaper.de><img align=right src=".plugin_dir_url("1000grad-epaper/1000grad_logo.png")."1000grad_logo.png></a>";
 ?>
        <b>1000°DIGITAL GmbH</b>
    <p>Lampestr. 2
    <br />D-04107 Leipzig
    <br />Support: +49 341 96382-63
    <br />Fax: +49 341 96382-22
    <p>info@1000grad.de
    <br />http://www.1000grad.de
    <br />http://www.1000grad-epaper.de/loesungen/wp-plugin
        
        <?php
}

function epaperLicense() {
  global $test;  global $apiKey;  epaper::epaperConnect();
  global $channel;  epaper::epaperChannelConnect();
if ($apiKey>'') {
	
	try {
      $clientinfos=$test->clientGetInfos($apiKey);
    } catch (SoapFault $e) {echo '<br /><b>Fehler bei API Key Athentifizierung'.$e->getMessage().'</b>'; return false;}

	$clientinfo=json_decode($clientinfos);
	echo 'Name: '.$clientinfo->name;
	echo ' ('.$clientinfo->firstname.' '.$clientinfo->lastname.') ';
	echo '<br />Kurzname: '.$clientinfo->short_name;
	echo '<br />Kundennummer: '.$clientinfo->customer_id;
	echo '<br />Email: '.$clientinfo->email;
	echo '<br /><b>vorhandene Kanäle: '.$clientinfo->channels_count.'</b>';
	echo '<br />verwendeter Speicher: '.round($clientinfo->disk_usage / 1024 / 1024 ).' MByte';
	echo '<br />vorhandene ePapers: '.$clientinfo->count_created;
	echo '<br />publizierte ePapers: '.$clientinfo->count_published;
//	echo '<br />ePapers Limit: '.$clientinfo->count_limit;


	try {	$clientchannels=json_decode($channel->channelsGetList($apiKey));
    } catch (SoapFault $e) {echo '<br /><b>Fehler bei API Key Athentifizierung '.$e->getMessage().'</b>'; return false;}
	echo '<br />ePapers Channels: '.$clientchannels->count;
	foreach ( $clientchannels->channels as $channelt ) {
//	$channelinfo=json_decode($testchannel->GetChannelInfo($apiKey,$channel->id));
	echo "<br />ID: ".$channelt->id;
	echo " - ".$channelt->time_created;
	echo " - ".$channelt->time_modified;
	echo " - ".$channelt->expiry_time;
        echo " ";
    if ($channelt->id_epaper>"")       echo "- ePaper Nr. ".$channelt->id_epaper;
    else _e('not used','1000grad-epaper');

      }
  return true;
}
}


function epaperSettings() {

	$epaper_options = get_option("plugin_epaper_options");

	if (!is_array( $epaper_options )) { 
		$epaper_options = array(
			'url' => '',
			'wordpressapi' => 'http://epaperplugin.1kcloud.com/api/v2/wordpress-wsdl/',
			'apikey' => '', 
		);
	} 						
	if ($_GET['epaper-settings-save']) { 
	

		$epaper_options['url'] = htmlspecialchars($_GET['epaper_url']);
		$epaper_options['wordpressapi'] = htmlspecialchars($_GET['epaper_wordpressapi']);
		$epaper_options['apikey'] = htmlspecialchars($_GET['epaper_apikey']); 
		update_option("plugin_epaper_options", $epaper_options);  
	}

//        epaper_more_channels
       	if ($_GET['epaper-more-channels']) {    echo "upgrade not in settings - tbo";	}

//    echo "<img align=right src=".plugin_dir_url("1000grad-epaper/1000grad_logo.png")."1000grad_logo.png><br />";

  
?>
      
<div class="wrap"> 
  <h1>1000°ePaper</h1>
  <div class="postbox-container" style="width:70%;">
    <div class="metabox-holder">

    <div class="ui-sortable meta-box-sortables">    <div class="postbox">    <h3><?php _e("Feedback",'1000grad-epaper'); ?></h3>    <div class="inside">

<?                if (isset($_POST['feedback'])) { 
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
        if (extension_loaded('curl')) $more="curl ist installiert";

      try {
            global $testapikey;  global $apiKey;  epaper::epaperApikeyConnect();
            $email=json_decode($testapikey->sendFeedback($epaper_options['email'],$text,$more,admin_url(),$phpupload,$phptime,$version_wordpress,$version_php,$language));
    } catch (SoapFault $e) {
      echo '<br /><b>Error '.$e->getMessage().'</b>'; 
//	  if ($e->getMessage()=="(605) no valid email adress") _e("<br />Email adress is not valid.",'1000grad-epaper');
	   }
                  _e("<br>Your feedback comment was sent to the 1000°ePaper Support Team. Thank you for contacting us.");
                  echo "<br><i>".$text."</i>";

  } else {
      ?>
      <form action="" method="post">
        <label for="text"><?php _e("Your opinion is important! We develop our software continuously and the focus of our efforts, you as a user of our software. Please send us your comments, questions and suggestions. We will contact you immediately.",'1000grad-epaper'); ?></label>
        <textarea name="text" id="epaper_wordpressapi" value="" rows="5" cols="75"></textarea>
        <br />
        <!--<input type="submit" name="epaper-feedback-send" id="epaper-feedback-send" value="Send" class="button" />-->
        <input type="submit" name="feedback" id="feedback" value="<?php _e("send feedback",'1000grad-epaper'); ?>" class="button" />
        <input type="hidden" name="page" value="feedback_send" />
      </form>
  <?
                }
                
   ?>            
                
    </div></div></div><div class="ui-sortable meta-box-sortables">    <div class="postbox">    <h3><?php _e("Contact",'1000grad-epaper'); ?></h3>    <div class="inside">
<?php
      echo "<p><a href=http://www.1000grad.de><img align=right src=".plugin_dir_url("1000grad-epaper/1000grad_logo.png")."1000grad_logo.png></a>";
 ?>
    <b>1000°DIGITAL GmbH</b>
    <p>Lampestr. 2
    <br />D-04107 Leipzig
    <br />Support: +49 341 96382-63
    <br />Fon: +49 341 96382-82
    <br />Fax: +49 341 96382-22
    <p>info@1000grad.de
    <br />http://www.1000grad.de
    <br />http://www.1000grad-epaper.de/loesungen/wp-plugin
                
    </div></div></div><div class="ui-sortable meta-box-sortables">    <div class="postbox">    <h3><?php _e("Settings",'1000grad-epaper'); ?></h3>    <div class="inside">

          <?php
        if (extension_loaded('curl')) echo "";
          else {
            _e("<br /><b>Error, there is NO CURL installed at your wordpress system!</b>",'1000grad-epaper');
            _e("<br />Please install it first!<br><code>apt-get install php5-curl</code>",'1000grad-epaper');
            exit();
          }
?>
      
            
      <form action="" method="get">
        <label for="epaper_url">ePaper Wordpress API: </label>
        <input type="text" name="epaper_wordpressapi" id="epaper_wordpressapi" value="<?php echo ($epaper_options['wordpressapi']); ?>" size="55" />
<!--		<i>e.g. "https://epaper.1000grad.com/plugin/api/v2/wordpress-wsdl/" </i> -->
        <br />
        <label for="epaper_url">ePaper API URL: </label>
        <input type="text" name="epaper_url" id="epaper_url" value="<?php echo ($epaper_options['url']); ?>" size="55" />
<!--		<i>e.g. "https://epaper.1000grad.com/html/api/v2/" </i>-->
        <br />
        <label for="">ePaper API Key: </label>
        <input type="text" name="epaper_apikey" id="epaper_apikey" value="<?php echo ($epaper_options['apikey']); ?>" size="50" />
        <br />
        <input type="submit" name="epaper-settings-save" id="epaper-settings-save" value="Save" class="button" />
        <input type="hidden" name="page" value="epaper_settings" />
      </form>

            

    </div></div></div><div class="ui-sortable meta-box-sortables">    <div class="postbox">    <h3>Infos</h3>    <div class="inside">
            <?php  epaper::epaperTestWordpress();  ?>
    </div></div></div><div class="ui-sortable meta-box-sortables">    <div class="postbox">    <h3>ePaper Wordpress API</h3>    <div class="inside">
            <?php  epaper::epaperTestApikeyApi();?>
    </div></div></div><div class="ui-sortable meta-box-sortables">    <div class="postbox">    <h3>ePaper User API</h3>    <div class="inside">
            <?php  epaper::epaperTestApi(); ?>
    </div></div></div><div class="ui-sortable meta-box-sortables">    <div class="postbox">    <h3>ePaper Channel API</h3>    <div class="inside">
            <?php  epaper::epaperTestChannelApi(); ?>
    </div></div></div><div class="ui-sortable meta-box-sortables">    <div class="postbox">    <h3>ePaper API License</h3>    <div class="inside">
            <?php  epaper::epaperLicense(); ?>
    </div></div></div>

  
  </div>
  </div>
  
	<br />
  
  <?php
      echo "</div>";     
 }


function epaperApikey() {
  global $testapikey;  global $apiKey;  epaper::epaperApikeyConnect();
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
              # Daten werden angefordert. Bitte schauen Sie in Ihr email-Postfach
		$epaper_options['email'] = $email; 
		$epaper_options['text'] = $text; 
		update_option("plugin_epaper_options", $epaper_options);  

	try {
	$email=json_decode($testapikey->getRegistrationCodeByEmail($email,$text,$wordpress,$phpupload,$phptime,$wordpresscode,$agb,$newsletter,$version_wordpress,$version_php,$language));
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
	<!--	<i>e.g. "Walter.Schmidt@email.tv" </i>
        <label for="apikey_text">Text : </label>
        <input type="text" name="apikey_text" id="apikey_text" value="<?php echo ($epaper_options['text']); ?>" size="55" />
		<i>e.g. Firmenname, Aktions-Code usw.</i>
  -->
                <br /><input type="checkbox" name="agb" value="yes"> 
          <?php _e("I've read the <a href=http://www.1000grad.de/upload/Dokumente/agb/terms_of_use_1000grad_ePaper_API_WP_Plugin.pdf>terms of use</a> and I agree.",'1000grad-epaper'); ?>
                <br /><input type="checkbox" name="newsletter" value="yes" checked>
    <?php _e("I want to receive newsletters from 1000°DIGITAL GmbH.",'1000grad-epaper'); ?>
        
        <br />
        <input type="hidden" name="apikey_wordpress" id="apikey_wordpress" value="<?php print admin_url(); ?>" />
        <input type="hidden" name="apikey_phpupload" id="apikey_phpupload" value="<?php print $upload_mb; ?>"  />
        <input type="hidden" name="apikey_phptime" id="apikey_phptime" value="<?php print $uptime_mb; ?>"   />
        <input type="submit" name="on" id="on" value="<?php _e("send request",'1000grad-epaper'); ?>" class="button" />
        <input type="hidden" name="page" value="epaper_apikey" />      
</form>    
<!--                <p>Zusatzinformationen:
            <br />Wordpress Admin URL : <?php print admin_url(); ?>
            <br />PHP max. Upload: <?php print $upload_mb; ?> MB,
        PHP max. Time: <?php print $uptime_mb; ?> sek.
        <br />
        Wordpress Version: <?php print $versionwordpress; ?>,
        PHP: <?php print $versionphp; ?> 
-->

       
        
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
  epaper::epaperContact(); 
  echo "</div>";
  echo "</div>";
  echo "</div>";

    }
  echo "</div>";
  echo "</div>";
  echo "</div>";
}



function epaperChannelUpload() {
#
//           global $test; global $channel; global $apiKey;  epaper::epaperConnect(); epaper::epaperChannelConnect();
  global $test; global $channel; global $apiKey;  epaper::epaperConnect(); epaper::epaperChannelConnect();

      $upload=$_FILES['uploadfile'];
    $id=$_POST['id'];
    $id_epaper=$_POST['id_epaper'];

//        echo '<br />ID channel '.$id;
//        echo '<br />ID des alten ePapers '.$id_epaper;
// Vorbereitung
            if ($id_epaper>"") {
            if ($channel->channelsRemoveEpaperFromChannel($apiKey,$id)) echo '<br />Kanal freigestellt<b>OK</b>' ;    else echo "<b>Fehler beim Kanal-freistellen!</b>";
            if ($test->epaperDelete($apiKey,$id_epaper)) echo '<br />voriges ePaper gelöscht<b>OK</b>' ;    else echo "<b>Fehler beim löschen des vorigen epapers!</b>";
            }
// sooo
        {
	echo '<h1>ePaper Upload</h1>Datei <b>'.$upload['name'].'</b> ('.$upload['size'].' byte / '.round($upload['size']/1024/1024).'MByte) ';
//print_r($upload);

   if ($upload['error']=='0') echo "<b>OK</b>";
    else {
        _e("<b>Error!</b> ");
        echo $upload['error'];
        echo "<hr>";
        _e("<br />Maybe file is larger than your wordpress php settings.",'1000grad-epaper');
        _e("<br />You can rise up these limits by creating file <b>wp-admin/.htaccess</b>",'1000grad-epaper');
        echo "<pre>
        php_value upload_max_filesize 100M
        php_value post_max_size 100M
        php_value max_execution_time 200
        php_value max_input_time 200
</pre><br />";
        _e("or look for hints at",'1000grad-epaper');
        echo " <a href=http://php.net/manual/en/features.file-upload.php>php.net</a><hr>";
        epaper::epaperTestWordpress();
        return false;
    }
    $file = $upload['tmp_name'];
    $epaper_options = get_option("plugin_epaper_options");
    $apiKey=$epaper_options['apikey'];
    $uploadUrl = $epaper_options['url']."pdf-upload/";
    $postParams =array(
    'file' => "@" . $file.';filename='.urlencode($upload['name']),
    'apikey' =>  $apiKey
);
// upload
    
   if (extension_loaded('curl')) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_URL, $uploadUrl);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postParams);
    $response = curl_exec($ch);
    curl_close($ch);
    $result = json_decode($response, true);
    if (!$result) {
        print_r($response);
        echo '<br />Error: Could not decode response!';
        return false;
    } elseif (!$result['success']) {
        echo '<p><b>Error: '.$result['errors']['errorDesc'].'</b>';
    //    echo '<br />Error-Code: '.$result['errors']['errorCode'].' ';
    //    print_r($result);
        return false;
    } else {
//        echo '<br />PDF-ID: '.$result['pdfId'];
    }
    $uploadit=$result['pdfId'];
//        print_r($result);
    _e('<br />Upload was sucessful','1000grad-epaper');

                }
          else {
            _e("<br /><b>Error, there is NO CURL installed at your wordpress system!</b>",'1000grad-epaper');
//           return false; 
  echo "<pre>";
      $data = "";
      $boundary = "---------------------".substr(md5(rand(0,32000)), 0, 10); 
      $fileContents = file_get_contents($upload['tmp_name']); 
       $data .= "--$boundary\n";
       $data .= "Content-Disposition: form-data; name=\"apikey\"\n\n".$apiKey."\n"; 
       $data .= "--$boundary\n";
        $data .= "Content-Disposition: form-data; name=\"file\"; filename=\"".urlencode($upload['name'])."\"\n";
        $data .= "Content-Type: application/octet-stream\n";
//        $data .= "Content-Transfer-Encoding: binary\n\n";
        $data .= $fileContents."\n";
        $data .= "--$boundary\n"; 

   $opts = array(
    'http'=>array(
      'method'=>"POST ".parse_url($uploadUrl, PHP_URL_PATH),
      'user_agent'=>"Wordpress ePaper upload",
      'header'=>"Accept-language: en\r\n" .
                "Cookie: foo=bar\r\n" .
             "Content-Type: multipart/form-data; boundary=".$boundary,
      'content'=>$data

        )
      );
   print_r($opts);
$context = stream_context_create($opts);
$socket = stream_socket_client("tcp://".parse_url($uploadUrl, PHP_URL_HOST).":80", $errno, $errstr, 30, STREAM_CLIENT_CONNECT, $context);
while (!feof($socket)) {
  sleep(1);
        echo fgets($socket, 1024);
    }
    fclose($socket);
echo "xx- upload ende des tests";
return false;
          }

    
    $uploadid = $test->epaperCreateFromPdf($apiKey,$uploadit);
//    _e('<br />ePaper generation started','1000grad-epaper');
    if ($test->epaperStartRenderprocess($apiKey,$uploadid))     _e('<br />ePaper Rendering was started.','1000grad-epaper');
               else                                             _e('<br />ePaper rendering couldnt start <b>error</b>.','1000grad-epaper');
                        
     require_once 'ProgressBar.class.php';
         $bar = new ProgressBar();
         $bar->setAutohide(true);
         $bar->setSleepOnFinish(1);
         $bar->setForegroundColor('#333333');
         $elements = 10; //total number of elements to process
         $bar->setBarLength(400);
         for($j=5;$j>2;$j++){
             $infoj=$test->epaperGetInfos($apiKey,$uploadid);
             $info = json_decode($infoj, true);
//             echo "".preg_replace("/(render)/", "", $info['status'])."";
             echo "".$info['status']." ";
             $status=$info['status'];
             if ($status=="ready") $j=0;
             echo $info['renderprocess']['current_page']."/".$info['pages'];
         $bar->initialize($elements); //print the empty bar
         $bar->setMessage($status);
         for($i=0;$i<$elements;$i++){
 	sleep(1); 
 	$bar->increase(); //calls the bar with every processed element
        } 
        }
              
        echo "<br clear=all>";
        echo "<p>";
    epaper::epaperChannelDetailsForm($id,$uploadid,$upload['name']);
	}




}

function epaperChannelEmpty() {
  global $test; global $channel; global $apiKey;  epaper::epaperConnect(); epaper::epaperChannelConnect();

    $id=$_POST['id'];
    $id_epaper=$_POST['id_epaper'];
            if ($id_epaper>"") {
            if ($channel->channelsRemoveEpaperFromChannel($apiKey,$id)) echo '<br />Kanal freigestellt<b>OK</b>' ;    else echo "<b>Fehler beim Kanal-freistellen!</b>";
            if ($test->epaperDelete($apiKey,$id_epaper)) echo '<br />voriges ePaper gelöscht<b>OK</b>' ;    else echo "<b>Fehler beim löschen des vorigen epapers!</b>";
            }
}

function epaperChannelDetails() {
  global $test; global $channel; global $apiKey;  epaper::epaperConnect(); epaper::epaperChannelConnect();

    $uploadid=$_POST['id_epaper'];
    $id=$_POST['id'];
    $title=$_POST['title'];
    $lang=$_POST['lang'];
    _e('<br />Name:','1000grad-epaper');
    echo ' '.($title);
    if ($test->epaperSetVar($apiKey,$uploadid,'title',html_entity_decode($title))=='1') echo ' <b>OK</b>' ;
     else echo "probleme beim titel-Setzen";

      try {	$fileup = $test->epaperSetVar($apiKey,$uploadid,'is_pdf_download','0');
		} catch (SoapFault $e) {echo 'Fehler beim Editieren von ePaper (download)'.$e->getMessage(); return false;}
      try {	$fileup = $test->epaperSetVar($apiKey,$uploadid,'is_search','1');
		} catch (SoapFault $e) {echo 'Fehler beim Editieren von ePaper (search)'.$e->getMessage(); return false;}
      try {	$fileup = $test->epaperSetVar($apiKey,$uploadid,'use_ipad','1');
		} catch (SoapFault $e) {echo 'Fehler beim Editieren von ePaper (mobile)'.$e->getMessage(); return false;}

      try {	$fileup = $test->epaperMove($apiKey,$uploadid,html_entity_decode($title),'1');
		} catch (SoapFault $e) {echo 'Fehler beim Umbenennen vom ePaper '.$e->getMessage(); return false;}



// channelsPublishEpaperTochannel
      try {	$fileup = $channel->channelsPublishEpaperToChannel($apiKey,$uploadid,$id);
		} catch (SoapFault $e) {echo '<br />Fehler beim Kanalisieren von ePaper: '.$e->getMessage(); return false;}

        if ($fileup=='1')                 _e('<br />Publishing started.','1000grad-epaper');
           else   _e('<br />Unable to start publishing.','1000grad-epaper');
        echo "<br />";
        
        
            require_once 'ProgressBar.class.php';
         $bar = new ProgressBar();
         $bar->setAutohide(true);
         $bar->setSleepOnFinish(1);
         $bar->setForegroundColor('#333333');
         $elements = 10; //total number of elements to process
         $bar->setBarLength(400);

         for($j=5;$j>2;$j++){
             $infoj=$test->epaperGetInfos($apiKey,$uploadid);
             $info = json_decode($infoj, true);
             $status=$info['status'];
             if ($status=="ready") $j=0;
//             echo $info['status']." ";
//             echo $info['renderprocess']['current_page']."/".$info['pages'];
         $bar->initialize($elements); //print the empty bar
         $bar->setMessage($status);
         for($i=0;$i<$elements;$i++){
 	sleep(1); // simulate a time consuming process
 	$bar->increase(); //calls the bar with every processed element
        } 
        }

        echo "<br clear=all>";
        echo "<p>";
        _e("Your ePaper is now ready to use.",'1000grad-epaper');
        echo "<br />";
        
?>
	<form action="?page=epaper_channels" method="post" />
        <input type="submit" name="weiter" id="weiter" value="<?php _e('next','1000grad-epaper'); ?>" class="button" />
<?php
        
        

}

function epaperChannelDetailsForm($id,$id_epaper,$name) {
//    echo '<p><b>weitere Angaben:</b>';
//    echo "<br />channel: ".$id." neues Paper:".$id_epaper." Dateiname: ".htmlentities($name);
    $name = preg_replace("/^(.+)\.pdf$/i","\\1",$name);
    ?>
      <div class="epaperform">
    <div class="metabox-prefs">
     <form action="" method="post">
        <br /><label for="title"><?php _e('Title:','1000grad-epaper'); ?></label><input type="text" name="title" id="title" value="<?php echo ($name); ?>" size="50" />
        <br /><label for="lang"><?php _e('Language:','1000grad-epaper'); ?></label>
        <select name="lang" size="1">
            <option value=de>deutsch</option>
            <option value=en>english</option>
            <option value=es>spanish</option>
        </select>
        <input type="hidden" name="id" value="<?php echo $id; ?>" />
        <input type="hidden" name="id_epaper" value="<?php echo $id_epaper; ?>" />
        <input type="hidden" name="modus" value="channeldetails" />
        <br />
        <input type="submit" name="modusss" id="modus" value="<?php _e('save','1000grad-epaper'); ?>" class="button" />
      </form>
    </div>
  </div>
<?php

}

function epaperChannelUploadForm($id,$id_epaper) {
    // bgcolor=#cf3
    // 
//            echo "<table border=0 align=left bordercolor=#be3326 bordercolordark=#be3326 bordercolordark=#be3326 width=100 height=100><tr><td>";
?>
   <div class="epaperform">
    <div class="metabox-prefs">
<!--
<form action="" method="post">
        <input type="hidden" name="channelid" value="<?php echo $id; ?>" />
        <input type="hidden" name="modus" value="postpost" />
        <input type="submit" name="epaper-postpost" value="Post">
      </form>
-->

      <form action="" method="post" enctype="multipart/form-data">
        <?php _e('Upload a new PDF','1000grad-epaper'); ?><br /> 
        <input type="file" name="uploadfile" id="epaper-upload"><br />
        <input type="hidden" name="id" value="<?php echo $id; ?>" />
        <input type="hidden" name="id_epaper" value="<?php echo $id_epaper; ?>" />
        <input type="hidden" name="modus" value="upload" />
        <input type="submit" name="epaper-upload" value="<?php _e("upload",'1000grad-epaper'); ?>">
      </form>
<?php if ($id_epaper>""): ?>
        <form action="" method="post">
        <input type="hidden" name="id" value="<?php echo $id; ?>" />
        <input type="hidden" name="id_epaper" value="<?php echo $id_epaper; ?>" />
        <input type="hidden" name="modus" value="empty" />
        <input type="submit" name="epaper-delete" value="<?php _e("clear channel",'1000grad-epaper'); ?>">
      </form>
<?php endif; ?>
    </div>
  </div>
<?php
}

function epaperChannelPostPost() {
   global $channel; global $apiKey;  epaper::epaperConnect(); epaper::epaperChannelConnect();
	echo '<h3>Poste ePaper Kanal</h3>';
//        $channelid=$_GET["channelid"];
        $channelid=$_POST["channelid"];
        if ($channelid=="")   $channelid=$_GET["channelid"];
		try {
		$channelinfo = $channel->channelsGetChannelInfo($apiKey,$channelid);
		} catch (SoapFault $e) {echo 'Fehler beim Zugriff auf ePaper Kanal'.$e->getMessage(); return false;}
        $info=json_decode($channelinfo);
//        print_r($info);

      echo 'Kanal URL '.$info->url;
      $url=$info->url;
    $html="[ePaper url=".$url."]";
      $text='Hier mein neues ePaper <b>'.$info->title.'</b> zum durchbl&auml;ttern: ';

// need only ONE ePaper Link for the beta-test!    
        $text=__("This is my new ePaper, brought to you by the 1000°ePaper service. Even you can <a href=http://www.1000grad-epaper.de/de/loesungen/wp-plugin>share your ePapers</a> with this wordpress plugin! Get your first ePaper Channel  FOR FREE during beta stage.",'1000grad-epaper');
        $html="[ePaper]";

    $text=$html.$text;
//    <a title="ePaper" href="'.($info->url).'" target="_blank"><img class="alignright" src='.($info->url).'epaper/preview.jpg alt="ePaper preview" /></a>';
      
      $my_post = array(
     'post_title' => 'ePaper '.$info->title,
     'post_content' => $text,
     'post_status' => 'publish',
  );
  $postid=wp_insert_post( $my_post );
      _e('<br />was posted (without category)<br />');
      echo '<b><a href=post.php?post='.$postid.'&action=edit>';
      _e('Further edit this post');
      echo '</a></b>';

	}



function epaperChannels() {
  global $channel;  global $apiKey; epaper::epaperChannelConnect();
      $modus=$_REQUEST['modus'];
//  	$id=$_REQUEST['id']; // sollten noch mehr Sicherheitsabfragen rein (nur Zahlen und Buchstaben!)
//        $channelid=$_REQUEST['channelid'];

  if ($modus=="upload") {epaper::epaperChannelUpload(); }
  if ($modus=="postpost") {epaper::epaperChannelPostPost(); }
  if ($modus=="channeldetails") {epaper::epaperChannelDetails(); }
  if ($modus=="channelupgrade") {epaper::epaperChannelUpgrade(); }
  if ($modus=="empty") {epaper::epaperChannelEmpty(); }
  if ($modus=="list") {epaper::epaperList(); }
//  if ($modus=="upload") {epaper::epaperChannelUpload($channelid,$testchannel,$apiKey); }
   if ($modus=="" or $modus=="channelupgrade" or $modus=="empty") {

?>       
<div class="wrap"> 
  <h1>1000°ePaper</h1>
  <div class="postbox-container" style="width:95%;">
  <div class="metabox-holder">

      <div class="ui-sortable meta-box-sortables">
    <div class="postbox">
    <div class="inside">
<?php       
       
       
  echo "<img align=right hspace=20 vspace=10 src=".plugin_dir_url("1000grad-epaper/1000grad_logo.png")."1000grad_logo.png>";
    _e("The new ePaper PlugIn aims to support Wordpress users in creating and adding ePaper publications to the WordPress blog.  Creating interactive FLASH and HTML5 based ePapers has never been easier. Upload your pdf file and create your interactive multimedia publication in a few steps. Each publication is optimized for web and mobile (iOS and Android) display and is equipped with an automatic device recognition. Test this new beta service and get one publication channel for free!",'1000grad-epaper');
  echo "<br />";  

  
          if (extension_loaded('curl')) echo "";
          else _e("<br /><b>Error, there is NO CURL installed at your wordpress system!</b>",'1000grad-epaper');

  
  
  if ($apiKey>"") {
  
  $channels=json_decode($channel->channelsGetList($apiKey));
//        print_r($channels);
        $channelcount=count($channels->channels);
        $channelnr=0;
//        if ($channelcount=="0") _e('<h2>no ePaper Channel existing!</h2>','1000grad-epaper');
//        if ($channelcount=="1") _e('<h2>Your ePaper Channel</h2>','1000grad-epaper');
//        if ($channelcount> "1") _e('<h2>existing ePaper Channels</h2>','1000grad-epaper');
//
//   echo "<pre>";
//        print_r($channels);
//   echo "</pre>";
        echo "</div></div></div>";
	foreach ( $channels->channels as $channelz ) {
    $channelnr=$channelnr+1;
	$channelinfo=json_decode($channel->channelsGetChannelInfo($apiKey,$channelz->id));
        epaper::epaperChannelShow($channelinfo,$channelnr);

    }
//echo '<script>jQuery(".iframe").colorbox({iframe:true, width:"80%", height:"95%"}); </script>';

//    echo "<br clear=all><hr>";
//    echo "<img src=".plugins_url( '1000grad_logo.png', __FILE__ )."><br />";
    $channelnr=$channelnr+1;
//            echo "<tr bgcolor=grey><td align=left valign=middle colspan=3 bgcolor=grey>";
//            echo "<h3>";
//	    _e("ePaper Channel",'1000grad-epaper');
//            echo " #".$channelnr." ";
//            echo " </h3>";
//            echo "</td></tr>";
//    echo "<tr ><td bgcolor=grey valign=middle align=center>";
?>    
          <div class="ui-sortable meta-box-sortables">
    <div class="postbox">
    <h3><?php _e("add another ePaper Channel",'1000grad-epaper'); 
//    echo " #".$channelnr; 
    ?></h3>
    <div class="inside">
<?php
       echo "<table width=100%>";
            echo "<tr><td width=30% align=center valign=middle>";
    echo "<img src=".plugin_dir_url("1000grad-epaper/1000grad_logo.png")."1000grad_logo.png></td><td width=30%>";
        epaper::epaperChannelUpgradeForm();
    echo "</td><td width=30%>";
    _e("You can get more 1000°ePaper channels for your account for instance via PayPal. Coming soon.",'1000grad-epaper');
    _e("<br>Please <a href=options-general.php?page=epaper_settings>give us feedback about this plugin</a>",'1000grad-epaper');
    echo "</td></tr></table>";
     echo "</div></div></div>";
//            echo "</table>";
        echo "</div></div></div>";


      }
   }
}

function epaperChannelUpgradeForm() {
    ?>
        
            <form action="" method="post">
        <label for="epaper_code"><?php _e("Unlock more channels via activation code:",'1000grad-epaper'); ?> </label>
        <input type="text" name="epaper_code" id="epaper_code" value="" size="30" />
        <input type="submit" name="epaper-more-channels" id="epaper-more-channels" value="Upgrade" class="button" />
        <input type="hidden" name="modus" value="channelupgrade" />
      </form>
     <?php


}

function epaperChannelUpgrade() {
     global $testapikey;  global $apiKey;  epaper::epaperApikeyConnect();
	$epaper_options = get_option("plugin_epaper_options");
	$email = $epaper_options['email'];


     $code=$_POST['epaper_code'];

    	try {	$succ=json_decode($testapikey->sendCodeGetMoreChannels($email,$code));
    } catch (SoapFault $e) {
      echo '<br /><b>';
      _e('Es ist ein Fehler aufgetreten');
      echo ' '.$e->getMessage().'</b>';
	  if ($e->getMessage()=="(610) code failed") echo "<br />Code ungueltig oder schon verbraucht!<br />";
	  if ($e->getMessage()=="(605) no valid email adress") echo "<br />Bitte die Email-Adresse korrekt angeben!";
	  if ($e->getMessage()=="(604) no name") echo "<br />Bitte geben Sie den Name korrekt an!";
	else return false;
	   }
        if ($succ=="ok") _e("Channel upgrade was successful!",'1000grad-epaper');
}


function epaperChannelShow($channelinfo,$channelnr) {
    
    ?>
             <div class="ui-sortable meta-box-sortables">
    <div class="postbox">
    <h3><?php
	    _e("ePaper Channel",'1000grad-epaper');
            echo " #".$channelnr." ";
            echo " ".$channelinfo->title." ";
    
    ?></h3>
    <div class="inside">
        <?php
            echo "<table width=100%>";
            echo "<tr><td width=30% align=center valign=middle>";
    if (!isset($channelinfo->id_epaper)) {
        echo __("no ePaper given",'1000grad-epaper');
    }
        else {
//            echo "<img border=2 height=300 src=".$channelinfo->url."/epaper/preview_large.jpg?rnd=".rand(1000, 9999)." hspace=20>";
//            echo "<img border=2 height=300 src=".$channelinfo->url."/epaper/epaper-ani.gif?rnd=".rand(1000, 9999)." hspace=20>";
    $html="<a class='iframe cboxElement' href='";
    $html.=$channelinfo->url."'";
    $html.='> <img border=2 width=200 src="';
    $html.=$channelinfo->url.'/epaper/epaper-ani.gif?rnd='.rand(1000, 9999).'" alt="epaper preview gif" border="0" hspace=20 /> </a>';
//    $html.='<script>    jQuery(".iframe").colorbox({iframe:true, width:"80%", height:"95%"}); </script>';
    echo $html;
            
            
            
            
            	}
           echo "</td><td  width=30% valign=top>";
                   epaper::epaperChannelUploadForm($channelinfo->id,$channelinfo->id_epaper);
            echo "</td><td  width=30% valign=top>";
    if (!isset($channelinfo->id_epaper)) 
      { 
      _e("According to your wordpress settings the maximum upload size is limited to",'1000grad-epaper');
      	$max_upload = (int)(ini_get('upload_max_filesize'));
      	$max_post = (int)(ini_get('post_max_size'));
        $memory_limit = (int)(ini_get('memory_limit'));
        $upload_mb = min($max_upload, $max_post, $memory_limit);
	    echo " ".$upload_mb." MB.";
      
    }
    else {
        
            _e("Embed this ePaper:",'1000grad-epaper');
            echo "<ul>";          
                if ($channelnr=="1")   echo "<li><b><a href=widgets.php>via Widget</a></b>";
            
            echo "<li>";          
            _e("wordpress shortcode:",'1000grad-epaper');
                  if ($channelnr=="1")   echo "<br /><b>[ePaper]</b>";
                else            echo "<br /><b>[ePaper nr=".$channelnr."]</b>";
            echo "<li>";          
            _e("or",'1000grad-epaper');
            echo " [ePaper url=".$channelinfo->url."]";
//                if ($id_epaper>"")  { 

                
                
//            echo "<br /><a href=".$channelinfo->url."><b>".$channelinfo->url."</b></a>";
//             _e("<p><b>you can</b>",'1000grad-epaper');

                  
                  echo "<li>";
//                  _e("Embed this ePaper Channel into Post:",'1000grad-epaper'); 
            echo " <a href=?page=epaper_channels&modus=postpost&channelid=".$channelinfo->id."><b>";
             _e("Create a new post with this ePaper",'1000grad-epaper');
            echo "</b></a>";
                   //if ($epaper_options['apikey']=="")
//           echo "</td><td valign=top>";
	    _e("<li>HTML code for advanced users:",'1000grad-epaper');
            echo "<p><small><code>".htmlentities("<a href=".$channelinfo->url."><img src=".$channelinfo->url."epaper/preview.jpg></a>")."</code></small>";
            echo "</ul>";
       }
    
                $url=$channelinfo->url;
            	$epaper_options = get_option("plugin_epaper_options");
                $epaper_options['channelurl'.$channelnr]=$url;
                $epaper_options['channeltitle'.$channelnr]=$channelinfo->title;
                update_option("plugin_epaper_options", $epaper_options);
    echo "</td></tr></table>";
    echo "</div></div></div>";

}


  function epaperMetaBox() {
      global $channel;  global $apiKey; epaper::epaperChannelConnect();
	$channels=json_decode($channel->channelsGetList($apiKey));
        $channelcount=count($channels->channels);
        if ($channelcount=="0") _e('<h2>no ePaper Channel existing!</h2>','1000grad-epaper');
            _e("insert this shortcode into editor:",'1000grad-epaper');
        $channelnr=0;
	foreach ( $channels->channels as $channell ) {
        $channelnr=$channelnr+1;
	$channelinfo=json_decode($channel->channelsGetChannelInfo($apiKey,$channell->id));
        epaper::epaperChannelShowBox($channelinfo,$channelnr);

    }
           echo "<br clear=all><hr>";
//      echo "      <p>So fügen Sie das ePaper manuell hinzu</p>
//                <p>[epaperchannel title='TITEL_EPAPER']</p>           ";
  }


function epaperChannelShowBox($channelinfo,$channelnr) {
//    epaper::epaperChannelUploadForm($channelinfo->id,$channelinfo->id_epaper);
            echo "<br clear=all><hr>";
//            echo $channelnr.". ";
//	    _e("ePaper Channel ",'1000grad-epaper');
//            echo $channelinfo->id.": ";
            $url=$channelinfo->url;
            if ($channelnr=="1")   echo "<b>[ePaper]</b>";
                else            echo "<b>[ePaper nr=".$channelnr."]</b>";
    if (!isset($channelinfo->id_epaper)) echo __("<br />Channel empty<br />please upload a file",'1000grad-epaper');    else {
            echo "";

//            echo "<img align=right src=".$channelinfo->url."/epaper/thumbs/page_1.jpg?rnd=".rand(1000, 9999).">";
            echo "".$channelinfo->title."<br />";
            //echo "<a href=http://1000grad.de><img width=100 src=".$channelinfo->url."/epaper/preview_large.jpg?rnd=".rand(1000, 9999)." hspace=20></a>";


    $html="<a class='iframe cboxElement' href='";
    $html.=$url."'";
    $html.='> <img src="';
    $html.=$url.'epaper/epaper-ani.gif?rnd='.rand(1000, 9999).'" alt="epaper preview gif" border="0" /> </a>';
//    $html.='<script>    jQuery(".iframe").colorbox({iframe:true, width:"90%", height:"95%"}); </script>';
    
    
    echo $html;

	}

}

}
