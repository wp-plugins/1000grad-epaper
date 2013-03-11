<?php
/*
Plugin Name: 1000°ePaper
Plugin URI: http://epaper-apps.1000grad.com/
Description: Easily create browsable ePapers within Wordpress! Konvertieren Sie Ihre PDF in ein blätterbares Web-Dokument und binden Sie es mit einem Widget ein! Auch auf Android, iPad & Co. macht Ihr ePaper in der automatischen HTML5-Darstellung einen sehr guten Eindruck.
Version: 1.3.4
Author: 1000°DIGITAL Leipzig GmbH
Author URI: http://www.1000grad.de
*/

    // License 
   // Our plugin is compatible with the GNU General Public License v2, or any later version.
  //
 //Copyright (C) 2013 1000grad Digital GmbH
//
//This program is free software; you can redistribute it and/or
//modify it under the terms of the GNU General Public License
//as published by the Free Software Foundation; either version 2
//of the License, or (at your option) any later version.
//
//This program is distributed in the hope that it will be useful,
//but WITHOUT ANY WARRANTY; without even the implied warranty of
//MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//GNU General Public License for more details.
//

//script to hold config information
#require_once("inc/standard.inc.php");
//class contains main functions
require_once("lib/Epaper.php");
//hold wp functions 
require_once("lib/EpaperWpOptions.php");
//contains functions for html output
require_once("lib/epaperHtml.php");
//functions for com with channel API
require_once("lib/epaperChannelApi.php");
//functions for com with epaper API
require_once("lib/epaperApi.php");
//functions for com with 1000° wp API (Apikey API)
require_once("lib/epaperApikeyApi.php");
require_once ("lib/ProgressBar.class.php");

// setting config options
ini_set("soap.wsdl_cache_enabled", 1);
ini_set("soap.wsdl_cache_ttl", 86400);
 
//old
#$myEpaperClass = new epaper();

//option class object
$epaperWpOptions = new EpaperWpOptions();

add_action('init', array($epaperWpOptions,'epaperTextDomain'), 1);
add_action('admin_menu', array($epaperWpOptions,'epaperIntegrationMenu'));
add_shortcode('ePaper', array($epaperWpOptions,'epaperShortcode'));
wp_register_sidebar_widget('1000grad-ePaper','1000°ePaper', array($epaperWpOptions,'epaperWidget'), array('description' => 'Shows the first ePaper Channel' ));
wp_register_widget_control('1000grad-ePaper','1000grad-ePaper', array($epaperWpOptions,'epaperWidgetControl'));
//wp_register_sidebar_widget('1000grad-ePaper-2','1000°ePaper #2', array($epaperWpOptions, 'epaperWidget2'),
//                              array('description' => 'Shows the second ePaper Channel'));
//wp_register_widget_control('1000grad-ePaper-2','1000grad-ePaper #2', array(EpaperWpOptions, 'epaperWidgetControl2'));
add_action( 'add_meta_boxes', array( $epaperWpOptions, 'addEpaperMetaBox' ) );
add_filter('the_posts', array($epaperWpOptions,'conditionally_add_scripts_and_styles')); 


