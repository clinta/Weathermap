<?php
// Sample Pluggable datasource for PHP Weathermap 0.9
// - read a pair of values from a database, and return it

// TARGET dbplug:databasename:username:pass:hostkey

class WeatherMapDataSource_mrtg extends WeatherMapDataSource
{

    function Recognise($targetString)
    {
        if (preg_match("/\.(htm|html)$/", $targetString)) {
            return true;
        } else {
            return false;
        }
    }

    function ReadData($targetString, &$map, &$mapItem)
    {
        $data[IN] = null;
        $data[OUT] = null;
        $data_time = 0;

        $matchvalue= $mapItem->get_hint('mrtg_value');
        $matchperiod = $mapItem->get_hint('mrtg_period');
        $swap = intval($mapItem->get_hint('mrtg_swap'));
        $negate = intval($mapItem->get_hint('mrtg_negate'));

        if ($matchvalue =='') {
            $matchvalue = "cu";
        }
        if ($matchperiod =='') {
            $matchperiod = "d";
        }

        $fd=fopen($targetString, "r");

        if ($fd) {
            while (!feof($fd)) {
                $buffer=fgets($fd, 4096);
                wm_debug("MRTG ReadData: Matching on '${matchvalue}in $matchperiod' and '${matchvalue}out $matchperiod'\n");

                if (preg_match("/<\!-- ${matchvalue}in $matchperiod ([-+]?\d+\.?\d*) -->/", $buffer, $matches)) {
                    $data[IN] = $matches[1] * 8;
                }
                if (preg_match("/<\!-- ${matchvalue}out $matchperiod ([-+]?\d+\.?\d*) -->/", $buffer, $matches)) {
                    $data[OUT] = $matches[1] * 8;
                }
            }
            fclose($fd);
            # don't bother with the modified time if the target is a URL
            if (! preg_match('/^[a-z]+:\/\//', $targetString)) {
                $data_time = filemtime($targetString);
            }
        } else {
            // some error code to go in here
            wm_debug("MRTG ReadData: Couldn't open ($targetString). \n");
        }

        if ($swap==1) {
            wm_debug("MRTG ReadData: Swapping IN and OUT\n");
            $t = $data[OUT];
            $data[OUT] = $data[IN];
            $data[IN] = $t;
        }

        if ($negate) {
            wm_debug("MRTG ReadData: Negating values\n");
            $data[OUT] = -$data[OUT];
            $data[IN] = -$data[IN];
        }

        wm_debug("MRTG ReadData: Returning (".($data[IN]===null?'null':$data[IN]).",".($data[OUT]===null?'null':$data[OUT]).",$data_time)\n");

        return (array($data[IN], $data[OUT], $data_time));
    }
}

// vim:ts=4:sw=4:
