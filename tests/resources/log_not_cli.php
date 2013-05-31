<?php

require __DIR__ . '/bootstrap.php';

$oProperty = new ReflectionProperty($oErrorHandler, '_bIsRunningFromCLI');
$oProperty->setAccessible(true);
$oProperty->setValue($oErrorHandler, false);

$oErrorHandler->log('');
$oErrorHandler->log('a word');
$oErrorHandler->log(array('key' => 'value'));
echo 'Hello';
