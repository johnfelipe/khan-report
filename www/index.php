
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
                echo "<option value=\"$language\"" . (isset($_GET['language']) && $language === $_GET['language'] ? ' selected="selected"' : '') . ">$language</option>\n";
              }
            }
            ?>
          </select>
          <button type="submit" class="btn">Search</button>
        </form>
      </div>

      <?php
      if (isset($_GET['language'])):
      ?>
      <hr>
        <ul>
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
              $last_date = $date;
              echo "</ul>$date<ul>";
            }
            @mkdir(__DIR__ . '/cache'); // @ - may already exist
            $cache = __DIR__ . "/cache/" . str_replace('=', '', base64_encode($youtube_id));
            $meta = NULL;
            if (file_exists($cache)) {
              $meta = unserialize(file_get_contents($cache));
            }
            if (!$meta) {
              $meta = json_decode(file_get_contents("http://gdata.youtube.com/feeds/api/videos/$youtube_id?v=2&alt=jsonc&prettyprint=false"));
              file_put_contents($cache, serialize($meta));
            }
            echo "<li><button title=\"test\" data-clipboard-text=\"$youtube_id\"></button> <a href=\"http://www.youtube.com/watch/?v=$youtube_id\"><code>$youtube_id</code></a> &mdash; <a href=\"http://www.khanacademy.org/video?v=$youtube_id\">{$meta->data->title}</a></li>";
          }
        ?>
        </ul>
      <?php
      endif;
      ?>
      <hr>

      <footer>
        <p>&copy; <a href="http://twitter.com/mikulasdite" target="_blank">Mikuláš Dítě</a> 2013, released for Khanova škola Czech Republic <a href="https://khanovaskola.cz" target="_blank">khanovaskola.cz</a></p>
        <p>If you have any feature request or issue to report, please do contant <a href="http://twitter.com/mikulasdite" target="_blank">@MikulasDite</a> or at <a href="mailto:mikulas@khanovaskola.cz" target="_blank">mikulas@khanovaskola.cz</a>.
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
