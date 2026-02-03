<?php

class Iewil {

    protected string $res;
    protected string $req;

    function __construct(){
        $this->res = "http://az.vpnbersama.us:13141/api/res.php";
        $this->req = "http://az.vpnbersama.us:13141/api/req.php";
    }

    // ===== PRIVATE REQUEST =====
    private function request($url, $postParams = 0): array {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        if (!empty($postParams)) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postParams));
        }

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            $err = curl_error($ch);
            curl_close($ch);
            return ["status" => false, "message" => "CURL error: $err"];
        }

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            return ["status" => false, "message" => "HTTP code: $httpCode"];
        }

        $decoded = json_decode($response, true);
        if ($decoded === null) {
            return ["status" => false, "message" => "Invalid JSON response"];
        }

        return $decoded;
    }

    // ===== FLASH MESSAGE CLI =====
    private function flashMessage(string $message){
        Display::Error($message);
        sleep(2);
        Display::clearLine();
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
    // ===== PRIVATE GET RESULT =====
    private function getResult(array $postParams) {

        $method = $postParams['method'];

        $req = $this->request($this->req, $postParams);

        if (empty($req['request_id'])) {
            $this->flashMessage($req['message'] ?? 'request failed');
            return false;
        }

        $requestId = $req['request_id'];
        $a = 0;
        while (true) {
            $res = $this->request($this->res. "?request_id=".  $requestId);
            $msg = $res['message'] ?? null;
            if ($msg === 'being bypassed') {
                if($a>99)$a=99;
				$a = $this->solvingProgress($a,20, $method);
				continue;
            }
            if (!empty($res['status']) && !empty($res['result'])) {
                Display::sukses("Bypass $method success", false);
				sleep(2);
				Display::clearline();
                return json_decode($res['result'],true);
            }

            $msg = $res['message'] ?? null;
            if ($msg) {
                Display::error("$method: $msg");
            } else {
                Display::error("$method: captcha can't be solved");
            }
			sleep(2);
			Display::clearline();
            return false;
        }
    }

    // ===== PUBLIC FUNCTIONS =====
    public function Turnstile(string $pageUrl) {
        return $this->getResult([
            "pageurl" => $pageUrl,
            "method"  => "turnstile"
        ]);
    }

    public function Slide(
        string $background,
        string $piece,
        int $masterWidth,
        int $masterHeight,
        int $displayX,
        int $displayY,
        int $thumbWidth
    ) {
        return $this->getResult([
            "background"    => $background,
            "piece"         => $piece,
            "master_width"  => $masterWidth,
            "master_height" => $masterHeight,
            "display_x"     => $displayX,
            "display_y"     => $displayY,
            "thumb_width"   => $thumbWidth,
            "method"        => "rsslider"
        ]);
    }

    public function Antibot(string $source) {

        $data = ["method" => "antibot"];

        $main = @explode('"', @explode('src="', @explode('Bot links', $source)[1])[1])[0] ?? null;
        if (!$main) return false;
        $data['main'] = $main;
        
        $srcs = explode('rel=\"', $source);
        foreach ($srcs as $i => $s) {
            if ($i === 0) continue;
            $no  = explode('\"', $s)[0] ?? null;
            $img = explode('\"', @explode('src=\"', $s)[1])[0] ?? null;
            if ($no && $img) $data[$no] = $img;
        }
        $res = $this->getResult($data);

        if ($res) {
            $countData = count($data) - 2; // minus method & main
            $pieces = count(explode(',', $res));
            if ($countData === $pieces) {
                return str_replace(',', ' ', $res);
            }
        }

        return false;
    }

    public function IconCoordinate(string $base64Img) {
        return $this->getResult([
            'img'    => $base64Img,
            'method' => 'icon_coordinat'
        ]);
    }

    public function gp($src){
        return $this->getResult([
            "main"		=> base64_encode($src),
			"method"	=> "gp"
        ]);
	}

    public function altcha($signature, $salt, $challenge){
        return $this->getResult([
            "signature"	=> $signature,
			"salt"		=> $salt,
			"challenge"	=> $challenge,
			"method"	=> "altcha"
        ]);
	}

    public function Zera($postParameter){
		/*
		$postParameter = [
			"method"	=> "zera",
			"main_image" => imgToBase64("main.png"),
			"images" => [
				"gambar1.png" => imgToBase64("gambar1.png"),
				"gambar2.png" => imgToBase64("gambar2.png"),
				"gambar3.png" => imgToBase64("gambar3.png"),
				"gambar4.png" => imgToBase64("gambar4.png"),
				"gambar5.png" => imgToBase64("gambar5.png")
			]
		];
		*/
		return $this->getResult($postParameter);
	}
}
