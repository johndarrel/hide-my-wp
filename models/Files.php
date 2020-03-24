<?php
defined('ABSPATH') || die('Cheatin\' uh?');

class HMW_Models_Files {

    protected $_files = array();
    protected $_replace = array();

    public function __construct() {
        $this->_files = array('jpg', 'jpeg', 'png', 'bmp', 'gif', 'webp',
            'css', 'scss', 'js', 'woff', 'woff2', 'ttf', 'otf', 'pfb', 'pfm', 'tfil', 'eot', 'svg',
            'pdf', 'doc', 'docx', 'csv', 'xls', 'xslx',
            'mp2', 'mp3', 'mp4', 'mpeg',
            'zip', 'rar');

        //init the replace array
        $this->_replace = array('from' => [], 'to' => []);
    }

    /**
     * Check if the current URL is a file
     */
    public function checkBrokenFile() {
        //don't let to rename and hide the current paths if logout is required
        if (HMW_Classes_Tools::getOption('error') || HMW_Classes_Tools::getOption('logout')) {
            return;
        }

        //stop here is the option is default.
        //the prvious code is needed for settings change and validation
        if (HMW_Classes_Tools::getOption('hmw_mode') == 'default') {
            return;
        }

        if (is_404()) {
            $this->showFile($this->getCurrentURL());
        }
    }

    public function checkAdminPath() {

        if (function_exists('is_user_logged_in')) {
            if (is_user_logged_in()) {
                if (strpos($this->getCurrentURL(), home_url() . '/' . HMW_Classes_Tools::getOption('hmw_admin_url') . '/') !== false) {
                    wp_redirect(str_replace('/' . HMW_Classes_Tools::getOption('hmw_admin_url') . '/',
                        '/' . HMW_Classes_Tools::$default['hmw_admin_url'] . '/',
                        $this->getCurrentURL()));
                    exit();
                }
            }
        }

    }

    /**
     *
     * If the rewrite config is not set
     * If there is a new file path, change it back to real path and show the file
     * Prevents errors when the paths are chnged but the rewrite config is not set up correctly
     * @param $url
     * @return bool|string
     */
    public function isFile($url) {
        if ($url <> '') {
            if (strpos($url, '?') !== false) $url = substr($url, 0, strpos($url, '?'));
            if (strrpos($url, '.') !== false) {
                $ext = substr($url, strrpos($url, '.') + 1);
                if (in_array($ext, $this->_files)) {
                    return $ext;
                }
            }
        }

        return false;
    }

    /**
     * Get the current URL
     * @return string
     */
    public function getCurrentURL() {
        $url = '';

        if (isset($_SERVER['HTTP_HOST'])) {
            // build the URL in the address bar
            $url = is_ssl() ? 'https://' : 'http://';
            $url .= $_SERVER['HTTP_HOST'];
            $url .= $_SERVER['REQUEST_URI'];
        }

        return $url;
    }

    /**
     * Show the file when the server rewrite is not added
     * @param string $url broken URL
     */
    public function showFile($url) {
        if (!defined('ABSPATH')) {
            return;
        }

        /** @var HMW_Models_Rewrite $rewriteModel */
        $rewriteModel = HMW_Classes_ObjController::getClass('HMW_Models_Rewrite');


        if (empty($this->_replace['from']) && empty($this->_replace['to'])) {
            if (!isset($rewriteModel->_replace['from']) && !isset($rewriteModel->_replace['to'])) {
                $rewriteModel->buildRedirect();
            }

            //Verify only the rewrites
            $rewrite = $rewriteModel->_replace['rewrite'];
            $rewrite_from = $rewriteModel->_replace['from'];
            $rewrite_to = $rewriteModel->_replace['to'];
            foreach ($rewrite as $index => $value) {
                //add only the paths
                if (($index && isset($rewrite_to[$index]) && substr($rewrite_to[$index], -1) == '/') ||
                    strpos($rewrite_to[$index], '/' . HMW_Classes_Tools::getOption('hmw_themes_style'))) {
                    $this->_replace['from'][] = $rewrite_from[$index];
                    $this->_replace['to'][] = $rewrite_to[$index];
                }
            }

            //add the domain to rewrites
            $this->_replace['from'] = array_map(array($rewriteModel, 'addDomainUrl'), (array)$this->_replace['from']);
            $this->_replace['to'] = array_map(array($rewriteModel, 'addDomainUrl'), (array)$this->_replace['to']);

            unset($rewrite);
            unset($rewrite_from);
            unset($rewrite_to);
        }

        //Restore the URL to original
        $new_url = str_ireplace($this->_replace['to'], $this->_replace['from'], $url);
        $new_url = str_replace('/wp-admin/wp-admin/', '/wp-admin/', $new_url); //remove duplicates

        //Don't replace include if content was already replaced
        if (strpos($new_url, '/' . HMW_Classes_Tools::$default['hmw_wp-content_url'] . '/') !== false && strpos($new_url, '/busting/') === false) {
            $new_url = str_ireplace(HMW_Classes_Tools::$default['hmw_wp-includes_url'], HMW_Classes_Tools::getOption('hmw_wp-includes_url'), $new_url);
        }

        $ctype = false;
        if ($url <> $new_url) {
            if ($ext = $this->isFile($new_url)) {
                $new_path = HMW_Classes_Tools::getRootPath() . ltrim(str_replace(home_url(), '', $new_url), '/');
                if (strpos($new_path, '?') !== false) $new_path = substr($new_path, 0, strpos($new_path, '?'));

                if (file_exists($new_path)) {
                    switch ($ext) {
                        case "scss":
                        case "css":
                            $ctype = "text/css";
                            break;
                        case "js":
                            $ctype = "application/javascript";
                            break;
                        default:
                            if (function_exists('mime_content_type')) {
                                $ctype = @mime_content_type($new_path);
                            }
                    }

                    ob_clean(); //clear the buffer
                    $content = @file_get_contents($new_path);

                    header("HTTP/1.1 200 OK");
                    header("Cache-Control: max-age=2592000");
                    header('Vary: Accept-Encoding');
                    if ($ctype) header('Content-Type: ' . $ctype . '; charset: UTF-8');

                    if (strpos($new_url, '.js')) {
                        //$content = $rewriteModel->find_replace($content);
                    } elseif (strpos($new_url, '.css') || strpos($new_url, '.scss')) {
                        $content = preg_replace('/\*\*\*+/s', '', $content);
                        $content = preg_replace('/(\/\*[^\*]+\*\/)/s', '', $content);
                        $content = $rewriteModel->find_replace($content);
                    }

                    //gzip the CSS
                    if (function_exists('gzencode')) {
                        header("Content-Encoding: gzip"); //HTTP 1.1
                        $content = gzencode($content);
                    }

                    //Show the content
                    header('Content-Length: ' . strlen($content));
                    echo $content;
                    exit();
                }

            } elseif (strpos($new_url, 'wp-login.php') || strpos($new_url, HMW_Classes_Tools::getOption('hmw_login_url'))) {
                $actions = array('postpass', 'logout', 'lostpassword', 'retrievepassword', 'resetpass', 'rp', 'register', 'login', 'confirmaction');
                $_REQUEST['action'] = $this->strposa($new_url, $actions);

                ob_start();
                include(ABSPATH . '/wp-login.php');
                $content = ob_get_clean();

                header("HTTP/1.1 200 OK");
                echo $content;
                exit();
            } elseif (strpos($new_url, 'wp-activate.php')) {
                ob_start();
                include(ABSPATH . '/wp-activate.php');
                $content = ob_get_clean();

                header("HTTP/1.1 200 OK");
                echo $content;
                exit();
            } elseif (strpos($new_url, 'wp-signup.php')) {
                ob_start();
                include(ABSPATH . '/wp-signup.php');
                $content = ob_get_clean();

                header("HTTP/1.1 200 OK");
                echo $content;
                exit();
            } elseif (strpos($new_url, '/wp-admin/admin-ajax.php')) {
                if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
                    $options['headers'] = 'Referer: ' . wp_get_referer();
                    if (!empty($_COOKIE)) {
                        $cookies = array();
                        foreach ($_COOKIE as $name => $value) {
                            $cookies[] = new WP_Http_Cookie(array('name' => $name, 'value' => $value));
                        }
                        $options['cookies'] = $cookies;
                    }
                    $content = HMW_Classes_Tools::hmw_remote_post($new_url, $_POST, $options);
                } else {
                    $content = HMW_Classes_Tools::hmw_remote_get($new_url);
                }

                header("HTTP/1.1 200 OK");
                echo $content;
                exit();
            } elseif (strpos($new_url, '/wp-admin')) {
                $this->checkAdminPath();
            } else {
                if (strpos($new_url, '.css') || strpos($new_url, '.scss')) {
                    header('Content-Type: text/css; charset: UTF-8');
                } elseif (strpos($new_url, '.js')) {
                    header('Content-Type: application/javascript; charset: UTF-8');
                }

                $content = @file_get_contents($new_url);

                header("HTTP/1.1 200 OK");
                echo $content;
                exit();
            }
        }
    }

    function strposa($haystack, $needles = array(), $offset = 0) {
        foreach ($needles as $needle) {
            if (strpos($haystack, $needle, $offset) !== false) {
                return $needle;
            }
        }
        return false;
    }

}
