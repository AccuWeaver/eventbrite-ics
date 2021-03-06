<?php

require_once('Eventbrite.php');
require_once('iCalUtil.php');
require_once('Config.php');

define("CRLF", "\r\n");
define("LOGGING", false);

/**
 * Eventbrite ICS Class
 */
class EventbriteICS {

    private $config;
    private $eventbrite_client;
    private $events;
    private $logFh;
    private $outputFileName = "eventbrite.ics";
    private $timezone = "America/Los_Angeles";
    private $pubdate;
    private $today;
    private $begin_date;
    private $end_date;

    /**
     * Constructor
     */
    function __construct() {
        // Make sure we're doing UTF-8 - important for iCalendar
        mb_internal_encoding("UTF-8");

        $this->pubdate = strtotime("Now");
        $this->today = date('Ymd', $this->pubdate);
                // Get the configuration object
        $this->config = $this->getConfig();

        // Set the begin and end date based on parameters ...
        $before = '1 month';
        if ($this->config->getParam('before_period')){
            $before = $this->config->getParam('before_period');
        }
        $after = '1 month';
        if ($this->config->getParam('after_period')){
            $after = $this->config->getParam('after_period');
        }
        
        $this->setBeginDate(strtotime('-' . $before, strtotime("Now")));
        $this->setEndDate(strtotime('+' . $after, strtotime("Now")));

        // Get the parameters from the config object ...
        $authentication_tokens = array(
            'app_key' => $this->config->getParam('app_key')
            , 'user_key' => $this->config->getParam('user_key')
        );


        // Some other defaults from config
        if ($this->config->getParam('output_file_name')) {
            $this->outputFileName = $this->config->getParam('output_file_name');
        }
        if ($this->config->getParam('log_file_name')) {
            $this->logFileName = $this->config->getParam('log_file_name');
        }

        $this->ical_util = new ICalUtil();

        // Initialize the API client
        //  Eventbrite API / Application key (REQUIRED)
        //  http://www.eventbrite.com/api/key/
        //  Eventbrite user_key (OPTIONAL, only needed for reading/writing private user data)
        //   http://www.eventbrite.com/userkeyapi
        $this->eventbrite_client = new Eventbrite($authentication_tokens);
    }

    /**
     * Set Config
     * @param type $config
     */
    public function setConfig($config = array()) {
        $this->config = $config;
        $this->eventbrite_client->auth_tokens = $config;
    }

    /**
     * Get Config
     * @return type
     */
    public function getConfig() {
        if ($this->config == null) {
            $this->config = new Config();
            $this->config->read();
        }
        return $this->config;
    }

    /**
     * Get file name 
     * 
     * @return type
     */
    public function getFileName() {
        if ($this->outputFileName == null) {
            $this->getConfig();
            $this->outputFileName = $this->config->getFileName();
        }
        return $this->outputFileName;
    }

    /**
     * Set file name
     * 
     * @param type $output_file_name
     */
    public function setFileName($output_file_name = 'eventbrite.ics') {
        $this->getConfig();
        $this->config->setFileName($output_file_name);
    }

    /**
     * 
     * @param type $eventbrite
     */
    public function setEventbrite($eventbrite) {
        $this->eventbrite_client = $eventbrite;
    }

    /**
     * 
     * @param type $date
     */
    public function setBeginDate($date) {
        $this->begin_date = $date;
    }

    /**
     * 
     * @param type $date
     */
    public function setEndDate($date) {
        $this->end_date = $date;
    }

    /**
     * 
     * @return type
     */
    public function getEvents() {
        return $this->events;
    }

    /**
     * 
     */
    private function sendHeaders() {
        header('Content-type: text/calendar; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $this->getFileName() . '"');
    }

#
# Read Eventbrite and generate an ical calendar with the feed items as events.
#
#

    /**
     * 
     * @return string
     */
    public function readEventbrite() {
        if (LOGGING) {
            // Logging file handle
            $this->logFh = fopen('eventbrite-ics.log', 'w') or die("can't open file");
            $this->writeLog("eventbrite-ics.log created");
        } else {
            $this->logFh = false;
        }


        // For more information about the features that are available through 
        // the Eventbrite API, see http://developer.eventbrite.com/doc/
        try {
            // This actually uses the __call method to fetch the data from the
            // web service (JSON) and decodes the value back into the array
            // of events ...
            $this->events = $this->eventbrite_client->user_list_events();
        } catch (Exception $e) {
            $this->writeLog("Problem with Eventbrite: " . $e);
            $this->events = false;
        }

        $events = "BEGIN:VCALENDAR" . CRLF;
        $events .= "VERSION:2.0" . CRLF;
        $events .= "METHOD:PUBLISH" . CRLF;

        // FIXME - this should be an ID for the calendar - needs to
        //         be configurable.
        $events .= "PRODID:-//EventbriteICS//EventbriteCalendar//EN" . CRLF;

        // More elements from Google Calendar
        $events .= "CALSCALE:GREGORIAN" . CRLF;

        // FIXME - make the name of the calendar a config value
        //         or look this up from Eventbrite
        $events .= "X-WR-CALNAME:PMI-SFBAC Eventbrite Calendar" . CRLF;

        // And the Time Zone should come from Eventbrite and be consistent
        // throughout ...
        $events .= "X-WR-TIMEZONE:" . $this->timezone . CRLF;

        // This probably can come from Eventbrite too
        $events .= "X-WR-CALDESC:This is the PMI-SFBAC Eventbrite Calendar" . CRLF;

        // Time zone definition (from Google Calendar) ...
        $events .= "BEGIN:VTIMEZONE" . CRLF;
        $events .= "TZID:" . $this->timezone . CRLF;
        $events .= "X-LIC-LOCATION:" . $this->timezone . CRLF;
        $events .= "BEGIN:DAYLIGHT" . CRLF;
        $events .= "TZOFFSETFROM:-0800" . CRLF;
        $events .= "TZOFFSETTO:-0700" . CRLF;
        $events .= "TZNAME:PDT" . CRLF;
        $events .= "DTSTART:19700308T020000" . CRLF;
        $events .= "RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=2SU" . CRLF;
        $events .= "END:DAYLIGHT" . CRLF;
        $events .= "BEGIN:STANDARD" . CRLF;
        $events .= "TZOFFSETFROM:-0700" . CRLF;
        $events .= "TZOFFSETTO:-0800" . CRLF;
        $events .= "TZNAME:PST" . CRLF;
        $events .= "DTSTART:19701101T020000" . CRLF;
        $events .= "RRULE:FREQ=YEARLY;BYMONTH=11;BYDAY=1SU" . CRLF;
        $events .= "END:STANDARD" . CRLF;
        $events .= "END:VTIMEZONE" . CRLF;


        $this->writeLog("begin date " . date('Y-M-d', $this->begin_date));
        $this->writeLog("end date " . date('Y-M-d', $this->end_date));

        $i = 0;
        if ($this->events) {
            //print_r($events->events);
            # Loop through the feed items and format an event for each
            foreach ($this->events->events as $event) {
                // First we want to make sure not to display draft events ...
                if ($event->event->status == "Draft") {
                    $this->writeLog("Draft event " . $event->event->title . "\n");
                    continue;
                }

                // And don't import private events ...
                if ($event->event->privacy == "Private") {
                    $this->writeLog("Private event " . $event->event->title . "\n");
                    continue;
                }

                // Convert to time 
                $start_date = strtotime($event->event->start_date, $this->today);

                //writeLog($begin_date . " < " . $start_date . " > " . $end_date);
                // Check if within next 30 days ...
                if (($this->begin_date > $start_date) || ($start_date > $this->end_date)) {
                    $this->writeLog(date('Y-M-d', $start_date) . " start date not in range " . $event->event->title);
                    continue;
                } else {
                    $this->writeLog(date('Y-M-d', $start_date) . " start date in range " . $event->event->title);
                }

                $start = date('Ymd\THis', strtotime($event->event->start_date));
                $end = date('Ymd\THis', strtotime($event->event->end_date));
                $created = date('Ymd\THis', strtotime($event->event->created));
                $modified = date('Ymd\THis', strtotime($event->event->modified));

                $events .= "BEGIN:" . "VEVENT" . CRLF;
                $events .= "ORGANIZER;CN=PMI San Francisco Bay Area Chapter:MAILTO:eventbrite@pmi-sfbac.org" . CRLF;
                $events .= "UID:" . $event->event->id . CRLF;
                $events .= "URL:" . $this->formatURLText($event->event->url) . CRLF;
                $events .= "CATEGORIES:" . $event->event->category . CRLF;
                $events .= "CLASS:" . $event->event->privacy . CRLF;
                $events .= "CREATED;TZID=" . $event->event->timezone . ":" . $created . CRLF;
                $events .= "DTSTART;TZID=" . $event->event->timezone . ":" . $start . CRLF;
                $events .= "DTEND;TZID=" . $event->event->timezone . ":" . $end .  CRLF;
                $events .= $this->ical_util->write_item("SUMMARY:", $event->event->title) . CRLF;
                $events .= $this->getDescription($event->event->description, $event->event->url);
                $events .= "LAST-MODIFIED;TZID=" . $event->event->timezone . ":" . $modified . CRLF;
                // Not sure if we need this, or if we could get it from the event.
                $events .= "STATUS:" . $event->event->status . CRLF;
                $events .= "END:" . "VEVENT" . CRLF;
                # debug - uncomment the following to dump and stop after one event ...
                #print_r($event->event);
                #if ($i++ > 3) 
                //break;       
            } // End For loop
        } else {
            # The calendar must have 1 event
            # Make one for now containing the error message
            $events .= "BEGIN:VEVENT" . CRLF;
            $events .= "URL;VALUE=URI:http://www.pmi-sfbac.org/" . CRLF;
            $events .= "DTSTART;TZID=" . $this->timezone . ":" . $this->today . "T120000" . CRLF;
            $events .= "DTEND;TZID=" . $this->timezone . ":" . $this->today . "T163000" . CRLF;
            $events .= "SUMMARY:No rows found" . CRLF;
            $events .= "END:VEVENT" . CRLF;
        }
        $this->writeLog("------");
        $this->writeLog($events);
        $this->writeLog("------");
        if (LOGGING) {
            $this->logFh = fclose($this->logFh);
        }

        $events .= "END:VCALENDAR";

        return $events;
    }

    /**
     * 
     * @param type $url
     * @return boolean
     */
    private function formatURLText($url) {
        if (is_null($url)) {
            return false;
        }
        if (is_scalar($url)) {
            return $url;
        }
        return $url[0];
    }

    /**
     * 
     * @param type $url
     * @return string
     */
    private function formatURLHTML($url) {
        $newurl = $this->formatURLText($url);
        if ($newurl) {
            $newurl = '<a href="' . $newurl . '" target="_blank">' . $newurl . '</a>';
        }
        return $newurl;
    }

    /**
     *  Format the description and URL into a description 
     * 
     */
    private function getDescriptionText($description, $url) {
        $new_description = $description;
        $new_url = $this->formatURLText($url);
        if ($new_url) {
            $new_description .= "\n\n" . $new_url;
        }
        $returnText = $this->ical_util->write_item("DESCRIPTION:", $new_description);
        $returnText .= CRLF;

        return $returnText;
    }

    /**
     * 
     * @param type $description
     * @param type $url
     * @return type
     */
    private function getDescriptionHTML($description, $url) {

        $new_description = $description;
        $new_url = $this->formatURLHTML($url);
        
        if ($new_url) {
            $new_description .= "<p>" . $new_url . "</p>";
        }

        $returnHTML = "X-ALT-DESC;FMTTYPE=text/html:";
        $returnHTML .= $this->ical_util->encode_ical($new_description);
        $returnHTML .= CRLF;
        return $returnHTML;
    }

    /**
     * 
     * @param type $description
     * @param type $url
     * @return type
     */
    private function getDescription($description, $url) {
        $returnDescription = $this->getDescriptionText($description, $url);
        $returnDescription .= $this->getDescriptionHTML($description, $url);
        return $returnDescription;
    }

    /**
     * 
     */
    public function sendICS() {
        $this->sendHeaders();
        echo $this->readEventbrite();
    }

    /**
     * 
     * @param type $logText
     */
    private function writeLog($logText) {
        if (LOGGING) {
            fwrite($this->logFh, print_r($logText, true) . "\n");
        }
    }

}