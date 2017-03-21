<?php

$secret = $_REQUEST['secret'];
$event = $_REQUEST['event'];
$username = $_REQUEST['username'];

if ($secret !== App::$config->s5->secret) {
    exit;
}

switch ($event) {
    case 'User.Create':
    case 'User.Update':
    case 'User.Restore':
        GAppsUtils::EnsureUserSynced(App::$s5->User->get($username));
        break;

    case 'User.Delete':
        GAppsUtils::SuspendUser($username);
        break;

    case 'User.Rename':
        $old_username = $_REQUEST['old_username'];
        $new_username = $_REQUEST['new_username'];
        GAppsUtils::RenameUser($old_username, $new_username);

    case 'User.PasswordChange':
        $password = $_REQUEST['password'];
        GAppsUtils::ProcessPasswordUpdate($username, $password);
        break;
}