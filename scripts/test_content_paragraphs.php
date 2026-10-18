<?php
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
define('_GNUBOARD_', true);
define('G5_PATH', dirname(__DIR__));
require G5_PATH.'/willow/content.lib.php';

$cases = array(
    array("first\r\n\r\nsecond", array('first', 'second')),
    array("first\n\nsecond", array('first', 'second')),
    array("first\r\rsecond", array('first', 'second')),
    array("first\r\nsecond", array("first\nsecond")),
    array('<p>first</p><p>second</p>', array('first', 'second')),
    array('first<br>second', array("first\nsecond")),
    array('', array()),
);
foreach ($cases as $index => $case) {
    if (willow_content_paragraphs($case[0]) !== $case[1]) {
        throw new RuntimeException('Paragraph test failed: '.$index);
    }
}
echo "Paragraph tests passed: ".count($cases)."\n";
