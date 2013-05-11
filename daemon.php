<?php

require __DIR__ . '/amara.php';

define('DATA_FILE', __DIR__ . '/data/translations_%s.dat');

@mkdir(__DIR__ . '/data');

// read last result
$fp = fopen(getDatafileName(), "r");
if ($fp) {
	$pos = -2; // skip first newline
	do {
		fseek($fp, $pos, SEEK_END);
		$char = fgetc($fp);
		$pos--;
	} while ($char !== "\n");
	$last = explode("\t", fgets($fp))[1];
	fclose($fp);

	unset($fp);
	unset($pos);
	unset($char);
}

$handle = fopen(__DIR__ . "/youtube_ids.dat", "r");

// seek to last result
if ($last) {
	echo "Seeking last location ($last)\n";
	while (substr(fgets($handle, 12 * 8), 0, 11) !== $last);
}

echo "Daemon started\n";
// daemon loop
for (;;) {
	if (feof($handle)) {
		file_put_contents(__DIR__ . '/cycles.dat', time() . "\n", FILE_APPEND);
		fseek($handle, 0);
	}

	$id = substr(fgets($handle, 12 * 8), 0, 11); // every line only has 11+1 1-byte chars
	$langs = [];
	foreach (getVideoTranslatedLangs($id) as $lang => $percent) {
		if ($percent >= 95) { // basically complete (chinese messing up timings etc)
			$langs[] = $lang;
		}
	}
	if ($langs) {
		file_put_contents(getDatafileName(), time() . "\t$id\t;" . implode(';', $langs) . ";\n", FILE_APPEND);
	}
	//echo "$line\n";
}

function mem() {
	echo number_format(memory_get_usage() / 1e6) . " MB\n";
}

function getDatafileName()
{
	return sprintf(DATA_FILE, date('Y-m-d'));
}
