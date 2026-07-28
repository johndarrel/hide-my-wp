<?php
/**
 * Brute Force Protection Model
 * Called from Brute Force Class
 *
 * @file  The Brute Force Shortcode file
 * @package HMWP/BruteForce/Shortcode
 * @since 8.1
 */

defined( 'ABSPATH' ) || die( 'Cheating uh?' );

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

		if ( HMWP_Classes_Tools::isLoggedInUser() ) {
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
			$script = '<script>
					(function () {
					  function upsertHidden(form, name, value) {
					    if (value == null) return;
					
					    // Avoid creating duplicates on repeated submits
					    var selector = \'input[type="hidden"][name="\' + name + \'"][data-hmwp="1"]\';
					    var input = form.querySelector(selector);
					
					    if (!input) {
					      input = document.createElement("input");
					      input.type = "hidden";
					      input.name = name;
					      input.setAttribute("data-hmwp", "1");
					      form.appendChild(input);
					    }
					
					    input.value = value;
					  }
					
					  function reCaptchaSubmit(e) {
					    var form = e.target;
					
					    // Prefer fields inside the form; fallback if your fields are outside the form
					    var bruteNumEl = form.querySelector(\'[name="brute_num"]\') || document.querySelector(\'[name="brute_num"]\');
					    var bruteCkEl  = form.querySelector(\'[name="brute_ck"]\')  || document.querySelector(\'[name="brute_ck"]\');
					
					    if (bruteNumEl && bruteCkEl) {
					      upsertHidden(form, "brute_num", bruteNumEl.value);
					      upsertHidden(form, "brute_ck", bruteCkEl.value);
					    }
					
					  }
					
					  // Capture phase so we run before most libraries’ submit handlers
					  document.addEventListener("submit", reCaptchaSubmit, true);
					})();
			</script>';

		}

		// Get the active Brute Force class
		$bruteforce = $bruteForceModel->getInstance();

		// Return the active brute force
		return $bruteforce->head() . $bruteforce->form() . $script;

	}

}
