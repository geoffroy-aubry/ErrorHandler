<?php

require __DIR__ . '/bootstrap.php';

$oProperty = new ReflectionProperty($oErrorHandler, 'bIsRunningFromCLI');
$oProperty->setAccessible(true);
$oProperty->setValue($oErrorHandler, false);

$oErrorHandler->log('');
$oErrorHandler->log('a word');
$oErrorHandler->log(array('key' => 'value'));
echo 'Hello';
