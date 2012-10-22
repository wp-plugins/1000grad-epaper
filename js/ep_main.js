jQuery(document).ready(function()   // jQuery on READY 
  {  
    /*
     *  INIT SHADOWBOX
     */
    Shadowbox.init({
      language: 'de-DE',
      players: ['html', 'iframe']
    });
    
    // Set z-index div 'sb-container' to 10000.
    // Wordpress 3.4 has set header image to high z-index
    // so that the shadowbox opened beneath it.
    jQuery('#sb-container').css('z-index', 10000);
    
    /*
     * EPAPER MANAGER OVERVIEW PAGE
     */
    jQuery('.ep_delete_link').click(function()
    {
      if ( true != confirm('Wollen Sie das ePaper wirklich löschen?') ) {
        return false;
      }
        
    });

    jQuery('img.ep_preview_small').fadeIn('slow');
       
    /*
     * ROTATE MAGAZIN COVERS 
     */ 
    
    if (  window.location.search.indexOf( "esid=edit_epaper" ) == -1 &&
      window.location.search.indexOf( "esid=insert_epaper" ) == -1 ) {
            
      if ( jQuery.browser.msie ){
        var rotate_time = 600;
        jQuery('.ep_magazin_cover_1').rotate({
          animateTo:-5,
          duration: 0,
          callback: function(){
            jQuery(this).css('position', 'absolute');
            jQuery('.ep_magazin_cover_2').rotate({
              animateTo:10,
              duration: rotate_time,
              callback: function(){
                jQuery(this).css('position', 'absolute');
                jQuery('.ep_magazin_cover_3').css('position', 'absolute')
                jQuery('.ep_magazin_cover_3').rotate({
                  animateTo:15,
                  duration: rotate_time
                });
              }
            });
          }
        });
      }
      else {
        
        rotate_cover("ep_magazin_cover_1", -5, 0);
        rotate_cover("ep_magazin_cover_2", 10, 1500);
        rotate_cover("ep_magazin_cover_3", 15, 1500);
      }
        
    }
    
        

    
    /*
 * FADE OUT FEEDBACK BOXES
 */ 
    if ( jQuery('#ep_success_box') ){
      jQuery('#ep_success_box').delay(4000).fadeOut(1500);
    }
    if ( jQuery('#ep_error_box') ){
      jQuery('#ep_error_box').delay(4000).fadeOut(1500);
    }
    
    /*
 * INSERT AN EPAPER
 */
    
    var ep_absPathEpaperDir = String ( jQuery('#ep_absPathEpaperDir').html() );
    var ep_pluginSiteUrl = String( jQuery('#ep_pluginSiteUrl').html() );
    
    // show preview image of epaper 
    if ( ep_pluginSiteUrl != 'null' ) {
      getPreviewImage( jQuery('#ep_link').val(), ep_absPathEpaperDir );
    }
       
    // handle reset option
    jQuery('input#ep_reset').click(function(){
        
      if ( true != confirm('Wollen Sie wirklich abbrechen. Alle nichtgespeicherten Änderungen gehen verloren') ){
        return false;
      }
            
      window.location.href = ep_pluginSiteUrl; 
    });
    
    // preview epaper cover on epaper link input or change
    jQuery('#ep_link').change(function()
    {                    
      getPreviewImage( jQuery('#ep_link').val(), ep_absPathEpaperDir );
    });

    // on page load, get view option if already selected
    // and toggle link text span
    toogle_link_text( jQuery("input[name='ep_view_as']:checked").val() );

    // toggle css display property of 'link text div' 
    jQuery('#ep_radio_link_btn').click(function()
    {
      toogle_link_text( "Link" );
    });
    
    jQuery('#ep_radio_gif_btn').click(function()
    {
      toogle_link_text( "Animiertes GIF" );
    });
    
    jQuery('#ep_radio_grafik_btn').click(function()
    {
      toogle_link_text( "Grafik" );
    });
    
  }); // ready end

/*
* LIB
*/

function toogle_link_text( selected_view_option ){
    
  switch( selected_view_option ) {
    case 'Link':
      jQuery('.ep_text_link_row').fadeIn(500);
      break;
            
    default:
      jQuery('.ep_text_link_row').fadeOut(200);
      break;
  }
}

function getPreviewImage( ep_url, ep_absPathEpaperDir )
{
  if ( ep_url.substring( ep_url.length -1 ) != '/' ) {
    ep_url += '/';
  }
    
  jQuery('img#ep_loader').fadeIn('slow');
                    
  jQuery.ajax({
    type: 'POST',
    url:  String(ep_absPathEpaperDir + 'php/checkImage.php'),
    data: 'ep_link=' + encodeURI( ep_url ),
    dataType: 'json',
    success: function(json)
    {
      if ( true == json['img_exists_large'] )
      {
        jQuery('#ep_preview_img img').css('display', 'none');
        jQuery('#ep_preview_img img').attr('src', String(ep_url) + 'epaper/preview_large.png').fadeIn('slow', function(){
          load_cover_rotation( ep_absPathEpaperDir );
        });
      }
      else if ( true == json['img_exists_small'] )
      {
        jQuery('#ep_preview_img img').css('display', 'none');
        jQuery('#ep_preview_img img').attr('src', String(ep_url) + 'epaper/preview.jpg' ).fadeIn('slow', function(){
          load_cover_rotation( ep_absPathEpaperDir );
        });

      }
      else {
        jQuery('#ep_preview_img img').attr('src', String(ep_absPathEpaperDir  + 'img/Not_Available.jpg') ).fadeIn('slow');
      }
                  
      jQuery('img#ep_loader').fadeOut('fast');
    }
        
  });
}

function load_cover_rotation( absPathEpaperDir ) {

  jQuery('#ep_preview_img').
  prepend( "<img width=\"140px\" class=\"ep_magazin_cover_img ep_magazin_cover_3\" src=\"" + String( absPathEpaperDir + "img/dummy_page_120x160.jpg") + "\" alt=\"\" />" ).
  prepend( "<img width=\"140px\" class=\"ep_magazin_cover_img ep_magazin_cover_2\" src=\"" + String( absPathEpaperDir + "img/dummy_page_120x160.jpg") + "\" alt=\"\" />" );
  jQuery('#ep_preview_img').children().css('display', 'block');
    
  if ( jQuery.browser.msie ){
        
    var rotate_time = 600;
    jQuery('.ep_magazin_cover_1').rotate({
      animateTo:-5,
      duration: 0,
      callback: function(){
        jQuery(this).css('position', 'absolute');
        jQuery('.ep_magazin_cover_2').rotate({
          animateTo:5,
          duration: rotate_time,
          callback: function(){
            jQuery(this).css('position', 'absolute');
            jQuery('.ep_magazin_cover_3').css('position', 'absolute')
            jQuery('.ep_magazin_cover_3').rotate({
              animateTo:15,
              duration: rotate_time
            });
          }
        });
      }
    });
  }
  else {
        
    jQuery('.ep_magazin_cover_1').rotate({
      animateTo:5,
      duration: 1500
    });
        
    jQuery('.ep_magazin_cover_2').rotate({
      animateTo:-10,
      duration: 1500
    });
            
    jQuery('.ep_magazin_cover_3').rotate({
      animateTo:10,
      duration: 1500
    });
  }
    

}

function rotate_cover ( class_name,  angle, duration) {
    
  jQuery('.' + class_name).rotate({
    animateTo: parseInt( angle ),
    duration: parseInt( duration )
  });
}