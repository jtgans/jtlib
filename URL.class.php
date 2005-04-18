<?PHP
class URL
{
    var $_url;
    var $_protocol;
    var $_host;
    var $_port;
    var $_user;
    var $_pass;
    var $_path;
    var $_query;
    var $_fragment;
    
    function URL($str)
    {
        $this->setURL($str);
    }

    function _parseURL()
    {
        $result = parse_url($this->_url);

        $this->_protocol = isset($result['scheme'])   ? $result['scheme']   : false;
        $this->_host     = isset($result['host'])     ? $result['host']     : false;
        $this->_port     = isset($result['port'])     ? $result['port']     : false;
        $this->_user     = isset($result['user'])     ? $result['user']     : false;
        $this->_pass     = isset($result['pass'])     ? $result['pass']     : false;
        $this->_path     = isset($result['path'])     ? $result['path']     : false;
        $this->_query    = isset($result['query'])    ? $result['query']    : false;
        $this->_fragment = isset($result['fragment']) ? $result['fragment'] : false;
    }

    function isValidURL()
    {
        // INVALID: protocol://
        // INVALID: ://host
        if (!isset($this->_protocol)) return false;
        if (!isset($this->_host))     return false;

        // INVALID: protocol://:pass@host
        if (isset($this->_pass) &&
            (!isset($this->_user))) return false;

        return true;
    }

    function setURL($str)
    {
        $this->_url = $str;
        unset($this->_protocol);
        unset($this->_host);
        unset($this->_port);
        unset($this->_user);
        unset($this->_pass);
        unset($this->_path);
        unset($this->_query);
        unset($this->_fragment);
        
        $this->_parseURL();
    }

    function getURL()
    {
        return $this->_url;
    }
    
    function getProtocol()
    {
        return $this->_protocol;
    }

    function getHost()
    {
        return $this->_host;
    }

    function getPort()
    {
        return $this->_port;
    }

    function getUser()
    {
        return $this->_user;
    }

    function getPass()
    {
        return $this->_pass;
    }

    function getPath()
    {
        return $this->_path;
    }

    function getQuery()
    {
        return $this->_query;
    }

    function getFragment()
    {
        return $this->_fragment;
    }
}
