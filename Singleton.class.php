<?PHP
class Singleton
{
    function &getInstance($class)
    {
        static $instances;

        if (class_exists($class)) {
            if (!isset($instances[$class])) {
                $instances[$class] = new $class;
            }

            return $instances[$class];
        } else {
            trigger_error("Singleton::&getInstance: Unable to find a class by the name $class.", ERR_ERROR);
            return null;
        }
    }
}
