<?PHP
class DolphinDriver
{
    function DolphinDriver()
    {
        trigger_error("DolphinDriver::DolphinDriver: I shouldn't have been instanciated!", ERR_ERROR);
        return false;
    }

    function registerDriver($protocol, $class)
    {
        global $___DOLPHIN_DRIVERS;

        if (class_exists($class)) {
            $___DOLPHIN_DRIVERS[$protocol] = $class;

            return true;
        } else {
            trigger_error("DolphinDriver::registerDriver: No class by $class for protocol $protocol.", ERR_ERROR);
            return false;
        }
    }

    function createInstance($protocol)
    {
        global $___DOLPHIN_DRIVERS;
        
        if (isset($___DOLPHIN_DRIVERS[$protocol])) {
            if (class_exists($___DOLPHIN_DRIVERS[$protocol])) {
                $obj = new $___DOLPHIN_DRIVERS[$protocol];
                return $obj;
            } else {
                trigger_error("DolphinDriver::createInstance: No class by name ". $___DOLPHIN_DRIVERS[$protocol] ." for protocol $protocol.", ERR_ERROR);
                return null;
            }
        } else {
            trigger_error("DolphinDriver::createInstance: No class for protocol $protocol.", ERR_ERROR);
            return null;
        }
    }

    function connect($url)
    {
    }

    function getHandle()
    {
    }

    function query($query)
    {
    }

    function getResultRow()
    {
    }

    function getResultArray()
    {
    }

    function getResultValue($key)
    {
    }

    function getNumRows()
    {
    }
}
