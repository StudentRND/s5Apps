<?php
$me = App::$s5->User->me();
try {
    SlackUtils::invite($me->first_name, $me->last_name, trim($me->email));
} catch (\Exception $ex) {}
echo "An invitation to join our Slack was sent to ".$me->email.". If this email was incorrect, please update it in s5.<br /><br />If you did not receive your invite, you will need to contact support@studentrnd.org, or have someone with Slack access ping @tylermenezes.";
