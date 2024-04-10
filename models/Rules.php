<?php
/**
 * Rules Model
 * Called to handle the vulnerability Rules in the config file based on the server type
 *
 * @file  The Rules Model file
 * @package HMWP/RulesModel
 * @since 4.0.0
 */
defined('ABSPATH') || die('Cheatin\' uh?');

class HMWP_Models_Rules
{
    public $root_path;
    public $config_file;
    public $config_chmod;

    public function __construct()
    {
        $this->root_path = HMWP_Classes_Tools::getRootPath();

        if (HMWP_Classes_Tools::isNginx() ) {
            $this->config_file = $this->root_path . 'hidemywpghost.conf';
        } elseif (HMWP_Classes_Tools::isIIS() ) {
            $this->config_file = $this->root_path . 'web.config';
        } elseif (HMWP_Classes_Tools::isApache() || HMWP_Classes_Tools::isLitespeed() ) {
            $this->config_file = $this->root_path . '.htaccess';
        } else {
            $this->config_file = false;
        }
    }

    /**
     * Get the config file
     * @return mixed|void
     */
    public function getConfFile()
    {
        return apply_filters('hmwp_config_file', $this->config_file);
    }

    /**
     * Check if the config file is writable
     *
     * @param string $config_file
     *
     * @return bool
     */
    public function isConfigWritable( $config_file = null )
    {

        //Initialize WordPress Filesystem
        $wp_filesystem = HMWP_Classes_ObjController::initFilesystem();

        //get the global config file if not specified
        if (!isset($config_file) ) {
            $config_file = $this->getConfFile();
        }

        if ($config_file ) {
            //If the config file does not exist
            if (!$wp_filesystem->exists($config_file) ) {
                //can write into directory
                if (!$wp_filesystem->is_writable(dirname($config_file)) ) {
                    return false;
                }
                //can create the file
                if (!$wp_filesystem->touch($config_file) ) {
                    return false;
                }

            } elseif (!$wp_filesystem->is_writable($config_file) ) { //is writable
                return false;
            }
        }

        return true;
    }


    /**
     * Write to config file
     *
     * @param $rules
     * @param string $header
     *
     * @return bool
     * @throws Exception
     */
    public function writeToFile( $rules, $header = 'HMWP_RULES' )
    {
        if ($this->getConfFile() ) {
            if (HMWP_Classes_Tools::isNginx() ) {
                return $this->writeInNginx($rules, $header);
            } elseif (HMWP_Classes_Tools::isIIS() && !HMWP_Classes_Tools::getOption('logout') ) {
                return HMWP_Classes_ObjController::getClass('HMWP_Models_Rewrite')->flushRewrites();
            } elseif (HMWP_Classes_Tools::isApache() || HMWP_Classes_Tools::isLitespeed() ) {
                return $this->writeInHtaccess($rules, $header);
            }
        }

        return false;
    }

    /**
     * Replace text in file
     *
     * @param $old
     * @param $new
     * @param $file
     *
     * @return bool
     */
    public function setReplace( $old, $new, $file )
    {
        if (!$this->isConfigWritable($file) ) {
            return false;
        }

        $found = false;
        $lines = file($file);

        foreach ( (array)$lines as $line ) {
            if (preg_match("/$old/", $line) ) {
                $found = true;
                break;
            }
        }


        if ($found ) {
            $fd = fopen($file, 'w');
            foreach ( (array)$lines as $line ) {
                if (!preg_match("/$old/", $line) ) {
                    fputs($fd, $line);
                } elseif ($new <> '' ) {
                    fputs($fd, $new);
                }
            }
            fclose($fd);
        } elseif ($new <> '' ) {
            $fd = fopen($file, 'w');
            foreach ( (array)$lines as $line ) {
                fputs($fd, $line);

                if (!$found && preg_match('/\$table_prefix/', $line) ) {
                    fputs($fd, $new);
                    $found = true;
                }
            }
            fclose($fd);
        }

        return $found;
    }

    public function writeInNginx( $rules, $header = 'HMWP_RULES' )
    {
        return $this->insertWithMarkers($header, $rules);
    }

    public function writeInHtaccess( $rules, $header = 'HMWP_RULES' )
    {
        if (HMWP_Classes_Tools::isModeRewrite() ) {
            return $this->insertWithMarkers($header, $rules);
        }

        return false;
    }


    /**
     * Inserts an array of strings into a file (.htaccess ), placing it between
     * BEGIN and END markers.
     *
     * Replaces existing marked info. Retains surrounding
     * data. Creates file if none exists.
     *
     * @param string       $marker    The marker to alter.
     * @param array|string $insertion The new content to insert.
     *
     * @return bool True on write success, false on failure.
     */
    public function insertWithMarkers( $marker, $insertion )
    {

        //Initialize WordPress Filesystem
        $wp_filesystem = HMWP_Classes_ObjController::initFilesystem();

        if (!$this->isConfigWritable() ) {
            if (!$this->forceOpenConfigFile($this->getConfFile()) ) {
                return false;
            }
        }

        $start_marker = "# BEGIN $marker";
        $end_marker = "# END $marker";

        if ($insertion == '' ) { //delete the marker if there is no data to add in it

            if (method_exists($wp_filesystem, 'get_contents') && method_exists($wp_filesystem, 'put_contents') ) {
                try {
                    $htaccess = $wp_filesystem->get_contents($this->getConfFile());
                    $htaccess = preg_replace("/$start_marker.*$end_marker/s", "", $htaccess);
                    $htaccess = preg_replace("/\n+/", "\n", $htaccess);
                    $wp_filesystem->put_contents($this->getConfFile(), $htaccess);

                    return true;
                } catch ( Exception $e ) {
                }
            }
        }


        if (!is_array($insertion) ) {
            $insertion = explode("\n", $insertion);
        }

        //open the file only if writable
        if($wp_filesystem->is_writable($this->getConfFile())) {

            $fp = fopen($this->getConfFile(), 'r+');
            if (!$fp) {
                return false;
            }

            // Attempt to get a lock. If the filesystem supports locking, this will block until the lock is acquired.
            flock($fp, LOCK_EX);

            $lines = array();
            while (!feof($fp)) {
                $lines[] = rtrim(fgets($fp), "\r\n");
            }

            // Split out the existing file into the preceding lines, and those that appear after the marker
            $pre_lines = $post_lines = $existing_lines = array();
            $found_marker = $found_end_marker = false;
            foreach ($lines as $line) {
                if (!$found_marker && false !== strpos($line, $start_marker)) {
                    $found_marker = true;
                    continue;
                } elseif (!$found_end_marker && false !== strpos($line, $end_marker)) {
                    $found_end_marker = true;
                    continue;
                }
                if (!$found_marker) {
                    $pre_lines[] = $line;
                } elseif ($found_end_marker) {
                    $post_lines[] = $line;
                } else {
                    $existing_lines[] = $line;
                }
            }

            // Check to see if there was a change
            if ($existing_lines === $insertion) {
                flock($fp, LOCK_UN);
                fclose($fp);

                //Set the chmod back on file close
                $this->closeConfigFile($this->getConfFile());

                return true;
            }

            // Generate the new file data
            if (!$found_marker) {
                $new_file_data = implode(
                    "\n", array_merge(
                        array($start_marker),
                        $insertion,
                        array($end_marker),
                        $pre_lines
                    )
                );
            } else {
                $new_file_data = implode(
                    "\n", array_merge(
                        $pre_lines,
                        array($start_marker),
                        $insertion,
                        array($end_marker),
                        $post_lines
                    )
                );
            }

            // Write to the start of the file, and truncate it to that length
            fseek($fp, 0);
            $bytes = fwrite($fp, $new_file_data);
            if ($bytes) {
                ftruncate($fp, ftell($fp));
            }
            fflush($fp);
            flock($fp, LOCK_UN);
            fclose($fp);

            //Set the chmod back on file close
            $this->closeConfigFile($this->getConfFile());

            return (bool)$bytes;
        }

        return false;
    }

    /**
     * Force opening the file
     *
     * @param $config_file
     *
     * @return bool
     */
    public function forceOpenConfigFile( $config_file )
    {
        $this->config_chmod = false;

        //Initialize WordPress Filesystem
        $wp_filesystem = HMWP_Classes_ObjController::initFilesystem();

        if (!HMWP_Classes_Tools::isWindows() && $wp_filesystem->exists($config_file) ) {

            if (method_exists($wp_filesystem, 'getchmod') && method_exists($wp_filesystem, 'chmod') ) {
                $this->config_chmod = $wp_filesystem->getchmod($config_file);
                $wp_filesystem->chmod($config_file, 0664);

                if (is_writeable($config_file) ) {
                    if (method_exists($wp_filesystem, 'copy') ) {
                        $wp_filesystem->copy($config_file, $config_file . '_bk');
                    }

                    return true;
                }
            }
        }

        return false;
    }

    /**
     *  Set the chmod back on file close
     *
     * @param $config_file
     */
    public function closeConfigFile( $config_file )
    {

        //Initialize WordPress Filesystem
        $wp_filesystem = HMWP_Classes_ObjController::initFilesystem();

        if ($this->config_chmod && isset($wp_filesystem) ) {
            if ($this->config_chmod == '400' ) {
                $wp_filesystem->chmod($config_file, 0400);
            } elseif ($this->config_chmod == '440' ) {
                $wp_filesystem->chmod($config_file, 0440);
            } else {
                $wp_filesystem->chmod($config_file, 0444);
            }
        }
    }

    /**
     * Add rules to protect the website from sql injection
     *
     * @return string
     */
    public function getInjectionRewrite()
    {
        $rules = '';

        if (HMWP_Classes_Tools::isNginx() ) {

            if (HMWP_Classes_Tools::getOption('hmwp_security_header') ) {

                $headers = (array)HMWP_Classes_Tools::getOption('hmwp_security_headers');

                if(!empty($headers)) {
                    foreach ($headers as $name => $value) {
                        if ($value <> '') {
                            $rules .= 'add_header ' . $name . ' "' . str_replace('"', '\"', $value) . '";' . PHP_EOL;
                        }
                    }
                }
            }

            if (HMWP_Classes_Tools::getOption('hmwp_detectors_block') ) {
                $rules .= 'if ( $remote_addr ~ \'35.214.130.87\' ) { return 404; }' . PHP_EOL;
                $rules .= 'if ( $remote_addr ~ \'192.185.4.40\' ) { return 404; }' . PHP_EOL;
                $rules .= 'if ( $remote_addr ~ \'15.235.50.223\' ) { return 404; }' . PHP_EOL;
                $rules .= 'if ( $remote_addr ~ \'172.105.48.130\' ) { return 404; }' . PHP_EOL;
                $rules .= 'if ( $remote_addr ~ \'167.99.233.123\' ) { return 404; }' . PHP_EOL;
                $rules .= 'if ( $http_referer ~ \'wpthemedetector\' ) { return 404; }' . PHP_EOL;
                $rules .= 'if ( $http_user_agent ~ \'builtwith\' ) { return 404; }' . PHP_EOL;
                $rules .= 'if ( $http_user_agent ~ \'isitwp\' ) { return 404; }' . PHP_EOL;
                $rules .= 'if ( $http_user_agent ~ \'wapalyzer\' ) { return 404; }' . PHP_EOL;
                $rules .= 'if ( $http_referer ~ \'mShots\' ) { return 404; }' . PHP_EOL;
                $rules .= 'if ( $http_referer ~ \'WhatCMS\' ) { return 404; }' . PHP_EOL;
            }

        } elseif (HMWP_Classes_Tools::isApache() || HMWP_Classes_Tools::isLitespeed() ) {

            if (HMWP_Classes_Tools::getOption('hmwp_detectors_block') ) {
                $rules .= "<IfModule mod_rewrite.c>" . PHP_EOL;
                $rules .= "RewriteEngine On" . PHP_EOL;
                $rules .= "RewriteCond %{REMOTE_ADDR} ^35.214.130.87$ [NC,OR]" . PHP_EOL;
                $rules .= "RewriteCond %{REMOTE_ADDR} ^192.185.4.40$ [NC,OR]" . PHP_EOL;
                $rules .= "RewriteCond %{REMOTE_ADDR} ^15.235.50.223$ [NC,OR]" . PHP_EOL;
                $rules .= "RewriteCond %{REMOTE_ADDR} ^172.105.48.130$ [NC,OR]" . PHP_EOL;
                $rules .= "RewriteCond %{REMOTE_ADDR} ^167.99.233.123$ [NC,OR]" . PHP_EOL;
                $rules .= "RewriteCond %{HTTP_USER_AGENT} (wpthemedetector|builtwith|isitwp|wapalyzer|mShots|WhatCMS|gochyu|wpdetector|scanwp) [NC]" . PHP_EOL;
                $rules .= "RewriteRule ^(.*)$ - [L,R=404]" . PHP_EOL;
                $rules .= "</IfModule>" . PHP_EOL . PHP_EOL;
            }

            if (HMWP_Classes_Tools::getOption('hmwp_hide_unsafe_headers')) {
                $rules .= "<IfModule mod_headers.c>" . PHP_EOL;
                $rules .= 'Header always unset x-powered-by' . PHP_EOL;
	            $rules .= 'Header always unset server' . PHP_EOL;
	            $rules .= 'ServerSignature Off' . PHP_EOL;
                $rules .= "</IfModule>" . PHP_EOL . PHP_EOL;
            }

            if (HMWP_Classes_Tools::getOption('hmwp_security_header') ) {

                $headers = (array)HMWP_Classes_Tools::getOption('hmwp_security_headers');

                if(!empty($headers)) {
                    $rules .= "<IfModule mod_headers.c>" . PHP_EOL;

                    foreach ($headers as $name => $value) {
                        if ($value <> '') {
                            $rules .= 'Header set ' . $name . ' "' . str_replace('"', '\"', $value) . '"' . PHP_EOL;
                        }
                    }

                    $rules .= "</IfModule>" . PHP_EOL . PHP_EOL;
                }
            }

        }

        // Add in the rules
	    if($rules <> '') {
		    return $rules . PHP_EOL;
	    }

		return '';
    }

    /**
     * Check if the ADMIN_COOKIE_PATH is present in wp-config.php
     *
     * @return bool
     */
    public function isConfigAdminCookie()
    {
        $config_file = HMWP_Classes_Tools::getConfigFile();

        $lines = file($config_file);

        foreach ( (array)$lines as $line ) {
            if (preg_match("/ADMIN_COOKIE_PATH/", $line) && !preg_match("/^\/\//", trim($line)) ) {
                return true;
            }
        }

        return false;
    }


}
