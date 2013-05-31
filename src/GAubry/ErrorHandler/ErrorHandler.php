<?php

namespace GAubry\ErrorHandler;

use GAubry\Helpers\Helpers;

/**
 * Simple error and exception handler.
 *   – wraps the error to an ErrorException instance according to error reporting level
 *   – when running the PHP CLI, reports errors/exceptions to STDERR (even fatal error)
 *     and uses exception code as exit status
 *   – allows to deactivate '@' operator
 *   – catches fatal error
 *   – accepts callback to be executed at the end of the internal shutdown function
 *   – accepts callback to display an apology when errors are hidden
 *   – allows to ignore errors on some paths, useful with old libraries and deprecated code…
 *
 * Copyright (c) 2012 Geoffroy Aubry <geoffroy.aubry@free.fr>
 * Licensed under the GNU Lesser General Public License v3 (LGPL version 3).
 *
 * @copyright 2012 Geoffroy Aubry <geoffroy.aubry@free.fr>
 * @license http://www.gnu.org/licenses/lgpl.html
 */
class ErrorHandler
{

    /**
     * Error codes.
     * @var array
     * @see internalErrorHandler()
     */
    public static $aErrorTypes = array(
        E_ERROR             => 'ERROR',
        E_WARNING           => 'WARNING',
        E_PARSE             => 'PARSING ERROR',
        E_NOTICE            => 'NOTICE',
        E_CORE_ERROR        => 'CORE ERROR',
        E_CORE_WARNING      => 'CORE WARNING',
        E_COMPILE_ERROR     => 'COMPILE ERROR',
        E_COMPILE_WARNING   => 'COMPILE WARNING',
        E_USER_ERROR        => 'USER ERROR',
        E_USER_WARNING      => 'USER WARNING',
        E_USER_NOTICE       => 'USER NOTICE',
        E_STRICT            => 'STRICT NOTICE',
        E_RECOVERABLE_ERROR => 'RECOVERABLE ERROR'
    );

    /**
     * CLI ?
     * @var bool
     */
    private $_bIsRunningFromCLI;

    /**
     * Errors will be ignored on these paths.
     * Useful with old libraries and deprecated code.
     *
     * @var array
     * @see addExcludedPath()
     */
    private $_aExcludedPaths;

    /**
     * Callback to display an apology when errors are hidden.
     * @var callback
     */
    private $_callbackGenericDisplay;

    /**
     * Callback to be executed at the end of the internal shutdown function
     * @var callback
     */
    private $_callbackAdditionalShutdownFct;

    /**
     * Default config.
     *   – 'display_errors'        => (bool) Determines whether errors should be printed to the screen
     *                                as part of the output or if they should be hidden from the user.
     *   – 'error_log_path'        => (string) Name of the file where script errors should be logged.
     *   – 'error_reporting_level' => (int) Error reporting level.
     *   – 'auth_error_suppr_op'   => (bool) Allows to deactivate '@' operator.
     *   – 'default_error_code'    => (int) Default error code for errors converted into exceptions
     *                                or for exceptions without code.
     * @var array
     */
    private static $_aDefaultConfig = array(
        'display_errors'        => true,
        'error_log_path'        => '',
        'error_reporting_level' => -1,
        'auth_error_suppr_op'   => false,
        'default_error_code'    => 1
    );

    /**
     * Configuration.
     * @var array
     * @see self::$_aDefaultConfig
     */
    private $_aConfig;

    /**
     * Constructor.
     *
     * @param array $aConfig see self::$_aDefaultConfig
     */
    public function __construct (array $aConfig=array())
    {
        $this->_aConfig = Helpers::arrayMergeRecursiveDistinct(self::$_aDefaultConfig, $aConfig);
        $this->_aExcludedPaths = array();
        $this->_bIsRunningFromCLI = defined('STDIN');	// or (PHP_SAPI === 'cli')
        $this->_callbackGenericDisplay = array($this, 'displayDefaultApologies');
        $this->_callbackAdditionalShutdownFct = '';

        error_reporting($this->_aConfig['error_reporting_level']);
        if ($this->_aConfig['display_errors'] && $this->_bIsRunningFromCLI) {
            ini_set('display_errors', 'stderr');
        } else {
            ini_set('display_errors', $this->_aConfig['display_errors']);
        }
        ini_set('log_errors', true);
        ini_set('html_errors', false);
        ini_set('display_startup_errors', true);
        if ( ! empty($this->_aConfig['error_log_path'])) {
            ini_set('error_log', $this->_aConfig['error_log_path']);
        }
        ini_set('ignore_repeated_errors', true);

        // Make sure we have a timezone for date functions. It is not safe to rely on the system's timezone settings.
        // Please use the date.timezone setting, the TZ environment variable
        // or the date_default_timezone_set() function.
        if (ini_get('date.timezone') == '') {
            date_default_timezone_set('Europe/Paris');
        }

        set_error_handler(array($this, 'internalErrorHandler'));
        set_exception_handler(array($this, 'internalExceptionHandler'));
        register_shutdown_function(array($this, 'internalShutdownFunction'));
    }

    /**
     * Allows to ignore errors on some paths, useful with old libraries and deprecated code…
     * Trailing slash is optional.
     *
     * @param string $sPath
     * @see internalErrorHandler()
     */
    public function addExcludedPath ($sPath)
    {
        if (substr($sPath, -1) !== '/') {
            $sPath .= '/';
        }
        $sPath = realpath($sPath);
        if ( ! in_array($sPath, $this->_aExcludedPaths)) {
            $this->_aExcludedPaths[] = $sPath;
        }
    }

    /**
     * Set callback to display an apology when errors are hidden.
     * Current \Exception will be provided in parameter.
     *
     * @param callback $callbackGenericDisplay
     */
    public function setCallbackGenericDisplay ($callbackGenericDisplay)
    {
        $this->_callbackGenericDisplay = $callbackGenericDisplay;
    }

    /**
     * Set callback to be executed at the end of the internal shutdown function.
     *
     * @param callback $callbackAdditionalShutdownFct
     */
    public function setCallbackAdditionalShutdownFct ($callbackAdditionalShutdownFct)
    {
        $this->_callbackAdditionalShutdownFct = $callbackAdditionalShutdownFct;
    }

    /**
     * Customized error handler function: throws an Exception with the message error if @ operator not used
     * and error source is not in excluded paths.
     *
     * @param int $iErrNo level of the error raised.
     * @param string $sErrStr the error message.
     * @param string $sErrFile the filename that the error was raised in.
     * @param int $iErrLine the line number the error was raised at.
     * @return boolean true, then the normal error handler does not continues.
     * @see addExcludedPath()
     */
    public function internalErrorHandler ($iErrNo, $sErrStr, $sErrFile, $iErrLine)
    {
        // Si l'erreur provient d'un répertoire exclu de ce handler, alors l'ignorer.
        foreach ($this->_aExcludedPaths as $sExcludedPath) {
            if (stripos($sErrFile, $sExcludedPath) === 0) {
                return true;
            }
        }

        // Gestion de l'éventuel @ (error suppression operator) :
        if (
            $this->_aConfig['error_reporting_level'] !== 0 && error_reporting() === 0
            && $this->_aConfig['auth_error_suppr_op']
        ) {
            $iErrorReporting = 0;
        } else {
            $iErrorReporting = $this->_aConfig['error_reporting_level'];
        }

        // Le seuil de transformation en exception est-il atteint ?
        if (($iErrorReporting & $iErrNo) !== 0) {
            $msg = "[from error handler] " . self::$aErrorTypes[$iErrNo]
                 . " -- $sErrStr, in file: '$sErrFile', line $iErrLine";
            throw new \ErrorException($msg, $this->_aConfig['default_error_code'], $iErrNo, $sErrFile, $iErrLine);
        }
        return true;
    }

    /**
     * Exception handler.
     *
     * @param \Exception $oException
     */
    public function internalExceptionHandler (\Exception $oException)
    {
        if ( ! $this->_aConfig['display_errors'] && ini_get('error_log') !== '' && ! $this->_bIsRunningFromCLI) {
            call_user_func($this->_callbackGenericDisplay, $oException);
        }
        $this->log($oException);
        if ($oException->getCode() != 0) {
            $iErrorCode = $oException->getCode();
        } else {
            $iErrorCode = $this->_aConfig['default_error_code'];
        }
        exit($iErrorCode);
    }

    /**
     * Default callback to display an apology when errors are hidden.
     */
    public function displayDefaultApologies ()
    {
        echo '<div class="exception-handler-message">We are sorry, an internal error occurred.<br />'
             . 'We apologize for any inconvenience this may cause</div>';
    }

    /**
     * Registered shutdown function.
     */
    public function internalShutdownFunction ()
    {
        $aError = error_get_last();
        if ( ! $this->_aConfig['display_errors'] && is_array($aError) && $aError['type'] === E_ERROR) {
            $oException = new \ErrorException(
                $aError['message'], $this->_aConfig['default_error_code'], $aError['type'], $aError['file'], $aError['line']
            );
            call_user_func($this->_callbackGenericDisplay, $oException);
        }
        if ( ! empty($this->_callbackAdditionalShutdownFct)) {
            call_user_func($this->_callbackAdditionalShutdownFct);
            // @codeCoverageIgnoreStart
        }
    }
    // @codeCoverageIgnoreEnd

    /**
     * According to context, logs specified error into STDERR, STDOUT or via error_log().
     *
     * @param mixed $mError Error to log. Can be string, array or object.
     */
    public function log ($mError)
    {
        if (is_array($mError) || (is_object($mError) && ! ($mError instanceof \Exception))) {
            $mError = print_r($mError, true);
        }

        if ($this->_aConfig['display_errors']) {
            if ($this->_bIsRunningFromCLI) {
                file_put_contents('php://stderr', $mError . "\n", E_USER_ERROR);
            } else {
                echo $mError;
            }
        }

        if ( ! empty($this->_aConfig['error_log_path'])) {
            error_log($mError);
        }
    }
}
