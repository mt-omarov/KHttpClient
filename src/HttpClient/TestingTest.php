<?php

require_once __DIR__ . "/../../vendor/autoload.php";
use Kaa\HttpClient\Components\Test;

//Test::testing();

var_dump(curl_multi_init());

//Test::phpPredefinedToFile();
//foreach (Test::getDefinedKPHP() as $n){
//    if ($n !== "CURLOPT_HTTP200ALIASES") continue;
//    print($n);
//}