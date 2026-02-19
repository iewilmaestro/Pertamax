<?php

const host = "https://litecoinfarm.online/";
const reff = "https://litecoinfarm.online/index.php?ref=378767";
const cookieFile = "data/".TITLE."/cookie.txt";

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
    CURLOPT_COOKIE => true, 
    CURLOPT_COOKIEFILE => cookieFile, 
	CURLOPT_COOKIEJAR => cookieFile
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
function headers($referer = 0){
    $h[] = "Host: ".parse_url(host)['host'];
	if($referer)$h[] = "referer: ".$referer;
	$h[] = "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36";
	//$h[] = "cookie: ".Config::pick('cookie');
	return $h;
}
function Dashboard(){
    global $scrap;
    $email = '';
	$r = curl(host."profile",headers());
    $sc = $scrap->Result($r);
    if(str_contains($sc['title'],'Profile')){
       $email = Functions::xp($r,'data-cfemail="', '"');
       $email = Functions::cfDecodeEmail($email);
    }
    return $email;
}
function login(){
	global $iewil,$scrap;
    while(true){
        $r = curl(reff, headers());
        //print_r($r);exit;
        $sc = $scrap->Result($r);
        if(count($sc['input']) > 1){
            $data = [];
            $data = $sc['input'];
            $data['faucet_email'] = Config::pick('email');

            $cap = $iewil->Turnstile(host);
            if(!$cap)continue;
            $data['cf-turnstile-response'] = $cap;

            $data = array_merge($data);
            return curl(reff, headers(reff), $data);
        }
        Display::Error("Captcha Update\n");
        exit;
    }
}
$icon = new IconCoordinat(host);
$icon->icon_header = headers();

Display::banner();

cookie:
if(!Config::pick('email')){
	Config::hapus(0);
    Config::simpan(['email']);
}
if(!file_exists(cookieFile)){
    login();
}
$mine = curl(host.'mine.php', headers());
$csrf = explode("'", explode("let csrfToken = '", $mine)[1])[0];
if(!$csrf){
    unlink(cookieFile);
    goto cookie;
}
while(true){
    $r = json_decode(curl(host.'mine.php', headers(), 'check_timer=1'),1);
    if(isset($r['success'])){
        if($r['click_limit'] == $r['clicks_today'])break;
        $mine = curl(host.'mine.php', headers());
        $csrf = explode("'", explode("let csrfToken = '", $mine)[1])[0];
        $sc = $scrap->Result($mine);
        $cap = $iewil->Turnstile(host);
        if(!$cap)continue;
        Display::Cetak("Limit",$r['clicks_today']."/". $r['click_limit']);
        $requestId = 'CLAIM_' . round(microtime(true) * 1000) . '_' . substr(base_convert(bin2hex(random_bytes(5)), 16, 36), 0, 9);
        $data = "mine_action=1&cf-turnstile-response=$cap&request_id=$requestId&csrf_token=$csrf";
        $r = json_decode(curl(host.'mine.php', headers(), $data),1);
        if(isset($r['success'])){
            Display::sukses($r['message']);
            Display::Cetak("Balance", $r['new_balance']);
            Display::Line();
        }
        Display::Tmr(15);
        continue;
    }
    unlink(cookieFile);
    goto cookie;
}
Display::Error("Limit Reach\n");