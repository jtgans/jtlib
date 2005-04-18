<?PHP
class XMLRPC
{
    var $_methods;
    var $_server;
    var $_last_request;
    var $_last_response;
    
    function XMLRPC()
    {
        $this->_server = xmlrpc_server_create();
        register_shutdown_function(array(&$this, "_shutdown"));

        foreach ($this->_methods AS $method => $function) {
            xmlrpc_server_register_method($this->_server, $method, $function);
        }
    }

    function _handleRequest()
    {
        global $HTTP_RAW_POST_DATA;
        
        $request = file_get_contents("php://input");
        if (($request === false) || (strlen($request) == 0)) {
            if (isset($HTTP_RAW_POST_DATA)) {
                $request = $HTTP_RAW_POST_DATA;
            } else {
                $request = "!!! Unable to read \$HTTP_RAW_POST_DATA or php://input !!!";
            }
        }

        // Strip out any oddball stuff, like any NULLs
        $request = str_replace("\000", "", $request);
        $response = xmlrpc_server_call_method($this->_server, $request, null);
        
        header('Content-Type: text/xml');
        header('Connection: close');
        header('Server: XMLRPC PHP Class');
        header('Status: 200 OK');
        print_r($response);

        $this->_last_request  = $request;
        $this->_last_response = $response;
    }

    function _shutdown()
    {
        xmlrpc_server_destroy($this->_server);
    }
}
