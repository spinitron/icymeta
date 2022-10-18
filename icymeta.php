<?php

/*
 * Extract and display StreamTitle from a media stream using the Shoutcast Metadata Protocol.
 * Thank you, Scott McIntyre! http://www.smackfu.com/stuff/programming/shoutcast.html (since gone)
 * https://web.archive.org/web/20190521093812/https://www.smackfu.com/stuff/programming/shoutcast.html
 *
 * Copyright 2019 by Tom Worster
 *
 * Permission to use, copy, modify, and/or distribute this software for any purpose with or without fee is hereby granted, provided that the above copyright notice and this permission notice appear in all copies.
 *
 * THE SOFTWARE IS PROVIDED “AS IS” AND THE AUTHOR DISCLAIMS ALL WARRANTIES WITH REGARD TO THIS SOFTWARE INCLUDING ALL IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS. IN NO EVENT SHALL THE AUTHOR BE LIABLE FOR ANY SPECIAL, DIRECT, INDIRECT, OR CONSEQUENTIAL DAMAGES OR ANY DAMAGES WHATSOEVER RESULTING FROM LOSS OF USE, DATA OR PROFITS, WHETHER IN AN ACTION OF CONTRACT, NEGLIGENCE OR OTHER TORTIOUS ACTION, ARISING OUT OF OR IN CONNECTION WITH THE USE OR PERFORMANCE OF THIS SOFTWARE.
 */

if (empty($argv[1])) {
    die("Pass stream URL as first argument\n");
}
$url = $argv[1];

$stream = fopen($url, 'rb', false, stream_context_create(['http' => ['header' => ['Icy-MetaData:1']]]));
if ($stream === false) {
    die("Failed to open stream to: {$url}\n");
}

echo array_shift($http_response_header) . "\n";
$icyMetaInterval = false;
foreach ($http_response_header as $header) {
    if (preg_match('{^(?:icy-|content-type)}i', $header)) {
        echo "$header\n";
    }
    if (preg_match('{icy-metaint *: *(\d+)}i', $header, $matches)) {
        $icyMetaInterval = (int) $matches[1];
    }
}
if ($icyMetaInterval === false) {
    die("icy-metaint header not found\n");
}

function streamRead($stream, int $count): string
{
    $buffer = '';
    while (strlen($buffer) < $count) {
        $chunk = fread($stream, $count - strlen($buffer));
        if ($chunk === false) {
            die("Failed to read from stream\n");
        }
        $buffer .= $chunk;
    }

    return $buffer;
}

while (true) {
    $buffer = streamRead($stream, $icyMetaInterval);
    $metaLength = 16 * ord(streamRead($stream, 1));
    if ($metaLength > 0) {
        $metadata = streamRead($stream, $metaLength);
        echo (new \DateTime())->format('H:i:s ');
        if (preg_match("{StreamTitle='(.*?)';}i", $metadata, $matches)) {
            echo $matches[1];
        } else {
            echo 'Metadata parse error: ';
            var_dump($metadata);
        }
        echo "\n";
    }
}
