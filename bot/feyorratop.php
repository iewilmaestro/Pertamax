<?php

const host = "https://feyorra.top/";
const reff = "https://feyorra.top/?r=34383";
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
	CURLOPT_COOKIEJAR => cookieFile]);
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
function Login(){
	global $scrap, $icon;
    $gagal = 1;
	while(true){
        $r = curl(host."login",headers());
        $sc = $scrap->Result($r);
        if($sc['input']['_iconcaptcha-token']){
            $icon->token = $sc['input']['_iconcaptcha-token'];
            $cap = $icon->getResult();
            if(!$cap)continue;
        }else{
            Display::Error("captcha update\n");
            exit;
        }
        $data = [];
        $data['csrf_token_name'] = $sc['input']['csrf_token_name'];
        $data['email'] = Config::pick('email');
        $data['password'] = Config::pick('password');
        $dataset = array_merge($data, $cap);
        $r = curl(host."auth/login",headers(), http_build_query($dataset));
        if (str_contains($r, 'Dashboard')){
            Display::sukses("login Sukses\n");
            return;
        }else{
            if($gagal > 5)exit;
            $gagal++;
            Display::Error("Login gagal\n");
            continue;
        }
    }
}
function headers(){
    $h[] = "Host: ".parse_url(host)['host'];
	$h[] = "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36";
	return $h;
}
function Dashboard(){
	$r = curl(host."dashboard",headers());
	$data['balance'] = explode('<',explode('<p>', explode('<div class="left_tsc">', $r)[1])[1])[0];
	return $data;
}
// ==== SET ICON COORDINAT =====
$icon = new IconCoordinat(host);
$icon->icon_header = headers();
// ===== SET RSCAPTCHA V5 =====
$rscap = new rsCaptchaV5(host);
$rscap->app_id = "1007";
$rscap->public_key = "iFwtElK1Re8L6YGqrAx5";

Display::banner();
if(count(Config::load()) < 1){
    Config::simpan(['email','password']);
}
Display::banner();

cookie:
if(!file_exists(cookieFile)){
	login();
}

$r = Dashboard();
if(!$r['balance']){
	unlink(cookieFile);
	Display::Error("Cookie expired\n");
	goto cookie;
}

Display::Cetak("balance", $r['balance']);
Display::Line();
while(true){
    $r = curl(host."faucet",headers());
    $sc = $scrap->Result($r);
    if(preg_match('/required to continue claiming!/', $r)){
		Display::Error("Shortlink\n");
		exit;
	}
	if(preg_match('/Daily limit reached/', $r)){
		Display::Error("Daily limit reached\n");
		exit;
	}
	if($sc['firewall']){
        Display::Error("Firewall Detect\n");
		//continue;
		exit;
	}
	if($sc['cloudflare']){
		Display::Error("Cloudflare Detect\n");
		//continue;
		exit;
	}
	$timer = explode('-',explode('let wait = ', $r)[1])[0];
	if($timer){
		Display::Tmr($timer);
		continue;
	}
	$data = $sc['input'];
	if($sc['options'][0] == "rscaptcha"){

        if(str_contains($r, 'rscaptcha_img')){
            Display::Error("RsCaptcha Updside not support\n");
            exit;
        }
		$cap = $rscap->getResult();
		if(!$cap)continue;	
		$cap['captcha'] = 'rscaptcha';
	}else{
        Display::Error("captcha Update\n");
        exit;
	}
	$dataset = array_merge($data, $cap);
	if(!$dataset){
        Display::Error("Data not found");
        sleep(2);
        Display::clearLine();
		continue;
	}
			
	$dataset = http_build_query($dataset);
	$r = curl(host."faucet/verify",headers(), $dataset);
    $sc = $scrap->Result($r);
    if(str_contains($sc['title'],'Login')){
        Display::Error("Please turn off Vpn Or Proxy\n");
        exit;
    }
	$wr = explode('</div>',explode('<i class="fas fa-exclamation-circle"></i> ', $r)[1])[0];
	$ss = explode("',",explode("title: '",$r)[1])[0];
	if(preg_match('/Shortlink must be completed/', $r)){
		$sl = explode("'",explode("Swal.fire('Error!', '", $r)[1])[0];
        Display::Error("$sl\n");
		exit;
	}
	if($ss){
        Display::sukses($ss);
		$r = Dashboard();
		Display::Cetak("balance", $r['balance']);
        Display::Line();
	}elseif($wr){
        Display::Error($wr);
        sleep(2);
        Display::clearLine();
	}else{
		print_r($r);
		exit;
	}
}
?>