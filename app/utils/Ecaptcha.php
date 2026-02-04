<?php

class Ecaptcha {

    public $headers;
    public $host;

    public function __construct($host){
        $this->host = $host;
    }

    private function getToken(){
        return json_decode(curl(host."ecaptcha/get_token",$this->headers),1);
    }

    private function getCaptcha(){
        return json_decode(curl(host."ecaptcha/get_captcha",$this->headers),1);
    }

    private function Validate($captcha_token, $captcha_key, $question){
        $data = http_build_query([
			"selected" => "$question.gif",
			"key"	=> $captcha_key,
			"token"	=> $captcha_token
		]);
        return json_decode(curl(host."ecaptcha/validate_icon",$this->headers, $data),1);
    }
    public function getResult(){
        $result = [];

        while(true){
            $r = $this->getToken();
            if(is_array($r) && $r["token"]){
                $result["captcha_token"] = $r["token"];
            }else{
                return;
            }
            $r = $this->getCaptcha();
            $result["captcha_key"] = $r["captcha_key"];
            $question = strtolower(trim(explode(':', $r["question"])[1]));

            $r = $this->Validate($result["captcha_token"], $result["captcha_key"], $question);
            if($r["status"] == "valid"){
                $result["captcha"] = "emoji_captcha";
                $result["selected_icon"] = "$question.gif";
                return $result;
            }
        }
    }
}

?>