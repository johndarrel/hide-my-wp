<?php
/**
 * Ghost Doctor
 * Finds why the website paths stopped working and repairs what it can
 *
 * @file The Ghost Doctor model file
 * @package HMWP/Ghostdoctor
 * @since 9.2.0
 */

defined( 'ABSPATH' ) || die( 'Cheating uh?' );

class HMWP_Models_Ghostdoctor {

	/**
	 * Highest number of repair steps in one run.
	 */
	const MAX_STEPS = 12;

	/**
	 * The repairs Ghost Doctor is allowed to make.
	 *
	 * This list is the whole permission surface. A repair that is not in here
	 * cannot be applied, which keeps the settings that could lock a user out of
	 * their own dashboard, such as the admin and login paths, out of reach.
	 *
	 * Order matters. The list runs from the cheapest and safest repair to the
	 * ones that trade a little protection for a working site, so the first thing
	 * that fixes the problem is also the least invasive.
	 *
	 * roles    which failing checks this repair can address
	 * requires an extra condition that must hold before it is offered
	 *
	 * @return array
	 */
	public function getFixes() {

		return array(

			'purge_cache' => array(
				'label'  => esc_html__( 'Clear the cache', 'hide-my-wp' ),
				'type'   => 'action',
				'roles'  => array( 'home', 'ajax', 'rest', 'asset', 'admin', 'render', 'logo' ),
				'why'    => esc_html__( 'Cached pages and files can still point at the old paths.', 'hide-my-wp' ),
				'risk'   => 'low',
			),

			'flush_rules' => array(
				'label'  => esc_html__( 'Write the server rules again', 'hide-my-wp' ),
				'type'   => 'action',
				'roles'  => array( 'home', 'ajax', 'rest', 'admin', 'logo' ),
				'why'    => esc_html__( 'The rules that map the new paths may be missing or out of date.', 'hide-my-wp' ),
				'risk'   => 'low',
			),

			'change_in_cache' => array(
				'label'    => esc_html__( 'Change Paths in Cached Files', 'hide-my-wp' ),
				'type'     => 'option',
				'option'   => 'hmwp_change_in_cache',
				'value'    => 1,
				'roles'    => array( 'asset', 'ajax' ),
				'requires' => 'cache_plugin',
				'why'      => esc_html__( 'Your cache plugin saved files that still contain the old paths.', 'hide-my-wp' ),
				'risk'     => 'low',
			),

			'prevent_slow_loading' => array(
				'label'  => esc_html__( 'Prevent Broken Website Layout', 'hide-my-wp' ),
				'type'   => 'option',
				'option' => 'prevent_slow_loading',
				'value'  => 1,
				'roles'  => array( 'asset', 'render' ),
				'why'    => esc_html__( 'Your theme files are served through WordPress so the layout is not broken. The site stays correct but loads more slowly.', 'hide-my-wp' ),
				'risk'   => 'medium',
			),

			'logged_users_off' => array(
				'label'    => esc_html__( 'Change Paths for Logged Users', 'hide-my-wp' ),
				'type'     => 'option',
				'option'   => 'hmwp_hide_loggedusers',
				'value'    => 0,
				'roles'    => array( 'asset' ),
				'requires' => 'cache_plugin',
				'why'      => esc_html__( 'Your cache plugin needs the real file paths while it works. Visitors still get the hidden paths.', 'hide-my-wp' ),
				'risk'     => 'low',
			),

			'laterload' => array(
				'label'    => esc_html__( 'Load the plugin later', 'hide-my-wp' ),
				'type'     => 'option',
				'option'   => 'hmwp_laterload',
				'value'    => 1,
				'roles'    => array( 'asset', 'render' ),
				'requires' => 'cache_plugin',
				'why'      => esc_html__( 'Your cache plugin needs to handle the files before WP Ghost changes the paths.', 'hide-my-wp' ),
				'risk'     => 'medium',
			),

			'unhide_ajax_admin' => array(
				'label'  => esc_html__( 'Hide wp-admin from Ajax URL', 'hide-my-wp' ),
				'type'   => 'option',
				'option' => 'hmwp_hideajax_admin',
				'value'  => 0,
				'roles'  => array( 'ajax' ),
				'why'    => esc_html__( 'Page builders and editors send their save requests to the ajax address. Your server is not mapping the hidden one.', 'hide-my-wp' ),
				'risk'   => 'medium',
			),

			'revert_ajax_path' => array(
				'label'   => esc_html__( 'Custom admin-ajax Path', 'hide-my-wp' ),
				'type'    => 'option',
				'option'  => 'hmwp_admin-ajax_url',
				'default' => true,
				'roles'   => array( 'ajax' ),
				'why'     => esc_html__( 'The ajax address goes back to the standard one because your server will not serve the custom one.', 'hide-my-wp' ),
				'risk'    => 'high',
			),

			'revert_rest_path' => array(
				'label'   => esc_html__( 'Custom REST API Path', 'hide-my-wp' ),
				'type'    => 'option',
				'option'  => 'hmwp_wp-json',
				'default' => true,
				'roles'   => array( 'rest' ),
				'why'     => esc_html__( 'Some hosts only serve the REST API on its standard address, so the custom one returns an error.', 'hide-my-wp' ),
				'risk'    => 'high',
			),

			// Last resort, and only ever reached because every cheaper repair above
			// changed nothing. A server that will not serve the rewrites cannot be
			// talked into it from PHP, so the remaining option is a configuration
			// that does not need any: the Minimal preset keeps the protections that
			// work without server rules and drops the ones that do not.
			'minimal_preset' => array(
				'label'  => esc_html__( 'Switch to protection that needs no server rules', 'hide-my-wp' ),
				'type'   => 'preset',
				'preset' => 1,
				'roles'  => array( 'home', 'ajax', 'rest', 'admin', 'asset', 'logo' ),
				'why'    => esc_html__( 'Your server is not serving the renamed paths and will not without a change you have to make by hand. This keeps the protections that work without server rules, so your website stops being broken.', 'hide-my-wp' ),
				'risk'   => 'high',
			),

		);
	}

	/**
	 * Turn the stored mode value into the name the user actually sees.
	 *
	 * The stored values are historical and do not match the interface. "lite" is
	 * shown as Safe Mode on premium and Lite Mode on the free version, and "ninja"
	 * is shown as Ghost Mode. Nothing in the product is called Ninja, so passing
	 * the raw value anywhere it might be read by a person produces a mode name
	 * that does not exist.
	 *
	 * @param string $mode The stored hmwp_mode value.
	 *
	 * @return string
	 */
	public function getModeLabel( $mode ) {

		switch ( $mode ) {
			case 'lite':
				// The same stored value carries a different name in each edition
				return HMWP_CLASS_CTA ? esc_html__( 'Lite Mode', 'hide-my-wp' ) : esc_html__( 'Safe Mode', 'hide-my-wp' );
			case 'ninja':
				return esc_html__( 'Ghost Mode', 'hide-my-wp' );
			case 'default':
				return esc_html__( 'Deactivated', 'hide-my-wp' );
		}

		return esc_html__( 'Unknown', 'hide-my-wp' );
	}

	/**
	 * Read the parts of the setup that decide which repairs make sense.
	 *
	 * @return array
	 * @throws Exception
	 */
	public function readSiteState() {

		/** @var HMWP_Models_Rules $rules */
		$rules = HMWP_Classes_ObjController::getClass( 'HMWP_Models_Rules' );

		$config   = $rules->getConfFile();
		$writable = $rules->isConfigWritable();

		// Are the WP Ghost rules actually inside the server config file
		$has_rules = false;
		if ( $config ) {
			$filesystem = HMWP_Classes_Tools::initFilesystem();
			if ( $filesystem->exists( $config ) ) {
				$has_rules = $rules->find( 'HMWP_RULES', $config );
			}
		}

		if ( HMWP_Classes_Tools::isNginx() ) {
			$server = 'nginx';
		} elseif ( HMWP_Classes_Tools::isIIS() ) {
			$server = 'iis';
		} elseif ( HMWP_Classes_Tools::isCloudPanel() ) {
			$server = 'cloudpanel';
		} elseif ( HMWP_Classes_Tools::isApache() ) {
			$server = 'apache';
		} else {
			$server = 'unknown';
		}

		return array(
			'server'          => $server,
			'config_file'     => $config,
			'config_writable' => (bool) $writable,
			'rules_in_config' => (bool) $has_rules,
			'cache_plugin'    => (bool) HMWP_Classes_Tools::isCachePlugin(),
			// The label, not the stored value. Anything that reads this and writes
			// text for a person must use the name that appears in the interface.
			'mode'            => $this->getModeLabel( HMWP_Classes_Tools::getOption( 'hmwp_mode' ) ),
			'file_mappings'   => count( (array) HMWP_Classes_Tools::getOption( 'file_mappings' ) ),
		);
	}

	/**
	 * Find the problems the plugin cannot repair on its own.
	 *
	 * These need a change on the server or in the hosting panel, so the honest
	 * answer is to name the cause and hand over the matching guide rather than
	 * to keep toggling settings that cannot help.
	 *
	 * @param array $state The result of readSiteState().
	 * @param array $probe The result of the frontend probe.
	 *
	 * @return array
	 */
	public function getBlockers( $state, $probe ) {

		$blockers = array();
		$website  = HMWP_Classes_Tools::getOption( 'hmwp_plugin_website' );

		// Nothing failed, so nothing is being blocked
		if ( ! empty( $probe['success'] ) ) {
			return $blockers;
		}

		if ( ! $state['config_writable'] ) {
			$blockers[] = array(
				'id'    => 'config_not_writable',
				'title' => esc_html__( 'WP Ghost cannot write to your server config file', 'hide-my-wp' ),
				'body'  => esc_html__( 'The rules that map the new paths are stored in that file. Until it can be written, the paths cannot work.', 'hide-my-wp' ),
				'link'  => $website . '/kb/theme-not-loading-correctly-website-loads-slower/',
			);
		}

		if ( 'nginx' === $state['server'] || 'cloudpanel' === $state['server'] ) {
			$blockers[] = array(
				'id'    => 'nginx_manual_rules',
				'title' => esc_html__( 'Nginx needs the rules added by hand', 'hide-my-wp' ),
				'body'  => esc_html__( 'Nginx does not read .htaccess. Copy the rules into your server config, then reload the Nginx service.', 'hide-my-wp' ),
				'link'  => $website . '/kb/setup-wp-ghost-on-nginx-server/',
			);
		} elseif ( 'iis' === $state['server'] ) {
			$blockers[] = array(
				'id'    => 'iis_setup',
				'title' => esc_html__( 'Windows IIS needs its own setup', 'hide-my-wp' ),
				'body'  => esc_html__( 'The rules go into web.config and the URL Rewrite module has to be installed.', 'hide-my-wp' ),
				'link'  => $website . '/kb/setup-wp-ghost-on-windows-iis-server/',
			);
		} elseif ( ! $state['rules_in_config'] && $state['config_writable'] ) {
			// The file can be written and the rules are still not being applied,
			// so Apache is most likely ignoring .htaccess
			$blockers[] = array(
				'id'    => 'allowoverride',
				'title' => esc_html__( 'Your server is ignoring the .htaccess file', 'hide-my-wp' ),
				'body'  => esc_html__( 'Ask your host to switch on AllowOverride All for your site. Until then the rules have no effect.', 'hide-my-wp' ),
				'link'  => $website . '/kb/how-to-set-allowoverride-all/',
			);
		}

		return $blockers;
	}

	/**
	 * Build the ordered list of repairs worth trying for this failure.
	 *
	 * @param array $probe The result of the frontend probe.
	 * @param array $state The result of readSiteState().
	 *
	 * @return array
	 */
	public function getPlan( $probe, $state ) {

		$plan = array();

		if ( ! empty( $probe['success'] ) ) {
			return $plan;
		}

		// Which kinds of check failed
		$failing = array();
		foreach ( $probe['errors'] as $error ) {
			$failing[ $error['role'] ] = true;
		}

		foreach ( $this->getFixes() as $id => $fix ) {

			// Does this repair address something that actually failed
			$matches = false;
			foreach ( $fix['roles'] as $role ) {
				if ( isset( $failing[ $role ] ) ) {
					$matches = true;
					break;
				}
			}

			if ( ! $matches ) {
				continue;
			}

			if ( isset( $fix['requires'] ) && 'cache_plugin' === $fix['requires'] && empty( $state['cache_plugin'] ) ) {
				continue;
			}

			// Skip a setting that is already where this repair would put it
			if ( 'option' === $fix['type'] && ! $this->wouldChange( $id ) ) {
				continue;
			}

			$fix['id'] = $id;
			$plan[]    = $fix;

			if ( count( $plan ) >= self::MAX_STEPS ) {
				break;
			}
		}

		return $plan;
	}

	/**
	 * The value a repair would write.
	 *
	 * @param string $id A key of getFixes().
	 *
	 * @return mixed Null when the repair is an action rather than a setting.
	 */
	public function getTargetValue( $id ) {

		$fixes = $this->getFixes();

		if ( ! isset( $fixes[ $id ] ) || 'option' !== $fixes[ $id ]['type'] ) {
			return null;
		}

		if ( ! empty( $fixes[ $id ]['default'] ) ) {
			return HMWP_Classes_Tools::getDefault( $fixes[ $id ]['option'] );
		}

		return $fixes[ $id ]['value'];
	}

	/**
	 * Would this repair actually change anything.
	 *
	 * @param string $id A key of getFixes().
	 *
	 * @return bool
	 */
	public function wouldChange( $id ) {

		$fixes = $this->getFixes();

		if ( ! isset( $fixes[ $id ] ) || 'option' !== $fixes[ $id ]['type'] ) {
			return true;
		}

		$current = HMWP_Classes_Tools::getOption( $fixes[ $id ]['option'] );

		return ( (string) $current !== (string) $this->getTargetValue( $id ) );
	}

	/**
	 * Carry out one repair.
	 *
	 * @param string $id A key of getFixes().
	 *
	 * @return array|false The previous state, so the repair can be undone.
	 * @throws Exception
	 */
	public function applyFix( $id ) {

		$fixes = $this->getFixes();

		// The catalogue is the allowlist. Anything else is refused.
		if ( ! isset( $fixes[ $id ] ) ) {
			return false;
		}

		$fix      = $fixes[ $id ];
		$previous = array( 'id' => $id, 'type' => $fix['type'] );

		if ( 'option' === $fix['type'] ) {
			$previous['option'] = $fix['option'];
			$previous['value']  = HMWP_Classes_Tools::getOption( $fix['option'] );

			HMWP_Classes_Tools::saveOptions( $fix['option'], $this->getTargetValue( $id ) );
			HMWP_Classes_ObjController::getClass( 'HMWP_Models_Settings' )->saveRules();
		} elseif ( 'preset' === $fix['type'] ) {

			// The whole option set is kept, not one key, because a preset rewrites
			// many of them and Undo has to put every one back.
			$previous['options'] = HMWP_Classes_Tools::getOptions();

			/** @var HMWP_Models_Presets $presets */
			$presets = HMWP_Classes_ObjController::getClass( 'HMWP_Models_Presets' );
			$method  = 'getPreset' . (int) $fix['preset'];

			if ( method_exists( $presets, $method ) ) {
				foreach ( call_user_func( array( $presets, $method ) ) as $key => $value ) {
					HMWP_Classes_Tools::saveOptions( $key, $value );
				}

				HMWP_Classes_ObjController::getClass( 'HMWP_Models_Settings' )->saveRules();
				HMWP_Classes_ObjController::getClass( 'HMWP_Models_Rewrite' )->flushChanges();
			}

		} elseif ( 'flush_rules' === $id ) {
			HMWP_Classes_ObjController::getClass( 'HMWP_Models_Settings' )->saveRules();
			HMWP_Classes_ObjController::getClass( 'HMWP_Models_Rewrite' )->flushChanges();
		}

		// Every repair is followed by a cache clear, otherwise the check that
		// comes next reads an old copy of the page and draws the wrong conclusion
		$this->purgeCaches();

		return $previous;
	}

	/**
	 * Undo one repair.
	 *
	 * @param array $previous The value returned by applyFix().
	 *
	 * @return void
	 * @throws Exception
	 */
	public function revertFix( $previous ) {

		if ( empty( $previous ) || ! isset( $previous['type'] ) ) {
			return;
		}

		if ( 'preset' === $previous['type'] && ! empty( $previous['options'] ) ) {

			foreach ( $previous['options'] as $key => $value ) {
				HMWP_Classes_Tools::saveOptions( $key, $value );
			}

			HMWP_Classes_ObjController::getClass( 'HMWP_Models_Settings' )->saveRules();
			HMWP_Classes_ObjController::getClass( 'HMWP_Models_Rewrite' )->flushChanges();

			return;
		}

		if ( 'option' === $previous['type'] && isset( $previous['option'] ) ) {
			HMWP_Classes_Tools::saveOptions( $previous['option'], $previous['value'] );
			HMWP_Classes_ObjController::getClass( 'HMWP_Models_Settings' )->saveRules();
		}

		$this->purgeCaches();
	}

	/**
	 * Clear every cache we know about.
	 *
	 * @return void
	 */
	public function purgeCaches() {
		HMWP_Classes_Tools::emptyCache();
	}

	/**
	 * Store the settings as they are now.
	 *
	 * Written to a key of its own. The plugin already keeps hmwp_options_safe as
	 * the rollback point the user relies on, and overwriting that would take away
	 * the safety net this feature is supposed to protect.
	 *
	 * @return void
	 */
	public function snapshot() {

		update_option( HMWP_GHOSTDOCTOR_SNAPSHOT, array(
			'time'    => time(),
			'options' => HMWP_Classes_Tools::getOptions(),
		), false );
	}

	/**
	 * Is there a snapshot waiting to be confirmed or undone.
	 *
	 * @return bool
	 */
	public function hasSnapshot() {

		$snapshot = get_option( HMWP_GHOSTDOCTOR_SNAPSHOT );

		return ( is_array( $snapshot ) && ! empty( $snapshot['options'] ) );
	}

	/**
	 * Put every setting back the way it was before the repair started.
	 *
	 * @return bool
	 * @throws Exception
	 */
	public function restore() {

		$snapshot = get_option( HMWP_GHOSTDOCTOR_SNAPSHOT );

		if ( ! is_array( $snapshot ) || empty( $snapshot['options'] ) ) {
			return false;
		}

		foreach ( $snapshot['options'] as $name => $value ) {
			HMWP_Classes_Tools::saveOptions( $name, $value );
		}

		HMWP_Classes_ObjController::getClass( 'HMWP_Models_Settings' )->saveRules();
		$this->purgeCaches();

		$this->clearSnapshot();

		return true;
	}

	/**
	 * Forget the snapshot, which is what confirming the repair does.
	 *
	 * @return void
	 */
	public function clearSnapshot() {
		delete_option( HMWP_GHOSTDOCTOR_SNAPSHOT );
	}

	/**
	 * Look at the website and work out what is wrong.
	 *
	 * @return array
	 * @throws Exception
	 */
	public function diagnose() {

		/** @var HMWP_Controllers_SecurityCheck $securitycheck */
		$securitycheck = HMWP_Classes_ObjController::getClass( 'HMWP_Controllers_SecurityCheck' );

		$probe = $securitycheck->runFrontendProbe();
		$state = $this->readSiteState();

		return array(
			'time'     => time(),
			'probe'    => $probe,
			'state'    => $state,
			'blockers' => $this->getBlockers( $state, $probe ),
			'plan'     => $this->getPlan( $probe, $state ),
		);
	}

	/**
	 * Try one repair and keep it only if it helped.
	 *
	 * A repair that does not reduce the number of failing checks is undone, so a
	 * run never leaves a setting changed for nothing.
	 *
	 * @param string $id     A key of getFixes().
	 * @param int    $before How many checks were failing before this step.
	 *
	 * @return array What happened, and the state of the site afterwards.
	 * @throws Exception
	 */
	public function repairStep( $id, $before ) {

		$fixes = $this->getFixes();

		if ( ! isset( $fixes[ $id ] ) ) {
			return array( 'id' => $id, 'outcome' => 'refused' );
		}

		$previous = $this->applyFix( $id );

		/** @var HMWP_Controllers_SecurityCheck $securitycheck */
		$securitycheck = HMWP_Classes_ObjController::getClass( 'HMWP_Controllers_SecurityCheck' );
		$probe         = $securitycheck->runFrontendProbe();
		$after         = count( $probe['errors'] );

		if ( ! empty( $probe['success'] ) ) {
			$outcome = 'solved';
		} elseif ( $after < $before ) {
			$outcome = 'improved';
		} else {
			// It did not help, so put it back rather than leave it changed
			$this->revertFix( $previous );
			$probe   = $securitycheck->runFrontendProbe();
			$after   = count( $probe['errors'] );
			$outcome = 'no_change';
		}

		return array(
			'id'      => $id,
			'label'   => $fixes[ $id ]['label'],
			'why'     => $fixes[ $id ]['why'],
			'risk'    => $fixes[ $id ]['risk'],
			'before'  => $before,
			'after'   => $after,
			'outcome' => $outcome,
			'probe'   => $probe,
		);
	}

	/**
	 * Store the result of a run so the screen can show it after a reload.
	 *
	 * @param array $report The report.
	 *
	 * @return void
	 */
	public function saveReport( $report ) {
		update_option( HMWP_GHOSTDOCTOR_REPORT, $report, false );
	}

	/**
	 * Read the stored result.
	 *
	 * @return array
	 */
	public function getReport() {

		$report = get_option( HMWP_GHOSTDOCTOR_REPORT );

		return is_array( $report ) ? $report : array();
	}

}
