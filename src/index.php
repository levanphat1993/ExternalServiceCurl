<?php

require_once 'curl/curl.php';

$curl =  new ExternalService_Curl();

$curl->setup('https://www.google.com/', 'GET');
$infor = [];
echo $curl->execute($infor, true);
print("<pre>" . print_r($infor, true) . "</pre>"); die;