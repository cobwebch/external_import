<?php

declare(strict_types=1);

use TYPO3\CodingStandards\CsFixerConfig;

$config = CsFixerConfig::create();
$config->getFinder()->in(__DIR__ . '/../../Build');
$config->getFinder()->in(__DIR__ . '/../../Classes');
$config->getFinder()->in(__DIR__ . '/../../Configuration');
$config->getFinder()->in(__DIR__ . '/../../Tests');
$config->getFinder()->exclude('node_modules');
$config->getFinder()->exclude('rector');
$config->setCacheFile(__DIR__ . '/../../.Build/.cache/.php_cs.cache');
return $config;
