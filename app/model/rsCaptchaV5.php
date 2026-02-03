<?php

class rsCaptchaV5 {

    public $host;
    public $app_id;
    public $public_key;

    public $user_agent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36';
    
    private $iewil;
    private $koordinat;
    private $key;

    public function __construct($host){
        $this->url = "https://rscaptcha.com/";
        $this->host = $host;
        $this->iewil = new Iewil();
    }

    private function headers(){
        return [
            "host: ".parse_url($this->url)['host'],
		    "origin: ".$this->host,
		    "referer: ".$this->host,
		    "accept-language: en-US,en;q=0.9",
		    "priority: u=1, i",
		    "user-agent: ".$this->user_agent
        ];
    }

    private function getCaptcha(){
        $data = [
            "app_id" => $this->app_id,
            "public_key" => $this->public_key,
            "version" => 'v5'
        ];
        return json_decode(curl($this->url . "captcha/v5/get", $this->headers(), $data),1);
    }

    private function getVerif(){
        $data = [
            "app_id" => $this->app_id,
            "public_key" => $this->public_key,
            "version" => "v5",
            "token" => $this->key,
            "response" => $this->koordinat
        ];
        return json_decode(curl($this->url . "captcha/v5/verify", $this->headers(), $data),1);
    }

    public function getresult(){
        while(true){
            $r = $this->getCaptcha();
            if(is_array($r) && isset($r['code']) && $r['code'] == 200){
                $cap = $this->iewil->Slide(
                    $r['data']["master_image_base64"], 
                    $r['data']["thumb_image_base64"],
                    $r['data']["master_width"],
                    $r['data']["master_height"],
                    $r['data']["display_x"],
                    $r['data']["display_y"],
                    $r['data']["thumb_width"],
                );
                if(!$cap)continue;
                $this->koordinat = "{$cap['x']},{$cap['y']}";
                $this->key =  $r['data']["captcha_key"];
            }else{
                continue;
            }

            $r = $this->getVerif();
            if(is_array($r) && isset($r['code']) && $r['code'] == 200){
                return [
                    "rscaptcha_token" => $this->key,
                    "rscaptcha_response" => $r['result']
                ];
            }
        }
    }
}

?>