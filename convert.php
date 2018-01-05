<?php

$PATH_BASE = __DIR__ . DIRECTORY_SEPARATOR;
$PATH_INPUT = $PATH_BASE . 'in';
$PATH_STYLE = $PATH_BASE . 'style';

function readFiles(string $relativePath, string $extension): array
{
    $files = [];

    foreach (scandir($relativePath) as $file) {
        $ext = pathinfo($file, PATHINFO_EXTENSION);
        $ext = strtolower($ext);

        if ($ext !== $extension) {
            continue;
        }

        $files[] = $file;
    }

    return $files;
}

var_dump(readFiles($PATH_INPUT, 'txt'));
var_dump(readFiles($PATH_STYLE, 'css'));
