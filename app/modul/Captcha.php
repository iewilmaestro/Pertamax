<?php

class Captcha {
	
	protected $url;
	protected $provider;
	protected $key;
	protected $providersFile = "apikey/providers.json";

	public function __construct(){

		$config = $this->simpanApikey();

		$this->url      = $config["url"];
		$this->provider = $config["provider"];
		$this->key      = $config["apikey"];
	}
	private function simpanApikey(){

        $folder = dirname($this->providersFile);
        if (!is_dir($folder)) {
            mkdir($folder, 0777, true);
        }

        $default = [
            "1" => [
                "provider" => "xevil",
                "url" => "https://sctg.xyz/",
                "register" => "t.me/Xevil_check_bot?start=1204538927",
                "apikey" => ""
            ],
            "2" => [
                "provider" => "multibot",
                "url" => "http://api.multibot.in/",
                "register" => "http://api.multibot.in",
                "apikey" => ""
            ]
        ];

        if (file_exists($this->providersFile)) {
            $data = json_decode(file_get_contents($this->providersFile), true);
            if (!is_array($data)) $data = $default;
        } else {
            $data = $default;
        }

        foreach ($data as $prov) {
            if (!empty($prov["apikey"])) {
                return $prov;
            }
        }

        display::title("Select Provider");
        display::menu(1, "Xevil");
        display::menu(2, "Multibot");
        display::isi("number");
        
        $input = trim(readline());
        display::line();

        if (!isset($data[$input])) {
            display::error("number not valid!\n");
            exit;
        }

        $prov = $data[$input];

        display::cetak("Register",$prov["register"]);

        display::isi("API Key");
        $key = trim(readline());

        if ($input == "1") {
            $key .= "|SOFTID1204538927";
        }

        $data[$input]["apikey"] = $key;
        file_put_contents($this->providersFile, json_encode($data, JSON_PRETTY_PRINT));

        $prov["apikey"] = $key;
        return $prov;
    }
    private function gradient(string $text, array $startColor, array $endColor, int $shift = 0): string {
        $len = mb_strlen($text, 'UTF-8');
        $result = "";

        for ($i = 0; $i < $len; $i++) {
            $char = mb_substr($text, $i, 1, 'UTF-8');

            // arah kiri → kanan (dibalik dari versi sebelumnya)
            $pos = ($i - $shift) % $len;
            if ($pos < 0) $pos += $len;

            $r = (int)($startColor[0] + ($endColor[0] - $startColor[0]) * $pos / $len);
            $g = (int)($startColor[1] + ($endColor[1] - $startColor[1]) * $pos / $len);
            $b = (int)($startColor[2] + ($endColor[2] - $startColor[2]) * $pos / $len);

            $result .= "\033[38;2;{$r};{$g};{$b}m{$char}\033[0m";
        }

        return $result;
    }
    private function solvingProgress($xr,$tmr, $cap){
        $noColor = class_exists('Display') && Display::$noColor;
        $gradients = [
            "hijau" => [[0,255,0],[0,128,0]],
            "merah" => [[255,0,0],[128,0,0]],
            "kuning" => [[255,165,0],[255,215,0]]
        ];
        $sym = ['⠋','⠙','⠹','⠸','⠼','⠴','⠦','⠧','⠇','⠏'];
        $a = 0;
        for($i=$tmr*4;$i>0;$i--){
            if($xr < 50){
                $var = "hijau";
            }elseif($xr >= 50 && $xr < 80){
                $var = "kuning";
            }else{
                $var = "merah";
            }
            $gradient = $gradients[$var];

            $text = " Bypass $cap $xr% " . $sym[$a % count($sym)];
            $varStr = self::gradient($text, $gradient[0], $gradient[1], $a);
            if ($noColor) {
                print $text. " \r";
            } else {
                print $varStr." \r";
            }
            usleep(50000);
            if($xr < 99 && $i % 4 == 0)$xr+=1;
            $a++;
        }
        return $xr;
    }
    private function in_api($content, $method, $headers = 0){
		$param = "key=".$this->key."&json=1&".$content;
		if($method == "GET")return json_decode(file_get_contents($this->url.'in.php?'.$param),1);
		$opts['http']['method'] = $method;
        if ($headers) {
            $opts['http']['header'] = $headers;
        } else {
            $opts['http']['header'] = "Content-Type: application/x-www-form-urlencoded\r\n";
        }
		//if($headers) $opts['http']['header'] = $headers;
		$opts['http']['content'] = $param;
		return file_get_contents($this->url.'in.php', false, stream_context_create($opts));
	}
    private function res_api($api_id){
		$params = "?key=".$this->key."&action=get&id=".$api_id."&json=1";
		return json_decode(file_get_contents($this->url."res.php".$params),1);
	}
    private function filter($method){
		$map = [
			"userrecaptcha" => "RecaptchaV2",
			"hcaptcha" => "Hcaptcha",
			"turnstile" => "Turnstile",
			"universal" => "Ocr",
			"base64" => "Ocr",
			"antibot" => "Antibot",
			"authkong" => "Authkong",
			"teaserfast" => "Teaserfast"
		];

		return $map[$method] ?? null;
	}
    private function getResult($data ,$method, $header = 0){
        $cap = $this->filter(explode('&',explode("method=",$data)[1])[0]);
        $get_res = $this->in_api($data ,$method, $header);
        if(is_array($get_res)){
            $get_in = $get_res;
		}else{
            $get_in = json_decode($get_res,1);
		}
        if(!$get_in["status"]){
			$msg = $get_in["request"];
			if($msg){
				Display::Error("in_api @".$this->provider." ".$msg."\n");
			}elseif($get_res){
				Display::Error($get_res."\n");
			}else{
				Display::Error("in_api @".$this->provider." something wrong\n");
			}
			return;
		}
        $a = 0;
        while(true){
			$get_res = $this->res_api($get_in["request"]);
			if($get_res["request"] == "CAPCHA_NOT_READY"){
				if($a>99)$a=99;
				$a = $this->solvingProgress($a,20, $cap);
				continue;
			}
			if($get_res["status"]){
                Display::sukses("Bypass $cap success", false);
				sleep(2);
				Display::clearline();
				return $get_res["request"];
			}
            Display::error("Bypass $cap failed");
			sleep(2);
			Display::clearline();
			Display::Error($cap." @".$this->provider." Error\n");
			return;
		}
    }
    public function getBalance(){
		$res =  json_decode(file_get_contents($this->url."res.php?action=userinfo&key=".$this->key),1);
		return $res["balance"];
	}
	public function RecaptchaV2($sitekey, $pageurl){
		$data = http_build_query(["method" => "userrecaptcha","sitekey" => $sitekey,"pageurl" => $pageurl]);
		return $this->getResult($data, "GET");
	}
    public function AntiBot($source){
		$main = explode('"',explode('data:image/png;base64,',explode('Bot links',$source)[1])[1])[0];
		if(!$main){
			$main = explode('"',explode('data:image/png;base64,',explode('Click the buttons in the following order',$source)[1])[1])[0];
			if(!$main)return 0;
		}
		if($this->provider == "xevil"){
			$data = "method=antibot&main=$main";
		}else{
			$data["method"] = "antibot";$data["main"] = $main;
		}
		
		$src = explode('rel=\"',$source);
		foreach($src as $x => $sour){
			if($x == 0)continue;
			$no = explode('\"',$sour)[0];
			if($this->provider == "xevil"){
				$img = explode('\"',explode('data:image/png;base64,',$sour)[1])[0];
				$data .= "&$no=$img";
			}else{
				$img = explode('\"',explode('src=\"',$sour)[1])[0];
				$data[$no] = $img;
			}
		}
		if($this->provider == "xevil"){
			$res = $this->getResult($data, "POST");
		}else{
			$data = http_build_query($data);
			$ua = "Content-type: application/x-www-form-urlencoded";
			$res = $this->getResult($data, "POST", $ua);
		}
		if($res)return " ".str_replace(","," ",$res);
		return;
	}
}