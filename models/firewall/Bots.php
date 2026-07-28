<?php
/**
 * Firewall Protection
 * Called when the Firewall Protection is activated
 *
 * @file  The Firewall file
 * @package HMWP/Firewall
 * @since 5.0
 */

defined( 'ABSPATH' ) || die( 'Cheating uh?' );

class HMWP_Models_Firewall_Bots {

	/**
	 * Checks if the current HTTP user agent corresponds to a search engine bot.
	 *
	 * @return bool True if the HTTP user agent matches a known bot pattern, false otherwise.
	 */
	public function isSearchEngineBot() {
		if ( empty( $_SERVER['HTTP_USER_AGENT'] ) ) {
			return false;
		}

		$userAgent = wp_unslash( $_SERVER['HTTP_USER_AGENT'] ); //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		return $this->detectBot( $userAgent );
	}

	/**
	 * Determines if the current user agent is a search engine bot by checking against patterns for Google, Yandex, and other bots.
	 *
	 * @return bool True if the user agent matches any known search engine bot patterns, false otherwise.
	 */
	public function detectBot( $userAgent ) {

		// Classic search / crawler bots
		$googleUserAgent = array(
			'@^Mozilla/5.0 (.*Google Keyword Tool.*)$@',
			'@^Mozilla/5.0 (.*Feedfetcher-Google.*)$@',
			'@^Feedfetcher-Google-iGoogleGadgets.*$@',
			'@^searchbot admin\@google.com$@',
			'@^Google-Site-Verification.*$@',
			'@^Google OpenSocial agent.*$@',
			'@^.*Googlebot-Mobile/2..*$@',
			'@^AdsBot-Google-Mobile.*$@',
			'@^google (.*Enterprise.*)$@',
			'@^Mediapartners-Google.*$@',
			'@^GoogleFriendConnect.*$@',
			'@^googlebot-urlconsole$@',
			'@^.*Google Web Preview.*$@',
			'@^Feedfetcher-Google.*$@',
			'@^AppEngine-Google.*$@',
			'@^Googlebot-Video.*$@',
			'@^Googlebot-Image.*$@',
			'@^Google-Sitemaps.*$@',
			'@^Googlebot/Test.*$@',
			'@^Googlebot-News.*$@',
			'@^.*Googlebot/2.1;.*google.com/bot.html.*$@',
			'@^AdsBot-Google.*$@',
			'@^.*APIs-Google.*$@',
			'@^.*DuplexWeb-Google.*$@',
			'@^.*Google\sFavicon.*$@',
			'@^.*Google-Read-Aloud.*$@',
			'@^.*Google-InspectionTool.*$@',
			'@^.*GoogleInspectionTool.*$@',
			'@^.*Storebot-Google.*$@',
			'@^.*googleweblight.*$@'
		);


		$yandexUserAgent = array(
			'@^.*YandexAccessibilityBot/3.0.*yandex.com/bots.*@',
			'@^.*YandexBot/3.0.*yandex.com/bots.*@',
			'@^.*YandexFavicons/1.0.*yandex.com/bots.*@',
			'@^.*YandexImages/3.0.*yandex.com/bots.*@',
			'@^.*YandexMobileScreenShotBot/1.0.*yandex.com/bots.*@',
			'@^.*YandexNews/4.0.*yandex.com/bots.*@',
			'@^.*YandexSearchShop/1.0.*yandex.com/bots.*@',
			'@^.*YandexSpravBot/1.0.*yandex.com/bots.*@',
			'@^.*YandexVertis/3.0.*yandex.com/bots.*@',
			'@^.*YandexVideo/3.0.*yandex.com/bots.*@',
			'@^.*YandexVideoParser/1.0.*yandex.com/bots.*@',
			'@^.*YandexWebmaster/2.0.*yandex.com/bots.*@',
			'@^.*YandexMobileBot/3.0.*yandex.com/bots.*@',
			'@^.*YandexCalendar/1.0.*yandex.com/bots.*@',
		);

		// Other search engines + AI / Chat / LLM crawlers
		$otherBots = array(
			// classic
			'@^.*bingbot.*@',
			'@^.*AdIdxBot.*@',
			'@^.*DuckDuckGo/.*@',
			'@^.*Baiduspider.*@',
			'@^.*Yahoo! Slurp.*@',
			'@^.*grapeshot.*@',
			'@^.*proximic.*@',

			// OpenAI / ChatGPT
			'@^.*GPTBot.*@',
			'@^.*ChatGPT-User.*@',
			'@^.*OAI-SearchBot.*@',

			// Anthropic / Claude
			'@^.*ClaudeBot.*@',
			'@^.*Claude-User.*@',
			'@^.*Claude-Web.*@',
			'@^.*anthropic-ai.*@',

			// Perplexity
			'@^.*PerplexityBot.*@',
			'@^.*Perplexity-User.*@',

			// Common Crawl
			'@^.*CCBot.*@',

			// Google AI-related
			'@^.*GoogleOther.*@',
			'@^.*GoogleOther-Image.*@',
			'@^.*GoogleOther-Video.*@',
			'@^.*Google-CloudVertexBot.*@',
			'@^.*Google-Extended.*@',

			// Apple / Siri / Apple Intelligence
			'@^.*Applebot-Extended.*@',
			'@^.*Applebot.*@',

			// Amazon / Meta / TikTok etc. (used by AI answer engines)
			'@^.*Amazonbot.*@',
			'@^.*FacebookBot.*@',
			'@^.*Meta-ExternalAgent.*@',
			'@^.*TikTokSpider.*@',
			'@^.*YouBot.*@',

			// Other AI data
			'@^.*AI2Bot.*@',
			'@^.*Ai2Bot-Dolma.*@',
			'@^.*aiHitBot.*@',
			'@^.*Bytespider.*@',
			'@^.*cohere-ai.*@',
			'@^.*cohere-training-data-crawler.*@',
			'@^.*DuckAssistBot.*@',
			'@^.*img2dataset.*@',
			'@^.*omgili.*@',
			'@^.*omgilibot.*@',
			'@^.*Quora-Bot.*@',
		);

		$allBots = array_merge( $googleUserAgent, $yandexUserAgent, $otherBots );

		foreach ( $allBots as $pat ) {
			if ( preg_match( $pat . 'i', $userAgent ) ) {
				return true;
			}
		}

		return false;
	}


}
