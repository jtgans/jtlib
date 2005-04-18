<?PHP
/**
 * Defines the DataObject abstract class
 * 
 * @package JTLib
 * @author June Tate
 */

require_once("Dolphin.class.php");

/**
 * Abstract base class for all classes
 * 
 * This is the base class for all classes that are used to handle
 * data retrieval, munging, expunging, etc. A messy term for it all,
 * but the system is really quite simple.
 * 
 * DataObject is really what we would call in Java an abstract Object.
 * It only defines basic functionality for all DataObject derived classes
 * and cannot do anything by itself if instanciated.
 */
class DataObject
{
    var $db;

    var $mode;
    var $fields;
    var $required;
    var $values;
    var $errors;
    var $table;
    var $dest;

    var $debug;

    /**
     * Initializer
     * 
     * Sets up the base variables' contents and sanitizes all GET, POST, COOKIE, and SESSION
     * variables by calling the initialize() and sanitizeInput() methods.
     * 
     * @return void
     */
    function DataObject($init = true, $sanitize = true)
    {
        if ($init) $this->initialize();
        if ($sanitize) $this->sanitizeInput();
    }

    /**
     * Initializes the base variables and makes sure we're in a sane state
     * 
     * Doublechecks to make sure there's a global instance of Dolphin before continuing.
     * Additionally grabs the mode and destination variables from $_POST and $_GET for
     * further processing used by subclasses.
     * 
     * @return void
     */
    function initialize()
    {
        global $_DB, $_ARGV, $_ARGC, $_THEME;
    
        if ($_DB) {
            $this->db = &$_DB;
        } else {
            Utils::errorMsg("DataObject", __LINE__, "Unable to find an instance of Dolphin!");
            exit;
        }

        if ($_POST['f']) {
            $this->mode = $_POST['f'];
        } else {
            $this->mode = $_GET['f'];
        }
    
        if ($_POST['dest']) {
            $this->dest = $_POST['dest'];
        }
    
        if (DEBUG == true) {
            $_THEME->addToDebug("DataObject", "<b>f:</b> $this->mode<br>");
            $_THEME->addToDebug("DataObject", "<b>dest:</b> $this->dest<br>");
            $_THEME->addToDebug("DataObject", "<b>DataObject::initialize() called<br>");
        }
    }

    /**
     * Provides an entry point for taking action depending on the mode set.
     * 
     * This is a generic handleMode() method and really should be overridden by subclasses as
     * it was written as a one-size-fits-all kind of solution. It's quick and easy to use in 
     * a pinch, but not very practical for specific purposes.
     * 
     * @return void
     */
    function handleMode()
    {
        switch ($this->mode) {
            case "mod":
                if (!$this->validateValues()) {
                    $this->updateDB();
                    if ($this->dest) header("Location: $this->dest");
                    return;
                }
                break;
        
            case "del":
                $this->deleteFromDB();
                if ($this->dest) header("Location: $this->dest");
                break;

            case "load":
                $this->loadFromDB();
                if ($this->dest) header("Location: $this->dest");
                break;
        
            case "post":
                if (!$this->validateValues()) {
                    $this->insertIntoDB();
                    if ($this->dest) header("Location: $this->dest");
                }
                break;
        
            default:
                if ($this->dest) header("Location: $this->dest");
                break;
        }
    }

    /**
     * Grabs variables from the global input arrays $_GET, $_POST, $_COOKIE, and $_SESSION
     * according to the $fields class variable.
     * 
     * This particular function is extremely handy for picking out which specific values
     * you're looking for and making sure they're sane, non-hackable data.
     * 
     * The format of the $fields array is as follows:
     * 
     * $this->fields = array("fieldname" => "type,type,type,type,type")
     * 
     * Where <type> is one of the following:
     * post - pull from the $_POST array
     * get - pull from the $_GET array
     * cookie - pull from the $_COOKIE array
     * session - pull from the $_SESSION array
     * file - pull from the $_FILES array while verifying that it is in fact a validly uploaded file
     * number - verify that the value is numeric and set it in the $values, otherwise unset it
     * string - verify that we have a non-zero length string and that it really is a string, and addslashes() to it
     * boolean - if it's set, set it it's value to 1, otherwise 0. Simple. =o)
     * 
     * @return void
     */
    function sanitizeInput()
    {
        if (is_array($this->fields)) {
            foreach ($this->fields as $key => $val) {
                foreach (explode(",", $val) as $subval) {
                    switch (strtolower($subval)) {
                        case "post":
                            if (!isset($this->values[$key])) $this->values[$key] = $_POST[$key];
                            break;
            
                        case "get":
                            if (!isset($this->values[$key])) $this->values[$key] = $_GET[$key];
                            break;
            
                        case "cookie":
                            if (!isset($this->values[$key])) $this->values[$key] = $_COOKIE[$key];
                            break;
            
                        case "session":
                            if (!isset($this->values[$key])) $this->values[$key] = $_SESSION[$key];
                            break;
            
                        case "file":
                            if (is_uploaded_file($_FILES[$key]['tmp_name'])) {
                                $this->values[$key] = $_FILES[$key];
                            }
                            break;
            
                        case "number":
                            if (is_numeric($this->values[$key])) {
                                $this->values[$key] = (integer)($this->values[$key]);
                            } else {
                                unset($this->values[$key]);
                            }
                            break;
            
                        case "string":
                            if (is_string($this->values[$key]) && (strlen($this->values[$key]) > 0)) {
                                $this->values[$key] = addslashes($this->values[$key]);
                            } else {
                                unset($this->values[$key]);
                            }
                            break;
            
                        case "boolean":
                            if (isset($this->values[$key])) {
                                $this->values[$key] = 1;
                            } else {
                                $this->values[$key] = 0;
                            }
                            break;
                    }
                }
            }
        }
    }

    /**
     * Make sure the required values are set
     * 
     * Very simple function to make sure that the values that are set in the $required array are actually
     * set in the $values array. Format is as follows:
     * 
     * $this->required = array("field1", "field2", "field3");
     * 
     * @return Array|void An array of errors or nothing
     */
    function validateValues()
    {
        global $_THEME;
    
        foreach ($this->required AS $val) {
            if (!isset($this->values[$val])) {
                $this->errors[$val] = true;
            }
        }
    
        if (DEBUG) {
            if (is_array($this->errors)) {
                foreach ($this->errors as $key => $val) {
                    $_THEME->addToDebug("DataObject::validateValues", "errors[$key]<br>");
                }
            }
        }
    
        return $this->errors;
    }

    /**
     * Returns a string representing a control of a field.
     * 
     * @return string
     * @param name string The name of the field
     * @param type string Any type that will fill the type attribute of the input HTML element
     * @param opts string Any extra option attributes to pass verbatim to the input HTML element
     */
    function getField($name, $type, $opts="")
    {
        if (($this->errors[$name]) && isset($this->mode)) {
            $str = "<table border=0 cellspacing=0 cellpadding=3 bgcolor='red'><tr><td>\n";
        }
    
        $str .= "<input type=\"$type\" name=\"$name\" value=\"";
        if (strcasecmp($type, "password") !== true) $str .= stripslashes(htmlentities($this->values[$name]));
        $str .= "\" $opts>\n";
    
        if (($this->errors[$name]) && isset($this->mode)) {
            $str .= "</td></tr></table>\n";
        }
    
        return $str;
    }

    /**
     * Returns a string representing a checkbox.
     * 
     * @return string
     * @param name string The name of the checkbox
     * @param opts string Any extra option attributes to pass verbatim to the input HTML element
     */
    function getCheckbox($name, $opts="")
    {
        if (($this->errors[$name]) && isset($this->mode)) {
            $str = "<table border=0 cellspacing=0 cellpadding=3 bgcolor='red'><tr><td>\n";
        }
        $str .= "<input type=\"checkbox\"";
        if (isset($this->values[$name])) {
            $str .= " checked";
        }
        $str .= " $opts>\n";
        if (($this->errors[$name]) && isset($this->mode)) {
            $str .= "</td></tr></table>\n";
        }
    
        return $str;
    }
    
    /**
     * Returns a string representing a text area form element.
     * 
     * @return string
     * @param name string The name of the text area.
     * @param opts string Any extra option attributes to pass verbatim to the textarea HTML element
     */
    function getTextArea($name, $opts="")
    {
        if (($this->errors[$name]) && isset($this->mode)) {
            $str = "<table border=0 cellspacing=0 cellpadding=3 bgcolor='red'><tr><td>\n";
        }
        $str .= "<textarea name=\"$name\" $opts>".stripslashes(htmlentities($this->values[$name]))."</textarea>\n";
        if (($this->errors[$name]) && isset($this->mode)) {
            $str .= "</td></tr></table>\n";
        }
    
        return $str;
    }
    
    /**
     * Returns a string representing an RTE form element.
     * 
     * @return string
     * @param name string The name of the text area.
     * @param opts string Any extra option attributes to pass verbatim to the textarea HTML element
     */
    function getRTE($name, $width = 300, $height = 200, $buttons = true, $ro = false, $imgs = "/assets/rte/images/", $incs = "/assets/rte/", $css = "/assets/rte/rte.css")
    {
        if (($this->errors[$name]) && isset($this->mode)) {
            $str = "<table border=0 cellspacing=0 cellpadding=3 bgcolor='red'><tr><td>\n";
        }
    
        $str .= "<script language=\"JavaScript\" type=\"text/javascript\">\n";
        $str .= "<!--\n";
        $str .= "\tinitRTE(\"". $imgs ."\", \"". $incs ."\", \"". $css ."\");\n";
        $str .= "\twriteRichText(\"". $name ."\", \"". Utils::RTESafe(stripslashes($this->values[$name])) ."\", ". $width .", ". $height .", ". ($buttons ? "true" : "false") .", ". ($ro ? "true" : "false") .");\n";
        $str .= "-->\n";
        $str .= "</script>";
    
        if (($this->errors[$name]) && isset($this->mode)) {
            $str .= "</td></tr></table>\n";
        }
    
        return $str;
    }
    
    /**
     * Generate a string for a select HTML form element.
     * This particular get{blah}() method is special, as it takes an associative array
     * as it's list of values and uses the key as the value and the value as the description
     * for each <option> element. Eg if you pass an array like this:
     * 
     *     $foo = array(0 => "Nothing",
     *                  1 => "Something",
     *                  2 => "WHAAAT?!");
     * 
     * Then the generated options will be like this:
     * 
     *     <option value="0">Nothing</option>
     *     <option value="1">Something</option>
     *     <option value="2">WHAAAT?!</option>
     * 
     * @return string
     * @param name string The name of the select form element.
     * @param options array The associative array of options.
     * @param value mixed The value the option was previously set to.
     * @param opts string A listing of additional attributes to pass verbatim to the select HTML tag
     * @param want_select_one boolean Weather or not you want to have a blank Select One... entry in the list
     */
    function getSelect($name, $options, $value = false, $opts = "", $want_select_one = true)
    {
        if (($this->errors[$name]) && isset($this->mode)) {
            $str = "<table border=0 cellspacing=0 cellpadding=3 bgcolor='red'><tr><td>\n";
        }
    
        if (is_array($options)) {
            $str .= "<select name=\"$name\" $opts>\n";
        
            if ($want_select_one) {
                $str .= "  <option value=\"\">Select One...</option>\n";
            }
        
            foreach ($options as $key => $val) {
                $str .= "  <option value=\"$key\"";
                if ($value === $key) $str .= " selected";
                $str .= ">";
                $str .= $val;
                $str .= "</option>\n";
            }
        
            $str .= "</select>\n";
        } else {
            $str = "<b>Warning:</b> DataObject::getSelect() argument \$options is not an array (\$options == $options)";
        }
    
        if (($this->errors[$name]) && isset($this->mode)) {
            $str .= "</td></tr></table>\n";
        }
    
        return $str;
    }

    /**
     * Returns a string representing a file upload form element.
     * 
     * @return string
     * @param name string The name of the upload element
     * @param maxsize string The maximum file upload size. Note that this is limited by the max_upload_size php ini setting
     */
    function getFile($name, $maxsize=30000)
    {
        $str  = "<input type='hidden' name='MAX_FILE_SIZE' value='$maxsize'>\n";
        $str .= "<input type='file' name='$name' value='". $this->values[$name]['name'] ."'>\n";
    
        return $str;
    }
        
    /**
     * A virtual function to be overrided.
     * 
     * @return void
     */
    function updateDB()
    {
        if ($this->debug) echo "<b>Warning:</b> DataObject::updateDB called!<br>";
    }
    
    /**
     * A virtual function to be overrided.
     * 
     * @return void
     */
    function insertIntoDB()
    {
        if ($this->debug) echo "<b>Warning:</b> DataObject::insertIntoDB called!<br>";
    }
    
    /**
     * A virtual function to be overrided.
     * 
     * @return void
     */
    function loadFromDB()
    {
        if ($this->debug) echo "<b>Warning:</b> DataObject::loadFromDB called!<br>";
    }
    
    /**
     * A virtual function to be overrided.
     * 
     * @return void
     */
    function deleteFromDB()
    {
        if ($this->debug) echo "<b>Warning:</b> DataObject::deleteFromDB called!<br>";
    }
}
