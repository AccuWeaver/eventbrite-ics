PHP code to read from Eventbrite and return an iCalendar of events.

Run the code on your web server. On initial startup you will get the configuration screen that will allow you to enter your Eventbrite API keys.

The format is iCalendar and has been tested with the PMI SFBAC calendar feed with output verified at http://severinghaus.org/projects/icv/http://severinghaus.org/projects/icv/)

A lot of the specifics for the spec were derived by exporting a Google Calendar and emulating the results.

Interpretation of the specification was mostly found at http://www.kanzaki.com/docs/ical/