<?PHP
require_once("DolphinDriver.class.php");
DolphinDriver::registerDriver("mysql", "DolphinDriver_mysql");

class DolphinDriver_mysql extends DolphinDriver
{
    var $_url;
    var $_result;
    var $_handle;
    
    function DolphinDriver_mysql()
    {
    }

    function connect($url)
    {
        $this->_url = $url;
        $port = ($url->getPort() ? ":". $url->getPort() : "");
        
        if ($url->getHost()) {
            if ($url->getUser()) {
                if ($url->getPass()) {
                    $this->_handle = mysql_connect($url->getHost() . $port, $url->getUser(), $url->getPass());
                } else {
                    $this->_handle = mysql_connect($url->getHost() . $port, $url->getUser());
                }
            } else {
                $this->_handle = mysql_connect($url->getHost() . $port);
            }
        }

        if (!$this->_handle) {
            trigger_error("DolphinDriver_mysql::connect: Unable to conect to MySQL server. MySQL reports ". mysql_error(), ERR_ERROR);
            return false;
        } else {
            if ($url->getPath()) {
                if (!mysql_select_db(str_replace("/", "", $url->getPath()))) {
                    trigger_error("DolphinDriver_mysql::connect: Unable to select database ". $url->getPath() .". MySQL reports ". mysql_error(), ERR_ERROR);
                    return false;
                } else {
                    return true;
                }
            } else {
                return true;
            }
        }
    }
    
    /**
     * Send an SQL or MySQL query directly to the database and return true on success.
     * 
     * @param string An SQL-92 or MySQL formatted string to pass to the database
     * @returns boolean
     */
    function query($query)
    {
        $this->_result = mysql_query($query);

        if ($this->_result !== false) {
            return true;
        } else {
            trigger_error("DolphinDriver_mysql::query: Unable to execute query $query. MySQL reports: ". mysql_error($this->_conn), ERR_ERROR);
            return false;
        }
    }
    
    /**
     * Return an array of results. The first index in the array is the row number, followed by an
     * associative list of field names => values. Note that this will only work if you have called
     * Dolphin::query() first!
     * 
     * @returns array The array of results from the previous query operation.
     */
    function getResultsArray()
    {
        if (is_resource($this->_result)) {
            $i = 0;
            $results = array();
            
            while ($row = mysql_fetch_assoc($this->_result)) {
                $results[$i++] = $row;
            }
            
            return $results;
        } else {
            trigger_error("DolphinDriver_mysql::getResultsArray: Previous query did not return a result resource!", ERR_ERROR);
            return false;
        }
    }
    
    /**
     * Return an associative results row from the previous query operation.
     * Basicially does the same job as mysql_fetch_assoc(). Each successive call
     * returns the next row of information.
     * 
     * @returns array Returns an associative array of results
     */
    function getResultsRow()
    {
        if (is_resource($this->_result)) {
            $row = mysql_fetch_assoc($this->_result);
            return $row;
        } else {
            trigger_error("DolphinDriver_mysql::getResultsRow: Previous query did not return a result resource.", ERR_ERROR);
            return false;
        }
    }
    
    /**
     * Return an integral value of the number of rows a previous query generated.
     * 
     * @returns integer Number of rows
     */
    function getNumRows()
    {
        if (is_resource($this->_result)) {
            return mysql_num_rows($this->_result);
        } else {
            trigger_error("DolphinDriver_mysql::getNumRows: Previous query did not return a result resource.", ERR_ERROR);
            return false;
        }
    }
    
    /**
     * This pulls a specific field out of a result. Note that successive calls to
     * this function will return the result of the next row instead of the same
     * row.
     * 
     * @returns mixed The result of a specific field in the current row
     */
    function getResultsValue($field)
    {
        if (is_resource($this->_result)) {
            $arry = $this->getResultsRow();
            return $arry[$field];
        } else {
            trigger_error("DolphinDriver_mysql::getResultValue: Previous query did not return a result resource.", ERR_ERROR);
            return false;
        }
    }

    function getHandle()
    {
        return $this->_handle;
    }
}
