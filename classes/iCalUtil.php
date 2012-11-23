<?php


class ICalUtil {
     
    function __construct() {
        mb_internal_encoding("UTF-8");
    }

    function ical_split($preamble, $value) {
        // Trim the value
        $retval = $this->encode_ical($value);

        // Add the preamble
        $preamble_len = strlen($preamble);

        // Array for lines
        $lines = array();

        // Now loop through and split into lines 50 char or less.
        while (strlen($retval) > (75 - $preamble_len)) {
            // Max length ...
            $space = (75 - $preamble_len);
            $mbcc = $space;
            // Loop until zero ...
            while ($mbcc) {
                // Grab the number of characters we figured out
                // will be up to 75
                $line = mb_substr($retval, 0, $mbcc);
                // Length of the line we found ...
                $oct = strlen($line);
                // If the length of the line is greater than the allowed
                if ($oct > $space) {
                    // subtract off the length we pulled ...
                    $mbcc -= $oct - $space;
                } else {
                    $lines[] = $line;
                    $preamble_len = 1; // Still take the tab into account
                    $retval = mb_substr($retval, $mbcc);
                    break;
                }
            }
        }


        if (!empty($retval)) {
            $lines[] = $retval;
        }

        return join($lines);
    }

    function write_item($preamble, $value) {
        $item = $preamble . $this->ical_split($preamble, $value);
        return $item;
    }

    function encode_ical($value) {
        // Trim the value
        $retval = trim($value);

        // Strip out tags ..
        //$retval = strip_tags($retval);
        // Escaped characters
        //ESCAPED-CHAR => "\\" / "\;" / "\," / "\N" / "\n")
        //  \N or \n    encodes newline
        $retval = preg_replace('/\n+/', ' ', $retval);

        // dedup spaces at the front ...
        $retval = preg_replace('/\s{2,}/', ' ', $retval);


        //  \\          encodes \
        $retval = preg_replace('/{\\\}/', '\\', $retval);
        //  \;          encodes ; 
        $retval = preg_replace('/;/', '\;', $retval);
        //  \,          encodes ,
        $retval = preg_replace('/,/', '\,', $retval);
        return $retval;
    }

}

?>
