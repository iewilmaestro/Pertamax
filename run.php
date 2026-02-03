<?php

error_reporting(0);

require "app/autoload.php";

App::boot();
App::helper();
App::display();
App::error();
App::data();

Display::banner();
$captcha = new Captcha();
$scrap = new HtmlScrap();
$iewil = new Iewil();

Display::banner();
$files = array_values(array_filter(scandir("bot"), function($f) {
    return is_file("bot/$f") && pathinfo($f, PATHINFO_EXTENSION) === 'php';
}));

if (empty($files)) {
    echo "Folder bot kosong atau tidak ada file PHP.\n";
    exit;
}

$xfile = array_map(function($f) {
    return pathinfo($f, PATHINFO_FILENAME);
}, $files);

Display::MultiMenu($xfile);
Display::Line();
Display::Isi("nomor pilihan");
$choice = intval(readline());

if ($choice < 1 || $choice > count($files)) {
    Display::Error("Pilihan tidak valid!\n");
    exit;
}
$selected_file = $files[$choice-1];
define('TITLE', pathinfo($selected_file, PATHINFO_FILENAME));

require_once "bot/" . $selected_file;
