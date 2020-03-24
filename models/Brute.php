<?php
defined('ABSPATH') || die('Cheatin\' uh?');

class HMW_Models_Brute {
    protected $user_ip;
    public $response;

    /**
     * Retrives and sets the ip address the person logging in
     *
     * @return string
     */

    public function brute_get_ip() {
        if (isset($this->user_ip)) {
            return $this->user_ip;
        }

        $ip = $_SERVER['REMOTE_ADDR'];
        $trusted_header = HMW_Classes_Tools::getOption('trusted_ip_header');

        if (is_string($trusted_header) && $trusted_header <> '') {
            if (isset($_SERVER[$trusted_header])) {
                $ip = $_SERVER[$trusted_header];
            }
        }

        $ips = array_reverse(explode(', ', $ip));
        foreach ($ips as $ip) {
            $ip = $this->clean_ip($ip);

            // If the IP is in a private or reserved range, keep looking
            if ($ip == '127.0.0.1' || $ip == '::1' || $this->ip_is_private($ip)) {
                continue;
            } else {
                $this->user_ip = $ip;
                return $this->user_ip;
            }
        }

        $this->user_ip = $this->clean_ip($_SERVER['REMOTE_ADDR']);
        return $this->user_ip;
    }


    public function clean_ip($ip) {
        $ip = trim($ip);

        // Check for IPv4 IP cast as IPv6
        if (preg_match('/^::ffff:(\d+\.\d+\.\d+\.\d+)$/', $ip, $matches)) {
            $ip = $matches[1];
        }

        return $ip;
    }


    /**
     * Checks an IP to see if it is within a private range
     * @param string $ip
     * @return bool
     */
    public function ip_is_private($ip) {
        $pri_addrs = array(
            '10.0.0.0|10.255.255.255', // single class A network
            '172.16.0.0|172.31.255.255', // 16 contiguous class B network
            '192.168.0.0|192.168.255.255', // 256 contiguous class C network
            '169.254.0.0|169.254.255.255', // Link-local address also refered to as Automatic Private IP Addressing
            '127.0.0.0|127.255.255.255' // localhost
        );

        $long_ip = ip2long($ip);
        if ($long_ip != -1) {

            foreach ($pri_addrs AS $pri_addr) {
                list ($start, $end) = explode('|', $pri_addr);

                // IF IS PRIVATE
                if ($long_ip >= ip2long($start) && $long_ip <= ip2long($end)) {
                    return true;
                }
            }
        }

        return false;
    }

    public function get_privacy_key() {
        // Privacy key generation uses the NONCE_SALT + admin email-- admin email is
        // used to prevent identical privacy keys if NONCE_SALT is not customized
        return substr(md5(NONCE_SALT . get_site_option('admin_email')), 5, 10);
    }

    /**
     * Checks the status for a given IP. API results are cached as transients in the wp_options table
     *
     * @return array
     */
    public function brute_check_loginability() {

        $ip = $this->brute_get_ip();
        $headers = $this->brute_get_headers();
        $header_hash = md5(json_encode($headers));

        $transient_name = 'hmw_brute_' . $header_hash;
        $transient_value = $this->get_transient($transient_name);
        //Never block login from whitelisted IPs
        if ($this->check_whitelisted_ip($ip)) {
            return $transient_value;
        }

        //Check out our transients
        if (isset($transient_value) && $transient_value['status'] == 'ok') {
            return $transient_value;
        }

        if (isset($transient_value) && $transient_value['status'] == 'blocked') {
            //there is a current block-- prevent login
            $this->brute_kill_login();
        }


        //If we've reached this point, this means that the IP isn't cached.
        //Now we check to see if we should allow login
        $response = $this->brute_call('check_ip');

        if ($response['status'] == 'blocked') {
            $this->brute_kill_login();
        }

        return $response;
    }

    public function check_whitelisted_ip($ip) {
        //Never block login from whitelisted IPs
        $whitelist = HMW_Classes_Tools::getOption('whitelist_ip');
        $wl_items = json_decode($whitelist, true);

        if (isset($wl_items) && !empty($wl_items)) {
            foreach ($wl_items as $item) {
                $item = trim($item);
                if ($ip == $item) {
                    return true;
                }

                if (strpos($item, '*') === false) { //no match, no wildcard
                    continue;
                }

                $iplong = ip2long($ip);
                $ip_low = ip2long(str_replace('*', '0', $item));
                $ip_high = ip2long(str_replace('*', '255', $item));

                if ($iplong >= $ip_low && $iplong <= $ip_high) {//IP is within wildcard range
                    return true;
                }

            }
        }
        return false;
    }

    public function brute_get_local_host() {
        if (isset($this->local_host)) {
            return $this->local_host;
        }

        $uri = 'http://' . strtolower($_SERVER['HTTP_HOST']);

        if (is_multisite()) {
            $uri = network_home_url();
        }

        $uridata = parse_url($uri);

        $domain = $uridata['host'];

        //if we still don't have it, get the site_url
        if (!$domain) {
            $uri = get_site_url(1);
            $uridata = parse_url($uri);
            if (isset($uridata['host'])) {
                $domain = $uridata['host'];
            }
        }

        $this->local_host = $domain;

        return $this->local_host;
    }


    public function brute_get_blocked_attempts() {
        $blocked_count = get_site_option('bruteprotect_blocked_attempt_count');
        if (!$blocked_count) {
            $blocked_count = 0;
        }

        return $blocked_count;
    }


    /**
     * Finds out if this site is using http or https
     *
     * @return string
     */
    public function brute_get_protocol() {
        $protocol = (is_ssl()) ? "https://" : "http://";

        return $protocol;
    }

    /**
     * Get all IP headers so that we can process on our server...
     *
     * @return array
     */
    public function brute_get_headers() {
        $o = array();

        $ip_related_headers = array(
            'GD_PHP_HANDLER',
            'HTTP_AKAMAI_ORIGIN_HOP',
            'HTTP_CF_CONNECTING_IP',
            'HTTP_CLIENT_IP',
            'HTTP_FASTLY_CLIENT_IP',
            'HTTP_FORWARDED',
            'HTTP_FORWARDED_FOR',
            'HTTP_INCAP_CLIENT_IP',
            'HTTP_TRUE_CLIENT_IP',
            'HTTP_X_CLIENTIP',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_X_FORWARDED',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_IP_TRAIL',
            'HTTP_X_REAL_IP',
            'HTTP_X_VARNISH',
            'REMOTE_ADDR'

        );

        foreach ($ip_related_headers as $header) {
            if (isset($_SERVER[$header])) {
                $o[$header] = $_SERVER[$header];
            }
        }

        return $o;
    }

    /**
     * process the brute call
     *
     * @param string $action 'check_ip', 'check_key', or 'failed_attempt'
     * @param array $info Any custom data to post to the api
     *
     * @return array
     */
    public function brute_call($action = 'check_ip', $info = array()) {
        $headers = $this->brute_get_headers();
        $header_hash = md5(json_encode($headers));
        $transient_name = 'hmw_brute_' . $header_hash;

        $response = $this->get_transient($transient_name);
        $attempts = (isset($response['attempts']) ? (int)$response['attempts'] : 0);

        if ($action == 'failed_attempt') {
            if ($this->check_whitelisted_ip($this->brute_get_ip())) {
                $response['status'] = 'ok';
                return $response;
            }

            $attempts = (int)$attempts + 1;

            if ($attempts > HMW_Classes_Tools::getOption('brute_max_attempts')) {

                $info['ip'] = $this->brute_get_ip();
                $info['host'] = $this->brute_get_local_host();
                $info['protocol'] = $this->brute_get_protocol();
                $info['headers'] = json_encode($this->brute_get_headers());

                $response = array_merge($response, $info);
                $response['attempts'] = $attempts;
                $response['status'] = 'blocked';

                wp_redirect(site_url());
            } else {
                $response['attempts'] = $attempts;
                $response['status'] = 'ok';
            }
            $this->set_transient($transient_name, $response, (int)HMW_Classes_Tools::getOption('brute_max_timeout'));
        } elseif ($action == 'check_ip') {
            $response['status'] = (isset($response['status']) ? $response['status'] : 'ok');

            //Always block a banned IP
            if ($this->check_banned_ip($this->brute_get_ip())) {
                $response['status'] = 'blocked';

            }

        } elseif ($action == 'clear_ip') {
            $this->delete_transient($transient_name);
        }

        return $response;
    }

    public function set_transient($transient, $value, $expiration) {
        if (is_multisite() && !is_main_site()) {
            switch_to_blog($this->get_main_blog_id());
            $return = set_transient($transient, $value, $expiration);
            restore_current_blog();
            return $return;
        }
        return set_transient($transient, $value, $expiration);
    }

    public function delete_transient($transient) {
        if (is_multisite() && !is_main_site()) {
            switch_to_blog($this->get_main_blog_id());
            $return = delete_transient($transient);
            restore_current_blog();
            return $return;
        }
        return delete_transient($transient);
    }

    public function get_transient($transient) {
        if (is_multisite() && !is_main_site()) {
            switch_to_blog($this->get_main_blog_id());
            $return = get_transient($transient);
            restore_current_blog();
            return $return;
        }
        return get_transient($transient);
    }

    /**
     * If we're in a multisite network, return the blog ID of the primary blog
     *
     * @return int
     */
    public function get_main_blog_id() {
        if (defined('BLOG_ID_CURRENT_SITE')) {
            return BLOG_ID_CURRENT_SITE;
        }

        return 1;
    }


    public function get_blocked_ips() {
        global $wpdb;
        $ips = array();
        $pattern = '_transient_timeout_hmw_brute_';
        //check 20 keyword at one time
        $sql = "SELECT `option_name`  FROM `" . $wpdb->options . "`  WHERE (`option_name` like '$pattern%')  ORDER BY `option_id` DESC";

        if ($rows = $wpdb->get_results($sql)) {
            foreach ($rows as $row) {
                if (!$transient_value = $this->get_transient(str_replace($pattern, 'hmw_brute_', $row->option_name))) {
                    $this->delete_transient(str_replace($pattern, '', $row->option_name));
                }
                if ($transient_value['status'] == 'blocked') {
                    $ips[str_replace($pattern, 'hmw_brute_', $row->option_name)] = $transient_value;
                }
            }
        }


        return $ips;
    }

    public function check_banned_ip($ip) {
        //Never block login from whitelisted IPs
        $banlist = HMW_Classes_Tools::getOption('banlist_ip');
        $wl_items = @json_decode($banlist, true);

        if (isset($wl_items) && !empty($wl_items)) {
            foreach ($wl_items as $item) {
                $item = trim($item);
                if ($ip == $item) {
                    return true;
                }

                if (strpos($item, '*') === false) { //no match, no wildcard
                    continue;
                }

                $iplong = ip2long($ip);
                $ip_low = ip2long(str_replace('*', '0', $item));
                $ip_high = ip2long(str_replace('*', '255', $item));

                if ($iplong >= $ip_low && $iplong <= $ip_high) {//IP is within wildcard range
                    return true;
                }

            }
        }
        return false;
    }

    public function delete_ip($transient) {
        $this->delete_transient($transient);
    }


    /**
     * Verifies that a user answered the math problem correctly while logging in.
     *
     * @param mixed $user
     * @param mixed $response
     * @return mixed $user Returns the user if the math is correct
     */
    public function brute_math_authenticate($user, $response) {

        if (isset($_POST['brute_num']) && isset($_POST['brute_ck'])) {
            $salt = HMW_Classes_Tools::getOption('hmw_disable') . get_site_option('admin_email');
            $ans = (int)$_POST['brute_num'];
            $salted_ans = sha1($salt . $ans);
            $correct_ans = $_POST['brute_ck'];

            if (!$correct_ans && !isset($_POST['brute_ck'])) {
            } elseif ($salted_ans != $correct_ans) {
                $user = new WP_Error('authentication_failed',
                    sprintf(__('%sYou failed to correctly answer the math problem.%s Please try again', _HMW_PLUGIN_NAME_), '<strong>', '</strong>')
                );
            } elseif (is_wp_error($user)) {
                if (!isset($response['attempts'])) {
                    $response['attempts'] = 0;
                }
                $left = max(((int)HMW_Classes_Tools::getOption('brute_max_attempts') - (int)$response['attempts']), 0);
                $user = new WP_Error('authentication_failed',
                    sprintf(__('%sERROR:%s Email or Password is incorrect. %s %d attempts left before lockout', _HMW_PLUGIN_NAME_), '<strong>', '</strong>', '<br />', $left)
                );
            }
        }

        return $user;
    }

    /**
     * Requires a user to solve a simple equation. Added to any WordPress login form.
     *
     * @return void outputs html
     */
    public function brute_math_form() {
        if (!HMW_Classes_Tools::getOption('brute_use_math')) {
            return;
        }
        $salt = HMW_Classes_Tools::getOption('hmw_disable') . get_site_option('admin_email');
        $num1 = rand(0, 10);
        $num2 = rand(1, 10);
        $sum = $num1 + $num2;
        $ans = sha1($salt . $sum);
        ?>
        <div style="margin: 5px 0 20px;">
            <strong><?php echo __('Prove your humanity: ', _HMW_PLUGIN_NAME_) ?></strong>
            <?php echo $num1 ?> &nbsp; + &nbsp; <?php echo $num2 ?> &nbsp; = &nbsp;
            <input type="input" name="brute_num" value="" size="2"/>
            <input type="hidden" name="brute_ck" value="<?php echo $ans; ?>" id="brute_ck"/>
        </div>
        <?php
    }


    public function brute_kill_login() {
        do_action('hmw_kill_login', $this->brute_get_ip());
        wp_ob_end_flush_all();
        wp_die(HMW_Classes_Tools::getOption('hmw_brute_message'),
            __('Login Blocked by Hide My WordPress', _HMW_PLUGIN_NAME_),
            array('response' => 403)
        );
    }

}
