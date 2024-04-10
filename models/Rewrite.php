<?php
/**
 * Rewrite Model
 * Called to handle the rewrites and to change the paths
 *
 * @file  The Rewrite Model file
 * @package HMWP/RewriteModel
 * @since 4.0.0
 */

defined('ABSPATH') || die('Cheatin\' uh?');

class HMWP_Models_Rewrite
{
    /**
     * All the paths that need to be changed
     *
     * @var array
     */
    public $_replace = array();
    public $paths;
    //
	protected $_rewrites = array();
	protected $_umrewrites = array();

    /**
     * Triggered after the paths are changed
     * @var bool
     */
    protected $_replaced;

    /**
     * The current website domain
     *
     * @var string
     */
    protected $_siteurl = '';
    protected $_pass;
    //
    /**
     * Text Mapping
     * @var array
     */
    protected $_findtextmapping = array();
    protected $_replacetextmapping = array();

    /**
     * HMWP_Models_Rewrite constructor.
     */
    public function __construct()
    {
        //Get the current site URL
        $siteurl = site_url();

        //Set the blog URL
        $this->_siteurl = str_replace('www.', '', parse_url($siteurl, PHP_URL_HOST) . parse_url($siteurl, PHP_URL_PATH));

        //Add the PORT if different from 80
        if(parse_url($siteurl, PHP_URL_PORT) && parse_url($siteurl, PHP_URL_PORT) <> 80) {
            $this->_siteurl = str_replace('www.', '', parse_url($siteurl, PHP_URL_HOST) . ':' . parse_url($siteurl, PHP_URL_PORT) . parse_url($siteurl, PHP_URL_PATH));
        }

    }

    /**
     * Get the blog URL with path & port
     *
     * @return string
     */
    public function getSiteUrl()
    {
        return $this->_siteurl;
    }

    /**
     * Start the buffer listener
     *
     * @throws Exception
     */
    public function startBuffer()
    {

        if (apply_filters('hmwp_start_buffer', true) ) {

            //start the buffer only for non files or 404 pages
            ob_start(array($this, 'getBuffer'));

        }

    }

    /**
     * Modify the output buffer
     * Only text/html header types
     *
     * @param $buffer
     *
     * @return mixed
     * @throws Exception
     */
    public function getBuffer( $buffer )
    {

        //If ajax call
        if (HMWP_Classes_Tools::isAjax()  ) {

            //if change the ajax paths
            if(HMWP_Classes_Tools::getOption('hmwp_hideajax_paths')) {
	            //replace the buffer in Ajax
	            $buffer = $this->find_replace($buffer);
            }

        } else {

            //////////////////////////////////////
            //Should the buffer be loaded
            if (apply_filters('hmwp_process_buffer', true) ) {

                //Don't run HMWP in these cases
                if (HMWP_Classes_Tools::getOption('hmwp_mode') <> 'default' ) { //If it's not the disabled

                    //If there is no buffer
                    if (strlen($buffer) < 255 ) { return $buffer;
                    }

                    //Check if other plugins already did the cache
                    try {

                        //If the content is HTML
                        if (HMWP_Classes_Tools::isContentHeader(array('text/html')) ) {
                            //If the user set to change the paths for logged users
                            $buffer = $this->find_replace($buffer);
                        }

                    } catch ( Exception $e ) {
                        return $buffer;
                    }
                }

            }
        }

        //Return the buffer to HTML
        return apply_filters('hmwp_buffer', $buffer);
    }

    /************************************
     *
     * BUID & FLUSH REWRITES
     ****************************************/
    /**
     * Prepare redirect build
     *
     * @return $this
     */
    public function clearRedirect()
    {
        HMWP_Classes_Tools::$options = HMWP_Classes_Tools::getOptions();
        $this->_replace = array();

        return $this;
    }

    /**
     * Build the array with find and replace
     * Decide what goes to htaccess and not
     *
     * @return $this
     */
    public function buildRedirect()
    {

        if (!empty($this->_replace) ) {
            return $this;
        }

	    add_action('home_url', array($this, 'home_url'), PHP_INT_MAX, 1);


	    if (HMWP_Classes_Tools::getOption('hmwp_mode') <> 'default' ) {
            if (HMWP_Classes_Tools::isMultisiteWithPath() ) {
                //get all blogs
                global $wpdb;
                $this->paths = array();

	            $blogs = get_sites( array( 'number'  => 10000, 'public'  => 1, 'deleted' => 0, ) );
                foreach ( $blogs as $blog ) {
                    $this->paths[] = HMWP_Classes_Tools::getRelativePath($blog->path);
                }
            }

            //Redirect the AJAX
            if (HMWP_Classes_Tools::$default['hmwp_admin_url'] . '/' . HMWP_Classes_Tools::$default['hmwp_admin-ajax_url'] <> HMWP_Classes_Tools::getOption('hmwp_admin-ajax_url')
                && HMWP_Classes_Tools::$default['hmwp_admin-ajax_url'] <> HMWP_Classes_Tools::getOption('hmwp_admin-ajax_url')
            ) {
                $this->_replace['from'][] = HMWP_Classes_Tools::$default['hmwp_admin_url'] . '/' . HMWP_Classes_Tools::$default['hmwp_admin-ajax_url'];
                $this->_replace['to'][] = HMWP_Classes_Tools::getOption('hmwp_admin-ajax_url');
                $this->_replace['rewrite'][] = true;

                $this->_replace['from'][] = HMWP_Classes_Tools::getOption('hmwp_admin_url') . '/' . HMWP_Classes_Tools::$default['hmwp_admin-ajax_url'];
                $this->_replace['to'][] = HMWP_Classes_Tools::getOption('hmwp_admin-ajax_url');
                $this->_replace['rewrite'][] = false;
            }

            //Redirect the ADMIN
            if (HMWP_Classes_Tools::$default['hmwp_admin_url'] <> HMWP_Classes_Tools::getOption('hmwp_admin_url') ) {
                $safeoptions = HMWP_Classes_Tools::getOptions(true);
                if (HMWP_Classes_Tools::$default['hmwp_admin_url'] <> $safeoptions['hmwp_admin_url'] ) {
                    $this->_replace['from'][] = "wp-admin" . '/';
                    $this->_replace['to'][] = $safeoptions['hmwp_admin_url'] . '/';
                    $this->_replace['rewrite'][] = true;
                }
                if (HMWP_Classes_Tools::getOption('hmwp_admin_url') <> $safeoptions['hmwp_admin_url'] ) {
                    $this->_replace['from'][] = "wp-admin" . '/';
                    $this->_replace['to'][] = HMWP_Classes_Tools::getOption('hmwp_admin_url') . '/';
                    $this->_replace['rewrite'][] = true;
                }
            }


            //Redirect the LOGIN
            if (HMWP_Classes_Tools::$default['hmwp_login_url'] <> HMWP_Classes_Tools::getOption('hmwp_login_url') ) {
                $this->_replace['from'][] = "wp-login.php";
                $this->_replace['to'][] = HMWP_Classes_Tools::getOption('hmwp_login_url');
                $this->_replace['rewrite'][] = true;

                $this->_replace['from'][] = "wp-login.php";
                $this->_replace['to'][] = HMWP_Classes_Tools::getOption('hmwp_login_url') . '/';
                $this->_replace['rewrite'][] = true;
            }

            if (HMWP_Classes_Tools::getOption('hmwp_lostpassword_url') <> '' ) {
                $this->_replace['from'][] = HMWP_Classes_Tools::getOption('hmwp_login_url') . "?action=lostpassword";
                $this->_replace['to'][] = HMWP_Classes_Tools::getOption('hmwp_lostpassword_url');
                $this->_replace['rewrite'][] = false;

                $this->_replace['from'][] = HMWP_Classes_Tools::$default['hmwp_login_url'] . "?action=lostpassword";
                $this->_replace['to'][] = HMWP_Classes_Tools::getOption('hmwp_lostpassword_url');
                $this->_replace['rewrite'][] = true;
            }

            if (HMWP_Classes_Tools::$default['hmwp_activate_url'] <> HMWP_Classes_Tools::getOption('hmwp_activate_url') ) {
                if (HMWP_Classes_Tools::getOption('hmwp_activate_url') <> '' ) {
                    $this->_replace['from'][] = HMWP_Classes_Tools::$default['hmwp_activate_url'];
                    $this->_replace['to'][] = HMWP_Classes_Tools::getOption('hmwp_activate_url');
                    $this->_replace['rewrite'][] = true;
                }
            }

            if (HMWP_Classes_Tools::getOption('hmwp_register_url') <> '' ) {
                $this->_replace['from'][] = HMWP_Classes_Tools::getOption('hmwp_login_url') . "?action=register";
                $this->_replace['to'][] = HMWP_Classes_Tools::getOption('hmwp_register_url');
                $this->_replace['rewrite'][] = false;

                $this->_replace['from'][] = HMWP_Classes_Tools::$default['hmwp_login_url'] . "?action=register";
                $this->_replace['to'][] = HMWP_Classes_Tools::getOption('hmwp_register_url');
                $this->_replace['rewrite'][] = true;
            }

            if (HMWP_Classes_Tools::getOption('hmwp_logout_url') <> '' ) {
                $this->_replace['from'][] = HMWP_Classes_Tools::getOption('hmwp_login_url') . "?action=logout";
                $this->_replace['to'][] = HMWP_Classes_Tools::getOption('hmwp_logout_url');
                $this->_replace['rewrite'][] = false;

                $this->_replace['from'][] = HMWP_Classes_Tools::$default['hmwp_login_url'] . "?action=logout";
                $this->_replace['to'][] = HMWP_Classes_Tools::getOption('hmwp_logout_url');
                $this->_replace['rewrite'][] = true;
            }

            //Modify plugins urls
            if (HMWP_Classes_Tools::getOption('hmwp_hide_plugins') ) {
                $all_plugins = HMWP_Classes_Tools::getOption('hmwp_plugins');

                if (!empty($all_plugins['to']) ) {
                    foreach ( $all_plugins['to'] as $index => $plugin_path ) {
                        if (HMWP_Classes_Tools::isMultisiteWithPath() ) {
                            foreach ( $this->paths as $path ) {
                                $this->_replace['from'][] = $path . HMWP_Classes_Tools::$default['hmwp_plugin_url'] . '/' . $all_plugins['from'][$index];
                                $this->_replace['to'][] = $path . HMWP_Classes_Tools::getOption('hmwp_plugin_url') . '/' . $plugin_path . '/';
                                $this->_replace['rewrite'][] = false;
                            }
                        }

                        $this->_replace['from'][] = HMWP_Classes_Tools::$default['hmwp_plugin_url'] . '/' . $all_plugins['from'][$index];
                        $this->_replace['to'][] = HMWP_Classes_Tools::getOption('hmwp_plugin_url') . '/' . $plugin_path . '/';
                        $this->_replace['rewrite'][] = true;
                    }
                }
            }
            //Modify plugins
            if (HMWP_Classes_Tools::$default['hmwp_plugin_url'] <> HMWP_Classes_Tools::getOption('hmwp_plugin_url') ) {
                if (HMWP_Classes_Tools::isMultisiteWithPath() ) {
                    foreach ( $this->paths as $path ) {
                        $this->_replace['from'][] = $path . HMWP_Classes_Tools::$default['hmwp_plugin_url'] . '/';
                        $this->_replace['to'][] = $path .HMWP_Classes_Tools::getOption('hmwp_plugin_url') . '/';
                        $this->_replace['rewrite'][] = false;
                    }
                }
                $this->_replace['from'][] = HMWP_Classes_Tools::$default['hmwp_plugin_url'] . '/';
                $this->_replace['to'][] = HMWP_Classes_Tools::getOption('hmwp_plugin_url') . '/';
                $this->_replace['rewrite'][] = true;

            }

            //Modify themes urls
            if (HMWP_Classes_Tools::getOption('hmwp_hide_themes') ) {
                $all_themes = HMWP_Classes_Tools::getOption('hmwp_themes');

                if (!empty($all_themes['to']) ) {
                    foreach ( $all_themes['to'] as $index => $theme_path ) {
                        if (HMWP_Classes_Tools::isMultisiteWithPath() ) {
                            foreach ( $this->paths as $path ) {
                                $this->_replace['from'][] = $path . HMWP_Classes_Tools::$default['hmwp_wp-content_url'] . '/' . HMWP_Classes_Tools::$default['hmwp_themes_url'] . '/' . $all_themes['from'][$index];
                                $this->_replace['to'][] = $path . HMWP_Classes_Tools::getOption('hmwp_themes_url') . '/' . $theme_path . '/';
                                $this->_replace['rewrite'][] = false;

                                $this->_replace['from'][] = $path . HMWP_Classes_Tools::$default['hmwp_wp-content_url'] . '/' . HMWP_Classes_Tools::$default['hmwp_themes_url'] . '/' . $all_themes['from'][$index] . HMWP_Classes_Tools::$default['hmwp_themes_style'];
                                $this->_replace['to'][] = $path . HMWP_Classes_Tools::getOption('hmwp_themes_url') . '/' . $theme_path . '/' . HMWP_Classes_Tools::getOption('hmwp_themes_style');
                                $this->_replace['rewrite'][] = false;
                            }
                        }


                        if (HMWP_Classes_Tools::$default['hmwp_themes_style'] <> HMWP_Classes_Tools::getOption('hmwp_themes_style') ) {
                            $this->_replace['from'][] = HMWP_Classes_Tools::$default['hmwp_wp-content_url'] . '/' . HMWP_Classes_Tools::$default['hmwp_themes_url'] . '/' . $all_themes['from'][$index] . HMWP_Classes_Tools::$default['hmwp_themes_style'];
                            $this->_replace['to'][] = HMWP_Classes_Tools::getOption('hmwp_themes_url') . '/' . $theme_path . '/' . HMWP_Classes_Tools::getOption('hmwp_themes_style');
                            $this->_replace['rewrite'][] = true;

                            $this->_replace['from'][] = HMWP_Classes_Tools::getOption('hmwp_themes_url') . '/' . $theme_path . '/' . HMWP_Classes_Tools::$default['hmwp_themes_style'];
                            $this->_replace['to'][] = HMWP_Classes_Tools::getOption('hmwp_themes_url') . '/' . $theme_path . '/' . HMWP_Classes_Tools::getOption('hmwp_themes_style');
                            $this->_replace['rewrite'][] = false;
                        }

                        $this->_replace['from'][] = HMWP_Classes_Tools::$default['hmwp_wp-content_url'] . '/' . HMWP_Classes_Tools::$default['hmwp_themes_url'] . '/' . $all_themes['from'][$index];
                        $this->_replace['to'][] = HMWP_Classes_Tools::getOption('hmwp_themes_url') . '/' . $theme_path . '/';
                        $this->_replace['rewrite'][] = true;
                    }

                }
            }

            //Modify theme URL
            if (HMWP_Classes_Tools::$default['hmwp_themes_url'] <> HMWP_Classes_Tools::getOption('hmwp_themes_url') ) {
                if (HMWP_Classes_Tools::isMultisiteWithPath() ) {
                    foreach ( $this->paths as $path ) {
                        $this->_replace['from'][] = $path . HMWP_Classes_Tools::$default['hmwp_wp-content_url'] . '/' . HMWP_Classes_Tools::$default['hmwp_themes_url'] . '/';
                        $this->_replace['to'][] = $path . HMWP_Classes_Tools::getOption('hmwp_themes_url') . '/';
                        $this->_replace['rewrite'][] = false;
                    }
                }

                $this->_replace['from'][] = HMWP_Classes_Tools::$default['hmwp_wp-content_url'] . '/' . HMWP_Classes_Tools::$default['hmwp_themes_url'] . '/';
                $this->_replace['to'][] = HMWP_Classes_Tools::getOption('hmwp_themes_url') . '/';
                $this->_replace['rewrite'][] = true;

            }

            //Modify uploads
            if (!defined('UPLOADS') ) {
                if (HMWP_Classes_Tools::$default['hmwp_upload_url'] <> HMWP_Classes_Tools::getOption('hmwp_upload_url') ) {
                    if (HMWP_Classes_Tools::isMultisiteWithPath() ) {
                        foreach ( $this->paths as $path ) {
                            $this->_replace['from'][] = $path . HMWP_Classes_Tools::$default['hmwp_wp-content_url'] . '/' . HMWP_Classes_Tools::$default['hmwp_upload_url'] . '/';
                            $this->_replace['to'][] = $path . HMWP_Classes_Tools::getOption('hmwp_upload_url') . '/';
                            $this->_replace['rewrite'][] = false;
                        }
                    }

                    $this->_replace['from'][] = HMWP_Classes_Tools::$default['hmwp_wp-content_url'] . '/' . HMWP_Classes_Tools::$default['hmwp_upload_url'] . '/';
                    $this->_replace['to'][] = HMWP_Classes_Tools::getOption('hmwp_upload_url') . '/';
                    $this->_replace['rewrite'][] = true;

                }
            }

            //Modify hmwp_wp-content_url
            if (HMWP_Classes_Tools::$default['hmwp_wp-content_url'] <> HMWP_Classes_Tools::getOption('hmwp_wp-content_url') ) {
                if (HMWP_Classes_Tools::isMultisiteWithPath() ) {
                    foreach ( $this->paths as $path ) {
                        $this->_replace['from'][] = $path . HMWP_Classes_Tools::$default['hmwp_wp-content_url'] . '/';
                        $this->_replace['to'][] = $path . HMWP_Classes_Tools::getOption('hmwp_wp-content_url') . '/';
                        $this->_replace['rewrite'][] = false;
                    }
                }

                $this->_replace['from'][] = HMWP_Classes_Tools::$default['hmwp_wp-content_url'] . '/';
                $this->_replace['to'][] = HMWP_Classes_Tools::getOption('hmwp_wp-content_url') . '/';
                $this->_replace['rewrite'][] = true;
            }

            //Modify hmwp_wp-includes_url
            if (HMWP_Classes_Tools::$default['hmwp_wp-includes_url'] <> HMWP_Classes_Tools::getOption('hmwp_wp-includes_url') ) {
                if (HMWP_Classes_Tools::isMultisiteWithPath() ) {
                    foreach ( $this->paths as $path ) {
                        $this->_replace['from'][] = $path . HMWP_Classes_Tools::$default['hmwp_wp-includes_url'] . '/';
                        $this->_replace['to'][] = $path . HMWP_Classes_Tools::getOption('hmwp_wp-includes_url') . '/';
                        $this->_replace['rewrite'][] = false;
                    }
                }

                $this->_replace['from'][] = HMWP_Classes_Tools::$default['hmwp_wp-includes_url'] . '/';
                $this->_replace['to'][] = HMWP_Classes_Tools::getOption('hmwp_wp-includes_url') . '/';
                $this->_replace['rewrite'][] = true;

            }

            //Modify wp-comments-post
            if (HMWP_Classes_Tools::$default['hmwp_wp-comments-post'] <> HMWP_Classes_Tools::getOption('hmwp_wp-comments-post') ) {
                $this->_replace['from'][] = HMWP_Classes_Tools::$default['hmwp_wp-comments-post'];
                $this->_replace['to'][] = HMWP_Classes_Tools::getOption('hmwp_wp-comments-post') . '/';
                $this->_replace['rewrite'][] = true;
            }

            //Modify the author link
            if (HMWP_Classes_Tools::$default['hmwp_author_url'] <> HMWP_Classes_Tools::getOption('hmwp_author_url') ) {
                $this->_replace['from'][] = HMWP_Classes_Tools::$default['hmwp_author_url'] . '/';
                $this->_replace['to'][] = HMWP_Classes_Tools::getOption('hmwp_author_url') . '/';
                $this->_replace['rewrite'][] = true;
            }


            if (HMWP_Classes_Tools::$default['hmwp_wp-json'] <> HMWP_Classes_Tools::getOption('hmwp_wp-json') ) {
                $this->_replace['from'][] = HMWP_Classes_Tools::$default['hmwp_wp-json'] . '/';
                $this->_replace['to'][] = HMWP_Classes_Tools::getOption('hmwp_wp-json') . '/';
                $this->_replace['rewrite'][] = true;
            }

        }

        return $this;

    }

    /**
     * Rename all the plugin names with a hash
     */
    public function hidePluginNames()
    {
        $dbplugins = array();

        $all_plugins = HMWP_Classes_Tools::getAllPlugins();

        foreach ( $all_plugins as $plugin ) {
            $dbplugins['to'][] = substr(md5($plugin), 0, 10);
            $dbplugins['from'][] = str_replace(' ', '+', plugin_dir_path($plugin));
        }

        HMWP_Classes_Tools::saveOptions('hmwp_plugins', $dbplugins);
    }

    /**
     * Rename all the themes name with a hash
     */
    public function hideThemeNames()
    {
        $dbthemes = array();

        //Initialize WordPress Filesystem
        $wp_filesystem = HMWP_Classes_ObjController::initFilesystem();

        $all_themes = HMWP_Classes_Tools::getAllThemes();

        foreach ( $all_themes as $theme => $value ) {
            if ($wp_filesystem->is_dir($value['theme_root']) ) {
                $dbthemes['to'][] = substr(md5($theme), 0, 10);
                $dbthemes['from'][] = str_replace(' ', '+', $theme) . '/';
            }
        }

        HMWP_Classes_Tools::saveOptions('hmwp_themes', $dbthemes);
    }

    /**
     * ADMIN_PATH is the new path and set in /config.php
     *
     * @return $this
     * @throws Exception
     */
    public function setRewriteRules()
    {
        $this->_rewrites = array();
	    $this->_umrewrites = array();
        include_once ABSPATH . 'wp-admin/includes/misc.php';
        include_once ABSPATH . 'wp-admin/includes/file.php';

		//get home pahe
	    $home_root = HMWP_Classes_Tools::getHomeRootPath();

	    //Build the redirects
        $this->buildRedirect();

        if (!empty($this->_replace) ) {
            //form the IIS rewrite call getIISRules
            if (HMWP_Classes_Tools::isIIS() ) {

                add_filter(
                    'hmwp_iis_hide_files_rules', array(
                    HMWP_Classes_ObjController::getClass('HMWP_Models_Rules'),
                    'getInjectionRewrite'
                    )
                );

                add_filter('iis7_url_rewrite_rules', array($this, 'getIISRules'));

            } else {
	            //URL Mapping
	            $hmwp_url_mapping = json_decode(HMWP_Classes_Tools::getOption('hmwp_url_mapping'), true);
	            if (isset($hmwp_url_mapping['from']) && !empty($hmwp_url_mapping['from']) ) {
		            foreach ( $hmwp_url_mapping['from'] as $index => $row ) {
			            if (substr($hmwp_url_mapping['from'][$index], -1) == '/' ) {
				            $this->_umrewrites[] = array(
					            'from' => '([_0-9a-zA-Z-]+/)?' . str_replace(home_url() . '/', '', ltrim($hmwp_url_mapping['to'][$index], '/')) . '(.*)',
					            'to' => $home_root . str_replace(home_url() . '/', '', ltrim($hmwp_url_mapping['from'][$index], '/')) . "$" . (substr_count($hmwp_url_mapping['from'][$index], '(') + 2),
				            );
			            } else {
				            $this->_umrewrites[] = array(
					            'from' => '([_0-9a-zA-Z-]+/)?' . str_replace(home_url() . '/', '', ltrim($hmwp_url_mapping['to'][$index], '/')) . '$',
					            'to' => $home_root .  str_replace(home_url() . '/', '', ltrim($hmwp_url_mapping['from'][$index], '/')),
				            );
			            }
		            }
	            }

	            if (HMW_RULES_IN_CONFIG ) { //if set to add the HMW rules into config file

		            foreach ( $this->_replace['to'] as $key => $row ) {
			            if ($this->_replace['rewrite'][$key] ) {
				            if(HMWP_Classes_Tools::isDifferentWPContentPath() && strpos($this->_replace['from'][$key], HMWP_Classes_Tools::getDefault('hmwp_wp-content_url')) !== false){
					            $this->_rewrites[] = array(
						            'from' => '([_0-9a-zA-Z-]+/)?' . $this->_replace['to'][$key] . (substr($this->_replace['to'][$key], -1) == '/' ? "(.*)" : "$"),
						            'to' => '/' . $this->_replace['from'][$key] . (substr($this->_replace['to'][$key], -1) == '/' ? "$" . (substr_count($this->_replace['to'][$key], '(') + 2) : ""),
					            );
				            }else{
					            $this->_rewrites[] = array(
						            'from' => '([_0-9a-zA-Z-]+/)?' . $this->_replace['to'][$key] . (substr($this->_replace['to'][$key], -1) == '/' ? "(.*)" : "$"),
						            'to' => $home_root . $this->_replace['from'][$key] . (substr($this->_replace['to'][$key], -1) == '/' ? "$" . (substr_count($this->_replace['to'][$key], '(') + 2) : ""),
					            );
				            }
			            }
		            }
	            }

	            //if set to add the HMW rules into WP rules area
	            if ( HMWP_Classes_Tools::getOption('hmwp_rewrites_in_wp_rules') ) {
		            foreach ( $this->_rewrites as $rewrite ) {

			            if (substr($rewrite['to'] , 0, strlen($home_root)) === $home_root) {
				            $rewrite['to']  = substr($rewrite['to'] , strlen($home_root));
			            }

			            add_rewrite_rule($rewrite['from'], $rewrite['to'], 'top');
		            }
	            }
            }
        }

        //Hook the rewrites rules
	    $this->_umrewrites = apply_filters('hmwp_umrewrites', $this->_umrewrites);
	    $this->_rewrites = apply_filters('hmwp_rewrites', $this->_rewrites);

        return $this;
    }

    /********
     *
     * IIS
     **********/
    /**
     * @param string $wrules
     *
     * @return string
     */
    public function getIISRules( $wrules )
    {
        $rules = '';

        $rules .= apply_filters('hmwp_iis_hide_paths_rules', false);
        $rules .= apply_filters('hmwp_iis_hide_files_rules', false);

	    $rewrites = array();

        //////////////IIS URL MAPPING
        $hmwp_url_mapping = json_decode(HMWP_Classes_Tools::getOption('hmwp_url_mapping'), true);
        if (isset($hmwp_url_mapping['from']) && !empty($hmwp_url_mapping['from']) ) {
            foreach ( $hmwp_url_mapping['from'] as $index => $row ) {
                if (substr($hmwp_url_mapping['from'][$index], -1) == '/' ) {
                    $rewrites[] = array(
                        'from' => '([_0-9a-zA-Z-]+/)?' . str_replace(array(home_url() . '/'), '', $hmwp_url_mapping['to'][$index]) . '(.*)',
                        'to' => str_replace(array(home_url() . '/'), '', $hmwp_url_mapping['from'][$index]) . "{R:" . (substr_count($hmwp_url_mapping['from'][$index], '(') + 2) . '}',
                    );
                } else {
                    $rewrites[] = array(
                        'from' => '([_0-9a-zA-Z-]+/)?' . str_replace(array(home_url() . '/'), '', $hmwp_url_mapping['to'][$index]) . '$',
                        'to' => str_replace(array(home_url() . '/'), '', $hmwp_url_mapping['from'][$index]),
                    );
                }
            }
        }

        if (!empty($rewrites) ) {
            foreach ( $rewrites as $rewrite ) {
                if (strpos($rewrite['to'], 'index.php') === false ) {
                    $rules .= '
                <rule name="HideMyWp: ' . md5($rewrite['from']) . '" stopProcessing="false">
                    <match url="^' . $rewrite['from'] . '" ignoreCase="false" />
                    <action type="Redirect" url="' . $rewrite['to'] . '" />
                </rule>';
                }
            }
        }

        ////////////////// IIS PATH CHANGING RULES
        $rewrites = array();

        if (!empty($this->_replace) ) {
            foreach ( $this->_replace['to'] as $key => $row ) {
                if ($this->_replace['rewrite'][$key] ) {
                    $rewrites[] = array(
                        'from' => '([_0-9a-zA-Z-]+/)?' . $this->_replace['to'][$key] . (substr($this->_replace['to'][$key], -1) == '/' ? "(.*)" : "$"),
                        'to' => $this->_replace['from'][$key] . (substr($this->_replace['to'][$key], -1) == '/' ? "{R:" . (substr_count($this->_replace['to'][$key], '(') + 2) . '}' : ''),
                    );
                }
            }
        }

        if (!empty($rewrites) ) {
            foreach ( $rewrites as $rewrite ) {
                if (strpos($rewrite['to'], 'index.php') === false ) {
                    $rules .= '
                <rule name="HideMyWp: ' . md5($rewrite['from']) . '" stopProcessing="true">
                    <match url="^' . $rewrite['from'] . '" ignoreCase="false" />
                    <action type="Rewrite" url="' . $rewrite['to'] . '" />
                </rule>';
                }
            }
        }

        return $rules . $wrules;
    }

    /**
     * Get Lavarage Cache for IIS
     *
     * @return string
     */
    public function getIISCacheRules()
    {
        return '
    <httpProtocol>
          <customHeaders>
            <add name="Cache-Control" value="max-age=300, must-revalidate" />
            <remove name="Vary"/>
            <add name="Vary" value="Accept-Encoding"/>
          </customHeaders>
    </httpProtocol>
    <staticContent>
       <clientCache cacheControlMode="UseMaxAge" cacheControlMaxAge="365.00:00:00"/>
    </staticContent>';

    }

    /**
     * @param $config_file
     *
     * @return null
     * @throws Exception
     */
	public function deleteIISRules( $config_file )
	{

		//Initialize WordPress Filesystem
		$wp_filesystem = HMWP_Classes_ObjController::initFilesystem();

		// If configuration file does not exist then rules also do not exist so there is nothing to delete
		if (!$wp_filesystem->exists($config_file) ) {
			return;
		}

		if($wp_filesystem->get_contents($config_file) == ''){
			return;
		}

		if (!class_exists('DOMDocument', false) ) {
			return;
		}

		if (!HMWP_Classes_ObjController::getClass('HMWP_Models_Rules')->isConfigWritable() ) {
			return;
		}

		$doc = new DOMDocument();
		$doc->preserveWhiteSpace = false;

		if ($doc->load($config_file) === false ) {
			return;
		}

		$xpath = new DOMXPath($doc);
		$rules = $xpath->query('/configuration/system.webServer/rewrite/rules/rule[starts-with(@name,\'HideMyWp\')]');

		if ($rules->length > 0 ) {
			foreach ( $rules as $item ) {
				$parent = $item->parentNode;
				if (method_exists($parent, 'removeChild') ) {
					$parent->removeChild($item);
				}
			}
		}

		if (!HMWP_Classes_Tools::isMultisites() ) {
			$rules = $xpath->query('/configuration/system.webServer/rewrite/rules/rule[starts-with(@name,\'wordpress\')] | /configuration/system.webServer/rewrite/rules/rule[starts-with(@name,\'WordPress\')]');

			if ($rules->length > 0 ) {
				foreach ( $rules as $item ) {

					$parent = $item->parentNode;
					if (method_exists($parent, 'removeChild') ) {
						$parent->removeChild($item);
					}
				}
			}
		}

		$doc->formatOutput = true;
		saveDomDocument($doc, $config_file);

		return;
	}
    /***************************/

    /**
     * Flush the Rules and write in htaccess or web.config
     *
     * @return bool
     * @throws Exception
     */
    public function flushRewrites()
    {
        $rewritecode = '';

	    $home_root = HMWP_Classes_Tools::getHomeRootPath();

	    $config_file = HMWP_Classes_ObjController::getClass('HMWP_Models_Rules')->getConfFile();

	    $form = '<a href="'.add_query_arg(array('hmwp_nonce' => wp_create_nonce('hmwp_manualrewrite'), 'action' => 'hmwp_manualrewrite')) .'" class="btn rounded-0 btn-success save" />' . esc_html__("Okay, I set it up", 'hide-my-wp') . '</a>';

        //If Windows Server
        if (HMWP_Classes_Tools::isIIS() ) {

            $this->deleteIISRules($config_file);
            if (!iis7_save_url_rewrite_rules() ) {
                $rewritecode .= $this->getIISRules('');

                if ($rewritecode <> '' ) {
                    HMWP_Classes_Error::setNotification(sprintf(esc_html__('IIS detected. You need to update your %s file by adding the following lines after &lt;rules&gt; tag: %s', 'hide-my-wp'), '<strong>' . $config_file . '</strong>', '<br /><br /><pre><strong>' . htmlentities(str_replace('    ', ' ', $rewritecode)) . '</strong></pre>' . $form),'notice',false);
                    return false; //Always show IIS as manuall action
                }

            }
        } elseif (HMWP_Classes_Tools::isWpengine() ) {
            $success = true;

            //if there are no rewrites, return true
            if (!empty($this->_rewrites) ) {
	            if (HMWP_Classes_Tools::getOption('hmwp_mapping_file') ) {
		            $rewritecode .= "<IfModule mod_rewrite.c>" . PHP_EOL;
		            $rewritecode .= "RewriteEngine On" . PHP_EOL;
		            $rewritecode .= "RewriteCond %{HTTP:Cookie} !" . HMWP_LOGGED_IN_COOKIE . 'admin' . " [NC]" . PHP_EOL;
		            $rewritecode .= "RewriteCond %{REQUEST_URI} ^". $home_root . HMWP_Classes_Tools::getDefault('hmwp_wp-content_url') . "/[^\.]+ [NC]" . PHP_EOL;
		            $rewritecode .= "RewriteRule ^([_0-9a-zA-Z-]+/)?(.*)\.(js|css|scss)$ " . $home_root . "$1$2.$3h" . " [QSA,L]" . PHP_EOL;
		            $rewritecode .= "</IfModule>\n" . PHP_EOL;
	            }

                $rewritecode .= "<IfModule mod_rewrite.c>" . PHP_EOL;
                $rewritecode .= "RewriteEngine On" . PHP_EOL;

	            //Add the URL Mapping rules
	            if (!empty($this->_umrewrites) ) {
		            foreach ( $this->_umrewrites as $rewrite ) {
			            $rewritecode .= 'RewriteRule ^' . $rewrite['from'] . ' ' . $rewrite['to'] . " [QSA,L]" . PHP_EOL;
		            }
	            }

	            //Add the New Paths rules
	            foreach ( $this->_rewrites as $rewrite ) {
                    if (strpos($rewrite['to'], 'index.php') === false ) {
                        $rewritecode .= 'RewriteRule ^' . $rewrite['from'] . ' ' . $rewrite['to'] . " [QSA,L]" . PHP_EOL;
                    }
                }
                $rewritecode .= "</IfModule>" . PHP_EOL . PHP_EOL;
            }

            if ($rewritecode <> '' ) {
                if (!HMWP_Classes_ObjController::getClass('HMWP_Models_Rules')->writeInHtaccess($rewritecode, 'HMWP_RULES') ) {
                    HMWP_Classes_Error::setNotification(sprintf(esc_html__('Config file is not writable. Create the file if not exists or copy to %s file the following lines: %s', 'hide-my-wp'), '<strong>' . $config_file . '</strong>', '<br /><br /><pre><strong># BEGIN HMWP_RULES<br />' . htmlentities(str_replace('    ', ' ', $rewritecode)) . '# END HMWP_RULES<br /></strong></pre>' . $form),'notice',false);
                    $success = false;
                }
            } else {
                HMWP_Classes_ObjController::getClass('HMWP_Models_Rules')->writeInHtaccess('', 'HMWP_RULES');
            }

	        $rewritecode = '';

	        //Add the URL Mapping rules
	        if (!empty($this->_umrewrites)) {
		        foreach ( $this->_umrewrites as $rewrite ) {
			        $rewritecode .= 'Source: <strong>^' . str_replace(array('.css', '.js'), array('\.css', '\.js'), $rewrite['from']) . '</strong> Destination: <strong>' . $rewrite['to'] . "</strong> Rewrite type: 301 Permanent;<br />";
		        }
	        }

	        //Add the New Paths rules
	        if (!empty($this->_rewrites) ) {
		        foreach ( $this->_rewrites as $rewrite ) {
			        if(PHP_VERSION_ID >= 70400 ){
				        $rewritecode .= 'Source: <strong>^/' . str_replace(array('.css', '.js'), array('\.css', '\.js'), $rewrite['from']) . '</strong> Destination: <strong>' . $rewrite['to'] . "</strong> Rewrite type: Break;<br />";
			        }elseif (strpos($rewrite['to'], 'index.php') === false && (strpos($rewrite['to'], HMWP_Classes_Tools::$default['hmwp_wp-content_url']) !== false || strpos($rewrite['to'], HMWP_Classes_Tools::$default['hmwp_wp-includes_url']) !== false)) {
				        if (strpos($rewrite['to'], HMWP_Classes_Tools::$default['hmwp_login_url']) === false && strpos($rewrite['to'], HMWP_Classes_Tools::$default['hmwp_admin_url']) === false ) {
					        $rewritecode .= 'Source: <strong>^/' . str_replace(array('.css', '.js'), array('\.css', '\.js'), $rewrite['from']) . '</strong> Destination: <strong>' . $rewrite['to'] . "</strong> Rewrite type: Break;<br />";
				        }
			        }
		        }
	        }

            if ($rewritecode <> '' ) {
                HMWP_Classes_Error::setNotification(sprintf(esc_html__('WpEngine detected. Add the redirects in the WpEngine Redirect rules panel %s.', 'hide-my-wp'), '<strong><a href="https://wpengine.com/support/redirect/" target="_blank" style="color: red">' . esc_html__("Learn How To Add the Code", 'hide-my-wp') . '</a></strong> <br /><br /><pre>' . $rewritecode . '</pre>' . $form),'notice',false);
                $success = false; //always show the WPEngine Rules as manually action
            }

            return $success;

        } elseif (HMWP_Classes_Tools::isFlywheel() ) {
	        $success = true;

	        //Add the URL Mapping rules
	        if (!empty($this->_umrewrites)) {
		        foreach ( $this->_umrewrites as $rewrite ) {
			        $rewritecode .= 'Source: <strong>^' . str_replace(array('.css', '.js'), array('\.css', '\.js'), $rewrite['from']) . '</strong> Destination: <strong>' . $rewrite['to'] . "</strong> Rewrite type: 301 Permanent;<br />";
		        }
	        }

	        //Add the New Paths rules
	        if (!empty($this->_rewrites) ) {
		        foreach ( $this->_rewrites as $rewrite ) {
			        $rewritecode .= 'Source: <strong>^/' . str_replace(array('.css', '.js'), array('\.css', '\.js'), $rewrite['from']) . '</strong> Destination: <strong>' . $rewrite['to'] . "</strong> Rewrite type: Break;<br />";
		        }
	        }

	        if ($rewritecode <> '' ) {
		        HMWP_Classes_Error::setNotification(sprintf(esc_html__('Flywheel detected. Add the redirects in the Flywheel Redirect rules panel %s.', 'hide-my-wp'), '<strong><a href="https://getflywheel.com/wordpress-support/flywheel-redirects/" target="_blank" style="color: red">' . esc_html__("Learn How To Add the Code", 'hide-my-wp') . '</a></strong> <br /><br /><pre>' . $rewritecode . '</pre>' . $form),'notice',false);
		        $success = false; //always show the Flywheel Rules as manually action
	        }

	        return $success;
        } elseif ((HMWP_Classes_Tools::isApache() || HMWP_Classes_Tools::isLitespeed()) ) {
            //if there are no rewrites, return true
            if (!empty($this->_rewrites) ) {
	            if (HMWP_Classes_Tools::getOption('hmwp_mapping_file') ) {
		            $rewritecode .= "<IfModule mod_rewrite.c>" . PHP_EOL;
		            $rewritecode .= "RewriteEngine On" . PHP_EOL;
		            $rewritecode .= "RewriteCond %{HTTP:Cookie} !" . HMWP_LOGGED_IN_COOKIE . 'admin' . " [NC]" . PHP_EOL;
		            $rewritecode .= "RewriteCond %{REQUEST_URI} ^".$home_root. HMWP_Classes_Tools::getDefault('hmwp_wp-content_url') . "/[^\.]+ [NC]" . PHP_EOL;
		            $rewritecode .= "RewriteRule ^([_0-9a-zA-Z-]+/)?(.*)\.(js|css|scss)$ " . $home_root . "$1$2.$3h" . " [QSA,L]" . PHP_EOL;
		            $rewritecode .= "</IfModule>\n" . PHP_EOL;
	            }

                if (HMWP_Classes_Tools::getOption('hmwp_file_cache') ) {
                    $rewritecode .= '<IfModule mod_expires.c>' . PHP_EOL;
                    $rewritecode .= 'ExpiresActive On' . PHP_EOL;
                    $rewritecode .= 'ExpiresDefault "access plus 1 month"' . PHP_EOL;
                    $rewritecode .= '# Feed' . PHP_EOL;
                    $rewritecode .= 'ExpiresByType application/rss+xml "access plus 1 hour"' . PHP_EOL;
                    $rewritecode .= 'ExpiresByType application/atom+xml "access plus 1 hour"' . PHP_EOL;
                    $rewritecode .= '# CSS, JavaScript' . PHP_EOL;
                    $rewritecode .= 'ExpiresByType text/css "access plus 1 year"' . PHP_EOL;
                    $rewritecode .= 'ExpiresByType text/javascript "access plus 1 year"' . PHP_EOL;
                    $rewritecode .= 'ExpiresByType application/javascript "access plus 1 year"' . PHP_EOL . PHP_EOL;
                    $rewritecode .= '# Webfonts' . PHP_EOL;
                    $rewritecode .= 'ExpiresByType font/ttf "access plus 1 year"' . PHP_EOL;
                    $rewritecode .= 'ExpiresByType font/otf "access plus 1 year"' . PHP_EOL;
                    $rewritecode .= 'ExpiresByType font/woff "access plus 1 year"' . PHP_EOL;
                    $rewritecode .= 'ExpiresByType font/woff2 "access plus 1 year"' . PHP_EOL;
                    $rewritecode .= 'ExpiresByType application/vnd.ms-fontobject "access plus 1 year"' . PHP_EOL . PHP_EOL;
                    $rewritecode .= '# Images' . PHP_EOL;
                    $rewritecode .= 'ExpiresByType image/jpeg "access plus 1 year"' . PHP_EOL;
                    $rewritecode .= 'ExpiresByType image/gif "access plus 1 year"' . PHP_EOL;
                    $rewritecode .= 'ExpiresByType image/png "access plus 1 year"' . PHP_EOL;
                    $rewritecode .= 'ExpiresByType image/webp "access plus 1 year"' . PHP_EOL;
                    $rewritecode .= 'ExpiresByType image/svg+xml "access plus 1 year"' . PHP_EOL;
                    $rewritecode .= 'ExpiresByType image/x-icon "access plus 1 year"' . PHP_EOL . PHP_EOL;
                    $rewritecode .= '# Video' . PHP_EOL;
                    $rewritecode .= 'ExpiresByType video/mp4 "access plus 1 year"' . PHP_EOL;
                    $rewritecode .= 'ExpiresByType video/mpeg "access plus 1 year"' . PHP_EOL;
                    $rewritecode .= 'ExpiresByType video/webm "access plus 1 year"' . PHP_EOL;
                    $rewritecode .= "</IfModule>\n" . PHP_EOL;
                }

                $rewritecode .= "<IfModule mod_rewrite.c>" . PHP_EOL;
                $rewritecode .= "RewriteEngine On" . PHP_EOL;

	            //Add the URL Mapping rules
	            if (!empty($this->_umrewrites) ) {
		            foreach ( $this->_umrewrites as $rewrite ) {
			            $rewritecode .= 'RewriteRule ^' . $rewrite['from'] . ' ' . $rewrite['to'] . " [QSA,L]" . PHP_EOL;
		            }
	            }

	            //Add the New Paths rules
	            foreach ( $this->_rewrites as $rewrite ) {
                    if (strpos($rewrite['to'], 'index.php') === false ) {
                        $rewritecode .= 'RewriteRule ^' . $rewrite['from'] . ' ' . $rewrite['to'] . " [QSA,L]" . PHP_EOL;
                    }
                }
                $rewritecode .= "</IfModule>" . PHP_EOL . PHP_EOL;

            }

            if ($rewritecode <> '' ) {
                if (!HMWP_Classes_ObjController::getClass('HMWP_Models_Rules')->writeInHtaccess($rewritecode, 'HMWP_RULES') ) {
                    HMWP_Classes_Error::setNotification(sprintf(esc_html__('Config file is not writable. Create the file if not exists or copy to %s file the following lines: %s', 'hide-my-wp'), '<strong>' . $config_file . '</strong>', '<br /><br /><pre><strong># BEGIN HMWP_RULES<br />' . htmlentities(str_replace('    ', ' ', $rewritecode)) . '# END HMWP_RULES</strong></pre>' . $form),'notice',false);
                    return false;
                }
            } else {
                HMWP_Classes_ObjController::getClass('HMWP_Models_Rules')->writeInHtaccess('', 'HMWP_RULES');
            }

        } elseif ( HMWP_Classes_Tools::isNginx() ) {
            $cachecode = '';
            //if there are no rewrites, return true
            if (!empty($this->_rewrites) ) {
                if (HMWP_Classes_Tools::getOption('hmwp_file_cache') ) {
                    $cachecode .= 'location ~* \.(?:ico|css|js|gif|jpe?g|png)$ {' . PHP_EOL;
                    $cachecode .= 'expires 365d;' . PHP_EOL;
                    $cachecode .= 'add_header Pragma public;' . PHP_EOL;
                    $cachecode .= 'add_header Cache-Control "public";' . PHP_EOL;
                    $cachecode .= '}' . PHP_EOL . PHP_EOL;
                }

	            //Add the URL Mapping rules
	            if (!empty($this->_umrewrites) ) {
		            foreach ( $this->_umrewrites as $rewrite ) {
			            $rewritecode .= 'rewrite ^/' . $rewrite['from'] . ' ' . $rewrite['to'] . ";<br />";
		            }
	            }

	            //Add the New Paths rules
	            foreach ( $this->_rewrites as $rewrite ) {

                    //most servers have issue when redirecting the login path
                    //let HMWP handle the login path
                    if (strpos($rewrite['to'], 'wp-login.php') !== false ) {
                        continue;
                    }

                    if (strpos($rewrite['to'], 'index.php') === false ) {
                        if (strpos($rewrite['from'], '$') ) {
                            $rewritecode .= 'rewrite ^/' . $rewrite['from'] . ' ' . $rewrite['to'] . ";<br />";
                        } else {
                            $rewritecode .= 'rewrite ^/' . $rewrite['from'] . ' ' . $rewrite['to'] . " last;<br />";
                        }
                    }
                }
            }
            if ($rewritecode <> '' ) {
                $rewritecode = str_replace('<br />', "\n", $rewritecode);
                $rewritecode = $cachecode . 'if (!-e $request_filename) {' . PHP_EOL . $rewritecode . '}';

                if (!HMWP_Classes_ObjController::getClass('HMWP_Models_Rules')->writeInNginx($rewritecode, 'HMWP_RULES') ) {
                    HMWP_Classes_Error::setNotification(sprintf(esc_html__('Config file is not writable. You have to added it manually at the beginning of the %s file: %s', 'hide-my-wp'), '<strong>' . $config_file . '</strong>', '<br /><br /><pre><strong># BEGIN HMWP_RULES<br />' . htmlentities(str_replace('    ', ' ', $rewritecode)) . '# END HMWP_RULES</strong></pre>'),'notice',false);
                    return false;
                }

            } else {
                HMWP_Classes_ObjController::getClass('HMWP_Models_Rules')->writeInNginx('', 'HMWP_RULES');
            }
        }

        return true;
    }

    /**
     * Not used yet
     *
     * @param $wp_rewrite
     *
     * @return mixed
     */
    public function setRewriteIndexRules( $wp_rewrite )
    {
        return $wp_rewrite;
    }

    /**
     * Flush the changes in htaccess
     *
     * @throws Exception
     */
    public function flushChanges()
    {

        if (!did_action('wp_loaded') ) {
            add_action('wp_loaded', array($this, 'flushChanges'));
        }

        //Build the redirect table
        $this->clearRedirect()->setRewriteRules()->flushRewrites();

        //Change the rest api for the rewrite process
        add_filter('rest_url_prefix', array($this, 'replace_rest_api'));

        //update the API URL
        rest_api_register_rewrites();

        //Flush the rules in WordPress
        flush_rewrite_rules();

        //Hook the flush process for compatibillity usage
        do_action('hmwp_flushed_rewrites', false);

    }

    /**
     * Send the email notification
     */
    public function sendEmail()
    {
        if (HMWP_Classes_Tools::getOption('hmwp_send_email') ) {
            $options = HMWP_Classes_Tools::getOptions();
            $lastsafeoptions = HMWP_Classes_Tools::getOptions(true);

            if ($lastsafeoptions['hmwp_admin_url'] <> $options['hmwp_admin_url']
                || $lastsafeoptions['hmwp_login_url'] <> $options['hmwp_login_url']
            ) {
                HMWP_Classes_Tools::sendEmail();
            }
        }
    }


    /**
     * Add the custom param vars for: disable HMWP and admin tabs
     *
     * @param $vars
     *
     * @return array
     */
    public function addParams( $vars )
    {
        $vars[] = HMWP_Classes_Tools::getOption('hmwp_disable_name');

        return $vars;
    }

    /*******************************
     *
     * RENAME URLS
     **************************************************/

	/**
	 * Filters the home URL.
	 *
	 * @param string      $url     The complete site URL including scheme and path.
	 * @param string      $path    Path relative to the site URL. Blank string if no path is specified.
	 * @param string|null $scheme  Scheme to give the site URL context. Accepts 'http', 'https', 'login',
	 *                             'login_post', 'admin', 'relative' or null.
	 * @param int|null    $blog_id Site ID, or null for the current site.
	 */
	public function home_url( $url, $path = '', $scheme = null )
	{
		if(!apply_filters('hmwp_change_home_url', true)){
			return $url;
		}

		if(!isset($scheme)) {
			$scheme = (((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == "on") || (defined('FORCE_SSL_ADMIN') && FORCE_SSL_ADMIN) || (function_exists('is_ssl') && is_ssl())) ? 'https' : 'http');
		}

		$url = set_url_scheme($url, $scheme);

		if (HMWP_Classes_Tools::$default['hmwp_login_url'] <> HMWP_Classes_Tools::getOption('hmwp_login_url') ) {

			if (strpos($url, 'wp-login') !== false ) {

				//check if disable and do not redirect to log in
				if (HMWP_Classes_Tools::getIsset(HMWP_Classes_Tools::getOption('hmwp_disable_name')) ) {
					if (HMWP_Classes_Tools::getValue(HMWP_Classes_Tools::getOption('hmwp_disable_name')) == HMWP_Classes_Tools::getOption('hmwp_disable') ) {
						//add the disabled param in order to work without issues
						return add_query_arg(array(HMWP_Classes_Tools::getOption('hmwp_disable_name') => HMWP_Classes_Tools::getOption('hmwp_disable')), $url);
					}
				}

				$query = '';
				if ($path <> '' ) {
					$parsed = @parse_url($path);
					if (isset($parsed['query']) && $parsed['query'] <> '' ) {
						$query = '?' . $parsed['query'];
					}
				}

				if ($query == '?action=lostpassword' && HMWP_Classes_Tools::getOption('hmwp_lostpassword_url') <> '' ) {
					$url = home_url(HMWP_Classes_Tools::getOption('hmwp_lostpassword_url'), $scheme);
				} elseif ($query == '?action=register' && HMWP_Classes_Tools::getOption('hmwp_register_url') <> '' ) {
					$url = home_url(HMWP_Classes_Tools::getOption('hmwp_register_url'), $scheme);
				} else {

					$url = home_url('', $scheme);
					if(function_exists('mb_stripos')){
						if (mb_stripos($url,'?') !== false) {
							$url = substr($url,0,mb_stripos($url,'?'));
						}
					}elseif(stripos($url,'?') !== false){
						$url = substr($url,0,stripos($url,'?'));
					}

					$url .= '/' . HMWP_Classes_Tools::getOption('hmwp_login_url') . $query;

					if (HMWP_Classes_Tools::getValue('nordt') ) {
						$url = add_query_arg(array('nordt' => true), $url);
					}
				}
			}

		}

		if (HMWP_Classes_Tools::$default['hmwp_activate_url'] <> HMWP_Classes_Tools::getOption('hmwp_activate_url') ) {
			if (strpos($url, 'wp-activate.php') !== false ) {
				$query = '';
				if ($path <> '' ) {
					$parsed = @parse_url($path);
					if (isset($parsed['query']) && $parsed['query'] <> '' ) {
						$query = '?' . $parsed['query'];
					}
				}
				$url = home_url('', $scheme) . '/' . HMWP_Classes_Tools::getOption('hmwp_activate_url') . $query;
			}
		}


		return $url;

	}

	/**
	 * Filters the site URL.
	 *
	 * @param string      $url     The complete site URL including scheme and path.
	 * @param string      $path    Path relative to the site URL. Blank string if no path is specified.
	 * @param string|null $scheme  Scheme to give the site URL context. Accepts 'http', 'https', 'login',
	 *                             'login_post', 'admin', 'relative' or null.
	 * @param int|null    $blog_id Site ID, or null for the current site.
	 */
	public function site_url( $url, $path = '', $scheme = null )
	{
		if(!apply_filters('hmwp_change_site_url', true)){
			return $url;
		}

		if(!isset($scheme)) {
			$scheme = (((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == "on") || (defined('FORCE_SSL_ADMIN') && FORCE_SSL_ADMIN) || (function_exists('is_ssl') && is_ssl())) ? 'https' : 'http');
		}

		$url = set_url_scheme($url, $scheme);

		if (HMWP_Classes_Tools::$default['hmwp_login_url'] <> HMWP_Classes_Tools::getOption('hmwp_login_url') ) {

			if (strpos($url, 'wp-login') !== false ) {

				//check if disable and do not redirect to log in
				if (HMWP_Classes_Tools::getIsset(HMWP_Classes_Tools::getOption('hmwp_disable_name')) ) {
					if (HMWP_Classes_Tools::getValue(HMWP_Classes_Tools::getOption('hmwp_disable_name')) == HMWP_Classes_Tools::getOption('hmwp_disable') ) {
						//add the disabled param in order to work without issues
						return add_query_arg(array(HMWP_Classes_Tools::getOption('hmwp_disable_name') => HMWP_Classes_Tools::getOption('hmwp_disable')), $url);
					}
				}

				$query = '';
				if ($path <> '' ) {
					$parsed = @parse_url($path);
					if (isset($parsed['query']) && $parsed['query'] <> '' ) {
						$query = '?' . $parsed['query'];
					}
				}

				if ($query == '?action=lostpassword' && HMWP_Classes_Tools::getOption('hmwp_lostpassword_url') <> '' ) {
					$url = site_url(HMWP_Classes_Tools::getOption('hmwp_lostpassword_url'), $scheme);
				} elseif ($query == '?action=register' && HMWP_Classes_Tools::getOption('hmwp_register_url') <> '' ) {
					$url = site_url(HMWP_Classes_Tools::getOption('hmwp_register_url'), $scheme);
				} else {

					$url = site_url('', $scheme);
					if(function_exists('mb_stripos')){
						if (mb_stripos($url,'?') !== false) {
							$url = substr($url,0,mb_stripos($url,'?'));
						}
					}elseif(stripos($url,'?') !== false){
						$url = substr($url,0,stripos($url,'?'));
					}

					$url .= '/' . HMWP_Classes_Tools::getOption('hmwp_login_url') . $query;

					if (HMWP_Classes_Tools::getValue('nordt') ) {
						$url = add_query_arg(array('nordt' => true), $url);
					}
				}
			}

		}

		if (HMWP_Classes_Tools::$default['hmwp_activate_url'] <> HMWP_Classes_Tools::getOption('hmwp_activate_url') ) {
			if (strpos($url, 'wp-activate.php') !== false ) {
				$query = '';
				if ($path <> '' ) {
					$parsed = @parse_url($path);
					if (isset($parsed['query']) && $parsed['query'] <> '' ) {
						$query = '?' . $parsed['query'];
					}
				}
				$url = site_url('', $scheme) . '/' . HMWP_Classes_Tools::getOption('hmwp_activate_url') . $query;
			}
		}


		return $url;
	}

    /**
     * Get the new admin URL
     *
     * @param string         $url
     * @param string         $path
     * @param integer | null $blog_id
     *
     * @return mixed|string
     */
    public function admin_url( $url, $path = '', $blog_id = null )
    {
        $find = $replace = array();

        if (!defined('ADMIN_COOKIE_PATH') ) {
            return $url;
        }

        if (HMWP_Classes_Tools::doChangePaths() ) {

            if (HMWP_Classes_Tools::getDefault('hmwp_admin-ajax_url') <> HMWP_Classes_Tools::getOption('hmwp_admin-ajax_url') ) {
                if (HMWP_Classes_Tools::getOption('hmwp_hideajax_admin')) {
                    $find[] = '/' . HMWP_Classes_Tools::getDefault('hmwp_admin_url') . '/' . HMWP_Classes_Tools::getDefault('hmwp_admin-ajax_url');
                } else {
                    $find[] = '/' . HMWP_Classes_Tools::getDefault('hmwp_admin-ajax_url');
                }
                $replace[] = '/' . HMWP_Classes_Tools::getOption('hmwp_admin-ajax_url');
            }

            if (HMWP_Classes_Tools::getDefault('hmwp_admin_url') <> HMWP_Classes_Tools::getOption('hmwp_admin_url')) {
                $find[] = '/' . HMWP_Classes_Tools::getDefault('hmwp_admin_url') . '/';
                $replace[] = '/' . HMWP_Classes_Tools::getOption('hmwp_admin_url') . '/';
            }

        }elseif ( strpos($url, HMWP_Classes_Tools::getDefault('hmwp_admin-ajax_url')) === false ) {

            if (HMWP_Classes_Tools::getDefault('hmwp_admin_url') <> HMWP_Classes_Tools::getOption('hmwp_admin_url')) {
                $find[] = '/' . HMWP_Classes_Tools::getDefault('hmwp_admin_url') . '/';
                $replace[] = '/' . HMWP_Classes_Tools::getOption('hmwp_admin_url') . '/';
            }

        }

        //if there is a custom path for admin or ajax
        if(!empty($find) && !empty($replace)) {
            return str_replace($find, $replace, $url);
        }

        //Return the admin URL
        return $url;

    }

	/**
	 * Change the admin URL for multisites
	 * Filters the network admin URL.
	 *
	 * @param string      $url    The complete network admin URL including scheme and path.
	 * @param string      $path   Path relative to the network admin URL. Blank string if
	 *                            no path is specified.
	 * @param string|null $scheme The scheme to use. Accepts 'http', 'https',
	 *                            'admin', or null. Default is 'admin', which obeys force_ssl_admin() and is_ssl().
	 */
    public function network_admin_url( $url, $path = '', $scheme = null )
    {
        $find = $replace = array();

        if (!defined('ADMIN_COOKIE_PATH') ) {
            return $url;
        }

        if (HMWP_Classes_Tools::getOption('hmwp_admin_url') == 'wp-admin' ) {
            return $url;
        }

        if (HMWP_Classes_Tools::doChangePaths() ) {

            if (HMWP_Classes_Tools::getDefault('hmwp_admin-ajax_url') <> HMWP_Classes_Tools::getOption('hmwp_admin-ajax_url') ) {
                if (HMWP_Classes_Tools::getOption('hmwp_hideajax_admin') ) {
                    $find[] = '/' . HMWP_Classes_Tools::getDefault('hmwp_admin_url') . '/' . HMWP_Classes_Tools::getDefault('hmwp_admin-ajax_url');
                } else {
                    $find[] = '/' . HMWP_Classes_Tools::getDefault('hmwp_admin-ajax_url');
                }
                $replace[] = '/' . HMWP_Classes_Tools::getOption('hmwp_admin-ajax_url');
            }

            if (HMWP_Classes_Tools::getDefault('hmwp_admin_url') <> HMWP_Classes_Tools::getOption('hmwp_admin_url')) {
                $find[] = network_site_url(HMWP_Classes_Tools::getDefault('hmwp_admin_url') . '/', HMWP_Classes_Tools::getOption('hmwp_admin_url'));
                $replace[] = network_site_url('/' . HMWP_Classes_Tools::getOption('hmwp_admin_url') . '/', HMWP_Classes_Tools::getOption('hmwp_admin_url'));
            }

        }elseif ( strpos($url, HMWP_Classes_Tools::getDefault('hmwp_admin-ajax_url')) === false ) {

            if (HMWP_Classes_Tools::getDefault('hmwp_admin_url') <> HMWP_Classes_Tools::getOption('hmwp_admin_url')) {
                $find[] = network_site_url(HMWP_Classes_Tools::getDefault('hmwp_admin_url') . '/', HMWP_Classes_Tools::getOption('hmwp_admin_url'));
                $replace[] = network_site_url('/' . HMWP_Classes_Tools::getOption('hmwp_admin_url') . '/', HMWP_Classes_Tools::getOption('hmwp_admin_url'));
            }
        }

        //if there is a custom path for admin or ajax
        if(!empty($find) && !empty($replace)) {
            return str_replace($find, $replace, $url);
        }

        //Return the admin URL
        return $url;

    }

    /**
     * Change the plugin URL with the new paths
     * for some plugins
     *
     * @param $url
     * @param $path
     * @param $plugin
     *
     * @return null|string|string[]
     * @throws Exception
     */
    public function plugin_url( $url, $path, $plugin )
    {
        $plugins = array('rocket-lazy-load');
        if (!is_admin() ) {
            if ($plugin <> '' && $url <> '' && HMWP_Classes_Tools::searchInString($url, $plugins) ) {
                $url = $this->find_replace_url($url);
            }
        }

        return $url;
    }

    /**
     * Login/Register title
     *
     * @param string $title
     * @return string
     */
    public function login_title($title)
    {
        if($title <> '') {
            $title = str_ireplace(array(' &lsaquo;  &#8212; WordPress', 'WordPress'), '', $title);
        }

        return $title;
    }

    /**
     * Login Header Hook
     *
     * @throws Exception
     */
    public function login_head()
    {

        add_filter('login_headerurl', array($this, 'login_url'), 99, 1);

        if (HMWP_Classes_Tools::getOption('hmwp_remove_third_hooks') ) {
            if (function_exists('get_theme_mod') && function_exists('wp_get_attachment_image_src') ) {
                $custom_logo_id = get_theme_mod('custom_logo');
                $image = wp_get_attachment_image_src($custom_logo_id, 'full');

                if (isset($image[0]) ) {
	                echo '<style>#login h1 a, .login h1 a {background-image: ' . "url($image[0])" . ' !important; background-position: center;}</style>';
                }
            }
        }

    }

    /**
     * Get the new Login URL
     *
     * @param $url
     *
     * @return string
     */
    public function login_url( $url )
    {

        if (HMWP_Classes_Tools::$default['hmwp_login_url'] <> HMWP_Classes_Tools::getOption('hmwp_login_url')
            && strpos($url, HMWP_Classes_Tools::$default['hmwp_login_url']) !== false
        ) {

            //check if disable and do not redirect to log in
            if (HMWP_Classes_Tools::getIsset(HMWP_Classes_Tools::getOption('hmwp_disable_name')) ) {
                if (HMWP_Classes_Tools::getValue(HMWP_Classes_Tools::getOption('hmwp_disable_name')) == HMWP_Classes_Tools::getOption('hmwp_disable') ) {
                    //add the disabled param in order to work without issues
                    return add_query_arg(array(HMWP_Classes_Tools::getOption('hmwp_disable_name') => HMWP_Classes_Tools::getOption('hmwp_disable')), $url);
                }
            }

            $url = site_url(HMWP_Classes_Tools::getOption('hmwp_login_url'));
        }

        return $url;
    }

    /**
     * Hook the wp_login action from WordPress
     *
     * @param string  $user_login
     * @param WP_User $user
     */
    public function wp_login( $user_login  = null, $user = null)
    {
        HMWP_Classes_Tools::setCurrentUserRole($user);
    }

    /**
     * Hook the login_init from wp-login.php
     *
     * @throws Exception
     */
    public function login_init()
    {

        if(HMWP_Classes_Tools::getOption('hmwp_remove_third_hooks')) {
            //////////////////////////////// Rewrite the login style
            wp_deregister_script('password-strength-meter');
            wp_deregister_script('user-profile');
            wp_deregister_style('forms');
            wp_deregister_style('l10n');
            wp_deregister_style('buttons');
            wp_deregister_style('login');

            wp_register_style('login', _HMWP_WPLOGIN_URL_ . 'css/login.min.css', array('dashicons', 'buttons', 'forms', 'l10n'), HMWP_VERSION_ID, false);
            wp_register_style('forms', _HMWP_WPLOGIN_URL_ . 'css/forms.min.css', null, HMWP_VERSION_ID, false);
            wp_register_style('buttons', _HMWP_WPLOGIN_URL_ . 'css/buttons.min.css', null, HMWP_VERSION_ID, false);
            wp_register_style('l10n', _HMWP_WPLOGIN_URL_ . 'css/l10n.min.css', null, HMWP_VERSION_ID, false);
            wp_register_script('password-strength-meter', _HMWP_WPLOGIN_URL_ . 'js/password-strength-meter.min.js', array('jquery', 'zxcvbn-async'), HMWP_VERSION_ID, true);
            wp_register_script('user-profile', _HMWP_WPLOGIN_URL_ . 'js/user-profile.min.js', array('jquery', 'password-strength-meter', 'wp-util'), HMWP_VERSION_ID, true);

            wp_localize_script(
                'password-strength-meter', 'pwsL10n', array(
                'unknown' => _x('Password strength unknown', 'password strength'),
                'short' => _x('Very weak', 'password strength'),
                'bad' => _x('Weak', 'password strength'),
                'good' => _x('Medium', 'password strength'),
                'strong' => _x('Strong', 'password strength'),
                'mismatch' => _x('Mismatch', 'password mismatch'),
                )
            );

            $user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
            wp_localize_script(
                'user-profile', 'userProfileL10n', array(
                'user_id' => $user_id,
                'nonce' => wp_create_nonce('reset-password-for-' . $user_id),
                )
            );
            /////////////////////////////////////////////////////////
        }

        add_filter('wp_redirect', array($this, 'loopCheck'), 99, 1);

        //remove clasiera theme loop
        remove_action("login_init", "classiera_cubiq_login_init");
        remove_filter("login_redirect", "loginstyle_login_redirect");

        //If Clean Login option is active or too many redirects
        $isRedirect = HMWP_Classes_Tools::getCustomLoginURL(false);
        if (HMWP_Classes_Tools::getValue('nordt') || $isRedirect || HMWP_Classes_Tools::getOption('hmwp_remove_third_hooks') ) {
            remove_all_actions('login_init');
            remove_all_actions('login_redirect');
            remove_all_actions('bbp_redirect_login');

            add_filter('login_headerurl', array($this, 'login_url'));
            add_filter('login_redirect', array($this, 'sanitize_login_redirect'), 1, 3);
        }

        //handle the lost password and registration redirects
        if (HMWP_Classes_Tools::$default['hmwp_login_url'] <> HMWP_Classes_Tools::getOption('hmwp_login_url') ) {
            add_filter('lostpassword_redirect', array($this, 'lostpassword_redirect'), 1);
            add_filter('registration_redirect', array($this, 'registration_redirect'), 1);

            HMWP_Classes_ObjController::getClass('HMWP_Models_Cookies')->setTestCookie();
        }

        //hide language switcher on login page
        if(HMWP_Classes_Tools::getOption('hmwp_disable_language_switcher')) {
            add_filter('login_display_language_dropdown', '__return_false');
        }

		//Hook the login page and check if the user is already logged in
	    if(HMWP_Classes_Tools::getOption('hmwp_logged_users_redirect')) {
			$this->dashboard_redirect();
	    }

        do_action('hmwp_login_init');
    }

	/**
	 * Hook the login page and check if the user is already logged in
	 *
	 * @return string
	 */
	public function dashboard_redirect()
	{
		global $current_user;
		//If the user is already logged in
		if ((!isset( $_REQUEST['action'] ) || $_REQUEST['action'] == 'login') && isset($current_user->ID) && $current_user->ID > 0) {
			//redirect to admin dashboard
			wp_redirect(apply_filters('hmwp_url_login_redirect', admin_url()));
			exit();
		}
	}

    /**
     * Change the password confirm URL with the new URL
     *
     * @return string
     */
    public function lostpassword_redirect()
    {
        return site_url('wp-login.php?checkemail=confirm');
    }

    /**
     * Change the register confirmation URL with the new URL
     *
     * @return string
     */
    public function registration_redirect()
    {
        return site_url('wp-login.php?checkemail=registered');
    }

    /**
     * Called from WP hook to change the lost password URL
     *
     * @param  $url
     * @return mixed
     * @throws Exception
     */
    public function lostpassword_url( $url )
    {
        if (HMWP_Classes_Tools::getOption('hmwp_lostpassword_url') <> '' ) {

            //check if the redirects are built
            $url = $this->find_replace_url($url);
        }

        return $url;
    }

    /**
     * Called from WP hook to change the register URL
     *
     * @param  $url
     * @return mixed
     * @throws Exception
     */
    public function register_url( $url )
    {
        if (HMWP_Classes_Tools::getOption('hmwp_register_url') <> '' ) {

            //check if the redirects are built
            $url = $this->find_replace_url($url);
        }

        return $url;
    }

    /**
     * Get the new Logout URL
     *
     * @param string $url
     * @param string $redirect
     *
     * @return string
     */
    public function logout_url( $url, $redirect = '' )
    {
        $args = array();
        if ($url <> '' ) {
            $parsed = @parse_url($url);
            if ($parsed['query'] <> '' ) {
                @parse_str(html_entity_decode($parsed['query']), $args);
            }
        }

        if (!isset($args['_wpnonce']) ) {
            $args['_wpnonce'] = wp_create_nonce('log-out');
            //correct the logout URL
            $url = add_query_arg(array('_wpnonce' => $args['_wpnonce']), site_url('wp-login.php?action=logout', 'login'));
        }

        if (HMWP_Classes_Tools::getOption('hmwp_logout_url') <> '' ) {
            //add the new URL
            $url = site_url() . '/' . add_query_arg(array('_wpnonce' => $args['_wpnonce']), HMWP_Classes_Tools::getOption('hmwp_logout_url'));
        }

        return $url;
    }

    /**
     * Get the new Author URL
     *
     * @param array $rewrite
     *
     * @return array
     */
    public function author_url( $rewrite )
    {

        if (HMWP_Classes_Tools::$default['hmwp_author_url'] <> HMWP_Classes_Tools::getOption('hmwp_author_url') ) {
            foreach ( $rewrite as $from => $to ) {
                $newfrom = str_replace(HMWP_Classes_Tools::$default['hmwp_author_url'], HMWP_Classes_Tools::getOption('hmwp_author_url'), $from);
                $rewrite[$newfrom] = $to;
            }
        }

        return $rewrite;
    }

    /********************************
     *
     * HOOK REDIRECTS
     *************************************************/

    /**
     * Hook the logout to flush the changes set in admin
     *
     * @throws Exception
     */
    public function wp_logout()
    {
        if (apply_filters('hmwp_url_logout_redirect', false) ) {
            $_REQUEST['redirect_to'] = apply_filters('hmwp_url_logout_redirect', false);
        }

        do_action('hmwp_wp_logout');
    }

    /**
     * Hook the logout referrer and logout the user
     *
     * @param $action
     * @param $result
     */
    public function check_admin_referer($action, $result)
    {

        if ($action == "log-out" && isset($_REQUEST[ '_wpnonce' ])) {
            $adminurl = strtolower(admin_url());
            $referer  = strtolower(wp_get_referer());
            if (! $result && ! ( -1 === $action && strpos($referer, $adminurl) === 0 ) ) {

                if (function_exists('is_user_logged_in') && function_exists('wp_get_current_user') && is_user_logged_in()) {

                    if (apply_filters('hmwp_url_logout_redirect', false)) {
                        $_REQUEST['redirect_to'] = apply_filters('hmwp_url_logout_redirect', false);
                    }

                    $user = wp_get_current_user();
                    $redirect_to = isset($_REQUEST['redirect_to']) ? $_REQUEST['redirect_to'] : home_url();
                    $redirect_to = apply_filters('logout_redirect', $redirect_to, $redirect_to, $user);

                    wp_logout();

                    header("Location: " . apply_filters('hmwp_url_logout_redirect', $redirect_to));
                    die;

                }

                wp_redirect(home_url());
                die;
            }


        }
    }

    /**
     * In case of  redirects, correct the redirect links
     *
     * @param string $redirect The path or URL to redirect to.
     * @param string $status   The HTTP response status code to use
     *
     * @return string
     * @throws Exception
     */
    public function sanitize_redirect( $redirect, $status = '' )
    {

        if (HMWP_Classes_Tools::$default['hmwp_admin_url'] <> HMWP_Classes_Tools::getOption('hmwp_admin_url') ) {
            if (strpos($redirect, 'wp-admin') !== false ) {
                $redirect = $this->admin_url($redirect);
            }
        }

        return $redirect;

    }

    /**
     * In case of login redirects, correct the redirect links
     *
     * @param string $redirect The path or URL to redirect to.
     * @param string $path
     * @param string $user
     *
     * @return string
     * @throws Exception
     */
    public function sanitize_login_redirect( $redirect, $path = null, $user = null )
    {

        if (HMWP_Classes_Tools::$default['hmwp_login_url'] <> HMWP_Classes_Tools::getOption('hmwp_login_url') ) {
            if (strpos($redirect, 'wp-login') !== false ) {
                $redirect = site_url(HMWP_Classes_Tools::getOption('hmwp_login_url'));
            }
        }

        if (HMWP_Classes_Tools::$default['hmwp_admin_url'] <> HMWP_Classes_Tools::getOption('hmwp_admin_url') ) {
            if (strpos($redirect, 'wp-admin') !== false ) {
                $redirect = $this->admin_url($redirect);
            }
        }

        //if user is logged in
        if (isset($user) && isset($user->ID) && !is_wp_error($user) ) {
            //Set the current user for custom redirects
            HMWP_Classes_Tools::setCurrentUserRole($user);

            //overwrite the login redirect with the custom HMWP redirect
            $redirect = apply_filters('hmwp_url_login_redirect', $redirect);

            //If the redirect URL is external, jump to redirect
            if(parse_url($redirect, PHP_URL_HOST) && parse_url($redirect, PHP_URL_HOST) <> parse_url(home_url(), PHP_URL_HOST)) {
                wp_redirect($redirect);
                exit();
            }
        }

        //Stop loops and other hooks
        if (HMWP_Classes_Tools::getValue('nordt') || HMWP_Classes_Tools::getOption('hmwp_remove_third_hooks') ) {

            //remove other redirect hooks
            remove_all_actions('login_redirect');

            //If user is logged in
            if (isset($user) && isset($user->ID) ) {
                if (!is_wp_error($user) && empty($_REQUEST['reauth']) ) {

                    //If admin redirect
                    if ((empty($redirect) || $redirect == 'wp-admin/' || $redirect == admin_url()) ) {

                        // If the user doesn't belong to a blog, send them to user admin. If the user can't edit posts, send them to their profile.
                        if (HMWP_Classes_Tools::isMultisites() && !get_active_blog_for_user($user->ID) && !is_super_admin($user->ID) ) {
                            $redirect = user_admin_url();
                        } elseif (method_exists($user, 'has_cap') ) {

                            if (HMWP_Classes_Tools::isMultisites() && !$user->has_cap('read') ) {
                                $redirect = get_dashboard_url($user->ID);
                            } elseif (!$user->has_cap('edit_posts') ) {
                                $redirect = $user->has_cap('read') ? admin_url('profile.php') : home_url();
                            }

                        }

                        //overwrite the login redirect with the custom HMWP redirect
                        $redirect = apply_filters('hmwp_url_login_redirect', $redirect);

                        wp_redirect($redirect);
                        exit();
                    }

                    //overwrite the login redirect with the custom HMWP redirect
                    $redirect = apply_filters('hmwp_url_login_redirect', $redirect);

                    wp_redirect($redirect);
                    exit();
                }
            }
        }

        //overwrite the login redirect with the custom HMWP redirect
        return apply_filters('hmwp_url_login_redirect', $redirect);

    }

    /**
     * Check if the current URL is the same with the redirect URL
     *
     * @param $url
     *
     * @return string
     * @throws Exception
     */
    public function loopCheck( $url )
    {
        if (isset($_SERVER['HTTP_HOST']) && isset($_SERVER['REQUEST_URI']) && $url <> '' ) {
            $current_url = $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
            $redirect_url = parse_url($url, PHP_URL_HOST) . parse_url($url, PHP_URL_PATH);
            if ($current_url <> '' && $redirect_url <> '' ) {
                if ($current_url == $redirect_url ) {
                    return add_query_arg(array('nordt' => true), $url);
                }else{
	                return remove_query_arg(array('nordt'), $url);
                }
            }

        }

        if (HMWP_Classes_Tools::getOption('hmwp_hide_wplogin') || HMWP_Classes_Tools::getOption('hmwp_hide_login') ) {
            if (HMWP_Classes_Tools::$default['hmwp_login_url'] <> HMWP_Classes_Tools::getOption('hmwp_login_url') ) {

	            //temporary deativate the change of home and site url
	            add_filter('hmwp_change_home_url', '__return_false');
	            add_filter('hmwp_change_site_url', '__return_false');

                if (function_exists('is_user_logged_in') && is_user_logged_in() ) {
                    $paths = array(
                        site_url('wp-login.php', 'relative'),
                        site_url('wp-login', 'relative'),
                    );
                } else {

                    $paths = array(
                        home_url('wp-login.php', 'relative'),
                        home_url('wp-login', 'relative'),
                        site_url('wp-login.php', 'relative'),
                        site_url('wp-login', 'relative'),
                    );

                    if (HMWP_Classes_Tools::getOption('hmwp_hide_login') ) {

                        $paths[] = home_url('login', 'relative');
                        $paths[] = site_url('login', 'relative');

                    }

                    $paths = array_unique($paths);
                }

	            //reactivate the change of the paths in home and site url
	            add_filter('hmwp_change_home_url', '__return_true');
	            add_filter('hmwp_change_site_url', '__return_true');

                if (HMWP_Classes_Tools::searchInString($url, $paths) ) {
                    if (site_url(HMWP_Classes_Tools::getOption('hmwp_login_url'), 'relative') <> $url ) {
                        return add_query_arg(array('nordt' => true), site_url(HMWP_Classes_Tools::getOption('hmwp_login_url')));
                    }
                }
            }
        }

        return $url;
    }

    /**
     * Check Hidden pages and return 404 if needed
     *
     * @throws Exception
     */
    public function hideUrls()
    {

        //Check if is valid for moving on
        if(HMWP_Classes_Tools::doHideURLs() ) {

	        //temporary deativate the change of home and site url
	        add_filter('hmwp_change_home_url', '__return_false');
	        add_filter('hmwp_change_site_url', '__return_false');

            $url = untrailingslashit(strtok($_SERVER["REQUEST_URI"], '?'));
            $http_post = (isset($_SERVER['REQUEST_METHOD']) && 'POST' == $_SERVER['REQUEST_METHOD']);

            //if user is logged in and is not set to hide the admin urls
            if (is_user_logged_in()) {

                //redirect if no final slash is added
                if ($_SERVER['REQUEST_URI'] == site_url(HMWP_Classes_Tools::getOption('hmwp_admin_url'), 'relative')) {
                    wp_safe_redirect($url . '/');
                    exit();
                }

                //Hide the wp-admin for logged users
                if (HMWP_Classes_Tools::$default['hmwp_admin_url'] <> HMWP_Classes_Tools::getOption('hmwp_admin_url') && HMWP_Classes_Tools::getOption('hmwp_hide_admin_loggedusers')) {
                    $paths = array(
                        home_url('wp-admin', 'relative'),
                        site_url('wp-admin', 'relative')
                    );

                    if (HMWP_Classes_Tools::searchInString($url, $paths)) {
                        if (!HMWP_Classes_Tools::userCan('manage_options')) {
                            $this->getNotFound($url);
                        }
                    }
                }
            } else {

                //Hide the param rest route
                if (HMWP_Classes_Tools::getOption('hmwp_disable_rest_api_param') ) {
                    $this->hideRestRouteParam();
                }

                //Check the whitelist IPs for accessing the hide paths
                if (HMWP_Classes_Tools::getOption('hmwp_detectors_block') ) {
                    HMWP_Classes_ObjController::getClass('HMWP_Models_Compatibility')->checkBlacklistIPs();
                }

                //if is set to hide the urls or not logged in
                if ($url <> '') {

                    /////////////////////////////////////////////////////
                    //Hide Admin URL when changed
                    if (HMWP_Classes_Tools::$default['hmwp_admin_url'] <> HMWP_Classes_Tools::getOption('hmwp_admin_url')) {

                        if (HMWP_Classes_Tools::getOption('hmwp_hide_newadmin')) {
                            if (strpos($url . '/', '/' . HMWP_Classes_Tools::getOption('hmwp_admin_url') . '/') !== false && HMWP_Classes_Tools::getOption('hmwp_hide_admin')) {
                                if (strpos($url . '/', '/' . HMWP_Classes_Tools::getOption('hmwp_admin-ajax_url') . '/') === false) {
                                    $this->getNotFound($url);
                                }
                            }
                        } else {
                            if ($_SERVER['REQUEST_URI'] == site_url(HMWP_Classes_Tools::getOption('hmwp_admin_url'), 'relative')) {
                                wp_safe_redirect($url . '/');
                                exit();
                            }
                        }

                        $paths = array(
                            home_url('wp-admin', 'relative'),
                            home_url('dashboard', 'relative'),
                            home_url('admin', 'relative'),
                            site_url('wp-admin', 'relative'),
                            site_url('dashboard', 'relative'),
                            site_url('admin', 'relative'),
                        );
                        $paths = array_unique($paths);

                        if (HMWP_Classes_Tools::searchInString($url, $paths)) {
                            if (site_url(HMWP_Classes_Tools::getOption('hmwp_admin_url'), 'relative') <> $url && HMWP_Classes_Tools::getOption('hmwp_hide_admin')) {
                                $this->getNotFound($url);
                            }
                        }
                    } else {
                        if (strpos($url, '/wp-admin') !== false && strpos($url, admin_url('admin-ajax.php', 'relative')) === false && HMWP_Classes_Tools::getOption('hmwp_hide_admin')) {
                            $this->getNotFound($url);
                        }
                    }

                    /////////////////////////////////////////////////////
                    //Protect lost password and register
                    if ($http_post) {
                        if (HMWP_Classes_Tools::getOption('hmwp_lostpassword_url') <> '') {
                            if (strpos($url, '/' . HMWP_Classes_Tools::getOption('hmwp_lostpassword_url')) !== false) {
                                $_REQUEST['action'] = 'lostpassword';
                            }
                        }

                        if (HMWP_Classes_Tools::getOption('hmwp_register_url') <> '') {
                            if (strpos($url, '/' . HMWP_Classes_Tools::getOption('hmwp_register_url')) !== false) {
                                $_REQUEST['action'] = 'register';
                            }
                        }
                    }

                    /////////////////////////////////////////////////////
                    //Hide Login URL when changed
                    if (HMWP_Classes_Tools::getOption('hmwp_hide_wplogin') || HMWP_Classes_Tools::getOption('hmwp_hide_login')) {

                        if (HMWP_Classes_Tools::$default['hmwp_login_url'] <> HMWP_Classes_Tools::getOption('hmwp_login_url')) {

	                        $paths = array(
		                        home_url('wp-login.php', 'relative'),
		                        home_url('wp-login', 'relative'),
		                        site_url('wp-login.php', 'relative'),
		                        site_url('wp-login', 'relative'),
	                        );

	                        if (!HMWP_Classes_Tools::isCloudPanel() && !HMWP_Classes_Tools::isWpengine() && $_SERVER['REQUEST_METHOD'] <> 'POST' && HMWP_Classes_Tools::getOption('hmwp_hide_login')) {

		                        $paths[] = home_url('login', 'relative');
		                        $paths[] = site_url('login', 'relative');

	                        }

	                        $paths = array_unique($paths);

                            if (HMWP_Classes_Tools::searchInString($url, $paths)) {

                                if (site_url(HMWP_Classes_Tools::getOption('hmwp_login_url'), 'relative') <> $url) {
                                    $this->getNotFound($url);
                                }

                            }
                        } elseif (defined('HMWP_DEFAULT_LOGIN') && HMWP_DEFAULT_LOGIN <> HMWP_Classes_Tools::$default['hmwp_login_url']) {

	                        $paths = array(
		                        home_url('wp-login.php', 'relative'),
		                        home_url('wp-login', 'relative'),
		                        site_url('wp-login.php', 'relative'),
		                        site_url('wp-login', 'relative'),
	                        );

	                        if (HMWP_Classes_Tools::getOption('hmwp_hide_login')) {

		                        $paths[] = home_url('login', 'relative');
		                        $paths[] = site_url('login', 'relative');

	                        }

	                        $paths = array_unique($paths);

	                        if (HMWP_Classes_Tools::searchInString($url, $paths)) {

		                        if (site_url(HMWP_DEFAULT_LOGIN, 'relative') <> $url) {
			                        $this->getNotFound($url);
		                        }

	                        }
                        }
                    }

                    /////////////////////////////////////////////////////
                    //Hide the author url when changed
                    if (HMWP_Classes_Tools::$default['hmwp_author_url'] <> HMWP_Classes_Tools::getOption('hmwp_author_url')) {
                        $paths = array(
                            home_url('author', 'relative'),
                            site_url('author', 'relative'),
                        );
                        if (HMWP_Classes_Tools::searchInString($url, $paths)) {
                            $this->getNotFound($url);
                        }
                    }

	                /////////////////////////////////////////////////////
	                //hide the /xmlrpc.php path when switched on
	                if (HMWP_Classes_Tools::getOption('hmwp_disable_xmlrpc')) {
		                $paths = array(
			                home_url('xmlrpc.php', 'relative'),
			                home_url('wp-trackback.php', 'relative'),
			                site_url('xmlrpc.php', 'relative'),
			                site_url('wp-trackback.php', 'relative'),
		                );
		                if (HMWP_Classes_Tools::searchInString($url, $paths)) {
			                $this->getNotFound($url);
		                }
	                }

                    /////////////////////////////////////////////////////
                    //disable rest api
	                if (HMWP_Classes_Tools::getOption('hmwp_disable_rest_api')) {
		                $paths = array(
			                home_url('wp-json', 'relative'),
			                home_url(HMWP_Classes_Tools::getOption('hmwp_wp-json'), 'relative'),
		                );
		                if (HMWP_Classes_Tools::searchInString($url, $paths)) {
			                $this->getNotFound($url);
		                }
	                }

                    /////////////////////////////////////////////////////
                    //Hide the common php file in case of other servers
                    $paths = array(
                        home_url('install.php', 'relative'),
                        home_url('upgrade.php', 'relative'),
                        home_url('wp-config.php', 'relative'),
                        site_url('install.php', 'relative'),
                        site_url('upgrade.php', 'relative'),
                        site_url('wp-config.php', 'relative'),
                    );
                    if (HMWP_Classes_Tools::searchInString($url, $paths)) {
                        $this->getNotFound($url);
                    }

                    //hide the wp-signup for WP Multisite
                    if (!HMWP_Classes_Tools::isMultisites()) {
                        $paths = array(
                            home_url('wp-signup.php', 'relative'),
                            site_url('wp-signup.php', 'relative'),
                        );
                        if (HMWP_Classes_Tools::searchInString($url, $paths)) {
                            $this->getNotFound($url);
                        }
                    }
                    /////////////////////////////////////////////////////

                }
            }

	        //reactivate the change of the paths in home and site url
	        add_filter('hmwp_change_home_url', '__return_true');
	        add_filter('hmwp_change_site_url', '__return_true');

        }
    }

    /**
     * Return 404 page or redirect
     *
     * @param  $url
     * @throws Exception
     */
    public function getNotFound( $url )
    {
        if (HMWP_Classes_Tools::getOption('hmwp_url_redirect') == '404' ) {
            if (HMWP_Classes_Tools::isThemeActive('Pro') ) {
                global $wp_query;
                $wp_query->is_404 = true;

                wp_safe_redirect(home_url('404'));
            } else {
                $this->get404Page();
            }
        } else if (HMWP_Classes_Tools::getOption('hmwp_url_redirect') == 'NFError' ) {
            $this->get404Page();
        } else if (HMWP_Classes_Tools::getOption('hmwp_url_redirect') == 'NAError' ) {
            $this->get403Error();
        } elseif (HMWP_Classes_Tools::getOption('hmwp_url_redirect') == '.' ) {
            //redirect to front page
            wp_safe_redirect(home_url());
        } else {
            //redirect to custom page
            wp_safe_redirect(home_url(HMWP_Classes_Tools::getOption('hmwp_url_redirect')));
        }

        die();
    }

    /**
     * Display 404 page to bump bots and bad guys
     *
     * @param  bool $usetheme If true force displaying basic 404 page
     * @throws Exception
     */
    function get404Page( $usetheme = false )
    {
        global $wp_query;

        //Initialize WordPress Filesystem
        $wp_filesystem = HMWP_Classes_ObjController::initFilesystem();

        try {
            if (function_exists('status_header') ) {
                status_header('404');
            }
            if (isset($wp_query) && is_object($wp_query) ) {
                $wp_query->set_404();
            }
            if ($usetheme ) {
                $template = null;
                if (function_exists('get_404_template') ) {
                    $template = get_404_template();
                }
                if (function_exists('apply_filters') ) {
                    $template = apply_filters('hmwp_404_template', $template);
                }
                if ($template && $wp_filesystem->exists($template) ) {
                    ob_start();
                        include $template;
                        echo $this->find_replace(ob_get_clean());
                    exit();
                }
            }

            header('HTTP/1.0 404 Not Found', true, 404);
            echo '<html><head><title>404 Not Found</title></head><body><h1>Not Found</h1><p>The requested URL ' . ((isset($_SERVER['REQUEST_URI'])) ? esc_url($_SERVER['REQUEST_URI']) : '') . ' was not found on this server.</p></body></html>';
            exit();

        } catch ( Exception $e ) {
        }

    }

    /**
     * Display 403 error to bump bots and bad guys
     *
     * @throws Exception
     */
    function get403Error()
    {

        try {

            header('HTTP/1.0 403 Forbidden', true, 403);
            echo '<html><head><title>403 Forbidden</title></head><body><h1>Forbidden</h1><p>You don\'t have the permission to access ' . ((isset($_SERVER['REQUEST_URI'])) ? esc_url($_SERVER['REQUEST_URI']) : '') . ' on this server.</p></body></html>';
            exit();

        } catch ( Exception $e ) {
        }

    }

    /*************************************
     *
     * FIND AND REPLACE
     *****************************************/
    /**
     * repare the replace function
     *
     * @throws Exception
     */
    public function prepareFindReplace()
    {
        $find = $replace = $findtext = $remplacetext = $findencoded = $findencodedfinal = $replaceencoded = $replaceencodedfinal = $findcdns = $replacecdns = $findurlmapping = $replaceurlmapping = array();

        //If there are rewrite rules
        if (!empty($this->_replace) ) {

            //If URL Mapping is activated
            if(HMWP_Classes_Tools::getOption('hmwp_mapping_cdn_show')) {
                if ($cdns = HMWP_Classes_ObjController::getClass('HMWP_Models_Compatibility')->findCDNServers() ) {
                    //if there are CDNs added
                    if(!empty($cdns)) {

                        $cdns = array_unique($cdns); //remove duplicates
                        foreach ($cdns as $cdn) {
                            $cdn = parse_url($cdn, PHP_URL_HOST) . parse_url($cdn, PHP_URL_PATH) . '/';

                            //Add PORT if different from 80
                            if (parse_url($cdn, PHP_URL_PORT) && parse_url($cdn, PHP_URL_PORT) <> 80) {
                                $cdn = parse_url($cdn, PHP_URL_HOST) . ':' . parse_url($cdn, PHP_URL_PORT) . parse_url($cdn, PHP_URL_PATH) . '/';
                            }

                            $findcdn = preg_replace('/^/', $cdn, (array)$this->_replace['from']);
                            $replacecdn = preg_replace('/^/', $cdn, (array)$this->_replace['to']);

                            //merge the urls
                            $findcdns = array_merge($findcdns, $findcdn);
                            $replacecdns = array_merge($replacecdns, $replacecdn);
                        }

                    }
                }
            }

            //make sure the paths are without schema
            $find = array_map(array($this, 'addDomainUrl'), (array)$this->_replace['from']);
            $replace = array_map(array($this, 'addDomainUrl'), (array)$this->_replace['to']);

	        //make sure the main domain is added on wp multisite with subdirectories
	        //used for custom wp-content, custom wp-includes, custom uploads
	        if(HMWP_Classes_Tools::isMultisiteWithPath() || HMWP_Classes_Tools::isDifferentWPContentPath()){
		        $find = array_merge($find, array_map(array($this, 'addMainDomainUrl'), (array)$this->_replace['from']));
		        $replace = array_merge($replace, array_map(array($this, 'addMainDomainUrl'), (array)$this->_replace['to']));
	        }

            //change the javascript urls
            $findencoded = array_map(array($this, 'changeEncodedURL'), (array)$this->_replace['from']);
            $replaceencoded = array_map(array($this, 'changeEncodedURL'), (array)$this->_replace['to']);

            //change the javascript urls
            $findencodedfinal = array_map(
                array(
                $this,
                'changeEncodedURLFinal'
                ), (array)$this->_replace['from']
            );

            $replaceencodedfinal = array_map(array($this, 'changeEncodedURLFinal'), (array)$this->_replace['to']);

        }

        //If URL Mapping is activated
        if(HMWP_Classes_Tools::getOption('hmwp_mapping_url_show')) {
            $hmwp_url_mapping = json_decode(HMWP_Classes_Tools::getOption('hmwp_url_mapping'), true);
            if (isset($hmwp_url_mapping['from']) && !empty($hmwp_url_mapping['to'])) {
                $findurlmapping = $hmwp_url_mapping['from'];
                $replaceurlmapping = $hmwp_url_mapping['to'];
                unset($hmwp_url_mapping);
            }
        }


        //merge the urls
        $this->_replace['from'] = array_merge($findtext, $findcdns, $find, $findencoded, $findencodedfinal, $findurlmapping);
        $this->_replace['to'] = array_merge($remplacetext, $replacecdns, $replace, $replaceencoded, $replaceencodedfinal, $replaceurlmapping);

    }

	/**
	 * Add the main domain into URL
	 * Used for multisites with paths
	 *
	 * @param $url
	 *
	 * @return string
	 */
	public function addMainDomainUrl( $url )
	{

		//Set the blog URL
		$mainsiteurl = str_replace('www.', '', parse_url(site_url(), PHP_URL_HOST) );

		if (strpos($url, $mainsiteurl) === false ) {
			return $mainsiteurl . '/' . $url;
		}

		return $url;
	}

    /**
     * Add the domain into URL
     *
     * @param $url
     *
     * @return string
     */
    public function addDomainUrl( $url )
    {
        if (strpos($url, $this->getSiteUrl()) === false ) {
            return $this->getSiteUrl() . '/' . $url;
        }

        return $url;
    }

    /**
     * Remove the Schema from url
     * Return slashed urls for javascript urls
     *
     * @param $url
     *
     * @return string
     */
    public function changeEncodedURL( $url )
    {
        if (strpos($url, $this->getSiteUrl()) === false ) {
            return str_replace('/', '\/', $this->getSiteUrl() . '/' . $url);
        }

        return $url;
    }

    /**
     * @param $url
     *
     * @return mixed
     */
    public function changeEncodedURLFinal( $url )
    {
        if (strpos($url, $this->getSiteUrl()) === false ) {
            return str_replace('/', '\/', rtrim($this->getSiteUrl() . '/' . $url, '/'));
        }

        return $url;
    }

    /**
     * Change content
     *
     * @param $content
     *
     * @return null|string|string[]
     * @throws Exception
     */
    public function find_replace( $content)
    {

	    if (HMWP_Classes_Tools::doChangePaths() && apply_filters('hmwp_process_find_replace', true) ) {

            if (is_string($content) && $content <> '') {

                //if the changes were made already, return the content
                if (strpos($content, HMWP_Classes_Tools::$default['hmwp_wp-content_url']) === false
                    && strpos($content, HMWP_Classes_Tools::$default['hmwp_wp-includes_url']) === false
                    && $this->_replaced
                ) {
                    return $content;
                }

	            //change and replace paths
	            $this->clearRedirect();
	            //builder the redirects
	            $this->buildRedirect();
	            //make sure to include the blog url
	            $this->prepareFindReplace();

	            //fix the relative links before
	            if (HMWP_Classes_Tools::getOption('hmwp_fix_relative')) {
		            $content = $this->fixRelativeLinks($content);
	            }

                //Find & Replace the tags and headers
                $content = $this->replaceHeadersAndTags($content);

                //Do the path replace for all paths
                if (isset($this->_replace['from']) && isset($this->_replace['to']) && !empty($this->_replace['from']) && !empty($this->_replace['to'])) {
                    $content = str_ireplace($this->_replace['from'], $this->_replace['to'], $content);
                }

                //If Text Mapping is activated
                if (HMWP_Classes_Tools::getOption('hmwp_mapping_text_show')) {
                    //Replace classes and IDs
                    $content = $this->replaceTextMapping($content);
                }

                //rename the CSS in Dynamic File mode to make sure they are not cached by Nginx of Apache
                if (HMW_DYNAMIC_FILES && !is_admin()) {
                    $content = preg_replace(
                        array(
                        '/(<link[^>]+' . str_replace('/', '\/', $this->getSiteUrl()) . '[^>]+).(css|scss)([\'|"|\?][^>]+type=[\'"]text\/css[\'"][^>]+>)/i',
                        '/(<link[^>]+type=[\'"]text\/css[\'"][^>]+' . str_replace('/', '\/', $this->getSiteUrl()) . '[^>]+).(css|scss)([\'|"|\?][^>]+>)/i',
                        '/(<script[^>]+' . str_replace('/', '\/', $this->getSiteUrl()) . '[^>]+).(js)([\'|"|\?][^>]+>)/i',
                        ), '$1.$2h$3', $content
                    );
                }

            }

            //emulate other CMS on request
            $content = $this->emulateCMS($content);

            //Set the replacement action to prevent multiple calls
            $this->_replaced = true;
        }
        //Return the buffer
        return $content;
    }

    /**
     * Add CMS Emulators for theme detectors
     *
     * @param  $content
     * @return string|string[]|null
     */
    public function emulateCMS( $content )
    {
        $generator = '';
        $header = array();

        //emulate other CMS
        if($emulate = HMWP_Classes_Tools::getOption('hmwp_emulate_cms')) {

            if (strpos($emulate, 'drupal') !== false ) {
                switch ($emulate){
                    case 'drupal7':
                        $generator = 'Drupal 7 (https://www.drupal.org)';
                        break;
                    case 'drupal':
                        $generator = 'Drupal 8 (https://www.drupal.org)';
                        break;
                    case 'drupal9':
                        $generator = 'Drupal 9 (https://www.drupal.org)';
                        break;
                    case 'drupal10':
                        $generator = 'Drupal 10 (https://www.drupal.org)';
                        break;
                    default:
                        $generator = 'Drupal (https://www.drupal.org)';
                        break;
                }

                $header['MobileOptimized'] = '<meta name="MobileOptimized" content="width" />';
                $header['HandheldFriendly'] = '<meta name="HandheldFriendly" content="true" />';

            }elseif (strpos($emulate, 'joomla') !== false ) {
                switch ($emulate){
                    case 'joomla1':
                        $generator = 'Joomla! 1.5 - Open Source Content Management';
                        break;
                    default:
                        $generator = 'Joomla! - Open Source Content Management';
                        break;
                }

            }

            $header['generator'] = '<meta name="generator" content="'.apply_filters('hmwp_emulate_cms',$generator).'" />';
            $header_str = str_replace('$', '\$', join("\n", $header));
            $content = @preg_replace('/(<head(\s[^>]*|)>)/si', sprintf("$1\n%s", $header_str) . PHP_EOL, $content, 1);
        }

        return $content;
    }

    /**
     * Rename the paths in URL with the new ones
     *
     * @param  $url
     * @return mixed
     * @throws Exception
     */
    public function find_replace_url( $url )
    {
        if (strpos($url, HMWP_Classes_Tools::$default['hmwp_wp-content_url']) !== false || strpos($url, HMWP_Classes_Tools::$default['hmwp_wp-includes_url']) !== false ) {

            //change and replace paths
            if (empty($this->_replace)  ) {
                //builder the redirects
                $this->buildRedirect();

                //make sure to include the blog url
                $this->prepareFindReplace();
            }

            if (isset($this->_replace['from']) && isset($this->_replace['to']) && !empty($this->_replace['from']) && !empty($this->_replace['to']) ) {

				if(!empty($this->_replace['rewrite'])) {
					foreach ( $this->_replace['rewrite'] as $index => $value ) {
						//add only the paths or the design path
						if ( ( $index && isset( $this->_replace['to'][ $index ] ) && substr( $this->_replace['to'][ $index ], - 1 ) == '/' )
						     || strpos( $this->_replace['to'][ $index ], '/' . HMWP_Classes_Tools::getOption( 'hmwp_themes_style' ) )
						) {
							$this->_replace['from'][] = $this->_replace['from'][ $index ];
							$this->_replace['to'][]   = $this->_replace['to'][ $index ];
						}
					}
				}

                //Don't replace include if content was already replaced
                $url = str_ireplace($this->_replace['from'], $this->_replace['to'], $url);
            }
        }

        return $url;
    }

    /**
     * Replace the wp-json URL is changed
     *
     * @param $url
     *
     * @return mixed
     */
    public function replace_rest_api( $url )
    {
        //Modify rest-api wp-json
        if (HMWP_Classes_Tools::$default['hmwp_wp-json'] <> HMWP_Classes_Tools::getOption('hmwp_wp-json') ) {
            $url = HMWP_Classes_Tools::getOption('hmwp_wp-json');
        }

        return $url;
    }

    /**
     * Change the image path to absolute when in feed
     *
     * @param string $content
     *
     * @return string
     */
    public function fixRelativeLinks( $content )
    {
	    $content = preg_replace_callback(
		    array('~(\s(href|src)\s*[=|:]\s*[\"\'])([^\"\']+)([\"\'])~i',
			    '~(\W(url\s*)[\(\"\']+)([^\)\"\']+)([\)\"\']+)~i',
			    '~(([\"\']url[\"\']\s*:)\s*[\"\'])([^\"\']+)([\"\'])~i',
			    '~((=|:)\s*[\"\'])(\\\/[^\"\']+)([\"\'])~i'
		    ),
		    array($this, 'replaceLinks'),
		    $content
	    );
	    return $content;
    }

    /**
     * If relative links then transform them to absolute
     *
     * @param $found
     *
     * @return string
     */
    public function replaceLinks( $found )
    {
        $url = $found[3];

        if (strpos($url, '//') === false && strpos($url, '\/\/') === false ) {
            if (strpos($url, HMWP_Classes_Tools::$default['hmwp_wp-content_url']) !== false
                || strpos($url, HMWP_Classes_Tools::$default['hmwp_wp-includes_url']) !== false
                || strpos($url, HMWP_Classes_Tools::$default['hmwp_admin_url']) !== false
                || strpos($url, HMWP_Classes_Tools::$default['hmwp_login_url']) !== false
            ) {
                return $found[1] . $this->_rel2abs($url) . $found[4];
            }
        }

        return $found[0];

    }

    /**
     * Change Relative links to Absolute links
     *
     * @param $rel
     *
     * @return string
     */
    protected function _rel2abs( $rel )
    {
        $scheme = $host = $path = '';
        $backslash = false;

        //if relative with preview dir
        if (strpos($rel, "../") !== false ) {
            return $rel;
        }

        // return if already absolute URL
        if (parse_url($rel, PHP_URL_SCHEME) != '' ) {
            return $rel;
        }

        // parse base URL  and convert to local variables: $scheme, $host,  $path
        extract(parse_url(home_url()));

        //add the scheme to the URL
        if (strpos($rel, "//") === 0 ) {
            return $scheme . ':' . $rel;
        }

        //if url encoded, rezolve until absolute
        if (strpos($rel, '\/') !== false ) {
            //if backslashes then change the URLs to normal
            $backslash = true;
            $rel = str_replace('\/', '/', $rel);
        }

        // queries and anchors
        if ($rel[0] == '#' || $rel[0] == '?' ) {
            return home_url() . $rel;
        }

        // dirty absolute URL
        if ($path <> '' && (strpos($rel, $path . '/') === false || strpos($rel, $path . '/') > 0) ) {
            $abs = $host . $path . "/" . $rel;
        } else {
            $abs = $host . "/" . $rel;
        }

        // replace '//' or  '/./' or '/foo/../' with '/'
        $abs = preg_replace("/(\/\.?\/)/", "/", $abs);
        $abs = preg_replace("/\/(?!\.\.)[^\/]+\/\.\.\//", "/", $abs);

        // absolute URL is ready!
        if ($backslash ) {
            return str_replace('/', '\/', $scheme . '://' . $abs);
        } else {
            return $scheme . '://' . $abs;

        }
    }

    /**
     * Remove the comments from source code
     *
     * @param $m
     *
     * @return string
     */
    protected function _commentRemove( $m )
    {
        return (0 === strpos($m[1], '[') || false !== strpos($m[1], '<!['))
            ? $m[0]
            : '';
    }


    /**
     * Remove the page headers
     */
    public function hideHeaders()
    {

        //Remove Powered by, X-Cf-Powered, Server headers
        if (HMWP_Classes_Tools::getOption('hmwp_hide_unsafe_headers')) {

            //Remove WordPress link from headers
            header(sprintf('%s: %s', 'Link', '<' . home_url() . '>; rel=shortlink'), true);

            if (function_exists('header_remove') ) {
                header_remove("x-powered-by");
                header_remove("x-cf-powered-by");
                header_remove("server");
                header('X-Powered-By: -');
            }
        }

        //Remove Pingback from website header
        if (HMWP_Classes_Tools::getOption('hmwp_disable_xmlrpc')) {

            if (function_exists('header_remove') ) {
                header_remove("x-pingback");
            }

        }
    }

    /**
     * Add all needed security headers
     */
    public function addSecurityHeader()
    {

        if (HMWP_Classes_Tools::getOption('hmwp_security_header') ) {

            $headers = (array)HMWP_Classes_Tools::getOption('hmwp_security_headers');

            if(!empty($headers)) {
                foreach ($headers as $name => $value) {
                    if ($value <> '') {
                        header($name . ": " . $value);
                    }
                }
            }

        }

    }

    /**
     * Find the text from Text Mapping in the source code
     *
     * @param $content
     *
     * @return mixed|string|string[]|null
     */
    public function replaceTextMapping( $content )
    {

        $findtextmapping = array();

        //Change the text in css and js files only for visitors
        if (HMWP_Classes_Tools::getOption('hmwp_mapping_file')
            && function_exists('is_user_logged_in') && is_user_logged_in()
        ) {
            return $content;
        }

        //Replace custom classes
        $hmwp_text_mapping = json_decode(HMWP_Classes_Tools::getOption('hmwp_text_mapping'), true);
        if (isset($hmwp_text_mapping['from']) && !empty($hmwp_text_mapping['from'])
            && isset($hmwp_text_mapping['to']) && !empty($hmwp_text_mapping['to'])
        ) {

            foreach ( $hmwp_text_mapping['to'] as &$value ) {
                if ($value <> '' ) {
                    if (strpos($value, '{rand}') !== false ) {
                        $value = str_replace('{rand}', HMWP_Classes_Tools::generateRandomString(5), $value);
                    } elseif (strpos($value, '{blank}') !== false ) {
                        $value = str_replace('{blank}', '', $value);
                    }
                }
            }

            $this->_findtextmapping = $hmwp_text_mapping['from'];
            $this->_replacetextmapping = $hmwp_text_mapping['to'];

            if (!empty($this->_findtextmapping) && !empty($this->_replacetextmapping) ) {
                //change only the classes and ids
                if (HMWP_Classes_Tools::getOption('hmwp_mapping_classes') ) {
                    foreach ($this->_findtextmapping as $from ) {
                        $findtextmapping[] = '/\s(class|id|aria-labelledby|aria-controls|data-lp-type|data-elementor-type|data-widget_type)=[\'"][^\'"]*(' . addslashes($from) . ')[^\'"]*[\'"]/';
                        if (HMWP_Classes_Tools::getOption('hmwp_mapping_file') ) {
                            $findtextmapping[] = "'<(style|script)((?!src|>).)*>.*?</(style|script)>'is";
                        }
                        $findtextmapping[] = "'<script((?!src|>).)*>'is";
                        $findtextmapping[] = "'<style[^>]*>.*?</style>'is";
                        $findtextmapping[] = "'<(a|div)[^>]*data-" . addslashes($from) . "[^>]*[^/]>'is";
                    }

                    if (!empty($findtextmapping) ) {
                        $content = preg_replace_callback(
                            $findtextmapping, array(
                            $this,
                            'replaceText'
                            ), $content
                        );
                    }

                } else {
                    $content = str_ireplace($this->_findtextmapping, $this->_replacetextmapping, $content);
                }
            }

            unset($hmwp_text_mapping);
        }

        return $content;
    }

    /**
     * Find & Replace the tags and headers
     *
     * @param  $content
     * @return string|string[]|null
     */
    public function replaceHeadersAndTags( $content )
    {
        $find = $replace = array();

        //Remove source comments
        if (HMWP_Classes_Tools::getOption('hmwp_hide_comments') ) {
            $content = preg_replace_callback('/<!--([\\s\\S]*?)-->/', array($this, '_commentRemove'), $content);
        }

        //Remove versions
        if (HMWP_Classes_Tools::getOption('hmwp_hide_version') ) {
            if(HMWP_Classes_Tools::getOption('hmwp_hide_version_random') ){
                if(function_exists('is_user_logged_in') && is_user_logged_in()){
                    HMWP_Classes_Tools::saveOptions('hmwp_hide_version_random', mt_rand(11111,99999));
                }
                $find[] = '/(\?|\&#038;|\&)ver=[0-9a-zA-Z\.\_\-\+]+(\&#038;|\&)/';
                $replace[] = '$1rnd=' . HMWP_Classes_Tools::getOption('hmwp_hide_version_random'). '$2';
                $find[] = '/(\?|\&#038;|\&)ver=[0-9a-zA-Z\.\_\-\+]+("|\')/';
                $replace[] = '$1rnd=' . HMWP_Classes_Tools::getOption('hmwp_hide_version_random'). '$2';
            }else{
                $find[] = '/(\?|\&#038;|\&)ver=[0-9a-zA-Z\.\_\-\+]+(\&#038;|\&)/';
                $replace[] = '$1';
                $find[] = '/(\?|\&#038;|\&)ver=[0-9a-zA-Z\.\_\-\+]+("|\')/';
                $replace[] = '$2';
            }
        }

        if (HMWP_Classes_Tools::getOption('hmwp_hide_in_sitemap')  && HMWP_Classes_Tools::getOption('hmwp_hide_author_in_sitemap')) {
            $find[] = '/(<\?xml-stylesheet[\s])([^>]+>)/i';
            $replace[] = '';
        }

        //Remove the Generator link
        if (HMWP_Classes_Tools::getOption('hmwp_hide_generator') ) {
            $find[] = '/<meta[^>]*name=[\'"]generator[\'"][^>]*>/i';
            $replace[] = '';
        }

        //Remove WP prefetch domains that reveal the CMS
        if (HMWP_Classes_Tools::getOption('hmwp_hide_prefetch') ) {
            $find[] = '/<link[^>]*rel=[\'"]dns-prefetch[\'"][^>]*w.org[^>]*>/i';
            $replace[] = '';

            $find[] = '/<link[^>]*rel=[\'"]dns-prefetch[\'"][^>]*wp.org[^>]*>/i';
            $replace[] = '';

            $find[] = '/<link[^>]*rel=[\'"]dns-prefetch[\'"][^>]*wordpress.org[^>]*>/i';
            $replace[] = '';
        }

        //Remove the Pingback link from source code
        if (HMWP_Classes_Tools::getOption('hmwp_disable_xmlrpc') ) {
            $find[] = '/(<link[\s])rel=[\'"]pingback[\'"][\s]([^>]+>)/i';
            $replace[] = '';
        }

        //remove Style IDs
        if (HMWP_Classes_Tools::getOption('hmwp_hide_styleids') ) {
            $find[] = '/(<link[^>]*rel=[^>]+)[\s]id=[\'"][0-9a-zA-Z._-]+[\'"]([^>]*>)/i';
            $replace[] = '$1 $2';

            $find[] = '/(<style[^>]*)[\s]id=[\'"][0-9a-zA-Z._-]+[\'"]([^>]*>)/i';
            $replace[] = '$1 $2';

            $find[] = '/(<script[^>]*)[\s]id=[\'"][0-9a-zA-Z._-]+[\'"]([^>]*>)/i';
            $replace[] = '$1 $2';
        }

        //remove the Feed from header
        if (HMWP_Classes_Tools::getOption('hmwp_hide_feed') ) {
            $find[] = '/<link[^>]*rel=[\'"]alternate[\'"][^>]*type=[\'"]application\/rss\+xml[\'"][^>]*>/i';
            $replace[] = '';

            $find[] = '/<link[^>]*type=[\'"]application\/rss\+xml[\'"][^>]*rel=[\'"]alternate[\'"][^>]*>/i';
            $replace[] = '';

	        $find[] = '/<link[^>]*type=[\'"]application\/atom\+xml[\'"][^>]*>/i';
	        $replace[] = '';
        }

        //remove wp-json
        if (HMWP_Classes_Tools::$default['hmwp_wp-json'] <> HMWP_Classes_Tools::getOption('hmwp_wp-json') ) {
            $find[] = '/(<link[\s])rel=[\'"]https:\/\/api.w.org\/[\'"][\s]([^>]+>)/i';
            $replace[] = '';
        }

        return preg_replace($find, $replace, $content);
    }

    /**
     * Replace the current buffer by content type
     *
     * @throws Exception
     */
    public function findReplaceXML()
    {

        //Force to change the URL for xml content types
	    if (HMWP_Classes_Tools::isContentHeader(array('text/xml','application/xml')) ||
	        (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'],'.xml') !== false)) {

	        //change and replace paths
	        $this->clearRedirect();
	        //builder the redirects
	        $this->buildRedirect();
	        //make sure to include the blog url
	        $this->prepareFindReplace();

	        $content = ob_get_contents();

            if($content <> '') {
                $content = str_ireplace($this->_replace['from'], $this->_replace['to'], $content);

				if(HMWP_Classes_Tools::getOption('hmwp_hide_author_in_sitemap')) {
					$content = $this->replaceHeadersAndTags( $content );
				}

                ob_end_clean();
                echo $content;
            }

        }

    }

    /**
     * Replace the robotx file fo rsecurity
     *
     * @throws Exception
     */
    public function replaceRobots()
    {

        //Force to change the URL for xml content types
        if (HMWP_Classes_Tools::isContentHeader(array('text/plain')) ||
            (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'],'.txt') !== false)) {

	        //change and replace paths
	        $this->clearRedirect();
	        //builder the redirects
	        $this->buildRedirect();

            $content = ob_get_contents();

            if($content <> '') {

                $array = explode("\n", $content);

                foreach ($array as $index => $row) {
                    if (strpos($row, '/wp-admin') !== false || strpos($row, '/wp-login.php') !== false) {
                        unset($array[$index]);
                    }
                }

                $content = join("\n", $array);
                $content = str_ireplace($this->_replace['from'], $this->_replace['to'], $content);

                ob_end_clean();
                echo $content;
            }

        }

    }


    /**
     * Callback for Text Mapping
     *
     * @param $found
     *
     * @return mixed
     */
    public function replaceText( $found )
    {
        $content = $found[0];
        if ($content <> '' ) {
            $content = str_ireplace($this->_findtextmapping, $this->_replacetextmapping, $content);
        }

        return $content;
    }

    /**
     * Disable the emoji icons
     */
    public function disableEmojicons()
    {

        // all actions related to emojis
        remove_action('admin_print_styles', 'print_emoji_styles');
        remove_action('wp_head', 'print_emoji_detection_script', 7);
        remove_action('admin_print_scripts', 'print_emoji_detection_script');
        remove_action('wp_print_styles', 'print_emoji_styles');
        remove_filter('wp_mail', 'wp_staticize_emoji_for_email');
        remove_filter('the_content_feed', 'wp_staticize_emoji');
        remove_filter('comment_text_rss', 'wp_staticize_emoji');
        add_filter('emoji_svg_url', '__return_false');

        // filter to remove TinyMCE emojis
        add_filter(
            'tiny_mce_plugins', function ( $plugins ) {
                if (is_array($plugins) ) {
                    return array_diff($plugins, array('wpemoji'));
                } else {
                    return array();
                }
            }
        );
    }

    /**
     * Hide the rest api from the source code
     *
     * @return void
     * @throws Exception
     */
    public function hideRestApi()
    {
        remove_action('wp_head', 'rest_output_link_wp_head');
        remove_action('template_redirect', 'rest_output_link_header', 11);

    }

    /**
     * Disable the rest Route param access
     *
     * @return void
     * @throws Exception
     */
    public function hideRestRouteParam()
    {
        /////////////////////////////////////////////////////
        //hide rest_route when rest api path is changed
        if(HMWP_Classes_Tools::getValue('rest_route')){
            $this->getNotFound(false);
        }
    }

    /**
     * Disable the Rest Api access
     */
    public function disableRestApi()
    {
        remove_action('init', 'rest_api_init');
        remove_action('rest_api_init', 'rest_api_default_filters');
        remove_action('parse_request', 'rest_api_loaded');
        remove_action('wp_head', 'rest_output_link_wp_head');
        remove_action('template_redirect', 'rest_output_link_header', 11);

        add_filter('json_enabled', '__return_false');
        add_filter('json_jsonp_enabled', '__return_false');
    }

    /**
     * Disable the embeds
     */
    public function disableEmbeds()
    {
        // Remove the REST API endpoint.
        remove_action('rest_api_init', 'wp_oembed_register_route');

        // Turn off oEmbed auto discovery.
        // Don't filter oEmbed results.
        remove_filter('oembed_dataparse', 'wp_filter_oembed_result');

        // Remove oEmbed discovery links.
        remove_action('wp_head', 'wp_oembed_add_discovery_links');

        // Remove oEmbed-specific JavaScript from the front-end and back-end.
        remove_action('wp_head', 'wp_oembed_add_host_js');
    }

    /**
     * Disable Windows Live Write
     */
    public function disableManifest()
    {
        remove_action('wp_head', 'wlwmanifest_link');
    }

    /**
     * Disable Really Simple Discovery
     * Disable RSD support from XML RPC
     */
    public function disableRsd()
    {
        remove_action('wp_head', 'rsd_link');
        remove_action('xmlrpc_rsd_apis', 'rest_output_rsd');
    }

    /**
     * Disable to commend from W3 Total Cache
     */
    public function disableComments()
    {
        global $wp_super_cache_comments;
        remove_all_filters('w3tc_footer_comment');
        $wp_super_cache_comments = false;
    }

    /**
     * Replace the Error Message that contains WordPress
     *
     * @param string $message
     * @param string $error
     *
     * @return string
     */
    public function replace_error_message( $message, $error )
    {
        if (is_protected_endpoint() ) {
            $message = esc_html__('There has been a critical error on your website. Please check your site admin email inbox for instructions.');
        } else {
            $message = esc_html__('There has been a critical error on your website.');
        }

        return $message;
    }



}
