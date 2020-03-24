<?php
$do_check = false;
//Set the alert if security wasn't check
if (HMW_Classes_Tools::getOption('hmw_security_alert')) {
    if (!get_option('hmw_securitycheck')) {
        $do_check = true;
    } elseif ($securitycheck_time = get_option('hmw_securitycheck_time')) {
        if ((isset($securitycheck_time['timestamp']) && time() - $securitycheck_time['timestamp'] > (3600 * 24 * 7))) {
            $do_check = true;
        }
    } else {
        $do_check = true;
    }
}

?>
<div class="hmw_widget_content" style="position: relative;">
    <div style="font-size: 18px; text-align: center; font-weight: bold"><?php echo __('Security Level', _HMW_PLUGIN_NAME_) ?></div>
    <?php if (!$do_check) { ?>
        <div style="text-align: center">
            <?php if (((count($view->riskreport) * 100) / count($view->risktasks)) > 90) { ?>
                <a href="<?php echo HMW_Classes_Tools::getSettingsUrl('hmw_securitycheck') ?>"><img src="<?php echo _HMW_THEME_URL_ . 'img/speedometer_danger.png' ?>" style="max-width: 60%; margin: 10px auto;"/></a>
                <div style="font-size: 14px; font-style: italic; text-align: center; color: red;"><?php echo sprintf(__("Your website security %sis extremely weak%s. %sMany hacking doors are available.", _HMW_PLUGIN_NAME_), '<strong>', '</strong>', '<br />') ?></div>
            <?php } elseif (((count($view->riskreport) * 100) / count($view->risktasks)) > 50) { ?>
                <a href="<?php echo HMW_Classes_Tools::getSettingsUrl('hmw_securitycheck') ?>"><img src="<?php echo _HMW_THEME_URL_ . 'img/speedometer_low.png' ?>" style="max-width: 60%; margin: 10px auto;"/></a>
                <div style="font-size: 14px; font-style: italic; text-align: center; color: red;"><?php echo sprintf(__("Your website security %sis very weak%s. %sMany hacking doors are available.", _HMW_PLUGIN_NAME_), '<strong>', '</strong>', '<br />') ?></div>
            <?php } elseif (((count($view->riskreport) * 100) / count($view->risktasks)) > 0) { ?>
                <a href="<?php echo HMW_Classes_Tools::getSettingsUrl('hmw_securitycheck') ?>"><img src="<?php echo _HMW_THEME_URL_ . 'img/speedometer_medium.png' ?>" style="max-width: 60%; margin: 10px auto;"/></a>
                <div style="font-size: 14px; font-style: italic; text-align: center; color: orangered;"><?php echo sprintf(__("Your website security is still weak. %sSome of the main hacking doors are still available.", _HMW_PLUGIN_NAME_), '<br />') ?></div>
            <?php } else { ?>
                <a href="<?php echo HMW_Classes_Tools::getSettingsUrl('hmw_securitycheck') ?>"><img src="<?php echo _HMW_THEME_URL_ . 'img/speedometer_high.png' ?>" style="max-width: 60%; margin: 10px auto;"/></a>
                <div style="font-size: 14px; font-style: italic; text-align: center; color: green;"><?php echo sprintf(__("Your website security is strong. %sKeep checking the security every week.", _HMW_PLUGIN_NAME_), '<br />') ?></div>
            <?php } ?>
        </div>
        <?php if (((count($view->riskreport) * 100) / count($view->risktasks)) > 0) { ?>
            <div style="margin: 20px 0;">
                <div style="font-size: 18px; text-align: left;"><?php echo __('Urgent Security Actions Required', _HMW_PLUGIN_NAME_) ?>:</div>
                <ul style="margin: 10px 0 10px 20px; list-style: initial;">
                    <?php foreach ($view->riskreport as $function => $row) { ?>
                        <li style="margin: 10px 0; line-height: 20px"> <?php echo $row['solution'] ?></li>
                    <?php } ?>
                </ul>

                <div style="font-size: 12px; text-align: center; font-weight: bold;">
                    <a href="<?php echo HMW_Classes_Tools::getSettingsUrl('hmw_securitycheck') ?>" style="color: orangered"><?php echo __('Check All The Security Tasks', _HMW_PLUGIN_NAME_) ?></a>
                     | <a href="https://hidemywpghost.com/hide-my-wp-pricing/" target="_blank" style="color: green">
                            <?php _e('Upgrade Your Security', _HMW_PLUGIN_NAME_); ?>
                        </a>
                </div>

            </div>
        <?php } ?>
    <?php } ?>

    <button type="button" class="wp_button recheck_security"><?php _e('Recheck Security', _HMW_PLUGIN_NAME_); ?></button>

</div>

<style>
    .wp_loading {
        border: 16px solid #f3f3f3;
        border-top: 16px solid #b0794a;
        border-radius: 50%;
        width: 80px;
        height: 80px;
        animation: spin 2s linear infinite;
        margin: 20px auto 0 auto;
    }

    .wp_button {
        display: block;
        font-weight: 400;
        text-align: center;
        white-space: nowrap;
        vertical-align: middle;
        -webkit-user-select: none;
        -moz-user-select: none;
        -ms-user-select: none;
        user-select: none;
        border: 1px solid transparent;
        border-radius: .25rem;
        transition: color .15s ease-in-out, background-color .15s ease-in-out, border-color .15s ease-in-out, box-shadow .15s ease-in-out;
        padding: .5rem 1rem;
        font-size: 1.25rem;
        line-height: 1;
        color: #fff !important;
        background-color: #ddaa00;
        border-color: #ddaa00;
        margin: 7px auto;
    }

    @keyframes spin {
        0% {
            transform: rotate(0deg);
        }
        100% {
            transform: rotate(360deg);
        }
    }
</style>
<script>
    (function ($) {
        $.fn.hmw_widget_recheck = function () {
            var $this = this;
            var $div = $this.find('.inside');

            $div.find('.hmw_widget_content').html('<div style="font-size: 18px; text-align: center; font-weight: bold"><?php echo __("Checking Website Security ...", _HMW_PLUGIN_NAME_) ?></div><div class="wp_loading"></div>');
            $.post(
                hmwQuery.ajaxurl,
                {
                    action: 'hmw_widget_securitycheck',
                    hmw_nonce: hmwQuery.nonce
                }
            ).done(function (response) {
                if (typeof response.data !== 'undefined') {
                    $div.html(response.data);
                }
            }).error(function () {
                $div.html('');
            });
        };

        $(document).ready(function () {
            $('#hmw_dashboard_widget').find('.recheck_security').on('click', function () {
                $('#hmw_dashboard_widget').hmw_widget_recheck();
            });

            <?php if($do_check){ ?>
            $('#hmw_dashboard_widget').hmw_widget_recheck();
            <?php }?>
        });
    })(jQuery);

</script>