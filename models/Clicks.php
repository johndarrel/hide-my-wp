<?php
/**
 * Right Click and Keys disable Model
 *
 * @file  The Click file
 * @package HMWP/ClickModel
 */
defined('ABSPATH') || die('Cheatin\' uh?');

class HMWP_Models_Clicks
{

    public function __construct()
    {
        add_action('wp_footer', array($this, 'disableKeysAndClicks'), PHP_INT_MAX);
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
(function() {"use strict";
function __IsDevToolOpen() {const widthDiff = window.outerWidth - window.innerWidth > 160;const heightDiff = window.outerHeight - window.innerHeight > 160;if (navigator.userAgent.match(/iPhone/i)) return false;if (!(heightDiff && widthDiff) && ((window.Firebug && window.Firebug.chrome && window.Firebug.chrome.isInitialized) || widthDiff || heightDiff)) {document.dispatchEvent(new Event('hmwp_is_devtool'));return true;}return false;}
var __devToolCheckInterval = setInterval(__IsDevToolOpen, 500);
function __disableOpen404() {document.documentElement.remove();}
function __showError(message) {var div = document.getElementById('disable_msg');if (message !== '' && div && (!div.style.display || div.style.display == 'none')) {div.innerHTML = message;div.style.display = 'block';setTimeout(function() {div.style.display = 'none';}, 1000);}}
function __disableEventListener(element, eventNames, message) {var events = eventNames.split(' ');events.forEach(function(event) {element.addEventListener(event, function(e) {e.preventDefault();if (message !== '') __showError(message);});});}
function __returnFalse() {return false;}
<?php  if(HMWP_Classes_Tools::getOption('hmwp_disable_inspect') && HMWP_Classes_Tools::getOption('hmwp_disable_inspect_blank') && !wp_is_mobile()) { ?>document.addEventListener("hmwp_is_devtool", function() {clearInterval(__devToolCheckInterval);__disableOpen404();});<?php }?>
<?php  if(HMWP_Classes_Tools::getOption('hmwp_disable_inspect') || HMWP_Classes_Tools::getOption('hmwp_disable_source')) { ?>
<?php  if(HMWP_Classes_Tools::getOption('hmwp_disable_inspect')) { ?>document.addEventListener("contextmenu", function(event) {if (event.keyCode === 123) {event.preventDefault();__showError('<?php echo esc_attr($hmwp_disable_inspect_message) ?>');return false;}});<?php }?>
document.addEventListener("keydown", function(event) {
<?php  if(HMWP_Classes_Tools::getOption('hmwp_disable_inspect')) { ?>if (event.keyCode === 123 || (event.ctrlKey && event.shiftKey && event.keyCode === 67) || ((event.ctrlKey || event.metaKey) && event.shiftKey && event.keyCode === 73) || (event.ctrlKey && event.shiftKey && event.keyCode === 75) || (event.ctrlKey && event.shiftKey && event.keyCode === 74) || (event.keyCode === 83 && (event.ctrlKey || event.metaKey)) || (event.keyCode === 67 && event.metaKey)) {event.preventDefault();__showError('<?php echo esc_attr($hmwp_disable_inspect_message) ?>');return false;}<?php }?>
<?php  if(HMWP_Classes_Tools::getOption('hmwp_disable_source')) { ?>if ((event.ctrlKey || event.metaKey) && event.keyCode === 85) {event.preventDefault();__showError('<?php echo esc_attr($hmwp_disable_source_message) ?>');return false;}<?php }?>
});
document.addEventListener("contextmenu", function(event) {event.preventDefault();return false;});
<?php }?>
<?php  if(HMWP_Classes_Tools::getOption('hmwp_disable_click')) { ?>__disableEventListener(document, 'contextmenu', '<?php echo esc_attr($hmwp_disable_click_message) ?>');<?php }?>
<?php  if(HMWP_Classes_Tools::getOption('hmwp_disable_copy_paste')) { ?>__disableEventListener(document, 'cut copy paste print', '<?php echo esc_attr($hmwp_disable_copy_paste_message) ?>');<?php }?>
<?php  if(HMWP_Classes_Tools::getOption('hmwp_disable_drag_drop')) { ?>document.querySelectorAll('img').forEach(function(img) {img.setAttribute('draggable', false);});document.ondragstart = __returnFalse;__disableEventListener(document, 'drag drop', '<?php echo esc_attr($hmwp_disable_drag_drop_message) ?>');<?php }?>
})();
//]]>
</script>
<?php  if(HMWP_Classes_Tools::getOption('hmwp_disable_copy_paste')) { ?><style>body * :not(input):not(textarea){user-select:none !important; -webkit-touch-callout: none !important;  -webkit-user-select: none !important; -moz-user-select:none !important; -khtml-user-select:none !important; -ms-user-select: none !important;}</style><?php } ?>
<style>#disable_msg{display:none;min-width:250px;margin-left:-125px;background-color:#333;color:#fff;text-align:center;border-radius:2px;padding:16px;position:fixed;z-index:999;left:50%;bottom:30px;font-size:17px}}</style>
<div id="disable_msg"></div><?php
    }

}
