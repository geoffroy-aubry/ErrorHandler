<?php

require __DIR__ . '/../../vendor/autoload.php';

use \GAubry\ErrorHandler\ErrorHandler;

$aConfig = json_decode(base64_decode($argv[1]), true);
$sCodeCoverageJSONPath = (string)$argv[2];

xdebug_start_code_coverage(XDEBUG_CC_UNUSED | XDEBUG_CC_DEAD_CODE);
$oErrorHandler = new ErrorHandler($aConfig);
$oErrorHandler->setCallbackAdditionalShutdownFct(
    function() use ($sCodeCoverageJSONPath, $aConfig) {
        file_put_contents($sCodeCoverageJSONPath, json_encode(xdebug_get_code_coverage()));
        echo $aConfig['shutdown_msg'];
    }
);
