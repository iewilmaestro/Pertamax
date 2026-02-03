<?php

require "app/autoload.php";

App::boot();
App::helper();   // <- help dulu
App::display();
App::error();

$iewil = new Iewil();

$cap = $iewil->Turnstile('https://satoshifaucet.io/');
print $cap;
?>