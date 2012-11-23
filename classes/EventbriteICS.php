<?php

require_once('Eventbrite.php');
require_once('iCalUtil.php');
require_once('Config.php');

define("CRLF", "\r\n");
define("LOGGING", false);

class EventbriteICS {

    private $config = array();
    private $eventbrite_client;
    private $logFh;
    private $outputFileName = "eventbrite.ics";
    

    function __construct() {
        // Make sure we're doing UTF-8 - important for iCalendar
        mb_internal_encoding("UTF-8");

        // Get the configuration object
        $this->config = new Config();
        $this->config->read();

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

    public function setConfig($config = array()) {
        $this->config = $config;
    }
    private function sendHeaders() {
        header('Content-type: text/calendar; charset=utf-8');
        header('Content-Disposition: attachment; filename="eventbrite.ics"');
    }

#
# Read Eventbrite and generate an ical calendar with the feed items as events.
#
#

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
            $eventbrite_events = $this->eventbrite_client->user_list_events();
        } catch (Exception $e) {
            $this->writeLog("Problem with Eventbrite: " . $e);
            $eventbrite_events = false;
        }

        $events = "BEGIN:VCALENDAR" . CRLF;
        $events .= "VERSION:2.0" . CRLF;
        $events .= "METHOD:PUBLISH" . CRLF;
        
        // FIXME - this should be an ID for the calendar - needs to
        //         be configurable.
        $events .= "PRODID:-//PMI SFBAC//PMICalendar//EN" . CRLF;

        // More elements from Google Calendar
        $events .= "CALSCALE:GREGORIAN" . CRLF;
        
        // FIXME - make the name of the calendar a config value
        $events .= "X-WR-CALNAME:PMI-SFBAC Eventbrite Calendar" . CRLF;
        $events .= "X-WR-TIMEZONE:America/Los_Angeles" . CRLF;
        $events .= "X-WR-CALDESC:This is the PMI-SFBAC Eventbrite Calendar" . CRLF;

        // Time zone definition (from Google Calendar) ...
        $events .= "BEGIN:VTIMEZONE" . CRLF;
        $events .= "TZID:America/Los_Angeles" . CRLF;
        $events .= "X-LIC-LOCATION:America/Los_Angeles" . CRLF;
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

        $pubdate = strtotime("Now");
        $today = date('Ymd', $pubdate);
        $begin_date = strtotime('-30 day', strtotime("Now"));
        $end_date = strtotime('+30 day', strtotime("Now"));

        $this->writeLog("begin date " . date('Y-M-d', $begin_date));
        $this->writeLog("end date " . date('Y-M-d', $end_date));

        $i = 0;
        if ($eventbrite_events) {
            #print_r($events->events);
            # Loop through the feed items and format an event for each
            foreach ($eventbrite_events->events as $event) {
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
                $start_date = strtotime($event->event->start_date, $today);

                //writeLog($begin_date . " < " . $start_date . " > " . $end_date);
                // Check if within next 30 days ...
                if (($begin_date > $start_date) || ($start_date > $end_date)) {
                    $this->writeLog(date('Y-M-d', $start_date) . " not in range " . $event->event->title);
                    continue;
                } else {
                    $this->writeLog(date('Y-M-d', $start_date) . " in range " . $event->event->title);
                }

                $start = date('Ymd\THis', strtotime($event->event->start_date));
                $end = date('Ymd\THis', strtotime($event->event->end_date));
                $created = date('Ymd\THis', strtotime($event->event->created));
                $modified = date('Ymd\THis', strtotime($event->event->modified));

                $events .= "BEGIN:" . "VEVENT" . CRLF;
                $events .= "ORGANIZER;CN=PMI San Francisco Bay Area Chapter:MAILTO:eventbrite@pmi-sfbac.org" . CRLF;
                $events .= "UID:" . $event->event->id . CRLF;
                $events .= "URL:" . $event->event->url . CRLF;
                $events .= "CATEGORIES:" . $event->event->category . CRLF;
                $events .= "CLASS:" . $event->event->privacy . CRLF;
                $events .= "CREATED;TZID=" . $event->event->timezone . ":" . $created . CRLF;
                $events .= "DTSTART;TZID=" . $event->event->timezone . ":" . $start . "Z" . CRLF;
                $events .= "DTEND;TZID=" . $event->event->timezone . ":" . $end . "Z" . CRLF;
                $events .= $this->ical_util->write_item("SUMMARY:", $event->event->title) . CRLF;
                $events .= $this->ical_util->write_item("DESCRIPTION:", $event->event->description) . CRLF;
                $events .= "X-ALT-DESC;FMTTYPE=text/html:" . $this->ical_util->encode_ical($event->event->description) . CRLF;
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
            $events .= "DTSTART;TZID=America/Los_Angeles:" . $today . "T120000" . CRLF;
            $events .= "DTEND;TZID=America/Los_Angeles:" . $today . "T163000" . CRLF;
            $events .= "SUMMARY:No rows found" . CRLF;
            $events .= "END:VEVENT" . CRLF;
        }

        if (LOGGING) {
            $this->logFh = fclose($this->logFh);
        }

        $events .= "END:VCALENDAR";
        return $events;
    }
    
    public function sendICS(){
        $this->sendHeaders();
        echo $this->readEventbrite();
    }

    private function writeLog($logText) {
        if (LOGGING) {
            fwrite($this->logFh, print_r($logText, true) . "\n");
        }
    }

}