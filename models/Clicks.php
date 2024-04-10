<?php
/**
 * Right Click and Keys disable Model
 *
 * @file  The Click file
 * @package HMWP/ClickModel
 * @since 6.0.0
 */
defined('ABSPATH') || die('Cheatin\' uh?');

class HMWP_Models_Clicks
{

    public function __construct()
    {
        add_action('wp_footer', array($this, 'disableKeysAndClicks'), PHP_INT_MAX);
        add_action('wp_enqueue_scripts', array($this, 'loadjQuery'), PHP_INT_MAX);
    }

    /**
     * Enqueue Jquery for later use
     */
    function loadjQuery()
    {
        if (! wp_script_is('jquery')) {
            wp_deregister_script('jquery');
            wp_register_script( 'jquery', includes_url( '/js/jquery/jquery.js' ), false, NULL, true );
            wp_enqueue_script('jquery');
        }
    }

    /**
     * Disable website keys and clicks
     */
    public function disableKeysAndClicks()
    {

        $hmwp_disable_inspect_message = ((HMWP_Classes_Tools::getOption('hmwp_disable_inspect_message') <> '') ? str_replace("'", "`", HMWP_Classes_Tools::getOption('hmwp_disable_inspect_message')) : '');
        $hmwp_disable_click_message = ((HMWP_Classes_Tools::getOption('hmwp_disable_click_message') <> '') ? str_replace("'", "`", HMWP_Classes_Tools::getOption('hmwp_disable_click_message')) : '');
        $hmwp_disable_copy_paste_message = ((HMWP_Classes_Tools::getOption('hmwp_disable_copy_paste_message') <> '') ? str_replace("'", "`", HMWP_Classes_Tools::getOption('hmwp_disable_copy_paste_message')) : '');
        $hmwp_disable_drag_drop_message = ((HMWP_Classes_Tools::getOption('hmwp_disable_drag_drop_message') <> '') ? str_replace("'", "`", HMWP_Classes_Tools::getOption('hmwp_disable_drag_drop_message')) : '');
        $hmwp_disable_source_message = ((HMWP_Classes_Tools::getOption('hmwp_disable_source_message') <> '') ? str_replace("'", "`", HMWP_Classes_Tools::getOption('hmwp_disable_source_message')) : '');
        ?>
        <script type="text/javascript">
            //<![CDATA[
            if (window.jQuery) {  (function ($) {  "use strict";
                $.hmwp_is_devtool = function () { const widthth= window.outerWidth - window.innerWidth > 160; const heightth = window.outerHeight - window.innerHeight > 160;  if(navigator.userAgent.match(/iPhone/i)) return false;  if ( !(heightth && widthth) && ((window.Firebug && window.Firebug.chrome && window.Firebug.chrome.isInitialized) || widthth || heightth) ) { $(document).trigger('hmwp_is_devtool'); return true; } return false; };
                var $hmtpcheck = setInterval(function(){$.hmwp_is_devtool();},500);
                $.disable_open_404 = function () {  $(document).find('html').remove(); };
                $.disable_show_error = function (message) { var $div = $('#disable_msg'); if (!$div.is(':visible')) { $div.html(message); $div.fadeIn('10'); setTimeout(function () {  $div.fadeOut('10');  }, 1000);  } };
                $.disable_event_listener = function (element, eventNames, message) { var events = eventNames.split(' '); for (var i = 0, iLen = events.length; i < iLen; i++) { element.addEventListener(events[i], function (e) {  e.preventDefault();  if (message !== '') $.disable_show_error(message); }); } };
                $.disable_return_false = function () {  return false;  };
                <?php  if(HMWP_Classes_Tools::getOption('hmwp_disable_inspect') && HMWP_Classes_Tools::getOption('hmwp_disable_inspect_blank')) { ?>if($.hmwpisdevtool){ clearInterval($hmtpcheck); $.disable_open_404(); } $(document).on("hmwp_is_devtool", function (event) {  clearInterval($hmtpcheck); $.disable_open_404(); }); $(document).on("contextmenu", function (event) {  event = (event || window.event);  if (event.keyCode === 123) {  <?php  if($hmwp_disable_inspect_message <> '') { ?>  $.disable_show_error('<?php echo esc_attr($hmwp_disable_inspect_message) ?>');  <?php } ?>  return false;  }});<?php } ?>
                <?php  if(HMWP_Classes_Tools::getOption('hmwp_disable_inspect') || HMWP_Classes_Tools::getOption('hmwp_disable_source')) { ?>$(document).on("keydown", function (event) { event = (event || window.event); <?php  if(HMWP_Classes_Tools::getOption('hmwp_disable_inspect')) { ?> if (event.keyCode === 123 ||  (event.ctrlKey && event.shiftKey && event.keyCode === 67) || ((event.ctrlKey || event.metaKey) && event.shiftKey && event.keyCode === 73) || (event.ctrlKey && event.shiftKey && event.keyCode === 75) || (event.ctrlKey && event.shiftKey && event.keyCode === 74) || (event.keyCode === 83 && (event.ctrlKey || event.metaKey)) || (event.keyCode === 67 && event.metaKey)) { <?php  if($hmwp_disable_inspect_message <> '') { ?> $.disable_show_error('<?php echo esc_attr($hmwp_disable_inspect_message) ?>');  <?php } ?>  return false;  } <?php } ?>
                <?php  if(HMWP_Classes_Tools::getOption('hmwp_disable_source')) { ?> if ((event.ctrlKey || event.metaKey) && event.keyCode === 85) {  <?php  if($hmwp_disable_source_message <> '') { ?> $.disable_show_error('<?php echo esc_attr($hmwp_disable_source_message) ?>'); <?php } ?>  return false;  } <?php } ?> });<?php } ?>
                <?php  if(HMWP_Classes_Tools::getOption('hmwp_disable_click')) { ?>$(document).on("contextmenu", function (event) { return false; }); $.disable_event_listener(document, 'contextmenu', '<?php echo esc_attr($hmwp_disable_click_message) ?>');<?php } ?>
                <?php  if(HMWP_Classes_Tools::getOption('hmwp_disable_copy_paste')) { ?>$.disable_event_listener(document, 'cut copy paste print', '<?php echo esc_attr($hmwp_disable_copy_paste_message) ?>');<?php } ?>
                <?php  if(HMWP_Classes_Tools::getOption('hmwp_disable_drag_drop')) { ?>$('img').attr('draggable', false); document.ondragstart = $.disable_return_false();  $.disable_event_listener(document, 'drag drop', '<?php echo esc_attr($hmwp_disable_drag_drop_message) ?>'); <?php } ?>
            })(window.jQuery); }
            //]]>
        </script>
        <?php  if(HMWP_Classes_Tools::getOption('hmwp_disable_copy_paste')) { ?><style>body * :not(input):not(textarea){user-select:none !important; -webkit-touch-callout: none !important;  -webkit-user-select: none !important; -moz-user-select:none !important; -khtml-user-select:none !important; -ms-user-select: none !important;}</style><?php } ?>
        <style>#disable_msg{display:none;min-width:250px;margin-left:-125px;background-color:#333;color:#fff;text-align:center;border-radius:2px;padding:16px;position:fixed;z-index:999;left:50%;bottom:30px;font-size:17px}}</style>
        <div id="disable_msg"></div>
        <?php
    }

}
