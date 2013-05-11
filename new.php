<?php

$languages = require __DIR__ . '/www/languages.php';

foreach ($languages as $node) {
	echo ".";
	$code = $node[2];
	$t = getTranslations($code);
	$old = [];
	$new = [];
	foreach ($t as $trans) {
		$day = date('Y-m-d', $trans[0]);
		if ($day === date('Y-m-d')) {
			$new[] = $trans[1];
		} else if ($day === date('Y-m-d', time() - 3600 * 24)) {
			$old[] = $trans[1];
		}
	}
	$old = array_unique($old);
	$new = array_unique($new);

	$added = array_diff($new, $old);
	$count = count($added);
	if ($count) {
		echo "\r" . str_repeat(' ', 100) . "\r";
		echo "$node[0]: $count new videos today!\n";
	}
}

function getTranslations($language)
{
  $handle = fopen(__DIR__ . "/translations.dat", "r");
  $data = [];
  while (!feof($handle)) {
    $line = trim(fgets($handle));
    if (!$line)
      continue;

    list($time, $youtube_id, $langs) = explode("\t", $line);
    if (preg_match("~(\s|;)$language(;|$)~", $line)) { // 14 409.1 ms
    //if (in_array($language, explode(';', $langs))) { // 14 331.9 ms
      $data[] = [$time, $youtube_id];
    }
  }

  return $data;
}


