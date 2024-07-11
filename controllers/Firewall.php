<?php
/**
 * Firewall Protection
 * Called when the Firewall Protection is activated
 *
 * @file  The Firewall file
 * @package HMWP/Firewall
 * @since 5.0
 */

defined('ABSPATH') || die('Cheatin\' uh?');

class HMWP_Controllers_Firewall extends HMWP_Classes_FrontController
{

    /**
     * Load the firewall on QUERY and URI
     * @return void
     */
    public function run()
    {

        //If firewall process is activated
        if(!HMWP_Classes_Tools::doFirewall()){
            return;
        }

        //If block detectors is activated
        if (HMWP_Classes_Tools::getOption('hmwp_detectors_block')) {
            if (isset($_SERVER['HTTP_USER_AGENT']) && $_SERVER['HTTP_USER_AGENT'] <> '') {
                if (preg_match('/(wpthemedetector|builtwith|isitwp|wapalyzer|mShots|WhatCMS|gochyu|wpdetector|scanwp)/i', $_SERVER['HTTP_USER_AGENT'])) {
                    //blocked by the firewall
                    $this->firewallBlock('Firewall');
                }
            }
        }

        //If firewall is activated
        if (HMWP_Classes_Tools::getOption('hmwp_sqlinjection')) {
            if ((int)HMWP_Classes_Tools::getOption('hmwp_sqlinjection_level') == 1) {

                if (isset($_SERVER['QUERY_STRING']) && $_SERVER['QUERY_STRING'] <> '') {
                    if (preg_match('/([a-z0-9]{2000,})/i', $_SERVER['REQUEST_URI']) || 
                        preg_match('/(<|%3C).*object.*(>|%3E)/i', $_SERVER['QUERY_STRING']) ||
                        preg_match('/(<|%3C)([^o]*o)+bject.*(>|%3E)/i', $_SERVER['QUERY_STRING']) ||
                        preg_match('/(<|%3C).*iframe.*(>|%3E)/i', $_SERVER['QUERY_STRING']) ||
                        preg_match('/(<|%3C)([^i]*i)+frame.*(>|%3E)/i', $_SERVER['QUERY_STRING']) ||
                        preg_match('/(etc(\/|%2f)passwd|self(\/|%2f)environ)/i', $_SERVER['QUERY_STRING']) ||
                        preg_match('/base64_encode.*\(.*\)/i', $_SERVER['QUERY_STRING']) ||
                        preg_match('/base64_(en|de)code[^(]*\([^)]*\)/i', $_SERVER['QUERY_STRING']) ||
                        preg_match('/(localhost|loopback|127\.0\.0\.1)/i', $_SERVER['QUERY_STRING']) ||
                        preg_match('/((\+|%2b)(concat|delete|get|select|union)(\+|%2b))/i', $_SERVER['QUERY_STRING']) ||
                        preg_match('/(concat|eval)(.*)(\(|%28)/i', $_SERVER['QUERY_STRING']) ||
                        preg_match('/union([^s]*s)+elect/i', $_SERVER['QUERY_STRING']) ||
                        preg_match('/union([^a]*a)+ll([^s]*s)+elect/i', $_SERVER['QUERY_STRING'])) {
                        //blocked by the firewall
                        $this->firewallBlock('5G Firewall');
                    }
                }

            }

            if ((int)HMWP_Classes_Tools::getOption('hmwp_sqlinjection_level') == 2) {

                if (isset($_SERVER['HTTP_USER_AGENT']) && $_SERVER['HTTP_USER_AGENT'] <> '') {
                    if (preg_match('/([a-z0-9]{2000,})/i', $_SERVER['HTTP_USER_AGENT']) || 
                        preg_match('/(&lt;|%0a|%0d|%27|%3c|%3e|%00|0x00)/i', $_SERVER['HTTP_USER_AGENT']) ||
                        preg_match('/(base64_decode|bin\/bash|disconnect|eval|lwp-download|unserialize)/i', $_SERVER['HTTP_USER_AGENT']) ||
                        preg_match('/(%0A|%0D|%3C|%3E|%00)/i', $_SERVER['HTTP_USER_AGENT']) ||
                        preg_match('/(;|<|>|\'|\"|\)|\(|%0A|%0D|%22|%28|%3C|%3E|%00).*(libwww-perl|wget|python|nikto|curl|scan|java|winhttp|HTTrack|clshttp|archiver|loader|email|harvest|extract|grab|miner)/i', $_SERVER['HTTP_USER_AGENT'])) {
                        //blocked by the firewall
                        $this->firewallBlock('6G Firewall');
                    }
                }

                if (isset($_SERVER['QUERY_STRING']) && $_SERVER['QUERY_STRING'] <> '') {
                    if (preg_match('/[a-zA-Z0-9_]=(http|https):\/\//i', $_SERVER['QUERY_STRING']) || 
                        preg_match('/[a-zA-Z0-9_]=(\.\.\/\/?)+/i', $_SERVER['QUERY_STRING']) ||
                        preg_match('/[a-zA-Z0-9_]=\/([a-z0-9_.]\/\/?)+/i', $_SERVER['QUERY_STRING']) ||
                        preg_match('/=PHP[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}/i', $_SERVER['QUERY_STRING']) ||
                        preg_match('/(\.\.\/|%2e%2e%2f|%2e%2e\/|\.\.%2f|%2e\.%2f|%2e\.\/|\.%2e%2f|\.%2e\/)/i', $_SERVER['QUERY_STRING']) ||
                        preg_match('/ftp:/i', $_SERVER['QUERY_STRING']) ||
                        preg_match('/^(.*)\/self\/(.*)$/i', $_SERVER['QUERY_STRING']) ||
                        preg_match('/^(.*)cPath=(http|https):\/\/(.*)$/i', $_SERVER['QUERY_STRING']) ||
                        preg_match('/(etc(\/|%2f)passwd|self(\/|%2f)environ)/i', $_SERVER['QUERY_STRING']) ||
                        preg_match('/base64_encode.*\(.*\)/i', $_SERVER['QUERY_STRING']) ||
                        preg_match('/base64_(en|de)code[^(]*\([^)]*\)/i', $_SERVER['QUERY_STRING']) ||
                        preg_match('/(localhost|loopback|127\.0\.0\.1)/i', $_SERVER['QUERY_STRING']) ||
                        preg_match('/GLOBALS(=|\[|%[0-9A-Z]{0,2})/i', $_SERVER['QUERY_STRING']) ||
                        preg_match('/_REQUEST(=|\[|%[0-9A-Z]{0,2})/i', $_SERVER['QUERY_STRING']) ||
                        preg_match('/^.*(x00|x04|x08|x0d|x1b|x20|x3c|x3e|x7f).*/i', $_SERVER['QUERY_STRING']) ||
                        preg_match('/(NULL|OUTFILE|LOAD_FILE)/i', $_SERVER['QUERY_STRING']) ||
                        preg_match('/(\.{1,}\/)+(motd|etc|bin)/i', $_SERVER['QUERY_STRING']) ||
                        preg_match('/(localhost|loopback|127\.0\.0\.1)/i', $_SERVER['QUERY_STRING']) ||
                        preg_match('/((\+|%2b)(concat|delete|get|select|union)(\+|%2b))/i', $_SERVER['QUERY_STRING']) ||
                        preg_match('/(concat|eval)(.*)(\(|%28)/i', $_SERVER['QUERY_STRING']) ||
                        preg_match('/-[sdcr].*(allow_url_include|allow_url_fopen|safe_mode|disable_functions|auto_prepend_file)/i', $_SERVER['QUERY_STRING']) ||
                        preg_match('/sp_executesql/i', $_SERVER['QUERY_STRING'])) {
                        //blocked by the firewall
                        $this->firewallBlock('6G Firewall');
                    }

                    if (!HMWP_Classes_Tools::isPluginActive('backup-guard-gold/backup-guard-pro.php') && !HMWP_Classes_Tools::isPluginActive('wp-reset/wp-reset.php') && !HMWP_Classes_Tools::isPluginActive('wp-statistics/wp-statistics.php')) {

                        if (preg_match('/(<|%3C).*script.*(>|%3E)/i', $_SERVER['QUERY_STRING']) || 
                        preg_match('/(<|%3C)([^s]*s)+cript.*(>|%3E)/i', $_SERVER['QUERY_STRING']) ||
                        preg_match('/(<|%3C).*embed.*(>|%3E)/i', $_SERVER['QUERY_STRING']) ||
                        preg_match('/(<|%3C)([^e]*e)+mbed.*(>|%3E)/i', $_SERVER['QUERY_STRING']) ||
                        preg_match('/(<|%3C).*object.*(>|%3E)/i', $_SERVER['QUERY_STRING']) ||
                        preg_match('/(<|%3C)([^o]*o)+bject.*(>|%3E)/i', $_SERVER['QUERY_STRING']) ||
                        preg_match('/(<|%3C).*iframe.*(>|%3E)/i', $_SERVER['QUERY_STRING']) ||
                        preg_match('/(<|%3C)([^i]*i)+frame.*(>|%3E)/i', $_SERVER['QUERY_STRING']) ||
                        preg_match('/^.*(\(|\)|<|>|%3c|%3e).*/i', $_SERVER['QUERY_STRING']) ||
                        preg_match('/(<|>|\'|%0A|%0D|%3C|%3E|%00)/i', $_SERVER['QUERY_STRING']) ||
                        preg_match('/(;|<|>|\'|"|\)|%0A|%0D|%22|%27|%3C|%3E|%00).*(\/\*|union|select|insert|drop|delete|cast|create|char|convert|alter|declare|script|set|md5|benchmark|encode)/i', $_SERVER['QUERY_STRING'])) {
                            //blocked by the firewall
                            $this->firewallBlock('6G Firewall');
                        }

                    }

                }

            }

            if ((int)HMWP_Classes_Tools::getOption('hmwp_sqlinjection_level') == 3) {

                if (isset($_SERVER['HTTP_USER_AGENT']) && $_SERVER['HTTP_USER_AGENT'] <> '') {
                    if (preg_match('/([a-z0-9]{2000,})/i', $_SERVER['HTTP_USER_AGENT']) || 
                        preg_match('/(&lt;|%0a|%0d|%27|%3c|%3e|%00|0x00)/i', $_SERVER['HTTP_USER_AGENT']) ||
                        preg_match('/(base64_decode|bin\/bash|disconnect|eval|lwp-download|unserialize)/i', $_SERVER['HTTP_USER_AGENT'])) {
                        //blocked by the firewall
                        $this->firewallBlock('7G Firewall');
                    }
                }

                if (isset($_SERVER['QUERY_STRING']) && $_SERVER['QUERY_STRING'] <> '') {
                    if (preg_match('/([a-z0-9]{2000,})/i', $_SERVER['QUERY_STRING']) || 
                        preg_match('/(\/|%2f)(:|%3a)(\/|%2f)/i', $_SERVER['QUERY_STRING']) ||
                        preg_match('/(order(\s|%20)by(\s|%20)1--)/i', $_SERVER['QUERY_STRING']) ||
                        preg_match('/(\/|%2f)(\*|%2a)(\*|%2a)(\/|%2f)/i', $_SERVER['QUERY_STRING']) ||
                        preg_match('/(ckfinder|fckeditor|fullclick)/i', $_SERVER['QUERY_STRING']) ||
                        preg_match('/(`|<|>|\^|\|\\|0x00|%00|%0d%0a)/i', $_SERVER['QUERY_STRING']) ||
                        preg_match('/((.*)header:|(.*)set-cookie:(.*)=)/i', $_SERVER['QUERY_STRING']) ||
                        preg_match('/(localhost|127(\.|%2e)0(\.|%2e)0(\.|%2e)1)/i', $_SERVER['QUERY_STRING']) ||
                        preg_match('/(cmd|command)(=|%3d)(chdir|mkdir)(.*)(x20)/i', $_SERVER['QUERY_STRING']) ||
                        preg_match('/(globals|mosconfig([a-z_]{1,22})|request)(=|\[)/i', $_SERVER['QUERY_STRING']) ||
                        preg_match('/(\/|%2f)((wp-)?config)((\.|%2e)inc)?((\.|%2e)php)/i', $_SERVER['QUERY_STRING']) ||
                        preg_match('/(thumbs?(_editor|open)?|tim(thumbs?)?)((\.|%2e)php)/i', $_SERVER['QUERY_STRING']) ||
                        preg_match('/(absolute_|base|root_)(dir|path)(=|%3d)(ftp|https?)/i', $_SERVER['QUERY_STRING']) ||
                        preg_match('/(s)?(ftp|inurl|php)(s)?(:(%2f|%u2215)(%2f|%u2215))/i', $_SERVER['QUERY_STRING']) ||
                        preg_match('/(\.|20)(get|the)(_)(permalink|posts_page_url)(\(|%28)/i', $_SERVER['QUERY_STRING']) ||
                        preg_match('/((boot|win)((\.|%2e)ini)|etc(\/|%2f)passwd|self(\/|%2f)environ)/i', $_SERVER['QUERY_STRING']) ||
                        preg_match('/(((\/|%2f){3,3})|((\.|%2e){3,3})|((\.|%2e){2,2})(\/|%2f|%u2215))/i', $_SERVER['QUERY_STRING']) ||
                        preg_match('/(benchmark|char|exec|fopen|function|html)(.*)(\(|%28)(.*)(\)|%29)/i', $_SERVER['QUERY_STRING']) ||
                        preg_match('/(php)([0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12})/i', $_SERVER['QUERY_STRING']) ||
                        preg_match('/(e|%65|%45)(v|%76|%56)(a|%61|%31)(l|%6c|%4c)(.*)(\(|%28)(.*)(\)|%29)/i', $_SERVER['QUERY_STRING']) ||
                        preg_match('/(\/|%2f)(=|%3d|$&|_mm|inurl(:|%3a)(\/|%2f)|(mod|path)(=|%3d)(\.|%2e))/i', $_SERVER['QUERY_STRING']) ||
                        preg_match('/(<|%3c)(.*)(e|%65|%45)(m|%6d|%4d)(b|%62|%42)(e|%65|%45)(d|%64|%44)(.*)(>|%3e)/i', $_SERVER['QUERY_STRING']) ||
                        preg_match('/(<|%3c)(.*)(i|%69|%49)(f|%66|%46)(r|%72|%52)(a|%61|%41)(m|%6d|%4d)(e|%65|%45)(.*)(>|%3e)/i', $_SERVER['QUERY_STRING']) ||
                        preg_match('/(<|%3c)(.*)(o|%4f|%6f)(b|%62|%42)(j|%4a|%6a)(e|%65|%45)(c|%63|%43)(t|%74|%54)(.*)(>|%3e)/i', $_SERVER['QUERY_STRING']) ||
                        preg_match('/(<|%3c)(.*)(s|%73|%53)(c|%63|%43)(r|%72|%52)(i|%69|%49)(p|%70|%50)(t|%74|%54)(.*)(>|%3e)/i', $_SERVER['QUERY_STRING']) ||
                        preg_match('/(\+|%2b|%20)(d|%64|%44)(e|%65|%45)(l|%6c|%4c)(e|%65|%45)(t|%74|%54)(e|%65|%45)(\+|%2b|%20)/i', $_SERVER['QUERY_STRING']) ||
                        preg_match('/(\+|%2b|%20)(i|%69|%49)(n|%6e|%4e)(s|%73|%53)(e|%65|%45)(r|%72|%52)(t|%74|%54)(\+|%2b|%20)/i', $_SERVER['QUERY_STRING']) ||
                        preg_match('/(\+|%2b|%20)(s|%73|%53)(e|%65|%45)(l|%6c|%4c)(e|%65|%45)(c|%63|%43)(t|%74|%54)(\+|%2b|%20)/i', $_SERVER['QUERY_STRING']) ||
                        preg_match('/(\+|%2b|%20)(u|%75|%55)(p|%70|%50)(d|%64|%44)(a|%61|%41)(t|%74|%54)(e|%65|%45)(\+|%2b|%20)/i', $_SERVER['QUERY_STRING']) ||
                        preg_match('/(\\x00|(\"|%22|\'|%27)?0(\"|%22|\'|%27)?(=|%3d)(\"|%22|\'|%27)?0|cast(\(|%28)0x|or%201(=|%3d)1)/i', $_SERVER['QUERY_STRING']) ||
                        preg_match('/(g|%67|%47)(l|%6c|%4c)(o|%6f|%4f)(b|%62|%42)(a|%61|%41)(l|%6c|%4c)(s|%73|%53)(=|\[|%[0-9A-Z]{0,2})/i', $_SERVER['QUERY_STRING']) ||
                        preg_match('/(_|%5f)(r|%72|%52)(e|%65|%45)(q|%71|%51)(u|%75|%55)(e|%65|%45)(s|%73|%53)(t|%74|%54)(=|\[|%[0-9A-Z]{2,})/i', $_SERVER['QUERY_STRING']) ||
                        preg_match('/(j|%6a|%4a)(a|%61|%41)(v|%76|%56)(a|%61|%31)(s|%73|%53)(c|%63|%43)(r|%72|%52)(i|%69|%49)(p|%70|%50)(t|%74|%54)(:|%3a)(.*)(;|%3b|\)|%29)/i', $_SERVER['QUERY_STRING']) ||
                        preg_match('/(b|%62|%42)(a|%61|%41)(s|%73|%53)(e|%65|%45)(6|%36)(4|%34)(_|%5f)(e|%65|%45|d|%64|%44)(e|%65|%45|n|%6e|%4e)(c|%63|%43)(o|%6f|%4f)(d|%64|%44)(e|%65|%45)(.*)(\()(.*)(\))/i', $_SERVER['QUERY_STRING']) ||
                        preg_match('/(@copy|\$_(files|get|post)|allow_url_(fopen|include)|auto_prepend_file|blexbot|browsersploit|(c99|php)shell|curl(_exec|test)|disable_functions?|document_root|elastix|encodeuricom|exploit|fclose|fgets|file_put_contents|fputs|fsbuff|fsockopen|gethostbyname|grablogin|hmei7|input_file|open_basedir|outfile|passthru|phpinfo|popen|proc_open|quickbrute|remoteview|root_path|safe_mode|shell_exec|site((.){0,2})copier|sux0r|trojan|user_func_array|wget|xertive)/i', $_SERVER['QUERY_STRING']) ||
                        preg_match('/(;|<|>|\'|\"|\)|%0a|%0d|%22|%27|%3c|%3e|%00)(.*)(\/\*|alter|base64|benchmark|cast|concat|convert|create|encode|declare|delete|drop|insert|md5|request|script|select|set|union|update)/i', $_SERVER['QUERY_STRING']) ||
                        preg_match('/((\+|%2b)(concat|delete|get|select|union)(\+|%2b))/i', $_SERVER['QUERY_STRING']) ||
                        preg_match('/(union)(.*)(select)(.*)(\(|%28)/i', $_SERVER['QUERY_STRING']) ||
                        preg_match('/(concat|eval)(.*)(\(|%28)/i', $_SERVER['QUERY_STRING'])) {
                        //blocked by the firewall
                        $this->firewallBlock('7G Firewall');
                    }
                }

                if (isset($_SERVER['REQUEST_URI']) && $_SERVER['REQUEST_URI'] <> '') {

                    if (preg_match('/(\^|`|<|>|\\|\|)/i', $_SERVER['REQUEST_URI']) || 
                        preg_match('/([a-z0-9]{2000,})/i', $_SERVER['REQUEST_URI']) ||
                        preg_match('/(\/)(\*|\"|\'|\.|,|&|&amp;?)\/?$/i', $_SERVER['REQUEST_URI']) ||
                        preg_match('/(\/)(vbulletin|boards|vbforum)(\/)?/i', $_SERVER['REQUEST_URI']) ||
                        preg_match('/\/((.*)header:|(.*)set-cookie:(.*)=)/i', $_SERVER['REQUEST_URI']) ||
                        preg_match('/(\/)(ckfinder|fck|fckeditor|fullclick)/i', $_SERVER['REQUEST_URI']) ||
                        preg_match('/(\.(s?ftp-?)config|(s?ftp-?)config\.)/i', $_SERVER['REQUEST_URI']) ||
                        preg_match('/(\{0\}|\"?0\"?=\"?0|\(\/\(|\.\.\.|\+\+\+|\\\")/i', $_SERVER['REQUEST_URI']) ||
                        preg_match('/(thumbs?(_editor|open)?|tim(thumbs?)?)(\.php)/i', $_SERVER['REQUEST_URI']) ||
                        preg_match('/(\.|20)(get|the)(_)(permalink|posts_page_url)(\()/i', $_SERVER['REQUEST_URI']) ||
                        preg_match('/(\/\/\/|\?\?|\/&&|\/\*(.*)\*\/|\/:\/|\\\\|0x00|%00|%0d%0a)/i', $_SERVER['REQUEST_URI']) ||
                        preg_match('/(\/%7e)(root|ftp|bin|nobody|named|guest|logs|sshd)(\/)/i', $_SERVER['REQUEST_URI']) ||
                        preg_match('/(\/)(etc|var)(\/)(hidden|secret|shadow|ninja|passwd|tmp)(\/)?$/i', $_SERVER['REQUEST_URI']) ||
                        preg_match('/(s)?(ftp|http|inurl|php)(s)?(:(\/|%2f|%u2215)(\/|%2f|%u2215))/i', $_SERVER['REQUEST_URI']) ||
                        preg_match('/(\/)(=|\$&?|&?(pws|rk)=0|_mm|_vti_|(=|\/|;|,)nt\.)/i', $_SERVER['REQUEST_URI']) ||
                        preg_match('/(\.)(ds_store|htaccess|htpasswd|init?|mysql-select-db)(\/)?$/i', $_SERVER['REQUEST_URI']) ||
                        preg_match('/(\/)(bin)(\/)(cc|chmod|chsh|cpp|echo|id|kill|mail|nasm|perl|ping|ps|python|tclsh)(\/)?$/i', $_SERVER['REQUEST_URI']) ||
                        preg_match('/(\/)(::[0-9999]|%3a%3a[0-9999]|127\.0\.0\.1|localhost|makefile|pingserver|wwwroot)(\/)?/i', $_SERVER['REQUEST_URI']) ||
                        preg_match('/(\(null\)|\{\$itemURL\}|cAsT\(0x|echo(.*)kae|etc\/passwd|eval\(|self\/environ|\+union\+all\+select)/i', $_SERVER['REQUEST_URI']) ||
                        preg_match('/(\/)?j((\s)+)?a((\s)+)?v((\s)+)?a((\s)+)?s((\s)+)?c((\s)+)?r((\s)+)?i((\s)+)?p((\s)+)?t((\s)+)?(%3a|:)/i', $_SERVER['REQUEST_URI']) ||
                        preg_match('/(\/)(awstats|(c99|php|web)shell|document_root|error_log|listinfo|muieblack|remoteview|site((.){0,2})copier|sqlpatch|sux0r)/i', $_SERVER['REQUEST_URI']) ||
                        preg_match('/(\/)((php|web)?shell|crossdomain|fileditor|locus7|nstview|php(get|remoteview|writer)|r57|remview|sshphp|storm7|webadmin)(.*)(\.|\()/i', $_SERVER['REQUEST_URI']) ||
                        preg_match('/(\/)(author-panel|class|database|(db|mysql)-?admin|filemanager|htdocs|httpdocs|https?|mailman|mailto|msoffice|_?php-my-admin(.*)|tmp|undefined|usage|var|vhosts|webmaster|www)(\/)/i', $_SERVER['REQUEST_URI']) ||
                        preg_match('/(base64_(en|de)code|benchmark|child_terminate|curl_exec|e?chr|eval|function|fwrite|(f|p)open|html|leak|passthru|p?fsockopen|phpinfo|posix_(kill|mkfifo|setpgid|setsid|setuid)|proc_(close|get_status|nice|open|terminate)|(shell_)?exec|system)(.*)(\()(.*)(\))/i', $_SERVER['REQUEST_URI']) ||
                        preg_match('/(\/)(^$|00.temp00|0day|3index|3xp|70bex?|admin_events|bkht|(php|web)?shell|c99|config(\.)?bak|curltest|db|dompdf|filenetworks|hmei7|index\.php\/index\.php\/index|jahat|kcrew|keywordspy|libsoft|marg|mobiquo|mysql|nessus|php-?info|racrew|sql|vuln|(web-?|wp-)?(conf\b|config(uration)?)|xertive)(\.php)/i', $_SERVER['REQUEST_URI']) ||
                        preg_match('/(\.)(ab4|ace|afm|ashx|aspx?|bash|ba?k?|bin|bz2|cfg|cfml?|conf\b|config|ctl|dat|db|dist|eml|engine|env|et2|fec|fla|hg|inc|inv|jsp|lqd|make|mbf|mdb|mmw|mny|module|old|one|orig|out|passwd|pdbprofile|psd|pst|ptdb|pwd|py|qbb|qdf|rdf|save|sdb|sh|soa|svn|swl|swo|swp|stx|tax|tgz|theme|tls|tmd|wow|xtmpl|ya?ml)$/i', $_SERVER['REQUEST_URI'])

                    ) {
                        //blocked by the firewall
                        $this->firewallBlock('7G Firewall');
                    }
                }

            }

            if ((int)HMWP_Classes_Tools::getOption('hmwp_sqlinjection_level') == 4) {

                if (isset($_SERVER['HTTP_USER_AGENT']) && $_SERVER['HTTP_USER_AGENT'] <> '') {
                    if (preg_match('/([a-z0-9]{2000,})/i', $_SERVER['HTTP_USER_AGENT']) || 
                        preg_match('/(&lt;|%0a|%0d|%27|%3c|%3e|%00|0x00)/i', $_SERVER['HTTP_USER_AGENT']) ||
                        preg_match('/(base64_decode|bin\/bash|disconnect|eval|lwp-download|unserialize)/i', $_SERVER['HTTP_USER_AGENT'])) {
                        //blocked by the firewall
                        $this->firewallBlock('8G Firewall');
                    }
                }

                if (isset($_SERVER['QUERY_STRING']) && $_SERVER['QUERY_STRING'] <> '') {
                    if (preg_match('/^(%2d|-)[^=]+$/i', $_SERVER['QUERY_STRING']) || 
                        preg_match('/([a-z0-9]{4000,})/i', $_SERVER['QUERY_STRING']) ||
                        preg_match('/(\/|%2f)(:|%3a)(\/|%2f)/i', $_SERVER['QUERY_STRING']) ||
                        preg_match('/(etc\/(hosts|motd|shadow))/i', $_SERVER['QUERY_STRING']) ||
                        preg_match('/(order(\s|%20)by(\s|%20)1--)/i', $_SERVER['QUERY_STRING']) ||
                        preg_match('/(\/|%2f)(\*|%2a)(\*|%2a)(\/|%2f)/i', $_SERVER['QUERY_STRING']) ||
                        preg_match('/(`|<|>|\^|\|\\|0x00|%00|%0d%0a)/i', $_SERVER['QUERY_STRING']) ||
                        preg_match('/(f?ckfinder|f?ckeditor|fullclick)/i', $_SERVER['QUERY_STRING']) ||
                        preg_match('/((.*)header:|(.*)set-cookie:(.*)=)/i', $_SERVER['QUERY_STRING']) ||
                        preg_match('/(localhost|127(\.|%2e)0(\.|%2e)0(\.|%2e)1)/i', $_SERVER['QUERY_STRING']) ||
                        preg_match('/(cmd|command)(=|%3d)(chdir|mkdir)(.*)(x20)/i', $_SERVER['QUERY_STRING']) ||
                        preg_match('/(globals|mosconfig([a-z_]{1,22})|request)(=|\[)/i', $_SERVER['QUERY_STRING']) ||
                        preg_match('/(\/|%2f)((wp-)?config)((\.|%2e)inc)?((\.|%2e)php)/i', $_SERVER['QUERY_STRING']) ||
                        preg_match('/(thumbs?(_editor|open)?|tim(thumbs?)?)((\.|%2e)php)/i', $_SERVER['QUERY_STRING']) ||
                        preg_match('/(absolute_|base|root_)(dir|path)(=|%3d)(ftp|https?)/i', $_SERVER['QUERY_STRING']) ||
                        preg_match('/(s)?(ftp|inurl|php)(s)?(:(\/|%2f|%u2215)(\/|%2f|%u2215))/i', $_SERVER['QUERY_STRING']) ||
                        preg_match('/(\.|20)(get|the)(_|%5f)(permalink|posts_page_url)(\(|%28)/i', $_SERVER['QUERY_STRING']) ||
                        preg_match('/((boot|win)((\.|%2e)ini)|etc(\/|%2f)passwd|self(\/|%2f)environ)/i', $_SERVER['QUERY_STRING']) ||
                        preg_match('/(((\/|%2f){3,3})|((\.|%2e){3,3})|((\.|%2e){2,2})(\/|%2f|%u2215))/i', $_SERVER['QUERY_STRING']) ||
                        preg_match('/(benchmark|char|exec|fopen|function|html)(.*)(\(|%28)(.*)(\)|%29)/i', $_SERVER['QUERY_STRING']) ||
                        preg_match('/(php)([0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12})/i', $_SERVER['QUERY_STRING']) ||
                        preg_match('/(e|%65|%45)(v|%76|%56)(a|%61|%31)(l|%6c|%4c)(.*)(\(|%28)(.*)(\)|%29)/i', $_SERVER['QUERY_STRING']) ||
                        preg_match('/(\/|%2f)(=|%3d|\$&|_mm|inurl(:|%3a)(\/|%2f)|(mod|path)(=|%3d)(\.|%2e))/i', $_SERVER['QUERY_STRING']) ||
                        preg_match('/(<|%3c)(.*)(e|%65|%45)(m|%6d|%4d)(b|%62|%42)(e|%65|%45)(d|%64|%44)(.*)(>|%3e)/i', $_SERVER['QUERY_STRING']) ||
                        preg_match('/(<|%3c)(.*)(i|%69|%49)(f|%66|%46)(r|%72|%52)(a|%61|%41)(m|%6d|%4d)(e|%65|%45)(.*)(>|%3e)/i', $_SERVER['QUERY_STRING']) ||
                        preg_match('/(<|%3c)(.*)(o|%4f|%6f)(b|%62|%42)(j|%4a|%6a)(e|%65|%45)(c|%63|%43)(t|%74|%54)(.*)(>|%3e)/i', $_SERVER['QUERY_STRING']) ||
                        preg_match('/(<|%3c)(.*)(s|%73|%53)(c|%63|%43)(r|%72|%52)(i|%69|%49)(p|%70|%50)(t|%74|%54)(.*)(>|%3e)/i', $_SERVER['QUERY_STRING']) ||
                        preg_match('/(\+|%2b|%20)(d|%64|%44)(e|%65|%45)(l|%6c|%4c)(e|%65|%45)(t|%74|%54)(e|%65|%45)(\+|%2b|%20)/i', $_SERVER['QUERY_STRING']) ||
                        preg_match('/(\+|%2b|%20)(i|%69|%49)(n|%6e|%4e)(s|%73|%53)(e|%65|%45)(r|%72|%52)(t|%74|%54)(\+|%2b|%20)/i', $_SERVER['QUERY_STRING']) ||
                        preg_match('/(\+|%2b|%20)(s|%73|%53)(e|%65|%45)(l|%6c|%4c)(e|%65|%45)(c|%63|%43)(t|%74|%54)(\+|%2b|%20)/i', $_SERVER['QUERY_STRING']) ||
                        preg_match('/(\+|%2b|%20)(u|%75|%55)(p|%70|%50)(d|%64|%44)(a|%61|%41)(t|%74|%54)(e|%65|%45)(\+|%2b|%20)/i', $_SERVER['QUERY_STRING']) ||
                        preg_match('/(\\x00|(\"|%22|\'|%27)?0(\"|%22|\'|%27)?(=|%3d)(\"|%22|\'|%27)?0|cast(\(|%28)0x|or%201(=|%3d)1)/i', $_SERVER['QUERY_STRING']) ||
                        preg_match('/(g|%67|%47)(l|%6c|%4c)(o|%6f|%4f)(b|%62|%42)(a|%61|%41)(l|%6c|%4c)(s|%73|%53)(=|\[|%[0-9A-Z]{0,2})/i', $_SERVER['QUERY_STRING']) ||
                        preg_match('/(_|%5f)(r|%72|%52)(e|%65|%45)(q|%71|%51)(u|%75|%55)(e|%65|%45)(s|%73|%53)(t|%74|%54)(=|\[|%[0-9A-Z]{2,})/i', $_SERVER['QUERY_STRING']) ||
                        preg_match('/(j|%6a|%4a)(a|%61|%41)(v|%76|%56)(a|%61|%31)(s|%73|%53)(c|%63|%43)(r|%72|%52)(i|%69|%49)(p|%70|%50)(t|%74|%54)(:|%3a)(.*)(;|%3b|\)|%29)/i', $_SERVER['QUERY_STRING']) ||
                        preg_match('/(b|%62|%42)(a|%61|%41)(s|%73|%53)(e|%65|%45)(6|%36)(4|%34)(_|%5f)(e|%65|%45|d|%64|%44)(e|%65|%45|n|%6e|%4e)(c|%63|%43)(o|%6f|%4f)(d|%64|%44)(e|%65|%45)(.*)(\()(.*)(\))/i', $_SERVER['QUERY_STRING']) ||
                        preg_match('/(@copy|\$_(files|get|post)|allow_url_(fopen|include)|auto_prepend_file|blexbot|browsersploit|call_user_func_array|(php|web)shell|curl(_exec|test)|disable_functions?|document_root)/i', $_SERVER['QUERY_STRING']) ||
                        preg_match('/(elastix|encodeuricom|exploit|fclose|fgets|file_put_contents|fputs|fsbuff|fsockopen|gethostbyname|hmei7|hubs_post-cta|input_file|invokefunction|(\b)load_file|open_basedir|outfile|p3dlite)/i', $_SERVER['QUERY_STRING']) ||
                        preg_match('/(pass(=|%3d)shell|passthru|phpinfo|phpshells|popen|proc_open|quickbrute|remoteview|root_path|shell_exec|site((.){0,2})copier|sp_executesql|sux0r|trojan|udtudt|user_func_array|wget|wp_insert_user|xertive)/i', $_SERVER['QUERY_STRING']) ||
                        preg_match('/(;|<|>|\'|\"|\)|%0a|%0d|%22|%27|%3c|%3e|%00)(.*)(\/\*|alter|base64|benchmark|cast|concat|convert|create|encode|declare|delay|delete|drop|hex|insert|load|md5|null|replace|request|script|select|set|sleep|truncate|unhex|update)/i', $_SERVER['QUERY_STRING']) ||
                        preg_match('/((\+|%2b)(concat|delete|get|select|union)(\+|%2b))/i', $_SERVER['QUERY_STRING']) ||
                        preg_match('/(union)(.*)(select)(.*)(\(|%28)/i', $_SERVER['QUERY_STRING']) ||
                        preg_match('/(concat|eval)(.*)(\(|%28)/i', $_SERVER['QUERY_STRING'])) {
                        //blocked by the firewall
                        $this->firewallBlock('8G Firewall');
                    }
                }

                if (isset($_SERVER['REQUEST_URI']) && $_SERVER['REQUEST_URI'] <> '') {

                    if (preg_match('/(,,,)/i', $_SERVER['REQUEST_URI']) || 
                        preg_match('/(-------)/i', $_SERVER['REQUEST_URI']) ||
                        preg_match('/(\^|`|<|>|\\|\|)/i', $_SERVER['REQUEST_URI']) ||
                        preg_match('/([a-z0-9]{2000,})/i', $_SERVER['REQUEST_URI']) ||
                        preg_match('/(=?\(\'|%27\)\/?)(\.)/i', $_SERVER['REQUEST_URI']) ||
                        preg_match('/(\/)(\*|\"|\'|\.|,|&|&amp;?)(\/)?$/i', $_SERVER['REQUEST_URI']) ||
                        preg_match('/(\.)(php)(\()?([0-9]+)(\))?(\/)?$/i', $_SERVER['REQUEST_URI']) ||
                        preg_match('/(\/)((.*)header:|(.*)set-cookie:(.*)=)/i', $_SERVER['REQUEST_URI']) ||
                        preg_match('/(\.(s?ftp-?)config|(s?ftp-?)config\.)/i', $_SERVER['REQUEST_URI']) ||
                        preg_match('/(\/)(f?ckfinder|fck\/|f?ckeditor|fullclick)/i', $_SERVER['REQUEST_URI']) ||
                        preg_match('/(\/)((force-)?download|framework\/main)(\.php)/i', $_SERVER['REQUEST_URI']) ||
                        preg_match('/(\{0\}|\"?0\"?=\"?0|\(\/\(|\.\.\.|\+\+\+|\\\")/i', $_SERVER['REQUEST_URI']) ||
                        preg_match('/(\/)(vbull(etin)?|boards|vbforum|vbweb|webvb)(\/)?/i', $_SERVER['REQUEST_URI']) ||
                        preg_match('/(\.|20)(get|the)(_)(permalink|posts_page_url)(\()/i', $_SERVER['REQUEST_URI']) ||
                        preg_match('/(\/\/\/|\?\?|\/&&|\/\*(.*)\*\/|\/:\/|\\\\|0x00|%00|%0d%0a)/i', $_SERVER['REQUEST_URI']) ||
                        preg_match('/(\/)(cgi_?)?alfa(_?cgiapi|_?data|_?v[0-9]+)?(\.php)/i', $_SERVER['REQUEST_URI']) ||
                        preg_match('/(thumbs?(_editor|open)?|tim(thumbs?)?)((\.|%2e)php)/i', $_SERVER['REQUEST_URI']) ||
                        preg_match('/(\/)((boot)?_?admin(er|istrator|s)(_events)?)(\.php)/i', $_SERVER['REQUEST_URI']) ||
                        preg_match('/(\/%7e)(root|ftp|bin|nobody|named|guest|logs|sshd)\//i', $_SERVER['REQUEST_URI']) ||
                        preg_match('/(\/)(\.?mad|alpha|c99|php|web)?sh(3|e)ll([0-9]+|\w)(\.php)/i', $_SERVER['REQUEST_URI']) ||
                        preg_match('/(\/)(admin-?|file-?)(upload)(bg|_?file|ify|svu|ye)?(\.php)/i', $_SERVER['REQUEST_URI']) ||
                        preg_match('/(\/)(etc|var)(\/)(hidden|secret|shadow|ninja|passwd|tmp)(\/)?$/i', $_SERVER['REQUEST_URI']) ||
                        preg_match('/(s)?(ftp|http|inurl|php)(s)?(:(\/|%2f|%u2215)(\/|%2f|%u2215))/i', $_SERVER['REQUEST_URI']) ||
                        preg_match('/(\/)(=|\$&?|&?(pws|rk)=0|_mm|_vti_|(=|\/|;|,)nt\.)/i', $_SERVER['REQUEST_URI']) ||
                        preg_match('/(\.)(ds_store|htaccess|htpasswd|init?|mysql-select-db)(\/)?$/i', $_SERVER['REQUEST_URI']) ||
                        preg_match('/(\/)(bin)(\/)(cc|chmod|chsh|cpp|echo|id|kill|mail|nasm|perl|ping|ps|python|tclsh)(\/)?$/i', $_SERVER['REQUEST_URI']) ||
                        preg_match('/(\/)(::[0-9999]|%3a%3a[0-9999]|127\.0\.0\.1|ccx|localhost|makefile|pingserver|wwwroot)(\/)?/i', $_SERVER['REQUEST_URI']) ||
                        preg_match('/^(\/)(123|backup|bak|beta|bkp|default|demo|dev(new|old)?|home|new-?site|null|old|old_files|old1)(\/)?$/i', $_SERVER['REQUEST_URI']) ||
                        preg_match('/(\/)?j((\s)+)?a((\s)+)?v((\s)+)?a((\s)+)?s((\s)+)?c((\s)+)?r((\s)+)?i((\s)+)?p((\s)+)?t((\s)+)?(%3a|:)/i', $_SERVER['REQUEST_URI']) ||
                        preg_match('/^(\/)(old-?site(back)?|old(web)?site(here)?|sites?|staging|undefined)(\/)?$/i', $_SERVER['REQUEST_URI']) ||
                        preg_match('/(\/)(filemanager|htdocs|httpdocs|https?|mailman|mailto|msoffice|undefined|usage|var|vhosts|webmaster|www)(\/)/i', $_SERVER['REQUEST_URI']) ||
                        preg_match('/(\(null\)|\{\$itemURL\}|cast\(0x|echo(.*)kae|etc\/passwd|eval\(|null(.*)null|open_basedir|self\/environ|\+union\+all\+select)/i', $_SERVER['REQUEST_URI']) ||
                        preg_match('/(\/)(db-?|j-?|my(sql)?-?|setup-?|web-?|wp-?)?(admin-?)?(setup-?)?(conf\b|conf(ig)?)(uration)?(\.?bak|\.inc)?(\.inc|\.old|\.php|\.txt)/i', $_SERVER['REQUEST_URI']) ||
                        preg_match('/(\/)((.*)crlf-?injection|(.*)xss-?protection|__(inc|jsc)|administrator|author-panel|database|downloader|(db|mysql)-?admin)(\/)/i', $_SERVER['REQUEST_URI']) ||
                        preg_match('/(\/)(haders|head|hello|helpear|incahe|includes?|indo(sec)?|infos?|install|ioptimizes?|jmail|js|king|kiss|kodox|kro|legion|libsoft)(\.php)/i', $_SERVER['REQUEST_URI']) ||
                        preg_match('/(\/)(awstats|document_root|dologin\.action|error.log|extension\/ext|htaccess\.|lib\/php|listinfo|phpunit\/php|remoteview|server\/php|www\.root\.)/i', $_SERVER['REQUEST_URI']) ||
                        preg_match('/(base64_(en|de)code|benchmark|curl_exec|e?chr|eval|function|fwrite|(f|p)open|html|leak|passthru|p?fsockopen|phpinfo)(.*)(\(|%28)(.*)(\)|%29)/i', $_SERVER['REQUEST_URI']) ||
                        preg_match('/(posix_(kill|mkfifo|setpgid|setsid|setuid)|(child|proc)_(close|get_status|nice|open|terminate)|(shell_)?exec|system)(.*)(\(|%28)(.*)(\)|%29)/i', $_SERVER['REQUEST_URI']) ||
                        preg_match('/(\/)((c99|php|web)?shell|crossdomain|fileditor|locus7|nstview|php(get|remoteview|writer)|r57|remview|sshphp|storm7|webadmin)(.*)(\.|%2e|\(|%28)/i', $_SERVER['REQUEST_URI']) ||
                        preg_match('/(\/)((wp-)((201\d|202\d|[0-9]{2})|ad|admin(fx|rss|setup)|booking|confirm|crons|data|file|mail|one|plugins?|readindex|reset|setups?|story))(\.php)/i', $_SERVER['REQUEST_URI']) ||
                        preg_match('/(\/)(^$|-|\!|\w|\.(.*)|100|123|([^iI])?ndex|index\.php\/index|3xp|777|7yn|90sec|99|active|aill|ajs\.delivery|al277|alexuse?|ali|allwrite)(\.php)/i', $_SERVER['REQUEST_URI']) ||
                        preg_match('/(\/)(analyser|apache|apikey|apismtp|authenticat(e|ing)|autoload_classmap|backup(_index)?|bakup|bkht|black|bogel|bookmark|bypass|cachee?)(\.php)/i', $_SERVER['REQUEST_URI']) ||
                        preg_match('/(\/)(clean|cm(d|s)|con|connector\.minimal|contexmini|contral|curl(test)?|data(base)?|db|db-cache|db-safe-mode|defau11|defau1t|dompdf|dst)(\.php)/i', $_SERVER['REQUEST_URI']) ||
                        preg_match('/(\/)(elements|emails?|error.log|ecscache|edit-form|eval-stdin|export|evil|fbrrchive|filemga|filenetworks?|f0x|gank(\.php)?|gass|gel|guide)(\.php)/i', $_SERVER['REQUEST_URI']) ||
                        preg_match('/(\/)(logo_img|lufix|mage|marg|mass|mide|moon|mssqli|mybak|myshe|mysql|mytag_js?|nasgor|newfile|news|nf_?tracking|nginx|ngoi|ohayo|old-?index)(\.php)/i', $_SERVER['REQUEST_URI']) ||
                        preg_match('/(\/)(olux|owl|pekok|petx|php-?info|phpping|popup-pomo|priv|r3x|radio|rahma|randominit|readindex|readmy|reads|repair-?bak|root)(\.php)/i', $_SERVER['REQUEST_URI']) ||
                        preg_match('/(\/)(router|savepng|semayan|shell|shootme|sky|socket(c|i|iasrgasf)ontrol|sql(bak|_?dump)?|support|sym403|sys|system_log|test|tmp-?(uploads)?)(\.php)/i', $_SERVER['REQUEST_URI']) ||
                        preg_match('/(\/)(traffic-advice|u2p|udd|ukauka|up__uzegp|up14|upxx?|vega|vip|vu(ln)?(\w)?|webroot|weki|wikindex|wp_logns?|wp_wrong_datlib)(\.php)/i', $_SERVER['REQUEST_URI']) ||
                        preg_match('/(\/)((wp-?)?install(ation)?|wp(3|4|5|6)|wpfootes|wpzip|ws0|wsdl|wso(\w)?|www|(uploads|wp-admin)?xleet(-shell)?|xmlsrpc|xup|xxu|xxx|zibi|zipy)(\.php)/i', $_SERVER['REQUEST_URI']) ||
                        preg_match('/(bkv74|cachedsimilar|core-stab|crgrvnkb|ctivrc|deadcode|deathshop|dkiz|e7xue|eqxafaj90zir|exploits|ffmkpcal|filellli7|(fox|sid)wso|gel4y|goog1es|gvqqpinc)/i', $_SERVER['REQUEST_URI']) ||
                        preg_match('/(@md5|00.temp00|0byte|0d4y|0day|0xor|wso1337|1h6j5|3xp|40dd1d|4price|70bex?|a57bze893|abbrevsprl|abruzi|adminer|aqbmkwwx|archivarix|backdoor|beez5|bgvzc29)/i', $_SERVER['REQUEST_URI']) ||
                        preg_match('/(handler_to_code|hax(0|o)r|hmei7|hnap1|home_url=|ibqyiove|icxbsx|indoxploi|jahat|jijle3|kcrew|keywordspy|laobiao|lock360|longdog|marijuan|mod_(aratic|ariimag))/i', $_SERVER['REQUEST_URI']) ||
                        preg_match('/(mobiquo|muiebl|nessus|osbxamip|phpunit|priv8|qcmpecgy|r3vn330|racrew|raiz0|reportserver|r00t|respectmus|rom2823|roseleif|sh3ll|site((.){0,2})copier|sqlpatch|sux0r)/i', $_SERVER['REQUEST_URI']) ||
                        preg_match('/(sym403|telerik|uddatasql|utchiha|visualfrontend|w0rm|wangdafa|wpyii2|wsoyanzo|x5cv|xattack|xbaner|xertive|xiaolei|xltavrat|xorz|xsamxad|xsvip|xxxs?s?|zabbix|zebda)/i', $_SERVER['REQUEST_URI']) ||
                        preg_match('/(\.)(ab4|ace|afm|alfa|as(h|m)x?|aspx?|aws|axd|bash|ba?k?|bat|bin|bz2|cfg|cfml?|cms|conf\b|config|ctl|dat|db|dist|dll|eml|eng(ine)?|env|et2|fec|fla|git(ignore)?)$/i', $_SERVER['REQUEST_URI']) ||
                        preg_match('/(\.)(hg|idea|inc|index|ini|inv|jar|jspa?|lib|local|log|lqd|make|mbf|mdb|mmw|mny|mod(ule)?|msi|old|one|orig|out|passwd|pdb|php\.(php|suspect(ed)?)|php([^\/])|phtml?|pl|profiles?)$/i', $_SERVER['REQUEST_URI']) ||
                        preg_match('/(\.)(pst|ptdb|production|pwd|py|qbb|qdf|rdf|remote|save|sdb|sh|soa|svn|swf|swl|swo|swp|stx|tax|tgz?|theme|tls|tmb|tmd|wok|wow|xsd|xtmpl|xz|ya?ml|za|zlib)$/i', $_SERVER['REQUEST_URI'])) {
                        //blocked by the firewall
                        $this->firewallBlock('8G Firewall');
                    }
                }

            }
        }

        //check and allow search engine bots
        if($this->isSearchEngineBot()){
            return;
        }

        //If user_agent blocking is activated
        if ($banlist = HMWP_Classes_Tools::getOption('banlist_user_agent')){
            if(!empty($banlist)) {
                //unpack $banlist
                $banlist = json_decode($banlist, true);
                //remove empty data
                $banlist = array_filter($banlist);
            }

            if(!empty($banlist)){
                if(isset($_SERVER['HTTP_USER_AGENT']) && $_SERVER['HTTP_USER_AGENT'] <> ''){

                    //set user agent
                    $user_agent = $_SERVER['HTTP_USER_AGENT'];

                    //check if the current item is in the blocked list
                    foreach ($banlist as $item){
                        if(preg_match('/'.$item.'/', $user_agent)){
                            //blocked by the firewall
                            $this->firewallBlock('Geo Security');
                        }
                    }

                }
            }

        }

        //If referrer blocking is activated
        if ($banlist = HMWP_Classes_Tools::getOption('banlist_referrer')){
            if(!empty($banlist)) {
                //unpack $banlist
                $banlist = json_decode($banlist, true);
                //remove empty data
                $banlist = array_filter($banlist);
            }

            if(!empty($banlist)) {
                if (isset($_SERVER['HTTP_REFERER']) && $_SERVER['HTTP_REFERER'] <> '') {

                    //set user agent
                    $referrer = $_SERVER['HTTP_REFERER'];

                    //check if the current item is in the blocked list
                    foreach ($banlist as $item) {
                        if (preg_match('/' . $item . '/', $referrer)) {
                            //blocked by the firewall
                            $this->firewallBlock('Geo Security');
                        }
                    }

                }
            }
        }

        //If hostname blocking is activated
        if ($banlist = HMWP_Classes_Tools::getOption('banlist_hostname')){
            if(!empty($banlist)) {
                //unpack $banlist
                $banlist = json_decode($banlist, true);
                //remove empty data
                $banlist = array_filter($banlist);
            }

            if(!empty($banlist)) {
                //get caller server ips
                $server = $this->getServerVariableIPs();

                if (!empty($server)) {

                    //for each IP found on the caller
                    foreach ($server as $ip) {
                        //get the hostname from the IP is possible
                        $hostname = $this->getHostname($ip);

                        //check if the current item is in the blocked list
                        foreach ($banlist as $item) {
                            if (preg_match('/' . $item . '/', $hostname)) {
                                //blocked by the firewall
                                $this->firewallBlock('Geo Security');
                            }
                        }

                    }
                }
            }
        }
    }

    /**
     * Check if there are whitelisted IPs for accessing the hidden paths
     *
     * @return void
     * @throws Exception
     */
    public function checkWhitelistIPs(){

        if (!HMWP_Classes_Tools::getValue('hmwp_preview') && isset($_SERVER['REMOTE_ADDR']) && strpos($_SERVER['REMOTE_ADDR'], '.') !== false ) {

            //get caller server ips
            $server = $this->getServerVariableIPs();

            if(isset($server['REMOTE_ADDR'])){

                //get only the remote address for whitelist
                $ip = $server['REMOTE_ADDR'];

                //for each IP found on the caller
                if(HMWP_Classes_Tools::isWhitelistedIP($ip)){
                    $this->whitelistLevel(HMWP_Classes_Tools::getOption('whitelist_level'));
                }
            }

        }
    }

    /**
     * Check if there are whitelisted paths for the current path
     *
     * @return void
     * @throws Exception
     */
    public function checkWhitelistPaths(){

        if(isset($_SERVER["REQUEST_URI"]) && $_SERVER["REQUEST_URI"] <> ''){
            $url = untrailingslashit(strtok($_SERVER["REQUEST_URI"], '?'));

            //check the whitelist URLs
            $whitelist_urls = HMWP_Classes_Tools::getOption('whitelist_urls');
            if (!empty($whitelist_urls)) {
                //unpack whitelist urls
                $whitelist_urls = json_decode($whitelist_urls, true);
                //remove empty data
                $whitelist_urls = array_filter($whitelist_urls);
            }

            if(!empty($whitelist_urls)){
                foreach ($whitelist_urls as $path){
                    if(strpos($path, ',')){
                        $paths = explode(',', $path);

                        foreach ($paths as $spath){
                            if (HMWP_Classes_Tools::searchInString($spath, array($url))) {
                                $this->whitelistLevel(HMWP_Classes_Tools::getOption('whitelist_level'));
                            }
                        }

                    }else{
                        if (HMWP_Classes_Tools::searchInString($path, array($url))) {
                            $this->whitelistLevel(HMWP_Classes_Tools::getOption('whitelist_level'));
                        }
                    }

                }
            }

        }

    }

    /**
     * Check if the IP is in blacklist
     * Include also the theme detectors
     * @return void
     * @throws Exception
     */
    public function checkBlacklistIPs(){

        if (!HMWP_Classes_Tools::getValue('hmwp_preview')) {

            //get caller server ips
            $server = $this->getServerVariableIPs();

            if(!empty($server)){
                //for each IP found on the caller
                foreach ($server as $ip){
                    if(!HMWP_Classes_Tools::isWhitelistedIP($ip) && HMWP_Classes_Tools::isBlacklistedIP($ip)){
                        HMWP_Classes_ObjController::getClass('HMWP_Models_Brute')->brute_kill_login();
                        break;
                    }
                }
            }

        }
    }

    /**
     * Whitelist features based on whitelist level
     *
     * @param $level
     *
     * @return void
     * @throws Exception
     */
    private function whitelistLevel($level) {

        //whitelist_level == 0
        if($level == 0){
            add_filter('hmwp_process_hide_urls', '__return_false');
        }

        //whitelist_level == 1
        if($level > 0){
            add_filter('hmwp_process_hide_urls', '__return_false');
            add_filter('hmwp_process_find_replace', '__return_false');
        }

        //whitelist_level == 2
        if($level > 1){
            add_filter('hmwp_process_init', '__return_false');
            add_filter('hmwp_process_buffer', '__return_false');
            add_filter('hmwp_process_hide_disable', '__return_false');
        }
    }

    /**
     * Get validated IPs from caller server
     * @return array
     */
    public function getServerVariableIPs()
    {
        $variables = array('REMOTE_ADDR', 'HTTP_CF_CONNECTING_IP', 'HTTP_X_REAL_IP', 'HTTP_X_FORWARDED_FOR');
        $ips = array();

        foreach ($variables as $variable) {
            $ip = isset($_SERVER[$variable]) ? $_SERVER[$variable] : false;

            if ($ip && strpos($ip, ',') !== false) {
                $ip = preg_replace('/[\s,]/', '', explode(',', $ip));
                if($clean_ip = $this->getCleanIp($ip)){
                    $ips[$variable] = $clean_ip;
                }
            } else {
                if($clean_ip = $this->getCleanIp($ip)){
                    $ips[$variable] = $clean_ip;
                }
            }
        }

        return $ips;
    }

    /**
     * Return the verified IP
     * @param $ip
     *
     * @return array|bool|mixed|string|string[]|null
     */
    public function getCleanIp($ip) {

        if (!$this->isValidIP($ip)) {
            $ip = preg_replace('/:\d+$/', '', $ip);
        }

        if($this->isValidIP($ip)){
            if (!$this->isIPv6MappedIPv4($ip)) {
                $ip = $this->inetNtop($this->inetPton($ip));
            }

            return $ip;
        }

        return false;

    }

    /**
     * @param $ip
     *
     * @return bool
     */
    private function isIPv6MappedIPv4($ip) {
        return preg_match('/^(?:\:(?:\:0{1,4}){0,4}\:|(?:0{1,4}\:){5})ffff\:\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$/i', $ip) > 0;
    }

    private function inetNtop($ip) {
        if (strlen($ip) == 16 && substr($ip, 0, 12) == "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\xff\xff") {
            $ip = substr($ip, 12, 4);
        }
        return self::isIPv6Support() ? @inet_ntop($ip) : $this->_inetNtop($ip);
    }

    private function _inetNtop($ip) {
        // IPv4
        if (strlen($ip) === 4) {
            return ord($ip[0]) . '.' . ord($ip[1]) . '.' . ord($ip[2]) . '.' . ord($ip[3]);
        }

        // IPv6
        if (strlen($ip) === 16) {

            // IPv4 mapped IPv6
            if (substr($ip, 0, 12) == "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\xff\xff") {
                return "::ffff:" . ord($ip[12]) . '.' . ord($ip[13]) . '.' . ord($ip[14]) . '.' . ord($ip[15]);
            }

            $hex = bin2hex($ip);
            $groups = str_split($hex, 4);
            $in_collapse = false;
            $done_collapse = false;
            foreach ($groups as $index => $group) {
                if ($group == '0000' && !$done_collapse) {
                    if ($in_collapse) {
                        $groups[$index] = '';
                        continue;
                    }
                    $groups[$index] = ':';
                    $in_collapse = true;
                    continue;
                }
                if ($in_collapse) {
                    $done_collapse = true;
                }
                $groups[$index] = ltrim($groups[$index], '0');
                if (strlen($groups[$index]) === 0) {
                    $groups[$index] = '0';
                }
            }
            $ip = join(':', array_filter($groups, 'strlen'));
            $ip = str_replace(':::', '::', $ip);
            return $ip == ':' ? '::' : $ip;
        }

        return false;
    }

    /**
     * Return the packed binary string of an IPv4 or IPv6 address.
     *
     * @param string $ip
     * @return string
     */
    private function inetPton($ip) {
        $pton = str_pad(self::isIPv6Support() ? @inet_pton($ip) : $this->_inetPton($ip), 16,
            "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\xff\xff\x00\x00\x00\x00", STR_PAD_LEFT);

        return $pton;
    }

    private function _inetPton($ip) {
        // IPv4
        if (preg_match('/^(?:\d{1,3}(?:\.|$)){4}/', $ip)) {
            $octets = explode('.', $ip);
            $bin = chr($octets[0]) . chr($octets[1]) . chr($octets[2]) . chr($octets[3]);
            return $bin;
        }

        // IPv6
        if (preg_match('/^((?:[\da-f]{1,4}(?::|)){0,8})(::)?((?:[\da-f]{1,4}(?::|)){0,8})$/i', $ip)) {
            if ($ip === '::') {
                return "\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0";
            }
            $colon_count = substr_count($ip, ':');
            $dbl_colon_pos = strpos($ip, '::');
            if ($dbl_colon_pos !== false) {
                $ip = str_replace('::', str_repeat(':0000',
                        (($dbl_colon_pos === 0 || $dbl_colon_pos === strlen($ip) - 2) ? 9 : 8) - $colon_count) . ':', $ip);
                $ip = trim($ip, ':');
            }

            $ip_groups = explode(':', $ip);
            $ipv6_bin = '';
            foreach ($ip_groups as $ip_group) {
                $ipv6_bin .= pack('H*', str_pad($ip_group, 4, '0', STR_PAD_LEFT));
            }

            return strlen($ipv6_bin) === 16 ? $ipv6_bin : false;
        }

        // IPv4 mapped IPv6
        if (preg_match('/^(?:\:(?:\:0{1,4}){0,4}\:|(?:0{1,4}\:){5})ffff\:(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})$/i', $ip, $matches)) {
            $octets = explode('.', $matches[1]);
            return "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\xff\xff" . chr($octets[0]) . chr($octets[1]) . chr($octets[2]) . chr($octets[3]);
        }

        return false;
    }

    /**
     * Verify PHP was compiled with IPv6 support.
     *
     * @return bool
     */
    private function isIPv6Support() {
        return defined('AF_INET6');
    }

    /**
     * Check and validate IP
     *
     * @param $ip
     *
     * @return bool
     */
    private function isValidIP($ip) {
        return filter_var($ip, FILTER_VALIDATE_IP) !== false;
    }

    /**
     * Get Hostname from IP
     * @param $ip
     *
     * @return array|false|mixed|string
     */
    private function getHostname($ip) {
        $host = false;

        // This function works for IPv4 or IPv6
        if (function_exists('gethostbyaddr')) {
            $host = @gethostbyaddr($ip);
        }

        if (!$host) {
            $ptr = false;
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false) {
                $ptr = implode(".", array_reverse(explode(".", $ip))) . ".in-addr.arpa";
            } else if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false) {
                $ptr = implode(".", array_reverse(str_split(bin2hex($ip)))) . ".ip6.arpa";
            }

            if ($ptr && function_exists('dns_get_record')) {
                $host = @dns_get_record($ptr, DNS_PTR);

                if ($host) {
                    $host = $host[0]['target'];
                }

            }
        }

        return $host;
    }

    /**
     * Check if google bot
     *
     * @return bool
     */
    public static function isSearchEngineBot(){

        if(isset($_SERVER['HTTP_USER_AGENT']) && $_SERVER['HTTP_USER_AGENT'] <> ''){
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
                '@^Google$@',
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

            $moreUserAgent = array(
                '@^.*bingbot/2.0;.*bing.com/bingbot.htm.*@',
                '@^.*AdIdxBot.*@',
                '@^.*DuckDuckGo/.*@',
                '@^.*Baiduspider.*@',
                '@^.*Yahoo! Slurp.*@',
                '@^.*grapeshot.*@',
                '@^.*proximic.*@',
                '@^.*GPTBot.*@',
            );

            $userAgent = $_SERVER['HTTP_USER_AGENT'];

            foreach ($googleUserAgent as $pat) {
                if (preg_match($pat . 'i', $userAgent)) {
                    return true;
                }
            }

            foreach ($yandexUserAgent as $pat) {
                if (preg_match($pat . 'i', $userAgent)) {
                    return true;
                }
            }

            foreach ($moreUserAgent as $pat) {
                if (preg_match($pat . 'i', $userAgent)) {
                    return true;
                }
            }

        }

        return false;
    }

    /**
     * Show the error message on firewall block
     * @return void
     */
    public function firewallBlock($name = '')
    {
        if(!$name){
            $name = HMWP_Classes_Tools::getOption('hmwp_plugin_name');
        }

        if(function_exists('wp_ob_end_flush_all') && function_exists('wp_die')){
            wp_ob_end_flush_all();
            wp_die(
                esc_html__("The process was blocked by the website’s firewall.", 'hide-my-wp'),
                esc_html__('Blocked by' . ' ' . $name, 'hide-my-wp'),
                array('response' => 403)
            );
        }

        header('HTTP/1.1 403 Forbidden');
        exit();
    }

}
