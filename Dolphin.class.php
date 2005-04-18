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

require_once("Singleton.class.php");
require_once("URL.class.php");
require_once("DolphinDriver.class.php");

/**
 * Defines the Dolphin MySQL database class
 * 
 * @package jtlib
 * @copyright Copyright(c) 2004, June Rebecca Tate
 * @author June R. Tate <june@theonelab.com>
 * @version $Revision$
 */

/**
 * Dolphin database abstraction class.
 *
 * A class for handling database transactions. Originally designed
 * specifically for MySQL operations, Dolphin has evolved to provide
 * a host of data munging operations, several database drivers
 * (currently PostgreSQL and MySQL), and is set and ready for more
 * additions.
 *
 * @package jtlib
 */
class Dolphin
{
    var $_url;
    var $_driver;

    /**
     * Constructor
     *
     * @access public
     * @param string The database to use
     * @param string The username to authenticate to MySQL as
     * @param string The password to authenticate with
     * @param string The hostname or IP address to connect to
     * @returns void
     */
    function Dolphin()
    {
    }

    function connect($url)
    {
        $this->_url = new URL($url);

        if (!$this->_url->isValidURL()) {
            trigger_error("Dolphin::connect: $url is not a valid URL.", ERR_ERROR);
            return false;
        }
        
        $this->_driver = DolphinDriver::createInstance($this->_url->getProtocol());

        if (!$this->_driver) {
            trigger_error("Dolphin::connect: Unable to create instance of DolphinDriver for ". $this->_url->getProtocol, ERR_ERROR);
            return false;
        }

        return $this->_driver->connect($this->_url);
    }
    
    /**
     * Send an SQL or MySQL query directly to the database and return true on success.
     * 
     * @param string An SQL-92 or MySQL formatted string to pass to the database
     * @returns boolean
     */
    function query($query)
    {
        return $this->_driver->query($query);
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
        return $this->_driver->getResultsArray();
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
        return $this->_driver->getResultsRow();
    }
    
    /**
     * Return an integral value of the number of rows a previous query generated.
     * 
     * @returns integer Number of rows
     */
    function getNumRows()
    {
        return $this->_driver->getNumRows();
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
        return $this->_driver->getResultsValue($field);
    }

    function getHandle()
    {
        return $this->_driver->getHandle();
    }
}
