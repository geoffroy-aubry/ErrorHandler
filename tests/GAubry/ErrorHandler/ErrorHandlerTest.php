<?php

namespace GAubry\ErrorHandler\Tests;

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

    private function exec ($sScriptName, array $aConfig)
    {
        $sResourcesDir = __DIR__ . '/../../resources';
        $sStdErrPath = tempnam(sys_get_temp_dir(), 'error-handler-');
        $sCodeCoverageJSONPath = tempnam(sys_get_temp_dir(), 'error-handler-');
        if ($aConfig['with_error_log_path']) {
            $aConfig['error_log_path'] = tempnam(sys_get_temp_dir(), 'error-handler-');
        } else {
            $aConfig['error_log_path'] = '';
        }
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
        if (! empty($aCoverage)) {
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
        list($sStdOut, $iErrorCode, $sStdErr, $sErrorLogContent) = $this->exec('control.php', $aConfig);
        $this->assertEquals('Hello' . $aConfig['shutdown_msg'], $sStdOut);
        $this->assertEquals(0, $iErrorCode);
        $this->assertEmpty($sStdErr);
        $this->assertEmpty($sErrorLogContent);
    }

    public function testNoticeWithDisplayErrors ()
    {
        $aConfig = array(
            'display_errors'        => true,
            'with_error_log_path'   => true,
            'error_reporting_level' => -1,
            'auth_error_suppr_op'   => false,
            'default_error_code'    => 17,
            'shutdown_msg'          => 'down'
        );
        list($sStdOut, $iErrorCode, $sStdErr, $sErrorLogContent) = $this->exec('notice.php', $aConfig);
        $this->assertEquals($aConfig['shutdown_msg'], $sStdOut);
        $this->assertEquals($aConfig['default_error_code'], $iErrorCode);
        $sErrorMsg = "exception 'ErrorException' with message '[from error handler] NOTICE"
                   . ' -- Undefined variable: unkown';
        $this->assertContains($sErrorMsg, $sStdErr);
        $this->assertContains($sErrorMsg, $sErrorLogContent);
    }

    public function testNoticeWithoutDisplayErrors ()
    {
        $aConfig = array(
            'display_errors'        => false,
            'with_error_log_path'   => true,
            'error_reporting_level' => -1,
            'auth_error_suppr_op'   => false,
            'default_error_code'    => 17,
            'shutdown_msg'          => 'down'
        );
        list($sStdOut, $iErrorCode, $sStdErr, $sErrorLogContent) = $this->exec('notice.php', $aConfig);
        $this->assertEquals($aConfig['shutdown_msg'], $sStdOut);
        $this->assertEquals($aConfig['default_error_code'], $iErrorCode);
        $this->assertEmpty($sStdErr);
        $sErrorMsg = "exception 'ErrorException' with message '[from error handler] NOTICE"
            . ' -- Undefined variable: unkown';
        $this->assertContains($sErrorMsg, $sErrorLogContent);
    }

    public function testNoticeWithDisplayErrorsWithHighErrorLevel ()
    {
        $aConfig = array(
            'display_errors'        => true,
            'with_error_log_path'   => true,
            'error_reporting_level' => E_WARNING,
            'auth_error_suppr_op'   => false,
            'default_error_code'    => 17,
            'shutdown_msg'          => 'down'
        );
        list($sStdOut, $iErrorCode, $sStdErr, $sErrorLogContent) = $this->exec('notice.php', $aConfig);
        $this->assertEquals('Hello' . $aConfig['shutdown_msg'], $sStdOut);
        $this->assertEquals(0, $iErrorCode);
        $this->assertEmpty($sStdErr);
        $this->assertEmpty($sErrorLogContent);
    }

    public function testWarningWithDisplayErrors ()
    {
        $aConfig = array(
            'display_errors'        => true,
            'with_error_log_path'   => true,
            'error_reporting_level' => E_WARNING,
            'auth_error_suppr_op'   => false,
            'default_error_code'    => 17,
            'shutdown_msg'          => 'down'
        );
        list($sStdOut, $iErrorCode, $sStdErr, $sErrorLogContent) = $this->exec('warning.php', $aConfig);
        $this->assertEquals($aConfig['shutdown_msg'], $sStdOut);
        $this->assertEquals($aConfig['default_error_code'], $iErrorCode);
        $sErrorMsg = "exception 'ErrorException' with message '[from error handler] WARNING"
            . ' -- Division by zero';
        $this->assertContains($sErrorMsg, $sStdErr);
        $this->assertContains($sErrorMsg, $sErrorLogContent);
    }

    public function testWarningWithDisplayErrorsWithHighErrorLevel ()
    {
        $aConfig = array(
            'display_errors'        => true,
            'with_error_log_path'   => true,
            'error_reporting_level' => E_ERROR,
            'auth_error_suppr_op'   => false,
            'default_error_code'    => 17,
            'shutdown_msg'          => 'down'
        );
        list($sStdOut, $iErrorCode, $sStdErr, $sErrorLogContent) = $this->exec('warning.php', $aConfig);
        $this->assertEquals('Hello' . $aConfig['shutdown_msg'], $sStdOut);
        $this->assertEquals(0, $iErrorCode);
        $this->assertEmpty($sStdErr);
        $this->assertEmpty($sErrorLogContent);
    }

    public function testFatalErrorWithDisplayErrors ()
    {
        $aConfig = array(
            'display_errors'        => true,
            'with_error_log_path'   => true,
            'error_reporting_level' => -1,
            'auth_error_suppr_op'   => false,
            'default_error_code'    => 17,
            'shutdown_msg'          => 'down'
        );
        list($sStdOut, $iErrorCode, $sStdErr, $sErrorLogContent) = $this->exec('fatal_error.php', $aConfig);
        $this->assertEquals($aConfig['shutdown_msg'], $sStdOut);
        $this->assertEquals(255, $iErrorCode);
        $sErrorMsg = 'Fatal error: Call to undefined function f()';
        $this->assertContains($sErrorMsg, $sStdErr);
        $sErrorMsg = 'Fatal error:  Call to undefined function f()';
        $this->assertContains($sErrorMsg, $sErrorLogContent);
    }

    public function testFatalErrorWithoutDisplayErrors ()
    {
        $aConfig = array(
            'display_errors'        => false,
            'with_error_log_path'   => true,
            'error_reporting_level' => -1,
            'auth_error_suppr_op'   => false,
            'default_error_code'    => 17,
            'shutdown_msg'          => 'down'
        );
        list($sStdOut, $iErrorCode, $sStdErr, $sErrorLogContent) = $this->exec('fatal_error.php', $aConfig);
        $sApologiesMsg = '<div class="exception-handler-message">We are sorry, an internal error occurred.<br />'
                       . 'We apologize for any inconvenience this may cause</div>';
        $this->assertEquals($sApologiesMsg . $aConfig['shutdown_msg'], $sStdOut);
        $this->assertEquals(255, $iErrorCode);
        $sErrorMsg = 'Fatal error:  Call to undefined function f()';
        $this->assertEmpty($sStdErr);
        $this->assertContains($sErrorMsg, $sErrorLogContent);
    }

    public function testLogWithDisplayErrors ()
    {
        $aConfig = array(
            'display_errors'        => true,
            'with_error_log_path'   => true,
            'error_reporting_level' => -1,
            'auth_error_suppr_op'   => false,
            'default_error_code'    => 17,
            'shutdown_msg'          => 'down'
        );
        list($sStdOut, $iErrorCode, $sStdErr, $sErrorLogContent) = $this->exec('log.php', $aConfig);
        $this->assertEquals('Hello' . $aConfig['shutdown_msg'], $sStdOut);
        $this->assertEquals(0, $iErrorCode);
        $this->assertEquals("\na word\n" . print_r(array('key' => 'value'), true) . "\n", $sStdErr);
        $this->assertContains("] \n", $sErrorLogContent);
        $this->assertContains("] a word\n", $sErrorLogContent);
        $this->assertContains(print_r(array('key' => 'value'), true) . "\n", $sErrorLogContent);
    }

    public function testLogWithoutDisplayErrors ()
    {
        $aConfig = array(
            'display_errors'        => false,
            'with_error_log_path'   => true,
            'error_reporting_level' => -1,
            'auth_error_suppr_op'   => false,
            'default_error_code'    => 17,
            'shutdown_msg'          => 'down'
        );
        list($sStdOut, $iErrorCode, $sStdErr, $sErrorLogContent) = $this->exec('log.php', $aConfig);
        $this->assertEquals('Hello' . $aConfig['shutdown_msg'], $sStdOut);
        $this->assertEquals(0, $iErrorCode);
        $this->assertEmpty($sStdErr);
        $this->assertContains("] \n", $sErrorLogContent);
        $this->assertContains("] a word\n", $sErrorLogContent);
        $this->assertContains(print_r(array('key' => 'value'), true) . "\n", $sErrorLogContent);
    }

    public function testLogNotCLI ()
    {
        $aConfig = array(
            'display_errors'        => true,
            'with_error_log_path'   => true,
            'error_reporting_level' => -1,
            'auth_error_suppr_op'   => false,
            'default_error_code'    => 17,
            'shutdown_msg'          => 'down'
        );
        list($sStdOut, $iErrorCode, $sStdErr, $sErrorLogContent) = $this->exec('log_not_cli.php', $aConfig);
        $sMsg = '<div class="error"></div>'
              . '<div class="error">a word</div>'
              . '<div class="error">' . print_r(array('key' => 'value'), true) . '</div>'
              . 'Hello' . $aConfig['shutdown_msg'];
        $this->assertEquals($sMsg, $sStdOut);
        $this->assertEquals(0, $iErrorCode);
        $this->assertEmpty($sStdErr);
        $this->assertContains("] \n", $sErrorLogContent);
        $this->assertContains("] a word\n", $sErrorLogContent);
        $this->assertContains(print_r(array('key' => 'value'), true) . "\n", $sErrorLogContent);
    }

    public function testInternalExceptionHandlerWithErrorCode ()
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
            $this->exec('exception_with_error_code.php', $aConfig);
        $this->assertEquals($aConfig['shutdown_msg'], $sStdOut);
        $this->assertEquals(3, $iErrorCode);
        $sErrorMsg = "exception 'RuntimeException' with message 'Bad !'";
        $this->assertContains($sErrorMsg, $sStdErr);
        $this->assertContains($sErrorMsg, $sErrorLogContent);
    }

    public function testInternalExceptionHandlerWithoutErrorCode ()
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
            $this->exec('exception_without_error_code.php', $aConfig);
        $this->assertEquals($aConfig['shutdown_msg'], $sStdOut);
        $this->assertEquals($aConfig['default_error_code'], $iErrorCode);
        $sErrorMsg = "exception 'RuntimeException' with message 'Bad !'";
        $this->assertContains($sErrorMsg, $sStdErr);
        $this->assertContains($sErrorMsg, $sErrorLogContent);
    }

    public function testInternalExceptionHandlerNotCLI ()
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
        $this->exec('exception_not_cli.php', $aConfig);
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
        list($sStdOut, $iErrorCode, $sStdErr, $sErrorLogContent) = $this->exec('exclude_path.php', $aConfig);
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
            $this->exec('callback_generic_display.php', $aConfig);
        $this->assertEquals('>>>Call to undefined function f()<<<' . $aConfig['shutdown_msg'], $sStdOut);
        $this->assertEquals(255, $iErrorCode);
        $sErrorMsg = 'Fatal error:  Call to undefined function f()';
        $this->assertEmpty($sStdErr);
        $this->assertContains($sErrorMsg, $sErrorLogContent);
    }

    public function testAtSignNotAuthorized ()
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
            $this->exec('at_sign.php', $aConfig);
        $this->assertEquals($aConfig['shutdown_msg'], $sStdOut);
        $this->assertEquals($aConfig['default_error_code'], $iErrorCode);
        $sErrorMsg = "exception 'ErrorException' with message '[from error handler] WARNING"
                   . ' -- include(not_exists): failed to open stream: No such file or directory';
        $this->assertContains($sErrorMsg, $sStdErr);
        $this->assertContains($sErrorMsg, $sErrorLogContent);
    }

    public function testAtSignAuthorized ()
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
            $this->exec('at_sign.php', $aConfig);
        $this->assertEquals('Hello' . $aConfig['shutdown_msg'], $sStdOut);
        $this->assertEquals(0, $iErrorCode);
        $this->assertEmpty($sStdErr);
        $this->assertEmpty($sErrorLogContent);
    }
}
