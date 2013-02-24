<!DOCTYPE 
    html 
    PUBLIC "-//W3C//DTD XHTML 1.1//EN" 
    "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd"">
<!-- paulirish.com/2008/conditional-stylesheets-vs-css-hacks-answer-neither/ -->
<!--[if lt IE 7]> <html class="no-js lt-ie9 lt-ie8 lt-ie7" lang="en"> <![endif]-->
<!--[if IE 7]>    <html class="no-js lt-ie9 lt-ie8" lang="en"> <![endif]-->
<!--[if IE 8]>    <html class="no-js lt-ie9" lang="en"> <![endif]-->
<!-- Consider adding a manifest.appcache: h5bp.com/d/Offline -->
<!--[if gt IE 8]><!--> 
<html xmlns="http://www.w3.org/1999/xhtml"
      lang="en"
      > <!--<![endif]-->
    <head>
        <meta http-equiv="content-type" content="text/html; charset=utf-8" />
        <!-- DataTables CSS -->
        <link rel="stylesheet" type="text/css" href="http://ajax.aspnetcdn.com/ajax/jquery.dataTables/1.9.4/css/jquery.dataTables.css"></link>

        <link rel="stylesheet" type="text/css" href="http://code.jquery.com/ui/1.9.2/themes/sunny/jquery-ui.css"></link>


        <?php
        require_once('classes/Config.php');
        require_once('classes/EventbriteICS.php');


        $config = new Config();
        $params = $config->read();


        if (isset($_POST['app_key'])) {
            $params['app_key'] = $_POST['app_key'];
            $params['user_key'] = $_POST['user_key'];
            $params['output_file_name'] = $_POST['output_file_name'];
            $params['timezone'] = $_POST['timezone'];
            $params['before_period'] = $_POST['before_period'];
            $params['after_period'] = $_POST['after_period'];
            
            $config->setConfig($params);
            $config->write();
        }

        // $config->read();

        $app_key = $config->getParam('app_key');
        $user_key = $config->getParam('user_key');
        $output_file_name = $config->getParam('output_file_name');
        $before_period = $config->getParam('before_period');
        $after_period = $config->getParam('after_period');

        // Get the user data ...
        $eb_client = new Eventbrite(array('app_key' => $app_key,
                    'user_key' => $user_key));
        try {
            $user = $eb_client->user_get();
        } catch (Exception $ex) {
            // No events means the key is no good 
            // This is a validation that should be reported, but for now
            // we just clear the params ...
            $message = $ex->getMessage();
        }
        ?>
        <!-- Use the .htaccess and remove these lines to avoid edge case issues.
             More info: h5bp.com/i/378 -->
        <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1"></meta>

        <title>Eventbrite iCalendar Interface</title>
        <meta name="description" content=""></meta>

        <!-- Mobile viewport optimized: h5bp.com/viewport -->
        <meta name="viewport" content="width=device-width"></meta>

        <!-- Place favicon.ico and apple-touch-icon.png in the root directory: mathiasbynens.be/notes/touch-icons -->

        <link rel="stylesheet" href="css/style.css"></link>

    </head>
    <body>
        <!-- Prompt IE 6 users to install Chrome Frame. Remove this if you support IE 6.
             chromium.org/developers/how-tos/chrome-frame-getting-started -->
        <!--[if lt IE 7]><p class=chromeframe>Your browser is <em>ancient!</em> <a href="http://browsehappy.com/">Upgrade to a different browser</a> or <a href="http://www.google.com/chromeframe/?redirect=true">install Google Chrome Frame</a> to experience this site.</p><![endif]-->
        <header class="ui-helper-clearfix ui-widget-header ui-corner-all">
            <?php if (isset($user)) { ?>
                <h1>Current Eventbrite Configuration</h1>
            <?php } else { ?>
                <h1>Set up Eventbrite configuration parameters</h1>
            <?php } ?>
        </header>
        <div role="main">

            <form method="POST" class="ui-widget ui-state-active ui-corner-all ui-helper-clearfix" id="eventbrite-ics">

                <label>Eventbrite API key:</label>
                <input type="text" 
                       id="app_key" 
                       name="app_key" 
                       value="<?= $app_key ?>" 
                       placeholder="Enter your Eventbrite API key here" 
                       size="50" required="required" 
                       class="ui-widget-content" 
                       <?php if (isset($user)) { ?>disabled="true"<?php } ?>
                       />

                <label>User key:</label>
                <input type="text" id="user_key" 
                       name="user_key" 
                       value="<?= $user_key ?>" 
                       placeholder="Enter your Eventbrite user key here" 
                       size="50"
                       required="required" 
                       class="ui-widget-content"
                       <?php if (isset($user)) { ?>disabled="true"<?php } ?>
                       />
                <label>Output file name:</label>
                <input type="text" id="output_file_name" 
                       name="output_file_name" 
                       value="<?= $output_file_name; ?>" 
                       required="required" 
                       placeholder="eventbrite.ics" 
                       pattern="^[a-zA-Z0-9_\s-]+\.ics$" 
                       class="ui-widget-content"
                       />

                <label>Time zone:</label>
                <select name="timezone" >
                    <?php echo $config->displayTimeZoneSelect(); ?>
                </select>                
                <label>On query show this many days earlier:</label>
                <select name="before_period" >
                    <?php echo $config->displayTimeOptions($before_period); ?>
                </select>                
                <label>On query show this many days later:</label>
                <select name="after_period" >
                    <?php echo $config->displayTimeOptions($after_period); ?>
                </select>
                <br clear="both" />
                <div class="buttons">
                    <button type="submit" name="Submit" value="submit">Submit</button> <button type="button" name="Reset" value="reset" onclick="clear_me();">Reset</button>
                </div>
            </form>


            <?php
            if (isset($user)) {
                $user = $user->user;
                $i = false;
                ?>
            <div  class="ui-widget-header ui-corner-all">
                <p>
                    iCalendar URL: <a href="<?= $output_file_name ?>"><?= $output_file_name ?></a>
                </p>
                
            </div>
                <table id="users" class="display" cellpadding="0" cellspacing="0" border="0">
                    <thead>
                        <tr>
                            <th>User ID</th><th>Email</th><th>User Key</th><th>First Name</th><th>Last Name</th><th>Date Created</th><th>Date Modified</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="<?php echo ($i = !$i) ? 'odd' : 'even'; ?>">
                            <td><?= $user->user_id ?></td>             
                            <td><?= $user->email ?></td>
                            <td><?= $user->user_key ?></td>
                            <td><?= $user->first_name ?></td>
                            <td><?= $user->last_name ?></td>
                            <td><?= $user->date_created ?></td>
                            <td><?= $user->date_modified ?></td>
                        </tr>
                        <?php
                        foreach ($user->subusers as $subuser) {
                            $subuser = $subuser->subuser;
                            $i++
                            ?>
                            <tr class="<?php echo ($i = !$i) ? 'odd' : 'even'; ?>">
                                <td><?= $subuser->id ?></td>
                                <td><?= $subuser->email ?></td>
                            </tr>
                            <?php
                        }
                        ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <th>User ID</th><th>Email</th><th>User Key</th><th>First Name</th><th>Last Name</th><th>Date Created</th><th>Date Modified</th>
                        </tr>
                    </tfoot>
                </table>
                <?php
            } else {
                if (isset($message)) {
                    ?>
                    <table id="messages" class="error ui-corner-all ui-state-error" cellpadding="10" cellspacing="0" border="0">
                        <thead>
                            <tr>
                                <th><?= $message; ?></th>
                            </tr>
                        </thead>
                    </table>
                    <?php
                }
            }
            ?>

            <footer class="ui-helper-clearfix ui-widget-footer ui-corner-all">
                <p>Copyright &copy; AccuWeaver LLC - GPL License</p>
                <p><a href="http://code.google.com/p/eventbrite-ics/" alt="Google Code">EventbriteICS on Google</a></p>
            </footer>


            <!-- JavaScript at the bottom for fast page loading -->


            <!-- jQuery -->
            <script type="text/javascript" language="javascript" charset="utf8" src="//code.jquery.com/jquery.min.js"></script>
            <script type="text/javascript" language="javascript" charset="utf8" src="//code.jquery.com/ui/1.9.2/jquery-ui.min.js"></script>


            <!-- DataTables -->
            <script type="text/javascript" language="javascript" charset="utf8" src="http://ajax.aspnetcdn.com/ajax/jquery.dataTables/1.9.4/jquery.dataTables.min.js"></script>

            <script type="text/javascript" language="javascript">
                function clear_me(){
                    $(':input','#eventbrite-ics')
                    .not(':button, :submit, :reset, :hidden')
                    .val('')
                    .removeAttr('checked')
                    .removeAttr('selected');
                }
                $(document).ready(
                function() {
           
                    var oTable = $('#users').dataTable({
                        "bJQueryUI": true,
                        "bProcessing": true
                    }
                );    
     
                }
                
            );
            </script>

            <!-- end scripts -->

            <!-- Asynchronous Google Analytics snippet. Change UA-XXXXX-X to be your site's ID.
                 mathiasbynens.be/notes/async-analytics-snippet -->
            <script language="javascript" type="text/javascript">
                var _gaq=[['_setAccount','UA-XXXXX-X'],['_trackPageview']];
                (function(d,t){var g=d.createElement(t),s=d.getElementsByTagName(t)[0];
                    g.src=('https:'==location.protocol?'//ssl':'//www')+'.google-analytics.com/ga.js';
                    s.parentNode.insertBefore(g,s)}(document,'script'));
            </script>
    </body>
</html>