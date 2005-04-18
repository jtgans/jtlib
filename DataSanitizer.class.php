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
 * Defines the DataSanitizer class
 *
 * @package jtlib
 * @copyright Copyright(c) 2005, June Rebecca Tate
 * @author June R. Tate <june@theonelab.com>
 * @version $Revision$
 */

/**
 * Input sanitizing class.
 *
 * This class's whole goal in life is to clean up and sanitize all
 * input data coming into code via the GET, POST, COOKIE, and REQUEST
 * superglobals. This class does this only to sepecific variables that
 * you tell it to (via it's required and fields constraints). The
 * format for the constraints array is the following:
 *
 * <code>
 * $fields = array("fieldname1" => "post,integer",
 *                 "fieldname2" => "get,integer",
 *                 // ... ad infinitum ...
 *                );
 * </code>
 * 
 * The generalized format is a one dimensional hash table of field
 * names as the key, and the constraints for that field as the
 * value. Constraints are a comma-separated list of keywords from the
 * following list:
 *
 * <code>
 * Source Keywords:
 *   post    - Pull the value from the $_POST array
 *   get     - Pull the value from the $_GET array
 *   cookie  - Pull the value from the $_COOKIE array
 *   request - Pull the value from the $_REQUEST array
 *
 * Constraint Keywords:
 *   integer - Force the value of the field to an integer
 *   float   - Force the value of the field to a float
 *   string  - Convert the value to a string
 *   boolean - Force the value to a boolean type (set = true, notset = false)
 *
 */
class DataSanitizer
{
    var $_fields;
    var $_required;
    var $_values;
    var $_errors;
    var $_form_is_valid;
    
    /**
     * Constructor
     *
     * Sets up the sanitizer with default values and prepares the
     * sanitizer for cleaning of incoming data.
     *
     * @return void
     */
    function DataSanitizer($fields = false, $required = false)
    {
        $this->_values = array();
        $this->_errors = array();
        $this->_fields = array();
        $this->_required = array();
        $this->_form_is_valid = false;
        
        if (($fields   !== false) && (is_array($fields)))   $this->_fields = $fields;
        if (($required !== false) && (is_array($required))) $this->_required = $required;
    }

    /**
     * Sets all of the fields and constraints to the array passed to
     * it. Returns true if the fields were set successfully. Otherwise
     * returns false.
     *
     * @access public
     * @param array The fields and constraints to sanitize by.
     * @returns boolean
     */
    function setFields($fields)
    {
        if (is_array($fields)) {
            $this->_fields = $fields;
            return true;
        } else {
            return false;
        }
    }

    /**
     * Adds a field to the constraint list. Returns true if the field
     * doesn't exist and was added successfully. Otherwise, returns false
     * if the field already existed.
     *
     * @access public
     * @param string The name of the field from the form
     * @param string The constraints to check against
     * @returns boolean
     */
    function addField($field, $spec)
    {
        if (isset($this->_fields[$field])) {
            return false;
        } else {
            $this->_fields[$field] = $spec;
            return true;
        }
    }

    /**
     * Removes a field from the constraint list. Returns true if the
     * field name was found and removed. Otherwise this method returns
     * false.
     *
     * @access public
     * @param string The name of the field to remove from the constraints.
     * @returns boolean
     */
    function removeField($field)
    {
        if (isset($this->_fields[$field])) {
            unset($this->_fields[$field]);
            return true;
        } else {
            return false;
        }
    }

    /**
     * Returns the constraints and field names in an array.
     *
     * @access public
     * @returns array
     */
    function getFields()
    {
        return $this->_fields;
    }

    /**
     * Set the fields that are required in the constraint
     * list. Returns true on success, otherwise false on failure.
     *
     * @access public
     * @param array A single-dimension array of fields to require.
     * @returns boolean
     */
    function setRequired($required)
    {
        if (is_array($required)) {
            $this->_required = $required;
            return true;
        } else {
            return false;
        }
    }

    /**
     * Add a required field name to the list of required fields.
     *
     * @access public
     * @returns void
     */
    function addRequired($name)
    {
        $this->_required[] = $name;
    }

    /**
     * Remove a required field from the list of required
     * fields. Returns true if the field existed in the list and was
     * removed, or false if it couldn't be found.
     *
     * @access public
     * @param string The field name to remove from the list.
     * @returns boolean
     */
    function removeRequired($name)
    {
        if (in_array($this->_required, $name)) {
            $index = array_search($this->_required, $name);
            unset($this->_required[$index]);
            return true;
        } else {
            return false;
        }
    }

    /**
     * Get a list of the required fields in an array.
     *
     * @access public
     * @returns array
     */
    function getRequired()
    {
        return $this->_required;
    }

    /**
     * Sanitize the data in the POST, GET, REQUEST, and COOKIE fields
     * according to the sanitization constraints. Returns true if the
     * form was filled out properly according to the
     * constraints. Otherwise, this method returns false.
     *
     * @access public
     * @returns boolean
     */
    function sanitizeData()
    {
        foreach ($this->_fields AS $field => $constraints) {
            // Clean up the constraints list, first off
            $constraints = strtolower($constraints);
            $constraints = str_replace(" ", "", $constraints);

            // Now run through each constraint, doing what it says.
            foreach (explode(",", $constraints) AS $action) {
                switch ($action) {
                    case "get":
                        if (isset($_GET[$field]) && (strlen($_GET[$field]) > 0) && ($_GET[$field] != "")) {
                            $this->_values[$field] = $_GET[$field];
                        } else {
                            unset($this->_values[$field]);
                        }
                        break;

                    case "post":
                        if (isset($_POST[$field]) && (strlen($_POST[$field]) > 0) && ($_POST[$field] != "")) {
                            $this->_values[$field] = $_POST[$field];
                        } else {
                            unset($this->_values[$field]);
                        }
                        break;

                    case "cookie":
                        if (isset($_COOKIE[$field]) && (strlen($_COOKIE[$field]) > 0) && ($_COOKIE[$field] != "")) {
                            $this->_values[$field] = $_COOKIE[$field];
                        } else {
                            unset($this->_values[$field]);
                        }
                        break;

                    case "request":
                        if (isset($_REQUEST[$field]) && (strlen($_REQUEST[$field]) > 0) && ($_REQUEST[$field] != "")) {
                            $this->_values[$field] = $_REQUEST[$field];
                        } else {
                            unset($this->_values[$field]);
                        }
                        break;

                    case "integer":
                        if (isset($this->_values[$field])) $this->_values[$field] = (integer)($this->_values[$field]);
                        break;
                        
                    case "float":
                        if (isset($this->_values[$field])) $this->_values[$field] = (float)($this->_values[$field]);
                        break;
                        
                    case "string":
                        if (isset($this->_values[$field])) $this->_values[$field] = (string)($this->_values[$field]);
                        break;

                    case "boolean":
                        if (isset($this->_values[$field])) {
                            $this->_values[$field] = true;
                        } else {
                            $this->_values[$field] = false;
                        }
                        break;
                        
                    case "lowercase":
                        if (isset($this->_values[$field]) && is_string($this->_values[$field])) {
                            $this->_values[$field] = strtolower($this->_values[$field]);
                        }
                        break;
                        
                    default:
                        trigger_error("DataSanitizer::sanitizeData: Unknown action '$action'.", ERR_NOTICE);
                        break;
                }
            }
        }
        
        return $this->checkRequired();
    }

    function checkRequired()
    {
        // Assume the form is valid until we find a field that's not.
        $this->_form_is_valid = true;
        
        // Now run through our required list and check to make sure
        // the form is valid.
        foreach ($this->_required AS $field) {
            if (!isset($this->_values[$field])) {
                $this->_form_is_valid = false;
                $this->_errors[$field] = array("code"   => 1,
                                               "string" => "Missing data."
                                               );
            } else {
                unset($this->_errors[$field]);
            }
        }

        return $this->_form_is_valid;
    }
    
    /**
     * Returns a hash table of all of the fields and their
     * corresponding values.
     *
     * @access public
     * @returns array
     */
    function getAllData()
    {
        return $this->_values;
    }

    /**
     * Retrieve a specific field's data after it was
     * sanitized. Returns the field's values if the field was
     * available. Otherwise, this method returns NULL.
     *
     * @access public
     * @returns mixed
     */
    function getDataByField($field)
    {
        if (isset($this->_values[$field])) {
            return $this->_values[$field];
        } else {
            return NULL;
        }
    }

    /**
     * Return an associative array of each field's error code and
     * messages.
     *
     * @access public
     * @returns array
     */
    function getAllErrors()
    {
        return $this->_errors;
    }

    /**
     * Return the error code and string in an associative array of a
     * specific field if it exists. The key "code" contains the error
     * code number, and the "string" contains the error string. This
     * method returns false if there is no error for the specified
     * field.
     *
     * @access public
     * @param string The field name
     * @returns mixed
     */
    function getErrorByField($field)
    {
        if (isset($this->_errors[$field])) {
            return $this->_errors[$field];
        } else {
            return false;
        }
    }

    /**
     * Checks to see if the form was valid, according to the
     * constraints given to the object. Returns true if it was valid,
     * otherwise false.
     *
     * @access public
     * @returns boolean
     */
    function isFormValid()
    {
        return $this->_form_is_valid;
    }
}