<?php

require __DIR__ . '/amara.php';
require __DIR__ . '/vendor/autoload.php';

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

echo "Daemon started\n";
// daemon loop
for (;;) {
	if (feof($handle)) {
		file_put_contents(__DIR__ . '/cycles.dat', time() . "\n", FILE_APPEND);
		file_put_contents(__DIR__ . '/mem_usage.log', memory_get_usage() . "\n", FILE_APPEND);
		fseek($handle, 0);
	}

	$id = substr(fgets($handle, 12 * 8), 0, 11); // every line only has 11+1 1-byte chars
	$langs = [];
	foreach (getVideoTranslatedLangs($id) as $lang => $percent) {
		if ($percent >= 95) { // basically complete (chinese messing up timings etc)
			$langs[] = $lang;
		}
	}

	foreach ($langs as $lang) {
		if ($container->database->table('translation')->where('day', date('Y-m-d', time() - 3600 * 24))->where('youtube_id', $id)->where('language', $lang)->count() !== 0) {
			// this video has been translated yesterday or before, do not save as new
			continue;
		}

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
