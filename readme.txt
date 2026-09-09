=== Hide My WP Ghost - Security & Firewall ===
Contributors: johndarrel
Tags: security,firewall,brute force,login,hide my wp
Requires at least: 5.8
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 7.0.10
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Hide My WP Ghost is a WordPress security plugin that hides WP paths and protects your site with Path Security, Firewall, Brute Force Protection, 2FA

== Description ==

WP Ghost (formerly Hide My WP Ghost) is a professional, comprehensive hack-prevention security solution for WordPress. Built for speed and maximum defense, it provides a multi-layered security architecture to block hacker bots, neutralize automated scanners, and stop hacks before reconnaissance begins.

While traditional security tools focus on Detection (scanning for malware after a breach) or Signature-Filtering (blocking known exploits), WP Ghost focuses on Architecture. By implementing Paths Security and Site Hardening, it removes digital footprints that make your site a target for automated botnets. This provides a proactive foundation that secures your site before it can be identified as a target.

[youtube https://youtu.be/QMdoSN8dk1c]

WP Ghost Global Stats:

* 10 Million+ Monthly Brute-Force Attempts Blocked
* 100 Million+ Monthly Security Threats Prevented

Official websites:
WP Ghost (wpghost.com)
Hide My WP Ghost (hidemywpghost.com)

Hide WordPress with Path Security and Reduce Your Attack Surface

Hide WordPress paths that attackers commonly scan, including wp-admin, wp-login.php, wp-content, wp-includes, plugin paths and theme paths.

Most WordPress attacks are automated. Bots scan millions of sites per hour looking for default paths like /wp-admin or /wp-login.php to confirm a site is running WordPress. Once confirmed, they launch targeted exploits against known plugin or theme vulnerabilities.

WP Ghost breaks this cycle. By changing and securing common paths, you reduce your attack surface by up to 90%. This is not “obscurity”, it is Site Hardening. The visible structure of your site is re-engineered so it is no longer low-hanging fruit for global botnets.

NEW: AI Security Explanations - Your Report, Explained for Your Website

Every security plugin hands you a list of red warnings. Almost none of them tell you what those warnings mean for your website.

WP Ghost now does. One click sends your findings for analysis based on your site’s actual configuration: server type, whether rewrite rules are active, if your config file is writable, and your security mode. It then returns a security report written specifically for you, not for WordPress in general.

Requires a WP Ghost subscription, which includes a monthly allowance of AI checks. Everything else on the Security Check page - the scan, the score, the prioritised list, the severity bands and every task detail - is free and works on its own.

What you get back:

* A plain-language summary of your whole website. One paragraph that finally answers “so, am I actually secure?”
* A headline you can understand. The technical task name is replaced with what is genuinely at stake.
* One clear action per finding. Not a description of the problem - an instruction telling you what to do.
* A security explanation on demand. Open Details on any finding and you get the reasoning: why it matters on your server, what an attacker would do with it, and why it was ranked where it was. This is the part that teaches you WordPress security instead of just flagging it.
* Priority based on real exposure. Findings are re-ranked using your actual configuration, so the item at the top is the one that genuinely puts you at risk - not the one that scores highest in a generic table.

Built with limits you can check. Only your findings and a small description of your setup are ever sent - no page content, no user data, no credentials. Your security list, your score, the severity bands and every task detail are generated locally and keep working whether the AI is available or not.

Read more about AI Security Explanations on wpghost.com

NEW: Ghost Doctor - Finds and Repairs a Broken Site by Itself

Changing your WordPress paths depends on your server honouring the new rules. When it doesn’t - Nginx never reads .htaccess, Apache ignores it without AllowOverride, a host locks the config file - your site can break, and nothing tells you why.

Ghost Doctor diagnoses issues and repairs what a plugin can. It checks your homepage, theme files, editor requests, and REST API as a visitor would see them. Then it works through repairs from safest to most invasive. Every repair is tested immediately and undone automatically if it does not help, so no setting is changed unnecessarily. Anything requiring a server change is clearly named with a guide, avoiding guesswork.

Read the Ghost Doctor troubleshooting guides

Key Protections Included

WP Ghost is packed with advanced defensive mechanisms to protect your site against:

* Brute Force Attacks: Blocks automated password guessing at the source.
* SQL Injection & XSS: Neutralizes malicious query strings and script injections.
* Zero-Day Exploits: Secures paths for plugins before patches are even released.
* XML-RPC & REST API Attacks: Shuts down common remote-access entry points.
* Bot Reconnaissance: Prevents “fingerprinting” that hackers use to map your site.
* Spam & Scrapers: Filters malicious traffic, saving bandwidth and server load.

Over 115 Free Security Features Included

We believe professional security should be accessible to everyone. The free version of WP Ghost includes a large suite of tools to harden your WordPress architecture.

1. Change and Secure Paths (Paths Security)

* Change wp-admin & wp-login.php: Move your login to a unique URL and show a 404 error to intruders.
* Change Lost Password & Register URLs: Secure all authentication entry points.
* Change wp-content & wp-includes: Secure your core system folders from direct access.
* Anonymize Plugins & Themes: Change visible plugin/theme paths so hackers can’t identify your software version.
* Secure admin-ajax.php & REST API: Change the /wp-json path to prevent data scraping.
* Security Presets: One-click activation with three preset levels from minimal to full protection including Firewall, Brute Force, Logs, and 2FA.
* Frontend Test: Verify your site loads correctly after changing paths before confirming settings.
* Custom Redirects: Set unique login/logout redirects based on user roles.
* Login Page Designer: Customize your secured login page with your logo, colors, background, and 10 color schemes.

2. WordPress Firewall Protection & WordPress Login Security

* 8G & 7G Firewall Filters: High-speed, lightweight server-edge filtering to block bad bots.
* Passkey Authentication (Passwordless 2FA): Use Face ID, Touch ID, or Windows Hello for un-phishable, device-based logins.
* Standard 2FA (Code & Email): Add an extra verification layer to all user accounts.
* Security Headers: Automatically implement CSP, HSTS, X-Frame-Options, and more.
* IP & User Agent Blocking: Manually blacklist suspicious traffic or referrers.
* Security Threats Log: Track blocked attacks and malicious requests directly in your dashboard (limited view).
* User Events Log: Monitor login activity, role changes, and user actions (limited view).
* GEO Threats Map: Visualize where attacks originate with an interactive world map showing the top 5 threat countries.
* Security Optimization Score: Real-time 0-100 score showing exactly how hardened your site is, with actionable recommendations.
* Temporary Logins: Create time-limited access links for developers and clients without sharing passwords.

3. Deep Hiding & Footprint Removal

* Scrub Meta Tags: Remove WordPress version numbers and generator tags.
* Clean HTML Comments: Strip identifiable comments that reveal your tech stack.
* Hide Admin Toolbar: Remove the toolbar for specific roles to hide backend indicators.
* Disable Emoticons & RSD: Remove unnecessary header links that bloat code and reveal info.

4. Advanced Disable Options

* Disable XML-RPC: Shut down the most common vector for DDoS and brute force.
* Disable REST API Access: Restrict API access to authenticated users only.
* Frontend Lockdown: Disable right-click, “View Source,” and text selection to prevent manual reconnaissance.
* Disable Directory Browsing: Ensure your server folders are never visible to the public.

5. Brute Force Protection

* Integrated ReCaptcha: Supports Google V2, V3, Enterprise, and Math ReCaptcha.
* Targeted Protection: Enable brute force defense on Login, Signup, and WooCommerce pages.
* Custom Throttling: Define your own lockout times and attempt limits.

6. Extra Tools & Integrations

* Magic Links: Log in securely without a password via a one-time email link.
* Text & URL Mapping: Change any class name or URL in your source code dynamically.
* CDN & Cache Support: Works perfectly with WP Rocket, Cloudflare, and Litespeed.
* Ghost Doctor: Diagnoses why your paths stopped working and repairs what it can. It tests every repair and undoes anything that does not help.
* Prioritized Security Check: Failing paths and failed hardening tasks appear as one list, worst first, with time-based bands such as “worth fixing this week” instead of separate tables to reconcile.
* AI Security Explanations: Get every finding explained for your website and ranked by real exposure. Requires a connected WP Ghost account with an active subscription; a monthly allowance applies. Learn more.

Premium Hack-Prevention Features

For agencies and high-traffic sites, WP Ghost Premium adds advanced features focused on Security Intelligence, Automated Response, and Copyright Protection.

* AI Security Explanations: Your findings explained for your own website and re-ranked by what actually exposes you, with a monthly allowance of AI checks included in your subscription. Read more.
* Ghost Mode: Maximum security preset, changes all paths, hides all file extensions, and enables all hiding options in one click.
* IP Block Automation: Automatically block IP addresses that trigger repeated security threats.
* AI Copyright Protection: Block 30+ AI training crawlers (GPTBot, ClaudeBot, PerplexityBot, and others) at the firewall level. List auto-updated with each release. Does not affect Google, Bing, or regular search visibility.
* Full Security Threats Log: Unlimited entries with filters by threat type, status, country, and time range, full-text search, pagination, and CSV export.
* Full User Events Log: Unlimited entries with filters, search, pagination, and CSV export.
* Cloud Event Storage: 30-day cloud retention for audits and incident reports.
* Real-time Email Alerts: Get notified instantly of brute-force attempts or suspicious activity.
* Geo-Security (Country Blocking): Block entire countries or specific paths by country.
* Advanced File Hardening: Hide file extensions (PHP, CSS, JS, JSON), secure wp-config.php, php.ini, and debug.log.
* Database & Server Hardening: Fix file permissions, change database prefix, regenerate SALT keys.
* Priority Support: Direct access to our security experts and founder-led assistance.

Hide My WP Premium Feature

== Technical Compatibility ==

WP Ghost is engineered for the modern WordPress ecosystem:

* Hosting Support: Optimized for WP Engine, Inmotion Hosting, Hostgator Hosting, Godaddy Hosting, Host1plus, Payperhost, Fastcomet, Dreamhost, Bitnami Apache, Bitnami Nginx, Google Cloud Hosting, Amazon AWS Lightsail, Litespeed Hosting, Flywheels Hosting, Kinsta Hosting, Ploi.io, CloudPanel, RunCloud, Rocket Domain, Yunohost.
* Server Support: Fully compatible with Nginx, Apache, LiteSpeed, and IIS.
* Plugin Support: Seamless integration with Woocommerce, WPML, WPMUDEV, W3 Total Cache, Gravity, WP Super Cache, WP Fastest Cache, Hummingbird Cache, Cachify Cache, Litespeed Cache, SiteGround Optimizer, Nitropack, Cache Enabler, CDN Enabler, WOT Cache, Autoptimize, Jetpack by WordPress, Contact Form 7, bbPress, Manage WP, All In One SEO, Rank Math, Yoast SEO, Squirrly SEO, WP-Rocket, Minify HTML, Solid Security, Sucuri Security, Really Simple SSL, WordFence Security, WP Cerber Security, BBQ Firewall, Anti-Malware Security, Back-Up WordPress, Elementor Page Builder, Divi Builder, Weglot Translate, AddToAny Share Btn, Limit Login Attempts Reloaded, Loginizer, Shield Security, Asset CleanUp, WP Hide & Security Enhancer, and more.

Stop the hack before it starts. Join over 100,000 users who trust WP Ghost to secure their digital presence.

== Installation ==

= From your WordPress Dashboard =

Step 1. Navigate to Plugins > Add New.
Step 2. Search for “WP Ghost”.
Step 3. Click Install Now and then Activate.
Step 4. Go to the WP Ghost menu in your sidebar.
Step 5. Enter your email address to receive your instant Free Access Token.
Step 6. Follow the built-in Setup Wizard to begin hardening your paths.

= Manual Installation =

Step 1. Download the hide-my-wp.zip file from the WordPress repository or your WP Ghost account.
Step 2. Log in to your WordPress dashboard as an Administrator.
Step 3. Navigate to Plugins > Add New > Upload Plugin.
Step 4. Select the .zip file and click Install Now.
Step 5. Click Activate Plugin.
Step 6. Connect the plugin using your email address to activate your security features.

= Reporting a Security Vulnerability =

Found a security issue in WP Ghost? Report it privately through our Patchstack managed disclosure programme at patchstack.com/database/report or email security@hidemywpghost.com. We acknowledge reports within 48 hours and coordinate disclosure within 90 days. Please do not post details in the support forum before a fix is released. The full policy is included with the plugin in SECURITY.md, and third-party components are listed in sbom.json.

Support period: Security updates are provided for 5 years from each version’s release. Version 7.0.10, released September 2026, is supported until September 2031. Installing a newer release extends the end date.

= Resources & Guides =
For advanced server configurations or detailed walkthroughs, please visit our comprehensive documentation:
How to Install and Setup WP Ghost

WP Ghost Knowledge Base:

== Screenshots ==

1. WP Ghost Overview: Choose your Level of Security to instantly harden your site architecture.
2. Admin Security: Change and secure the wp-admin path to block unauthorized dashboard access.
3. Paths Security: Customize and secure your login and registration entry points.
4. Core Security: Harden your system paths (wp-content, uploads, includes) against bot reconnaissance.
5. API & AJAX Security: Secure the REST API and admin-ajax paths to prevent data scraping.
6. 8G Firewall Engine: High-performance, server-edge threat filtering for proactive hack prevention.
7. Brute Force Defense: Integrated Google reCaptcha and Math protection for all authentication paths.
8. Modern Authentication: Secure logins with 2FA and future-proof Passkey (Passwordless) support.
9. Text Mapping: Dynamically change class names and IDs in your source code to prevent fingerprinting.
10. URL Mapping: Re-engineer internal URLs and paths for elite-level site hardening.
11. Hardening Tweaks: Deep hide options to remove WordPress version tags and identifiable meta-data.
12. Redirect Logic: Custom 404 and role-based redirect options for secured paths.
13. Safe Access: Manage Temporary Logins and Magic Links for secure developer access.
14. Security Threats Log: Activate Security Threats Log to track blocked attacks and malicious requests.
15. Security Threats Log: The list of the recent threats prevented by WP Ghost.
16. Overview Dashboard: An overview of the last 7 days of security events.
17. Front-end View: Example of a custom, secured login path (/newlogin).
18. Attack Blocked: Default wp-login.php now returns a 404 error to confuse hacker bots.
19. Access Denied: Default wp-admin path is fully secured and hidden from public view.
20. Source Code Proof: Core WordPress paths transformed and secured to neutralize bot scans.

== Changelog ==
= 7.0.10 (2 September 2026) =

* Update - Security update: the compatibility exceptions for WooCommerce, WPML and MainWP now confirm the request really comes from those plugins before the plugin steps aside
* Update - Security update: compatibility paths are matched from the site root, so an address that only contains a known path no longer matches
* Update - Security update: the iThemes/Solid Security loopback exception now requires that plugin to be active and a matching verification value
* Update - Security update: Temporary Login and Magic Login make the login page reachable only once the token matches a real user
* Update - Security update: the page builder preview exception now requires editing rights, and the logged in check during early load is stricter
* New - One click login support for the hosting panels: Softaculous (cPanel, CentOS Web Panel, DirectAdmin, Webuzo, Plesk) and aaPanel WP Toolkit
* Update - Security update: hardening for the way the plugin resolves and handles the current request
* Fix - CSS and JS are compressed only with what the browser actually accepts, instead of always answering compressed
* Fix - Compressed responses now use gzip, which every browser and CDN reads the same way
* Fix - Static files and the REST API path are now handled correctly when your site is reached on an alias domain
* Fix - REST API responses handled by the plugin no longer carry compression headers that do not match the returned content
* Fix - LiteSpeed: the QUIC.cloud address list is refreshed on its own and no longer kept forever, so QUIC.cloud keeps working as their addresses change
* Fix - LiteSpeed: the QUIC.cloud addresses are loaded even when the plugin settings have not been saved since LiteSpeed was installed
* Fix - Defender Security: the free edition of the plugin is now recognized, not only the Pro edition
* Fix - Ghost Doctor no longer reports the CSS and JS files as not loading when Text Mapping in CSS and JS files is on and those files are served by WordPress on purpose
* Fix - Ghost Doctor now checks the REST API path the way WordPress answers it, instead of reporting a working REST API as missing
* Fix - Changing the REST API path takes effect on save, without having to open Settings > Permalinks and save again
* Fix - No longer stops with a fatal error on servers where the getallheaders() function is missing
* Fix - Frontend Check no longer shows the word “undefined” at the end of the results when a path or an asset fails
* New - New hmwp_files_allowed_hosts, hmwp_files_request_headers, hmwp_files_response_headers, hmwp_files_skip_headers, hmwp_files_cache, hmwp_files_cache_dir and hmwp_files_cache_lifetime filters for developers

= 7.0.09 (17 August 2026) =

* New - AI Security Explanations: every finding on the Security Check page explained for your own website, in plain language
* New - AI Security Explanations rank your findings by what actually exposes your site, using your server type, your rules and your security mode
* New - A plain-language summary of your whole website, above the list of what to fix
* New - Open Details on any finding to read why it matters on your server and what an attacker would do with it
* Fix - Fix it now works for the Security Check tasks that change a plugin setting, such as Hide Old Paths, Hide Common Files, Hide wp-login and Disable XML-RPC
* Fix - Fix it no longer answers with “Ajax is not loading correctly. Clear all cache and try again.” after the paths have been changed
* Fix - The Security Check summary now shows whether your paths are working instead of always reporting that they have not been checked
* Update - Published a coordinated vulnerability disclosure policy (SECURITY.md), a list of the third-party components we bundle (sbom.json), and a declared 5 year security update period

= 7.0.08 (27 July 2026) =

* New - Ghost Doctor: finds what is breaking your paths and repairs it in one click
* New - Ghost Doctor can switch to protection that needs no server rules when your server will not serve them
* New - Security Check now opens with one prioritized list of what to fix first
* New - Change Paths shows one card after a path change instead of three
* Fix - Security Check no longer reports wp-content, the login path or the admin path as visible on sites that are hiding them correctly
* Fix - Frontend check no longer reports a failure when Disable REST API Access is on
* Fix - Open on a failing path check no longer leads to a 404 while the new paths are not working yet
* Fix - Security Tasks no longer shows an empty table when every task passed
* Fix - Last check now shows how long ago the scan ran, not a clock time

= 7.0.07 (20 July 2026) =

* Security - Two-Factor Authentication settings are now bound to the account they belong to, so the 2FA method, authenticator, email codes, backup codes and passkeys can only be managed by the account owner or by an administrator allowed to edit that user
* Security - Passkey enrollment is now always self-service, matching the device the passkey is created on
* Security - Temporary Login updates and deletions now apply only to temporary accounts, so regular accounts are never affected from this screen
* Security - The role assigned to a temporary login is now validated and can never grant more capabilities than the user creating it already has
* Security - Magic Login links now require the same permissions as the screen they are offered from

= 7.0.06 (15 July 2026) =

* Fix - Editing a custom post type that uses the built-in Categories/Tags taxonomy no longer crashes the Block Editor
* Fix - Block Editor no longer crashes with “Cannot read properties of undefined” when Hide User Enumeration is on
* Fix - Settings page navigation menu no longer loses its layout on sites where another plugin or theme restricts the allowed HTML tags

= 7.0.05 (08 July 2026) =

* New - Force 2FA Setup: require selected user roles to set up Two-Factor Authentication before they can access the dashboard, with a guided setup screen shown right after login
* Fix - Temporary Login page no longer triggers a fatal error when opened for a user that was deleted or expired
* Fix - Two-Factor email setup and Magic Login no longer emit PHP warnings when the requested user no longer exists
* Fix - WP-CLI commands no longer emit a PHP 8.5 “Undefined array key hostname” warning
* Fix - Settings page navigation menu no longer loses its layout on sites where another plugin or theme restricts the allowed HTML tags; the menu now keeps its own classes and attributes regardless of the site’s wp_kses filters

= 7.0.04 (29 June 2026) =

* New - Frontend Check now also verifies the theme’s CSS and JS files load, catching a broken layout on pages that still return 200
* New - Detects when CSS/JS/font files load through WordPress instead of the server config, falls back to safe paths and warns you on the settings page
* Improvement - Redesigned the Frontend Check results into clear, uniform rows that stay readable with long URLs

= 7.0.03 (02 June 2026) =

* Fix - WPML/Polylang with a custom or renamed REST API path: WPML Advanced Translation Editor and other REST API calls no longer fail with a network error in the admin
* Fix - REST API requests made through the ?rest_route= form are no longer mistakenly treated as normal page requests and blocked
* Fix - Renamed REST API path: legacy, cached and external clients still calling the default wp-json path are now recognized correctly instead of being 404’d
* Fix - Compatibility module for WPML: the Advanced Translation Editor sync routes and WPML/ICL admin-ajax requests are no longer rewritten with the active language prefix
* Fix - Some sites could show a blank page when source code optimization was enabled; the header/tag find & replace is now fail-safe and never blanks the output on a regex error
* Fix - Prefetch/speculation rules cleanup now removes the wp-admin and wp-*.php entries without leaving the rules JSON invalid
* Security - Hardened REST API detection: the firewall can no longer be bypassed by appending /wp-json/ (or ?rest_route=) to the query string of another request
* Security - Brute force protection also covers REST API Application Password authentication
* Security - Fixed unauthenticated Open Redirect via the redirect_to parameter on the custom logout URL.

= 7.0.02 (11 May 2026) =

* Fix - Compatibility with WPML and Polylang: static asset URLs (wp-content, wp-includes) no longer get the language prefix prepended (e.g. /en/wp-content/…) when “Change Relative URLs to Absolute URLs” is enabled
* Fix - Password-protected pages (built-in WordPress post password) now submit correctly on Nginx and other servers without server-level rewrites when the login URL is customized
* Fix - Refreshed knowledge base links across admin notices to point to the new documentation
* Fix - Minor bugs and typos

= 7.0.01 (15 April 2026) =

* Update - Added the option to roll back to the last stable version in the Backup/Restore page
* Fix - Corrected the colors in the dark mode style
* Fix - Rewrite rules not correctly displayed in the notification bar when rules must be added manually to the server config
* Fix - Typos and minor bugs

= 7.0.00 (31 March 2026) =
Major Release: Security Score, Login Page Designer, Passkey 2FA, Security Threats Log, User Events Log, GEO Threats Map, and expanded 7G/8G Firewall rules. Free update for all users.

* New - Security Optimization Score (0-100) with dynamic gauge on the Overview dashboard and Security Check page
* New - Security Threats Log to track blocked attacks and malicious requests (limited view)
* New - User Events Log to track login activity and user changes (limited view)
* New - GEO Threats Map with top 5 threat countries on the Overview dashboard
* New - Country filter in Security Threats Log and User Events Log
* New - Login Page Designer with custom logo, background image, colors, and color scheme presets
* New - Security Presets for one-click activation: Minimal, Lite Mode + Firewall + Compatibility, and Lite Mode + Firewall + Brute Force + Logs + Two Factor
* New - Contextual upgrade suggestions based on your site’s actual threat data
* New - Two-Factor Authentication by Passkey (Face ID, Touch ID, Windows Hello)
* New - Each user can select their preferred 2FA method from their profile
* New - Trust current browser option for 2FA, skip verification on trusted devices
* New - Hide WordPress Common Files (wp-config.php, readme.html, license.txt, php.ini)
* New - Hide WordPress Common Paths with file extension filtering (html, txt, lock)
* New - Hide User Enumeration to block author discovery scans
* New - Dark mode support (browser-based)
* New - Translation in Indonesian (id_ID) language
* New - Translation in Turkish (tr_TR) language

Firewall & Security Updates:

* Update - Expanded 7G & 8G Firewall rules to block SQL injection, XSS, file inclusion, directory traversal, and automated vulnerability scans
* Update - Firewall rules optimized for reduced overhead under high attack traffic
* Update - Improved threat detection to stop malicious requests before WordPress core execution
* Update - Security Headers updated with latest best practices
* Update - Notification on Overview to activate 7G/8G Firewall when unblocked threats are detected

Compatibility:

* Update - Compatibility with WordPress 7.0 and PHP 8.5
* Update - Compatibility with WooCommerce 10.6
* Update - Compatibility with Elementor, Bricks Builder, Blocksy, Nicepage, Avada, and Riode themes
* Update - Compatibility with WP Rocket, LiteSpeed, Nitropack, SiteGround Optimizer, and Cloudflare
* Update - Compatibility with Wordfence, Sucuri, and Solid Security
* Update - Compatibility with WP Engine, Kinsta, CloudPanel, Ploi.io, and IIS servers
* Update - Compatibility with WPML and Polylang for multilingual Brute Force, 2FA, and Magic Login texts
* Update - Translations updated in all 14 supported languages

UI & Experience:

* Update - Redesigned Overview dashboard with Security Optimization Score, threat chart, and GEO map
* Update - Improved Security Check page with numeric score and actionable task list
* Update - Reorganized Logs section into Security Threats and User Events
* Update - Improved activation flow with clearer setup wizard
* Update - Translations updated in all 16 supported languages: Arabic, Brazilian Portuguese, Chinese (Simplified), Dutch, Finnish, French, German, Indonesian, Italian, Japanese, Portuguese, Romanian, Russian, Spanish, Turkish, and English (default).
* Fix - Dropdown and Help icon in the RTL languages

= 5.5.04 (26 Mar 2026) =

* Update - Firewall rules on WP core init
* Update - Added compatibility with Photo Gallery from 10Web
* Fix - Security Check to properly update the options and handle
* Fix - Small bugs

= 5.5.02 (10 Feb 2026) =

* Fix - Compatibility with IIS Server
* Fix - Compatibility with LiteSpeed Quic Cloud

= 5.5.01 (22 Dec 2025) =

* Update - Added the option to hide WordPress Common Paths with extension html, txt, lock
* Update - Added the option to hide WordPress Common Files like wp-config, readme.html, license.html, php.ini
* Update - Added the option to Hide Source Map References
* Update - Added the option to Hide User Enumeration

= 5.4.08 (09 Dec 2025) =

* Update - Compatibility with WP 6.9
* Fix - Remove the wp-*.php and admin path from prefetch paths in WP 6.9
* Update - 2FA to allow each user to select the 2FA method in the profile
* Update - 2FA to connect through passkey and fingerprint
* Update - 2FA to trust the current browser

= 5.4.07 (29 Sept 2025) =

* Update - Compatibility with the plugin WP Social & WP Social PRO
* Update - Compatibility with LiteSpeed Quic Cloud on IPV6
* Update - Make REST API test work when permalinks are set to the default PHP parameter
* Update - Minimum PHP version required is 8.0 in the Security Check section
* Update - Added New Feature Two-factor Authentication By Passkey (2FA)
* Update - Whitelist more known AI Chatbots in firewall rules

= 5.4.06 (21 Aug 2025) =

* Update - Firewall rules for more compatibility
* Update - Safe URL verification process
* Update - Compatibility with the plugin Debloat

= 5.4.05 (27 May 2025) =

* Update - Add AI support in the plugin settings
* Update - 7G & 8G Firewall for more compatibility with WP Plugins
* Update - Compatibility with Riode theme on Brute Force
* Fix - Compatibility WooCommerce login/register with reCaptcha V3
* Fix - Update check error

= 5.4.04 (21 Mar 2025) =

* Update - Compatibility with the WP 6.8
* Fixed - Function _load_textdomain_just_in_time was called incorrectly

= 5.4.03 (11 Mar 2025) =

* Update - Compatibility with the new WP Engine rewrite rules
* Update - Add the option to customize all active and inactive themes
* Fix - File security when the rewrite rules are not loaded correctly
* Fix - Prevent Brute Force from updating the warning text without space when switched off
* Fix - Prevent PHP warning when IP address unknown in Brute Force IP check
* Fix - Load i18n on login page for password-strength-meter messages when the Clean Login option is activated
* Fix - File security when the rewrite rules are not loaded correctly
* Fix - Dynamic file mapping to load through index.php for better compatibility with all server types

= 5.4.02 (04 Mar 2025) =

* Update - Security update on wp-activate.php path call
* Update - Translations in all languages for the last changes
* Update - the Brute Force to load Google Enterprise reCaptcha
* Update - Brute Force compatibility with other plugins
* Fix - Headers check on Brute Force to get the real IP behind Proxy
* Fix - Include parent theme in the custom theme name list if the child theme is loaded
* Fix - Admin layout issue when other plugins notification is loading in Wp Ghost settings
* Fix - Prevent redirecting URLs to hidden paths like new admin path or new login path
* Fix - Paths changed in cache files when CSS and JS files are loaded dynamically
* Fix - Hide the new login on registration redirect when the registration is deactivated
* Fix - Remove newlines from the rewrite rules

Security:
Ocultar Mi WP - Plugin de seguridad de WordPress
Ocultar meu WP - Segurança do WordPress
Cacher mon WordPress - Plugin de sécurité WordPress
Verstecken Sie mein WordPress - WordPress Sicherheits-Plugin

== Frequently Asked Questions ==

= Does WP Ghost physically move or rename my WordPress files? =
No. WP Ghost uses high-performance server rewrite rules (Nginx, Apache, IIS) to change visible paths in your source code. Your actual WordPress files and directories stay exactly where they are, ensuring no risk to your site’s stability or core updates.

= Is WP Ghost a complete standalone solution? =
For most WordPress sites, yes. By combining Architectural Hardening with an 8G Firewall and Automated IP Blocking, WP Ghost neutralizes automated reconnaissance and brute-force attempts that account for over 90% of real-world attacks. It provides a foundational defense often sufficient on its own, while remaining fully compatible with “Defense in Depth” strategies involving malware scanners or file-integrity monitors.

= What are AI Security Explanations? =
Security scanners tell you what is wrong. AI Security Explanations tell you what it means for your website. WP Ghost sends your findings for analysis along with a short description of your site setup: server type, whether rewrite rules are active, if your config file is writable, and your security mode. It returns a plain-language explanation for each finding, one clear action to take, and a priority order based on what genuinely exposes your site. Open Details on any finding to read the reasoning behind it. Read more on wpghost.com.

= What data is sent when I use AI Security Explanations? =
Only the findings already on your Security Check page and a small description of your configuration are sent: server type, whether the config file is writable, whether rules are present, if a cache plugin is installed, your security mode, and a count of mapped files. No page content, user data, passwords, or database contents are ever sent. The feature is entirely opt-in; nothing is transmitted until you press the button.

= Does WP Ghost still work without the AI? =
Completely. The Security Check scan, 0-100 score, prioritized list, severity bands, every task detail, and the entire Ghost Doctor diagnosis and repair process run on your server and need no connection. AI Security Explanations only add wording on top. Using them requires a connected WP Ghost account with an active subscription and a monthly allowance of checks. The page shows exactly how many you have left.

= Is it compatible with other WordPress security plugins? =Yes. WP Ghost is designed as your “Outer Perimeter” defense. It works perfectly alongside malware scanners and reactive security tools like Wordfence, Sucuri, or Solid Security. By implementing Paths Security first, WP Ghost stops bots before they get close enough to be scanned by other plugins..

= Will changing my paths affect my SEO or Google rankings? =
Not at all. WP Ghost handles Sitemap.xml and Robots.txt mapping automatically. This ensures Google and other search engines can index your content perfectly, while malicious bots receive a 404 error when probing your system paths.

= What is the difference between Paths Security and “Security through Obscurity”? =
Obscurity is simply hiding a key under a mat. Paths Security is an architectural hardening strategy like moving the door to a secure, unique location and changing the lock. It is a recognized technical hardening standard used by enterprise-grade sites to prevent Bot Reconnaissance.

= Does WP Ghost work on WP Multisite and different server types? =
Yes. The plugin is fully compatible with WP Multisite (Network-wide configuration) and supports Apache, Nginx, IIS, and LiteSpeed servers.

= How do I configure WP Ghost on an Nginx Server? =
WP Ghost fully supports Nginx. Because Nginx does not use .htaccess, you will be guided to add the generated rewrite rules manually to your nginx.conf file. We provide specific tutorials for Kinsta, RunCloud, CloudPanel, CWP7, AAPanel, and Ploi.io.

= My theme is not loading correctly after changing paths. What should I do? =
This usually happens when the server rewrite rules are not yet active.

* Purge Cache: Clear your WordPress cache and any server-side caching (Varnish, Nginx FastCGI).
* Manual Rewrites: If your server config file is not writable, copy the rules from WP Ghost and add them manually to your .htaccess or nginx.conf.
* Restart Nginx: If on Nginx, you must reload/restart the service after saving settings.
* Free Support: If the issue persists, contact us and we will set up the plugin for you for free.

= I am locked out or forgot my custom login URL. How do I get back in? =

* Safe URL: Use the “Safe URL” text file automatically generated and downloaded when you saved your settings.
* Manual Reset: Access your server via FTP/SFTP and rename the folder /wp-content/plugins/hide-my-wp to something else. This temporarily disables the path changes so you can login via the default wp-login.php.

= Does WP Ghost work for WordPress.com websites? =
Due to the restricted infrastructure of WordPress.com managed hosting, changes to the administrative and login paths are not allowed. However, you can still use WP Ghost for Site Hardening, the 8G Firewall, Passkey Authentication, and other Hack Prevention features.

= Is the WP Ghost plugin free of charge? =
Yes. The Lite version of WP Ghost will always be free and includes essential WordPress Security updates. To unlock advanced features like IP Block Automation, Geo-Security, and Cloud Monitoring, you can upgrade to WP Ghost Premium.

= How can I hide my site from WordPress Theme Detectors? =
By using Paths Security to change common directories (plugins, themes, wp-content), you effectively neutralize most automated detectors. For a deep-dive on total anonymity, read our guide: How to Hide Your Site From WordPress Theme Detectors.

= Is this plugin enough to protect my website from all hackers? =
WP Ghost provides an elite proactive defense by neutralizing the Reconnaissance phase of an attack. While the Free version blocks the vast majority of bot traffic, we recommend the Premium version for advanced Brute Force Protection and Automated Threat Intelligence.

= How do I change the WordPress paths in the Admin Dashboard area? =
By default, WP Ghost only changes paths on the frontend to ensure maximum compatibility. To harden the admin dashboard as well, add define('HMW_ALWAYS_CHANGE_PATHS', true); to your wp-config.php file and re-save your settings.

= Does WP Ghost include a security score? =
Yes. WP Ghost 7.0 includes a Security Optimization Score from 0 to 100 that shows exactly how hardened your site is. The score updates automatically as you enable features and complete security tasks. It appears on the Overview dashboard and the Security Check page as both a visual gauge and a numeric value.

= Can I customize the WordPress login page with WP Ghost? =
Yes. WP Ghost includes a Login Page Designer that lets you add your custom logo, background image, and brand colors to your secured login page. It includes 12 layout presets and 10 color scheme presets. The designer works with your custom login URL, so your branded page is served at your hidden path instead of the default wp-login.php.

= Does WP Ghost protect my content from AI training bots? =
WP Ghost Premium includes an AI Copyright Protection feature that blocks 30+ AI training crawlers including GPTBot, ClaudeBot, PerplexityBot, CCBot, and Bytespider at the firewall level. It also adds Disallow rules to your robots.txt automatically. This protects your copyrighted content from being used for AI model training without affecting your regular Google, Bing, or Yahoo search visibility. The crawler list is automatically updated with each plugin release.