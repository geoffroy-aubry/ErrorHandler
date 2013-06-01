<?php

require __DIR__ . '/bootstrap.php';

$oProperty = new ReflectionProperty($oErrorHandler, 'bIsRunningFromCLI');
$oProperty->setAccessible(true);
$oProperty->setValue($oErrorHandler, false);

throw new RuntimeException('Bad !');
echo 'Hello';
