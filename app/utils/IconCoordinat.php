<?php

class IconCoordinat {

    public $host;
    public $token;
    public $icon_header;
    public $theme = "light";
    public $patch = "icaptcha/req";
    
    private $iewil;
    private $widgetID;
    private $challengeId;
    private $cap;

    public function __construct($host){
        $this->host = $host;
        $this->iewil = new Iewil();
    }
    
    private function headers(){
        if(!$this->icon_header){
            Display::Error("icon Headers belum di set\n");
            exit;
        }
        $icon_header = [];
        $icon_header = $this->icon_header;
        $icon_header[] = "origin: ".$this->host;
        $icon_header[] = "referer: ".$this->host;
        $icon_header[] = "x-iconcaptcha-token: ".$this->token;
        $icon_header[] = "x-requested-with: XMLHttpRequest";
        return $icon_header;
    }

    private function generateWidgetId() {
        $uuid = '';
        for ($n = 0; $n < 32; $n++) {
            if ($n == 8 || $n == 12 || $n == 16 || $n == 20) {
                $uuid .= '-';
            }
            $e = mt_rand(0, 15);
            if ($n == 12) {
                $e = 4;
            } elseif ($n == 16) {
                $e = ($e & 0x3) | 0x8;
            }
            $uuid .= dechex($e);
        }
        return $uuid;
    }

    private function randomTimestamp(){
        $data['timestamp'] = round(microtime(true) * 1000);
	    $data['initTimestamp'] = $data['timestamp'] - 2000;
        return $data;
    }

    private function getChallenge(){
        $x = $this->randomTimestamp();
        $data = ["payload" => 
            base64_encode(json_encode([
                "widgetId"	=> $this->widgetID,
                "action" 	=> "LOAD",
                "theme" 	=> $this->theme,
                "token" 	=> $this->token,
                "timestamp"	=> $x['timestamp'],
                "initTimestamp"	=> $x['initTimestamp']
            ]))
        ];
        return json_decode(base64_decode(curl($this->host.$this->patch,$this->headers(), $data)),1);
    }

    private function getComplete(){
        $x = $this->randomTimestamp();
        $data = ["payload" => 
            base64_encode(json_encode([
                "widgetId"		=> $this->widgetID,
                "challengeId"	=> $this->challengeId,
                "action"		=> "SELECTION",
                "x"				=> $this->cap['x'],
                "y"				=> 24,
                "width"			=> 320,
                "token" 		=> $this->token,
                "timestamp"		=> $x['timestamp'],
                "initTimestamp"	=> $x['initTimestamp']
            ]))
        ];
        return json_decode(base64_decode(curl(host.$this->patch,$this->headers(), $data)),1);
    }

    public function getResult(){
        while(true){
            $this->widgetID = $this->generateWidgetId();
            $r = $this->getChallenge();
            if(!isset($r["challenge"]))continue;
            $base64Image = $r["challenge"];
            $this->challengeId = $r["identifier"];
            $this->cap = $this->iewil->IconCoordinate($base64Image);
            if(!$this->cap)continue;
            $r = $this->getComplete();
            if (($r['completed'] ?? false) === false) {
                continue;
            }elseif(isset($r['completed'])){
                $data = [];
                $data['captcha'] = "icaptcha";
                $data['_iconcaptcha-token'] = $this->token;
                $data['ic-rq'] = 1;
                $data['ic-wid'] = $this->widgetID;
                $data['ic-cid'] = $this->challengeId;
                $data['ic-hp'] = '';
                return $data;
            }
        }
    }
}

?>