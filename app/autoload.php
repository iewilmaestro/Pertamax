<?php

spl_autoload_register(function($class){

    $dirs = ['modul','utils'];

    foreach ($dirs as $dir) {
        $file = __DIR__ . "/$dir/$class.php";
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }

    // load App.php
    $root = __DIR__ . "/$class.php";
    if (file_exists($root)) {
        require_once $root;
    }
});
