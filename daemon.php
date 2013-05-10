<?php

require __DIR__ . '/amara.php';

define('DATA_FILE', __DIR__ . '/translations.dat');

$handle = fopen(__DIR__ . "/youtube_ids.dat", "r");
for (;;) {
	if (feof($handle)) {
		fseek($handle, 0);
	}

	$id = substr(fgets($handle, 12 * 8), 0, 11); // every line only has 11+1 1-byte chars
	$langs = [];
	foreach (getVideoTranslatedLangs($id) as $lang => $percent) {
		if ($percent === 100) {
			$langs[] = $lang;
		}
	}
	if ($langs) {
		file_put_contents(DATA_FILE, time() . "\t$id\t" . implode(';', $langs) . "\n", FILE_APPEND);
	}
	//echo "$line\n";
}

function mem() {
	echo number_format(memory_get_usage() / 1e6) . " MB\n";
}
