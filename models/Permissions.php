<?php
/**
 * Change Files permissions
 *
 * @file  The Files permissions file
 * @package HMWP/Permissions
 * @since 7.3.0
 */
defined('ABSPATH') || die('Cheating uh?');

class HMWP_Models_Permissions {

    protected $paths = array();

    public function __construct() {

	    $paths = array();

        $server_config_file = HMWP_Classes_ObjController::getClass('HMWP_Models_Rules')->getConfFile();
        $wp_config_file = HMWP_Classes_Tools::getConfigFile();
        $wp_upload_dir = $this->getUploadDir();

        //Set the main paths to check
        if(!HMWP_Classes_Tools::isWindows()){
            $paths =  array(
                ABSPATH => HMW_DIR_PERMISSION,
                ABSPATH . HMWP_Classes_Tools::getDefault('hmwp_wp-includes_url') => HMW_DIR_PERMISSION,
                ABSPATH . HMWP_Classes_Tools::getDefault('hmwp_admin_url') => HMW_DIR_PERMISSION,
                ABSPATH . HMWP_Classes_Tools::getDefault('hmwp_admin_url') . '/js' => HMW_DIR_PERMISSION,
                ABSPATH . HMWP_Classes_Tools::getDefault('hmwp_login_url')  => HMW_FILE_PERMISSION,
                WP_CONTENT_DIR => HMW_DIR_PERMISSION,
                get_theme_root() => HMW_DIR_PERMISSION,
                WP_PLUGIN_DIR => HMW_DIR_PERMISSION,
                $wp_upload_dir => HMW_DIR_PERMISSION,
                //$wp_config_file => HMW_CONFIG_PERMISSION,
               //$server_config_file => HMW_CONFIG_PERMISSION,
            );
        }else{
            $paths = [
                //$wp_config_file => HMW_CONFIG_PERMISSION,
                $server_config_file => HMW_CONFIG_PERMISSION,
            ];
        }

        $this->paths = apply_filters('hmwp_permission_paths', $paths);
    }

    /**
     * Get the uploads directory
     *
     * @return string
     */
    protected function getUploadDir() {
        //get the uploads directory
        if (HMWP_Classes_Tools::isMultisites() && defined('BLOG_ID_CURRENT_SITE') ) {
            switch_to_blog( BLOG_ID_CURRENT_SITE );
            $wp_upload_dir = wp_upload_dir();
            restore_current_blog();
        } else {
            $wp_upload_dir = wp_upload_dir();
        }

        if(isset($wp_upload_dir['basedir']) && $wp_upload_dir['basedir'] <> ''){
            return $wp_upload_dir['basedir'];
        }

        return false;
    }

    /**
     * Return all invalid paths that don't match the recommended permissions
     *
     * @return array
     */
    public function getInvalidPermissions(){

        $values = array();
        $wp_filesystem = HMWP_Classes_ObjController::initFilesystem();

		if(!empty($this->paths)){
			foreach ( $this->paths as $path => $suggested ) {
				if($wp_filesystem->exists($path)){
					$display_path = preg_replace( '/^' . preg_quote( ABSPATH, '/' ) . '/', '', $path );
					$display_path = ltrim( $display_path, '/' );

					if ( empty( $display_path ) ) {
						$display_path = '/';
					}

					//get chmod of the path
					$display_chmod = sprintf("0%d", $wp_filesystem->getchmod($path));

					if($wp_filesystem->is_file($path) ){

						if (HMWP_Classes_Tools::isWindows() ) {
							if($wp_filesystem->is_writable($path)) {
								$values[] = array(
									'path' => $path,
									'suggested' => $suggested,
									'display_path' => $display_path,
									'display_permission' => $display_chmod
								);
							}
						}else {
							$chmod = $wp_filesystem->getchmod($path);
							$suggested = sprintf('%o', $suggested);

							if($suggested < $chmod) {
								$values[] = array(
									'path' => $path,
									'suggested' => $suggested,
									'display_path' => $display_path,
									'display_permission' => $display_chmod
								);
							}
						}

					}else{
						$chmod = $wp_filesystem->getchmod($path);
						$suggested = sprintf('%o', $suggested);

						if($suggested < $chmod) {
							//if it's a directory
							$values[] = array(
								'path' => $path,
								'suggested' => $suggested,
								'display_path' => $display_path,
								'display_permission' => $display_chmod
							);
						}
					}

				}
			}
		}

        return $values;

    }

    /**
     * Change the invalid permissions with the recommended ones
     * @param $value
     *
     * @return bool
     */
    public function changePermissions( $value ) {

        $wp_filesystem = HMWP_Classes_Tools::initFilesystem();

        if(!$value){
            return false;
        }

        try{
            //fix all files and directories permissions
            if ($value == 'complete') {
                $this->changePermissionsAll(ABSPATH, true);
            }

            //get all invalid permissions
            $values = $this->getInvalidPermissions();
            foreach ( $values as $value ) {
                if(!$wp_filesystem->chmod($value['path'], octdec($value['suggested']))){
                    return false;
                }
            }

        }catch (Exception $e){}

        return true;
    }

    /**
     * Changes filesystem permissions.
     *
     * @param string    $file      Path to the file.
     * @param bool      $recursive Optional. If set to true, changes file permissions recursively.
     *                             Default false.
     * @return bool True on success, false on failure.
     */
    private function changePermissionsAll( $file, $recursive = false ) {

        $wp_filesystem = HMWP_Classes_Tools::initFilesystem();

        if ( $wp_filesystem->is_file( $file ) ) {
            if(strpos( $file, '.php' ) !== false){
                $mode = HMW_FILE_PERMISSION;
            }else{
                $mode = 0644;
            }
        } elseif ( $wp_filesystem->is_dir( $file ) ) {
            $mode = HMW_DIR_PERMISSION;
        } else {
            return false;
        }

        if ( ! $recursive || ! $wp_filesystem->is_dir( $file ) ) {
            return $wp_filesystem->chmod( $file, $mode );
        }

        // Is a directory, and we want recursive.
        $file     = trailingslashit( $file );
        $filelist = $wp_filesystem->dirlist( $file );

        foreach ( (array) $filelist as $filename => $filemeta ) {
            $this->changePermissionsAll( $file . $filename,$recursive );
        }

        return true;
    }

}
