<?php
defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );

class HMW_Debug {

    /** @var array */
    private static $debug;

    public function __construct() {
    }

    public function logQueries($query) {
        self::dump($query);
        return $query;
    }

    public function _isAjax() {
        if (defined('DOING_AJAX') && DOING_AJAX) {
            return true;
        }
        return false;
    }

    /**
     * Get a value from $_POST / $_GET
     * if unavailable, take a default value
     *
     * @param string $key Value key
     * @param mixed $defaultValue (optional)
     * @return mixed Value
     */
    public static function getValue($key, $defaultValue = false) {
        if (!isset($key) OR empty($key) OR !is_string($key))
            return false;
        $ret = (isset($_POST[$key]) ? $_POST[$key] : (isset($_GET[$key]) ? $_GET[$key] : $defaultValue));

        if (is_string($ret) === true)
            $ret = urldecode(preg_replace('/((\%5C0+)|(\%00+))/i', '', urlencode($ret)));
        return !is_string($ret) ? $ret : stripslashes($ret);
    }

    public static function setValue($key, $value) {
        $_POST[$key] = $value;
        $_GET[$key] = $value;
    }

    /**
     * Check if the parameter is set
     *
     * @param string $key
     * @return boolean
     */
    public static function getIsset($key) {
        if (!isset($key) OR empty($key) OR !is_string($key))
            return false;
        return isset($_POST[$key]) ? true : (isset($_GET[$key]) ? true : false);
    }

    /**
     * Check if debug is called
     */
    public static function checkDebug() {
        //if debug is called
        if (self::getIsset('debug')) {
            if (self::getValue('debug') === 'on' && HMW_DEBUG) {
                error_reporting(E_ALL);
                @ini_set('display_errors', true);
                if (function_exists('register_shutdown_function')) {
                    register_shutdown_function(array(new HMW_Debug(), 'showDebug'));
                }
            }
        }
//        elseif (HMW_DEBUG) {
//            register_shutdown_function(array(new HMW_Debug(), 'showLoadingTime'));
//        }
    }

    /**
     * Get the debug buffer
     * @param $buffer
     * @return mixed
     */
    public function getBuffer($buffer) {
        if (!$this->_isAjax()) {
            return $buffer;
        }
        if (!$this->getIsset('debug')) {
            return $buffer;
        }

        return false;
    }

    /**
     * Store the debug for a later view
     *
     * @return bool|void
     */
    public static function dump() {
        if (HMW_DEBUG) {
            if (self::getValue('debug') !== 'on') {
                return;
            }

            $output = '';
            $total_arguments = 0;
            $callee = array('file' => '', 'line' => '');
            $run_time = number_format(microtime(true) - HMW_REQUEST_TIME, 3);

            if (function_exists('func_get_args')) {
                $arguments = func_get_args();
                $total_arguments = count($arguments);
            } else
                $arguments = array();


            if (function_exists('debug_backtrace'))
                list($callee) = debug_backtrace();

            $output .= '<fieldset style="background: #FFFFFF; border: 1px #CCCCCC solid; padding: 5px; font-size: 9pt; margin: 0;">';
            $output .= '<legend style="background: #EEEEEE; padding: 2px; font-size: 8pt;">' . $callee['file'] . ' Time: ' . $run_time . ' @ line: ' . $callee['line']
                . '</legend><pre style="margin: 0; font-size: 8pt; text-align: left;">';

            $i = 0;
            foreach ($arguments as $argument) {
                if (count($arguments) > 1)
                    $output .= "\n" . '<strong>#' . (++$i) . ' of ' . $total_arguments . '</strong>: ';

                // if argument is boolean, false value does not display, so ...
                if (is_bool($argument))
                    $argument = ($argument) ? 'TRUE' : 'FALSE';
                else
                    if (is_object($argument) && function_exists('array_reverse') && function_exists('class_parents'))
                        $output .= implode("\n" . '|' . "\n", array_reverse(class_parents($argument))) . "\n" . '|' . "\n";

                $output .= htmlspecialchars(json_encode($argument))
                    . ((is_object($argument) && function_exists('spl_object_hash')) ? spl_object_hash($argument) : '');
            }
            $output .= "</pre>";
            $output .= "</fieldset>";

            self::$debug[] = $output;
        }
    }

    /**
     * Show the debug dump
     */
    public static function showDebug() {
        HMW_Classes_Tools::setHeader('html');
        $errors = error_get_last();
        HMW_Debug::dump($errors);
        echo "Debug result: <br />" . '<div id="wpcontent">' . '<br />' . @implode('<br />', self::$debug) . '<div>';

        $run_time = number_format(microtime(true) - HMW_REQUEST_TIME, 3);
        $pps = number_format(1 / $run_time, 0);
        $memory_avail = ini_get('memory_limit');
        $memory_used = number_format(memory_get_usage(true) / (1024 * 1024), 2);
        $memory_peak = number_format(memory_get_peak_usage(true) / (1024 * 1024), 2);

        if (PHP_SAPI == 'cli') {
            // if run for command line, display some info
            $debug = PHP_EOL
                . "======================================================================================"
                . PHP_EOL
                . " Config: php " . phpversion() . " " . php_sapi_name() . " / zend engine " . zend_version()
                . PHP_EOL
                . " Load: {$memory_avail} (avail) / {$memory_used}M (used) / {$memory_peak}M (peak)"
                . "  | Time: {$run_time}s | {$pps} req/sec"
                . PHP_EOL
                . "  | Server Timezone: " . date_default_timezone_get()
                . "  | Agent: CLI"
                . PHP_EOL
                . "======================================================================================"
                . PHP_EOL;
        } else {
            // if not run from command line, only display if debug is enabled
            $debug = "" //<hr />"
                . "<div style=\"text-align: left;\">"
                . "<small><hr />"
                . " Config: "
                . "<br />"
                . " &nbsp;&nbsp; | php " . phpversion() . " " . php_sapi_name() . " / zend engine " . zend_version()
                . "<br />"
                . " &nbsp;&nbsp; | Server Timezone: " . date_default_timezone_get()
                . "<br />"
                . " Load: "
                . "<br />"
                . " &nbsp;&nbsp; | Memory: {$memory_avail} (avail) / {$memory_used}M (used) / {$memory_peak}M (peak)"
                . "<br />"
                . " &nbsp;&nbsp; | Time: {$run_time}s &nbsp;&nbsp; | {$pps} req/sec"
                . "<br />"
                . "Url: "
                . "<br />"
                . " &nbsp;&nbsp; |"
                . "<br />"
                . " &nbsp;&nbsp; | Agent: " . (@$_SERVER["HTTP_USER_AGENT"])
                . "<br />"
                . "Version Control: "
                . "<br />"
                . "</small>"
                . "</div>"
                . "<br />";
        }


        echo '<pre><div id="wpcontent">' . $debug . '</div></pre>';
    }

    public static function showLoadingTime() {
        $run_time = number_format(microtime(true) - HMW_REQUEST_TIME, 3);
        $pps = number_format(1 / $run_time, 0);
        $memory_avail = ini_get('memory_limit');
        $memory_used = number_format(memory_get_usage(true) / (1024 * 1024), 2);
        $memory_peak = number_format(memory_get_peak_usage(true) / (1024 * 1024), 2);

        $debug = PHP_EOL
            . "======================================================================================"
            . PHP_EOL
            . " Config: php " . phpversion() . " " . php_sapi_name() . " / zend engine " . zend_version()
            . PHP_EOL
            . " Load: {$memory_avail} (avail) / {$memory_used}M (used) / {$memory_peak}M (peak)"
            . "  | Time: {$run_time}s | {$pps} req/sec"
            . PHP_EOL
            . "  | Server Timezone: " . date_default_timezone_get()
            . "  | Agent: CLI"
            . PHP_EOL
            . "======================================================================================"
            . PHP_EOL;


        echo '<pre><div id="wpcontent">' . $debug . '</div></pre>';
    }

}

defined('HMW_DEBUG') || define('HMW_DEBUG', false);
HMW_Debug::checkDebug();
