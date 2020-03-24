<?php
defined('ABSPATH') || die('Cheatin\' uh?');

class HMW_Controllers_Notice extends HMW_Classes_FrontController {

    protected $notice_spam = 0;

    public function __construct() {
        parent::__construct();
        add_action('admin_notices', array($this, 'hmw_admin_notices'));

        //check if notice is disabled
        $this->action();

    }

    /**
     * Show the notifications for review
     * @param $notices
     */
    public function hmw_admin_notices($notices) {
        global $wp;

        if (!isset($notices) || !is_array($notices)) {
            $notices = array();
        }
        $disable = '<form id="hmw_notice_form" method="POST">
                            ' . wp_nonce_field('hmw_disable_notice', 'hmw_nonce', true, false) . '
                            <input type="hidden" name="action" value="hmw_disable_notice" />
                            <input type="hidden" name="hmw_admin_notice" value="two_week_review">
                            <i type="submit" class="dashicons dashicons-no" style="cursor: pointer" title="'.__('Close notification', _HMW_PLUGIN_NAME_).'" onclick="jQuery(\'#hmw_notice_form\').submit();"></i>
                        </form>';
        if (is_string($disable) && $disable <> '') {
            $notices['two_week_review'] = array(
                'title' => __('Thank you for using Hide My WP?', _HMW_PLUGIN_NAME_),
                'msg' => sprintf(__("Add %sXML-RPC attack protection, SQL/Script firewall, reCaptcha login%s and more with Hide My WP Ghost premium features.", _HMW_PLUGIN_NAME_), '<strong style="color: red">', '</strong>', '<strong style="color: red">', '</strong>'),
                'link' => '<li><i class="dashicons dashicons-external" style="line-height: 25px;"></i><a href="https://hidemywpghost.com/hide-my-wp/" target="_blank" style="font-weight: normal">' . __("See all premium features", _HMW_PLUGIN_NAME_) . '</a></li>',

                'later_link' => $disable,
                'int' => 14
            );
        }
        HMW_Classes_ObjController::getClass('HMW_Classes_Error')->hookNotices();
        $this->showMessage($notices);

    }

    /**
     * Primary notice function that can be called from an outside function sending necessary variables
     *
     * @param $notices
     * @return bool|void
     */
    public function showMessage($notices) {
        foreach ($notices as $slug => $notice) {
            // Check for required fields
            if (!$this->required_fields($notice)) {
                // Call for spam protection
                if ($this->anti_notice_spam()) {
                    return;
                }

                // Get the current date then set start date to either passed value or current date value and add interval
                $current_date = current_time("n/j/Y");
                $start = (isset($notice['start']) ? $notice['start'] : $current_date);
                $interval = (isset($notice['int']) ? $notice['int'] : 0);
                $start = date("n/j/Y", strtotime("+$interval DAY", strtotime($start)));

                // This is the main notices storage option
                $notices_option = HMW_Classes_Tools::getOption('admin_notice');

                // Check if the message is already stored and if so just grab the key otherwise store the message and its associated date information
                if (!is_array($notices_option)) {
                    $notices_option = array();
                }
                if (!array_key_exists($slug, $notices_option)) {

                    $notices_option[$slug]['start'] = $start;
                    $notices_option[$slug]['int'] = $interval;
                    HMW_Classes_Tools::saveOptions('admin_notice', $notices_option);
                }


                // Sanity check to ensure we have accurate information
                // New date information will not overwrite old date information
                $admin_display_check = (isset($notices_option[$slug]['dismissed']) ? $notices_option[$slug]['dismissed'] : 0);
                $admin_display_start = (isset($notices_option[$slug]['start']) ? $notices_option[$slug]['start'] : $start);
                $admin_display_msg = (isset($notice['msg']) ? $notice['msg'] : '');
                $admin_display_link = (isset($notice['link']) ? $notice['link'] : '');
                $output_css = false;


                // Ensure the notice hasn't been hidden and that the current date is after the start date
                if ($admin_display_check == 0 && strtotime($admin_display_start) <= strtotime($current_date)) {

                    // Get remaining query string
                    $query_str = (isset($notice['later_link']) ? $notice['later_link'] : '<a href="' . esc_url(add_query_arg('hmw_admin_notice', $slug)) . '" class="dashicons dashicons-dismiss"></a>');
                    // Admin notice display output
                    echo '<div class="update-nag hmw-admin-notice">
                            <div style="float: right; margin: 10px;">'.$query_str.'</div>
                            <div class="' . 'hmw-notice-logo"></div>
                            <p class="hmw-notice-body">' . $admin_display_msg . '</p>
                            <ul class="hmw-notice-body hmw-blue">' . $admin_display_link . '</ul>
                            
                          </div>';

                    $this->notice_spam += 1;
                    $output_css = true;
                }

                if ($output_css) {
                    HMW_Classes_ObjController::getClass('HMW_Classes_DisplayController')->loadMedia('notice');
                }
            }
        }
    }

    /**
     * Called when an action is triggered
     *
     * @return void
     */
    public function action() {
        parent::action();


        switch (HMW_Classes_Tools::getValue('action')) {

            case 'hmw_disable_notice':
                $notices_option = HMW_Classes_Tools::getOption('admin_notice');
                if (is_array($notices_option)) {
                    $notices_option[HMW_Classes_Tools::getValue('hmw_admin_notice')]['dismissed'] = 1;
                    HMW_Classes_Tools::saveOptions('admin_notice', $notices_option);
                }
                break;
            case 'hmw_ignore_notice':
                $notices_option = HMW_Classes_Tools::getOption('admin_notice');
                $new_start = date("n/j/Y", strtotime("+90 DAY"));

                $notices_option[HMW_Classes_Tools::getValue('hmw_admin_notice')]['start'] = $new_start;
                $notices_option[HMW_Classes_Tools::getValue('hmw_admin_notice')]['dismissed'] = 0;
                HMW_Classes_Tools::saveOptions('admin_notice', $notices_option);
                break;
        }
    }


    /**
     * Spam protection check
     * @return bool
     */
    public function anti_notice_spam() {
        if ($this->notice_spam >= 1) {
            return true;
        }
        return false;
    }

    /**
     * Required fields check
     * @param $fields
     * @return bool
     */
    public function required_fields($fields) {
        if (!isset($fields['msg']) || (isset($fields['msg']) && empty($fields['msg']))) {
            return true;
        }
        if (!isset($fields['title']) || (isset($fields['title']) && empty($fields['title']))) {
            return true;
        }
        return false;
    }

}
