<?php
const host  = "https://trxwin.top/";
const reff  = "https://trxwin.top/?ref=1862";
const cookieFile = "data/".TITLE."/cookie.txt";
const zeraCookieFile = "data/".TITLE."/zera.txt";

function curl($url, $headers, $data = 0) {
    $ch = curl_init();
    curl_setopt_array($ch, [ 
        CURLOPT_URL => $url, 
        CURLOPT_RETURNTRANSFER => true, 
        CURLOPT_FOLLOWLOCATION => true, 
        CURLOPT_SSL_VERIFYPEER => false, 
        CURLOPT_SSL_VERIFYHOST => false, 
        CURLOPT_CONNECTTIMEOUT => 30, 
        CURLOPT_TIMEOUT => 60, 
        CURLOPT_COOKIEFILE => cookieFile, 
        CURLOPT_COOKIEJAR => cookieFile,
        CURLOPT_COOKIE => true
    ]);
    if (!empty($headers))  {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }
    if ($data)  {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

    }
    $response = curl_exec($ch);
    curl_close($ch);
    return $response;
}
function headers($ref = 0){
    $h[] = "Host: ".parse_url(host)['host'];
    if($ref)$h[] = "referer: ".$ref;
	$h[] = "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36";
	return $h;
}

Display::banner();
$r = Config::load();
if(count($r) < 1){
    Config::simpan(['email']);
}
Display::banner();

Display::Cetak('user', Config::pick('email'));
Display::Line();

while(true){
    $cap = $iewil->Turnstile(host);
	if(!$cap)continue;
	$data = [
		"fp_email" => Config::pick('email'),
		"website_url" => '',
		"cf-turnstile-response" => $cap,
		"start_shortlink" => ''
	];
    $r = curl(host.'index.php', headers(reff), http_build_query($data));
    Display::Tmr(30);
    $r = curl('https://trxwin.top/index.php?claim_done=1', headers('https://zerads.com/'));
    preg_match_all("/Swal\.fire\(\s*{\s*icon:\s*'([^']+)',\s*title:\s*'([^']+)',\s*text:\s*'([^']+)'/", $r, $matches);
	if($matches[2][2] == 'Success'){
        Display::sukses($matches[3][2]);
    }
    Display::Tmr(30);
}