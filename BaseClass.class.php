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
 * An interface class with which to derive all other data-handling
 * classes.
 *
 * This class is (in Java and PHP5 terms) considered an interface or
 * abstract class. You don't ever instanciate it itself, but subclass
 * it to get the basic functionality of the object.
 *
 * This class provides for variable sanitation, verification, and
 * access to a local instance of the Dolphin database
 * object. Currently it's a basic shell of a class, and I expect to
 * add more functionality to it in future revisions.
 *
 * @package jtlib
 * @copyright Copyright(c) 2004, June Rebecca Tate
 * @author June R. Tate <june@theonelab.com>
 * @version $Revision$
 */

require_once("Dolphin.class.php");
require_once("ErrorHandler.class.php");

class BaseClass
{
    var $form;
    var $data;
    var $mode;
    var $errors;
 
    var $_db;
    var $_fields;

    function BaseClass($sanitize = true, $validate = true, $require_db = true)
    {
        $this->form = array();
        $this->data = array();
        $this->errors = array();
        $this->mode = "";
        
        if (!isset($this->_fields)) {
            $this->_fields = array();
        }

        $this->_getMode();
        $this->_findDolphin($require_db);
        if ($sanitize) $this->_sanitizeInput();
        if ($validate) $this->_validateValues();
    }

    function formHasErrors()
    {
        if (count($this->errors) > 0) {
            return true;
        } else {
            return false;
        }
    }

    function _grabFormField($array, $field)
    {
        if (!is_array($array)) return false;
        
        if (isset($array[$field])
            && (strlen((string)($array[$field])) > 0)
            && (!isset($this->form[$field]))) {
            
            $this->form[$field] = $array[$field];

            return true;
        } else {
            return false;
        }
    }
    
    function _sanitizeInput()
    {
        // $fields = array("text"      => "get,post,string,required",
        //                 "integer"   => "get,post,integer,required",
        //                 "float"     => "get,post,float,required",
        //                 "file"      => "file,required",
        //                 "password"  => "post,string,required",
        //                 "password2" => "post,string,required");

        foreach ($this->_fields AS $field => $actionlist) {
            foreach (explode(",", $actionlist) AS $action) {
                switch (strtolower(trim($action))) {
                    case "get":
                        $this->_grabFormField($_GET, $field);
                        break;

                    case "post":
                        $this->_grabFormField($_POST, $field);
                        break;

                    case "cookie":
                        $this->_grabFormField($_COOKIE, $field);
                        break;

                    case "session":
                        $this->_grabFormField($_SESSION, $field);
                        break;

                    case "file":
                        $this->_grabFormField($_FILES, $field);

                        if (isset($this->form[$field])) {
                            if (is_array($this->form[$field])) {
                                if (!is_uploaded_file($this->form[$field]['userfile'])) {
                                    trigger_error("BaseClass::_sanitizeInput: Attempted file upload attack! Filename was [". $this->form[$field]['userfile'] ."].", ERR_ERROR);
                                    unset($this->form[$field]);
                                    $this->errors[$field][] = "Possible file upload attack.";
                                }
                            } else {
                                unset($this->form[$field]);
                                $this->errors[$field][] = "Not a file upload array.";
                            }
                        }
                        break;

                    case "string":
                        if (isset($this->form[$field])) {
                            if (is_string($this->form[$field])) {
                                if (strlen($this->form[$field]) > 0) {
                                    $this->form[$field] = (string)($this->form[$field]);
                                } else {
                                    unset($this->form[$field]);
                                    $this->errors[$field][] = "Empty string.";
                                }
                            } else {
                                unset($this->form[$field]);
                                $this->errors[$field][] = "Not a string.";
                            }
                        }
                        break;

                    case "integer":
                        if (isset($this->form[$field])) {
                            if (is_numeric($this->form[$field])) {
                                $this->form[$field] = (integer)($this->form[$field]);
                            } else {
                                unset($this->form[$field]);
                                $this->errors[$field][] = "Not an integer.";
                            }
                        }
                        break;

                    case "float":
                        if (isset($this->form[$field])) {
                            if (is_numeric($this->form[$field])) {
                                $this->form[$field] = (float)($this->form[$field]);
                            } else {
                                unset($this->form[$field]);
                                $this->errors[$field][] = "Not a float.";
                            }
                        }
                        break;
                        
                    default:
                        trigger_error("BaseClass::_sanitizeInput: Unknown action [$action] for field [$field].", ERR_WARN);
                        break;
                }
            }
        }
    }

    function _validateValues()
    {
        foreach ($this->_fields AS $field => $actionlist) {
            if (strpos($actionlist, "required")) {
                if (!isset($this->form[$field])) {
                    $this->errors[$field][] = "Required value not found.";
                }
            }
        }
    }

    function _getMode()
    {
        if (isset($_POST['f'])) {
            $this->mode = $_POST['f'];
        } else if (isset($_GET['f'])) {
            $this->mode = $_GET['f'];
        }
    }
    
    function _findDolphin($require_db)
    {
        global $_DB;

        if (is_object($_DB)) {
            $this->_db = &$_DB;
        } else {
            if ($require_db) {
                trigger_error("BaseClass::_findDolphin: Can't find an instance of Dolphin.", ERR_FATAL);
            } else {
                trigger_error("BaseClass::_findDolphin: Can't find an instance of Dolphin.", ERR_WARN);
            }
        }
    }
}
