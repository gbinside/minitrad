<?php
#controllo del cambio lingua ?langcode=en da qualunque url...
if (isset($_REQUEST['langcode']) && !empty($_REQUEST['langcode'])) {
	$request_langcode = $_REQUEST['langcode'];
  setcookie("minitrad_langcode", $request_langcode, time()+3600);

	Header( "HTTP/1.1 302 Found" ); 
	Header( "Location: ?" ); 
	exit();
}

$GLOBALS['minitrad_langcode_list'] = array(
'af', // afrikaans.
'ar', // arabic.
'bg', // bulgarian.
'ca', // catalan.
'cs', // czech.
'da', // danish.
'de', // german.
'el', // greek.
'en', // english.
'es', // spanish.
'et', // estonian.
'fi', // finnish.
'fr', // french.
'gl', // galician.
'he', // hebrew.
'hi', // hindi.
'hr', // croatian.
'hu', // hungarian.
'id', // indonesian.
'it', // italian.
'ja', // japanese.
'ko', // korean.
'ka', // georgian.
'lt', // lithuanian.
'lv', // latvian.
'ms', // malay.
'nl', // dutch.
'no', // norwegian.
'pl', // polish.
'pt', // portuguese.
'ro', // romanian.
'ru', // russian.
'sk', // slovak.
'sl', // slovenian.
'sq', // albanian.
'sr', // serbian.
'sv', // swedish.
'th', // thai.
'tr', // turkish.
'uk', // ukrainian.
'zh' // chinese.
);

class Language {
  public $language = 'it';
}

class AutodetectLanguageButKeepTheOneInSessionIfExists extends Language {
    public function __construct() {
        if (isset($_COOKIE['minitrad_langcode'])) {
            $this->language = $_COOKIE['minitrad_langcode'];
        } else {
            if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
              $lingue = explode(',',$_SERVER['HTTP_ACCEPT_LANGUAGE']);
              foreach ($lingue as $lingua) {
                  $pieces = explode(';',$lingua);
              	  $fname = dirname(__FILE__).DIRECTORY_SEPARATOR.$pieces[0].'.yaml';
                  if (file_exists($fname)) {
                      $this->language = $pieces[0];
                      setcookie("minitrad_langcode", $this->language, time()+3600);
                      break;
                  }
              }
            }            
        }
    }
}

$language = new AutodetectLanguageButKeepTheOneInSessionIfExists();

require_once "spyc.php";

function format_plural($count, $singular, $plural, $args = array(), $langcode = NULL) {
  $args['@count'] = $count;
  if ($count == 1) {
    return t($singular, $args, $langcode);
  }

  // Get the plural index through the gettext formula.
  $index = (function_exists('locale_get_plural')) ? locale_get_plural($count, $langcode) : -1;
  // Backwards compatibility.
  if ($index < 0) {
    return t($plural, $args, $langcode);
  }
  else {
    switch ($index) {
      case "0":
        return t($singular, $args, $langcode);
      case "1":
        return t($plural, $args, $langcode);
      default:
        unset($args['@count']);
        $args['@count[' . $index . ']'] = $count;
        return t(strtr($plural, array('@count' => '@count[' . $index . ']')), $args, $langcode);
    }
  }
}

function __($string, $args = array(), $langcode = NULL) {
  return t($string, $args, $langcode);
}

function t($string, $args = array(), $langcode = NULL) {
  global $language;
  static $custom_strings;

  $langcode = isset($langcode) ? $langcode : $language->language;

  // First, check for an array of customized strings. If present, use the array
  // *instead of* database lookups. This is a high performance way to provide a
  // handful of string replacements. See settings.php for examples.
  // Cache the $custom_strings variable to improve performance.
  if (!isset($custom_strings[$langcode])) {
    $custom_strings[$langcode] = array();
  }
  // Custom strings work for English too, even if locale module is disabled.
  if (isset($custom_strings[$langcode][$string])) {
    $string = $custom_strings[$langcode][$string];
  }
  // Translate with locale module if enabled.
  elseif (function_exists('locale')) {
    $custom_strings[$langcode][$string] = locale($string, $langcode);
    $string = $custom_strings[$langcode][$string] ;
  }
  if (empty($args)) {
    return $string;
  }
  else {
    // Transform arguments before inserting them.
    foreach ($args as $key => $value) {
      switch ($key[0]) {
        case '@':
          // Escaped only.
          $args[$key] = check_plain($value);
          break;

        case '%':
        default:
          // Escaped and placeholder.
          $args[$key] = drupal_placeholder($value);
          break;

        case '!':
          // Pass-through.
      }
    }
    return strtr($string, $args);
  }
}

function drupal_placeholder($text) {
  return '<em class="placeholder">' . check_plain($text) . '</em>';
}

function check_plain($text) {
  static $php525;

  if (!isset($php525)) {
    $php525 = version_compare(PHP_VERSION, '5.2.5', '>=');
  }
  // We duplicate the preg_match() to validate strings as UTF-8 from
  // drupal_validate_utf8() here. This avoids the overhead of an additional
  // function call, since check_plain() may be called hundreds of times during
  // a request. For PHP 5.2.5+, this check for valid UTF-8 should be handled
  // internally by PHP in htmlspecialchars().
  // @see http://www.php.net/releases/5_2_5.php
  // @todo remove this when support for either IE6 or PHP < 5.2.5 is dropped.

  if ($php525) {
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
  }
  return (preg_match('/^./us', $text) == 1) ? htmlspecialchars($text, ENT_QUOTES, 'UTF-8') : '';
}

function locale($string = NULL, $langcode = NULL, $reset = FALSE) {
  global $language;
  static $locale_t;

  if ($reset || !isset($locale_t)) {
    $locale_t = array();
  }

  if (!isset($string)) {
    // Return all strings if no string was specified
    return $locale_t;
  }

  $langcode = isset($langcode) ? $langcode : $language->language;

  if (!isset($locale_t[$langcode])) {
	  $fname = dirname(__FILE__).DIRECTORY_SEPARATOR.$langcode.'.yaml';
    $locale_t[$langcode] = file_exists($fname) ? Spyc::YAMLLoad($fname) : array();
  }

  if (!isset($locale_t[$langcode][$string])) {
      $locale_t[$langcode][$string] = TRUE;
  }

  return ($locale_t[$langcode][$string] === TRUE ? $string : $locale_t[$langcode][$string]);
}