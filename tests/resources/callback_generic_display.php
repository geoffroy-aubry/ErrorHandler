<?php

require __DIR__ . '/bootstrap.php';

$oErrorHandler->setCallbackGenericDisplay(
    function (\Exception $oException) {
        echo '>>>' . $oException->getMessage() . '<<<';
    }
);
f();
echo 'Hello';
