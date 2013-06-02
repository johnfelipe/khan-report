<?php

function getAmara($youtube_id, $lang) {
    $servers = [
        '88.86.121.16' => [ // Endora
            'amara.8u.cz',
            'amara.9e.cz',
            'amara.g6.cz',
        ],
        '10.10.30.29' => [ // IC
            'amara.kx.cz',
            'amara.hu.cz',
        ],
        '217.198.115.56' => [ // php5.cz
            'amara.php5.cz',
        ],
        /* // blocked
        '78.46.88.202' => [ // killu.info
            'amara.24.eu',
        ],
        */
        '31.170.160.169' => [ // 000webhost
            'amara2.netne.net',
        ]
    ];
    $ip = array_rand($servers);
    $block = $servers[$ip];
    $server = $block[array_rand($block)];

    //echo "Using $ip\n";

    $url = "http://$server/?youtube_id={$youtube_id}&lang={$lang}";
    //echo "\t$url\n";
    $res = file_get_contents($url);

    // strip 000webhost html
    if ($ip === '31.170.160.169') {
        $res = substr($res, 0, - strlen('<!-- Hosting24 Analytics Code -->\r\n<script type="text/javascript" src="http://stats.hosting24.com/count.php"></script>\r\n<!-- End Of Analytics Code -->'));
    }

    $json = json_decode($res);

    if (!isset($json[0]) || !property_exists($json[0], 'drop_down_contents')) {
        return FALSE;
    }
    return $json[0];
}

function getVideoTranslatedLangs($id)
{
    $json = getAmara($id, 'cs');

    $languages = [];
    foreach ($json->drop_down_contents as $node) {
    	if (property_exists($node, 'percent_done')) {
    		$languages[$node->language] = $node->percent_done;
    	} else {
    		$languages[$node->language] = $node->is_complete ? 100 : 0;
    	}
    }

    return [$json->video_id, $languages, $json];
}
