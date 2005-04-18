<?PHP
/*
 * This file is part of the JTLib library.
 *
 * JTLib is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of the
 * License, or (at your option) any later version.
 *
 * JTLib is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with JTLib; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA
 * 02111-1307  USA
 */

/**
 * General purpose error handler class.
 *
 * This class handles all error codes and provides for logging to a
 * file, out to the HTML stream, and also (if the APD debugging module
 * is installed in PHP's ini file) provides for traceback lists when a
 * fatal error occurs.
 *
 * @package jtlib
 * @copyright Copyright(c) 2004, June Rebecca Tate
 * @author June R. Tate <june@theonelab.com>
 * @version $Revision$
 */

define("ERR_ERROR",   E_USER_ERROR);
define("ERR_WARNING", E_USER_WARNING);
define("ERR_NOTICE",  E_USER_NOTICE);

define("ERR_INLINE", 0);
define("ERR_FILE",   1);

/**
 * General purpose error handling class.
 *
 * This class provides a clean, safe way of implementing an error
 * handler for the JTLib framework.
 *
 * @package jtlib
 */
class ErrorHandler
{
    var $_mode;
    var $_level;
    var $_logfile;
    var $_old_level;
    var $_old_handler;

    /**
     * Constructor.
     *
     * This method will initialize and register the error handler with
     * the PHP system.
     *
     * $mode is one of the following: ERR_INLINE or
     * ERR_FILE. ERR_INLINE means to go ahead and log directly out to
     * the HTML stream. ERR_FILE means to log to a file instead. If
     * $mode is set to ERR_FILE, $logfile MUST be set to a valid path
     * of a file to log messages to.
     *
     * $level is the error reporting level. This parameter behaves
     * exactly as the paramter to the built in PHP function,
     * error_reporting().
     *
     * @access public
     * @param integer One of ERR_INLINE or ERR_FILE. Defaults to ERR_INLINE.
     * @param string The full pathname of the file to log to. Required if mode is not ERR_INLINE.
     * @param integer The error reporting level to log. See also PHP's builtin error_reporting() function.
     * @returns void
     */
    function ErrorHandler($mode = ERR_INLINE, $logfile = false, $level = E_ALL)
    {
        if ($mode == ERR_FILE) {
            if ($logfile !== false) {
                if (!$this->_openLog($logfile)) {
                    trigger_error("Unable to open $logfile for appending", E_USER_ERROR);
                    exit(1);
                }
            } else {
                trigger_error("No filename specified to log to", E_USER_ERROR);
                exit(1);
            }
        }

        if (function_exists("apd_callstack")) {
            $this->_apd_enabled = true;
        } else {
            $this->_apd_enabled = false;
        }

        switch ($mode) {
            case ERR_INLINE:
                $handler = "_inlineHandler";
                break;

            case ERR_FILE:
                $handler = "_fileHandler";
                break;

            default:
                trigger_error("Don't know what mode to use", E_USER_ERROR);
                exit(1);
                break;
        }
        
        $this->_mode = $mode;
        $this->_level = $level;
        $this->_logfile = $logfile;
        $this->_old_level = error_reporting($level);
        $this->_old_handler = set_error_handler(array($this, $handler));
    }

    /**
     * Shutdown the error handler.
     *
     * This function is not normally needed to be called, but when
     * called this method closes all open files and reregisters the
     * previously installed error handler.
     *
     * @access public
     * @returns void
     */
    function shutdown()
    {
        restore_error_handler();

        if ($this->_log_open) {
            $this->_closeLog();
        }
    }

    /**
     * Opens the log file for append writing.
     *
     * This method opens the log file passed to it, and returns either
     * a true or false if the open succeeded or failed, respectively.
     *
     * @access private
     * @param string The filename to append to
     * @returns boolean
     */
    function _openLog($filename)
    {
        $this->_fp = @fopen($filename, "a+");
        
        if (!$this->_fp) {
            $this->_log_open = false;
            return false;
        } else {
            $this->_log_open = true;
            return true;
        }
    }

    /**
     * Close the log file that was previously opened by _openLog.
     *
     * This method closes all open log files previously opened when
     * the class was instanciated. Normally this method is called from
     * the shutdown() method, and should not be called for any other
     * reason.
     *
     * @access private
     * @returns void
     */
    function _closeLog()
    {
        fclose($this->_fp);
        $this->_fp = false;
        $this->_log_open = false;
    }

    /**
     * Handle an error and log it to a file.
     *
     * This method is the handler used to format an error message and
     * store it to a previously opened ASCII log file. The method's
     * prototype is the same as what is required for the
     * set_error_handler's callback function, as defined in the PHP
     * manual.
     *
     * Currently this method always returns true.
     *
     * @access private
     * @returns true
     */
    function _fileHandler($errno, $errstr, $errfile, $errline, $context)
    {
        if (!($errno && $this->_level)) return;
        
        switch ($errno) {
            case E_ERROR:
            case E_USER_ERROR:
                $msg  = "ERROR: $errfile($errline): $errstr\n";

                if ($this->_apd_enabled) {
                    $msg .= "    Backtrace:\n";
                    
                    foreach (apd_callstack() AS $key => $call) {
                        $msg .= "        ";
                        $msg .= basename($call[1]) .":". $call[2] ." - ";
                        $msg .= $call[0] ."(";

                        foreach ($call[3] AS $key => $val) {
                            $msg .= $val;
                            if ($key != count($call[3])) {
                                $msg .= ", ";
                            }
                        }
                        
                        $msg .= ")\n";
                    }
                }

                fputs($this->_fp, $msg);
                exit(1);
                break;
                    
            case E_WARNING:
            case E_USER_WARNING:
                $msg = "WARNING: $errfile($errline): $errstr\n";
                fputs($this->_fp, $msg);
                break;
                
            case E_NOTICE:
            case E_USER_NOTICE:
                $msg = "NOTICE: $errfile($errline): $errstr\n";
                fputs($this->_fp, $msg);
                break;
        }

        return true;
    }

    /**
     * Handle an error and log it to the current HTML stream.
     *
     * This method handles the error passed to it via the
     * set_error_handler PHP builtin function. The prototype is the
     * same as what is required for set_error_handler()'s callback
     * function.
     *
     * This method currently always returns true.
     *
     * @access private
     * @returns true
     */
    function _inlineHandler($errno, $errstr, $errfile, $errline, $context)
    {
        if (!($errno && $this->_level)) return;
        
        echo '<div style="margin: 5px auto 5px auto; width: 90%; background: #FFF; border: 1px solid #000; padding: 5px;">';
        
        switch ($errno) {
            case E_ERROR:
            case E_USER_ERROR:
                echo '<span style="color: #F00; font-weight: bold; font-family: sans-serif;">';
                echo "<tt>ERROR: $errfile($errline): $errstr</tt><br>";
                
                if ($this->_apd_enabled) {
                    echo '<table border="0" width="100%">';
                    echo '<tr><td><b>Backtrace:</b></td></tr>';
                    
                    foreach (apd_callstack() AS $key => $call) {
                        echo '<tr>';
                        echo '<td style="padding-left: 20px">'. basename($call[1]) .':'. $call[2] .'</td>';
                        
                        echo '<td>'. $call[0] .'(';
                        foreach ($call[3] AS $key => $val) {
                            echo $val;
                            if ($key != count($call[3])) {
                                echo ", ";
                            }
                        }
                        echo ')</td>';
                        echo '</pre></td></tr>';
                    }

                    echo '</table>';
                }

                echo '</span>';
                echo '</div>';
                exit(1);
                break;
                    
            case E_WARNING:
            case E_USER_WARNING:
                echo '<span style="color: #F0F;">';
                echo "<tt>WARNING: $errfile($errline): $errstr</tt>";
                echo '</span>';
                break;
                
            case E_NOTICE:
            case E_USER_NOTICE:
                echo '<span style="color: #00F;">';
                echo "<tt>NOTICE: $errfile($errline): $errstr</tt>";
                echo '</span>';
                break;
        }

        echo "</div>\n";
        
        return true;
    }
}
