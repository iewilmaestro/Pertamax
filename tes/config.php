<?php

require "app/autoload.php";

define("TITLE", 'abc');

$r = Config::load();
if(count($r) < 1){
    Config::simpan(['username','password']);
}
// Config::hapus(0); -> untuk hapus data index ke n
Config::hapus(0, 'username');

$r = Config::load();
if(!isset($r[0]['username'])){
    Config::tambahKey(0, 'username');
}

$r = Config::load();
print_r($r);