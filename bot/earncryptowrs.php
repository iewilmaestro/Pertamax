<?php
const host  = "https://earncryptowrs.in/";
const reff  = "https://earncryptowrs.in/?r=959";
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
	$h[] = "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36";
	return $h;
}

function login(){
    global $scrap, $icon;
    $gagal = 1;
	while(true){
        $r = curl(reff, headers());
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
        $data = $sc['input'];
        $data['wallet'] = Config::pick('email');
        $data['uid'] = md5(Config::pick('email'));
        $data['private_ip'] = '';
        
        $dataset = array_merge($data, $cap);
        $r = curl(host."app/auth/validation",headers(), http_build_query($dataset));
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
function Dashboard(){
	$r = curl(host."app/dashboard",headers());
	return explode('"', explode('value="'.host.'?r=', $r)[1])[0];
}
// ==== SET ICON COORDINAT =====
$icon = new IconCoordinat(host);
$icon->icon_header = headers();

Display::banner();
if(count(Config::load()) < 1){
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
Display::Cetak('email', Config::pick('email'));
Display::Cetak('reffId', $r);
Display::Line();

$r = curl(host."app/dashboard",headers());
preg_match_all('#app\/faucet\?currency=([a-zA-Z0-9]+)#i', $r, $matches);
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
			
			$r = curl(host.'app/faucet?currency='.$c, headers());
			$sc = $scrap->Result($r);
            if(!$r){
				Display::Error("Please turn off Vpn Or Proxy\n");
                Display::Error("Please Verify Your Account to use any fetures.\n");
				exit;
			}
            // == Opsi Timer 1
			$tmr = explode('var wait = ', $r);
			if(isset($tmr[1])){
				$tmr = explode('-',$tmr[1])[0];
				if($tmr){
					Display::tmr($tmr);
					continue;
				}
			}
			
            // === opsi Timer 2
			preg_match('/<b id="minute">(\d+)<\/b>:(<b id="second">(\d+)<\/b>)/', $r, $matches);
			if (isset($matches[1]) && isset($matches[3])) {
				$minute = $matches[1];
				$second = $matches[3];
				$tmr = ($minute * 60) + $second;
				Display::Tmr($tmr+5);
				continue;
			}

			$status_bal = Functions::xp($r, '<span class="badge badge-danger">', '</span>');
			if(isset($status_bal) && $status_bal == "Empty"){
				$temp[$c] = true;
				Display::Cetak($coin,"Sufficient funds");
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

            if($sc['captcha']){
				if($sc['captcha']['cf-turnstile']){
					$data['captcha'] = "turnstile";
					$cap = $iewil->Turnstile(host);
					$data['cf-turnstile-response']=$cap;
				}else{
					Display::Error("Sitekey Error\n"); 
					continue;
				}
				if(!$cap)continue;
			}else
			if($sc['input']['_iconcaptcha-token']){
				$icon->token = $sc['input']['_iconcaptcha-token'];
                $cap = $icon->getResult();
                if(!$cap)continue;
				$data = array_merge($data, $icon);
			}else{
				Display::Error("captcha update\n");
				exit;
            }

			$data = http_build_query($data);

			$r = curl(host.'app/faucet/verify?currency='.$c, headers(), $data);

            if(preg_match('/invalid api key used/',strtolower($r))){
				Display::Error($c.":: Invalid apikey used");
				continue;
			}

			if(preg_match('/sufficient funds/',strtolower($r))){
				$temp[$c] = true;
                Display::Error($c.":: Sufficient funds\n");
				continue;
			}
            if(preg_match('/invalid amount/',strtolower($r))){
				$temp[$c] = true;
				Display::Error("You are sending an invalid amount of payment to the user\n");
				continue;
			}
			if(preg_match('/invalid claim/',strtolower($r))){
				$temp[$c] = true;
				Display::Error("invalid claim\n");
				continue;
			}
            if(preg_match('/Shortlink in order to claim from the faucet!/',$r)){
				Display::Error(explode("'",explode("html: '",$r)[1])[0]);
				exit;
			}
			preg_match("/Toast\.fire\({\s*icon:\s*'([^']+)',\s*title:\s*'([^']+)',\s*text:\s*'([^']+)'/", $r, $matches);
			if($matches[1] == "success"){
				Display::Sukses($matches[3]);
				Display::Line();
			}elseif(isset($matches[3])){
				Display::Error($matches[3]);
				if(preg_match('/Shortlink/',$matches[3])){
					print "\n";
					Display::Line();
					exit;
				}
				sleep(3);
				Display::Clearline();
			}else{
				Display::Error("Ups");
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
	Display::Error("All coins have been claimed\n");
}
?>
