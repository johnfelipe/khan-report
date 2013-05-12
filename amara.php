<?php

define('URL_BASE', 'https://www.universalsubtitles.org');
define('API_USER', 'rullaf');
define('API_KEY', '4ed3fdc29f72ca55e460adb10be0e9f869f6c308');
define('FILTER_TEAM', 'khanacademy');

function getVideoTranslatedLangs($id)
{
	$url = 'http://www.universalsubtitles.org/widget/rpc/jsonp/show_widget?video_url=' . urlencode("\"http://www.youtube.com/watch?v={$id}\"") . '&is_remote=false&callback=';
	$res = file_get_contents($url);
    $json = json_decode("[" . substr($res, 1, -2) . "]"); // remove colon and parenthesis

if (!isset($json[0]) || !property_exists($json[0], 'drop_down_contents')) {
	return FALSE;
}

    $languages = [];
    foreach ($json[0]->drop_down_contents as $node) {
    	if (property_exists($node, 'percent_done')) {
    		$languages[$node->language] = $node->percent_done;
    	} else {
    		$languages[$node->language] = $node->is_complete ? 100 : 0;
    	}
    }

    return $languages;
}
