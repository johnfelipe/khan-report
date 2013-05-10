<?php

// ///
// WARNING: Excel reader requires at least 350MB of memory to load the 6MB xls file!
// ///

require __DIR__ . '/excel_reader2.php';


define('URL', 'http://khan-report.appspot.com/translations/subtitlestatus?download=1');
define('TEMP_FILE', __DIR__ . '/status.tgz');
define('DATA_DIR', __DIR__ . '/data');

$file_last = DATA_DIR . '/' . date('Y-m-d') . '.xls';
//*
echo "downloading...\n";
file_put_contents(TEMP_FILE, file_get_contents(URL));
echo "done\n";

echo "untar...\n";
if (file_exists('/Volumes')) {
	`tar zxvf status.tgz`;
} else {
	`tar zxvf /srv/sites/khan-report.khanovaskola.cz/status.tgz`;
}
echo "done\n";

echo "process temp files\n";
@mkdir(DATA_DIR); // @ - dir may exist
rename(__DIR__ . '/subtitle_data.xls', $file_last);
echo "done\n";

echo "remove temp files\n";
unlink(__DIR__ . '/subtitles.xls');
unlink(__DIR__ . '/status.tgz');
echo "done\n";
//*/
echo "read new xls\n";

$reader = new Spreadsheet_Excel_Reader($file_last);
echo "loaded\n";

$keys = [];
$rowcount = $reader->rowcount(1);
$newdata = [];

for ($row = 2; $row < $rowcount; ++$row) {
	$done = $reader->val($row, 5, 1) === 100;
	$language = $reader->val($row, 4, 1);

	if (!isset($newdata[$language])) {
		// we need to iterate over every language later
		$newdata[$language] = [];
	}
	if (!$done) {
		continue;
	}

//	$video = $reader->val($row, 2, 1);
	$youtube_id = $reader->val($row, 3, 1);

	$newdata[$language][] = $youtube_id;
}
unset($reader);
echo "done\n";

var_dump("test, removing " . $newdata['Greek'][0]);
var_dump(count($newdata['Greek']));
do {
	$k = array_search('9p10aMu1Glo', $newdata['Greek']);
	unset($newdata['Greek'][$k]);
} while ($k !== FALSE);

var_dump(count($newdata['Greek']));

echo "saving new data to files\n";
foreach ($newdata as $language => $data) {
	$language = str_replace(' ', '-', str_replace(':', '-', strToLower($language)));
	$file = DATA_DIR . "/times_$language";
	$old = getIds($file, FALSE); // @ - file might not exist

	$diff = array_diff($data, $old);
	echo "updating $language (" . count($diff) . ")\n";

	// videos that were at 100% but dropped lower (because English translation has been extended)
	$removed = array_unique(array_diff($old, $data));

	$output = '';
	foreach (explode("\n", file_get_contents($file)) as $line) {
		list($id) = explode(" ", $line);
		if (!in_array($id, $removed)) {
			$output .= "$line\n";
		}
	}

	foreach ($diff as $youtube_id) {
		$output .= "$youtube_id " . date('Y-m-d h:i') . "\n";
	}

	file_put_contents($file, $output);
}
echo "done\n";

echo "remove data file\n";
unlink($file_last);
echo "done\n";

echo "end\n";

function getIds($file)
{
	if (!file_exists($file)) {
		return [];
	}
	$data = file_get_contents($file);
	$ids = [];
	foreach (explode("\n", $data) as $line) {
		if (!$line)
			continue;
		list($id) = explode(" ", $line);
		$ids[] = $id;
	}
	return $ids;
}
