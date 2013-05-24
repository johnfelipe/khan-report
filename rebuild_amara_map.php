<?php

require __DIR__ . '/amara.php';
require __DIR__ . '/vendor/autoload.php';

$configurator = new Nette\Configurator;
$configurator->setDebugMode(TRUE);
$configurator->setTempDirectory(__DIR__ . '/temp');
$configurator->addConfig(__DIR__ . '/config.neon');
$configurator->addConfig(__DIR__ . '/config.local.neon');
$container = $configurator->createContainer();

foreach ($container->database->query('SELECT DISTINCT youtube_id FROM translation WHERE youtube_id NOT IN (SELECT youtube_id FROM amara_map)') as $row) {
	$id = $row['youtube_id'];

	list($amara_id) = getVideoTranslatedLangs($id);
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

	sleep(3); // just be patient
}
