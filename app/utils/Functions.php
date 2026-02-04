<?php

class Functions {

    public static function xp($html, $start, $end, int $index = 1){
        $Find = explode($start, $html);
        if(isset($Find[$index])){
            return explode($end, $Find[$index])[0];
        }
    }

    public static function cfDecodeEmail($encodedString){
        $k = hexdec(substr($encodedString,0,2));
        for($i=2,$email='';$i<strlen($encodedString)-1;$i+=2){
            $email.=chr(hexdec(substr($encodedString,$i,2))^$k);
        }
        return $email;
    }
}

?>