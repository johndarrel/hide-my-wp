<?php
/**
 * Brute Force Protection Model
 * Called from Brute Force Class
 *
 * @file  The Brute Force Shortcode file
 * @package HMWP/BruteForce/Shortcode
 * @since 8.1
 */

defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );

class HMWP_Models_Bruteforce_Shortcode {

	public function __construct() {

		// Listen for brute force shortcode on forms
		add_shortcode( 'hmwp_bruteforce', array( $this, 'init' ) );

	}
	/**
	 * Get the brute force using shortcode
	 *
	 * @param  array  $atts
	 * @param  string  $content
	 *
	 * @return string|void
	 * @throws Exception
	 */
	public function init( $atts = array(), $content = '' ) {
		// Set brute force globally
		global $hmwp_bruteforce;

		if ( ! function_exists( 'is_user_logged_in' ) || is_user_logged_in() ) {
			return;
		}

		// Activate Brute Force globally
		$hmwp_bruteforce = true;

		/** @var HMWP_Models_Brute $bruteForceModel */
		$bruteForceModel = HMWP_Classes_ObjController::getClass( 'HMWP_Models_Brute' );

		// Get the active Brute Force name
		$name = $bruteForceModel->getName();

		// If extra script is needed for the shortcode
		$script = '';
		if ( $name == 'Math' ) {
			$script = '
                    <script>
                    function reCaptchaSubmit(e) {
                        var form = this;
                        e.preventDefault();
        
                        var brute_num = document.getElementsByName("brute_num")[0];
                        if(typeof brute_num !== "undefined"){
                            var input = document.createElement("input");
                            input.type = "hidden";
                            input.name = "brute_num" ;
                            input.value = brute_num.value ;
                            form.appendChild(input);
                        }
                        
                        var brute_ck = document.getElementsByName("brute_ck")[0];
                        if(typeof brute_ck !== "undefined"){
                            var input = document.createElement("input");
                            input.type = "hidden";
                            input.name = "brute_ck" ;
                            input.value = brute_ck.value ;
                            form.appendChild(input);
                        }
                        
                        HTMLFormElement.prototype.submit.call(form);
                    }
        
                    if(document.getElementsByTagName("form").length > 0) {
                        var x = document.getElementsByTagName("form");
                        for (var i = 0; i < x.length; i++) {
                            x[i].addEventListener("submit", reCaptchaSubmit);
                        }
                    }
                </script>';
		}

		// Get the active Brute Force class
		$bruteforce = $bruteForceModel->getInstance();

		// Return the active brute force
		return $bruteforce->head() . $bruteforce->form() . $script;

	}

}
