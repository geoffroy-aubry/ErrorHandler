<?php

namespace GAubry\ErrorHandler\Tests;

use GAubry\ErrorHandler\ErrorHandler;

/**
 *
 *
 * @TODO trigger_error
 * @TODO @
 * @TODO _aExcludedPaths
 */
class ErrorHandlerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     */
    public function setUp ()
    {
    }

    /**
     * Tears down the fixture, for example, close a network connection.
     * This method is called after a test is executed.
     */
    public function tearDown()
    {
    }

    private function _exec ($sScriptName, array $aConfig)
    {
        $sResourcesDir = __DIR__ . '/../../resources';
        $sStdErrPath = tempnam(sys_get_temp_dir(), 'error-handler-');
        $sCodeCoverageJSONPath = tempnam(sys_get_temp_dir(), 'error-handler-');
        $aConfig['error_log_path'] = ($aConfig['with_error_log_path'] ? tempnam(sys_get_temp_dir(), 'error-handler-') : '');
        $sConfig = base64_encode(json_encode($aConfig));
        $sCmd = "php $sResourcesDir/$sScriptName $sConfig"
              . " '$sCodeCoverageJSONPath'"
              . " 2>$sStdErrPath";
        $aOutput = '';
        $iErrorCode = 0;
        exec($sCmd, $aOutput, $iErrorCode);

        $sStdErr = file_get_contents($sStdErrPath);
        unlink($sStdErrPath);
        $aCoverage = json_decode(file_get_contents($sCodeCoverageJSONPath), true);
        unlink($sCodeCoverageJSONPath);
        $sErrorLogContent = file_get_contents($aConfig['error_log_path']);
        unlink($aConfig['error_log_path']);
        if ( ! empty($aCoverage)) {
            $this->getTestResultObject()->getCodeCoverage()->append($aCoverage);
        }

        return array(implode("\n", $aOutput), $iErrorCode, $sStdErr, $sErrorLogContent);
    }

    public function testControl ()
    {
        $aConfig = array(
            'display_errors'        => true,
            'with_error_log_path'   => true,
            'error_reporting_level' => -1,
            'auth_error_suppr_op'   => false,
            'default_error_code'    => 1,
            'shutdown_msg'          => 'down'
        );
        list($sStdOut, $iErrorCode, $sStdErr, $sErrorLogContent) = $this->_exec('control.php', $aConfig);
        $this->assertEquals('Hello' . $aConfig['shutdown_msg'], $sStdOut);
        $this->assertEquals(0, $iErrorCode);
        $this->assertEmpty($sStdErr);
        $this->assertEmpty($sErrorLogContent);
    }

    public function testNotice_WithDisplayErrors ()
    {
        $aConfig = array(
            'display_errors'        => true,
            'with_error_log_path'   => true,
            'error_reporting_level' => -1,
            'auth_error_suppr_op'   => false,
            'default_error_code'    => 17,
            'shutdown_msg'          => 'down'
        );
        list($sStdOut, $iErrorCode, $sStdErr, $sErrorLogContent) = $this->_exec('notice.php', $aConfig);
        $this->assertEquals($aConfig['shutdown_msg'], $sStdOut);
        $this->assertEquals($aConfig['default_error_code'], $iErrorCode);
        $sErrorMsg = "exception 'ErrorException' with message '[from error handler] NOTICE"
                   . ' -- Undefined variable: unkown';
        $this->assertContains($sErrorMsg, $sStdErr);
        $this->assertContains($sErrorMsg, $sErrorLogContent);
    }

    public function testNotice_WithoutDisplayErrors ()
    {
        $aConfig = array(
            'display_errors'        => false,
            'with_error_log_path'   => true,
            'error_reporting_level' => -1,
            'auth_error_suppr_op'   => false,
            'default_error_code'    => 17,
            'shutdown_msg'          => 'down'
        );
        list($sStdOut, $iErrorCode, $sStdErr, $sErrorLogContent) = $this->_exec('notice.php', $aConfig);
        $this->assertEquals($aConfig['shutdown_msg'], $sStdOut);
        $this->assertEquals($aConfig['default_error_code'], $iErrorCode);
        $this->assertEmpty($sStdErr);
        $sErrorMsg = "exception 'ErrorException' with message '[from error handler] NOTICE"
            . ' -- Undefined variable: unkown';
        $this->assertContains($sErrorMsg, $sErrorLogContent);
    }

    public function testNotice_WithDisplayErrors_WithHighErrorLevel ()
    {
        $aConfig = array(
            'display_errors'        => true,
            'with_error_log_path'   => true,
            'error_reporting_level' => E_WARNING,
            'auth_error_suppr_op'   => false,
            'default_error_code'    => 17,
            'shutdown_msg'          => 'down'
        );
        list($sStdOut, $iErrorCode, $sStdErr, $sErrorLogContent) = $this->_exec('notice.php', $aConfig);
        $this->assertEquals('Hello' . $aConfig['shutdown_msg'], $sStdOut);
        $this->assertEquals(0, $iErrorCode);
        $this->assertEmpty($sStdErr);
        $this->assertEmpty($sErrorLogContent);
    }

    public function testWarning_WithDisplayErrors ()
    {
        $aConfig = array(
            'display_errors'        => true,
            'with_error_log_path'   => true,
            'error_reporting_level' => E_WARNING,
            'auth_error_suppr_op'   => false,
            'default_error_code'    => 17,
            'shutdown_msg'          => 'down'
        );
        list($sStdOut, $iErrorCode, $sStdErr, $sErrorLogContent) = $this->_exec('warning.php', $aConfig);
        $this->assertEquals($aConfig['shutdown_msg'], $sStdOut);
        $this->assertEquals($aConfig['default_error_code'], $iErrorCode);
        $sErrorMsg = "exception 'ErrorException' with message '[from error handler] WARNING"
            . ' -- Division by zero';
        $this->assertContains($sErrorMsg, $sStdErr);
        $this->assertContains($sErrorMsg, $sErrorLogContent);
    }

    public function testWarning_WithDisplayErrors_WithHighErrorLevel ()
    {
        $aConfig = array(
            'display_errors'        => true,
            'with_error_log_path'   => true,
            'error_reporting_level' => E_ERROR,
            'auth_error_suppr_op'   => false,
            'default_error_code'    => 17,
            'shutdown_msg'          => 'down'
        );
        list($sStdOut, $iErrorCode, $sStdErr, $sErrorLogContent) = $this->_exec('warning.php', $aConfig);
        $this->assertEquals('Hello' . $aConfig['shutdown_msg'], $sStdOut);
        $this->assertEquals(0, $iErrorCode);
        $this->assertEmpty($sStdErr);
        $this->assertEmpty($sErrorLogContent);
    }

    public function testFatalError_WithDisplayErrors ()
    {
        $aConfig = array(
            'display_errors'        => true,
            'with_error_log_path'   => true,
            'error_reporting_level' => -1,
            'auth_error_suppr_op'   => false,
            'default_error_code'    => 17,
            'shutdown_msg'          => 'down'
        );
        list($sStdOut, $iErrorCode, $sStdErr, $sErrorLogContent) = $this->_exec('fatal_error.php', $aConfig);
        $sErrorMsg = 'Fatal error: Call to undefined function f()';
        $this->assertContains($sErrorMsg, $sStdOut);
        $this->assertContains($aConfig['shutdown_msg'], $sStdOut);
        $this->assertNotContains('We are sorry, an internal error occurred.', $sStdOut);
        $this->assertEquals(255, $iErrorCode);
        $this->assertEmpty($sStdErr);
        $sErrorMsg = 'Fatal error:  Call to undefined function f()';
        $this->assertContains($sErrorMsg, $sErrorLogContent);
    }

    public function testFatalError_WithoutDisplayErrors ()
    {
        $aConfig = array(
            'display_errors'        => false,
            'with_error_log_path'   => true,
            'error_reporting_level' => -1,
            'auth_error_suppr_op'   => false,
            'default_error_code'    => 17,
            'shutdown_msg'          => 'down'
        );
        list($sStdOut, $iErrorCode, $sStdErr, $sErrorLogContent) = $this->_exec('fatal_error.php', $aConfig);
        $sApologiesMsg = '<div class="exception-handler-message">We are sorry, an internal error occurred.<br />'
                       . 'We apologize for any inconvenience this may cause</div>';
        $this->assertEquals($sApologiesMsg . $aConfig['shutdown_msg'], $sStdOut);
        $this->assertEquals(255, $iErrorCode);
        $sErrorMsg = 'Fatal error:  Call to undefined function f()';
        $this->assertEmpty($sStdErr);
        $this->assertContains($sErrorMsg, $sErrorLogContent);
    }

    public function testLog_WithDisplayErrors ()
    {
        $aConfig = array(
            'display_errors'        => true,
            'with_error_log_path'   => true,
            'error_reporting_level' => -1,
            'auth_error_suppr_op'   => false,
            'default_error_code'    => 17,
            'shutdown_msg'          => 'down'
        );
        list($sStdOut, $iErrorCode, $sStdErr, $sErrorLogContent) = $this->_exec('log.php', $aConfig);
        $this->assertEquals('Hello' . $aConfig['shutdown_msg'], $sStdOut);
        $this->assertEquals(0, $iErrorCode);
        $this->assertEquals("\na word\n" . print_r(array('key' => 'value'), true) . "\n", $sStdErr);
        $this->assertContains("] \n", $sErrorLogContent);
        $this->assertContains("] a word\n", $sErrorLogContent);
        $this->assertContains(print_r(array('key' => 'value'), true) . "\n", $sErrorLogContent);
    }

    public function testLog_WithoutDisplayErrors ()
    {
        $aConfig = array(
            'display_errors'        => false,
            'with_error_log_path'   => true,
            'error_reporting_level' => -1,
            'auth_error_suppr_op'   => false,
            'default_error_code'    => 17,
            'shutdown_msg'          => 'down'
        );
        list($sStdOut, $iErrorCode, $sStdErr, $sErrorLogContent) = $this->_exec('log.php', $aConfig);
        $this->assertEquals('Hello' . $aConfig['shutdown_msg'], $sStdOut);
        $this->assertEquals(0, $iErrorCode);
        $this->assertEmpty($sStdErr);
        $this->assertContains("] \n", $sErrorLogContent);
        $this->assertContains("] a word\n", $sErrorLogContent);
        $this->assertContains(print_r(array('key' => 'value'), true) . "\n", $sErrorLogContent);
    }

    public function testLog_NotCLI ()
    {
        $aConfig = array(
            'display_errors'        => true,
            'with_error_log_path'   => true,
            'error_reporting_level' => -1,
            'auth_error_suppr_op'   => false,
            'default_error_code'    => 17,
            'shutdown_msg'          => 'down'
        );
        list($sStdOut, $iErrorCode, $sStdErr, $sErrorLogContent) = $this->_exec('log_not_cli.php', $aConfig);
        $sMsg = "a word" . print_r(array('key' => 'value'), true) . 'Hello' . $aConfig['shutdown_msg'];
        $this->assertEquals($sMsg, $sStdOut);
        $this->assertEquals(0, $iErrorCode);
        $this->assertEmpty($sStdErr);
        $this->assertContains("] \n", $sErrorLogContent);
        $this->assertContains("] a word\n", $sErrorLogContent);
        $this->assertContains(print_r(array('key' => 'value'), true) . "\n", $sErrorLogContent);
    }

    public function testInternalExceptionHandler_WithErrorCode ()
    {
        $aConfig = array(
            'display_errors'        => true,
            'with_error_log_path'   => true,
            'error_reporting_level' => -1,
            'auth_error_suppr_op'   => false,
            'default_error_code'    => 17,
            'shutdown_msg'          => 'down'
        );
        list($sStdOut, $iErrorCode, $sStdErr, $sErrorLogContent) =
            $this->_exec('exception_with_error_code.php', $aConfig);
        $this->assertEquals($aConfig['shutdown_msg'], $sStdOut);
        $this->assertEquals(3, $iErrorCode);
        $sErrorMsg = "exception 'RuntimeException' with message 'Bad !'";
        $this->assertContains($sErrorMsg, $sStdErr);
        $this->assertContains($sErrorMsg, $sErrorLogContent);
    }

    public function testInternalExceptionHandler_WithoutErrorCode ()
    {
        $aConfig = array(
            'display_errors'        => true,
            'with_error_log_path'   => true,
            'error_reporting_level' => -1,
            'auth_error_suppr_op'   => false,
            'default_error_code'    => 17,
            'shutdown_msg'          => 'down'
        );
        list($sStdOut, $iErrorCode, $sStdErr, $sErrorLogContent) =
            $this->_exec('exception_without_error_code.php', $aConfig);
        $this->assertEquals($aConfig['shutdown_msg'], $sStdOut);
        $this->assertEquals($aConfig['default_error_code'], $iErrorCode);
        $sErrorMsg = "exception 'RuntimeException' with message 'Bad !'";
        $this->assertContains($sErrorMsg, $sStdErr);
        $this->assertContains($sErrorMsg, $sErrorLogContent);
    }

    public function testInternalExceptionHandler_NotCLI ()
    {
        $aConfig = array(
            'display_errors'        => false,
            'with_error_log_path'   => true,
            'error_reporting_level' => -1,
            'auth_error_suppr_op'   => false,
            'default_error_code'    => 17,
            'shutdown_msg'          => 'down'
        );
        list($sStdOut, $iErrorCode, $sStdErr, $sErrorLogContent) =
        $this->_exec('exception_not_cli.php', $aConfig);
        $sApologiesMsg = '<div class="exception-handler-message">We are sorry, an internal error occurred.<br />'
                       . 'We apologize for any inconvenience this may cause</div>';
        $this->assertEquals($sApologiesMsg . $aConfig['shutdown_msg'], $sStdOut);
        $this->assertEquals($aConfig['default_error_code'], $iErrorCode);
        $sErrorMsg = "exception 'RuntimeException' with message 'Bad !'";
        $this->assertEmpty($sStdErr);
        $this->assertContains($sErrorMsg, $sErrorLogContent);
    }

    public function testAddExcludedPath ()
    {
        $aConfig = array(
            'display_errors'        => true,
            'with_error_log_path'   => true,
            'error_reporting_level' => -1,
            'auth_error_suppr_op'   => false,
            'default_error_code'    => 17,
            'shutdown_msg'          => 'down'
        );
        list($sStdOut, $iErrorCode, $sStdErr, $sErrorLogContent) = $this->_exec('exclude_path.php', $aConfig);
        $this->assertEquals('Hello' . $aConfig['shutdown_msg'], $sStdOut);
        $this->assertEquals(0, $iErrorCode);
        $this->assertEmpty($sStdErr);
        $this->assertEmpty($sErrorLogContent);
    }

    public function testSetCallbackGenericDisplay ()
    {
        $aConfig = array(
            'display_errors'        => false,
            'with_error_log_path'   => true,
            'error_reporting_level' => -1,
            'auth_error_suppr_op'   => false,
            'default_error_code'    => 17,
            'shutdown_msg'          => 'down'
        );
        list($sStdOut, $iErrorCode, $sStdErr, $sErrorLogContent) =
            $this->_exec('callback_generic_display.php', $aConfig);
        $this->assertEquals('>>>Call to undefined function f()<<<' . $aConfig['shutdown_msg'], $sStdOut);
        $this->assertEquals(255, $iErrorCode);
        $sErrorMsg = 'Fatal error:  Call to undefined function f()';
        $this->assertEmpty($sStdErr);
        $this->assertContains($sErrorMsg, $sErrorLogContent);
    }

    public function testAtSign_NotAuthorized ()
    {
        $aConfig = array(
            'display_errors'        => true,
            'with_error_log_path'   => true,
            'error_reporting_level' => -1,
            'auth_error_suppr_op'   => false,
            'default_error_code'    => 17,
            'shutdown_msg'          => 'down'
        );
        list($sStdOut, $iErrorCode, $sStdErr, $sErrorLogContent) =
            $this->_exec('at_sign.php', $aConfig);
        $this->assertEquals($aConfig['shutdown_msg'], $sStdOut);
        $this->assertEquals($aConfig['default_error_code'], $iErrorCode);
        $sErrorMsg = "exception 'ErrorException' with message '[from error handler] WARNING"
                   . ' -- include(not_exists): failed to open stream: No such file or directory';
        $this->assertContains($sErrorMsg, $sStdErr);
        $this->assertContains($sErrorMsg, $sErrorLogContent);
    }

    public function testAtSign_Authorized ()
    {
        $aConfig = array(
            'display_errors'        => true,
            'with_error_log_path'   => true,
            'error_reporting_level' => -1,
            'auth_error_suppr_op'   => true,
            'default_error_code'    => 17,
            'shutdown_msg'          => 'down'
        );
        list($sStdOut, $iErrorCode, $sStdErr, $sErrorLogContent) =
            $this->_exec('at_sign.php', $aConfig);
        $this->assertEquals('Hello' . $aConfig['shutdown_msg'], $sStdOut);
        $this->assertEquals(0, $iErrorCode);
        $this->assertEmpty($sStdErr);
        $this->assertEmpty($sErrorLogContent);
    }
}
