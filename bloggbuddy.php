<?php
/**
 * Plugin Name: BloggBuddy Lite
 * Plugin URI: http://bloggbuddy.com
 * Description: Quickly find relevant blogs for comments, links, traffic and engagement. <strong>Settings:</strong> Simply activate and you will see a new meta box on the "Post" and "Page" edit screens. No other setting required! <strong>Usage:</strong> Enter a keyword, select the "type", click update keyword button, and a list of relevant articles will appear.
 * Version: 1.1
 * Author: Brian Oliver
 * Author URI: http://brianoliverblog.com
 */
// ----------------------- Original Code --------------

/* Direct Access To This File is Restricted */

if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
  die('You cannot access this page directly.');
}

function google_search_activate() {
  update_option('googlesearch', '0');
}

register_activation_hook(__FILE__, 'google_search_activate');

function google_search_deactivate() {
  delete_option('googlesearch');
}

register_deactivation_hook(__FILE__, 'google_search_deactivate');

function GoogleBL($domain) {
  $url = "http://ajax.googleapis.com/ajax/services/search/web?v=1.0&q=link:" . $domain . "&filter=0";
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
  curl_setopt($ch, CURLOPT_HEADER, 0);
  curl_setopt($ch, CURLOPT_NOBODY, 0);
  curl_setopt($ch, CURLOPT_TIMEOUT, 30);
  $json = curl_exec($ch);
  curl_close($ch);
  $data = json_decode($json, true);

  if ($data['responseStatus'] == 200) {
    return $data['responseData']['cursor']['resultCount'];
  } else {
    return false;
  }
}

function alexa_rank($url) {
  $xml = simplexml_load_file('http://data.alexa.com/data?cli=10&dat=snbamz&url=' . $url);
  $rank = isset($xml->SD[1]->POPULARITY) ? $xml->SD[1]->POPULARITY->attributes()->TEXT : 0;
//  $web = (string) $xml->SD[0]->attributes()->HOST;
  return $rank;
}

class PR {

  public function get_google_pagerank($url) {
    $query = "http://toolbarqueries.google.com/tbr?client=navclient-auto&ch=" . $this->CheckHash($this->HashURL($url)) . "&features=Rank&q=info:" . $url . "&num=100&filter=0";
    $data = file_get_contents($query);
    $pos = strpos($data, "Rank_");
    if ($pos === false) {
      return '';
    } else {
      $pagerank = substr($data, $pos + 9);
      return $pagerank;
    }
  }

  public function StrToNum($Str, $Check, $Magic) {
    $Int32Unit = 4294967296;
    //  2^32
    $length = strlen($Str);
    for ($i = 0; $i < $length; $i++) {
      $Check *= $Magic;
      if ($Check >= $Int32Unit) {
        $Check = ($Check - $Int32Unit * (int) ($Check / $Int32Unit));
        $Check = ($Check < -2147483648) ? ($Check + $Int32Unit) : $Check;
      }
      $Check += ord($Str{$i});
    }
    return $Check;
  }

  public function HashURL($String) {
    $Check1 = $this->StrToNum($String, 0x1505, 0x21);
    $Check2 = $this->StrToNum($String, 0, 0x1003F);

    $Check1 >>= 2;
    $Check1 = (($Check1 >> 4) & 0x3FFFFC0 ) | ($Check1 & 0x3F);
    $Check1 = (($Check1 >> 4) & 0x3FFC00 ) | ($Check1 & 0x3FF);
    $Check1 = (($Check1 >> 4) & 0x3C000 ) | ($Check1 & 0x3FFF);

    $T1 = (((($Check1 & 0x3C0) << 4) | ($Check1 & 0x3C)) << 2 ) | ($Check2 & 0xF0F );
    $T2 = (((($Check1 & 0xFFFFC000) << 4) | ($Check1 & 0x3C00)) << 0xA) | ($Check2 & 0xF0F0000 );

    return ($T1 | $T2);
  }

  public function CheckHash($Hashnum) {
    $CheckByte = 0;
    $Flag = 0;

    $HashStr = sprintf('%u', $Hashnum);
    $length = strlen($HashStr);

    for ($i = $length - 1; $i >= 0; $i --) {
      $Re = $HashStr{$i};
      if (1 === ($Flag % 2)) {
        $Re += $Re;
        $Re = (int) ($Re / 10) + ($Re % 10);
      }
      $CheckByte += $Re;
      $Flag ++;
    }

    $CheckByte %= 10;
    if (0 !== $CheckByte) {
      $CheckByte = 10 - $CheckByte;
      if (1 === ($Flag % 2)) {
        if (1 === ($CheckByte % 2)) {
          $CheckByte += 9;
        }
        $CheckByte >>= 1;
      }
    }
    return '7' . $CheckByte . $HashStr;
  }

}

$prank = new PR();

function wdm_keyword_add_custom_box() {

  // Define the custom attachment for posts
  add_meta_box('wdm_keyword_meta_id', 'BloggBuddy Lite', 'wdm_keyword_fun_enter_keyword', 'post', 'normal');

  // Define the custom attachment for pages
  add_meta_box('wdm_keyword_meta_id', 'BloggBuddy Lite', 'wdm_keyword_fun_enter_keyword', 'page', 'normal');
}

add_action('add_meta_boxes', 'wdm_keyword_add_custom_box');

function wdm_keyword_fun_enter_keyword() {
  $pid = get_the_id();

  $keyword_listword_list = str_replace(' ', '+', get_post_meta($pid, 'key', true)); //get the keyword
  wp_register_style('wdm_comments_css', plugins_url('css/wdm_commments_pro.css', __FILE__));
  wp_enqueue_style('wdm_comments_css');

  //code to load js files
  wp_register_script('wdm_keyword_update_js', plugins_url('js/wdm_save_keywords_links.js', __FILE__), array('jquery'), false, true);
  wp_enqueue_script('wdm_keyword_update_js');
  $setting_values = array('image_path' => plugins_url('image/wpspin.gif', __FILE__), 'admin_ajax_path' => admin_url('admin-ajax.php'), 'post_id' => $pid);

  wp_localize_script('wdm_keyword_update_js', 'wdm_obj', $setting_values);
  echo '<img src="' . plugins_url('bloggbuddy/image/logo.png', dirname(__FILE__)) . '" width="300"> ';
  ?>

  <div align="left" style="padding: 5px 25px;border: 1px solid #ccc;margin-bottom: 13px;box-shadow: 1px 0px 1px 2px #ccc;border-radius: 8px;">
    <span style="margin-top: 6px;float: left;">You are using BloggBuddy Lite Version -Upgrade now to unlock premium features.</span><a href="http://bloggbuddy.com/" class="button button-primary button-large" style="margin-left:10px;" target="_blank">Upgrade Now</a>
  </div>
  <div style='width:100%'>
    <div style='width:45% !important;float:left'>
      <table class="wdm_options_table">
        <tr>
          <td>
            Enter Keywords:
          </td>
          <td>
            <input type='text' id='id_keywords' name='txt_keyword'
                   value="<?php if ($keyword_listword_list != '') echo $keyword_listword_list; ?>"
                   placeholder="Enter keyword list">
          </td>
        </tr>
        <tr>
          <td>
            Keyword + Commentluv
          </td>
          <td>
            <input type='checkbox' class="google-search" value='commentluv' name='comment_plug'
                   id='wdm_comment_plug' <?php if (get_post_meta($pid, 'commentluv', true)) { ?> checked  <?php } ?>>
          </td>
        </tr>
        <tr>
          <td>
            Keyword + One week old
          </td>
          <td>
            <input class="google-search" type='checkbox' value='pastweek' name='timespan' id='wdm_timespan' <?php if (get_post_meta($pid, 'timespan', true)) { ?> checked  <?php } ?>>
          </td>
        </tr>
        <tr>
          <td>
            Keyword + Allintitle
          </td>
          <td>
            <input  type='checkbox' value='intitle' name='title_in'
                    id='wdm_title_in' <?php if (get_post_meta($pid, 'title_url', true)) { ?> checked  <?php } ?>>
          </td>
        </tr>
        <tr>
          <td>
            Keyword + Google
          </td>
          <td>
            <input type='checkbox' value='google' name='google_in'
                   id='wdm_google_in' <?php if (get_post_meta($pid, 'google', true)) { ?> checked  <?php } ?>>
          </td>
        </tr>
        <tr>
          <td colspan="3">
            <strong>BloggBuddy</strong>:
            <img style="height:12px" src="<?php echo plugins_url('/bloggbuddy/image/google.png'); ?>" />
            <label for="pr">PR</label> <input class="google-search" type="checkbox" id="pr" name="pr" value="pr" <?php if (get_post_meta($pid, 'pr', true) == 'pr') { ?> checked  <?php } ?>>

            <img style="height:12px" src="<?php echo plugins_url('/bloggbuddy/image/google.png'); ?>" />
            <label for="bl">BL</label> <input class="google-search" type="checkbox" id="bl" name="bl" value="bl" <?php if (get_post_meta($pid, 'bl', true) == 'bl') { ?> checked  <?php } ?>>

            <img style="height:12px" src="<?php echo plugins_url('/bloggbuddy/image/alexa.png'); ?>" />
            <label for="ar">Rank</label> <input class="google-search" type="checkbox" id="ar" name="ar" value="ar" <?php if (get_post_meta($pid, 'ar', true) == 'ar') { ?> checked  <?php } ?>>
          </td>
        </tr>
      </table>
    </div>
    <div class="wdm_update_container" style='width:35% !important;float:left'>
      <div>
        <label class="wdm_lbl">Do Not Find : </label>
        <br />
        <textarea class='wdm_exclude_list' placeholder="Enter exclude list separated with comma" rows="4"
                  cols="20"
                  name='wdm_exclude_list_container'><?php
                    $exclude_result = get_post_meta($pid, 'wdm_exclude_result', true);
                    if (!empty($exclude_result))
                      echo trim($exclude_result);
                    ?></textarea>
      </div>
    </div>
    <div style='width:20% !important;float:left'>
      <input type="button" value="Update Keyword" class="button button-primary button-large wdm_update_btn"
             name="wdm_update_keywords">
      <span class="wdm_image_container"></span>
    </div>
    <div style="clear:both"></div>
    <div style="float:right;margin-right:40px"><a href="http://bloggbuddy.com/register/" target="_blank">BloggBuddy Support</a></div>
    <div style="clear:both"></div>
  </div>
  <div class="wdm_keyword_results">
    <?php
    //code to fetch links stored in database
    if ($opt_link = get_post_meta($pid, 'keywords', true)) {
      echo "<h4>Keyword + Commentluv:</h4>";
      $arr = array();
      $arr = explode(',', $opt_link);
      $arr = array_unique($arr);
      foreach ($arr as $a) {
        echo '<p style="color:red; font-weight:bold;">Please Upgrade to Use Premium Features. <a target="_blank" href="http://bloggbuddy.com/">Click here to Upgrade.</a></p>';
        break;
      }

      if (get_post_meta($pid, 'pr', true) == 'pr') {
        ?>
        <strong>BloggBuddy</strong>:
        <img style="height:12px" src="<?php echo plugins_url('/bloggbuddy/image/google.png'); ?>" />
        <?php
        $prank = new PR();
        $pr = $prank->get_google_pagerank($a);
        ?>
        <?php
        echo 'PR ';
        echo $pr == '' ? '<span style="color:#0074a2">0</span>' : '<span style="color:#0074a2">' . $pr . '</span>';
      }
      ?>

      <?php if (get_post_meta($pid, 'bl', true) == 'bl') { ?>
        <img style="height:12px" src="<?php echo plugins_url('/bloggbuddy/image/google.png'); ?>" />
        <?php $bl = GoogleBL($a); ?>
        <?php
        echo 'BL ';
        echo $bl == '' ? '<a target="_blank" href="http://www.majesticseo.com/reports/site-explorer?folder=&IndexDataSource=F&q=' . $a . '"><span style="color:#0074a2">BL</span></a>' : '<a target="_blank" href="http://www.majesticseo.com/reports/site-explorer?folder=&IndexDataSource=F&q=' . $a . '"><span style="color:#0074a2">' . $bl . '</span></a>';
      }
      ?>

      <?php if (get_post_meta($pid, 'ar', true) == 'ar') { ?>
        <img style="height:12px" src="<?php echo plugins_url('/bloggbuddy/image/alexa.png'); ?>" />
        <?php $ar = alexa_rank($a) ?>
        <?php
        echo 'Rank ';
        echo $ar == '' ? '<span style="color:#0074a2">0</span>' : '<a target="_blank" href="http://www.alexa.com/siteinfo/' . $a . '"><span style="color:#0074a2">' . $ar . '</span></a>';
      }
      ?><br /><br />
      <?php
    }

    if ($time_link = get_post_meta($pid, 'time', true)) {
      echo "<h4>Keyword + One week old:</h4>";
      $arr = array();
      $arr = explode(',', $time_link);
      $arr = array_unique($arr);
      foreach ($arr as $a) {
        echo '<p style="color:red; font-weight:bold;">Please Upgrade to Use Premium Features. <a target="_blank" href="http://bloggbuddy.com/">Click here to Upgrade.</a></p>';
        break;
      }
    }

    if ($title_link = get_post_meta($pid, 'allin', true)) {
      echo "<h4>Keyword + Allintitle:</h4>";
      $arr = array();
      $arr = explode(',', $title_link);
      $arr = array_unique($arr);
      $opl = 1;

      foreach ($arr as $a) {
        if ($a && $opl <= 2) {
          echo "$opl) <a target='_blank' href=$a>$a</a><br><br>";
        }
        $opl++;
      }

      $a = "http://www.google.com/search?q=allintitle:+$keyword_listword_list&tbm=blg";
      echo "<a class='google-search' style='cursor:pointer;'>More...</a><br><br>";
    }

    if ($google_link = get_post_meta($pid, 'google', true)) {
      echo "<h4>Keyword + Google:</h4>";
      $arr = array();
      $arr = explode(',', $google_link);

      $arr = array_unique($arr);

      $b = "";
      $op = 1;
      foreach ($arr as $a) {
        if ($a && $op <= 2) {
          echo "$op) <a target='_blank' href=$a>$a</a><br><br>";
        }
        $op++;
      }

      $a = "http://www.google.com/search?q=$keyword_listword_list&";
      echo "<a class='google-search' style='cursor:pointer;'>More...</a><br><br>";
    }
    ?></div>
  <div style="text-align:center">
    <?php echo '<a href="http://wp-commentpro.com" target="_blank"><img src="' . plugins_url('bloggbuddy/image/banner.jpg', dirname(__FILE__)) . '" width="300"></a> '; ?>
  </div>
  <?php
}

add_action('save_post', 'wdm_keyword_fun_fetch_urls');

function wdm_keyword_fun_fetch_urls() {

  $pid = get_the_id();

  $f_link = array();

  $keyword_list = isset($_POST['txt_keyword']) ? $_POST['txt_keyword'] : '';
  $opt1 = isset($_POST['comment_plug']) ? $_POST['comment_plug'] : '';
  $opt2 = isset($_POST['title_in']) ? $_POST['title_in'] : '';
  $opt3 = isset($_POST['timespan']) ? $_POST['timespan'] : '';
  $opt4 = isset($_POST['google']) ? $_POST['google'] : '';
  $opt5 = isset($_POST['pr']) ? $_POST['pr'] : '';
  $opt6 = isset($_POST['bl']) ? $_POST['bl'] : '';
  $opt7 = isset($_POST['ar']) ? $_POST['ar'] : '';
  //$opt8 = $_POST['google'];
  $keyword_list1 = str_replace(' ', '+', $keyword_list);
  $f_link = array();
  $comment_links = array();
  $time_links = array();
  $google_links = array();
  $title_links = array();
  $title_links1 = array();
  $title_links2 = array();
  $temp_links = array();
  $parts = array();

  $com = array();

  //exclude list addition
  $exclude_list = isset($_POST['wdm_exclude_list_container']) ? trim($_POST['wdm_exclude_list_container']) : '';
  $exclude_list = preg_replace("/[\n\r]/", "", $exclude_list);

  //Split exclude list into array
  if (empty($exclude_list)) {
    $exclude_result = false;
    update_post_meta($pid, 'wdm_exclude_result', '');
  } else {
    $exclude_result = true;
    update_post_meta($pid, 'wdm_exclude_result', $exclude_list);
    if (strpos($exclude_list, ',') > 0) {
      $exclude_list_arr = explode(',', $exclude_list);
    } else {
      $exclude_list_arr = explode(' ', $exclude_list);
    }
  }

  if ($opt1 != '') {
    $my_url = "http://www.google.com/search?q=$keyword_list1+%22This+blog+uses+premium+CommentLuv%22+-%22The+version+of+CommentLuv+on+this+site+is+no+longer+supported.%22";

    $c = curl_init($my_url);
    curl_setopt($c, CURLOPT_HEADER, false);
    curl_setopt($c, CURLOPT_USERAGENT, "Mozilla/5.0 (X11; U; Linux x86_64; en-ca) AppleWebKit/531.2+ (KHTML, like Gecko) Version/5.0 Safari/531.2+");
    curl_setopt($c, CURLOPT_FAILONERROR, true);
    curl_setopt($c, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($c, CURLOPT_AUTOREFERER, true);
    curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($c, CURLOPT_TIMEOUT, 10);

    $html = curl_exec($c);

    $dom = new DOMDocument();
    @$dom->loadHTML($html);

    $xpath = new DOMXPath($dom);
    $lns = array();
    $domains = array();
    $lns = $dom->getElementsByTagName('a');
    foreach ($lns as $l) {
      $url = $l->getAttribute('href');
      if (!empty($url) && strpos($url, 'google') == FALSE && strpos($url, '?') == FALSE && ($url != '#') && strpos($url, 'youtube') == FALSE) {
        // $title_links2
        if (!preg_match('/javascript/', $url)) {
          $f = 0;
          $parts = explode('/', $url);
          if (count($title_links2) > 0) {
            foreach ($title_links2 as $t) {
              $arr = array();
              $arr = explode('/', $t);
              if (strcmp($arr[2], $parts[2]) == 0) {
                $f = 1;
              }
            }
          }

          if ($f == 0) {
            if ($exclude_result == true) {
              //check against exclusion list
              $excluded = 0;
              foreach ($exclude_list_arr as $single_exclude_url) {
                if (!strpos($url, $single_exclude_url) == false) {
                  $excluded = 1;
                  break;
                } else {
                  if (strcmp($url, $single_exclude_url) == 0) {
                    $excluded = 1;
                    break;
                  }
                }
              }

              if ($excluded == 0 && !in_array(parse_url($url, PHP_URL_HOST), $domains)) {
                array_push($title_links2, $url);
                /**
                 * add the domain to our list of domains
                 * this way we can exclude this domain
                 * later.
                 */
                array_push($domains, parse_url($url, PHP_URL_HOST));
              }
            } else {
              array_push($title_links2, $url);
              /**
               * add the domain to our list of domains
               * this way we can exclude this domain
               * later.
               */
              array_push($domains, parse_url($url, PHP_URL_HOST));
            }
          }
        }
      }
    }

    foreach ($title_links2 as $t) {
      if (count($comment_links) <= 5) {
        array_push($comment_links, $t);
      } else
        break;
    }
  }

  if ($opt4 != '') {
    //https://www.google.com/search?q=allintitle%3A+affliate+marketing
    $m = 0;
    $my_url = "http://www.google.com/search?q=$keyword_list1";
    $c = curl_init($my_url);
    curl_setopt($c, CURLOPT_HEADER, false);
    curl_setopt($c, CURLOPT_USERAGENT, "Mozilla/5.0 (X11; U; Linux x86_64; en-ca) AppleWebKit/531.2+ (KHTML, like Gecko) Version/5.0 Safari/531.2+");
    curl_setopt($c, CURLOPT_FAILONERROR, true);
    curl_setopt($c, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($c, CURLOPT_AUTOREFERER, true);
    curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($c, CURLOPT_TIMEOUT, 10);

    $html = curl_exec($c);
    $dom = new DOMDocument();
    @$dom->loadHTML($html);
    $xpath = new DOMXPath($dom);
    $lns = array();
    $domains = array();
    $lns = $dom->getElementsByTagName('a');
    foreach ($lns as $l) {
      $url = $l->getAttribute('href');
      if (!empty($url) && strpos($url, 'google') == FALSE && strpos($url, 'youtube') == FALSE && strpos($url, '?') == FALSE && ($url != '#')) {
        if (count($google_links) < 5) {
          if ($exclude_result == true) {
            //check against exclusion list
            $excluded = 0;
            foreach ($exclude_list_arr as $single_exclude_url) {
              if (!strpos($url, $single_exclude_url) == false) {
                $excluded = 1;
                break;
              } else {
                if (strcmp($url, $single_exclude_url) == 0) {
                  $excluded = 1;
                  break;
                }
              }
            }

            if ($excluded == 0 && !in_array(parse_url($url, PHP_URL_HOST), $domains)) {
              array_push($google_links, $url);
              /**
               * add the domain to our list of domains
               * this way we can exclude this domain
               * later.
               */
              array_push($domains, parse_url($url, PHP_URL_HOST));
            }
          } else {
            array_push($google_links, $url);
            /**
             * add the domain to our list of domains
             * this way we can exclude this domain
             * later.
             */
            array_push($domains, parse_url($url, PHP_URL_HOST));
          }
        } else {
          break;
        }
      }
    }
  }

  if ($opt3 != '') {
    //https://www.google.com/search?q=allintitle%3A+affliate+marketing
    $m = 0;
    $my_url = "http://www.google.com/search?q=$keyword_list1&tbs=qdr:w&tbm=blg";
    $c = curl_init($my_url);
    curl_setopt($c, CURLOPT_HEADER, false);
    curl_setopt($c, CURLOPT_USERAGENT, "Mozilla/5.0 (X11; U; Linux x86_64; en-ca) AppleWebKit/531.2+ (KHTML, like Gecko) Version/5.0 Safari/531.2+");
    curl_setopt($c, CURLOPT_FAILONERROR, true);
    curl_setopt($c, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($c, CURLOPT_AUTOREFERER, true);
    curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($c, CURLOPT_TIMEOUT, 10);

    $html = curl_exec($c);
    $dom = new DOMDocument();
    @$dom->loadHTML($html);
    $xpath = new DOMXPath($dom);
    $lns = array();
    $domains = array();
    $lns = $dom->getElementsByTagName('a');
    foreach ($lns as $l) {
      $url = $l->getAttribute('href');
      if (!empty($url) && strpos($url, 'google') == FALSE && strpos($url, 'youtube') == FALSE && strpos($url, '?') == FALSE && ($url != '#')) {
        if (count($time_links) < 5) {
          if ($exclude_result == true) {
            //check against exclusion list
            $excluded = 0;
            foreach ($exclude_list_arr as $single_exclude_url) {
              if (!strpos($url, $single_exclude_url) == false) {
                $excluded = 1;
                break;
              } else {
                if (strcmp($url, $single_exclude_url) == 0) {
                  $excluded = 1;
                  break;
                }
              }
            }

            if ($excluded == 0 && !in_array(parse_url($url, PHP_URL_HOST), $domains)) {
              array_push($time_links, $url);
              /**
               * add the domain to our list of domains
               * this way we can exclude this domain
               * later.
               */
              array_push($domains, parse_url($url, PHP_URL_HOST));
            }
          } else {
            array_push($time_links, $url);
            /**
             * add the domain to our list of domains
             * this way we can exclude this domain
             * later.
             */
            array_push($domains, parse_url($url, PHP_URL_HOST));
          }
        } else {
          break;
        }
      }
    }
  }

  if ($opt2 != '') {
    $k_array = array();
    $my_url = "http://www.google.com/search?q=allintitle:+$keyword_list1&tbm=blg";
    $c = curl_init($my_url);
    curl_setopt($c, CURLOPT_HEADER, false);
    curl_setopt($c, CURLOPT_USERAGENT, "Mozilla/5.0 (X11; U; Linux x86_64; en-ca) AppleWebKit/531.2+ (KHTML, like Gecko) Version/5.0 Safari/531.2+");
    curl_setopt($c, CURLOPT_FAILONERROR, true);
    curl_setopt($c, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($c, CURLOPT_AUTOREFERER, true);
    curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($c, CURLOPT_TIMEOUT, 10);
    $response = curl_exec($c);
    //$response =file_get_contents($my_url);
    $dom = new DOMDocument();
    @$dom->loadHTML($response);
    $xpath = new DOMXPath($dom);

    $lns = array();
    $domains = array();
    $lns = $dom->getElementsByTagName('a');
    foreach ($lns as $l) {
      $url = $l->getAttribute('href');
      if (strpos($url, 'http://') !== false && strpos($url, 'tab=') === false && strpos($url, 'youtube') === false && strpos($url, 'google.com') === false && strpos($url, 'allintitle') === false && strpos($url, 'cache') === false && $url != '#') {
        if ($url) {
          if (count($title_links) < 5) {

            if ($exclude_result == true) {

              //check against exclusion list



              $excluded = 0;



              foreach ($exclude_list_arr as $single_exclude_url) {

                if (!strpos($url, $single_exclude_url) == false) {

                  $excluded = 1;

                  break;
                } else {

                  if (strcmp($url, $single_exclude_url) == 0) {

                    $excluded = 1;

                    break;
                  }
                }
              }



              if ($excluded == 0 && !in_array(parse_url($url, PHP_URL_HOST), $domains)) {

                array_push($title_links, $url);



                /**

                 * add the domain to our list of domains

                 * this way we can exclude this domain

                 * later.

                 */
                array_push($domains, parse_url($url, PHP_URL_HOST));
              }
            } else {

              array_push($title_links, $url);



              /**

               * add the domain to our list of domains

               * this way we can exclude this domain

               * later.

               */
              array_push($domains, parse_url($url, PHP_URL_HOST));
            }
          }
        } else
          break;
      }
    }
  }





  update_post_meta($pid, 'key', $keyword_list);

  if ($opt1) {

    $f_link = implode(',', $comment_links);

    update_post_meta($pid, 'commentluv', $opt1);

    update_post_meta($pid, 'keywords', $f_link);
  } else {

    update_post_meta($pid, 'commentluv', '');

    update_post_meta($pid, 'keywords', '');
  }

  if ($opt2) {

    $title = implode(',', $title_links);

    update_post_meta($pid, 'allin', $title);

    update_post_meta($pid, 'title_url', $opt2);
  } else {

    update_post_meta($pid, 'allin', '');

    update_post_meta($pid, 'title_url', '');
  }



  if ($opt3) {

    $time = implode(',', $time_links);

    update_post_meta($pid, 'time', $time);

    update_post_meta($pid, 'timespan', $opt3);
  } else {



    update_post_meta($pid, 'time', '');

    update_post_meta($pid, 'timespan', '');
  }



  if ($opt4) {

    $g_link = implode(',', $google_links);

    update_post_meta($pid, 'google', $g_link);

    update_post_meta($pid, 'g_keywords', $opt4);
  } else {



    update_post_meta($pid, 'google', '');

    update_post_meta($pid, 'g_keywords', '');
  }


  if ($opt5 == 'pr') {

    update_post_meta($pid, 'pr', $opt5);
  } else {
    update_post_meta($pid, 'pr', '');
  }
  if ($opt6 == 'bl') {

    update_post_meta($pid, 'bl', $opt6);
  } else {
    update_post_meta($pid, 'bl', '');
  }
  if ($opt7 == 'ar') {
    update_post_meta($pid, 'ar', $opt7);
  } else {
    update_post_meta($pid, 'ar', '');
  }
}

add_action('wp_ajax_wdm_fetch_links', 'wdm_fetch_links_callback');
add_action('wp_ajax_nopriv_wdm_fetch_links', 'wdm_fetch_links_callback');

function wdm_fetch_links_callback() {
  $post_id = isset($_POST["post_id"]) ? $_POST["post_id"] : '';
  $re_domains = array();
  $keyword_list = isset($_POST['keywords']) ? $_POST['keywords'] : '';
  echo "<h4>Keyword List : " . $keyword_list . "</h4>";

  //update post meta
  update_post_meta($post_id, 'key', $keyword_list);

  //Replace spaces by '+'
  if (strpos($keyword_list, ' ') > 0) {
    $keyword_list = str_replace(' ', '+', $keyword_list);
  }

  //Replace ',' by '+'
  if (strpos($keyword_list, ',') > 0) {
    $keyword_list = str_replace(',', '+', $keyword_list);
  }

  //code to fetch other details
  $wdm_commentluv = isset($_POST['Commentluv']) ? $_POST['Commentluv'] : '';
  $wdm_timespan = isset($_POST['timespan']) ? $_POST['timespan'] : '';
  $wdm_allintitle = isset($_POST['Allintitle']) ? $_POST['Allintitle'] : '';
  $wdm_google = isset($_POST['google']) ? $_POST['google'] : '';
  $wdm_comment_option_value = isset($_POST['comment_value']) ? $_POST['comment_value'] : '';
  $wdm_timespan_option_value = isset($_POST['timespan_value']) ? $_POST['timespan_value'] : '';
  $wdm_title_option_value = isset($_POST['title_value']) ? $_POST['title_value'] : '';
  $wdm_google_option_value = isset($_POST['google_value']) ? $_POST['google_value'] : '';
  $exclude_list = isset($_POST['exclude_list']) ? $_POST['exclude_list'] : '';

  //Split exclude list into array
  if (empty($exclude_list)) {
    $exclude_result = false;
    update_post_meta($post_id, 'wdm_exclude_result', '');
  } else {
    $exclude_result = true;
    update_post_meta($post_id, 'wdm_exclude_result', $exclude_list);
    if (strpos($exclude_list, ',') > 0) {
      $exclude_list_arr = explode(',', $exclude_list);
    } else {
      $exclude_list_arr = explode(' ', $exclude_list);
    }
  }

  //Fetch result
  //comment option
  if ($wdm_commentluv == 'true') {
    echo "<h4>Keyword + Commentluv</h4>";
    $search_url = "http://www.google.com/search?q=$keyword_list+%22This+blog+uses+premium+CommentLuv%22+-%22The+version+of+CommentLuv+on+this+site+is+no+longer+supported.%22";

    $c = curl_init($search_url);
    curl_setopt($c, CURLOPT_HEADER, false);
    curl_setopt($c, CURLOPT_USERAGENT, "Mozilla/5.0 (X11; U; Linux x86_64; en-ca) AppleWebKit/531.2+ (KHTML, like Gecko) Version/5.0 Safari/531.2+");
    curl_setopt($c, CURLOPT_FAILONERROR, true);
    curl_setopt($c, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($c, CURLOPT_AUTOREFERER, true);
    curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($c, CURLOPT_TIMEOUT, 10);

    $html = curl_exec($c);

    $desired_title_links = array();
    $domains = array();
    if (preg_match_all('|<h3 class="r"><a href="/url\?q=(.+?)&amp;.*?"|', $html, $matches)) {
      $url_matches = $matches[1];

      for ($i = 0; $i < count($url_matches); $i++) {
        $url = $url_matches[$i];
        $found = 0;

        $parts = explode('/', $url);
        if ($url == '' || strpos($url, 'youtube.com') !== false) {
          continue;
        }
        if (count($desired_title_links) > 0) {
          foreach ($desired_title_links as $title_link) {
            $arr = explode('/', $title_link);
            if (isset($parts[2]) && isset($arr[2]) && strcmp($arr[2], $parts[2]) == 0) {
              $found = 1;
            }
          }
        }

        if ($found == 0) {
          if (count($desired_title_links) < 5) {
            if ($exclude_result == true) {
              //check against exclusion list
              $excluded = 0;
              foreach ($exclude_list_arr as $single_exclude_url) {
                if (!strpos($url, $single_exclude_url) == false) {
                  $excluded = 1;
                  break;
                } else {
                  if (strcmp($url, $single_exclude_url) == 0) {
                    $excluded = 1;
                    break;
                  }
                }
              }

              if ($excluded == 0 && !in_array(parse_url($url, PHP_URL_HOST), $domains)) {
                array_push($desired_title_links, $url);

                /**
                 * add the domain to our list of domains
                 * this way we can exclude this domain
                 * later.
                 */
                array_push($domains, parse_url($url, PHP_URL_HOST));
              }
            } else {
              array_push($desired_title_links, $url);

              /**
               * add the domain to our list of domains
               * this way we can exclude this domain
               * later.
               */
              array_push($domains, parse_url($url, PHP_URL_HOST));
            }
          } else {
            break;
          }
        }
      }
    }

    //update metadata, if result found
    if (!empty($desired_title_links)) {
      //update post meta
      update_post_meta($post_id, 'commentluv', $wdm_comment_option_value);
      update_post_meta($post_id, 'keywords', implode(',', $desired_title_links));

      //display result
      $desired_link_counter = 1;

      foreach ($desired_title_links as $link) {
        echo '<p style="color:red; font-weight:bold;">Please Upgrade to Use Premium Features. <a target="_blank" href="http://bloggbuddy.com/">Click here to Upgrade.</a></p>';
        break;
      }
    } else {
      //update post meta
      update_post_meta($post_id, 'commentluv', '');
      update_post_meta($post_id, 'keywords', '');
      echo "No results Found.<br>";
    }
  } else {

    //update post meta
    update_post_meta($post_id, 'commentluv', '');
    update_post_meta($post_id, 'keywords', '');
  }

  //Timespan option
  if ($wdm_timespan == 'true') {
    echo "<h4>Keyword + One week old</h4>";
    $search_url = "http://www.google.com/search?q=$keyword_list&tbs=qdr:w&tbm=blg&gws_rd=ssl";

    $c = curl_init($search_url);
    curl_setopt($c, CURLOPT_HEADER, false);
    curl_setopt($c, CURLOPT_USERAGENT, "Mozilla/5.0 (X11; U; Linux x86_64; en-ca) AppleWebKit/537.3+ (KHTML, like Gecko) Chrome/37.0.2054.3 Safari/537.3+");
    curl_setopt($c, CURLOPT_FAILONERROR, true);
    curl_setopt($c, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($c, CURLOPT_AUTOREFERER, true);
    curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($c, CURLOPT_TIMEOUT, 10);

    $html = curl_exec($c);

    $lns = array();
    $domains = array();
    $time_links = array();
    $link_counter = 0;
    if (preg_match_all('|<h3 class="r"><a href="(.+?)"|', $html, $matches)) {
      $url_matches = $matches[1];
      for ($i = 0; $i < count($url_matches); $i++) {
        $url = urldecode($url_matches[$i]);
        $link_counter = $link_counter + 1;
        if ($url == '' || strpos($url, 'youtube.com') != FALSE) {
          continue;
        }

        if (!in_array(parse_url($url, PHP_URL_HOST), $re_domains)) {
          $re_domains[] = parse_url($url, PHP_URL_HOST);
          if (count($time_links) < 5) {
            if ($exclude_result == true) {
              //check against exclusion list
              $excluded = 0;
              foreach ($exclude_list_arr as $single_exclude_url) {
                if (!strpos($url, $single_exclude_url) == false) {
                  $excluded = 1;
                  break;
                } else {
                  if (strcmp($url, $single_exclude_url) == 0) {
                    $excluded = 1;
                    break;
                  }
                }
              }

              if ($excluded == 0 && !in_array(parse_url($url, PHP_URL_HOST), $domains)) {
                array_push($time_links, $url);
                /**
                 * add the domain to our list of domains
                 * this way we can exclude this domain
                 * later.
                 */
                array_push($domains, parse_url($url, PHP_URL_HOST));
              }
            } else {
              array_push($time_links, $url);
              /**
               * add the domain to our list of domains
               * this way we can exclude this domain
               * later.
               */
              array_push($domains, parse_url($url, PHP_URL_HOST));
            }
          } else {
            break;
          }
        }
      }
    }


    if (!empty($time_links)) {
      //update post meta
      update_post_meta($post_id, 'time', implode(',', $time_links));
      update_post_meta($post_id, 'timespan', $wdm_timespan_option_value);
      //display the result
      $selected_link_counter = 1;

      foreach ($time_links as $time_link) {
        echo '<p style="color:red; font-weight:bold;">Please Upgrade to Use Premium Features. <a target="_blank" href="http://bloggbuddy.com/">Click here to Upgrade.</a></p>';
        break;
      }
    } else {
      //update post meta
      update_post_meta($post_id, 'time', '');
      update_post_meta($post_id, 'timespan', '');
      echo "No results Found.<br>";
    }
  } else {
    //update post meta
    update_post_meta($post_id, 'time', '');
    update_post_meta($post_id, 'timespan', '');
  }

  //Allintitle
  if ($wdm_allintitle == 'true') {
    echo "<h4>Keyword + Allintitle</h4>";

    $allintitle_links = array();
    $search_url = "http://www.google.com/search?q=allintitle:+$keyword_list&tbm=blg&gws_rd=ssl";
    $c = curl_init($search_url);

    curl_setopt($c, CURLOPT_HEADER, false);
    curl_setopt($c, CURLOPT_USERAGENT, "Mozilla/5.0 (X11; U; Linux x86_64; en-ca) AppleWebKit/537.3+ (KHTML, like Gecko) Chrome/37.0.2054.3 Safari/537.3+");
    curl_setopt($c, CURLOPT_FAILONERROR, true);
    curl_setopt($c, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($c, CURLOPT_AUTOREFERER, true);
    curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($c, CURLOPT_TIMEOUT, 10);

    $html = curl_exec($c);

    $lns = array();
    $domains = array();
    if (preg_match_all('|<h3 class="r"><a href="(.+?)"|', $html, $matches)) {
      $url_matches = $matches[1];
      for ($i = 0; $i < count($url_matches); $i++) {
        $url = urldecode($url_matches[$i]);
        if ($url == '' || strpos($url, 'youtube.com') !== false ||
                strpos($url, 'allintitle') !== false || strpos($url, 'google.com') !== false) {
          continue;
        }

        if (!in_array(parse_url($url, PHP_URL_HOST), $re_domains)) {
          $re_domains[] = parse_url($url, PHP_URL_HOST);
          if (count($allintitle_links) < 5) {
            if ($exclude_result == true) {
              //check against exclusion list
              $excluded = 0;
              foreach ($exclude_list_arr as $single_exclude_url) {
                if (!strpos($url, $single_exclude_url) == false) {
                  $excluded = 1;
                  break;
                } else {
                  if (strcmp($url, $single_exclude_url) == 0) {
                    $excluded = 1;
                    break;
                  }
                }
              }

              if ($excluded == 0 && !in_array(parse_url($url, PHP_URL_HOST), $domains)) {
                array_push($allintitle_links, $url);
                /**
                 * add the domain to our list of domains
                 * this way we can exclude this domain
                 * later.
                 */
                array_push($domains, parse_url($url, PHP_URL_HOST));
              }
            } else {
              array_push($allintitle_links, $url);
              /**
               * add the domain to our list of domains
               * this way we can exclude this domain
               * later.
               */
              array_push($domains, parse_url($url, PHP_URL_HOST));
            }
          } else {
            break;
          }
        }
      }
    }

    if (!empty($allintitle_links)) {
      //update post meta
      update_post_meta($post_id, 'allin', implode(',', $allintitle_links));
      update_post_meta($post_id, 'title_url', $wdm_title_option_value);

      //display result
      $selected_title_counter = 1;
      foreach ($allintitle_links as $single_allintitle) {
        echo $selected_title_counter . ") <a href='" . $single_allintitle . "' target='_blank'>" . $single_allintitle . "</a><br>";
        ?>
        <?php if (isset($_POST['pr']) && $_POST['pr'] != '') { ?>
          <strong>BloggBuddy</strong>:
          <img style="height:12px" src="<?php echo plugins_url('/bloggbuddy/image/google.png'); ?>" />
          <?php
          $prank = new PR();
          $pr = $prank->get_google_pagerank($single_allintitle);
          ?>
          <?php
          echo 'PR ';
          echo $pr == '' ? '<span style="color:#0074a2">0</span>' : '<span style="color:#0074a2">' . $pr . '</span>';
        }
        ?>
        <?php if (isset($_POST['bl']) && $_POST['bl'] != '') { ?>
          <img style="height:12px" src="<?php echo plugins_url('/bloggbuddy/image/google.png'); ?>" />
          <?php $bl = GoogleBL($single_allintitle); ?>
          <?php
          echo 'BL ';
          echo $bl == '' ? '<a target="_blank" href="http://www.majesticseo.com/reports/site-explorer?folder=&IndexDataSource=F&q=' . $single_allintitle . '"><span style="color:#0074a2">BL</span></a>' : '<a target="_blank" href="http://www.majesticseo.com/reports/site-explorer?folder=&IndexDataSource=F&q=' . $single_allintitle . '"><span style="color:#0074a2">' . $bl . '</span></a>';
        }
        ?>
        <?php if (isset($_POST['ar']) && $_POST['ar'] != '') { ?>
          <img style="height:12px" src="<?php echo plugins_url('/bloggbuddy/image/alexa.png'); ?>" />
          <?php $ar = alexa_rank($single_allintitle) ?>
          <?php
          echo 'Rank ';
          echo $ar == '' ? '<span style="color:#0074a2">0</span>' : '<a target="_blank" href="http://www.alexa.com/siteinfo/' . $single_allintitle . '"><span style="color:#0074a2">' . $ar . '</span></a>';
        }
        ?><br /><br />
        <?php
        $selected_title_counter += 1;
      }
      $more_link = "http://www.google.com/search?q=allintitle:+$keyword_list&tbm=blg";
      echo "<br><a style='cursor:pointer;' class='google-search'>More...</a><br><br>";
    } else {
      //update post meta
      update_post_meta($post_id, 'allin', '');
      update_post_meta($post_id, 'title_url', '');
      echo "No results Found.<br>";
    }
  } else {
//update post meta
    update_post_meta($post_id, 'allin', '');
    update_post_meta($post_id, 'title_url', '');
  }
  //Google option

  if ($wdm_google == 'true') {
    echo "<h4>Keyword + Google</h4>";
    $search_url = "http://www.google.com/search?q=$keyword_list";
    $c = curl_init($search_url);

    curl_setopt($c, CURLOPT_HEADER, false);
    curl_setopt($c, CURLOPT_USERAGENT, "Mozilla/5.0 (X11; U; Linux x86_64; en-ca) AppleWebKit/531.2+ (KHTML, like Gecko) Version/5.0 Safari/531.2+");
    curl_setopt($c, CURLOPT_FAILONERROR, true);
    curl_setopt($c, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($c, CURLOPT_AUTOREFERER, true);
    curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($c, CURLOPT_TIMEOUT, 10);

    $html = curl_exec($c);

    $lns = array();
    $domains = array();
    $link_counter = 0;
    $google_links = array();
    if (preg_match_all('|<h3 class="r"><a href="/url\?q=(.+?)&amp;.*?"|', $html, $matches)) {
      $url_matches = $matches[1];
      for ($i = 0; $i < count($url_matches); $i++) {
        $url = urldecode($url_matches[$i]);
        $link_counter = $link_counter + 1;
        if ($url == '' || strpos($url, 'youtube.com') !== false) {
          continue;
        }

        if (!in_array(parse_url($url, PHP_URL_HOST), $re_domains)) {
          $re_domains[] = parse_url($url, PHP_URL_HOST);
          if (count($google_links) < 5) {
            if ($exclude_result == true) {
              //check against exclusion list
              $excluded = 0;
              foreach ($exclude_list_arr as $single_exclude_url) {
                if (!strpos($url, $single_exclude_url) == false) {
                  $excluded = 1;
                  break;
                } else {
                  if (strcmp($url, $single_exclude_url) == 0) {
                    $excluded = 1;
                    break;
                  }
                }
              }
              if ($excluded == 0 && !in_array(parse_url($url, PHP_URL_HOST), $domains)) {
                array_push($google_links, $url);

                /**
                 * add the domain to our list of domains
                 * this way we can exclude this domain
                 * later.
                 */
                array_push($domains, parse_url($url, PHP_URL_HOST));
              }
            } else {
              array_push($google_links, $url);

              /**
               * add the domain to our list of domains
               * this way we can exclude this domain
               * later.
               */
              array_push($domains, parse_url($url, PHP_URL_HOST));
            }
          } else {
            break;
          }
        }
      }
    }

    if (!empty($google_links)) {
      //update post meta
      update_post_meta($post_id, 'google', implode(',', $google_links));
      update_post_meta($post_id, 'g_keywords', $wdm_google_option_value);

      //display the result
      $selected_link_counter = 1;
      foreach ($google_links as $google_link) {
        echo $selected_link_counter . ") <a href='" . $google_link . "' target='_blank'>" . $google_link . "</a><br>";
        ?>
        <?php if (isset($_POST['pr']) && $_POST['pr'] != '') { ?>
          <strong>BloggBuddy</strong>:
          <img style="height:12px" src="<?php echo plugins_url('/bloggbuddy/image/google.png'); ?>" />
          <?php
          $prank = new PR();
          $pr = $prank->get_google_pagerank($google_link);
          ?>
          <?php
          echo 'PR ';
          echo $pr == '' ? '<span style="color:#0074a2">0</span>' : '<span style="color:#0074a2">' . $pr . '</span>';
        }
        ?>

        <?php if (isset($_POST['bl']) && $_POST['bl'] != '') { ?>
          <img style="height:12px" src="<?php echo plugins_url('/bloggbuddy/image/google.png'); ?>" />
          <?php $bl = GoogleBL($google_link); ?>
          <?php
          echo 'BL ';
          echo $bl == '' ? '<a target="_blank" href="http://www.majesticseo.com/reports/site-explorer?folder=&IndexDataSource=F&q=' . $google_link . '"><span style="color:#0074a2">BL</span></a>' : '<a target="_blank" href="http://www.majesticseo.com/reports/site-explorer?folder=&IndexDataSource=F&q=' . $google_link . '"><span style="color:#0074a2">' . $bl . '</span></a>';
        }
        ?>

        <?php if (isset($_POST['ar']) && $_POST['ar'] != '') { ?>
          <img style="height:12px" src="<?php echo plugins_url('/bloggbuddy/image/alexa.png'); ?>" />
          <?php $ar = alexa_rank($google_link) ?>
          <?php
          echo 'Rank ';
          echo $ar == '' ? '<span style="color:#0074a2">0</span>' : '<a target="_blank" target="_blank" href="http://www.alexa.com/siteinfo/' . $google_link . '"><span style="color:#0074a2">' . $ar . '</span></a>';
        }
        ?><br /><br />
        <?php
        $selected_link_counter = $selected_link_counter + 1;
      }
      $more_link = "http://www.google.com/search?q=$keyword_list";

      echo "<br><a class='google-search' style='cursor:pointer;'>More...</a><br><br>";
    } else {
      //update post meta
      update_post_meta($post_id, 'google', '');
      update_post_meta($post_id, 'g_keywords', '');
      echo "No results Found.<br>";
    }
  } else {
//update post meta
    update_post_meta($post_id, 'google', '');
    update_post_meta($post_id, 'g_keywords', '');
  }
  die();
}
