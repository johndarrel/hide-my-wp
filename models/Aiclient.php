<?php
/**
 * Talks to the WP Ghost account API for the Ghost Doctor AI features
 *
 * @file The AI client model file
 * @package HMWP/Aiclient
 * @since 9.2.0
 */

defined( 'ABSPATH' ) || die( 'Cheating uh?' );

class HMWP_Models_Aiclient {

	/**
	 * How long the allowance is remembered before asking again.
	 *
	 * The Security Check page must never wait on a network call, so the answer is
	 * cached and the page renders from whatever it last knew.
	 */
	const QUOTA_CACHE = 900; // 15 minutes

	/**
	 * A failed lookup is remembered for a shorter time, so a brief outage does
	 * not hide the feature for a quarter of an hour.
	 */
	const FAIL_CACHE = 300; // 5 minutes

	/**
	 * Where the account API lives.
	 *
	 * Filterable so a staging site can point somewhere else without a code change.
	 *
	 * @param string $path The endpoint path, for example ai/quota.
	 *
	 * @return string
	 */
	public function getEndpoint( $path ) {
		return apply_filters( 'hmwp_ai_api', _HMWP_API_SITE_ ) . '/api/' . ltrim( $path, '/' );
	}

	/**
	 * Make one call and turn whatever comes back into a predictable shape.
	 *
	 * The licence headers are added by add_remote_options, so nothing here has to
	 * know about tokens. The helper only returns the body, so failures are read
	 * from the error key the account API sets on every unhappy path.
	 *
	 * @param string $path   The endpoint path.
	 * @param array  $params The request body.
	 * @param string $method GET or POST.
	 * @param int    $timeout Seconds to wait.
	 *
	 * @return array ok, plus either data or error and message.
	 */
	public function call( $path, $params = array(), $method = 'GET', $timeout = 10 ) {

		$url     = $this->getEndpoint( $path );
		$options = array( 'timeout' => $timeout );

		if ( 'POST' === $method ) {
			$response = HMWP_Classes_Tools::hmwp_remote_post( $url, $params, $options );
		} else {
			$response = HMWP_Classes_Tools::hmwp_remote_get( $url, $params, $options );
		}

		if ( ! $response ) {
			return array(
				'ok'      => false,
				'error'   => 'connection_failed',
				'message' => esc_html__( 'Your website could not reach the WP Ghost servers.', 'hide-my-wp' ),
			);
		}

		$body = json_decode( $response, true );

		if ( ! is_array( $body ) ) {
			return array(
				'ok'      => false,
				'error'   => 'bad_response',
				'message' => esc_html__( 'The WP Ghost servers returned an answer that could not be read.', 'hide-my-wp' ),
			);
		}

		if ( isset( $body['error'] ) ) {
			return array(
				'ok'      => false,
				'error'   => $body['error'],
				'message' => isset( $body['message'] ) ? $body['message'] : '',
				'renew'   => isset( $body['renew'] ) ? $body['renew'] : '',
				'data'    => $body,
			);
		}

		return array( 'ok' => true, 'data' => $body );
	}

	/**
	 * How many AI checks are left this month.
	 *
	 * Cached, because this runs while the Security Check page is being drawn and
	 * that page must not wait on the network.
	 *
	 * @param bool $fresh Skip the cache.
	 *
	 * @return array
	 */
	public function getQuota( $fresh = false ) {

		$key = 'hmwp_ai_quota';

		if ( ! $fresh ) {
			$cached = get_transient( $key );
			if ( $cached !== false ) {
				return $cached;
			}
		}

		// Short timeout on purpose. A slow answer is worth less than a fast page.
		$result = $this->call( 'ai/quota', array(), 'GET', 5 );

		set_transient( $key, $result, ( $result['ok'] ? self::QUOTA_CACHE : self::FAIL_CACHE ) );

		return $result;
	}

	/**
	 * Is the AI available to this site right now.
	 *
	 * @param array $quota The result of getQuota().
	 *
	 * @return bool
	 */
	public function isReady( $quota ) {

		if ( empty( $quota['ok'] ) ) {
			return false;
		}

		if ( empty( $quota['data']['ai_ready'] ) ) {
			return false;
		}

		return ( (int) $quota['data']['used'] < (int) $quota['data']['allowed'] );
	}

	/**
	 * How many checks are left.
	 *
	 * @param array $quota The result of getQuota().
	 *
	 * @return int
	 */
	public function getRemaining( $quota ) {

		if ( empty( $quota['ok'] ) ) {
			return 0;
		}

		return max( 0, (int) $quota['data']['allowed'] - (int) $quota['data']['used'] );
	}

	/**
	 * Ask the account API to explain and rank a set of findings.
	 *
	 * @param array  $findings Each needs an id.
	 * @param array  $state    The site configuration, so exposure can be judged.
	 * @param string $symptoms What the owner said is wrong, may be empty.
	 *
	 * @return array
	 */
	public function explain( $findings, $state = array(), $symptoms = '' ) {

		$result = $this->call( 'ai/explain', array(
			'findings' => $findings,
			'state'    => $state,
			'symptoms' => $symptoms,
		), 'POST', 120 );

		// The allowance just moved, so stop showing a stale number
		delete_transient( 'hmwp_ai_quota' );

		return $result;
	}

	/**
	 * Forget the cached allowance.
	 *
	 * @return void
	 */
	public function forgetQuota() {
		delete_transient( 'hmwp_ai_quota' );
	}
}
