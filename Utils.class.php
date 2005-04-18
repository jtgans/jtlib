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
 * General class containing useful misc. functions.
 *
 * This class contains a plethora of utility functions I've found
 * useful over the years. Yes, many of these functions can be
 * broken out into separate classes to clean it up a bit, and perhaps
 * sometime I'll give it a go, but for now, they all reside here.
 *
 * @package jtlib
 * @copyright Copyright(c) 2004, June Rebecca Tate
 * @author June R. Tate <june@theonelab.com>
 * @version $Revision$
 */
class Utils
{
    function errorMsg($class_name, $line, $errormsg)
    {
        echo "<b>$class_name: $line:</b> $erormsg<br />\n";
        
        $error = mysql_error();
        if ($error) {
            echo "<b>MySQL Error:</b> $error<br />\n";
        }
    }
    
    function breakUpLine($text, $limit)
    {
        // Make sure that the $text doesn't have any really long words in it
        // Split it up on the spaces
    
        $ary = explode(" ", $text);
    
        // Now run through each element making sure that it isn't too long
        foreach ($ary AS $key => $val) {
            if (strlen($val) <= $limit) {
                $retval .= $val . " ";
            } else {
                while (strlen($val) > $limit) {
                    $retval .= substr($val, 0, $limit) . " ";
                    $val = substr($val, $limit, strlen($val));
                }
                $retval .= $val . " ";
            }
        }

        return trim($retval);
    }

    function randomString($num_chars)
    {
        for ($i=0; $i<$num_chars; $i++) {
            $string .= chr((rand() % 30) + 60);
        }
    
        return $string;
    }
    
    function shortenLine($str, $len, $keep_extra = false)
    {
        if (strlen($str) > $len) {
            $newstr = substr($str, 0, floor($len / 2));
            $newstr .= "...";
        
            if ($keep_extra) $newstr .= substr($str, -(floor($len / 2)), floor($len / 2));
        } else {
            $newstr = $str;
        }
    
        return $newstr;
    }

    function array_insert_assoc($ary1, $start_key, $ary2)
    {
        $result = array();
    
        foreach ($ary1 AS $key => $val) {
            $result["$key"] = $val;
        
            if ((string)($key) === (string)($start_key)) {
                foreach ($ary2 AS $ky => $val) {
                    $result["$ky"] = $val;
                }
            }
        }
    
        return $result;
    }
    
    function str_pad_str($src, $times, $str = " ", $pos = STR_PAD_LEFT)
    {
        $str = str_pad($str, strlen($str) * $times, $str);
    
        if ($pos === STR_PAD_LEFT) {
            return $str . $src;
        } else if ($pos === STR_PAD_RIGHT) {
            return $src . $str;
        } else if ($pos === STR_PAD_BOTH) {
            return $str . $src . $str;
        }
    }
    
    function redirect($url)
    {
        header("Location: $url");
        exit;
    }
    
    function preg_escape($str)
    {
        $str = str_replace('\\', '\\\\', $str);
        $str = str_replace('/', '\/', $str);
        $str = str_replace('(', '\(', $str);
        $str = str_replace(')', '\)', $str);
        $str = str_replace('[', '\[', $str);
        $str = str_replace(']', '\]', $str);
        $str = str_replace('$', '\$', $str);
        $str = str_replace('^', '\^', $str);
    
        return $str;
    }

    function timeDiff($start, $end)
    {
        $diff = ($end - $start);
        
        $days = floor($diff / (24 * 60 * 60));
        $remainder = $diff % (24 * 60 * 60);

        $hours = floor($remainder / (60 * 60));
        $remainder = $remainder % (60 * 60);

        $minutes = floor($remainder / 60);
        $seconds = $remainder % 60;

        return array($days, $hours, $minutes, $seconds);
    }

    function iso8601_date($time = false) {
        if ($time === false) {
            $time = time();
        }
        
        $tzd = date('O',$time);
        $tzd = substr(chunk_split($tzd, 3, ':'),0,6);
        $date = date('Y-m-d\TH:i:s', $time) . $tzd;
        return $date;
    }
}
