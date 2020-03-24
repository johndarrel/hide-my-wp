<?php
defined('ABSPATH') || die('Cheatin\' uh?');

class HMW_Controllers_SecurityCheck extends HMW_Classes_FrontController {
    /** @var bool Security check time */
    public $securitycheck_time = false;
    /** @var array Security Report */
    public $report = array();
    public $risktasks = array();
    public $riskreport = array();

    public function init() {
        //Initiate security
        $this->initSecurity();

        //Add the Menu Tabs in variable
        HMW_Classes_ObjController::getClass('HMW_Classes_DisplayController')->loadMedia('bootstrap.min');
        HMW_Classes_ObjController::getClass('HMW_Classes_DisplayController')->loadMedia('font-awesome.min');
        HMW_Classes_ObjController::getClass('HMW_Classes_DisplayController')->loadMedia('settings');

        if ($this->securitycheck_time = get_option('hmw_securitycheck_time')) {
            if (time() - $this->securitycheck_time['timestamp'] > (3600 * 24 * 7)) {
                HMW_Classes_Error::setError(__('You should check your website every week to see if there are any security changes.', _HMW_PLUGIN_NAME_));
                HMW_Classes_ObjController::getClass('HMW_Classes_Error')->hookNotices();
            }
        }

        $this->risktasks = $this->getRiskTasks();
        $this->riskreport = $this->getRiskReport();

        return parent::init();
    }

    /**
     * Initiate Security List
     * @return array|mixed
     */
    public function initSecurity() {
        $this->report = get_option('hmw_securitycheck');

        if (!empty($this->report)) {
            if (!$tasks_ignored = get_option('hmw_securitycheck_ignore')) {
                $tasks_ignored = array();
            }
            $tasks = $this->getTasks();
            foreach ($this->report as $function => &$row) {
                if (!in_array($function, $tasks_ignored)) {
                    if (isset($tasks[$function])) {
                        if (isset($row['version']) && $function == 'checkWP') {
                            $tasks[$function]['solution'] = str_replace('{version}', $row['version'], $tasks[$function]['solution']);
                        }
                        $row = array_merge($tasks[$function], $row);

                        if (!HMW_Classes_Tools::getOption('hmw_token')) {
                            if (isset($row['javascript']) && $row['javascript'] <> '') {
                                $row['javascript'] = 'alert(\'' . __('First, you need to connect Hide My Wp with WPPlugins and switch from Default mode to Lite Mode.', _HMW_PLUGIN_NAME_) . '\')';
                            }
                        } elseif ($function <> 'checkVersionDisplayed') {
                            if (isset($row['javascript']) && $row['javascript'] <> '') {
                                $row['pro'] = '<div class="btn btn-warning text-white" data-toggle="popover" data-html="true" data-placement="top" data-content="' . sprintf(__('This feature requires %sHide My WP Ghost%s.', _HMW_PLUGIN_NAME_), "<a href='http://hidemywpghost.com/' target='_blank'>", "</a>") . '">' . __('PRO', _HMW_PLUGIN_NAME_) . '</div>';
                            }
                        } elseif (HMW_Classes_Tools::getOption('hmw_mode') == 'default') {
                            if (isset($row['javascript']) && $row['javascript'] <> '') {
                                $row['javascript'] = 'alert(\'' . __('First, you need to switch Hide My Wp from Default mode to Lite Mode.', _HMW_PLUGIN_NAME_) . '\')';
                            }
                        }
                    }
                } else {
                    unset($this->report[$function]);
                }
            }
        }

        return $this->report;
    }

    /**
     * Process the security check
     */
    public function doSecurityCheck() {
        if (!$tasks_ignored = get_option('hmw_securitycheck_ignore')) {
            $tasks_ignored = array();
        }
        $tasks = $this->getTasks();
        foreach ($tasks as $function => $task) {
            if (!in_array($function, $tasks_ignored)) {
                if ($result = @call_user_func(array($this, $function))) {
                    $this->report[$function] = $result;
                }
            }
        }


        update_option('hmw_securitycheck', $this->report);
        update_option('hmw_securitycheck_time', array('timestamp' => current_time('timestamp', 1)));
    }

    /**
     * Get all the security tasks
     * @return array
     */
    public function getTasks() {
        return array(
            'checkPHP' => array(
                'name' => __('PHP Version', _HMW_PLUGIN_NAME_),
                'value' => false,
                'valid' => false,
                'warning' => false,
                'message' => __("Using an old version of PHP makes your site slow and prone to hacker attacks due to known vulnerabilities that exist in versions of PHP that are no longer maintained. <br /><br />You need <strong>PHP 7.0</strong> or higher for your website.", _HMW_PLUGIN_NAME_),
                'solution' => __("Email your hosting company and tell them you'd like to switch to a newer version of PHP or move your site to a better hosting company.", _HMW_PLUGIN_NAME_),
            ),
            'checkMysql' => array(
                'name' => __('Mysql Version', _HMW_PLUGIN_NAME_),
                'value' => false,
                'valid' => false,
                'warning' => false,
                'message' => __("Using an old version of MySQL makes your site slow and prone to hacker attacks due to known vulnerabilities that exist in versions of MySQL that are no longer maintained. <br /><br />You need <strong>Mysql 5.4</strong> or higher", _HMW_PLUGIN_NAME_),
                'solution' => __("Email your hosting company and tell them you'd like to switch to a newer version of MySQL or move your site to a better hosting company", _HMW_PLUGIN_NAME_),
            ),
            'checkWP' => array(
                'name' => __('WordPress Version', _HMW_PLUGIN_NAME_),
                'value' => false,
                'valid' => false,
                'warning' => false,
                'message' => sprintf(__("You should always update WordPress to the %slatest versions%s. These usually include the latest security fixes, and don't alter WP in any significant way. These should be applied as soon as WP releases them. <br /><br />When a new version of WordPress is available, you will receive an update message on your WordPress Admin screens. To update WordPress, click the link in this message.", _HMW_PLUGIN_NAME_), '<a href="https://wordpress.org/download/" target="_blank">', '</a>'),
                'solution' => __("There is a newer version of WordPress available ({version}).", _HMW_PLUGIN_NAME_),
            ),
            'checkWPDebug' => array(
                'name' => __('WP Debug Mode', _HMW_PLUGIN_NAME_),
                'value' => false,
                'valid' => false,
                'warning' => false,
                'message' => __("Every good developer should turn on debugging before getting started on a new plugin or theme. In fact, the WordPress Codex 'highly recommends' that developers use WP_DEBUG. <br /><br />Unfortunately, many developers forget the debug mode, even when the website is live. Showing debug logs in the frontend will let hackers know a lot about your WordPress website.", _HMW_PLUGIN_NAME_),
                'solution' => __("Disable WP_DEBUG for live websites in wp_config.php <code>define('WP_DEBUG', false);</code>", _HMW_PLUGIN_NAME_),
                'javascript' => "jQuery(this).hmw_fixConfig('WP_DEBUG',false);",
            ),
            'checkDBDebug' => array(
                'name' => __('DB Debug Mode', _HMW_PLUGIN_NAME_),
                'value' => false,
                'valid' => false,
                'warning' => false,
                'message' => __("It's not safe to have Database Debug turned on. Make sure you don't use Database debug on live websites.", _HMW_PLUGIN_NAME_),
                'solution' => sprintf(__("Turn off the debug plugins if your website is live. You can also switch on %sHide My Wp > Tweaks > Disable DB Debug in Frontent%s", _HMW_PLUGIN_NAME_), '<strong>', '</strong>'),
                'javascript' => "jQuery(this).hmw_fixSettings('hmw_disable_debug',1);",
            ),
            'checkScriptDebug' => array(
                'name' => __('Script Debug Mode', _HMW_PLUGIN_NAME_),
                'value' => false,
                'valid' => false,
                'warning' => false,
                'message' => __("Every good developer should turn on debugging before getting started on a new plugin or theme. In fact, the WordPress Codex 'highly recommends' that developers use SCRIPT_DEBUG. Unfortunately, many developers forget the debug mode even when the website is live. Showing debug logs in the frontend will let hackers know a lot about your WordPress website.", _HMW_PLUGIN_NAME_),
                'solution' => __("Disable SCRIPT_DEBUG for live websites in wp_config.php <code>define('SCRIPT_DEBUG', false);</code>", _HMW_PLUGIN_NAME_),
                'javascript' => "jQuery(this).hmw_fixConfig('SCRIPT_DEBUG',false);",
            ),
            'checkDisplayErrors' => array(
                'name' => __('display_errors PHP directive', _HMW_PLUGIN_NAME_),
                'value' => false,
                'valid' => false,
                'warning' => false,
                'message' => __("Displaying any kind of debug info in the frontend is extremely bad. If any PHP errors happen on your site they should be logged in a safe place and not displayed to visitors or potential attackers.", _HMW_PLUGIN_NAME_),
                'solution' => __("Edit wp_config.php and add <code>ini_set('display_errors', 0);</code>", _HMW_PLUGIN_NAME_),
            ),
            'checkSSL' => array(
                'name' => __('Backend under SSL', _HMW_PLUGIN_NAME_),
                'value' => false,
                'valid' => false,
                'warning' => false,
                'message' => __("SSL is an abbreviation used for Secure Sockets Layers, which are encryption protocols used on the internet to secure information exchange and provide certificate information.<br /><br />These certificates provide an assurance to the user about the identity of the website they are communicating with. SSL may also be called TLS or Transport Layer Security protocol. <br /><br />It's important to have a secure connection for the Admin Dashboard in WordPress.", _HMW_PLUGIN_NAME_),
                'solution' => sprintf(__("Learn how to set your website as %s. %sClick Here%s", _HMW_PLUGIN_NAME_), '<strong>' . str_replace('http:', 'https:', site_url()) . '</strong>', '<a href="https://hidemywpghost.com/how-to-move-wordpress-from-http-to-https/" target="_blank">', '</a>'),
            ),
            'checkAdminUsers' => array(
                'name' => __("User 'admin' as Administrator", _HMW_PLUGIN_NAME_),
                'value' => false,
                'valid' => false,
                'warning' => false,
                'message' => __("In the old days, the default WordPress admin username was 'admin'. Since usernames make up half of the login credentials, this made it easier for hackers to launch brute-force attacks. <br /><br />Thankfully, WordPress has since changed this and now requires you to select a custom username at the time of installing WordPress.", _HMW_PLUGIN_NAME_),
                'solution' => __("Change the user 'admin' with another name to improve security.", _HMW_PLUGIN_NAME_),
            ),
            'checkUserRegistration' => array(
                'name' => __("Spammers can easily signup", _HMW_PLUGIN_NAME_),
                'value' => false,
                'valid' => false,
                'warning' => false,
                'message' => __("If you do not have an e-commerce, membership or guest posting website, you shouldn't let users subscribe to your blog. You will end up with spam registrations and your website will be filled with spammy content and comments.", _HMW_PLUGIN_NAME_),
                'solution' => sprintf(__("Change the signup path from %sHide My Wp > Custom Register URL%s or uncheck the option %s > %s > %s", _HMW_PLUGIN_NAME_), '<strong>', '</strong>', '<strong>' . __('Settings'), __('General'), __('Membership') . '</strong>')
            ),
            'checkPluginsUpdates' => array(
                'name' => __("Outdated Plugins", _HMW_PLUGIN_NAME_),
                'value' => false,
                'valid' => false,
                'warning' => false,
                'message' => __("WordPress and its plugins and themes are like any other software installed on your computer, and like any other application on your devices. Periodically, developers release updates which provide new features, or fix known bugs. <br /><br />These new features may not necessarily be something that you want. In fact, you may be perfectly satisfied with the functionality you currently have. Nevertheless, you are still likely to be concerned about bugs.<br /><br />Software bugs can come in many shapes and sizes. A bug could be very serious, such as preventing users from using a plugin, or it could be minor and only affect a certain part of a theme, for example. In some cases, bugs can cause serious security holes. <br /><br />Keeping plugins up to date is one of the most important and easiest ways to keep your site secure.", _HMW_PLUGIN_NAME_),
                'solution' => __("Go to the Updates page and update all the plugins to the last version.", _HMW_PLUGIN_NAME_),
            ),
            'checkOldPlugins' => array(
                'name' => __("Not Updated Plugins", _HMW_PLUGIN_NAME_),
                'value' => false,
                'valid' => false,
                'warning' => false,
                'message' => __("Plugins that have not been updated in the last 12 months can have real security problems. Make sure you use updated plugins from WordPress Directory.", _HMW_PLUGIN_NAME_),
                'solution' => __("Go to the Updates page and update all the plugins to the last version.", _HMW_PLUGIN_NAME_),
            ),
            'checkIncompatiblePlugins' => array(
                'name' => __("Version Incompatible Plugins", _HMW_PLUGIN_NAME_),
                'value' => false,
                'valid' => false,
                'warning' => false,
                'message' => __("Plugins that are incompatible with your version of WordPress can have real security problems. Make sure you use tested plugins from WordPress Directory.", _HMW_PLUGIN_NAME_),
                'solution' => __("Make sure you use tested plugins from WordPress Directory.", _HMW_PLUGIN_NAME_),
            ),
            'checkThemesUpdates' => array(
                'name' => __("Outdated Themes", _HMW_PLUGIN_NAME_),
                'value' => false,
                'valid' => false,
                'warning' => false,
                'message' => __("WordPress and its plugins and themes are like any other software installed on your computer, and like any other application on your devices. Periodically developers release updates which provide new features or fix known bugs. <br /><br />New features may be something that you do not necessarily want. In fact, you may be perfectly satisfied with the functionality you currently have. Nevertheless, you may still be concerned about bugs.<br /><br />Software bugs can come in many shapes and sizes. A bug could be very serious, such as preventing users from using a plugin, or it could be a minor bug that only affects a certain part of a theme, for example. In some cases, bugs can even cause serious security holes.<br /><br />Keeping themes up to date is one of the most important and easiest ways to keep your site secure.", _HMW_PLUGIN_NAME_),
                'solution' => __("Go to the Updates page and update all the themes to the last version.", _HMW_PLUGIN_NAME_),
            ),
            'checkDBPrefix' => array(
                'name' => __("Database Prefix", _HMW_PLUGIN_NAME_),
                'value' => false,
                'valid' => false,
                'warning' => false,
                'message' => __("The WordPress database is like a brain for your entire WordPress site, because every single bit of information about your site is stored there, thus making it a hacker’s favorite target. <br /><br />Spammers and hackers run automated code for SQL injections.<br />Unfortunately, many people forget to change the database prefix when they install WordPress. <br />This makes it easier for hackers to plan a mass attack by targeting the default prefix <strong>wp_</strong>.", _HMW_PLUGIN_NAME_),
                'solution' => sprintf(__("Hide My WP protects your website from most SQL injections but, if possible, use a custom prefix for database tables to avoid SQL injections. %sRead more%s", _HMW_PLUGIN_NAME_), '<a href="https://firstsiteguide.com/change-database-prefix/" target="_blank">', '</a>'),
            ),
            'checkVersionDisplayed' => array(
                'name' => __("Versions in Source Code", _HMW_PLUGIN_NAME_),
                'value' => false,
                'valid' => false,
                'warning' => false,
                'message' => __("WordPress, plugins and themes add their version info to the source code, so anyone can see it. <br /><br />Hackers can easily find a website with vulnerable version plugins or themes, and target these with Zero-Day Exploits.", _HMW_PLUGIN_NAME_),
                'solution' => sprintf(__("Switch on %sHide My WP > Tweaks > %s %s", _HMW_PLUGIN_NAME_), '<strong>', __('Hide Versions and WordPress Tags', _HMW_PLUGIN_NAME_), '</strong>'),
                'javascript' => "jQuery(this).hmw_fixSettings('hmw_hide_version',1);",
            ),
            'checkSaltKeys' => array(
                'name' => __("Salts and Security Keys valid", _HMW_PLUGIN_NAME_),
                'value' => false,
                'valid' => false,
                'warning' => false,
                'message' => __("Security keys are used to ensure better encryption of information stored in the user's cookies and hashed passwords. <br /><br />These make your site more difficult to hack, access and crack by adding random elements to the password. You don't have to remember these keys. In fact, once you set them you'll never see them again. Therefore there's no excuse for not setting them properly.", _HMW_PLUGIN_NAME_),
                'solution' => __("Security keys are defined in wp-config.php as constants on lines. They should be as unique and as long as possible. <code>AUTH_KEY,SECURE_AUTH_KEY,LOGGED_IN_KEY,NONCE_KEY,AUTH_SALT,SECURE_AUTH_SALT,LOGGED_IN_SALT,NONCE_SALT</code>", _HMW_PLUGIN_NAME_),
            ),
            'checkSaltKeysAge' => array(
                'name' => __("Security Keys Updated", _HMW_PLUGIN_NAME_),
                'value' => false,
                'valid' => false,
                'warning' => false,
                'message' => __("The security keys in wp-config.php should be renewed as often as possible.", _HMW_PLUGIN_NAME_),
                'solution' => sprintf(__("You can generate %snew Keys from here%s <code>AUTH_KEY,SECURE_AUTH_KEY,LOGGED_IN_KEY,NONCE_KEY,AUTH_SALT,SECURE_AUTH_SALT,LOGGED_IN_SALT,NONCE_SALT</code>", _HMW_PLUGIN_NAME_), '<a href="https://api.wordpress.org/secret-key/1.1/salt/" target="_blank">', '</a>'),
            ),
            'checkDbPassword' => array(
                'name' => __("WordPress dDatabase Password", _HMW_PLUGIN_NAME_),
                'value' => false,
                'valid' => false,
                'warning' => false,
                'message' => __("There is no such thing as an \"unimportant password\"! The same goes for your WordPress database password. <br />Although most servers are configured so that the database can't be accessed from other hosts (or from outside of the local network), that doesn't mean your database password should be \"12345\" or no password at all.", _HMW_PLUGIN_NAME_),
                'solution' => __("Choose a proper database password, at least 8 characters long with a combination of letters, numbers and special characters. After you change it, set the new password in the wp_config.php file <code>define('DB_PASSWORD', 'NEW_DB_PASSWORD_GOES_HERE');</code>", _HMW_PLUGIN_NAME_),
            ),
//            'checkBlogSiteURL' => array(
//                'name' => __("Same Backend and Frontend URLs", _HMW_PLUGIN_NAME_),
//                'value' => false,
//                'valid' => false,
//                'warning' => false,
//                'message' => __("Moving WP core files to any non-standard folder will make your site less vulnerable to automated attacks. This is ", _HMW_PLUGIN_NAME_),
//                'solution' => __("(Optional) If your blog is setup on www.site.com you can put WP files in ie: /site.com/www/my-app/ instead of the obvious /site.com/www/.", _HMW_PLUGIN_NAME_),
//            ),
            'checkCommonPaths' => array(
                'name' => __("/wp-content is visible in source code", _HMW_PLUGIN_NAME_),
                'value' => false,
                'valid' => false,
                'warning' => false,
                'message' => __("It's important to rename common WordPress paths, such as wp-content and wp-includes to prevent hackers from knowing that you have a WordPress website.", _HMW_PLUGIN_NAME_),
                'solution' => sprintf(__("Change the wp-content, wp-includes and other common paths with %sHide My Wp > Permalinks%s", _HMW_PLUGIN_NAME_), '<strong>', '</strong>'),
            ),
            'checkOldPaths' => array(
                'name' => __("/wp-content path is accessible", _HMW_PLUGIN_NAME_),
                'value' => false,
                'valid' => false,
                'warning' => false,
                'message' => __("It's important to hide the common WordPress paths to prevent attacks on vulnerable plugins and themes. <br /> Also, it's important to hide the names of plugins and themes to make it impossible for bots to detect them.", _HMW_PLUGIN_NAME_),
                'solution' => sprintf(__("Switch on %sHide My Wp > Hide WordPress Common Paths%s to hide the old paths", _HMW_PLUGIN_NAME_), '<strong>', '</strong>'),
                'javascript' => "jQuery(this).hmw_fixSettings('hmw_hide_oldpaths',1);",
            ),
            'checkAdminPath' => array(
                'name' => sprintf(__("%s is visible in source code", _HMW_PLUGIN_NAME_), '/' . HMW_Classes_Tools::getOption('hmw_admin_url')),
                'value' => false,
                'valid' => false,
                'warning' => false,
                'message' => sprintf(__("Having the admin URL visible in the source code it's really bad because hackers will immediately know your secret admin path and start a Brute Force attack. The custom admin path should not appear in the ajax URL. <br /><br />Find solutions for %show to hide the path from source code%s.", _HMW_PLUGIN_NAME_), '<a href="https://hidemywpghost.com/how-to-hide-wp-admin-and-wp-login-php-from-source-code/" target="_blank">', '</a>'),
                'solution' => sprintf(__("Switch on %sHide My WP > Permalinks > Hide wp-admin from ajax URL%s. Hide any reference to admin path from the installed plugins.", _HMW_PLUGIN_NAME_), '<strong>', '</strong>', '<strong>', '</strong>'),
            ),
            'checkLoginPath' => array(
                'name' => sprintf(__("%s is visible in source code", _HMW_PLUGIN_NAME_), '/' . HMW_Classes_Tools::getOption('hmw_login_url')),
                'value' => false,
                'valid' => false,
                'warning' => false,
                'message' => sprintf(__("Having the login URL visible in the source code it's really bad because hackers will immediately know your secret login path and start a Brute Force attack. <br /><br />The custom login path should be kept secret and with the Brute Force Protection activated for it. <br ><br />Find solutions for %show to hide the path from source code%s.", _HMW_PLUGIN_NAME_), '<a href="https://hidemywpghost.com/how-to-hide-wp-admin-and-wp-login-php-from-source-code/" target="_blank">', '</a>'),
                'solution' => sprintf(__("%sHide the login path%s from theme menu or widget.", _HMW_PLUGIN_NAME_), '<strong>', '</strong>'),
            ),
            'checkOldLogin' => array(
                'name' => __("/wp-login path is accessible", _HMW_PLUGIN_NAME_),
                'value' => false,
                'valid' => false,
                'warning' => false,
                'message' => __("If your site allows user logins, you need your login page to be easy to find for your users. You also need to do other things to protect against malicious login attempts. <br /><br />However, obscurity is a valid security layer when used as part of a comprehensive security strategy, and if you want to cut down on the number of malicious login attempts. Making your login page difficult to find is one way to do that.", _HMW_PLUGIN_NAME_),
                'solution' => sprintf(__("Change the wp-login from %sHide My Wp > Custom login URL%s and Switch on %sHide My Wp > Brute Force Protection%s", _HMW_PLUGIN_NAME_), '<strong>', '</strong>', '<strong>', '</strong>'),
            ),
            'checkConfigChmod' => array(
                'name' => __("/wp_config.php file is writable", _HMW_PLUGIN_NAME_),
                'value' => false,
                'valid' => false,
                'warning' => false,
                'message' => __("One of the most important files in your WordPress installation is the wp-config.php file. <br />This file is located in the root directory of your WordPress installation, and contains your website's base configuration details, such as database connection information.", _HMW_PLUGIN_NAME_),
                'solution' => sprintf(__("Try setting chmod to %s0400%s or %s0440%s and if the website works normally that's the best one to use.", _HMW_PLUGIN_NAME_), '<a href="http://www.filepermissions.com/directory-permission/0400" target="_blank">', '</a>', '<a href="http://www.filepermissions.com/directory-permission/0440" target="_blank">', '</a>'),
            ),
            'checkConfig' => array(
                'name' => __("wp-config.php & wp-config-sample.php files are accessible ", _HMW_PLUGIN_NAME_),
                'value' => false,
                'valid' => false,
                'warning' => false,
                'message' => __("One of the most important files in your WordPress installation is the wp-config.php file. <br />This file is located in the root directory of your WordPress installation and contains your website's base configuration details, such as database connection information.", _HMW_PLUGIN_NAME_),
                'solution' => sprintf(__("Switch on %sHide My Wp > Hide WordPress Common Files%s to hide wp-config.php & wp-config-sample.php files", _HMW_PLUGIN_NAME_), '<strong>', '</strong>'),
                'javascript' => "jQuery(this).hmw_fixSettings('hmw_hide_commonfiles',1);",
            ),
            'checkReadme' => array(
                'name' => __("readme.html file is accessible ", _HMW_PLUGIN_NAME_),
                'value' => false,
                'valid' => false,
                'warning' => false,
                'message' => __("It's important to hide or remove the readme.html file because it contains WP version details.", _HMW_PLUGIN_NAME_),
                'solution' => sprintf(__("Rename readme.html file or switch on %sHide My Wp > Hide WordPress Common Files%s", _HMW_PLUGIN_NAME_), '<strong>', '</strong>'),
                'javascript' => "jQuery(this).hmw_fixSettings('hmw_hide_commonfiles',1);",
            ),
            'checkInstall' => array(
                'name' => __("install.php & upgrade.php files are accessible ", _HMW_PLUGIN_NAME_),
                'value' => false,
                'valid' => false,
                'warning' => false,
                'message' => __("WordPress is well-known for its ease of installation. <br/>It's important to hide the wp-admin/install.php and wp-admin/upgrade.php files because there have already been a couple of security issues regarding these files.", _HMW_PLUGIN_NAME_),
                'solution' => sprintf(__("Switch on %sHide My Wp > Hide WordPress Common Files%s to hide wp-admin/install.php & wp-admin/upgrade.php files", _HMW_PLUGIN_NAME_), '<strong>', '</strong>'),
                'javascript' => "jQuery(this).hmw_fixSettings('hmw_hide_oldpaths',1);",
            ),
            'checkRegisterGlobals' => array(
                'name' => __("PHP register_globals is on", _HMW_PLUGIN_NAME_),
                'value' => false,
                'valid' => false,
                'warning' => false,
                'message' => __("This is one of the biggest security issues you can have on your site! If your hosting company has this directive enabled by default, switch to another company immediately!", _HMW_PLUGIN_NAME_),
                'solution' => __("If you have access to php.ini file, set <code>register_globals = off</code> or contact the hosting company to set it off", _HMW_PLUGIN_NAME_),
            ),
            'checkExposedPHP' => array(
                'name' => __("PHP expose_php is on", _HMW_PLUGIN_NAME_),
                'value' => false,
                'valid' => false,
                'warning' => false,
                'message' => __("Exposing the PHP version will make the job of attacking your site much easier.", _HMW_PLUGIN_NAME_),
                'solution' => __("If you have access to php.ini file, set <code>expose_php = off</code> or contact the hosting company to set it off", _HMW_PLUGIN_NAME_),
            ),
            'checkPHPSafe' => array(
                'name' => __("PHP safe_mode is on", _HMW_PLUGIN_NAME_),
                'value' => false,
                'valid' => false,
                'warning' => false,
                'message' => __("PHP safe mode was one of the attempts to solve security problems of shared web hosting servers. <br /><br />It is still being used by some web hosting providers, however, nowadays this is regarded as improper. A systematic approach proves that it’s architecturally incorrect to try solving complex security issues at the PHP level, rather than at the web server and OS levels.<br /><br />Technically, safe mode is a PHP directive that restricts the way some built-in PHP functions operate. The main problem here is inconsistency. When turned on, PHP safe mode may prevent many legitimate PHP functions from working correctly. At the same time there exists a variety of methods to override safe mode limitations using PHP functions that aren’t restricted, so if a hacker has already got in – safe mode is useless.", _HMW_PLUGIN_NAME_),
                'solution' => __("If you have access to php.ini file, set <code>safe_mode = off</code> or contact the hosting company to set it off", _HMW_PLUGIN_NAME_),
            ),
            'checkAllowUrlInclude' => array(
                'name' => __("PHP allow_url_include is on", _HMW_PLUGIN_NAME_),
                'value' => false,
                'valid' => false,
                'warning' => false,
                'message' => __("Having this PHP directive enabled will leave your site exposed to cross-site attacks (XSS). <br /><br />There's absolutely no valid reason to enable this directive, and using any PHP code that requires it is very risky.", _HMW_PLUGIN_NAME_),
                'solution' => __("If you have access to php.ini file, set <code>allow_url_include = off</code> or contact the hosting company to set it off", _HMW_PLUGIN_NAME_),
            ),
            'checkAdminEditor' => array(
                'name' => __("Plugins/Themes editor enabled", _HMW_PLUGIN_NAME_),
                'value' => false,
                'valid' => false,
                'warning' => false,
                'message' => __("The plugins and themes file editor is a very convenient tool because it enables you to make quick changes without the need to use FTP. <br /><br />Unfortunately, it's also a security issue because it not only shows the PHP source code, it also enables attackers to inject malicious code into your site if they manage to gain access to admin.", _HMW_PLUGIN_NAME_),
                'solution' => __("Disable DISALLOW_FILE_EDIT for live websites in wp_config.php <code>define('DISALLOW_FILE_EDIT', true);</code>", _HMW_PLUGIN_NAME_),
                'javascript' => "jQuery(this).hmw_fixConfig('DISALLOW_FILE_EDIT',true);",
            ),
            'checkUploadsBrowsable' => array(
                'name' => sprintf(__("Folder %s is browsable ", _HMW_PLUGIN_NAME_), HMW_Classes_Tools::$default['hmw_upload_url']),
                'value' => false,
                'valid' => false,
                'warning' => false,
                'message' => __("Allowing anyone to view all files in the Uploads folder with a browser will allow them to easily download all your uploaded files. It's a security and a copyright issue.", _HMW_PLUGIN_NAME_),
                'solution' => sprintf(__("Learn how to disable %sDirectory Browsing%s", _HMW_PLUGIN_NAME_), '<a href="https://www.netsparker.com/blog/web-security/disable-directory-listing-web-servers/">', '</a>'),
                'javascript' => "jQuery(this).hmw_fixSettings('hmw_disable_browsing',1);",
            ),
            'checkWLW' => array(
                'name' => __("Windows Live Writer is on ", _HMW_PLUGIN_NAME_),
                'value' => false,
                'valid' => false,
                'warning' => false,
                'message' => __("If you're not using Windows Live Writer there's really no valid reason to have its link in the page header, because this tells the whole world you're using WordPress.", _HMW_PLUGIN_NAME_),
                'solution' => sprintf(__("Switch on %sHide My Wp > Tweaks > Disable WLW Manifest scripts%s", _HMW_PLUGIN_NAME_), '<strong>', '</strong>'),
                'javascript' => "jQuery(this).hmw_fixSettings('hmw_disable_manifest',1);",
            ),
            'checkXmlrpc' => array(
                'name' => __("XML-RPC access is on", _HMW_PLUGIN_NAME_),
                'value' => false,
                'valid' => false,
                'warning' => false,
                'message' => __("WordPress XML-RPC is a specification that aims to standardize communications between different systems. It uses HTTP as the transport mechanism and XML as encoding mechanism to enable a wide range of data to be transmitted. <br /><br />The two biggest assets of the API are its extendibility and its security. XML-RPC authenticates using basic authentication. It sends the username and password with each request, which is a big no-no in security circles.", _HMW_PLUGIN_NAME_),
                'solution' => sprintf(__("Switch on %sHide My Wp > Tweaks > Disable XML-RPC access%s", _HMW_PLUGIN_NAME_), '<strong>', '</strong>'),
                'javascript' => "jQuery(this).hmw_fixSettings('hmw_disable_xmlrpc',1);",
            ),
            'checkRDS' => array(
                'name' => __("RDS is visible", _HMW_PLUGIN_NAME_),
                'value' => false,
                'valid' => false,
                'warning' => false,
                'message' => __("If you're not using any Really Simple Discovery services such as pingbacks, there's no need to advertise that endpoint (link) in the header. Please note that for most sites this is not a security issue because they \"want to be discovered\", but if you want to hide the fact that you're using WP, this is the way to go.", _HMW_PLUGIN_NAME_),
                'solution' => sprintf(__("Switch on %sHide My Wp > Tweaks > Hide RSD header%s", _HMW_PLUGIN_NAME_), '<strong>', '</strong>'),
                'javascript' => "jQuery(this).hmw_fixSettings('hmw_hide_header',1);",
            ),
            'checkMysqlPermissions' => array(
                'name' => __("MySql Grant All Permissions", _HMW_PLUGIN_NAME_),
                'value' => false,
                'valid' => false,
                'warning' => false,
                'message' => __("If an attacker gains access to your wp-config.php file and gets the MySQL username and password, he'll be able to login to that database and do whatever that account allows. <br /><br />That's why it's important to keep the account's privileges to a bare minimum.<br /><br />For instance, if you're not installing any new plugins or updating WP, that account doesn't need the CREATE or DROP table privileges.<br /><br />For regular, day-to-day usage these are the recommended privileges: SELECT, INSERT, UPDATE and DELETE.", _HMW_PLUGIN_NAME_),
                'solution' => sprintf(__("To learn how to revoke permissions from PhpMyAdmin %sClick here%s", _HMW_PLUGIN_NAME_), '<a href="http://hidemywpghost.com/article/how-to-grant-and-revoke-permissions-to-database-using-phpmyadmin/" target="_blank">', '</a>'),
            ),
            'checkUsersById' => array(
                'name' => __("Author URL by ID access", _HMW_PLUGIN_NAME_),
                'value' => false,
                'valid' => false,
                'warning' => false,
                'message' => __("Usernames (unlike passwords) are not secret. By knowing someone's username, you can't log in to their account. You also need the password. <br /><br />However, by knowing the username, you are one step closer to logging in using the username to brute-force the password, or to gain access in a similar way. <br /><br />That's why it's advisable to keep the list of usernames private, at least to some degree. By default, by accessing siteurl.com/?author={id} and looping through IDs from 1 you can get a list of usernames, because WP will redirect you to siteurl.com/author/user/ if the ID exists in the system.", _HMW_PLUGIN_NAME_),
                'solution' => sprintf(__("Switch on %sHide My Wp > Hide Author ID URL%s", _HMW_PLUGIN_NAME_), '<strong>', '</strong>'),
                'javascript' => "jQuery(this).hmw_fixSettings('hmw_hide_authors',1);",
            ),
            'checkBlogDescription' => array(
                'name' => __("Default WordPress Tagline", _HMW_PLUGIN_NAME_),
                'value' => false,
                'valid' => false,
                'warning' => false,
                'message' => __("The WordPress site tagline is a short phrase located under the site title, similar to a subtitle or advertising slogan. The goal of a tagline is to convey the essence of your site to visitors. <br /><br />If you don't change the default tagline it will be very easy to detect that your website was actually built with WordPress", _HMW_PLUGIN_NAME_),
                'solution' => sprintf(__("Change the Tagline in %s > %s", _HMW_PLUGIN_NAME_), '<strong>' . __('General'), __('Tagline') . '</strong>'),
            ),


        );
    }

    /**
     * Get the Risk Tasks for speedometer
     * @return array
     */
    public function getRiskTasks() {
        return array(
            'checkPHP',
            'checkXmlrpc',
            'checkUsersById',
            'checkRDS',
            'checkUploadsBrowsable',
            'checkConfig',
            'checkOldLogin',
            'checkLoginPath',
            'checkOldPaths',
            'checkCommonPaths',
            'checkVersionDisplayed',
            'checkSSL',
            'checkDBDebug',
            'checkLoginPath',
        );
    }

    /**
     * Get the Risk Report for Daskboard Widget and speedometer
     * @return mixed
     */
    public function getRiskReport() {
        $riskreport = array();
        //get all the risk tasks
        $risktasks = $this->getRiskTasks();
        //initiate the security report
        $report = $this->initSecurity();

        if (!empty($report)) {
            foreach ($report as $function => $row) {
                if (in_array($function, $risktasks)) {
                    if (!$row['valid']) {
                        //add the invalid tasks into risk report
                        $riskreport[$function] = $row;
                    }
                }
            }
        }

        //return the risk report
        return $riskreport;
    }

    public function action() {
        parent::action();

        if (!current_user_can('manage_options')) {
            return;
        }

        switch (HMW_Classes_Tools::getValue('action')) {
            case 'hmw_securitycheck':
                $this->doSecurityCheck();

                break;

            case 'hmw_fixsettings':
                HMW_Classes_Tools::setHeader('json');
                $name = HMW_Classes_Tools::getValue('name', false);
                $value = HMW_Classes_Tools::getValue('value', false);
                if ($name && $value) {
                    if (in_array($name, array_keys(HMW_Classes_Tools::$options))) {
                        HMW_Classes_Tools::saveOptions($name, $value);

                        $message = __('Saved! You can run the test again.', _HMW_PLUGIN_NAME_);
                        echo json_encode(array('success' => true, 'message' => $message));
                        exit();
                    }
                }
                echo json_encode(array('success' => false, 'message' => __('Could not fix it. You need to change it yourself.', _HMW_PLUGIN_NAME_)));
                exit();
            case 'hmw_fixconfig':
                HMW_Classes_Tools::setHeader('json');
                $name = HMW_Classes_Tools::getValue('name', false);
                $value = HMW_Classes_Tools::getValue('value', null);


                if ($name && isset($value)) {
                    $root_path = HMW_Classes_Tools::getRootPath();
                    $config_file = $root_path . 'wp-config.php';
                    switch ($value) {
                        case 'true':
                            $value = 1;
                            break;
                        case 'false':
                            $value = 0;
                            break;
                        default:
                            $value = "'$value'";
                    }

                    if (HMW_Classes_ObjController::getClass('HMW_Models_Rules')->replaceToFile("define\s?\(\s?'$name'", "define('$name',$value);\n", $config_file)) {
                        echo json_encode(array('success' => true, 'message' => __('Saved! You can run the test again.', _HMW_PLUGIN_NAME_)));
                        exit();
                    }
                }
                echo json_encode(array('success' => false, 'message' => __('Could not fix it. You need to change it yourself.', _HMW_PLUGIN_NAME_)));
                exit();
            case 'hmw_securityexclude':
                $name = HMW_Classes_Tools::getValue('name', false);
                if ($name) {
                    if (!$tasks_ignored = get_option('hmw_securitycheck_ignore')) {
                        $tasks_ignored = array();
                    }

                    array_push($tasks_ignored, $name);
                    $tasks_ignored = array_unique($tasks_ignored);
                    update_option('hmw_securitycheck_ignore', $tasks_ignored);
                }
                HMW_Classes_Tools::setHeader('json');
                echo json_encode(array('success' => true, 'message' => __('Saved! This task will be ignored on future tests.', _HMW_PLUGIN_NAME_)));

                exit();

            case 'hmw_resetexclude':
                update_option('hmw_securitycheck_ignore', array());
                HMW_Classes_Tools::setHeader('json');
                echo json_encode(array('success' => true, 'message' => __('Saved! You can run the test again.', _HMW_PLUGIN_NAME_)));

                exit();

        }


    }

    /**
     * Check PHP version
     * @return array
     */
    public function checkPHP() {
        $phpversion = phpversion();
        if (strpos($phpversion, '-') !== false) {
            $phpversion = substr($phpversion, 0, strpos($phpversion, '-'));
        }
        return array(
            'value' => $phpversion,
            'valid' => (version_compare($phpversion, '7.0', '>=')),
        );
    }

    /**
     * Check if mysql is up-to-date
     * @return array
     */
    public function checkMysql() {
        global $wpdb;

        $mysql_version = $wpdb->db_version();

        return array(
            'value' => $mysql_version,
            'valid' => (version_compare($mysql_version, '5.0', '>')),
        );

    }

    /**
     * Check is WP_DEBUG is true
     * @return array|bool
     */
    public function checkWPDebug() {
        if (defined('WP_DEBUG')) {
            return array(
                'value' => (WP_DEBUG ? __('Yes') : __('No')),
                'valid' => !WP_DEBUG,
            );
        }

        return false;
    }

    /**
     * Check if DB debugging is enabled
     *
     * @return array
     */
    static function checkDbDebug() {
        global $wpdb;
	    $show_errors = ( $wpdb->show_errors && ! HMW_Classes_Tools::getOption( 'hmw_disable_debug' ) );

        return array(
            'value' => ($show_errors ? __('Yes') : __('No')),
            'valid' => !$show_errors,
        );

    }

    /**
     * Check if global WP JS debugging is enabled
     *
     * @return array|bool
     */
    static function checkScriptDebug() {
        if (defined('SCRIPT_DEBUG')) {
            return array(
                'value' => (SCRIPT_DEBUG ? __('Yes') : __('No')),
                'valid' => !SCRIPT_DEBUG,
            );
        }
        return false;
    }

    /**
     * Check if the backend is SSL or not
     * @return array
     */
    public function checkSSL() {
        return array(
            'value' => (is_ssl() ? __('Yes') : __('No')),
            'valid' => (is_ssl()),
        );
    }

    /**
     * Check Admin User declared
     * @return array
     */
    public function checkAdminUsers() {
        $users = get_users(array('role' => 'administrator', 'login' => 'admin'));
        return array(
            'value' => (!empty($users) ? __('Yes') : __('No')),
            'valid' => (empty($users)),
        );
    }

    /**
     * Check WordPress version
     * @return array|bool
     */
    public function checkWP() {
        global $wp_version;
        $wp_lastversion = $wpversion = false;
        if (isset($wp_version)) {

            $url = 'https://api.wordpress.org/core/version-check/1.7/';
            $response = wp_remote_get($url);
            $obj = json_decode($response['body']);
            if (isset($obj->offers[0])) {
                $upgrade = $obj->offers[0];
                if (isset($upgrade->version)) {
                    $wp_lastversion = $upgrade->version;
                }
            }

            if ($wp_lastversion) {
                return array(
                    'value' => $wp_version,
                    'valid' => version_compare($wp_version, $wp_lastversion, '=='),
                    'version' => $wp_lastversion,
                );
            }
        }

        return false;
    }

    /**
     * Check if plugins are up to date
     * @return array
     */
    public function checkPluginsUpdates() {
        //Get the current update info
        $current = get_site_transient('update_plugins');

        if (!is_object($current)) {
            $current = new stdClass;
        }

        set_site_transient('update_plugins', $current);

        // run the internal plugin update check
        wp_update_plugins();

        $current = get_site_transient('update_plugins');

        if (isset($current->response) && is_array($current->response)) {
            $plugin_update_cnt = count($current->response);
        } else {
            $plugin_update_cnt = 0;
        }

        $plugins = array();
        foreach ($current->response as $plugin_path => $tmp) {
            if (isset($tmp->slug)) {
                $plugins[] = $tmp->slug;
            }
        }

        return array(
            'value' => ($plugin_update_cnt > 0 ? sprintf(__('%s plugin are outdated: %s', _HMW_PLUGIN_NAME_), $plugin_update_cnt, join('<br />', $plugins)) : __('All plugins are up to date', _HMW_PLUGIN_NAME_)),
            'valid' => (!$plugin_update_cnt),
        );

    }

    /**
     * Check if themes are up to date
     * @return array
     */
    public function checkThemesUpdates() {
        $current = get_site_transient('update_themes');

        if (!is_object($current)) {
            $current = new stdClass;
        }

        set_site_transient('update_themes', $current);
        wp_update_themes();

        $current = get_site_transient('update_themes');

        if (isset($current->response) && is_array($current->response)) {
            $theme_update_cnt = count($current->response);
        } else {
            $theme_update_cnt = 0;
        }

        foreach ($current->response as $theme_name => $tmp) {
            $themes[] = $theme_name;
        }

        return array(
            'value' => ($theme_update_cnt > 0 ? sprintf(__('%s theme(s) are outdated: %s', _HMW_PLUGIN_NAME_), $theme_update_cnt, '<br />' . join("<br />", $themes)) : __('Themes are up to date', _HMW_PLUGIN_NAME_)),
            'valid' => (!$theme_update_cnt),
        );

    }

    public function checkOldPlugins() {
        //return false;
        global $hmw_plugin_details;

        $hmw_plugin_details = $return = array();
        $bad = array();
        $active_plugins = get_option('active_plugins', array());

        foreach ($active_plugins as $plugin_path) {
            $plugin = explode('/', $plugin_path);
            $plugin = @$plugin[0];
            if (empty($plugin) || empty($plugin_path)) {
                continue;
            }
            $response = wp_remote_get('https://api.wordpress.org/plugins/info/1.1/?action=plugin_information&request%5Bslug%5D=' . $plugin, array('timeout' => 5));
            if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) == 200 && wp_remote_retrieve_body($response)) {
                $details = wp_remote_retrieve_body($response);
                $details = json_decode($details, true);
                if (empty($details)) {
                    continue;
                }
                $hmw_plugin_details[$plugin_path] = $details;
                $updated = strtotime($details['last_updated']);
                if ($updated + 365 * DAY_IN_SECONDS < time()) {
                    $bad[$plugin_path] = true;
                }
            }
        } // foreach active plugin

        if (!empty($bad)) {
            $plugins = get_plugins();
            foreach ($bad as $plugin_path => $tmp) {
                $bad[$plugin_path] = $plugins[$plugin_path]['Name'];
            }
        }

        return array(
            'value' => (empty($bad) ? __('All plugins are up to date', _HMW_PLUGIN_NAME_) : implode('<br />', $bad)),
            'valid' => empty($bad),
        );

    }

    /**
     * Check incompatible plugins
     * @return array
     */
    public function checkIncompatiblePlugins() {
        //return false;
        global $hmw_plugin_details, $wp_version;

        $good = $bad = array();

        if (empty($hmw_plugin_details)) {
            $this->checkOldPlugins();
        }

        foreach ($hmw_plugin_details as $plugin_path => $plugin) {
            if (version_compare($wp_version, $plugin['tested'], '>')) {
                $bad[$plugin_path] = $plugin;
            } else {
                $good[$plugin_path] = $plugin;
            }
        } // foreach active plugins we have details on

        HMW_Debug::dump($bad);
        if (!empty($bad)) {
            $plugins = get_plugins();
            foreach ($bad as $plugin_path => $tmp) {
                $bad[$plugin_path] = $plugins[$plugin_path]['Name'];
            }
        }
        return array(
            'value' => (empty($bad) ? __('All plugins are compatible', _HMW_PLUGIN_NAME_) : implode('<br />', $bad)),
            'valid' => empty($bad),
        );

    }

    /**
     * Check if version is displayed in source code
     * @return array
     */
    public function checkVersionDisplayed() {
        $version = HMW_Classes_Tools::getOption('hmw_hide_version');
        if (HMW_Classes_Tools::getOption('hmw_mode') == 'default') {
            $version = false;
        }
        return array(
            'value' => ($version ? 'Removed' : 'Visible'),
            'valid' => ($version),
        );
    }

    /**
     * Check if PHP is exposed
     * @return array
     */
    public function checkExposedPHP() {
        $check = (bool)ini_get('expose_php');

        return array(
            'value' => ($check ? 'Yes' : 'No'),
            'valid' => (!$check),
        );

    }

    /**
     * Check Database Prefix
     * @return array
     */
    public function checkDBPrefix() {
        global $wpdb;

        return array(
            'value' => $wpdb->prefix,
            'valid' => !($wpdb->prefix == 'wp_' && $wpdb->prefix == 'wordpress_' && $wpdb->prefix == 'wp3_'),
        );
    }

    /**
     * Check Salt Keys
     * @return array
     */
    public function checkSaltKeys() {
        $keys = array('AUTH_KEY', 'SECURE_AUTH_KEY', 'LOGGED_IN_KEY', 'NONCE_KEY',
            'AUTH_SALT', 'SECURE_AUTH_SALT', 'LOGGED_IN_SALT', 'NONCE_SALT');

        foreach ($keys as $key) {
            $constant = @constant($key);
            if (empty($constant) || trim($constant) == 'put your unique phrase here' || strlen($constant) < 50) {
                $bad_keys[] = $key;
            }
        } // foreach

        return array(
            'value' => (!empty($bad_keys) ? implode(', ', $bad_keys) : __('Yes', _HMW_PLUGIN_NAME_)),
            'valid' => empty($bad_keys),
        );

    }

    /**
     * Check if wp-config.php has the right chmod
     *
     * @return array|false
     */
    public function checkSaltKeysAge() {
        $old = 95;

        if (file_exists(HMW_Classes_Tools::getRootPath() . 'wp-config.php')) {
            $age = @filemtime(HMW_Classes_Tools::getRootPath() . 'wp-config.php');

            if (!empty($age)) {
                $diff = time() - $age;

                return array(
                    'value' => (($diff > (DAY_IN_SECONDS * $old)) ? sprintf(__('%s days since last update', _HMW_PLUGIN_NAME_), $diff) : __('Updated', _HMW_PLUGIN_NAME_)),
                    'valid' => ($diff <= (DAY_IN_SECONDS * $old)),
                );
            }
        }
        return false;
    }

    /**
     * Check Database Password
     * @return array
     */
    public function checkDbPassword() {
        $password = DB_PASSWORD;

        if (empty($password)) {
            return array(
                'value' => __('Empty', _HMW_PLUGIN_NAME_),
                'valid' => false,
            );
        } elseif (strlen($password) < 6) {
            return array(
                'value' => __('only ' . strlen($password) . ' chars', _HMW_PLUGIN_NAME_),
                'valid' => false,
            );
        } elseif (sizeof(count_chars($password, 1)) < 5) {
            return array(
                'value' => __('too simple', _HMW_PLUGIN_NAME_),
                'valid' => false,
            );
        } else {
            return array(
                'value' => __('Good', _HMW_PLUGIN_NAME_),
                'valid' => true,
            );
        }
    }

    /**
     * Check if display_errors is off
     * @return array
     */
    public function checkDisplayErrors() {
        $check = ini_get('display_errors');

        return array(
            'value' => $check,
            'valid' => !(bool)$check,
        );
    }

    /**
     * Compare WP Blog Url with WP Site Url
     *
     * @return array
     */
    public function checkBlogSiteURL() {
        $siteurl = home_url();
        $wpurl = site_url();

        return array(
            'value' => (($siteurl == $wpurl) ? __('Yes') : __('No')),
            'valid' => ($siteurl <> $wpurl),
        );

    }

    /**
     * Check if wp-config.php has the right chmod
     * @return array|bool
     */
    public function checkConfigChmod() {
        $wp_config = HMW_Classes_Tools::getRootPath() . 'wp-config.php';

        if (file_exists($wp_config)) {
            if (HMW_Classes_Tools::isWindows()) {
                return array(
                    'value' => ((is_writeable($wp_config)) ? __('Yes') : __('No')),
                    'valid' => (!is_writeable($wp_config)),
                    'solution' => sprintf(__("Change the wp-config.php file permission to Read-Only using File Manager.", _HMW_PLUGIN_NAME_), '<a href="http://www.filepermissions.com/directory-permission/0400" target="_blank">', '</a>', '<a href="http://www.filepermissions.com/directory-permission/0440" target="_blank">', '</a>'),
                );
            } else {
                $mode = substr(sprintf('%o', @fileperms($wp_config)), -4);

                return array(
                    'value' => ((substr($mode, -1) != 0) ? __('Yes') : __('No')),
                    'valid' => (substr($mode, -1) == 0),
                );
            }
        }
        return false;
    }

    /**
     * Check wp-config.php file
     * @return array
     */
    public function checkConfig() {
        $url = home_url('wp-config.php?rnd=' . rand());
        $response = wp_remote_get($url, array('redirection' => 0));

        $visible = false;
        if (!is_wp_error($response)) {
            if (wp_remote_retrieve_response_code($response) == 200) {
                $visible = true;
            }
        }

        $url = home_url('wp-config-sample.php?rnd=' . rand());
        $response = wp_remote_get($url, array('redirection' => 0));

        if (!is_wp_error($response)) {
            if (wp_remote_retrieve_response_code($response) == 200) {
                $visible = true;
            }
        }

        return array(
            'value' => ($visible ? 'Yes' : 'No'),
            'valid' => (!$visible),
        );
    }

    /**
     * Check readme.html file
     * @return array
     */
    public function checkReadme() {
        $url = home_url('readme.html?rnd=' . rand());
        $response = wp_remote_get($url, array('redirection' => 0));

        $visible = false;
        if (!is_wp_error($response)) {
            if (wp_remote_retrieve_response_code($response) == 200) {
                $visible = true;
            }
        }

        return array(
            'value' => ($visible ? 'Yes' : 'No'),
            'valid' => (!$visible),
        );
    }


    /**
     * Does WP install.php file exist?
     * @return array
     */
    public function checkInstall() {
        $url = site_url() . '/wp-admin/install.php?rnd=' . rand();
        $response = wp_remote_get($url, array('redirection' => 0));

        $visible = false;
        if (!is_wp_error($response)) {
            if (wp_remote_retrieve_response_code($response) == 200) {
                $visible = true;
            }
        }

        return array(
            'value' => ($visible ? __('Yes') : __('No')),
            'valid' => (!$visible),
        );
    }

    /**
     * Check if register_globals is off
     *
     * @return array
     */
    public function checkRegisterGlobals() {
        $check = (bool)ini_get('register' . '_globals');
        return array(
            'value' => ($check ? __('Yes') : __('No')),
            'valid' => (!$check),
        );

    }

    /**
     * Check if safe mode is off
     *
     * @return array
     */
    public function checkPHPSafe() {
        $check = (bool)ini_get('safe' . '_mode');
        return array(
            'value' => ($check ? __('Yes') : __('No')),
            'valid' => (!$check),
        );

    }

    /**
     * Check if allow_url_include is off
     * @return array
     */
    public function checkAllowUrlInclude() {
        $check = (bool)ini_get('allow_url_include');
        return array(
            'value' => ($check ? __('Yes') : __('No')),
            'valid' => (!$check),
        );
    }

    /**
     * Is theme/plugin editor disabled?
     * @return array
     */
    public function checkAdminEditor() {
        if (defined('DISALLOW_FILE_EDIT')) {
            return array(
                'value' => (DISALLOW_FILE_EDIT ? __('Yes') : __('No')),
                'valid' => DISALLOW_FILE_EDIT,
            );
        } else {
            return array(
                'value' => __('Yes'),
                'valid' => false,
            );
        }
    }


    /**
     * Check if Upload Folder is browsable
     * @return array|bool
     */
    public function checkUploadsBrowsable() {
        $upload_dir = wp_upload_dir();

        $args = array('method' => 'GET', 'timeout' => 5, 'redirection' => 0, 'sslverify' => false,
            'httpversion' => 1.0, 'blocking' => true, 'headers' => array(), 'body' => null, 'cookies' => array());
        $response = wp_remote_get(rtrim($upload_dir['baseurl'], '/') . '/?nocache=' . rand(), $args);

        if (is_wp_error($response)) {
            $return = array(
                'value' => __('No'),
                'valid' => true,
            );
        } elseif (wp_remote_retrieve_response_code($response) == 200 && stripos($response['body'], 'index') !== false) {
            $return = array(
                'value' => __('Yes'),
                'valid' => false,
            );
        } else {
            $return = array(
                'value' => __('No'),
                'valid' => true,
            );
        }

        if (!HMW_Classes_Tools::isApache() && !HMW_Classes_Tools::isNginx() && !HMW_Classes_Tools::isLitespeed()) {
            $return['javascript'] = '';
        }

        return $return;
    }

    /**
     * Check if Wondows Live Writer is not disabled
     *
     * @return array
     */
    public function checkWLW() {
        $check = (!HMW_Classes_Tools::getOption('hmw_disable_manifest'));
        return array(
            'value' => ($check ? __('Yes') : __('No')),
            'valid' => (!$check),
        );

    }

    /**
     * Check if XML PRC
     *
     * @return array
     */
    public function checkXmlrpc() {
        $check = (!HMW_Classes_Tools::getOption('hmw_disable_xmlrpc'));
        return array(
            'value' => ($check ? __('Yes') : __('No')),
            'valid' => (!$check),
        );

    }

    /**
     * Check if XML PRC
     *
     * @return array
     */
    public function checkRDS() {
        $check = (!HMW_Classes_Tools::getOption('hmw_hide_header'));
        return array(
            'value' => ($check ? __('Yes') : __('No')),
            'valid' => (!$check),
        );

    }

    /**
     * Check if the WP MySQL user has too many permissions granted
     * @return array
     */
    static function checkMysqlPermissions() {
        global $wpdb;

        $grants = $wpdb->get_results('SHOW GRANTS', ARRAY_N);
        foreach ($grants as $grant) {
            if (stripos($grant[0], 'GRANT ALL PRIVILEGES') !== false) {
                return array(
                    'value' => __('Yes'),
                    'valid' => false,
                );
                break;
            }
        }

        return array(
            'value' => __('no'),
            'valid' => true,
        );
    }

    /**
     * Check if an user can be found by its ID
     * @return mixed
     */
    static function checkUsersById() {
        $users = get_users('number=5');
        $success = false;
        $url = home_url() . '/?author=';

        foreach ($users as $user) {
            $response = wp_remote_get($url . $user->ID, array('redirection' => 0));
            $response_code = wp_remote_retrieve_response_code($response);
            if ($response_code == 301) {
                $success = true;
                break;
            }
        } // foreach

        return array(
            'value' => ($success ? __('Yes') : __('No')),
            'valid' => (!$success),
        );
    }

    /**
     * Check if XML PRC
     *
     * @return array|bool
     */
    public function checkOldPaths() {
        $url = site_url() . '/' . HMW_Classes_Tools::$default['hmw_wp-content_url'] . '/';
        $response = wp_remote_get($url, array('redirection' => 0));

        $visible = false;
        if (!is_wp_error($response)) {
            if (wp_remote_retrieve_response_code($response) == 200) {
                $visible = true;
            }
        }

        return array(
            'value' => ($visible ? __('Yes') : __('No')),
            'valid' => (!$visible),
        );

    }

    /**
     * Check the Old paths in source code
     * @return array|bool
     */
    public function checkCommonPaths() {
        if (!isset($this->html)) {
            if (!$this->getSourceCode()) {
                return false;
            }
        }

        if (!$found = strpos($this->html, content_url())) {
            $found = strpos($this->html, plugins_url());
        }

        return array(
            'value' => ($found ? __('Yes') : __('No')),
            'valid' => (!$found),
        );

    }

    /**
     * Check the Login path in source code
     * @return array|bool
     */
    public function checkLoginPath() {
        if (!isset($this->html)) {
            if (!$this->getSourceCode()) {
                return false;
            }
        }

        if (!$found = strpos($this->html, home_url('wp-login.php'))) {
            $found = strpos($this->html, home_url(HMW_Classes_Tools::getOption('hmw_login_url')));
        }

        return array(
            'value' => ($found ? __('Yes') : __('No')),
            'valid' => (!$found),
        );

    }

    /**
     * Check the Admin path in source code
     * @return array|bool
     */
    public function checkAdminPath() {
        if (!isset($this->html)) {
            if (!$this->getSourceCode()) {
                return false;
            }
        }

        $found = strpos($this->html, home_url(HMW_Classes_Tools::getOption('hmw_admin_url')));

        return array(
            'value' => ($found ? __('Yes') : __('No')),
            'valid' => (!$found),
        );

    }

    /**
     * Check if wp-admin is accessible for visitors
     * @return array
     */
    public function checkOldLogin() {
        $url = site_url() . '/wp-login.php';
        $response = wp_remote_get($url, array('redirection' => 0));

        $visible = false;
        if (!is_wp_error($response)) {
            if (wp_remote_retrieve_response_code($response) == 200) {
                $visible = true;
            }
        }

        return array(
            'value' => ($visible ? __('Yes') : __('No')),
            'valid' => (!$visible),
        );
    }

    /**
     * Check if anyone can register easily
     * @return array
     */
    public function checkUserRegistration() {
        $check = (get_option('users_can_register'));
        if ($check) $check = (HMW_Classes_Tools::getOption('hmw_register_url') == '');

        return array(
            'value' => ($check ? __('Yes') : __('No')),
            'valid' => (!$check),
        );
    }

    public function checkBlogDescription() {
        $check = (get_option('blogdescription') == __('Just another WordPress site'));

        return array(
            'value' => ($check ? __('Yes') : __('No')),
            'valid' => (!$check),
        );
    }

    /**
     * Get the homepage source code
     * @return string
     */
    public function getSourceCode() {
        if (!isset($this->html) && !isset($this->htmlerror)) {
            $url = home_url('?rnd=' . rand());
            $response = wp_remote_get($url, array('redirection' => 0));

            if (!is_wp_error($response)) {
                if (wp_remote_retrieve_response_code($response) == 200) {
                    $this->html = wp_remote_retrieve_body($response);
                } else {
                    $this->htmlerror = true;
                    $this->html = false;
                }
            } else {
                $this->htmlerror = true;
                $this->html = false;
            }
        }
        return $this->html;
    }
}