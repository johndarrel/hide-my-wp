<?php
/**
 * Brute Force Protection Model
 * Called from Brute Force Class
 *
 * @file  The Brute Force Model file
 * @package HMWP/BruteForce
 * @since 4.2.0
 */

defined('ABSPATH') || die('Cheatin\' uh?');

class HMWP_Models_Brute
{
    /**
     * The user IP address
     * @var string
     */
    protected $user_ip;
    public $response;

    /**
     * Retrives and sets the ip address the person logging in
     *
     * @return string
     */
    public function brute_get_ip()
    {
        if (isset($this->user_ip)) {
            return $this->user_ip;
        }

        if(!isset($_SERVER['REMOTE_ADDR'])) {
            return '127.0.0.1';
        }

        $ip = $_SERVER['REMOTE_ADDR'];
        $trusted_header = HMWP_Classes_Tools::getOption('trusted_ip_header');

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


    /**
     * Clean the IP address if altered
     * @param $ip
     * @return mixed|string
     */
    public function clean_ip($ip)
    {
        $ip = trim($ip);

        // Check for IPv4 IP cast as IPv6
        if (preg_match('/^::ffff:(\d+\.\d+\.\d+\.\d+)$/', $ip, $matches)) {
            $ip = $matches[1];
        }

        return $ip;
    }


    /**
     * Checks an IP to see if it is within a private range
     *
     * @param  string $ip
     * @return bool
     */
    public function ip_is_private($ip)
    {
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

    /**
     * Generate a pivacy key
     * @return string
     */
    public function get_privacy_key()
    {
        // Privacy key generation uses the NONCE_SALT + admin email-- admin email is
        // used to prevent identical privacy keys if NONCE_SALT is not customized
        return substr(md5(NONCE_SALT . get_site_option('admin_email')), 5, 10);
    }

    /**
     * Checks the status for a given IP. API results are cached as transients in the wp_options table
     *
     * @return array|mixed
     * @throws Exception
     */
    public function brute_check_loginability()
    {

        $ip = $this->brute_get_ip();
	    $transient_name = 'hmwp_brute_' . md5($ip);
        $transient_value = $this->get_transient($transient_name);

        //Never block login from whitelisted IPs
        if ($this->check_whitelisted_ip($ip)) {
            $transient_value['status'] = 'whitelist';
            return $transient_value;
        }

        //Check out our transients
        if (isset($transient_value['status']) && $transient_value['status'] == 'ok') {
            return $transient_value;
        }

        if (isset($transient_value['status']) && $transient_value['status'] == 'blocked') {
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

    /**
     * Check if the current IP address is whitelisted by the user
     *
     * @param string $ip
     * @return bool
     */
    public function check_whitelisted_ip($ip)
    {
	    if(HMWP_Classes_Tools::isWhitelistedIP($ip)){
		    return true;
	    }

	    return false;
    }

    /**
     * Get the current local host
     *
     * @return mixed|string
     */
    public function brute_get_local_host()
    {
        if (isset($this->local_host)) {
            return $this->local_host;
        }

        $uri = 'http://' . strtolower($_SERVER['HTTP_HOST']);

        if (HMWP_Classes_Tools::isMultisites()) {
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

    /**
     * Count the number of fail attempts
     *
     * @return false|int|mixed
     */
    public function brute_get_blocked_attempts()
    {
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
    public function brute_get_protocol()
    {
        return (is_ssl()) ? "https://" : "http://";
    }

    /**
     * Get all IP headers so that we can process on our server...
     *
     * @return array
     */
    public function brute_get_headers()
    {
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
     * @param  string $action 'check_ip', 'check_key', or 'failed_attempt'
     * @param  array  $info   Any custom data to post to the api
     * @return array|mixed
     * @throws Exception
     */
    public function brute_call($action = 'check_ip', $info = array())
    {
	    $ip = $this->brute_get_ip();
	    $transient_name = 'hmwp_brute_' . md5($ip);
	    if(!$response = $this->get_transient($transient_name)){
		    $response = array();
	    }

        $attempts = (isset($response['attempts']) ? (int)$response['attempts'] : 0);

        if ($action == 'failed_attempt') {
            if ($this->check_whitelisted_ip($ip)) {
                $response['status'] = 'ok';
                return $response;
            }

            $attempts = (int)$attempts + 1;

            if ($attempts >= HMWP_Classes_Tools::getOption('brute_max_attempts')) {

                $info['ip'] = $ip;
                $info['host'] = $this->brute_get_local_host();
                $info['protocol'] = $this->brute_get_protocol();
                $info['headers'] = json_encode($this->brute_get_headers());

                $response = array_merge($response, $info);
                $response['attempts'] = $attempts;
                $response['status'] = 'blocked';

                $this->set_transient($transient_name, $response, (int)HMWP_Classes_Tools::getOption('brute_max_timeout'));

                //Log the block IP on the server
                HMWP_Classes_ObjController::getClass('HMWP_Models_Log')->hmwp_log_actions('block_ip', array('ip' => $ip));

                wp_redirect(home_url());
                exit();
            } else {
                $response['attempts'] = $attempts;
                $response['status'] = 'ok';

                $this->set_transient($transient_name, $response, (int)HMWP_Classes_Tools::getOption('brute_max_timeout'));
            }


        } elseif ($action == 'check_ip') {
            $response['status'] = (isset($response['status']) ? $response['status'] : 'ok');

            //Always block a banned IP
            if ($this->check_banned_ip($ip)) {
                $response['status'] = 'blocked';
            }

        } elseif ($action == 'clear_ip') {
            $this->delete_transient($transient_name);
        }

        return $response;
    }

    /**
     * Save the transient with the blocked IP in database
     *
     * @param $transient
     * @param $value
     * @param $expiration
     * @return bool
     */
    public function set_transient($transient, $value, $expiration)
    {
        if (HMWP_Classes_Tools::isMultisites() && !is_main_site()) {
            switch_to_blog($this->get_main_blog_id());
            $return = set_transient($transient, $value, $expiration);
            restore_current_blog();
            return $return;
        }
        return set_transient($transient, $value, $expiration);
    }

    /**
     * Delete the transient from database
     *
     * @param $transient
     * @return bool
     */
    public function delete_transient($transient)
    {
        if (HMWP_Classes_Tools::isMultisites() && !is_main_site()) {
            switch_to_blog($this->get_main_blog_id());
            $return = delete_transient($transient);
            restore_current_blog();
            return $return;
        }
        return delete_transient($transient);
    }

    /**
     * Get the saved transient from database
     *
     * @param $transient
     * @return mixed
     */
    public function get_transient($transient)
    {
        if (HMWP_Classes_Tools::isMultisites() && !is_main_site()) {
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
    public function get_main_blog_id()
    {
        if (defined('BLOG_ID_CURRENT_SITE')) {
            return BLOG_ID_CURRENT_SITE;
        }

        return 1;
    }


    /**
     * Get all blocked IPs
     *
     * @return array
     */
    public function get_blocked_ips()
    {
        global $wpdb;
        $ips = array();
        $pattern = '_transient_timeout_hmwp_brute_';

        //check 20 keyword at one time
        $sql = $wpdb->prepare("SELECT `option_name` FROM `{$wpdb->options}` WHERE (`option_name` LIKE %s) ORDER BY `option_id` DESC", $pattern . '%');

        if ($rows = $wpdb->get_results($sql)) {
            foreach ($rows as $row) {
                if (!$transient_value = $this->get_transient(str_replace($pattern, 'hmwp_brute_', $row->option_name))) {
                    $this->delete_transient(str_replace($pattern, '', $row->option_name));
                }elseif (isset($transient_value['status']) && $transient_value['status'] == 'blocked') {
                    $ips[str_replace($pattern, 'hmwp_brute_', $row->option_name)] = $transient_value;
                }
            }
        }


        return $ips;
    }

    /**
     * Check if the IP address is already banned by the user
     *
     * @param $ip
     * @return bool
     */
    public function check_banned_ip($ip)
    {
        //Never block login from whitelisted IPs
        $banlist = HMWP_Classes_Tools::getOption('banlist_ip');

        if($banlist <> '' && is_string($banlist)) {
            $bl_items = @json_decode($banlist, true);

	        //add the hook for users to add IPs in the blacklist
	        $bl_items = apply_filters('hmwp_blacklisted_ips', $bl_items);

	        if (!empty($bl_items)) {
                foreach ($bl_items as $item) {
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
        }
        return false;
    }

    /**
     * Delete the IP address from database
     *
     * @param $transient
     * @return void
     */
    public function delete_ip($transient)
    {
        $this->delete_transient($transient);
    }


    /**
     * Verifies that a user answered the math problem correctly while logging in.
     *
     * @param  mixed $user
     * @param  mixed $response
     * @return mixed $user Returns the user if the math is correct
     */
    public function brute_math_authenticate($user, $response)
    {
        $salt = HMWP_Classes_Tools::getOption('hmwp_disable') . get_site_option('admin_email');
        $ans = (int)HMWP_Classes_Tools::getValue('brute_num', 0);
        $salted_ans = sha1($salt . $ans);
        $correct_ans = HMWP_Classes_Tools::getValue('brute_ck');

        if ($correct_ans === false || $salted_ans != $correct_ans) {
            $user = new WP_Error(
                'authentication_failed',
                sprintf(esc_html__('%sYou failed to correctly answer the math problem.%s Please try again', 'hide-my-wp'), '<strong>', '</strong>')
            );
        }

        return $user;
    }

    /**
     * Requires a user to solve a simple equation. Added to any WordPress login form.
     *
     * @return void outputs html
     */
    public function brute_math_form()
    {
        if (!HMWP_Classes_Tools::getOption('brute_use_math')) {
            return;
        }
        $salt = HMWP_Classes_Tools::getOption('hmwp_disable') . get_site_option('admin_email');
        $num1 = rand(0, 10);
        $num2 = rand(1, 10);
        $sum = $num1 + $num2;
        $ans = sha1($salt . $sum);
        ?>
        <div style="margin: 5px 0 20px;">
            <strong><?php echo esc_html__('Prove your humanity:', 'hide-my-wp') ?> </strong>
            <?php echo esc_attr($num1) ?> &nbsp; + &nbsp; <?php echo esc_attr($num2) ?> &nbsp; = &nbsp;
            <input type="input" name="brute_num" value="" size="2"/>
            <input type="hidden" name="brute_ck" value="<?php echo esc_attr($ans); ?>" id="brute_ck"/>
        </div>
        <?php
    }

    /************************************************************************************/
    /**
     * Verifies the Google Captcha while logging in.
     *
     * @param  mixed $user
     * @param  mixed $response
     * @return mixed $user Returns the user if the math is correct
     * @throws WP_Error message if the math is wrong
     */
    public function brute_catpcha_authenticate($user, $response)
    {
        $error_message = false;

        if(HMWP_Classes_Tools::getOption('brute_use_captcha')) {
            $error_message = $this->brute_catpcha_call();
        }elseif(HMWP_Classes_Tools::getOption('brute_use_captcha_v3')) {
            $error_message = $this->brute_catpcha_v3_call();
        }

        if ($error_message) {
            $user = new WP_Error('authentication_failed', $error_message);
        }

        return $user;
    }


    /**
     * Call the reCaptcha V2 from Google
     */
    public function brute_catpcha_call()
    {
        $error_message = false;
        $error_codes = array(
            'missing-input-secret' => esc_html__('The secret parameter is missing.', 'hide-my-wp'),
            'invalid-input-secret' => esc_html__('The secret parameter is invalid or malformed.', 'hide-my-wp'),
            'missing-input-response' => esc_html__('Empty ReCaptcha. Please complete reCaptcha.', 'hide-my-wp'),
            'timeout-or-duplicate' => esc_html__('The response parameter is invalid or malformed.', 'hide-my-wp'),
            'invalid-input-response' => esc_html__('The response parameter is invalid or malformed.', 'hide-my-wp')
        );

        $captcha = HMWP_Classes_Tools::getValue('g-recaptcha-response', false);
        $secret = HMWP_Classes_Tools::getOption('brute_captcha_secret_key');

        if ($secret <> '') {
            $response = json_decode(HMWP_Classes_Tools::hmwp_remote_get("https://www.google.com/recaptcha/api/siteverify?secret=$secret&response=" . $captcha . "&remoteip=" . $_SERVER['REMOTE_ADDR']), true);

            if (isset($response['success']) && !$response['success']) {
                //If captcha errors, let the user login and fix the error
                if (isset($response['error-codes']) && !empty($response['error-codes'])) {
                    foreach ($response['error-codes'] as $error_code) {
                        if(isset($error_codes[$error_code])){
	                        $error_message = $error_codes[$error_code];
                        }
                    }
                }

                if (!$error_message) {
                    $error_message = sprintf(esc_html__('%sIncorrect ReCaptcha%s. Please try again', 'hide-my-wp'), '<strong>', '</strong>');
                }
            }
        }

        return $error_message;
    }


    /**
     * reCAPTCHA head and login form
     */
    public function brute_recaptcha_head()
    {
        ?>
        <script src='https://www.google.com/recaptcha/api.js?hl=<?php echo(HMWP_Classes_Tools::getOption('brute_captcha_language') <> '' ? HMWP_Classes_Tools::getOption('brute_captcha_language') : get_locale()) ?>' async defer></script>
        <style>#login{min-width: 354px;}</style>
        <?php
    }

    /**
     * reCAPTCHA head and login form
     */
    public function brute_recaptcha_form()
    {
        if (HMWP_Classes_Tools::getOption('brute_captcha_site_key') <> '' && HMWP_Classes_Tools::getOption('brute_captcha_secret_key') <> '') {
            ?>
            <div class="g-recaptcha" data-sitekey="<?php echo HMWP_Classes_Tools::getOption('brute_captcha_site_key') ?>" data-theme="<?php echo HMWP_Classes_Tools::getOption('brute_captcha_theme') ?>" style="margin: 12px 0 24px 0;"></div>
            <?php
        }
    }

    /**
     * Call the reCaptcha V3 from Google
     */
    public function brute_catpcha_v3_call()
    {

        $error_message = false;

        if(!HMWP_Classes_Tools::getOption('brute_use_captcha_v3')) {
            return false;
        }

        $error_codes = array(
            'missing-input-secret' => esc_html__('The secret parameter is missing.', 'hide-my-wp'),
            'invalid-input-secret' => esc_html__('The secret parameter is invalid or malformed.', 'hide-my-wp'),
            'missing-input-response' => esc_html__('Empty ReCaptcha. Please complete reCaptcha.', 'hide-my-wp'),
            'invalid-input-response' => esc_html__('The response parameter is invalid or malformed.', 'hide-my-wp'),
            'timeout-or-duplicate' => esc_html__('The response parameter is invalid or malformed.', 'hide-my-wp'),
            'bad-request' => esc_html__('The response parameter is invalid or malformed.', 'hide-my-wp')
        );

        $captcha = HMWP_Classes_Tools::getValue('g-recaptcha-response');
        $secret = HMWP_Classes_Tools::getOption('brute_captcha_secret_key_v3');

        if ($secret <> '') {
            $response = json_decode(HMWP_Classes_Tools::hmwp_remote_get("https://www.google.com/recaptcha/api/siteverify?secret=$secret&response=" . $captcha . "&remoteip=" . $_SERVER['REMOTE_ADDR']), true);

            if (isset($response['success']) && !$response['success']) {
                //If captcha errors, let the user login and fix the error
                if (isset($response['error-codes']) && !empty($response['error-codes'])) {
                    foreach ($response['error-codes'] as $error_code) {
	                    if(isset($error_codes[$error_code])){
		                    $error_message = $error_codes[$error_code];
	                    }
                    }
                }

                if (!$error_message) {
                    $error_message = sprintf(esc_html__('%sIncorrect ReCaptcha%s. Please try again', 'hide-my-wp'), '<strong>', '</strong>');
                }
            }
        }

        return $error_message;
    }

    /**
     * reCAPTCHA head and login form
     */
    public function brute_recaptcha_head_v3()
    {
        ?>
        <script src='https://www.google.com/recaptcha/api.js?render=<?php echo HMWP_Classes_Tools::getOption('brute_captcha_site_key_v3') ?>' async defer></script>
        <style>#login{min-width: 354px;}</style>
        <?php
    }

    /**
     * reCAPTCHA head and login form
     */
    public function brute_recaptcha_form_v3()
    {
        if(!HMWP_Classes_Tools::getOption('brute_use_captcha_v3')) {
            return;
        }

        if (HMWP_Classes_Tools::getOption('brute_captcha_site_key_v3') <> '' && HMWP_Classes_Tools::getOption('brute_captcha_secret_key_v3') <> '') {
            ?>
            <script>
                function reCaptchaSubmit(e) {
                    var form = this;
                    e.preventDefault();
                    grecaptcha.ready(function() {
                        grecaptcha.execute('<?php echo HMWP_Classes_Tools::getOption('brute_captcha_site_key_v3') ?>', {action: 'submit'}).then(function(token) {
                            var input = document.createElement("input");
                            input.type = "hidden";
                            input.name = "g-recaptcha-response" ;
                            input.value = token ;
                            form.appendChild(input);
                            form.submit();
                        });
                    });
                }

                if(document.getElementsByTagName("form").length > 0) {
                    var x = document.getElementsByTagName("form");
                    for (var i = 0; i < x.length; i++) {
                        x[i].addEventListener("submit", reCaptchaSubmit);
                    }
                }
            </script>
            <?php
        }
    }


    /************************************************************************************/
    /**
     * Show the error message on IP address banned
     * @return void
     */
    public function brute_kill_login()
    {
        do_action('hmwp_kill_login', $this->brute_get_ip());

        wp_ob_end_flush_all();
        wp_die(
            HMWP_Classes_Tools::getOption('hmwp_brute_message'),
            esc_html__('Login Blocked by Hide My WordPress', 'hide-my-wp'),
            array('response' => 403)
        );
    }

}
