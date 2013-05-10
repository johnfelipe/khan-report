<?php

// ///
// WARNING: Excel reader requires at least 350MB of memory to load the 6MB xls file!
// ///

require __DIR__ . '/excel_reader2.php';


define('URL', 'http://khan-report.appspot.com/translations/subtitlestatus?download=1');
define('TEMP_FILE', __DIR__ . '/status.tgz');
define('DATA_DIR', __DIR__ . '/data');

echo "\n\n#############\n" . date('Y-m-d h:i:s'). "\n";

$file_last = DATA_DIR . '/' . date('Y-m-d') . '.xls';
//*
echo "downloading...\n";
file_put_contents(TEMP_FILE, file_get_contents(URL));
echo "\tdone\n";

echo "untar...\n";
if (file_exists('/Volumes')) {
	`tar zxvf /Volumes/Cifrita/Web/ka_changes/status.tgz -C /Volumes/Cifrita/Web/ka_changes/`;
} else {
	`tar zxvf /srv/sites/khan-report.khanovaskola.cz/status.tgz -C /srv/sites/khan-report.khanovaskola.cz/`;
}
echo "\tdone\n";

echo "process temp files\n";
@mkdir(DATA_DIR); // @ - dir may exist
rename(__DIR__ . '/subtitle_data.xls', $file_last);
echo "\tdone\n";

echo "remove temp files\n";
unlink(__DIR__ . '/subtitles.xls');
unlink(__DIR__ . '/status.tgz');
echo "\tdone\n";
//*/
echo "read new xls\n";

$reader = new Spreadsheet_Excel_Reader($file_last);
echo "loaded\n";

$rowcount = $reader->rowcount(1);
$youtube_ids = [];
for ($row = 2; $row < $rowcount; ++$row) {
	$youtube_ids[] = $reader->val($row, 3, 1);
}
unset($reader);
echo "\tdone\n";

echo "uniquing youtube_ids\n";
$youtube_ids = array_unique($youtube_ids);
echo "\tdone\n";

echo "saving\n";
file_put_contents('youtube_ids.dat', implode("\n", $youtube_ids));
echo "\tdone\n";

echo "remove data file\n";
unlink($file_last);
echo "\tdone\n";

echo "end\n";
