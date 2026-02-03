<?php
const host  = "http://satoshifaucet.io/";
const reff  = "http://satoshifaucet.io/?r=8841";
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
    CURLOPT_RESOLVE => ["satoshifaucet.io:80:173.249.41.150","satoshifaucet.io:443:173.249.41.150"], 
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
function headers($data = 0){
    $h[] = "Host: ".parse_url(host)['host'];
	if($data)$h[] = "Content-Length: ".strlen($data);
    $h[] = "Accept: */*";
	$h[] = "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36";
    $h[] = "content-type: application/x-www-form-urlencoded";
	$h[] = "x-requested-with: XMLHttpRequest";
	return $h;
}
function login(){
	global $iewil;
    Display::info("Login");
	$r = curl(reff, headers());
	preg_match_all('/<input[^>]*name=["\'](.*?)["\'][^>]*value=["\'](.*?)["\'][^>]*>/i', $r, $input);
	for ($i = 0; $i < count($input[1]); $i++) {
		$data[$input[1][$i]] = $input[2][$i];
	}
	$data['wallet'] = Config::pick('email');
    $data['utt'] = 'Asia/Jakarta';
    $data['ls'] = 'en-US,en';
    $data['uf'] = md5(Config::pick('email'));
	$sitekey = explode('"', explode('<div class="cf-turnstile" data-sitekey="', $r)[1])[0];
	$emot = explode('"', explode('<option value="', $r)[1])[0];
	if($sitekey){
		$data["captcha"] = "turnstile-cloudflare";
		$cap = $iewil->Turnstile("https://satoshifaucet.io");
		if(!$cap){
            Display::Error("try again\n");
			exit;
		}
		$data["cf-turnstile-response"] = $cap;
	}elseif($emot == "emoji_captcha"){
        Display::Error("Emot captcha belum update\n");
		exit;
		$captcha = EmotCaptcha();
		$data = array_merge($data, $captcha);
	}else{
        Display::Error("ganti captcha belum di set\n");
		exit;
	}
	$data = http_build_query($data);
	curl(host.'auth/login', headers(), $data);
}
function Dashboard(){
	$r = curl(host."referrals",headers());
	$refId = explode('"', explode('value="https://satoshifaucet.io/?r=', $r)[1])[0];
	return $refId;
}
Display::banner();
$r = Config::load();
if(count($r) < 1){
    Config::simpan(['email']);
}
Display::banner();

cookie:
if(!file_exists(cookieFile)){
	login();
}

$r = Dashboard();
if(!$r){
	unlink(cookieFile);
	sleep(3);
	goto cookie;
}

Display::Cetak('userId', $r);
Display::Line();

$r = curl(host."dashboard",headers());
preg_match_all('#faucet\/currency\/([a-zA-Z0-9]+)#i', $r, $matches);

if(isset($matches[1])){
	$coins = array_values(array_unique(array_map('strtolower', $matches[1])));
	$list = implode(',', $coins);
	print "list coin: ".$list."\n";
    Display::isi("Input Coin");
	$pil = readline();
	$coin = array_map('trim', explode(',', $pil));
	Display::Line();
	
	$temp = [];
	foreach ($coin as $c) {
		$temp[$c] = false;
	}
	
	while(true){
		$allDone = true;
        foreach ($coin as $c) {
            if ($temp[$c] === false) {// masih ada yang bisa di gas
                $allDone = false;
                break;
            }
        }
        if ($allDone) break;
		foreach($coin as $a => $c){
			$r = curl(host.'faucet/currency/'.$c, headers());
			$tmr = explode('var wait = ', $r);
			if(isset($tmr[1])){
				$tmr = explode('-',$tmr[1])[0];
				if($tmr){
					Display::tmr($tmr);
					continue;
				}
			}
			
			if(!$r){
                Display::Error("Please Verify Your Account to use any fetures.\n");
				exit;
			}
			if(preg_match('/Daily claim limit/', $r)){
                Display::Error($c.":: Daily claim limit.\n");
				$temp[$c] = true;
				continue;
			}
			$data = [];
			preg_match_all('/<input[^>]*name=["\'](.*?)["\'][^>]*value=["\'](.*?)["\'][^>]*>/i', $r, $input);
			for ($i = 0; $i < count($input[1]); $i++) {
				$data[$input[1][$i]] = $input[2][$i];
			}
			if(explode('\"',explode('rel=\"',$r)[1])[0]){
				$atb = $iewil->AntiBot($r);
				if(!$atb)continue;
				$data['antibotlinks'] = $atb;
			}
			$data['utt'] = 'Asia/Jakarta';
			$data['ls'] = 'en-US,en';
			$data['uf'] = md5(Config::pick('email'));
			$sitekey = explode('"', explode('<div class="cf-turnstile" data-sitekey="', $r)[1])[0];
			$emot = explode('"', explode('<option value="', $r)[1])[0];
			if($sitekey){
				$cap = $iewil->Turnstile('https://satoshifaucet.io');
				if(!$cap)continue;
				$data["captcha"] = "turnstile-cloudflare";
				$data["cf-turnstile-response"] = $cap;
			}elseif($emot == "emoji_captcha"){
                Display::Error("Emot captcha belum update\n");
		        exit;
				$captcha = EmotCaptcha();
				if(!$captcha)continue;
				$data = array_merge($data, $captcha);
			}else{
				Display::Error("ganti captcha belum di set\n");
		        exit;
			}
			$data = http_build_query($data);
			curl(host.'faucet/verify/'.$c, headers(), $data);
			$r = curl(host.'faucet/currency/'.$c, headers());
			preg_match("/Swal\.fire\(\s*{\s*icon:\s*'([^']+)',\s*title:\s*'([^']+)',\s*html:\s*'([^']+)'/", $r, $matches);
			if(preg_match('/sufficient funds/',$r)){
				$temp[$c] = true;
                Display::Error($c.":: Sufficient funds\n");
				continue;
			}
			if($matches[2] == "Success!"){
                Display::sukses($matches[3]);
			}else{
                Display::Error($matches[3]);
				if(preg_match('/Shortlink/',$matches[3])){
					exit;
				}
				sleep(3);
                Display::Clearline();
			}
			$tmr = explode('var wait = ', $r);
			if(isset($tmr[1])){
				$tmr = explode('-',$tmr[1])[0];
				if($tmr){
					Display::tmr($tmr);
					continue;
				}
			}
		}
	}
}
?>