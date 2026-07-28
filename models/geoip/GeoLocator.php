<?php
defined( 'ABSPATH' ) || die( 'Cheating uh?' );

class HMWP_Models_Geoip_GeoLocator {

	const SOURCE_WFLOGS = 1;
	const DATABASE_FILE_NAME = 'GeoCountry.mmdb';
	private $database;
	private static $instances = array();
	private static $cache = array();

	/**
	 * @param $database
	 *
	 * @return $this
	 */
	private function init( $database ) {
		$this->database = $database;

		return $this;
	}

	/**
	 * Get the Database Filename for Country Blocking
	 *
	 * @return string
	 */
	public function getDatabaseFilename() {
		return self::DATABASE_FILE_NAME;
	}

	/**
	 * Locate the IP address
	 *
	 * @param string $ip
	 *
	 * @return null
	 */
	public function locate( $ip ) {

		if ( $this->database !== null ) {
			try {

				$record = $this->database->search( $ip );

				if ( $record !== null ) {
					return HMWP_Classes_ObjController::getClass( 'HMWP_Models_Geoip_IpLocator' )->init( $record );
				}
			} catch ( Exception $e ) {
			}
		}

		return null;
	}

	public function getCountryCode( $ip, $default = '' ) {
		$cacheKey   = md5( $ip );

		if ( isset(self::$cache[$cacheKey]) ) {
			return self::$cache[$cacheKey];
		}

		$record = $this->locate( $ip );
		$value  = ( $record !== null ) ? $record->getCountryCode() : $default;

		self::$cache[$cacheKey] = $value;

		return $value;
	}

	private function getDatabaseDirectory() {
		return __DIR__;
	}

	private function initializeDatabase() {

		try {
			$path = $this->getDatabaseDirectory() . '/' . $this->getDatabaseFilename();

			if ( file_exists( $path ) ) {
				return HMWP_Classes_ObjController::getClass( 'HMWP_Models_Geoip_Database' )->open( $path );
			}
		} catch ( Exception $e ) {
		}

		return null;
	}

	/**
	 * Retrieves an instance of the GeoLocator object for the preferred source.
	 *
	 * If the instance for the preferred source does not exist, it initializes the database
	 * and creates a new instance of the GeoLocator model, storing it in the instances array.
	 *
	 * @return object The GeoLocator instance for the preferred source.
	 * @throws Exception
	 */
	public function getInstance() {

		$preferredSource = self::SOURCE_WFLOGS;

		if ( ! array_key_exists( $preferredSource, self::$instances ) ) {
			$database = $this->initializeDatabase();

			self::$instances[ $preferredSource ] = HMWP_Classes_ObjController::newInstance( 'HMWP_Models_Geoip_GeoLocator' )->init( $database );
		}

		return self::$instances[ $preferredSource ];
	}

	/**
	 * Get Countries for GeoIp Blocking
	 *
	 * @return array
	 */
	public function getCountryCodes() {
		return array( "AD" => __( "Andorra" ), "AE" => __( "United Arab Emirates" ), "AF" => __( "Afghanistan" ), "AG" => __( "Antigua and Barbuda" ), "AI" => __( "Anguilla" ), "AL" => __( "Albania" ), "AM" => __( "Armenia" ), "AO" => __( "Angola" ), "AQ" => __( "Antarctica" ), "AR" => __( "Argentina" ), "AS" => __( "American Samoa" ), "AT" => __( "Austria" ), "AU" => __( "Australia" ), "AW" => __( "Aruba" ), "AX" => __( "Aland Islands" ), "AZ" => __( "Azerbaijan" ), "BA" => __( "Bosnia and Herzegovina" ), "BB" => __( "Barbados" ), "BD" => __( "Bangladesh" ), "BE" => __( "Belgium" ), "BF" => __( "Burkina Faso" ), "BG" => __( "Bulgaria" ), "BH" => __( "Bahrain" ), "BI" => __( "Burundi" ), "BJ" => __( "Benin" ), "BL" => __( "Saint Bartelemey" ), "BM" => __( "Bermuda" ), "BN" => __( "Brunei Darussalam" ), "BO" => __( "Bolivia" ), "BQ" => __( "Bonaire, Saint Eustatius and Saba" ), "BR" => __( "Brazil" ), "BS" => __( "Bahamas" ), "BT" => __( "Bhutan" ), "BV" => __( "Bouvet Island" ), "BW" => __( "Botswana" ), "BY" => __( "Belarus" ), "BZ" => __( "Belize" ), "CA" => __( "Canada" ), "CC" => __( "Cocos (Keeling) Islands" ), "CD" => __( "Congo, The Democratic Republic of the" ), "CF" => __( "Central African Republic" ), "CG" => __( "Congo" ), "CH" => __( "Switzerland" ), "CI" => __( "Cote dIvoire" ), "CK" => __( "Cook Islands" ), "CL" => __( "Chile" ), "CM" => __( "Cameroon" ), "CN" => __( "China" ), "CO" => __( "Colombia" ), "CR" => __( "Costa Rica" ), "CU" => __( "Cuba" ), "CV" => __( "Cape Verde" ), "CW" => __( "Curacao" ), "CX" => __( "Christmas Island" ), "CY" => __( "Cyprus" ), "CZ" => __( "Czech Republic" ), "DE" => __( "Germany" ), "DJ" => __( "Djibouti" ), "DK" => __( "Denmark" ), "DM" => __( "Dominica" ), "DO" => __( "Dominican Republic" ), "DZ" => __( "Algeria" ), "EC" => __( "Ecuador" ), "EE" => __( "Estonia" ), "EG" => __( "Egypt" ), "EH" => __( "Western Sahara" ), "ER" => __( "Eritrea" ), "ES" => __( "Spain" ), "ET" => __( "Ethiopia" ), "EU" => __( "Europe" ), "FI" => __( "Finland" ), "FJ" => __( "Fiji" ), "FK" => __( "Falkland Islands (Malvinas)" ), "FM" => __( "Micronesia, Federated States of" ), "FO" => __( "Faroe Islands" ), "FR" => __( "France" ), "GA" => __( "Gabon" ), "GB" => __( "United Kingdom" ), "GD" => __( "Grenada" ), "GE" => __( "Georgia" ), "GF" => __( "French Guiana" ), "GG" => __( "Guernsey" ), "GH" => __( "Ghana" ), "GI" => __( "Gibraltar" ), "GL" => __( "Greenland" ), "GM" => __( "Gambia" ), "GN" => __( "Guinea" ), "GP" => __( "Guadeloupe" ), "GQ" => __( "Equatorial Guinea" ), "GR" => __( "Greece" ), "GS" => __( "South Georgia and the South Sandwich Islands" ), "GT" => __( "Guatemala" ), "GU" => __( "Guam" ), "GW" => __( "Guinea-Bissau" ), "GY" => __( "Guyana" ), "HK" => __( "Hong Kong" ), "HM" => __( "Heard Island and McDonald Islands" ), "HN" => __( "Honduras" ), "HR" => __( "Croatia" ), "HT" => __( "Haiti" ), "HU" => __( "Hungary" ), "ID" => __( "Indonesia" ), "IE" => __( "Ireland" ), "IL" => __( "Israel" ), "IM" => __( "Isle of Man" ), "IN" => __( "India" ), "IO" => __( "British Indian Ocean Territory" ), "IQ" => __( "Iraq" ), "IR" => __( "Iran, Islamic Republic of" ), "IS" => __( "Iceland" ), "IT" => __( "Italy" ), "JE" => __( "Jersey" ), "JM" => __( "Jamaica" ), "JO" => __( "Jordan" ), "JP" => __( "Japan" ), "KE" => __( "Kenya" ), "KG" => __( "Kyrgyzstan" ), "KH" => __( "Cambodia" ), "KI" => __( "Kiribati" ), "KM" => __( "Comoros" ), "KN" => __( "Saint Kitts and Nevis" ), "KP" => __( "North Korea" ), "KR" => __( "South Korea" ), "KW" => __( "Kuwait" ), "KY" => __( "Cayman Islands" ), "KZ" => __( "Kazakhstan" ), "LA" => __( "Lao Peoples Democratic Republic" ), "LB" => __( "Lebanon" ), "LC" => __( "Saint Lucia" ), "LI" => __( "Liechtenstein" ), "LK" => __( "Sri Lanka" ), "LR" => __( "Liberia" ), "LS" => __( "Lesotho" ), "LT" => __( "Lithuania" ), "LU" => __( "Luxembourg" ), "LV" => __( "Latvia" ), "LY" => __( "Libyan Arab Jamahiriya" ), "MA" => __( "Morocco" ), "MC" => __( "Monaco" ), "MD" => __( "Moldova, Republic of" ), "ME" => __( "Montenegro" ), "MF" => __( "Saint Martin" ), "MG" => __( "Madagascar" ), "MH" => __( "Marshall Islands" ), "MK" => __( "North Macedonia, Republic of" ), "ML" => __( "Mali" ), "MM" => __( "Myanmar" ), "MN" => __( "Mongolia" ), "MO" => __( "Macao" ), "MP" => __( "Northern Mariana Islands" ), "MQ" => __( "Martinique" ), "MR" => __( "Mauritania" ), "MS" => __( "Montserrat" ), "MT" => __( "Malta" ), "MU" => __( "Mauritius" ), "MV" => __( "Maldives" ), "MW" => __( "Malawi" ), "MX" => __( "Mexico" ), "MY" => __( "Malaysia" ), "MZ" => __( "Mozambique" ), "NA" => __( "Namibia" ), "NC" => __( "New Caledonia" ), "NE" => __( "Niger" ), "NF" => __( "Norfolk Island" ), "NG" => __( "Nigeria" ), "NI" => __( "Nicaragua" ), "NL" => __( "Netherlands" ), "NO" => __( "Norway" ), "NP" => __( "Nepal" ), "NR" => __( "Nauru" ), "NU" => __( "Niue" ), "NZ" => __( "New Zealand" ), "OM" => __( "Oman" ), "PA" => __( "Panama" ), "PE" => __( "Peru" ), "PF" => __( "French Polynesia" ), "PG" => __( "Papua New Guinea" ), "PH" => __( "Philippines" ), "PK" => __( "Pakistan" ), "PL" => __( "Poland" ), "PM" => __( "Saint Pierre and Miquelon" ), "PN" => __( "Pitcairn" ), "PR" => __( "Puerto Rico" ), "PS" => __( "Palestinian Territory" ), "PT" => __( "Portugal" ), "PW" => __( "Palau" ), "PY" => __( "Paraguay" ), "QA" => __( "Qatar" ), "RE" => __( "Reunion" ), "RO" => __( "Romania" ), "RS" => __( "Serbia" ), "RU" => __( "Russian Federation" ), "RW" => __( "Rwanda" ), "SA" => __( "Saudi Arabia" ), "SB" => __( "Solomon Islands" ), "SC" => __( "Seychelles" ), "SD" => __( "Sudan" ), "SE" => __( "Sweden" ), "SG" => __( "Singapore" ), "SH" => __( "Saint Helena" ), "SI" => __( "Slovenia" ), "SJ" => __( "Svalbard and Jan Mayen" ), "SK" => __( "Slovakia" ), "SL" => __( "Sierra Leone" ), "SM" => __( "San Marino" ), "SN" => __( "Senegal" ), "SO" => __( "Somalia" ), "SR" => __( "Suriname" ), "ST" => __( "Sao Tome and Principe" ), "SV" => __( "El Salvador" ), "SX" => __( "Sint Maarten" ), "SY" => __( "Syrian Arab Republic" ), "SZ" => __( "Swaziland" ), "TC" => __( "Turks and Caicos Islands" ), "TD" => __( "Chad" ), "TF" => __( "French Southern Territories" ), "TG" => __( "Togo" ), "TH" => __( "Thailand" ), "TJ" => __( "Tajikistan" ), "TK" => __( "Tokelau" ), "TL" => __( "Timor-Leste" ), "TM" => __( "Turkmenistan" ), "TN" => __( "Tunisia" ), "TO" => __( "Tonga" ), "TR" => __( "Turkey" ), "TT" => __( "Trinidad and Tobago" ), "TV" => __( "Tuvalu" ), "TW" => __( "Taiwan" ), "TZ" => __( "Tanzania, United Republic of" ), "UA" => __( "Ukraine" ), "UG" => __( "Uganda" ), "UM" => __( "United States Minor Outlying Islands" ), "US" => __( "United States" ), "UY" => __( "Uruguay" ), "UZ" => __( "Uzbekistan" ), "VA" => __( "Holy See (Vatican City State)" ), "VC" => __( "Saint Vincent and the Grenadines" ), "VE" => __( "Venezuela" ), "VG" => __( "Virgin Islands, British" ), "VI" => __( "Virgin Islands, U.S." ), "VN" => __( "Vietnam" ), "VU" => __( "Vanuatu" ), "WF" => __( "Wallis and Futuna" ), "WS" => __( "Samoa" ), "XK" => __( "Kosovo" ), "YE" => __( "Yemen" ), "YT" => __( "Mayotte" ), "ZA" => __( "South Africa" ), "ZM" => __( "Zambia" ), "ZW" => __( "Zimbabwe" ), ); //phpcs:ignore
	}

	/**
	 * Download the Geo Country database
	 *
	 * @param bool $forced
	 *
	 * @return bool
	 */
	public function downloadDatabase( $forced = false) {

		$database = $this->getDatabaseFilename();
		HMWP_Classes_ObjController::initFilesystem();

		// Delete the transient on download
		delete_transient( 'hmwp_geoip_download' );

		// If the file does not exist, download it
		if ( function_exists( 'download_url' ) && ( $forced || ! file_exists( _HMWP_MODEL_DIR_ . 'geoip/' . $database ) ) ) {

			// Get database from Cloud
			if( ! function_exists('wp_generate_password') ){
				include_once ABSPATH . WPINC . '/pluggable.php';
			}

			try{
				$tmp_file = download_url( esc_url( _HMWP_ACCOUNT_SITE_ . '/downloads/' . $database ) );

				// If there is an error, return false
				if ( is_wp_error( $tmp_file ) ) {
					return false;
				}

				// Copy the file locally
				copy( $tmp_file, _HMWP_MODEL_DIR_ . 'geoip/' . $database );
				wp_delete_file( $tmp_file );
			} catch ( Exception $e ){
				return false;
			}

		}

		return true;
	}
}