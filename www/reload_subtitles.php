<?php

require __DIR__ . '/../amara.php';
require __DIR__ . '/../vendor/autoload.php';

if (extension_loaded('newrelic')) {
	newrelic_set_appname('khan-report.khanovaskola.cz-daemon');
}

$configurator = new Nette\Configurator;
$configurator->setDebugMode(TRUE);
$configurator->setTempDirectory(__DIR__ . '/../temp');
$configurator->addConfig(__DIR__ . '/../config.neon');
$configurator->addConfig(__DIR__ . '/../config.local.neon');
$container = $configurator->createContainer();

if ($_GET['password'] !== '65aw344tsz16254sdF4@-13265') {
	echo "invalid";
	die;
}
$id = $_GET['id'];

list($amara_id, $data, $json) = getVideoTranslatedLangs($id);
$container->database->table('subtitles')->where('amara_id', $amara_id)->delete();
$container->database->table('subtitles')->insert([
	'amara_id' => $amara_id,
	'language' => 'cs',
	'label' => $json->subtitles->title,
	'description' => $json->subtitles->description,
	'subs' => json_encode($json->subtitles->subtitles),
]);
