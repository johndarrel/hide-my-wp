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
        //If block detectors is activated
        if (HMWP_Classes_Tools::getOption('hmwp_detectors_block')) {
            if (isset($_SERVER['HTTP_USER_AGENT']) && $_SERVER['HTTP_USER_AGENT'] <> '') {
                if (preg_match('/(wpthemedetector|builtwith|isitwp|wapalyzer|mShots|WhatCMS|gochyu|wpdetector|scanwp)/i', $_SERVER['HTTP_USER_AGENT'])) {
                    //set as fail attempt
                    $this->setFailAttempt();

                    header('HTTP/1.1 403 Forbidden');
                    exit();
                }
            }
        }

        //If firewall is activated
        if (HMWP_Classes_Tools::getOption('hmwp_sqlinjection')) {
            if ((int)HMWP_Classes_Tools::getOption('hmwp_sqlinjection_level') == 1) {

                if (isset($_SERVER['QUERY_STRING']) && $_SERVER['QUERY_STRING'] <> '') {
                    if (preg_match('/([a-z0-9]{2000,})/i', $_SERVER['REQUEST_URI']) || preg_match('/(<|%3C).*object.*(>|%3E)/i', $_SERVER['QUERY_STRING']) || preg_match('/(<|%3C)([^o]*o)+bject.*(>|%3E)/i', $_SERVER['QUERY_STRING']) || preg_match('/(<|%3C).*iframe.*(>|%3E)/i', $_SERVER['QUERY_STRING']) || preg_match('/(<|%3C)([^i]*i)+frame.*(>|%3E)/i', $_SERVER['QUERY_STRING']) || preg_match('/(etc(\/|%2f)passwd|self(\/|%2f)environ)/i', $_SERVER['QUERY_STRING']) || preg_match('/base64_encode.*\(.*\)/i', $_SERVER['QUERY_STRING']) || preg_match('/base64_(en|de)code[^(]*\([^)]*\)/i', $_SERVER['QUERY_STRING']) || preg_match('/(localhost|loopback|127\.0\.0\.1)/i', $_SERVER['QUERY_STRING']) || preg_match('/((\+|%2b)(concat|delete|get|select|union)(\+|%2b))/i', $_SERVER['QUERY_STRING']) || preg_match('/(concat|eval)(.*)(\(|%28)/i', $_SERVER['QUERY_STRING']) || preg_match('/union([^s]*s)+elect/i', $_SERVER['QUERY_STRING']) || preg_match('/union([^a]*a)+ll([^s]*s)+elect/i', $_SERVER['QUERY_STRING'])) {
                        //set as fail attempt
                        $this->setFailAttempt();

                        header('HTTP/1.1 403 Forbidden');
                        exit();
                    }
                }

            }

            if ((int)HMWP_Classes_Tools::getOption('hmwp_sqlinjection_level') == 2) {

                if (isset($_SERVER['HTTP_USER_AGENT']) && $_SERVER['HTTP_USER_AGENT'] <> '') {
                    if (preg_match('/([a-z0-9]{2000,})/i', $_SERVER['HTTP_USER_AGENT']) || preg_match('/(&lt;|%0a|%0d|%27|%3c|%3e|%00|0x00)/i', $_SERVER['HTTP_USER_AGENT']) || preg_match('/(base64_decode|bin\/bash|disconnect|eval|lwp-download|unserialize)/i', $_SERVER['HTTP_USER_AGENT']) || preg_match('/(%0A|%0D|%3C|%3E|%00)/i', $_SERVER['HTTP_USER_AGENT']) || preg_match('/(;|<|>|\'|\"|\)|\(|%0A|%0D|%22|%28|%3C|%3E|%00).*(libwww-perl|wget|python|nikto|curl|scan|java|winhttp|HTTrack|clshttp|archiver|loader|email|harvest|extract|grab|miner)/i', $_SERVER['HTTP_USER_AGENT'])) {
                        //set as fail attempt
                        $this->setFailAttempt();

                        header('HTTP/1.1 403 Forbidden');
                        exit();
                    }
                }

                if (isset($_SERVER['QUERY_STRING']) && $_SERVER['QUERY_STRING'] <> '') {
                    if (preg_match('/[a-zA-Z0-9_]=(http|https):\/\//i', $_SERVER['QUERY_STRING']) || preg_match('/[a-zA-Z0-9_]=(\.\.\/\/?)+/i', $_SERVER['QUERY_STRING']) || preg_match('/[a-zA-Z0-9_]=\/([a-z0-9_.]\/\/?)+/i', $_SERVER['QUERY_STRING']) || preg_match('/=PHP[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}/i', $_SERVER['QUERY_STRING']) || preg_match('/(\.\.\/|%2e%2e%2f|%2e%2e\/|\.\.%2f|%2e\.%2f|%2e\.\/|\.%2e%2f|\.%2e\/)/i', $_SERVER['QUERY_STRING']) || preg_match('/ftp:/i', $_SERVER['QUERY_STRING']) || preg_match('/^(.*)\/self\/(.*)$/i', $_SERVER['QUERY_STRING']) || preg_match('/^(.*)cPath=(http|https):\/\/(.*)$/i', $_SERVER['QUERY_STRING']) || preg_match('/(etc(\/|%2f)passwd|self(\/|%2f)environ)/i', $_SERVER['QUERY_STRING']) || preg_match('/base64_encode.*\(.*\)/i', $_SERVER['QUERY_STRING']) || preg_match('/base64_(en|de)code[^(]*\([^)]*\)/i', $_SERVER['QUERY_STRING']) || preg_match('/(localhost|loopback|127\.0\.0\.1)/i', $_SERVER['QUERY_STRING']) || preg_match('/GLOBALS(=|\[|%[0-9A-Z]{0,2})/i', $_SERVER['QUERY_STRING']) || preg_match('/_REQUEST(=|\[|%[0-9A-Z]{0,2})/i', $_SERVER['QUERY_STRING']) || preg_match('/^.*(x00|x04|x08|x0d|x1b|x20|x3c|x3e|x7f).*/i', $_SERVER['QUERY_STRING']) || preg_match('/(NULL|OUTFILE|LOAD_FILE)/i', $_SERVER['QUERY_STRING']) || preg_match('/(\.{1,}\/)+(motd|etc|bin)/i', $_SERVER['QUERY_STRING']) || preg_match('/(localhost|loopback|127\.0\.0\.1)/i', $_SERVER['QUERY_STRING']) || preg_match('/((\+|%2b)(concat|delete|get|select|union)(\+|%2b))/i', $_SERVER['QUERY_STRING']) || preg_match('/(concat|eval)(.*)(\(|%28)/i', $_SERVER['QUERY_STRING']) || preg_match('/-[sdcr].*(allow_url_include|allow_url_fopen|safe_mode|disable_functions|auto_prepend_file)/i', $_SERVER['QUERY_STRING']) || preg_match('/sp_executesql/i', $_SERVER['QUERY_STRING'])) {
                        //set as fail attempt
                        $this->setFailAttempt();

                        header('HTTP/1.1 403 Forbidden');
                        exit();
                    }

                    if (!HMWP_Classes_Tools::isPluginActive('backup-guard-gold/backup-guard-pro.php') && !HMWP_Classes_Tools::isPluginActive('wp-reset/wp-reset.php') && !HMWP_Classes_Tools::isPluginActive('wp-statistics/wp-statistics.php')) {

                        if (preg_match('/(<|%3C).*script.*(>|%3E)/i', $_SERVER['QUERY_STRING']) || preg_match('/(<|%3C)([^s]*s)+cript.*(>|%3E)/i', $_SERVER['QUERY_STRING']) || preg_match('/(<|%3C).*embed.*(>|%3E)/i', $_SERVER['QUERY_STRING']) || preg_match('/(<|%3C)([^e]*e)+mbed.*(>|%3E)/i', $_SERVER['QUERY_STRING']) || preg_match('/(<|%3C).*object.*(>|%3E)/i', $_SERVER['QUERY_STRING']) || preg_match('/(<|%3C)([^o]*o)+bject.*(>|%3E)/i', $_SERVER['QUERY_STRING']) || preg_match('/(<|%3C).*iframe.*(>|%3E)/i', $_SERVER['QUERY_STRING']) || preg_match('/(<|%3C)([^i]*i)+frame.*(>|%3E)/i', $_SERVER['QUERY_STRING']) || preg_match('/^.*(\(|\)|<|>|%3c|%3e).*/i', $_SERVER['QUERY_STRING']) || preg_match('/(<|>|\'|%0A|%0D|%3C|%3E|%00)/i', $_SERVER['QUERY_STRING']) || preg_match('/(;|<|>|\'|"|\)|%0A|%0D|%22|%27|%3C|%3E|%00).*(\/\*|union|select|insert|drop|delete|cast|create|char|convert|alter|declare|script|set|md5|benchmark|encode)/i', $_SERVER['QUERY_STRING'])) {
                            //set as fail attempt
                            $this->setFailAttempt();

                            header('HTTP/1.1 403 Forbidden');
                            exit();
                        }

                    }

                }

            }

            if ((int)HMWP_Classes_Tools::getOption('hmwp_sqlinjection_level') == 3) {

                if (isset($_SERVER['HTTP_USER_AGENT']) && $_SERVER['HTTP_USER_AGENT'] <> '') {
                    if (preg_match('/([a-z0-9]{2000,})/i', $_SERVER['HTTP_USER_AGENT']) || preg_match('/(&lt;|%0a|%0d|%27|%3c|%3e|%00|0x00)/i', $_SERVER['HTTP_USER_AGENT']) || preg_match('/(base64_decode|bin\/bash|disconnect|eval|lwp-download|unserialize)/i', $_SERVER['HTTP_USER_AGENT'])) {
                        //set as fail attempt
                        $this->setFailAttempt();

                        header('HTTP/1.1 403 Forbidden');
                        exit();
                    }
                }

                if (isset($_SERVER['QUERY_STRING']) && $_SERVER['QUERY_STRING'] <> '') {
                    if (preg_match('/([a-z0-9]{2000,})/i', $_SERVER['QUERY_STRING']) || preg_match('/(\/|%2f)(:|%3a)(\/|%2f)/i', $_SERVER['QUERY_STRING']) || preg_match('/(order(\s|%20)by(\s|%20)1--)/i', $_SERVER['QUERY_STRING']) || preg_match('/(\/|%2f)(\*|%2a)(\*|%2a)(\/|%2f)/i', $_SERVER['QUERY_STRING']) || preg_match('/(ckfinder|fckeditor|fullclick)/i', $_SERVER['QUERY_STRING']) || preg_match('/(`|<|>|\^|\|\\|0x00|%00|%0d%0a)/i', $_SERVER['QUERY_STRING']) || preg_match('/((.*)header:|(.*)set-cookie:(.*)=)/i', $_SERVER['QUERY_STRING']) || preg_match('/(localhost|127(\.|%2e)0(\.|%2e)0(\.|%2e)1)/i', $_SERVER['QUERY_STRING']) || preg_match('/(cmd|command)(=|%3d)(chdir|mkdir)(.*)(x20)/i', $_SERVER['QUERY_STRING']) || preg_match('/(globals|mosconfig([a-z_]{1,22})|request)(=|\[)/i', $_SERVER['QUERY_STRING']) || preg_match('/(\/|%2f)((wp-)?config)((\.|%2e)inc)?((\.|%2e)php)/i', $_SERVER['QUERY_STRING']) || preg_match('/(thumbs?(_editor|open)?|tim(thumbs?)?)((\.|%2e)php)/i', $_SERVER['QUERY_STRING']) || preg_match('/(absolute_|base|root_)(dir|path)(=|%3d)(ftp|https?)/i', $_SERVER['QUERY_STRING']) || preg_match('/(s)?(ftp|inurl|php)(s)?(:(%2f|%u2215)(%2f|%u2215))/i', $_SERVER['QUERY_STRING']) || preg_match('/(\.|20)(get|the)(_)(permalink|posts_page_url)(\(|%28)/i', $_SERVER['QUERY_STRING']) || preg_match('/((boot|win)((\.|%2e)ini)|etc(\/|%2f)passwd|self(\/|%2f)environ)/i', $_SERVER['QUERY_STRING']) || preg_match('/(((\/|%2f){3,3})|((\.|%2e){3,3})|((\.|%2e){2,2})(\/|%2f|%u2215))/i', $_SERVER['QUERY_STRING']) || preg_match('/(benchmark|char|exec|fopen|function|html)(.*)(\(|%28)(.*)(\)|%29)/i', $_SERVER['QUERY_STRING']) || preg_match('/(php)([0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12})/i', $_SERVER['QUERY_STRING']) || preg_match('/(e|%65|%45)(v|%76|%56)(a|%61|%31)(l|%6c|%4c)(.*)(\(|%28)(.*)(\)|%29)/i', $_SERVER['QUERY_STRING']) || preg_match('/(\/|%2f)(=|%3d|$&|_mm|inurl(:|%3a)(\/|%2f)|(mod|path)(=|%3d)(\.|%2e))/i', $_SERVER['QUERY_STRING']) || preg_match('/(<|%3c)(.*)(e|%65|%45)(m|%6d|%4d)(b|%62|%42)(e|%65|%45)(d|%64|%44)(.*)(>|%3e)/i', $_SERVER['QUERY_STRING']) || preg_match('/(<|%3c)(.*)(i|%69|%49)(f|%66|%46)(r|%72|%52)(a|%61|%41)(m|%6d|%4d)(e|%65|%45)(.*)(>|%3e)/i', $_SERVER['QUERY_STRING']) || preg_match('/(<|%3c)(.*)(o|%4f|%6f)(b|%62|%42)(j|%4a|%6a)(e|%65|%45)(c|%63|%43)(t|%74|%54)(.*)(>|%3e)/i', $_SERVER['QUERY_STRING']) || preg_match('/(<|%3c)(.*)(s|%73|%53)(c|%63|%43)(r|%72|%52)(i|%69|%49)(p|%70|%50)(t|%74|%54)(.*)(>|%3e)/i', $_SERVER['QUERY_STRING']) || preg_match('/(\+|%2b|%20)(d|%64|%44)(e|%65|%45)(l|%6c|%4c)(e|%65|%45)(t|%74|%54)(e|%65|%45)(\+|%2b|%20)/i', $_SERVER['QUERY_STRING']) || preg_match('/(\+|%2b|%20)(i|%69|%49)(n|%6e|%4e)(s|%73|%53)(e|%65|%45)(r|%72|%52)(t|%74|%54)(\+|%2b|%20)/i', $_SERVER['QUERY_STRING']) || preg_match('/(\+|%2b|%20)(s|%73|%53)(e|%65|%45)(l|%6c|%4c)(e|%65|%45)(c|%63|%43)(t|%74|%54)(\+|%2b|%20)/i', $_SERVER['QUERY_STRING']) || preg_match('/(\+|%2b|%20)(u|%75|%55)(p|%70|%50)(d|%64|%44)(a|%61|%41)(t|%74|%54)(e|%65|%45)(\+|%2b|%20)/i', $_SERVER['QUERY_STRING']) || preg_match('/(\\x00|(\"|%22|\'|%27)?0(\"|%22|\'|%27)?(=|%3d)(\"|%22|\'|%27)?0|cast(\(|%28)0x|or%201(=|%3d)1)/i', $_SERVER['QUERY_STRING']) || preg_match('/(g|%67|%47)(l|%6c|%4c)(o|%6f|%4f)(b|%62|%42)(a|%61|%41)(l|%6c|%4c)(s|%73|%53)(=|\[|%[0-9A-Z]{0,2})/i', $_SERVER['QUERY_STRING']) || preg_match('/(_|%5f)(r|%72|%52)(e|%65|%45)(q|%71|%51)(u|%75|%55)(e|%65|%45)(s|%73|%53)(t|%74|%54)(=|\[|%[0-9A-Z]{2,})/i', $_SERVER['QUERY_STRING']) || preg_match('/(j|%6a|%4a)(a|%61|%41)(v|%76|%56)(a|%61|%31)(s|%73|%53)(c|%63|%43)(r|%72|%52)(i|%69|%49)(p|%70|%50)(t|%74|%54)(:|%3a)(.*)(;|%3b|\)|%29)/i', $_SERVER['QUERY_STRING']) || preg_match('/(b|%62|%42)(a|%61|%41)(s|%73|%53)(e|%65|%45)(6|%36)(4|%34)(_|%5f)(e|%65|%45|d|%64|%44)(e|%65|%45|n|%6e|%4e)(c|%63|%43)(o|%6f|%4f)(d|%64|%44)(e|%65|%45)(.*)(\()(.*)(\))/i', $_SERVER['QUERY_STRING']) || preg_match('/(@copy|\$_(files|get|post)|allow_url_(fopen|include)|auto_prepend_file|blexbot|browsersploit|(c99|php)shell|curl(_exec|test)|disable_functions?|document_root|elastix|encodeuricom|exploit|fclose|fgets|file_put_contents|fputs|fsbuff|fsockopen|gethostbyname|grablogin|hmei7|input_file|open_basedir|outfile|passthru|phpinfo|popen|proc_open|quickbrute|remoteview|root_path|safe_mode|shell_exec|site((.){0,2})copier|sux0r|trojan|user_func_array|wget|xertive)/i', $_SERVER['QUERY_STRING']) || preg_match('/(;|<|>|\'|\"|\)|%0a|%0d|%22|%27|%3c|%3e|%00)(.*)(\/\*|alter|base64|benchmark|cast|concat|convert|create|encode|declare|delete|drop|insert|md5|request|script|select|set|union|update)/i', $_SERVER['QUERY_STRING']) || preg_match('/((\+|%2b)(concat|delete|get|select|union)(\+|%2b))/i', $_SERVER['QUERY_STRING']) || preg_match('/(union)(.*)(select)(.*)(\(|%28)/i', $_SERVER['QUERY_STRING']) || preg_match('/(concat|eval)(.*)(\(|%28)/i', $_SERVER['QUERY_STRING'])) {
                        //set as fail attempt
                        $this->setFailAttempt();

                        header('HTTP/1.1 403 Forbidden');
                        exit();
                    }
                }

                if (isset($_SERVER['REQUEST_URI']) && $_SERVER['REQUEST_URI'] <> '') {

                    if (preg_match('/(\^|`|<|>|\\|\|)/i', $_SERVER['REQUEST_URI']) || preg_match('/([a-z0-9]{2000,})/i', $_SERVER['REQUEST_URI']) || preg_match('/(\/)(\*|\"|\'|\.|,|&|&amp;?)\/?$/i', $_SERVER['REQUEST_URI']) || preg_match('/(\/)(vbulletin|boards|vbforum)(\/)?/i', $_SERVER['REQUEST_URI']) || preg_match('/\/((.*)header:|(.*)set-cookie:(.*)=)/i', $_SERVER['REQUEST_URI']) || preg_match('/(\/)(ckfinder|fck|fckeditor|fullclick)/i', $_SERVER['REQUEST_URI']) || preg_match('/(\.(s?ftp-?)config|(s?ftp-?)config\.)/i', $_SERVER['REQUEST_URI']) || preg_match('/(\{0\}|\"?0\"?=\"?0|\(\/\(|\.\.\.|\+\+\+|\\\")/i', $_SERVER['REQUEST_URI']) || preg_match('/(thumbs?(_editor|open)?|tim(thumbs?)?)(\.php)/i', $_SERVER['REQUEST_URI']) || preg_match('/(\.|20)(get|the)(_)(permalink|posts_page_url)(\()/i', $_SERVER['REQUEST_URI']) || preg_match('/(\/\/\/|\?\?|\/&&|\/\*(.*)\*\/|\/:\/|\\\\|0x00|%00|%0d%0a)/i', $_SERVER['REQUEST_URI']) || preg_match('/(\/%7e)(root|ftp|bin|nobody|named|guest|logs|sshd)(\/)/i', $_SERVER['REQUEST_URI']) || preg_match('/(\/)(etc|var)(\/)(hidden|secret|shadow|ninja|passwd|tmp)(\/)?$/i', $_SERVER['REQUEST_URI']) || preg_match('/(s)?(ftp|http|inurl|php)(s)?(:(\/|%2f|%u2215)(\/|%2f|%u2215))/i', $_SERVER['REQUEST_URI']) || preg_match('/(\/)(=|\$&?|&?(pws|rk)=0|_mm|_vti_|(=|\/|;|,)nt\.)/i', $_SERVER['REQUEST_URI']) || preg_match('/(\.)(ds_store|htaccess|htpasswd|init?|mysql-select-db)(\/)?$/i', $_SERVER['REQUEST_URI']) || preg_match('/(\/)(bin)(\/)(cc|chmod|chsh|cpp|echo|id|kill|mail|nasm|perl|ping|ps|python|tclsh)(\/)?$/i', $_SERVER['REQUEST_URI']) || preg_match('/(\/)(::[0-9999]|%3a%3a[0-9999]|127\.0\.0\.1|localhost|makefile|pingserver|wwwroot)(\/)?/i', $_SERVER['REQUEST_URI']) || preg_match('/(\(null\)|\{\$itemURL\}|cAsT\(0x|echo(.*)kae|etc\/passwd|eval\(|self\/environ|\+union\+all\+select)/i', $_SERVER['REQUEST_URI']) || preg_match('/(\/)?j((\s)+)?a((\s)+)?v((\s)+)?a((\s)+)?s((\s)+)?c((\s)+)?r((\s)+)?i((\s)+)?p((\s)+)?t((\s)+)?(%3a|:)/i', $_SERVER['REQUEST_URI']) || preg_match('/(\/)(awstats|(c99|php|web)shell|document_root|error_log|listinfo|muieblack|remoteview|site((.){0,2})copier|sqlpatch|sux0r)/i', $_SERVER['REQUEST_URI']) || preg_match('/(\/)((php|web)?shell|crossdomain|fileditor|locus7|nstview|php(get|remoteview|writer)|r57|remview|sshphp|storm7|webadmin)(.*)(\.|\()/i', $_SERVER['REQUEST_URI']) || preg_match('/(\/)(author-panel|class|database|(db|mysql)-?admin|filemanager|htdocs|httpdocs|https?|mailman|mailto|msoffice|_?php-my-admin(.*)|tmp|undefined|usage|var|vhosts|webmaster|www)(\/)/i', $_SERVER['REQUEST_URI']) || preg_match('/(base64_(en|de)code|benchmark|child_terminate|curl_exec|e?chr|eval|function|fwrite|(f|p)open|html|leak|passthru|p?fsockopen|phpinfo|posix_(kill|mkfifo|setpgid|setsid|setuid)|proc_(close|get_status|nice|open|terminate)|(shell_)?exec|system)(.*)(\()(.*)(\))/i', $_SERVER['REQUEST_URI']) || preg_match('/(\/)(^$|00.temp00|0day|3index|3xp|70bex?|admin_events|bkht|(php|web)?shell|c99|config(\.)?bak|curltest|db|dompdf|filenetworks|hmei7|index\.php\/index\.php\/index|jahat|kcrew|keywordspy|libsoft|marg|mobiquo|mysql|nessus|php-?info|racrew|sql|vuln|(web-?|wp-)?(conf\b|config(uration)?)|xertive)(\.php)/i', $_SERVER['REQUEST_URI']) || preg_match('/(\.)(ab4|ace|afm|ashx|aspx?|bash|ba?k?|bin|bz2|cfg|cfml?|conf\b|config|ctl|dat|db|dist|eml|engine|env|et2|fec|fla|hg|inc|inv|jsp|lqd|make|mbf|mdb|mmw|mny|module|old|one|orig|out|passwd|pdbprofile|psd|pst|ptdb|pwd|py|qbb|qdf|rdf|save|sdb|sh|soa|svn|swl|swo|swp|stx|tax|tgz|theme|tls|tmd|wow|xtmpl|ya?ml)$/i', $_SERVER['REQUEST_URI'])

                    ) {
                        //set as fail attempt
                        $this->setFailAttempt();

                        header('HTTP/1.1 403 Forbidden');
                        exit();
                    }
                }

            }

            if ((int)HMWP_Classes_Tools::getOption('hmwp_sqlinjection_level') == 4) {

                if (isset($_SERVER['HTTP_USER_AGENT']) && $_SERVER['HTTP_USER_AGENT'] <> '') {
                    if (preg_match('/([a-z0-9]{2000,})/i', $_SERVER['HTTP_USER_AGENT']) || preg_match('/(&lt;|%0a|%0d|%27|%3c|%3e|%00|0x00)/i', $_SERVER['HTTP_USER_AGENT']) || preg_match('/(base64_decode|bin\/bash|disconnect|eval|lwp-download|unserialize)/i', $_SERVER['HTTP_USER_AGENT'])) {
                        //set as fail attempt
                        $this->setFailAttempt();

                        header('HTTP/1.1 403 Forbidden');
                        exit();
                    }
                }

                if (isset($_SERVER['QUERY_STRING']) && $_SERVER['QUERY_STRING'] <> '') {
                    if (preg_match('/^(%2d|-)[^=]+$/i', $_SERVER['QUERY_STRING']) || preg_match('/([a-z0-9]{4000,})/i', $_SERVER['QUERY_STRING']) || preg_match('/(\/|%2f)(:|%3a)(\/|%2f)/i', $_SERVER['QUERY_STRING']) || preg_match('/(etc\/(hosts|motd|shadow))/i', $_SERVER['QUERY_STRING']) || preg_match('/(order(\s|%20)by(\s|%20)1--)/i', $_SERVER['QUERY_STRING']) || preg_match('/(\/|%2f)(\*|%2a)(\*|%2a)(\/|%2f)/i', $_SERVER['QUERY_STRING']) || preg_match('/(`|<|>|\^|\|\\|0x00|%00|%0d%0a)/i', $_SERVER['QUERY_STRING']) || preg_match('/(f?ckfinder|f?ckeditor|fullclick)/i', $_SERVER['QUERY_STRING']) || preg_match('/((.*)header:|(.*)set-cookie:(.*)=)/i', $_SERVER['QUERY_STRING']) || preg_match('/(localhost|127(\.|%2e)0(\.|%2e)0(\.|%2e)1)/i', $_SERVER['QUERY_STRING']) || preg_match('/(cmd|command)(=|%3d)(chdir|mkdir)(.*)(x20)/i', $_SERVER['QUERY_STRING']) || preg_match('/(globals|mosconfig([a-z_]{1,22})|request)(=|\[)/i', $_SERVER['QUERY_STRING']) || preg_match('/(\/|%2f)((wp-)?config)((\.|%2e)inc)?((\.|%2e)php)/i', $_SERVER['QUERY_STRING']) || preg_match('/(thumbs?(_editor|open)?|tim(thumbs?)?)((\.|%2e)php)/i', $_SERVER['QUERY_STRING']) || preg_match('/(absolute_|base|root_)(dir|path)(=|%3d)(ftp|https?)/i', $_SERVER['QUERY_STRING']) || preg_match('/(s)?(ftp|inurl|php)(s)?(:(\/|%2f|%u2215)(\/|%2f|%u2215))/i', $_SERVER['QUERY_STRING']) || preg_match('/(\.|20)(get|the)(_|%5f)(permalink|posts_page_url)(\(|%28)/i', $_SERVER['QUERY_STRING']) || preg_match('/((boot|win)((\.|%2e)ini)|etc(\/|%2f)passwd|self(\/|%2f)environ)/i', $_SERVER['QUERY_STRING']) || preg_match('/(((\/|%2f){3,3})|((\.|%2e){3,3})|((\.|%2e){2,2})(\/|%2f|%u2215))/i', $_SERVER['QUERY_STRING']) || preg_match('/(benchmark|char|exec|fopen|function|html)(.*)(\(|%28)(.*)(\)|%29)/i', $_SERVER['QUERY_STRING']) || preg_match('/(php)([0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12})/i', $_SERVER['QUERY_STRING']) || preg_match('/(e|%65|%45)(v|%76|%56)(a|%61|%31)(l|%6c|%4c)(.*)(\(|%28)(.*)(\)|%29)/i', $_SERVER['QUERY_STRING']) || preg_match('/(\/|%2f)(=|%3d|\$&|_mm|inurl(:|%3a)(\/|%2f)|(mod|path)(=|%3d)(\.|%2e))/i', $_SERVER['QUERY_STRING']) || preg_match('/(<|%3c)(.*)(e|%65|%45)(m|%6d|%4d)(b|%62|%42)(e|%65|%45)(d|%64|%44)(.*)(>|%3e)/i', $_SERVER['QUERY_STRING']) || preg_match('/(<|%3c)(.*)(i|%69|%49)(f|%66|%46)(r|%72|%52)(a|%61|%41)(m|%6d|%4d)(e|%65|%45)(.*)(>|%3e)/i', $_SERVER['QUERY_STRING']) || preg_match('/(<|%3c)(.*)(o|%4f|%6f)(b|%62|%42)(j|%4a|%6a)(e|%65|%45)(c|%63|%43)(t|%74|%54)(.*)(>|%3e)/i', $_SERVER['QUERY_STRING']) || preg_match('/(<|%3c)(.*)(s|%73|%53)(c|%63|%43)(r|%72|%52)(i|%69|%49)(p|%70|%50)(t|%74|%54)(.*)(>|%3e)/i', $_SERVER['QUERY_STRING']) || preg_match('/(\+|%2b|%20)(d|%64|%44)(e|%65|%45)(l|%6c|%4c)(e|%65|%45)(t|%74|%54)(e|%65|%45)(\+|%2b|%20)/i', $_SERVER['QUERY_STRING']) || preg_match('/(\+|%2b|%20)(i|%69|%49)(n|%6e|%4e)(s|%73|%53)(e|%65|%45)(r|%72|%52)(t|%74|%54)(\+|%2b|%20)/i', $_SERVER['QUERY_STRING']) || preg_match('/(\+|%2b|%20)(s|%73|%53)(e|%65|%45)(l|%6c|%4c)(e|%65|%45)(c|%63|%43)(t|%74|%54)(\+|%2b|%20)/i', $_SERVER['QUERY_STRING']) || preg_match('/(\+|%2b|%20)(u|%75|%55)(p|%70|%50)(d|%64|%44)(a|%61|%41)(t|%74|%54)(e|%65|%45)(\+|%2b|%20)/i', $_SERVER['QUERY_STRING']) || preg_match('/(\\x00|(\"|%22|\'|%27)?0(\"|%22|\'|%27)?(=|%3d)(\"|%22|\'|%27)?0|cast(\(|%28)0x|or%201(=|%3d)1)/i', $_SERVER['QUERY_STRING']) || preg_match('/(g|%67|%47)(l|%6c|%4c)(o|%6f|%4f)(b|%62|%42)(a|%61|%41)(l|%6c|%4c)(s|%73|%53)(=|\[|%[0-9A-Z]{0,2})/i', $_SERVER['QUERY_STRING']) || preg_match('/(_|%5f)(r|%72|%52)(e|%65|%45)(q|%71|%51)(u|%75|%55)(e|%65|%45)(s|%73|%53)(t|%74|%54)(=|\[|%[0-9A-Z]{2,})/i', $_SERVER['QUERY_STRING']) || preg_match('/(j|%6a|%4a)(a|%61|%41)(v|%76|%56)(a|%61|%31)(s|%73|%53)(c|%63|%43)(r|%72|%52)(i|%69|%49)(p|%70|%50)(t|%74|%54)(:|%3a)(.*)(;|%3b|\)|%29)/i', $_SERVER['QUERY_STRING']) || preg_match('/(b|%62|%42)(a|%61|%41)(s|%73|%53)(e|%65|%45)(6|%36)(4|%34)(_|%5f)(e|%65|%45|d|%64|%44)(e|%65|%45|n|%6e|%4e)(c|%63|%43)(o|%6f|%4f)(d|%64|%44)(e|%65|%45)(.*)(\()(.*)(\))/i', $_SERVER['QUERY_STRING']) || preg_match('/(@copy|\$_(files|get|post)|allow_url_(fopen|include)|auto_prepend_file|blexbot|browsersploit|call_user_func_array|(php|web)shell|curl(_exec|test)|disable_functions?|document_root)/i', $_SERVER['QUERY_STRING']) || preg_match('/(elastix|encodeuricom|exploit|fclose|fgets|file_put_contents|fputs|fsbuff|fsockopen|gethostbyname|ghost|grablogin|hmei7|hubs_post-cta|input_file|invokefunction|(\b)load_file|open_basedir|outfile|p3dlite)/i', $_SERVER['QUERY_STRING']) || preg_match('/(pass(=|%3d)shell|passthru|phpinfo|phpshells|popen|proc_open|quickbrute|remoteview|root_path|safe_mode|shell_exec|site((.){0,2})copier|sp_executesql|sux0r|trojan|udtudt|user_func_array|wget|wp_insert_user|xertive)/i', $_SERVER['QUERY_STRING']) || preg_match('/(;|<|>|\'|\"|\)|%0a|%0d|%22|%27|%3c|%3e|%00)(.*)(\/\*|alter|base64|benchmark|cast|concat|convert|create|encode|declare|delay|delete|drop|hex|insert|load|md5|null|replace|request|script|select|set|sleep|truncate|unhex|update)/i', $_SERVER['QUERY_STRING']) || preg_match('/((\+|%2b)(concat|delete|get|select|union)(\+|%2b))/i', $_SERVER['QUERY_STRING']) || preg_match('/(union)(.*)(select)(.*)(\(|%28)/i', $_SERVER['QUERY_STRING']) || preg_match('/(concat|eval)(.*)(\(|%28)/i', $_SERVER['QUERY_STRING'])) {
                        //set as fail attempt
                        $this->setFailAttempt();

                        header('HTTP/1.1 403 Forbidden');
                        exit();
                    }
                }

                if (isset($_SERVER['REQUEST_URI']) && $_SERVER['REQUEST_URI'] <> '') {

                    if (preg_match('/(,,,)/i', $_SERVER['REQUEST_URI']) || preg_match('/(-------)/i', $_SERVER['REQUEST_URI']) || preg_match('/(\^|`|<|>|\\|\|)/i', $_SERVER['REQUEST_URI']) || preg_match('/([a-z0-9]{2000,})/i', $_SERVER['REQUEST_URI']) || preg_match('/(=?\(\'|%27\)\/?)(\.)/i', $_SERVER['REQUEST_URI']) || preg_match('/(\/)(\*|\"|\'|\.|,|&|&amp;?)(\/)?$/i', $_SERVER['REQUEST_URI']) || preg_match('/(\.)(php)(\()?([0-9]+)(\))?(\/)?$/i', $_SERVER['REQUEST_URI']) || preg_match('/(\/)((.*)header:|(.*)set-cookie:(.*)=)/i', $_SERVER['REQUEST_URI']) || preg_match('/(\.(s?ftp-?)config|(s?ftp-?)config\.)/i', $_SERVER['REQUEST_URI']) || preg_match('/(\/)(f?ckfinder|fck\/|f?ckeditor|fullclick)/i', $_SERVER['REQUEST_URI']) || preg_match('/(\/)((force-)?download|framework\/main)(\.php)/i', $_SERVER['REQUEST_URI']) || preg_match('/(\{0\}|\"?0\"?=\"?0|\(\/\(|\.\.\.|\+\+\+|\\\")/i', $_SERVER['REQUEST_URI']) || preg_match('/(\/)(vbull(etin)?|boards|vbforum|vbweb|webvb)(\/)?/i', $_SERVER['REQUEST_URI']) || preg_match('/(\.|20)(get|the)(_)(permalink|posts_page_url)(\()/i', $_SERVER['REQUEST_URI']) || preg_match('/(\/\/\/|\?\?|\/&&|\/\*(.*)\*\/|\/:\/|\\\\|0x00|%00|%0d%0a)/i', $_SERVER['REQUEST_URI']) || preg_match('/(\/)(cgi_?)?alfa(_?cgiapi|_?data|_?v[0-9]+)?(\.php)/i', $_SERVER['REQUEST_URI']) || preg_match('/(thumbs?(_editor|open)?|tim(thumbs?)?)((\.|%2e)php)/i', $_SERVER['REQUEST_URI']) || preg_match('/(\/)((boot)?_?admin(er|istrator|s)(_events)?)(\.php)/i', $_SERVER['REQUEST_URI']) || preg_match('/(\/%7e)(root|ftp|bin|nobody|named|guest|logs|sshd)\//i', $_SERVER['REQUEST_URI']) || preg_match('/(\/)(\.?mad|alpha|c99|php|web)?sh(3|e)ll([0-9]+|\w)(\.php)/i', $_SERVER['REQUEST_URI']) || preg_match('/(\/)(admin-?|file-?)(upload)(bg|_?file|ify|svu|ye)?(\.php)/i', $_SERVER['REQUEST_URI']) || preg_match('/(\/)(etc|var)(\/)(hidden|secret|shadow|ninja|passwd|tmp)(\/)?$/i', $_SERVER['REQUEST_URI']) || preg_match('/(s)?(ftp|http|inurl|php)(s)?(:(\/|%2f|%u2215)(\/|%2f|%u2215))/i', $_SERVER['REQUEST_URI']) || preg_match('/(\/)(=|\$&?|&?(pws|rk)=0|_mm|_vti_|(=|\/|;|,)nt\.)/i', $_SERVER['REQUEST_URI']) || preg_match('/(\.)(ds_store|htaccess|htpasswd|init?|mysql-select-db)(\/)?$/i', $_SERVER['REQUEST_URI']) || preg_match('/(\/)(bin)(\/)(cc|chmod|chsh|cpp|echo|id|kill|mail|nasm|perl|ping|ps|python|tclsh)(\/)?$/i', $_SERVER['REQUEST_URI']) || preg_match('/(\/)(::[0-9999]|%3a%3a[0-9999]|127\.0\.0\.1|ccx|localhost|makefile|pingserver|wwwroot)(\/)?/i', $_SERVER['REQUEST_URI']) || preg_match('/^(\/)(123|backup|bak|beta|bkp|default|demo|dev(new|old)?|home|new-?site|null|old|old_files|old1)(\/)?$/i', $_SERVER['REQUEST_URI']) || preg_match('/(\/)?j((\s)+)?a((\s)+)?v((\s)+)?a((\s)+)?s((\s)+)?c((\s)+)?r((\s)+)?i((\s)+)?p((\s)+)?t((\s)+)?(%3a|:)/i', $_SERVER['REQUEST_URI']) || preg_match('/^(\/)(old-?site(back)?|old(web)?site(here)?|sites?|staging|undefined|wordpress([0-9]+)|wordpress-old)(\/)?$/i', $_SERVER['REQUEST_URI']) || preg_match('/(\/)(filemanager|htdocs|httpdocs|https?|login|mailman|mailto|msoffice|undefined|usage|var|vhosts|webmaster|www)(\/)/i', $_SERVER['REQUEST_URI']) || preg_match('/(\(null\)|\{\$itemURL\}|cast\(0x|echo(.*)kae|etc\/passwd|eval\(|null(.*)null|open_basedir|self\/environ|\+union\+all\+select)/i', $_SERVER['REQUEST_URI']) || preg_match('/(\/)(db-?|j-?|my(sql)?-?|setup-?|web-?|wp-?)?(admin-?)?(setup-?)?(conf\b|conf(ig)?)(uration)?(\.?bak|\.inc)?(\.inc|\.old|\.php|\.txt)/i', $_SERVER['REQUEST_URI']) || preg_match('/(\/)((.*)crlf-?injection|(.*)xss-?protection|__(inc|jsc)|administrator|author-panel|cgi-bin|database|downloader|(db|mysql)-?admin)(\/)/i', $_SERVER['REQUEST_URI']) || preg_match('/(\/)(haders|head|hello|helpear|incahe|includes?|indo(sec)?|infos?|install|ioptimizes?|jmail|js|king|kiss|kodox|kro|legion|libsoft)(\.php)/i', $_SERVER['REQUEST_URI']) || preg_match('/(\/)(awstats|document_root|dologin\.action|error.log|extension\/ext|htaccess\.|lib\/php|listinfo|phpunit\/php|remoteview|server\/php|www\.root\.)/i', $_SERVER['REQUEST_URI']) || preg_match('/(base64_(en|de)code|benchmark|curl_exec|e?chr|eval|function|fwrite|(f|p)open|html|leak|passthru|p?fsockopen|phpinfo)(.*)(\(|%28)(.*)(\)|%29)/i', $_SERVER['REQUEST_URI']) || preg_match('/(posix_(kill|mkfifo|setpgid|setsid|setuid)|(child|proc)_(close|get_status|nice|open|terminate)|(shell_)?exec|system)(.*)(\(|%28)(.*)(\)|%29)/i', $_SERVER['REQUEST_URI']) || preg_match('/(\/)((c99|php|web)?shell|crossdomain|fileditor|locus7|nstview|php(get|remoteview|writer)|r57|remview|sshphp|storm7|webadmin)(.*)(\.|%2e|\(|%28)/i', $_SERVER['REQUEST_URI']) || preg_match('/(\/)((wp-)((201\d|202\d|[0-9]{2})|ad|admin(fx|rss|setup)|booking|confirm|crons|data|file|mail|one|plugins?|readindex|reset|setups?|story))(\.php)/i', $_SERVER['REQUEST_URI']) || preg_match('/(\/)(^$|-|\!|\w|\.(.*)|100|123|([^iI])?ndex|index\.php\/index|3xp|777|7yn|90sec|99|active|aill|ajs\.delivery|al277|alexuse?|ali|allwrite)(\.php)/i', $_SERVER['REQUEST_URI']) || preg_match('/(\/)(analyser|apache|apikey|apismtp|authenticat(e|ing)|autoload_classmap|backup(_index)?|bakup|bkht|black|bogel|bookmark|bypass|cachee?)(\.php)/i', $_SERVER['REQUEST_URI']) || preg_match('/(\/)(clean|cm(d|s)|con|connector\.minimal|contexmini|contral|curl(test)?|data(base)?|db|db-cache|db-safe-mode|defau11|defau1t|dompdf|dst)(\.php)/i', $_SERVER['REQUEST_URI']) || preg_match('/(\/)(elements|emails?|error.log|ecscache|edit-form|eval-stdin|export|evil|fbrrchive|filemga|filenetworks?|f0x|gank(\.php)?|gass|gel|guide)(\.php)/i', $_SERVER['REQUEST_URI']) || preg_match('/(\/)(logo_img|lufix|mage|marg|mass|mide|moon|mssqli|mybak|myshe|mysql|mytag_js?|nasgor|newfile|news|nf_?tracking|nginx|ngoi|ohayo|old-?index)/i', $_SERVER['REQUEST_URI']) || preg_match('/(\/)(olux|owl|pekok|petx|php-?info|phpping|popup-pomo|priv|r3x|radio|rahma|randominit|readindex|readmy|reads|repair-?bak|robot(s\.txt)?|root)/i', $_SERVER['REQUEST_URI']) || preg_match('/(\/)(router|savepng|semayan|shell|shootme|sky|socket(c|i|iasrgasf)ontrol|sql(bak|_?dump)?|support|sym403|sys|system_log|test|tmp-?(uploads)?)/i', $_SERVER['REQUEST_URI']) || preg_match('/(\/)(traffic-advice|u2p|udd|ukauka|up__uzegp|up14|upa?|upxx?|vega|vip|vu(ln)?(\w)?|webroot|weki|wikindex|wordpress|wp_logns?|wp_wrong_datlib)/i', $_SERVER['REQUEST_URI']) || preg_match('/(\/)((wp-?)?install(ation)?|wp(3|4|5|6)|wpfootes|wpzip|ws0|wsdl|wso(\w)?|www|(uploads|wp-admin)?xleet(-shell)?|xmlsrpc|xup|xxu|xxx|zibi|zipy)/i', $_SERVER['REQUEST_URI']) || preg_match('/(bkv74|cachedsimilar|core-stab|crgrvnkb|ctivrc|deadcode|deathshop|dkiz|e7xue|eqxafaj90zir|exploits|ffmkpcal|filellli7|(fox|sid)wso|gel4y|goog1es|gvqqpinc)/i', $_SERVER['REQUEST_URI']) || preg_match('/(@md5|00.temp00|0byte|0d4y|0day|0xor|wso1337|1h6j5|3xp|40dd1d|4price|70bex?|a57bze893|abbrevsprl|abruzi|adminer|aqbmkwwx|archivarix|backdoor|beez5|bgvzc29)/i', $_SERVER['REQUEST_URI']) || preg_match('/(handler_to_code|hax(0|o)r|hmei7|hnap1|home_url=|ibqyiove|icxbsx|indoxploi|jahat|jijle3|kcrew|keywordspy|laobiao|lock360|longdog|marijuan|mod_(aratic|ariimag))/i', $_SERVER['REQUEST_URI']) || preg_match('/(mobiquo|muiebl|nessus|osbxamip|phpunit|priv8|qcmpecgy|r3vn330|racrew|raiz0|reportserver|r00t|respectmus|rom2823|roseleif|sh3ll|site((.){0,2})copier|sqlpatch|sux0r)/i', $_SERVER['REQUEST_URI']) || preg_match('/(sym403|telerik|uddatasql|utchiha|visualfrontend|w0rm|wangdafa|wpyii2|wsoyanzo|x5cv|xattack|xbaner|xertive|xiaolei|xltavrat|xorz|xsamxad|xsvip|xxxs?s?|zabbix|zebda)/i', $_SERVER['REQUEST_URI']) || preg_match('/(\.)(ab4|ace|afm|alfa|as(h|m)x?|aspx?|aws|axd|bash|ba?k?|bat|bin|bz2|cfg|cfml?|cms|conf\b|config|ctl|dat|db|dist|dll|eml|eng(ine)?|env|et2|fec|fla|git(ignore)?)$/i', $_SERVER['REQUEST_URI']) || preg_match('/(\.)(hg|idea|inc|index|ini|inv|jar|jspa?|lib|local|log|lqd|make|mbf|mdb|mmw|mny|mod(ule)?|msi|old|one|orig|out|passwd|pdb|php\.(php|suspect(ed)?)|php([^\/])|phtml?|pl|profiles?)$/i', $_SERVER['REQUEST_URI']) || preg_match('/(\.)(pst|ptdb|production|pwd|py|qbb|qdf|rdf|remote|save|sdb|sh|soa|svn|swf|swl|swo|swp|stx|tax|tgz?|theme|tls|tmb|tmd|wok|wow|xsd|xtmpl|xz|ya?ml|za|zlib)$/i', $_SERVER['REQUEST_URI'])) {
                        //set as fail attempt
                        $this->setFailAttempt();

                        header('HTTP/1.1 403 Forbidden');
                        exit();
                    }
                }

            }
        }
    }

    /**
     * Set attempt as brute force
     *
     * @return void
     * @throws Exception
     */
    public function setFailAttempt() {
        if (HMWP_Classes_Tools::getOption('hmwp_bruteforce')) {
            HMWP_Classes_ObjController::getClass('HMWP_Models_Brute')->brute_call('failed_attempt');
        }
    }

}
