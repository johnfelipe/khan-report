<?php

require __DIR__ . '/vendor/autoload.php';

if (extension_loaded('newrelic')) {
	newrelic_set_appname('khan-report.khanovaskola.cz-daemon');
}

$configurator = new Nette\Configurator;
$configurator->setDebugMode(TRUE);
$configurator->enableDebugger(__DIR__ . '/log');
$configurator->setTempDirectory(__DIR__ . '/temp');
$configurator->addConfig(__DIR__ . '/config.neon');
$configurator->addConfig(__DIR__ . '/config.local.neon');
$container = $configurator->createContainer();

foreach (scandir(__DIR__ . '/data') as $file) {
	$match = [];
	if (preg_match('~translations_(?<date>[0-9-]+)\.dat~', $file, $match)) {
		$day = $match['date'];
		/*if ($day === date('Y-m-d')) {
			echo "Writing to $day is still in progress, skipping...\n";
			continue;
		}*/
		if ($container->database->table('translation')->where('day', $day)->count()) {
			echo "Results for $day seem to already be in database, skipping...\n";
			continue;
		}

		$t = getTranslations(__DIR__ . "/data/$file", $day);
		foreach ($t as $youtube_id => $languages) {
			foreach ($languages as $language) {
				if (!$language) {
					continue;
				}

				if ($day === '2013-05-11' && $container->database->table('translation')->where('day', '2013-05-10')->where('youtube_id', $youtube_id)->where('language', $language)->count() !== 0) {
					// this video has been translated yesterday or before, do not save as new
					continue;
				}

				try {
					$container->database->table('translation')->insert([
						'day' => $day,
						'youtube_id' => $youtube_id,
						'language' => $language,
					]);
				} catch (PDOException $e) {
					if ($e->getCode() != 23000) {
						throw $e;
					}
					// else duplicate entry, ignore
				}
			}
		}
	}
}

// TODO: time check is only implemented because original data were in in huge file, remove it
function getTranslations($file, $day)
{
	$min = strtotime($day);
	$max = strtotime($day) + 3600 * 24;

	$handle = fopen($file, "r");
	$data = [];
	while (!feof($handle)) {
		$line = trim(fgets($handle));
		if (!$line)
			continue;

		list($time, $youtube_id, $langs) = explode("\t", $line);
		$langs = explode(';', $langs);

		if ($time >= $min && $time < $max) {
			foreach ($langs as $l) {
				if (!isset($data[$youtube_id]) || !in_array($l, $data[$youtube_id])) {
					$data[$youtube_id][] = $l;
				}
			}
		}
	}

	return $data;
}
