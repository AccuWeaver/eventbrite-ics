<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Config
 *
 * @author newadminaccount
 */
class Config {

    //put your code here
    private $config = false;
    private $filename = "eventbrite-ics.ini";

    function __construct() {
        
    }

    function setFileName($filename = null) {
        $this->filename = $filename;
    }

    function getFileName() {
        return $this->filename;
    }

    function read() {
        try {
            if (parse_ini_file($this->filename)) {
                $this->config = parse_ini_file($this->filename);
            }
        } catch (Exception $ex) {
            // We cheat and redirect to the config page ...
            $this->config = false;
        }
        if (isset($_SERVER['REQUEST_URI'])) {
            if (!strpos($_SERVER['REQUEST_URI'], 'index')) {
                if (!$this->config) {
                    header('Location: index.php');
                    ob_clean();
                    flush();
                }
            }
        }
        return $this->config;
    }

    function write() {
        $this->write_php_ini($this->config, $this->filename);
    }

    function getParam($key = null) {
        if (isset($this->config[$key])) {
            return $this->config[$key];
        }
        return null;
    }

    function setConfig($config = null) {
        if ($config == null) {
            $this->config = $this->read();
        } else {
            $this->config = $config;
        }
    }

    function write_php_ini($array, $file) {
        $res = array();
        foreach ($array as $key => $val) {
            if (is_array($val)) {
                $res[] = "[$key]";
                foreach ($val as $skey => $sval) {
                    if (is_array($sval)) {
                        foreach ($sval as $i => $v) {
                            $res[] = "{$skey}[$i] = $v";
                        }
                    } else {
                        $res[] = "$skey = $sval";
                    }
                }
            }
            else
                $res[] = "$key = $val";
        }
        $dataToSave = implode("\r\n", $res);
        if (!$this->safefilerewrite($file, $dataToSave)) {
            throw new ErrorException("Can't write '" . $file . "'");
        }
    }

    function safefilerewrite($fileName, $dataToSave) {
        if ($fp = fopen($fileName, 'w')) {
            $startTime = microtime();
            do {
                $canWrite = flock($fp, LOCK_EX);
                // If lock not obtained sleep for 0 - 100 milliseconds, to avoid collision and CPU load
                if (!$canWrite)
                    usleep(round(rand(0, 100) * 1000));
            } while ((!$canWrite) and ((microtime() - $startTime) < 1000));

            //file was locked so now we can store information
            if ($canWrite) {
                $return = fwrite($fp, $dataToSave);
                flock($fp, LOCK_UN);
            }
            fclose($fp);
        }
        return $return;
    }

    /*
     * The following was taken from http://e-tel.eu/post/10489929846/create-a-time-zone-drop-down-list-ddl-in-php
     *
     * returns a HTML formated TimeZone select
     *
     * @param $selectedTimeZone string The timezone marked as "selected"
     * @return string
     */

    function displayTimeZoneSelect($selectedTimeZone = 'America/Los_Angeles') {
        $countryCodes = $this->getCountryCodes();
        $return = null;
        foreach ($countryCodes as $country => $countryCode) {
            $timezone_identifiers = DateTimeZone::listIdentifiers(DateTimeZone::PER_COUNTRY, $countryCode);
            foreach ($timezone_identifiers as $value) {
                /* getTimeZoneOffset returns minutes and we need to display hours */
                $offset = $this->getTimeZoneOffset($value) / 60;
                /* for the GMT+1 GMT-1 display */
                $offset = ( substr($offset, 0, 1) == "-" ? " (GMT" : " (GMT+" ) . $offset . ")";
                /* America/New_York -> America/New York */
                $displayValue = (str_replace('_', ' ', $value));
                /* Find the city */
                $ex = explode("/", $displayValue);
                $city = ( ($ex[2]) ? $ex[2] : $ex[1] );
                /* For the special names */
                $displayValue = htmlentities($country . " - " . $city . $offset);
                /* handle the $selectedTimeZone in the select form */
                $selected = ( ($value == $selectedTimeZone) ? ' selected="selected"' : null );
                $return .= '<option value="' . $value . '"' . $selected . '>'
                        . $displayValue
                        . '</option>' . PHP_EOL;
            }
        }

        return $return;
    }

    /**
     * Write the options for time period (before and after) ..
     * 
     * @param type $selectedTimeOptions
     */
    function displayTimeOptions($selectedTimeOptions = '1 Month') {
        $options = array(
            "1 Month" => "1 Month",
            "3 Month" => "3 Month",
            "6 Month" => "6 Month",
            "12 Month" => "12 Month"
        );
        $return = null;
        foreach ($options as $option => $value) {
            $selected = ( ($value == $selectedTimeOptions) ? ' selected="selected"' : null );
            $return .= '<option value="' . $value . '"' . $selected . '>'
                    . $value
                    . '</option>' . PHP_EOL;
        }
        return $return;
    }

    /**
     * ISO 3166 code list
     *
     * @return array The country codes in 'COUNTRY' => 'CODE' format
     * @link http://www.iso.org/iso/iso_3166_code_lists ISO Website
     */
    function getCountryCodes() {

        $return = array(
            "AFGHANISTAN" => "AF",
            "ALAND ISLANDS" => "AX",
            "ALBANIA" => "AL",
            "ALGERIA" => "DZ",
            "AMERICAN SAMOA" => "AS",
            "ANDORRA" => "AD",
            "ANGOLA" => "AO",
            "ANGUILLA" => "AI",
            "ANTARCTICA" => "AQ",
            "ANTIGUA AND BARBUDA" => "AG",
            "ARGENTINA" => "AR",
            "ARMENIA" => "AM",
            "ARUBA" => "AW",
            "AUSTRALIA" => "AU",
            "AUSTRIA" => "AT",
            "AZERBAIJAN" => "AZ",
            "BAHAMAS" => "BS",
            "BAHRAIN" => "BH",
            "BANGLADESH" => "BD",
            "BARBADOS" => "BB",
            "BELARUS" => "BY",
            "BELGIUM" => "BE",
            "BELIZE" => "BZ",
            "BENIN" => "BJ",
            "BERMUDA" => "BM",
            "BHUTAN" => "BT",
            "BOLIVIA, PLURINATIONAL STATE OF" => "BO",
            "BONAIRE, SINT EUSTATIUS AND SABA" => "BQ",
            "BOSNIA AND HERZEGOVINA" => "BA",
            "BOTSWANA" => "BW",
            "BOUVET ISLAND" => "BV",
            "BRAZIL" => "BR",
            "BRITISH INDIAN OCEAN TERRITORY" => "IO",
            "BRUNEI DARUSSALAM" => "BN",
            "BULGARIA" => "BG",
            "BURKINA FASO" => "BF",
            "BURUNDI" => "BI",
            "CAMBODIA" => "KH",
            "CAMEROON" => "CM",
            "CANADA" => "CA",
            "CAPE VERDE" => "CV",
            "CAYMAN ISLANDS" => "KY",
            "CENTRAL AFRICAN REPUBLIC" => "CF",
            "CHAD" => "TD",
            "CHILE" => "CL",
            "CHINA" => "CN",
            "CHRISTMAS ISLAND" => "CX",
            "COCOS (KEELING) ISLANDS" => "CC",
            "COLOMBIA" => "CO",
            "COMOROS" => "KM",
            "CONGO" => "CG",
            "CONGO, THE DEMOCRATIC REPUBLIC OF THE" => "CD",
            "COOK ISLANDS" => "CK",
            "COSTA RICA" => "CR",
            "CÔTE D'IVOIRE" => "CI",
            "CROATIA" => "HR",
            "CUBA" => "CU",
            "CURAÇAO" => "CW",
            "CYPRUS" => "CY",
            "CZECH REPUBLIC" => "CZ",
            "DENMARK" => "DK",
            "DJIBOUTI" => "DJ",
            "DOMINICA" => "DM",
            "DOMINICAN REPUBLIC" => "DO",
            "ECUADOR" => "EC",
            "EGYPT" => "EG",
            "EL SALVADOR" => "SV",
            "EQUATORIAL GUINEA" => "GQ",
            "ERITREA" => "ER",
            "ESTONIA" => "EE",
            "ETHIOPIA" => "ET",
            "FALKLAND ISLANDS (MALVINAS)" => "FK",
            "FAROE ISLANDS" => "FO",
            "FIJI" => "FJ",
            "FINLAND" => "FI",
            "FRANCE" => "FR",
            "FRENCH GUIANA" => "GF",
            "FRENCH POLYNESIA" => "PF",
            "FRENCH SOUTHERN TERRITORIES" => "TF",
            "GABON" => "GA",
            "GAMBIA" => "GM",
            "GEORGIA" => "GE",
            "GERMANY" => "DE",
            "GHANA" => "GH",
            "GIBRALTAR" => "GI",
            "GREECE" => "GR",
            "GREENLAND" => "GL",
            "GRENADA" => "GD",
            "GUADELOUPE" => "GP",
            "GUAM" => "GU",
            "GUATEMALA" => "GT",
            "GUERNSEY" => "GG",
            "GUINEA" => "GN",
            "GUINEA-BISSAU" => "GW",
            "GUYANA" => "GY",
            "HAITI" => "HT",
            "HEARD ISLAND AND MCDONALD ISLANDS" => "HM",
            "HOLY SEE (VATICAN CITY STATE)" => "VA",
            "HONDURAS" => "HN",
            "HONG KONG" => "HK",
            "HUNGARY" => "HU",
            "ICELAND" => "IS",
            "INDIA" => "IN",
            "INDONESIA" => "ID",
            "IRAN, ISLAMIC REPUBLIC OF" => "IR",
            "IRAQ" => "IQ",
            "IRELAND" => "IE",
            "ISLE OF MAN" => "IM",
            "ISRAEL" => "IL",
            "ITALY" => "IT",
            "JAMAICA" => "JM",
            "JAPAN" => "JP",
            "JERSEY" => "JE",
            "JORDAN" => "JO",
            "KAZAKHSTAN" => "KZ",
            "KENYA" => "KE",
            "KIRIBATI" => "KI",
            "KOREA, DEMOCRATIC PEOPLE'S REPUBLIC OF" => "KP",
            "KOREA, REPUBLIC OF" => "KR",
            "KUWAIT" => "KW",
            "KYRGYZSTAN" => "KG",
            "LAO PEOPLE'S DEMOCRATIC REPUBLIC" => "LA",
            "LATVIA" => "LV",
            "LEBANON" => "LB",
            "LESOTHO" => "LS",
            "LIBERIA" => "LR",
            "LIBYAN ARAB JAMAHIRIYA" => "LY",
            "LIECHTENSTEIN" => "LI",
            "LITHUANIA" => "LT",
            "LUXEMBOURG" => "LU",
            "MACAO" => "MO",
            "MACEDONIA, THE FORMER YUGOSLAV REPUBLIC OF" => "MK",
            "MADAGASCAR" => "MG",
            "MALAWI" => "MW",
            "MALAYSIA" => "MY",
            "MALDIVES" => "MV",
            "MALI" => "ML",
            "MALTA" => "MT",
            "MARSHALL ISLANDS" => "MH",
            "MARTINIQUE" => "MQ",
            "MAURITANIA" => "MR",
            "MAURITIUS" => "MU",
            "MAYOTTE" => "YT",
            "MEXICO" => "MX",
            "MICRONESIA, FEDERATED STATES OF" => "FM",
            "MOLDOVA, REPUBLIC OF" => "MD",
            "MONACO" => "MC",
            "MONGOLIA" => "MN",
            "MONTENEGRO" => "ME",
            "MONTSERRAT" => "MS",
            "MOROCCO" => "MA",
            "MOZAMBIQUE" => "MZ",
            "MYANMAR" => "MM",
            "NAMIBIA" => "NA",
            "NAURU" => "NR",
            "NEPAL" => "NP",
            "NETHERLANDS" => "NL",
            "NEW CALEDONIA" => "NC",
            "NEW ZEALAND" => "NZ",
            "NICARAGUA" => "NI",
            "NIGER" => "NE",
            "NIGERIA" => "NG",
            "NIUE" => "NU",
            "NORFOLK ISLAND" => "NF",
            "NORTHERN MARIANA ISLANDS" => "MP",
            "NORWAY" => "NO",
            "OMAN" => "OM",
            "PAKISTAN" => "PK",
            "PALAU" => "PW",
            "PALESTINIAN TERRITORY, OCCUPIED" => "PS",
            "PANAMA" => "PA",
            "PAPUA NEW GUINEA" => "PG",
            "PARAGUAY" => "PY",
            "PERU" => "PE",
            "PHILIPPINES" => "PH",
            "PITCAIRN" => "PN",
            "POLAND" => "PL",
            "PORTUGAL" => "PT",
            "PUERTO RICO" => "PR",
            "QATAR" => "QA",
            "RÉUNION" => "RE",
            "ROMANIA" => "RO",
            "RUSSIAN FEDERATION" => "RU",
            "RWANDA" => "RW",
            "SAINT BARTHÉLEMY" => "BL",
            "SAINT HELENA, ASCENSION AND TRISTAN DA CUNHA" => "SH",
            "SAINT KITTS AND NEVIS" => "KN",
            "SAINT LUCIA" => "LC",
            "SAINT MARTIN (FRENCH PART)" => "MF",
            "SAINT PIERRE AND MIQUELON" => "PM",
            "SAINT VINCENT AND THE GRENADINES" => "VC",
            "SAMOA" => "WS",
            "SAN MARINO" => "SM",
            "SAO TOME AND PRINCIPE" => "ST",
            "SAUDI ARABIA" => "SA",
            "SENEGAL" => "SN",
            "SERBIA" => "RS",
            "SEYCHELLES" => "SC",
            "SIERRA LEONE" => "SL",
            "SINGAPORE" => "SG",
            "SINT MAARTEN (DUTCH PART)" => "SX",
            "SLOVAKIA" => "SK",
            "SLOVENIA" => "SI",
            "SOLOMON ISLANDS" => "SB",
            "SOMALIA" => "SO",
            "SOUTH AFRICA" => "ZA",
            "SOUTH GEORGIA AND THE SOUTH SANDWICH ISLANDS" => "GS",
            "SPAIN" => "ES",
            "SRI LANKA" => "LK",
            "SUDAN" => "SD",
            "SURINAME" => "SR",
            "SVALBARD AND JAN MAYEN" => "SJ",
            "SWAZILAND" => "SZ",
            "SWEDEN" => "SE",
            "SWITZERLAND" => "CH",
            "SYRIAN ARAB REPUBLIC" => "SY",
            "TAIWAN, PROVINCE OF CHINA" => "TW",
            "TAJIKISTAN" => "TJ",
            "TANZANIA, UNITED REPUBLIC OF" => "TZ",
            "THAILAND" => "TH",
            "TIMOR-LESTE" => "TL",
            "TOGO" => "TG",
            "TOKELAU" => "TK",
            "TONGA" => "TO",
            "TRINIDAD AND TOBAGO" => "TT",
            "TUNISIA" => "TN",
            "TURKEY" => "TR",
            "TURKMENISTAN" => "TM",
            "TURKS AND CAICOS ISLANDS" => "TC",
            "TUVALU" => "TV",
            "UGANDA" => "UG",
            "UKRAINE" => "UA",
            "UNITED ARAB EMIRATES" => "AE",
            "UNITED KINGDOM" => "GB",
            "UNITED STATES" => "US",
            "UNITED STATES MINOR OUTLYING ISLANDS" => "UM",
            "URUGUAY" => "UY",
            "UZBEKISTAN" => "UZ",
            "VANUATU" => "VU",
            "VENEZUELA, BOLIVARIAN REPUBLIC OF" => "VE",
            "VIET NAM" => "VN",
            "VIRGIN ISLANDS, BRITISH" => "VG",
            "VIRGIN ISLANDS, U.S." => "VI",
            "WALLIS AND FUTUNA" => "WF",
            "WESTERN SAHARA" => "EH",
            "YEMEN" => "YE",
            "ZAMBIA" => "ZM",
            "ZIMBABWE" => "ZW");
        return $return;
    }

    /**
     * Calculates the offset from UTC for a given timezone
     *
     * @return integer
     */
    function getTimeZoneOffset($timeZone) {
        $dateTimeZoneUTC = new DateTimeZone("UTC");
        $dateTimeZoneCurrent = new DateTimeZone($timeZone);

        $dateTimeUTC = new DateTime("now", $dateTimeZoneUTC);
        $dateTimeCurrent = new DateTime("now", $dateTimeZoneCurrent);

        $offset = (($dateTimeZoneCurrent->getOffset($dateTimeUTC)) / 60);
        return $offset;
    }

}

?>
