
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <title>New translated content on Khan Academy</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="">
    <meta name="author" content="">

    <!-- Le styles -->
    <link href="/css/bootstrap.min.css" rel="stylesheet">
    <link href="/css/chosen.css" rel="stylesheet">
    <style type="text/css">
      .hero-unit h1 {font-size: 20pt;}
      /* zeroclipboard */
      [data-clipboard-text] {width: 16px; height: 16px; border: none; text-align:center; background: url('/img/copy.png') no-repeat; opacity: .6}
      [data-clipboard-text].zeroclipboard-is-hover {opacity: 1}
      [data-clipboard-text].zeroclipboard-is-active {background-color: green}
      ul {list-style: none;}
      li {margin-bottom: 1ex;}
      h2 {font-size: 14pt;}

      .hero-unit {padding: 20px;}
      h1 {padding-bottom: 1ex;}
      .chzn-container {margin-top: 1px; font-size: 10pt;}

      table tr td {white-space: nowrap; overflow: hidden; max-width: 500px;}
      table tr td:first-of-type {width: 120px;}
      table tr td:nth-of-type(2) {width: 410px;}
    </style>

    <!-- HTML5 shim, for IE6-8 support of HTML5 elements -->
    <!--[if lt IE 9]>
      <script src="../assets/js/html5shiv.js"></script>
    <![endif]-->

    <!-- Fav and touch icons -->
    <link rel="apple-touch-icon-precomposed" sizes="144x144" href="../assets/ico/apple-touch-icon-144-precomposed.png">
    <link rel="apple-touch-icon-precomposed" sizes="114x114" href="../assets/ico/apple-touch-icon-114-precomposed.png">
      <link rel="apple-touch-icon-precomposed" sizes="72x72" href="../assets/ico/apple-touch-icon-72-precomposed.png">
                    <link rel="apple-touch-icon-precomposed" href="../assets/ico/apple-touch-icon-57-precomposed.png">
                                   <link rel="shortcut icon" href="../assets/ico/favicon.png">
  </head>

  <body>

    <div class="navbar navbar-static-top">
      <div class="navbar-inner">
        <div class="container">
          <button type="button" class="btn btn-navbar" data-toggle="collapse" data-target=".nav-collapse">
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
          </button>
          <a class="brand" href="#">Translation status</a>
          <div class="nav-collapse collapse pull-right">
            <?php
            $mtime = filemtime(__DIR__ . '/../data/.');
            $delta = time() - $mtime;
            ?>
            <p class="navbar-text">Last update: <span class="label label-<?php echo $delta < 3600 * 24 ? 'success' : ($delta < 3600 * 24 * 3 ? 'warning' : 'error') ?>"><?php echo timeAgoInWords($mtime); ?></span></p>
        </div>
      </div>
    </div>

    <div class="container">

      <!-- Main hero unit for a primary marketing message or call to action -->
      <div class="hero-unit">
        <h1>Find what videos has recenly been translated&mdash;in a user-friendly way.</h1>
        <form class="form-search" action="index.php">
          <select type="text" name="language" class="input-large chzn-select" data-placeholder="Choose your language&hellip;">
            <?php
            foreach (scandir(__DIR__ . '/../data') as $file) {
              if (strpos($file, 'times_') === 0) {
                $language = substr($file, 6);
                $forlg = ucwords(str_replace('-', ' ', $language));
                $raw = file_get_contents(__DIR__ . "/../data/$file");

                $data = [];
                foreach (explode("\n", $raw) as $line) {
                  if (!$line)
                    continue;
                  list($id) = explode(" ", $line);
                  $data[] = $id;
                }

                $count = count(array_unique($data));
                echo "<option value=\"$language\"" . (isset($_GET['language']) && $language === $_GET['language'] ? ' selected="selected"' : '') . ">$forlg ($count)</option>\n";
              }
            }
            ?>
          </select>
          <button type="submit" class="btn btn-primary">Show content</button>
        </form>
      </div>

      <?php
      if (isset($_GET['language'])):
      ?>
        <table class="table table-striped">
        <?php
          $language = str_replace('.', '', str_replace('/', '', $_GET['language']));
          $raw = file_get_contents(__DIR__ . "/../data/times_$language");
          $data = [];
          foreach (explode("\n", $raw) as $line) {
            if (!$line)
              continue;
            list($id, $date, $time) = explode(" ", $line);
            $data[$id] = strToTime("$date $time");
          }
          arsort($data);
          $last_date = NULL;
          foreach ($data as $youtube_id => $time) {
            $date = date('Y-m-d', $time);
            if ($date !== $last_date) {
              $count = 0;
              foreach ($data as $i => $t) {
                if ($date === date('Y-m-d', $t))
                  $count++;
              }
              $last_date = $date;
              echo "</table>\n<h2>" .
                (
                  $date === date('Y-m-d') ?
                    "Today" :
                  $date === date('Y-m-d', time() - 3600 * 24) ?
                    "Yesterday" :
                    "$date (" . timeAgoInWords($time) . ")"
                ) .
                " ($count)</h2>\n<table class=\"table table-striped\">";
            }
            try {
              $meta = getYoutube($youtube_id);
              $amara = getAmara($youtube_id);
            } catch (Exception $e) {
              echo "<tr><td colspan=\"3\">API limit exceeded, please try later for more results</td></tr>";
              break;
            }
            $amara_url = "http://www.amara.org/cs/videos/{$amara->video_id}/cs/";
            $amara_text = truncate($amara->subtitles->title, 6) ?: "amara link";
            $youtube_text = truncate($meta->data->title, 6) ?: "khan academy link";
            echo "<tr><td><button title=\"test\" data-clipboard-text=\"$youtube_id\"></button> <a href=\"http://www.youtube.com/watch/?v=$youtube_id\"><code>$youtube_id</code></a></td><td><a href=\"$amara_url\" target=\"_blank\">$amara_text</a></td><td><a href=\"http://www.khanacademy.org/video?v=$youtube_id\">$youtube_text</a></td></tr>";
          }
        ?>
        </table>
      <?php
      endif;
      ?>

      <footer>
        <p>&copy; <a href="http://twitter.com/mikulasdite" target="_blank">Mikuláš Dítě</a> 2013, released for Khanova škola Czech Republic <a href="https://khanovaskola.cz" target="_blank">khanovaskola.cz</a></p>
        <p>If you have any feature request or issue to report, please contact me <a href="http://twitter.com/mikulasdite" target="_blank">@MikulasDite</a> or at <a href="mailto:mikulas@khanovaskola.cz" target="_blank">mikulas@khanovaskola.cz</a>.
      </footer>

    </div> <!-- /container -->

    <!-- Le javascript
    ================================================== -->
    <!-- Placed at the end of the document so the pages load faster -->
    <!--<script src="/js/bootstrap.min.js"></script>-->
    <script src="/js/jquery-1.9.1.min.js"></script>
    <script src="/js/jquery-ui-1.10.3.custom.min.js"></script>
    <script src="/js/chosen.jquery.min.js"></script>
    <script src="/js/ZeroClipboard.min.js"></script>
    <script src="/js/jquery.pulse.min.js"></script>
    <script>
      $(".chzn-select").chosen({no_results_text: "Language not supported"});
      ZeroClipboard.setDefaults({moviePath: '/js/ZeroClipboard.swf'});
      var clip = new ZeroClipboard($("button[data-clipboard-text]"));
      clip.on('complete', function(client, args) {
        var sibs = $("[data-clipboard-text=\"" + args.text + "\"]").siblings('a:first-of-type').find('code');
        sibs.pulse({backgroundColor: 'rgb(200, 230, 170)'}, {pulses: 2, duration: 200});
      });
    </script>
  </body>
</html>
<?php

function truncate($text, $limit) {
  $new = $text;
  if (str_word_count($text, 0) > $limit) {
      $words = str_word_count($text, 2);
      $pos = array_keys($words);
      $new = trim(mb_substr($text, 0, $pos[$limit])) . '&hellip;';
  }
  return $new;
}

function getYoutube($youtube_id)
{
  @mkdir(__DIR__ . '/cache'); // @ - may already exist
  $cache = __DIR__ . "/cache/youtube_" . str_replace('=', '', base64_encode($youtube_id));
  $meta = NULL;
  if (file_exists($cache)) {
    $meta = unserialize(file_get_contents($cache));
  }
  if (!$meta) {
    $meta = json_decode(@file_get_contents("http://gdata.youtube.com/feeds/api/videos/$youtube_id?v=2&alt=jsonc&prettyprint=false"));
    if (!$meta) {
      throw new Exception;
    }
    file_put_contents($cache, serialize($meta));
  }
  return $meta;
}

function getAmara($youtube_id) {
  $cache = __DIR__ . "/cache/amara_" . str_replace('=', '', base64_encode($youtube_id));
  $meta = NULL;
  if (file_exists($cache)) {
    return unserialize(file_get_contents($cache));
  }
  if (!$meta) {
    $url = 'http://www.universalsubtitles.org/widget/rpc/jsonp/show_widget?video_url=' . urlencode("\"http://www.youtube.com/watch?v={$youtube_id}\"") . '&is_remote=true&base_state=%7B%22language%22%3A%22cs%22%7D&callback=';
    $res = "[" . substr(@file_get_contents($url), 1, -2) . "]"; // remove colon and parenthesis
    $meta = json_decode($res);
    $id = NULL;
    if (!$meta) {
      throw new Exception;
    }
    $meta = $meta[0];
    file_put_contents($cache, serialize($meta));
    return $meta;
  }
}

function timeAgoInWords($time) {
    if (!$time) {
        return FALSE;
    } elseif (is_numeric($time)) {
        $time = (int) $time;
    } elseif ($time instanceof DateTime) {
        $time = $time->format('U');
    } else {
        $time = strtotime($time);
    }

    $delta = time() - $time;

    $delta = round($delta / 60);
    if ($delta == 0) return 'moments ago';
    if ($delta == 1) return 'a minute ago';
    if ($delta < 45) return "$delta minutes ago";
    if ($delta < 90) return 'an hour ago';
    if ($delta < 1440) return round($delta / 60) . ' hours ago';
    if ($delta < 2880) return 'yesterday';
    if ($delta < 43200) return round($delta / 1440) . ' days ago';
    if ($delta < 86400) return 'a month ago';
    if ($delta < 525960) return round($delta / 43200) . ' months ago';
    if ($delta < 1051920) return 'a year ago';
    return round($delta / 525960) . ' years ago';
}
?>
