<?php

$PATH_BASE = __DIR__ . DIRECTORY_SEPARATOR;
$PATH_INPUT = $PATH_BASE . 'in';
$PATH_STYLE = $PATH_BASE . 'style';
$PATH_OUTPUT = $PATH_BASE . 'out';

$title = isset($argv[1]) ? $argv[1] : null;

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
        $message = substr($match[3], $idx + ($person === '' ? 0 : 2));

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

function addEmojiSupport(string &$html): string
{
    $html .= '<script src="https://twemoji.maxcdn.com/twemoji.min.js"></script>';
    $html .= '<script>
window.onload = function() {

  // Set the size of the rendered Emojis
  // This can be set to 16x16, 36x36, or 72x72
  twemoji.size = \'16x16\';

  // Parse the document body and
  // insert <img> tags in place of Unicode Emojis
  twemoji.parse(document.body);

}
</script>';
    $html .= '<style>
img.emoji {  
  // Override any img styles to ensure Emojis are displayed inline
  margin: 0 1px !important;
  display: inline !important;
}
</style>';

    return $html;
}

function generateHtml(array $groups, string $style, string $title = null): string
{
    $result = '';
    $result .= '<html>';
    $result .= '<head>';
    $result .= '<style>';
    $result .= strip_tags($style);
    $result .= '</style>';
    $result .= '</head>';
    $result .= '<body>';
    $result .= '<div class="background"></div>';

    if ($title !== null) {
        $result .= '<h1>' . $title . '</h1>';
    }

    foreach ($groups as $date => $group) {
        $result .= '<div class="group">';
        $result .= '<div class="group-label"><span>' . $date . '</span></div>';

        foreach ($group as $message) {
            $time = $message['time'];
            $person = $message['person'];
            $personId = $message['personId'];
            $message = $message['message'];

            $result .= '<div class="container" data-person="' . $personId . '">';

            $result .= '<div class="datetime">';
            $result .= '<div class="date">' . $date . '</div>';
            $result .= '<div class="time">' . $time . '</div>';
            $result .= '</div>';
            $result .= '<div class="person">' . $person . '</div>';
            $result .= '<div class="message">' . $message . '</div>';

            $result .= '</div>';
        }

        $result .= '</div>';
    }

    addEmojiSupport($result);

    $result .= '</body>';
    $result .= '</html>';
    return $result;
}

foreach (readFiles($PATH_INPUT, 'txt') as $file) {
    $content = file_get_contents($file);

    // fix quotes
    $content = preg_replace('/"/', '&quot;', $content);

    // fix invalid html
    $content = preg_replace('/<(.*?)>/', '<span class="special">\1</span>', $content);

    // fix lines and linebreaks
    $content = preg_replace('/\r\n/', "\r", $content);
    $content = preg_replace('/\n/', '<br>', $content);
    $content = preg_replace('/\r/', "\r\n", $content);

    // parse data
    $matches = [];
    $result = preg_match_all('/(.*?),\s(.*?):\s(.*?)\r\n/', $content, $matches, PREG_SET_ORDER);

    if ($result === false) {
        throw new Exception('Unexpected result!');
    }

    $groups = groupMatches($matches);

    foreach (readFiles($PATH_STYLE, 'css') as $style) {
        $html = generateHtml($groups, file_get_contents($style), $title);

        $outputFilename = basename($file, '.txt') . '-' . basename($style, '.css') . '.html';

        file_put_contents($PATH_OUTPUT . DIRECTORY_SEPARATOR . $outputFilename, $html);
    }
}
