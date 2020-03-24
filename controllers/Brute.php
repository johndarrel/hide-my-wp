<?php
defined('ABSPATH') || die('Cheatin\' uh?');

class HMW_Controllers_Brute extends HMW_Classes_FrontController {

    public function __construct() {
        parent::__construct();

        add_action('admin_init', array($this, 'hmw_update_trusted_headers'), 99);
        add_filter('authenticate', array($this, 'hmw_check_preauth'), 99, 1);

        if (HMW_Classes_Tools::getOption('brute_use_math')) {
            add_action('wp_login_failed', array($this, 'hmw_failed_attempt'), 99);
            add_action('login_form', array($this->model, 'brute_math_form'), 99);
            add_filter('woocommerce_login_form', array($this->model, 'brute_math_form'), 99);
        }
    }

    public function hookFrontinit() {
        if (!is_user_logged_in()) {
            //Load the Multilanguage
            HMW_Classes_Tools::loadMultilanguage();

            $this->bruteBlockCheck();
        }
    }

    public function bruteBlockCheck() {
        $response = $this->model->brute_call('check_ip');
        if ($response['status'] == 'blocked') {
            if (!$this->model->check_whitelisted_ip($this->model->brute_get_ip())) {
                wp_ob_end_flush_all();
                wp_die(HMW_Classes_Tools::getOption('hmw_brute_message'),
                    __('IP Blocked by Hide My WordPress Brute Force Protection', _HMW_PLUGIN_NAME_),
                    array('response' => 403)
                );
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

            case 'hmw_brutesettings':
                HMW_Classes_Tools::saveOptions('hmw_bruteforce', HMW_Classes_Tools::getValue('hmw_bruteforce'));

                //whitelist_ip
                $whitelist = HMW_Classes_Tools::getValue('whitelist_ip', '', true);
                $ips = explode(PHP_EOL, $whitelist);
                foreach ($ips as &$ip) {
                    $ip = $this->model->clean_ip($ip);

                    // If the IP is in a private or reserved range, keep looking
                    if ($ip == '127.0.0.1' || $ip == '::1') {
                        HMW_Classes_Error::setError(__("Add only real IPs. No local ips needed.", _HMW_PLUGIN_NAME_));
                    }
                }
                if (!empty($ips)) {
                    $ips = array_unique($ips);
                    HMW_Classes_Tools::saveOptions('whitelist_ip', json_encode($ips));
                }

                //banlist_ip
                $banlist = HMW_Classes_Tools::getValue('banlist_ip', '', true);
                $ips = explode(PHP_EOL, $banlist);
                foreach ($ips as &$ip) {
                    $ip = $this->model->clean_ip($ip);

                    // If the IP is in a private or reserved range, keep looking
                    if ($ip == '127.0.0.1' || $ip == '::1') {
                        HMW_Classes_Error::setError(__("Add only real IPs. No local ips allowed.", _HMW_PLUGIN_NAME_));
                    }
                }
                if (!empty($ips)) {
                    $ips = array_unique($ips);
                    HMW_Classes_Tools::saveOptions('banlist_ip', json_encode($ips));
                }

                //Brute force math option
                HMW_Classes_Tools::saveOptions('brute_use_math', HMW_Classes_Tools::getValue('brute_use_math', 0));
                if (HMW_Classes_Tools::getValue('brute_use_math', 0)) {
                    $attempts = HMW_Classes_Tools::getValue('brute_max_attempts');
                    if ((int)$attempts <= 0) {
                        $attempts = 3;
                        HMW_Classes_Error::setError(__('You need to set a positive number of attempts ', _HMW_PLUGIN_NAME_));

                    }
                    HMW_Classes_Tools::saveOptions('brute_max_attempts', (int)$attempts);

                    $timeout = HMW_Classes_Tools::getValue('brute_max_timeout');
                    if ((int)$timeout <= 0) {
                        $timeout = 3600;
                        HMW_Classes_Error::setError(__('You need to set a positive waiting time', _HMW_PLUGIN_NAME_));

                    }
                    HMW_Classes_Tools::saveOptions('hmw_brute_message', HMW_Classes_Tools::getValue('hmw_brute_message', '', true));
                    HMW_Classes_Tools::saveOptions('brute_max_timeout', $timeout);
                }

                HMW_Classes_Error::setError(__('Saved'), 'success');

                HMW_Classes_Tools::emptyCache();
                break;
            case 'hmw_deleteip':
                $transient = HMW_Classes_Tools::getValue('transient', null);
                if (isset($transient)) {
                    $this->model->delete_ip($transient);
                }

                break;
            case 'hmw_deleteallips':
                $this->clearBlockedIPs();
                break;

            case 'hmw_blockedips':
                HMW_Classes_Tools::setHeader('json');
                $data = $this->getBlockedIps();
                echo json_encode(array('data' => $data));
                exit();
        }
    }

    public function getBlockedIps() {
        $data = '<table class="table table-striped" >';
        $ips = $this->model->get_blocked_ips();
        $data .= "<tr>
                    <th>" . __('Cnt', _HMW_PLUGIN_NAME_) . "</th>
                    <th>" . __('IP', _HMW_PLUGIN_NAME_) . "</th>
                    <th>" . __('Fail Attempts', _HMW_PLUGIN_NAME_) . "</th>
                    <th>" . __('Hostname', _HMW_PLUGIN_NAME_) . "</th>
                    <th>" . __('Options', _HMW_PLUGIN_NAME_) . "</th>
                 </tr>";
        if (!empty($ips)) {
            $cnt = 1;
            foreach ($ips as $transient => $ip) {
                $data .= "<tr>
                        <td>" . $cnt . "</td>
                        <td>{$ip['ip']}</td>
                        <td>{$ip['attempts']}</td>
                        <td>{$ip['host']}</td>
                        <td class=\"col-md-2 \"> <form method=\"POST\">
                                " . wp_nonce_field('hmw_deleteip', 'hmw_nonce', true, false) . "
                                <input type=\"hidden\" name=\"action\" value=\"hmw_deleteip\" />
                                <input type=\"hidden\" name=\"transient\" value=\"" . $transient . "\" />
                                <input type=\"submit\" class=\"btn rounded-0 btn-default save no-p-v\" value=\"Unlock\" />
                            </form>
                        </td>
                     </tr>";
                $cnt++;
            }
        } else {
            $data .= "<tr>
                                <td colspan='5'>" . _('No blacklisted ips') . "</td>
                             </tr>";
        }
        $data .= '</table>';

        return $data;
    }


    /**
     * Checks for loginability BEFORE authentication so that bots don't get to go around the log in form.
     *
     * If we are using our math fallback, authenticate via math-fallback.php
     *
     * @param string $user Passed via WordPress action. Not used.
     *
     * @return bool True, if WP_Error. False, if not WP_Error., $user Containing the auth results
     */
    function hmw_check_preauth($user = '') {
        if (is_wp_error($user)) {
            if (method_exists($user, 'get_error_codes')) {
                $errors = $user->get_error_codes();

                if (!empty($errors)) {
                    foreach ($errors as $error) {
                        if ($error == 'empty_username' || $error == 'empty_password') {
                            return $user;
                        }
                    }
                }
            }
        }

        $response = $this->model->brute_check_loginability();

        if (!HMW_Classes_Tools::getOption('brute_use_math')) {
            if (is_wp_error($user)) {
                if (!isset($response['attempts'])) {
                    $response['attempts'] = 0;
                }
                $left = max(((int)HMW_Classes_Tools::getOption('brute_max_attempts') - (int)$response['attempts']), 0);
                $user = new WP_Error('authentication_failed',
                    sprintf(__('<strong>ERROR:</strong> Email or Password is incorrect. <br /> %d attempts left before lockout', _HMW_PLUGIN_NAME_), $left)
                );
            }
        } elseif (HMW_Classes_Tools::getOption('brute_use_math')) {
            $user = $this->model->brute_math_authenticate($user, $response);
        }

        if (!is_wp_error($user)) {
            $this->model->brute_call('clear_ip');
        }

        return $user;
    }

    /**
     * Called via WP action wp_login_failed to log failed attempt in db
     *
     * @return void
     */
    function hmw_failed_attempt() {
        $this->model->brute_call('failed_attempt');
    }

    public function hmw_update_trusted_headers() {
        $updated_recently = $this->model->get_transient('brute_headers_updated_recently');

        // check that current user is admin so we prevent a lower level user from adding
        // a trusted header, allowing them to brute force an admin account
        if (!$updated_recently && current_user_can('update_plugins')) {

            $this->model->set_transient('brute_headers_updated_recently', 1, DAY_IN_SECONDS);

            $headers = $this->model->brute_get_headers();
            $trusted_header = 'REMOTE_ADDR';

            if (count($headers) == 1) {
                $trusted_header = key($headers);
            } elseif (count($headers) > 1) {
                foreach ($headers as $header => $ips) {
                    //explode string into array
                    $ips = explode(', ', $ips);

                    $ip_list_has_nonprivate_ip = false;
                    foreach ($ips as $ip) {
                        //clean the ips
                        $ip = $this->model->clean_ip($ip);

                        // If the IP is in a private or reserved range, return REMOTE_ADDR to help prevent spoofing
                        if ($ip == '127.0.0.1' || $ip == '::1' || $this->model->ip_is_private($ip)) {
                            continue;
                        } else {
                            $ip_list_has_nonprivate_ip = true;
                            break;
                        }
                    }

                    if (!$ip_list_has_nonprivate_ip) {
                        continue;
                    }

                    // IP is not local, we'll trust this header
                    $trusted_header = $header;
                    break;
                }
            }
            HMW_Classes_Tools::saveOptions('trusted_ip_header', $trusted_header);
        }
    }

    public function clearBlockedIPs() {
        $ips = $this->model->get_blocked_ips();
        if (!empty($ips)) {
            foreach ($ips as $transient => $ip) {
                $this->model->delete_ip($transient);
            }
        }
    }


}
