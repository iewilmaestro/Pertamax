<?php

const host = "https://hotfaucet.in/";
const reff = "https://hotfaucet.in/?r=10151";
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
//    CURLOPT_COOKIEFILE => cookieFile, 
//	CURLOPT_COOKIEJAR => cookieFile
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
function headers($data = 0){
    $h[] = "Host: ".parse_url(host)['host'];
	if($data)$h[] = "Content-Length: ".strlen($data);
	$h[] = "User-Agent: ".Config::pick('user_agent');
	$h[] = "cookie: ".Config::pick('cookie');
	return $h;
}
function Dashboard(){
	$r = curl(host."profile",headers());
    if(str_contains($r,'Login'))return;
    $email = Functions::xp($r,'data-cfemail="', '"');
    if(isset($email)){
        $email = Functions::cfDecodeEmail($email);
    }
    return $email;
}

$icon = new IconCoordinat(host);
$icon->icon_header = headers();

Display::banner();

cookie:
if(count(Config::load()) < 1){
    Config::simpan(['cookie', 'user_agent']);
}
Display::banner();

$email = Dashboard();
if(!$email){
	Config::hapus(0);
	sleep(3);
	goto cookie;
}

Display::Cetak("email", $email);
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
	$gagal = 0;
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
            if(count($data) < 1){
                Display::Error($c.":: Daily claim limit. #1\n");
				$temp[$c] = true;
				continue;
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
			if($matches[1] == "success"){
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