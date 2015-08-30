<?php
define("DOMAIN", "xz.com"); // domain
define("COOKIE", "PHPSESSID=u7q8hh5rheqg5oqjoih5n8m4t7"); // session
define("URI", $_SERVER["REQUEST_URI"]);
define("URL", parse_url(URI, PHP_URL_PATH));
define("ROOT", __DIR__."/".DOMAIN);
define("PATH", ROOT.preg_replace('/\/$/', "/index.html", str_replace(array("/?", "?"), array("/index.html#", "#"), URI)));

if ($_SERVER["REQUEST_METHOD"] == "POST") {
	http_response_code(403);
	exit("Not supported POST request!");
}

$mimes = array(
	"png" => "image/png",
	"jpg" => "image/jpeg",
	"jpeg" => "image/jpg",
	"js" => "text/javascript",
	"css" => "text/css",
	"html" => "text/html; charset=utf-8", // charset
);

$ext = pathinfo(URL, PATHINFO_EXTENSION);

if (file_exists(PATH)) {
	header("Content-Type: ".(array_key_exists($ext, $mimes) ? $mimes[$ext] : $mimes["html"]));
	readfile(PATH);
	exit();
}

$dir = pathinfo(PATH, PATHINFO_DIRNAME);
if (!file_exists($dir)) {
	@mkdir($dir, 0777, true);
}

$ch = curl_init("http://".DOMAIN.URI);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, array("Cookie: ".COOKIE));
$html = curl_exec($ch);
curl_close($ch);

header("Content-Type: ".(array_key_exists($ext, $mimes) ? $mimes[$ext] : $mimes["html"]));
echo $html;

file_put_contents(PATH, $html);