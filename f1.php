<?php
$industry = filter_input(INPUT_GET, 'industry', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($industry) {
    header('Location: desc.php?desc=' . $industry, true, 302);
    exit;
}
header('Location: music.php', true, 302);
exit;
