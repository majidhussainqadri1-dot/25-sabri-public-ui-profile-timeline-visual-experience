<?php
declare(strict_types=1);

/**
 * Reviews 915–954: run REST and HTML's actual private Timeline validators.
 * WordPress fixture has no installed/plugin/DB access.
 */
if (! defined('ABSPATH')) { define('ABSPATH', __DIR__ . '/fixtures/'); }
if (! function_exists('wp_strip_all_tags')) {
    function wp_strip_all_tags(string $text): string { return strip_tags($text); }
}
if (! function_exists('wp_unslash')) {
    function wp_unslash(mixed $value): mixed { return $value; }
}
require_once dirname(__DIR__) . '/includes/class-plan-completion.php';
require_once dirname(__DIR__) . '/includes/class-rest-controller.php';
require_once dirname(__DIR__) . '/includes/class-profile-renderer.php';

use Sabri\PublicExperience\Plan_Completion;
use Sabri\PublicExperience\Rest_Controller;
use Sabri\PublicExperience\Profile_Renderer;

$errors = [];
$check = static function (bool $condition, string $message) use (&$errors): void {
    if (! $condition) { $errors[] = $message; }
};
$restSearch = new ReflectionMethod(Rest_Controller::class,'exact_optional_search');
$restSearch->setAccessible(true);
$restTopic = new ReflectionMethod(Rest_Controller::class,'exact_optional_text');
$restTopic->setAccessible(true);
$renderer = (new ReflectionClass(Profile_Renderer::class))->newInstanceWithoutConstructor();
$htmlTopic = new ReflectionMethod(Profile_Renderer::class,'requested_timeline_text');
$htmlTopic->setAccessible(true);

$ur120 = str_repeat('ع',120);
$ur121 = $ur120.'ع';
$emoji120 = str_repeat('🙂',120);
foreach([$ur120,$emoji120] as $valid) {
    $check($restSearch->invoke(null,$valid) === true,
        'REST 120-character Unicode search must be valid.');
    $check($restTopic->invoke(null,$valid,120) === $valid,
        'REST 120-character Unicode topic must remain valid.');
    $_GET['topic'] = $valid;
    $check($htmlTopic->invoke($renderer,'topic',120) === $valid,
        'HTML Timeline topic must agree with REST Unicode allowance.');
}
foreach([$ur121, str_repeat('a',481)] as $invalid) {
    $check($restSearch->invoke(null,$invalid) === false,
        'REST search must reject overlong user inputs.');
    $check($restTopic->invoke(null,$invalid,120) === null,
        'REST topic must reject overlong user inputs.');
    $_GET['topic'] = $invalid;
    $check($htmlTopic->invoke($renderer,'topic',120) === '',
        'HTML Timeline must reject overlong topic inputs.');
}
$unsafe = "موضوع\u{202E}";
$check($restSearch->invoke(null,$unsafe) === false,
    'Hidden bidi overrides must be denied in REST search.');
$check($restTopic->invoke(null,$unsafe,120) === null,
    'Hidden bidi overrides must be denied in REST topic.');
$_GET['topic'] = $unsafe;
$check($htmlTopic->invoke($renderer,'topic',120) === '',
    'Hidden bidi overrides must be denied in public HTML topic.');

$check($restSearch->invoke(null,'') === true
    && $restTopic->invoke(null,'',120) === '',
    'Intentional empty filters must remain allowed.');
$_GET['topic'] = 'موضوع مفید';
$check($htmlTopic->invoke($renderer,'topic',120) === 'موضوع مفید',
    'Safe ordinary Urdu topic must render normally.');
unset($_GET['topic']);
$check($htmlTopic->invoke($renderer,'topic',120) === '',
    'Missing optional topic means unfiltered Timeline.');

if ($errors !== []) {
    fwrite(STDERR,"FAILED File25 Reviews 915-954 REST / HTML Unicode parity\n- "
        . implode("\n- ", $errors) . "\n");
    exit(1);
}
echo "PASS: File25 Reviews 915-954 REST/HTML 120-codepoint Unicode and bidi safety\n";
