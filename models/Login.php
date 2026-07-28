<?php
/**
 * Change Login Layout
 *
 * @file  The Login file
 * @package HMWP/Salts
 * @since 8.3
 */
defined( 'ABSPATH' ) || die( 'Cheating uh?' );

class HMWP_Models_Login {

	/**
	 * Output custom CSS on the login page based on saved customization options.
	 *
	 * @return void
	 */
	public function hookLoginPageStyles() {
		$logo         = HMWP_Classes_Tools::getOption( 'hmwp_login_page_logo' );
		$bg_image     = HMWP_Classes_Tools::getOption( 'hmwp_login_page_bg_image' );
		$bg_color     = HMWP_Classes_Tools::getOption( 'hmwp_login_page_bg_color' );
		$form_bg      = HMWP_Classes_Tools::getOption( 'hmwp_login_page_form_bg_color' );
		$btn_color    = HMWP_Classes_Tools::getOption( 'hmwp_login_page_btn_color' );
		$text_color   = HMWP_Classes_Tools::getOption( 'hmwp_login_page_text_color' );
		$link_color   = HMWP_Classes_Tools::getOption( 'hmwp_login_page_link_color' );

		$layout_preset = HMWP_Classes_Tools::getOption( 'hmwp_login_page_layout' );
		$bg_overlay    = HMWP_Classes_Tools::getOption( 'hmwp_login_page_bg_overlay' );
		$bg_blur       = (int) HMWP_Classes_Tools::getOption( 'hmwp_login_page_bg_blur' );

		if ( ! is_string( $layout_preset ) || $layout_preset === '' ) {
			$layout_preset = 'classic_card';
		}

		$css = '';

		$has_form_bg = ( $form_bg && preg_match( '/^[0-9a-fA-F]{6}([0-9a-fA-F]{2})?$/', $form_bg ) );
		$has_bg_image = (bool) $bg_image;
		$split_color = '';
		$form_split_mask = '#ffffff90';

		if ( $has_form_bg ) {
			$split_color = '#' . ( $bg_color ?: 'ffffff70');
		}

		if ( $has_bg_image ) {
			$form_split_mask = '#' . ( $bg_color ?: 'ffffff70');
		}

		// Get the logo from the theme
		if ( ! $logo ){
			if ( function_exists( 'get_theme_mod' ) && function_exists( 'wp_get_attachment_image_src' ) ) {
				$custom_logo_id = get_theme_mod( 'custom_logo' );
				if ( $custom_logo_id ) {
					$image          = wp_get_attachment_image_src( $custom_logo_id, 'full' );
					if ( isset( $image[0] ) ) {
						$logo = esc_url( $image[0] );
					}
				} else {
                    $logo = esc_url( _HMWP_WPLOGIN_URL_ . 'images/wordpress-logo.svg' );
                }
			}
		}

		/**
		 * ------------------------------------------------------------------
		 * Base logo styles
		 * ------------------------------------------------------------------
		 */
		if ( $logo ) {
			$logo_url = esc_url( $logo );
			$css     .= 'body.login #login h1 a,'
			            . 'body.login .wp-login-logo a{'
			            . 'background-image:url("' . $logo_url . '") !important;'
			            . 'background-size:contain !important;'
			            . 'background-repeat:no-repeat !important;'
			            . 'background-position:center !important;'
			            . 'width:100% !important;'
			            . 'max-width:180px !important;'
			            . 'height:90px !important;'
			            . 'margin:0 auto !important;'
			            . '}';
		}

		/**
		 * ------------------------------------------------------------------
		 * Base color styles
		 * ------------------------------------------------------------------
		 */
		if ( $bg_color && preg_match( '/^[0-9a-fA-F]{6}([0-9a-fA-F]{2})?$/', $bg_color ) ) {
			$css .= 'body.login{background-color:#' . esc_attr( $bg_color ) . ' !important;}';
		}


		// Form background is applied after layout presets (see below the switch) so it wins the cascade.

		if ( $btn_color && preg_match( '/^[0-9a-fA-F]{6}([0-9a-fA-F]{2})?$/', $btn_color ) ) {
			$css .= 'body.login input.button-primary,'
			        . 'body.login #wp-submit,'
			        . 'body.login .button-primary,'
			        . '.wp-core-ui .button-primary{'
			        . 'background:#' . esc_attr( $btn_color ) . ' !important;'
			        . 'border-color:#' . esc_attr( $btn_color ) . ' !important;'
			        . 'box-shadow:none !important;'
			        . '}';

			$css .= 'body.login input.button-primary:hover,'
			        . 'body.login input.button-secondary:hover,'
			        . 'body.login input.button-primary:focus,'
			        . 'body.login input.button-secondary:focus,'
			        . 'body.login input.button-cancel:focus,'
			        . 'body.login input.button-cancel:focus,'
			        . 'body.login .button-primary:hover,'
			        . 'body.login .button-primary:focus{'
			        . 'opacity:.92;'
			        . '}';
		}

		if ( $text_color && preg_match( '/^[0-9a-fA-F]{6}([0-9a-fA-F]{2})?$/', $text_color ) ) {
			$css .= 'body.login label,'
			        . 'body.login p,'
			        . 'body.login h1,'
			        . 'body.login h2,'
			        . 'body.login h3,'
			        . 'body.login #login_error,'
			        . 'body.login .message,'
			        . 'body.login .success,'
			        . 'body.login .humanity{'
			        . 'color:#' . esc_attr( $text_color ) . ' !important;'
			        . '}';
			$css .= 'body.login .notice,'
			        . 'body.login .message,'
			        . 'body.login .register,'
			        . 'body.login .notice p,'
			        . 'body.login .message p{'
			        . 'color:initial !important;'
			        . '}';
		}

		if ( $link_color && preg_match( '/^[0-9a-fA-F]{6}([0-9a-fA-F]{2})?$/', $link_color ) ) {
			$css .= 'body.login a,'
			        . 'body.login div a,'
			        . 'body.login p a,'
			        . 'body.login h1 a{'
			        . 'color:#' . esc_attr( $link_color ) . ' !important;'
			        . '}';
		}

		/**
		 * ------------------------------------------------------------------
		 * Background image / overlay / blur
		 * ------------------------------------------------------------------
		 */
		/**
		 * ------------------------------------------------------------------
		 * Background image / overlay / blur
		 * ------------------------------------------------------------------
		 */
		$overlay_rgba = 'transparent';
		switch ( $bg_overlay ) {
			case 'light':
				$overlay_rgba = 'rgba(255,255,255,0.18)';
				break;
			case 'medium':
				$overlay_rgba = 'rgba(0,0,0,0.22)';
				break;
			case 'dark':
				$overlay_rgba = 'rgba(0,0,0,0.42)';
				break;
		}

		if ( $bg_image ) {
			$bg_url = esc_url( $bg_image );

			$css .= '
				html{
					min-height:100%;
					background:transparent !important;
				}
				body.login{
					position:relative;
					min-height:100vh;
					background:transparent !important;
					overflow-x:hidden;
				}
				body.login:before{
					content:"";
					position:fixed;
					inset:0;
					pointer-events:none;
					background-image:
						linear-gradient(' . $overlay_rgba . ',' . $overlay_rgba . '),
						url("' . $bg_url . '");
					background-size:cover, cover;
					background-position:center center, center center;
					background-repeat:no-repeat, no-repeat;
					z-index:0;
				}
				';

			if ( $bg_blur > 0 ) {
				$css .= '
					body.login:after{
						content:"";
						position:fixed;
						inset:0;
						pointer-events:none;
						backdrop-filter:blur(' . (int) $bg_blur . 'px);
						-webkit-backdrop-filter:blur(' . (int) $bg_blur . 'px);
						z-index:0;
					}
				';
            } else {
                $css .= '
                    body.login:after{
                        display:none;
                    }
                ';
            }

			$css .= '
				body.login #login{
					position:relative;
					z-index:1;
                }
            ';
		}

		/**
		 * ------------------------------------------------------------------
		 * Shared base layout styles
		 * ------------------------------------------------------------------
		 */
		$css .= '
			body.login{
				min-height:100vh;
			}
			body.login #login{
				width:min(100%, 460px);
				box-sizing:border-box;
			}
			body.login #loginform,
			body.login #magicloginform,
			body.login #registerform,
			body.login #lostpasswordform,
			body.login #unique_login_form form{
				width:100%;
				box-sizing:border-box;
			}
			body.login .language-switcher{
				display:none;
				padding:10px 0 0 0 !important;
				background:transparent !important;
				border:none !important;
				box-shadow:none !important;
			}
			body.login input[type="text"],
			body.login input[type="password"],
			body.login input[type="email"],
			body.login input[type="url"],
			body.login input[type="tel"],
			body.login input[type="number"],
			body.login .input{
				width:100% !important;
				max-width:100% !important;
				min-height:46px;
				box-sizing:border-box !important;
				box-shadow:none !important;
			}
			body.login .button-primary,
			body.login .button-secondary,
			body.login .button-cancel,
			body.login #wp-submit{
				box-sizing:border-box !important;
				min-height:46px;
				padding: 0 30px !important;
				box-shadow:none !important;
			}
			body.login .button.button-small{
				min-height: 38px;
			}
			body.login .button.button-large{
				min-height: 46px;
			}
			body.login .message,
			body.login .notice,
			body.login .success,
			body.login #login_error{
				box-sizing:border-box;
			}
			body.login #login h1,
			body.login .wp-login-logo{
				margin-bottom:24px !important;
			}
		';


		/**
		 * ------------------------------------------------------------------
		 * Layout presets
		 * ------------------------------------------------------------------
		 */
		switch ( $layout_preset ) {

			case 'classic_card':
				$css .= '
					body.login{
						display:flex;
						align-items:center;
						justify-content:center;
						padding:0 20px;
					}
					body.login #login{
						margin:0 auto !important;
						padding:30px;
						background:' . ( $has_bg_image ? 'rgba(255,255,255,0.94)' : 'rgba(255,255,255,0.96)' ) . ';
						border-radius:18px;
						box-shadow:0 12px 40px rgba(0,0,0,0.08);
					}
					body.login #loginform,
					body.login #magicloginform,
					body.login #registerform,
					body.login #lostpasswordform,
					body.login #unique_login_form form{
						background:transparent !important;
						border:none !important;
						box-shadow:none !important;
						padding:0 !important;
					}
					body.login #login h1,
					body.login .wp-login-logo{
						text-align:center !important;
					}
					';
				break;

		}

		/**
		 * ------------------------------------------------------------------
		 * Form background color – applied after layout presets so it wins
		 * Targets the #login wrapper (the visible card / panel in all layouts)
		 * ------------------------------------------------------------------
		 */
		if ( $has_form_bg ) {
			$css .= 'body.login #login{background:#' . esc_attr( $form_bg ) . ' !important;}';
		}

		/**
		 * ------------------------------------------------------------------
		 * Responsive fallbacks
		 * ------------------------------------------------------------------
		 */
		$css .= '
			@media (max-width: 1200px) {
				body.login{
					overflow-y:auto;
				}
				body.login #login{
					width:min(100%, 500px);
				}
			}
			
			/* Keep the same preset look on smaller screens. Only scale and center it. */
			@media (max-width: 980px) {
			
				body.login{
					min-height:100vh;
					padding: 0 18px !important;
					display:flex !important;
					align-items:center !important;
					justify-content:center !important;
				}
			
				body.login #login{
					width:min(100%, 560px) !important;
					max-width:560px !important;
					margin:0 auto !important;
					padding:clamp(22px, 3vw, 30px) !important;
					position:relative !important;
					z-index:1 !important;
				}
			
				body.login #login:after{
					display:none !important;
				}
			
				body.login #login h1,
				body.login .wp-login-logo{
					text-align:center !important;
					margin-bottom:18px !important;
				}
			
				/* Do not destroy the selected preset card style */
				body.login #loginform,
				body.login #magicloginform,
				body.login #registerform,
				body.login #lostpasswordform,
				body.login #unique_login_form form{
					padding:0 !important;
				}
			
				/* Remove hard split dividers on smaller screens, but keep the full background image */
				body.login:before{
					' . ( $bg_image ? 'content:"";' : 'content:none !important;' ) . '
					position:fixed !important;
					inset:0 !important;
					left:0 !important;
					right:0 !important;
					top:0 !important;
					bottom:0 !important;
					background-image:
						linear-gradient(' . $overlay_rgba . ',' . $overlay_rgba . '),
						' . ( $bg_image ? 'url("' . esc_url( $bg_image ) . '")' : 'none' ) . ';
					background-size:cover, cover !important;
					background-position:center center, center center !important;
					background-repeat:no-repeat, no-repeat !important;
					z-index:0 !important;
					pointer-events:none !important;
				}
			
				' . ( $bg_blur > 0 ? '
				body.login:after{
					content:"";
					position:fixed !important;
					inset:0 !important;
					backdrop-filter:blur(' . (int) $bg_blur . 'px) !important;
					-webkit-backdrop-filter:blur(' . (int) $bg_blur . 'px) !important;
					z-index:0 !important;
					pointer-events:none !important;
				}
				' : '
				body.login:after{
					display:none !important;
				}
				' ) . '
			
				body.login #nav,
				body.login #backtoblog{
					text-align:left !important;
				}
			}
			';

		/**
		 * ------------------------------------------------------------------
		 * On mobile, reset the split-layout gradient to a solid background so
		 * the two-tone divider doesn't bleed through behind the centred form.
		 * ------------------------------------------------------------------
		 */
		$split_layouts = array( 'soft_split', 'image_right', 'bold_split', 'tinted_split', 'image_left' );
		if ( in_array( $layout_preset, $split_layouts, true ) && ! $bg_image ) {
			$solid_bg = ( $bg_color && preg_match( '/^[0-9a-fA-F]{6}([0-9a-fA-F]{2})?$/', $bg_color ) )
				? '#' . esc_attr( $bg_color )
				: '#f0f0f1';
			$css .= '@media (max-width:980px){body.login{background:' . $solid_bg . ' !important;}}';
		}

		if ( $css ) {
			echo '<style>' . apply_filters( 'hmwp_login_css', $css ) . '</style>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
	}

	/**
	 * Filter the login page logo link URL.
	 *
	 * @param string $url Current logo URL.
	 * @return string
	 */
	public function hookLoginLogoUrl( $url ) {
		$custom_url = HMWP_Classes_Tools::getOption( 'hmwp_login_page_logo_url' );
		return $custom_url ? esc_url_raw( $custom_url ) : $url;
	}

	/**
	 * Inject a loading spinner next to submit buttons on all login page forms.
	 * Fires on the login_footer action so it covers the standard login form,
	 * the 2FA form, and the magic-link form.
	 *
	 * @return void
	 */
	public function hookLoginSpinner() {
		?>
		<style>
			.hmwp-spinner {
                display: inline-block;
                width: 20px;
                height: 46px;
                background: url('<?php echo esc_url( _HMWP_WPLOGIN_URL_ . 'images/loading.gif' ) ?>') no-repeat center center;
                background-size: contain;
                opacity: 0;
                vertical-align: middle;
                margin: 0 8px;
                transition: opacity 0.15s ease;
                flex-shrink: 0;
                float: right;
			}

			.hmwp-spinner.is-active {
				opacity: 0.7;
			}
		</style>
		<script>
		(function () {
			function attachSpinner(form) {
				if (!form || form.dataset.hmwpSpinner) return;
				form.dataset.hmwpSpinner = '1';

                let submitBtn = form.querySelector('#wp-submit, input[type="submit"].button-primary:not([name="hmwp-email-code-resend"]), button[type="submit"].button-primary');
				if (!submitBtn) return;

                let spinner = document.createElement('span');
				spinner.className = 'hmwp-spinner';
				submitBtn.parentNode.insertBefore(spinner, submitBtn.nextSibling);

				form.addEventListener('submit', function (e) {
                    let triggeredBy = (e && e.submitter) ? e.submitter : document.activeElement;
					if (triggeredBy && triggeredBy.name === 'hmwp-email-code-resend') {
						return;
					}
					spinner.classList.add('is-active');
					setTimeout(function(){ submitBtn.disabled = true; }, 10);
				});
			}

			function init() {

                // move the language switcher to the login form
                let s=document.querySelector(".language-switcher");
                let l=document.getElementById("login");
                if(s&&l) {
                    l.appendChild(s);
                    s.style.display = 'block';
                }

				document.querySelectorAll('#loginform, #registerform, #magicloginform, #lostpasswordform').forEach(attachSpinner);

				// Watch for the magic-link form that is inserted dynamically
				if (typeof MutationObserver !== 'undefined') {
                    let observer = new MutationObserver(function (mutations) {
						mutations.forEach(function (m) {
							m.addedNodes.forEach(function (node) {
								if (node.nodeType !== 1) return;
								if (node.id === 'magicloginform') {
									attachSpinner(node);
								}
								node.querySelectorAll && node.querySelectorAll('#magicloginform').forEach(attachSpinner);
							});
						});
					});
					observer.observe(document.body, {childList: true, subtree: true});
				}
			}

			if (document.readyState === 'loading') {
				document.addEventListener('DOMContentLoaded', init);
			} else {
				init();
			}
		})();
		</script>
		<?php
	}
}