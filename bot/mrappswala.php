<?php

const host  = "https://mrappswala.com/";
const reff  = "https://t.me/MiniappFaucetsbot?start=1243";
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

function headers($referer = 0){
    $h[] = "Host: ".parse_url(host)['host'];
	if($referer)$h[] = "referer: ".$referer;
	$h[] = "User-Agent: Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36";
	return $h;
}

function login(){
	global $iewil;
	$query = Config::pick('QueryID');
	$user = urldecode(explode('&',explode('user=', $query)[1])[0]);
	$user = json_decode($user, true);
	if(is_array($user)){
		$url = host.'?r=0';
		$retry = 0;
		while ($retry < 5) {
			$cap = $iewil->turnstile(host);
			if(!$cap){
                Display::Error("Captcha tidak berhasil di bypass\n");
                $status = "Captcha Update\n";
				$retry++;
				continue;
			}
			
			$data = http_build_query([
				"ci_csrf_token" => "",
				"wallet" => Config::pick('email'),
				"uid" => "",
				"private_ip" => "",
				"telegram_user_id" => $user['id'],
				"telegram_first_name" => $user['first_name'],
				"telegram_last_name" => $user['last_name'],
				"telegram_username" => $user['username'],
				"telegram_language_code" => $user['language_code'],
				"telegram_is_premium" => "0",
				"telegram_photo_url" => $user['photo_url'],
				"captcha" => "turnstile",
				"cf-turnstile-response" => $cap
			]);
			$r = curl(host.'app/auth/validation', headers($url), $data);
			if (preg_match("/icon:\s*'([^']+)'.*?title:\s*'([^']+)'.*?text:\s*'([^']+)'/s", $r, $m)) {
				$icon  = $m[1];
				$title = $m[2];
				$text  = $m[3];
				if ($icon === 'success') {
                    Display::sukses($text);
					return;
				}
			}
			$retry++;
			$status = "Login Gagal\n";
		}
        Display::Error($status);
		exit;
	}
}
function Dashboard(){
	while(true){
		$r = curl(host."app/dashboard",headers());
		// === opsi Timer 2
		preg_match('/<b id="minute">(\d+)<\/b>:(<b id="second">(\d+)<\/b>)/', $r, $matches);
		if (isset($matches[1]) && isset($matches[3])) {
			Display::Error("Account Locked\n");
			$minute = $matches[1];
			$second = $matches[3];
			$tmr = ($minute * 60) + $second;
			Display::Tmr($tmr+5);
			continue;
		}
		return explode('"', explode('value="'.host.'?r=', $r)[1])[0];
	}
}

Display::banner();
if(count(Config::load()) < 1){
    Config::simpan(['QueryID', 'email']);
}
Display::banner();

cookie:
if(!file_exists(cookieFile)){
	login();
}

$r = curl(host."app/dashboard",headers());
preg_match_all('#faucet\?currency=([a-zA-Z0-9]+)#i', $r, $matches);
if(count($matches[1])<1){
	unlink(cookieFile);
	goto cookie;
}
if(isset($matches[1])){
	$coins = array_values(array_unique(array_map('strtolower', $matches[1])));
	pilih_coin:
	$list = implode(',', $coins);
	print "list coin: ".$list."\n";
    Display::isi("Coin");
    $input = trim(readline());

    if ($input === '') {
        Display::Error("Input tidak boleh kosong\n");
        goto pilih_coin;
    }

    $coin = array_map('trim', explode(',', $input));
    $selected = array_map('strtolower', $coin);
    $selected = array_values(array_unique(array_filter($selected)));
    if (empty($selected)) {
        Display::Error("Tidak ada coin valid\n");
        goto pilih_coin;
    }
    $invalid = array_diff($selected, $coins);
    if (!empty($invalid)) {
        Display::Error("Invalid coin: " . implode(', ', $invalid) ."\n");
        goto pilih_coin;
    }
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
			if ($temp[$c] === true) continue;
			$r = curl(host.'app/faucet?currency='.$c, headers());
			$cap = $iewil->turnstile(host);
			if(!$cap)continue;
			$data = http_build_query([
				"ci_csrf_token" => "",
				"captcha" => "turnstile",
				"cf-turnstile-response" => $cap
			]);
			$r = curl(host.'app/faucet/verify?currency='.$c, headers(), $data);
			if (preg_match("/icon:\s*'([^']+)'.*?title:\s*'([^']+)'.*?text:\s*'([^']+)'/s", $r, $m)) {
				$icon  = $m[1];
				$title = $m[2];
				$text  = $m[3];
				
				if ($icon === 'success') {
                    Display::Sukses($text);
				    Display::Line();
				}elseif($icon === 'warning'){
					if(preg_match('/sufficient/',$text)){
						$temp[$c] = true;
                        Display::Error($c."::".$text."\n");
					}else
					if(preg_match('/limit/',$text)){
						$temp[$c] = true;
                        Display::Error($c."::".$text."\n");
						exit;
					}else{
						print_r($m);
						exit;
					}
                }elseif($icon === 'error'){
					$temp[$c] = true;
                    Display::Error($c."::".$text."\n");
				}else{
					print_r($m);
					exit;
				}
			}
			Display::Tmr(10);
		}
	}
}