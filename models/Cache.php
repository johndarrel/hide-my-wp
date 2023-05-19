<?php
/**
 * Cache Model
 *
 * @file  The Cache file
 * @package HMWP/CacheModel
 * @since 5.0.0
 */
defined('ABSPATH') || die('Cheatin\' uh?');

class HMWP_Models_Cache
{

    protected $_replace = array();
    protected $_cachepath = '';
    protected $chmod = 644;

    public function __construct()
    {
        $this->setCachePath(WP_CONTENT_DIR . '/cache/');

    }

    /**
     * Set the Cache Path
     *
     * @param $path
     */
    public function setCachePath( $path )
    {
        $this->_cachepath = $path;
    }

    /**
     * Get the cache path
     *
     * @return string
     */
    public function getCachePath()
    {

        //Initialize WordPress Filesystem
        $wp_filesystem = HMWP_Classes_ObjController::initFilesystem();

        //Get the website cache path
        $path = $this->_cachepath;

        if (HMWP_Classes_Tools::isMultisites() ) {
            if ($wp_filesystem->is_dir($path . get_current_blog_id() . '/') ) {
                $path .= get_current_blog_id() . '/';
            }
        }

        if (!$wp_filesystem->is_dir($path) ) {
            return false;
        }

        return $path;
    }

    /**
     * Build the redirects array
     *
     * @throws Exception
     */
    public function buildRedirect()
    {

        //If the replacement was not already set
        if(empty($this->_replace)) {

            /**
             * The Rewrite Model
             *
             * @var HMWP_Models_Rewrite $rewriteModel
             */
            $rewriteModel = HMWP_Classes_ObjController::getClass('HMWP_Models_Rewrite');

            //build the rules paths to change back the hidden paths
            if (!isset($rewriteModel->_replace['from']) && !isset($rewriteModel->_replace['to'])) {
                $rewriteModel->buildRedirect();

	            //add the domain to rewrites if not multisite
	            if (HMWP_Classes_Tools::getOption('hmwp_fix_relative') && !HMWP_Classes_Tools::isMultisites()) {
		            $rewriteModel->prepareFindReplace();
	            }
            }

            //Verify only the rewrites
            if (isset($rewriteModel->_replace['from']) && isset($rewriteModel->_replace['to']) && !empty($rewriteModel->_replace['from']) && !empty($rewriteModel->_replace['to'])) {
                if (!empty($rewriteModel->_replace['rewrite'])) {
                    foreach ($rewriteModel->_replace['rewrite'] as $index => $value) {
                        //add only the paths or the design path
                        if ((isset($rewriteModel->_replace['to'][$index]) && substr($rewriteModel->_replace['to'][$index], -1) == '/') 
                            || strpos($rewriteModel->_replace['to'][$index], '/' . HMWP_Classes_Tools::getOption('hmwp_themes_style'))
                        ) {
                            $this->_replace['from'][] = $rewriteModel->_replace['from'][$index];
                            $this->_replace['to'][] = $rewriteModel->_replace['to'][$index];
                        }
                    }
                }

                //add the domain to rewrites
                if (HMWP_Classes_Tools::getOption('hmwp_fix_relative')) {
                    $this->_replace['from'] = array_map(array($rewriteModel, 'addDomainUrl'), (array)$this->_replace['from']);
                    $this->_replace['to'] = array_map(array($rewriteModel, 'addDomainUrl'), (array)$this->_replace['to']);
                }
            }
        }

    }

    /**
     * Replace the paths in CSS files
     *
     * @throws Exception
     */
    public function changePathsInCss()
    {
        if (HMWP_Classes_Tools::getOption('error') ) {
            return;
        }

        try {
            if ($this->getCachePath() ) {

                $cssfiles = $this->rsearch($this->getCachePath() . '*.css');

                if (!empty($cssfiles) ) {

                    //load the redirects into array
                    $this->buildRedirect();

                    foreach ( $cssfiles as $file ) {
                        //only if the file is writable
                        if (!$content = $this->readFile($file) ) {
                            continue;
                        }

	                    //find replace the content
	                    $newcontent = $this->findReplace($content);
	                    if($newcontent <> $content){
		                    //echo $newcontent;exit();
		                    //write into file
		                    $this->writeFile($file, $newcontent);
	                    }

                    }
                }
            }

        } catch ( Exception $e ) {
        }
    }

    /**
     * Replace the paths inHTML files
     *
     * @return void
     */
    public function changePathsInJs()
    {
        if (HMWP_Classes_Tools::getOption('error') ) {
            return;
        }

        try {
            if ($this->getCachePath() ) {

                $jsfiles = $this->rsearch($this->getCachePath() . '*.js');

                if (!empty($jsfiles) ) {

                    //load the redirects into array
                    $this->buildRedirect();

                    foreach ( $jsfiles as $file ) {

                        //only if the file is writable
                        if (!$content = $this->readFile($file) ) {
                            continue;
                        }

	                    //find replace the content
	                    $newcontent = $this->findReplace($content);
	                    if($newcontent <> $content){
		                    //echo $newcontent;exit();
		                    //write into file
		                    $this->writeFile($file, $newcontent);
	                    }
                    }
                }
            }
        } catch ( Exception $e ) {
            //echo $e->getMessage();exit();
        }
    }

    /**
     * Replace the paths inHTML files
     *
     * @return void
     */
    public function changePathsInHTML()
    {

        //Initialize WordPress Filesystem
        $wp_filesystem = HMWP_Classes_ObjController::initFilesystem();

        if (HMWP_Classes_Tools::getOption('error') ) {
            return;
        }

        try {
            if ($this->getCachePath() ) {
                $htmlfiles = $this->rsearch($this->getCachePath() . '*.html');

                if (!empty($htmlfiles) ) {

                    //load the redirects into array
                    $this->buildRedirect();

                    /**
                     * The Rewrite Model
                     *
                     * @var HMWP_Models_Rewrite $rewriteModel
                     */
                    $rewriteModel = HMWP_Classes_ObjController::getClass('HMWP_Models_Rewrite');

                    foreach ( $htmlfiles as $file ) {
                        //only if the file is writable
                        if (!$wp_filesystem->is_writable($file) ) {
                            continue;
                        }

                        //get the file content
                        $content = $wp_filesystem->get_contents($file);

	                    //find replace the content
	                    $newcontent = $this->findReplace($content);
	                    if($newcontent <> $content){
		                    //echo $newcontent;exit();
		                    //write into file
		                    $this->writeFile($file, $newcontent);
	                    }
                    }
                }
            }
        } catch ( Exception $e ) {
        }
    }

    /**
     * Find and replace the old paths into files
     *
     * @param string $content
     * @return string|string[]|null
     * @throws Exception
     */
    public function findReplace( $content )
    {

        //If there are replaced paths
        if(!empty($this->_replace) && isset($this->_replace['from']) && isset($this->_replace['to'])) {

            //if there is content in the file
            if ($content <> '') {
                //if the file has unchanged paths
                if (strpos($content, HMWP_Classes_Tools::$default['hmwp_admin_url']) !== false 
                    || strpos($content, HMWP_Classes_Tools::$default['hmwp_wp-content_url']) !== false 
                    || strpos($content, HMWP_Classes_Tools::$default['hmwp_wp-includes_url']) !== false
                ) {

                    //fix the relative links before
                    if (HMWP_Classes_Tools::getOption('hmwp_fix_relative')) {
                        $content = HMWP_Classes_ObjController::getClass('HMWP_Models_Rewrite')->fixRelativeLinks($content);
                    }

                    $content = str_ireplace($this->_replace['from'], $this->_replace['to'], $content);

                }

                //Text Mapping for all css files - Experimental
                if (HMWP_Classes_Tools::getOption('hmwp_mapping_file')) {
                    $hmwp_text_mapping = json_decode(HMWP_Classes_Tools::getOption('hmwp_text_mapping'), true);
                    if (isset($hmwp_text_mapping['from']) && !empty($hmwp_text_mapping['from'])
                        && isset($hmwp_text_mapping['to']) && !empty($hmwp_text_mapping['to'])
                    ) {

                        //only classes & ids
                        if (HMWP_Classes_Tools::getOption('hmwp_mapping_classes')) {

                            foreach ($hmwp_text_mapping['from'] as $index => $from) {
                                if (strpos($content, $from) !== false) {
                                    $content = preg_replace("'(?:([^/])" . addslashes($from) . "([^/]))'is", '$1' . $hmwp_text_mapping['to'][$index] . '$2', $content);
                                }
                            }

                        } else {
                            $content = str_ireplace($hmwp_text_mapping['from'], $hmwp_text_mapping['to'], $content);
                        }
                    }

                }
            }
        }

        return $content;
    }

    /**
     * Get the files paths by extension
     *
     * @param string $pattern
     * @param int $flags
     *
     * @return array
     */
    public function rsearch( $pattern, $flags = 0 )
    {
        $files = array();

        if (function_exists('glob') ) {
            $files = glob($pattern, $flags);
            foreach ( glob(dirname($pattern) . '/*', GLOB_ONLYDIR | GLOB_NOSORT) as $dir ) {
                $files = array_merge($files, $this->rsearch($dir . '/' . basename($pattern), $flags));
            }
        }

        return $files;
    }

    /**
     * Read the file content
     *
     * @param string $file
     *
     * @return bool
     */
    public function readFile( $file )
    {

        //Initialize WordPress Filesystem
        $wp_filesystem = HMWP_Classes_ObjController::initFilesystem();

        if ($wp_filesystem->is_writable($file) ) {
            return $wp_filesystem->get_contents($file);
        }

        return false;
    }

    /**
     * Write the file content
     *
     * @param string $file
     * @param string $content
     * @return void
     */
    public function writeFile( $file, $content )
    {

        //Initialize WordPress Filesystem
        $wp_filesystem = HMWP_Classes_ObjController::initFilesystem();

        if ($wp_filesystem->is_writable($file) ) {
            $wp_filesystem->put_contents($file, $content);
        }

    }

}
