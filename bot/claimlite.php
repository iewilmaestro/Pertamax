<?php
const host  = "https://claimlite.in/";
const reff  = "https://claimlite.in/?r=2638";
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

function headers($data = 0){
    $h[] = "Host: ".parse_url(host)['host'];
	if($data)$h[] = "Content-Length: ".strlen($data);
	//$h[] = "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36";
    $h[] = "User-Agent: ".Config::pick('user_agent');
    $h[] = "Cookie: ".Config::pick('cookie');
	return $h;
}
function login(){
	global $icon;
    while(true){
        $r = curl(reff, headers());
        preg_match_all('/<input[^>]*name=["\'](.*?)["\'][^>]*value=["\'](.*?)["\'][^>]*>/i', $r, $input);
        $data = [];
        for ($i = 0; $i < count($input[1]); $i++) {
            $data[$input[1][$i]] = $input[2][$i];
        }
        $icontoken = explode("'",explode("<input type='hidden' name='_iconcaptcha-token' value='",$r)[1])[0];
        $data['wallet'] = Config::pick('email');
        if($icontoken){
            $icon->token = $icontoken;
            $cap = $icon->getResult();
            if(!$cap)continue;
            $data = array_merge($data, $cap);
            $data = http_build_query($data);
            return curl(host.'auth/login', headers(), $data);
        }
        Display::Error("Captcha Update\n");
        exit;
    }
}
function Dashboard(){
	$r = curl(host."profile",headers());
	if(preg_match('/Not Verified/', $r)){
        Display::Error("Please Verify Your Account to use any fetures.\n");
		exit;
	}
	$refId =explode('</td>', explode('<td style="padding:12px;border-bottom:1px solid #222;">', $r)[4])[0];
	if(preg_match('/email-protection/', $refId)){
		$str = explode('"', explode('data-cfemail="', $refId)[1])[0];
		$refId = Functions::cfDecodeEmail($str);
	}
	return $refId;
}
$icon = new IconCoordinat(host);
$icon->icon_header = headers();

Display::banner();

cookie:
if(count(Config::load()) < 1){
    Config::simpan(['cookie', 'user_agent']);
    //Config::simpan(['email']);
}
Display::banner();
/*
if(!file_exists(cookieFile)){
	login();
}
*/
$r = Dashboard();
if(!$r){
    Config::hapus(0);
	unlink(cookieFile);
	sleep(3);
	goto cookie;
}
Display::Cetak('email', $r);
Display::Line();

$r = curl(host."dashboard",headers());
preg_match_all('#faucet\/currency\/([a-zA-Z0-9]+)#i', $r, $matches);
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
			$r = curl(host.'faucet/currency/'.$c, headers());
            $sc = $scrap->Result($r);

			preg_match('#Atleast\s(\d+)\sShortlinks#', $r, $matc);
            if(isset($matc[1])){
                Display::Error("You Need to Complete {$matc[1]} Shortlinks\n");
                exit;
            }
            if($sc['cloudflare']){
                Display::Error("cloudflare detect\n");
                Config::hapus(0);
                sleep(3);
                goto cookie;
            }
            if($sc['firewall']){
                Display::Error("firewall detect\n");
                Display::Error("bypass firewall belum di set\n");
                exit;
            }
            if($sc['locked']){
                Display::Error("locked detect\n");
                Display::Error("locked belum di set\n");
                exit;
            }
            $tmr = Functions::xp($r, 'var wait = ', '-');
			if(isset($tmr)){
				Display::Tmr($tmr);
				continue;
			}

			if(!$r){
                Display::Error("Please Verify Your Account to use any fetures.\n");
				exit;
			}

            // === Triger LIMIT 1
			if(preg_match('/Daily claim limit/', $r)){
				Display::Error($c.":: Daily claim limit. #1\n");
				$temp[$c] = true;
				continue;
			}

			$data = [];
			$data = $sc['input'];
            $cekATB = explode('rel=\"',$r);
			if(isset($cekATB[1])){
				$antibot = $iewil->AntiBot($r);
				if(!$antibot)continue;
				$data['antibotlinks'] = $antibot;
			}
            if($sc['input']['_iconcaptcha-token']){
				$icon->token = $sc['input']['_iconcaptcha-token'];
                $cap = $icon->getResult();
                if(!$cap)continue;
			}else{
				Display::Error("captcha update\n");
				exit;
            }
			$data = array_merge($data, $cap);
			$data = http_build_query($data);
			$r = curl(host.'faucet/verify/'.$c, headers(), $data);
			preg_match("/Swal\.fire\(\s*{\s*icon:\s*'([^']+)',\s*title:\s*'([^']+)',\s*html:\s*'([^']+)'/", $r, $matches);
			if(preg_match('/sufficient funds/',$r)){
				$temp[$c] = true;
				Display::Error($c.":: Sufficient funds\n");
				continue;
			}
            if($matches[2] == "Success!"){
                $gagal = 0;
                Display::sukses($matches[3]);
            }elseif($matches[3]){
                Display::Error($matches[3]);
				if(preg_match('/Shortlink/',$matches[3])){
					exit;
				}
				sleep(3);
                print PHP_EOL;
			}else{
				Display::Error("Not Found\n");
                if($gagal>5){
                    Config::hapus(0);
                    sleep(3);
                    goto cookie;
                }
                $gagal++;
                sleep(3);
                print PHP_EOL;
			}
			$tmr = Functions::xp($r, 'var wait = ', '-');
			if(isset($tmr)){
				Display::Tmr($tmr);
			}
		}
	}
}