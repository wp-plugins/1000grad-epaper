(function() {
    tinymce.create('tinymce.plugins.tg_tiny_button', {
 
        init : function(ed, url){
            ed.addButton('tg_tiny_button', {
            title : 'Insert 1000°ePaper',
                onclick : function() {
                    tb_show('1000°ePaper', ajaxurl+'?action=epaper_ajax&ajax_option=tg_tiny_mce_button');   
                },
                image: url + "/../img/1000grad_icon.png"
            });
        }
    });
 
    tinymce.PluginManager.add('tg_tiny_button', tinymce.plugins.tg_tiny_button);
})();