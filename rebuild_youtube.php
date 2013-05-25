<?php

require __DIR__ . '/vendor/autoload.php';

$configurator = new Nette\Configurator;
$configurator->setDebugMode(TRUE);
$configurator->setTempDirectory(__DIR__ . '/temp');
$configurator->addConfig(__DIR__ . '/config.neon');
$configurator->addConfig(__DIR__ . '/config.local.neon');
$container = $configurator->createContainer();

foreach ($container->database->query('SELECT * FROM video WHERE title IS NULL') as $row) {
	$id = $row['youtube_id'];
	list($cached, $meta) = getYoutube($id);

	try {
		$container->database->table('title')->insert([
			'youtube_id' => $id,
			'title' => $meta->data->title,
		]);
	} catch (PDOException $e) {
		if ($e->getCode() != 23000) {
			throw $e;
		}
		// else duplicate entry, ignore
	}
	echo ".";

	if (!$cached)
		sleep(2); // just be patient
}

function getYoutube($youtube_id)
{
	@mkdir(__DIR__ . '/www/cache'); // @ - may already exist
	$cache = __DIR__ . "/www/cache/youtube_" . str_replace('=', '', base64_encode($youtube_id));
	$meta = NULL;
	if (file_exists($cache)) {
		$meta = unserialize(file_get_contents($cache));
		return [TRUE, $meta];
	}
	if (!$meta) {
		$meta = json_decode(@file_get_contents("http://gdata.youtube.com/feeds/api/videos/$youtube_id?v=2&alt=jsonc&prettyprint=false"));
		if (!$meta) {
			throw new Exception;
		}
		file_put_contents($cache, serialize($meta));
		return [FALSE, $meta];
	}
}
