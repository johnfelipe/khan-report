<?php

require __DIR__ . '/amara.php';
require __DIR__ . '/vendor/autoload.php';

if (extension_loaded('newrelic')) {
	newrelic_set_appname('khan-report.khanovaskola.cz-daemon');
}

$configurator = new Nette\Configurator;
$configurator->setDebugMode(TRUE);
$configurator->setTempDirectory(__DIR__ . '/temp');
$configurator->addConfig(__DIR__ . '/config.neon');
$configurator->addConfig(__DIR__ . '/config.local.neon');
$container = $configurator->createContainer();

@mkdir(__DIR__ . '/data');

$handle = fopen(__DIR__ . "/youtube_ids.dat", "r");
$last = trim(@file_get_contents(__DIR__ . '/last_checked_youtube_id.dat')); // @ - file might not exist
if ($last) {
	echo "Seeking last location ($last)\n";
	while (substr(fgets($handle, 12 * 8), 0, 11) !== $last);
}

$verbose = isset($argv[1]) ? in_array($argv[1], ['--verbose', '-v']) : FALSE;

echo "Daemon started\n";
// daemon loop
$retry = 0;
for (;;) {
	sleep(2); // do not hammer the API
	if (feof($handle)) {
		file_put_contents(__DIR__ . '/cycles.dat', time() . "\n", FILE_APPEND);
		file_put_contents(__DIR__ . '/mem_usage.log', memory_get_usage() . "\n", FILE_APPEND);
		fseek($handle, 0);
	}

	$return_pos = ftell($handle);
	$id = substr(fgets($handle, 12 * 8), 0, 11); // every line only has 11+1 1-byte chars
	if ($verbose) echo "Checking $id\n";
	$langs = [];
	list($amara_id, $data, $json) = getVideoTranslatedLangs($id);
	try {
		$container->database->table('amara_map')->insert([
			'youtube_id' => $id,
			'amara_id' => $amara_id,
		]);
	} catch (PDOException $e) {
		if ($e->getCode() != 23000) {
			throw $e;
		}
		// else duplicate entry, ignore
	}

	if ($data === FALSE) { // dropdown not returned, query again
		if ($verbose) echo "Invalid data received\ttry #$retry\n";
		$retry++;
		if ($retry <= 3) {
			// retry
			fseek($handle, $return_pos);
			file_put_contents(__DIR__ . '/api_errors.log', time() . "\t$id\n", FILE_APPEND);
			continue;
		}
		$retry = 0;
		// skip
		continue;
	}

	foreach ($data as $lang => $percent) {
		if ($percent >= 95) { // basically complete (chinese messing up timings etc)
			$langs[] = $lang;
		}
	}

	if (in_array('cs', $langs)) {
		$container->database->table('subtitles')->where('amara_id', $amara_id)->delete();
		$container->database->table('subtitles')->insert([
			'amara_id' => $amara_id,
			'language' => 'cs',
			'label' => $json->subtitles->title,
			'subs' => json_encode($json->subtitles->subtitles),
		]);
	}

	foreach ($langs as $lang) {
		// @TODO: This is stupid solution and prevents videos that change from complete to incomplete from appearing again later
		if ($container->database->table('translation')->where('youtube_id', $id)->where('language', $lang)->count() !== 0) {
			// this video has been translated before, do not save as new
			continue;
		}
		if ($verbose) echo "\t saving new languages\n";

		try {
			$container->database->table('translation')->insert([
				'day' => date('Y-m-d'),
				'youtube_id' => $id,
				'language' => $lang,
			]);
		} catch (PDOException $e) {
			if ($e->getCode() != 23000) {
				throw $e;
			}
			// else duplicate entry, ignore
		}
	}

	file_put_contents(__DIR__ . '/last_checked_youtube_id.dat', $id);
}
