<?php

function showTree($dir, $prefix = "") {
    $files = scandir($dir);
    $files = array_diff($files, ['.', '..']);

    foreach ($files as $file) {
        $path = $dir . '/' . $file;
        echo $prefix . "├── " . $file . PHP_EOL;

        if (is_dir($path)) {
            showTree($path, $prefix . "│   ");
        }
    }
}

showTree(__DIR__);
