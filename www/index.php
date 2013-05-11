<?php

set_time_limit(60);

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/TemplateRouter.php';
$languages = require __DIR__ . '/languages.php';

if (extension_loaded('newrelic')) {
	newrelic_set_appname('khan-report.khanovaskola.cz');
}

$configurator = new Nette\Configurator;
$configurator->enableDebugger(__DIR__ . '/../log');
$configurator->setTempDirectory(__DIR__ . '/../temp');
$configurator->addConfig(__DIR__ . '/../config.neon');
$configurator->addConfig(__DIR__ . '/../config.local.neon');
$container = $configurator->createContainer();

$container->addService('router', new TemplateRouter('templates', __DIR__ . '/../temp'));
$container->application->run();

function isDaemonRunning()
{
	$processes = (int) trim(`ps aux | grep daemon.php | grep grep -v | wc -l`);
	return $processes > 0;
}

function getDays($language)
{
	$data = [];
	foreach (getTranslations($language) as $row) {
		$day = $row->day->format('Y-m-d');
		if (!isset($data[$day])) {
			$data[$day] = [];
		}
		$data[$day][] = $row->youtube_id;
	}

	return $data;
}

function getTranslations($language)
{
	global $container;
	return $container->database->table('translation')->select('youtube_id, day')->where('language', $language)->order('day DESC, id DESC');
}

function getLanguageCounts()
{
	global $container;
	return $container->database->table('translation')->select('Count(DISTINCT youtube_id) AS count, language')->group('language')->fetchPairs('language', 'count');
}

function getLanguageCount($language)
{
	global $container;
	return $container->database->table('translation')->select('DISTINCT youtube_id')->where('language', $language)->count();
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


function getAmara($youtube_id, $lang) {
	$cache = __DIR__ . "/cache/amara_{$lang}_" . str_replace('=', '', base64_encode($youtube_id));
	$meta = NULL;

	if (file_exists($cache)) {
		$meta = unserialize(file_get_contents($cache));
		return $meta;
	}
	if (!$meta) {
		$url = 'http://www.universalsubtitles.org/widget/rpc/jsonp/show_widget?video_url=' . urlencode("\"http://www.youtube.com/watch?v={$youtube_id}\"") . '&is_remote=true&base_state=%7B%22language%22%3A%22' . $lang .'%22%7D&callback=';
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
