<?php

require "vendor/autoload.php";

use PHPHtmlParser\Dom;

$dom = new Dom;
$dom->load('http://10.132.219.228/CGI/Java/Serviceability?adapter=device.statistics.streaming.0');
//$html = $dom->outerHtml;
print_r($html);