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

        $files[] = realpath($relativePath . DIRECTORY_SEPARATOR . $file);
    }

    return $files;
}

function groupMatches(array $matches): array
{
    $result = [];
    $persons = [
        '' => 0,
    ];

    foreach ($matches as $match) {
        $date = $match[1];

        if (!isset($result[$date])) {
            $result[$date] = [];
        }

        $idx = strpos($match[3], ': ');

        if ($idx === false) {
            $idx = 0;
        }

        $time = $match[2];
        $person = substr($match[3], 0, $idx);
        $message = substr($match[3], $idx);

        if (!isset($persons[$person])) {
            $persons[$person] = count($persons);
        }

        $result[$date][] = [
            'time' => $time,
            'person' => $person,
            'personId' => $persons[$person],
            'message' => $message,
        ];
    }

    return $result;
}

foreach (readFiles($PATH_INPUT, 'txt') as $file) {
    $content = file_get_contents($file);

    // fix invalid html
    $content = preg_replace('/<(.*?)>/', '<span class="special">\1</span>', $content);

    // fix lines and linebreaks
    $content = preg_replace('/\r\n/', "\r", $content);
    $content = preg_replace('/\n/', '<br>', $content);
    $content = preg_replace('/\r/', "\r\n", $content);

    // fix quotes
    $content = preg_replace('/"/', '&quot;', $content);

    // parse data
    $matches = [];
    $result = preg_match_all('/(.*?),\s(.*?):\s(.*?)\r\n/', $content, $matches, PREG_SET_ORDER);

    if ($result === false) {
        throw new Exception('Unexpected result!');
    }

    $groups = groupMatches($matches);

    var_dump($groups);
}
