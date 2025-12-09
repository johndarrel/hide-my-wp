<?php
/**
 * Brute Force Protection Model
 * Called from Brute Force Class
 *
 * @file  The Brute Force Math Recaptcha file
 * @package HMWP/BruteForce/Math
 * @since 8.1
 */

defined( 'ABSPATH' ) || die( 'Cheatin\' uh?' );

class HMWP_Models_Bruteforce_Math extends HMWP_Models_Bruteforce_Abstract {


	/**
	 * Verifies that a user answered the math problem correctly while logging in.
	 *
	 * @param  mixed  $user
	 * @param  mixed  $response
	 *
	 * @return mixed $user Returns the user if the math is correct
	 */
	public function authenticate( $user, $response ) {

		$error_message = $this->call();

		if ( $error_message ) {
			$user = new WP_Error( 'authentication_failed', $error_message );
		}

		return $user;
	}

	/**
	 * Call the reCaptcha math
	 */
	public function call() {
		$error_message = false;

		$salt        = HMWP_Classes_Tools::getOption( 'hmwp_disable' ) . get_site_option( 'admin_email' );
		$ans         = (int) HMWP_Classes_Tools::getValue( 'brute_num', 0 );
		$salted_ans  = sha1( $salt . $ans );
		$correct_ans = HMWP_Classes_Tools::getValue( 'brute_ck' );

		if ( $correct_ans === false || $salted_ans != $correct_ans ) {
			$error_message = sprintf( esc_html__( '%sYou failed to correctly answer the math problem.%s Please try again.', 'hide-my-wp' ), '<strong>', '</strong>' );
		}

		return $error_message;
	}

	public function head() {

	}

	/**
	 * Requires a user to solve a simple equation. Added to any WordPress login form.
	 *
	 * @return void outputs html
	 */
	public function form() {

		$salt = HMWP_Classes_Tools::getOption( 'hmwp_disable' ) . get_site_option( 'admin_email' );
		$num1 = wp_rand( 0, 10 );
		$num2 = wp_rand( 1, 10 );
		$sum  = $num1 + $num2;
		$ans  = sha1( $salt . $sum );
		?>
        <div class="humanity">
            <strong><?php echo esc_html__( 'Prove your humanity:', 'hide-my-wp' ) ?> </strong>
			<?php echo esc_attr( $num1 ) ?> &nbsp; + &nbsp; <?php echo esc_attr( $num2 ) ?> &nbsp; = &nbsp;
            <input type="input" name="brute_num" value="" size="2"/>
            <input type="hidden" name="brute_ck" value="<?php echo esc_attr( $ans ); ?>" id="brute_ck"/>
        </div>
        <style>
            div.humanity {
                margin: 5px 0 20px;
                clear: both;
            }

            div.humanity input[name=brute_num] {
                max-width: 60px;
                display: inline !important;
                border: 1px solid gray;
            }
        </style>
		<?php
	}


}
