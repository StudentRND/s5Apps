<?php

class GAppsUtils
{
    public static function ProcessPasswordUpdate($username, $password)
    {
        $gapps_user = App::gapps()->retrieveUser($username);
        if ($gapps_user !== null) {
            $gapps_user->login->password = $password;
            $gapps_user->save();
        }
    }

    public static function SuspendUser($username)
    {
        $gappsUser = App::gapps()->retrieveUser($username);
        if ($gappsUser !== null) {
            App::gapps()->suspendUser($username);
        }
    }

    public static function RenameUser($old, $new)
    {
        $gappsUser = App::gapps()->retrieveUser($old);
        if ($gappsUser !== null) {
            $gappsUser->login->userName = $new;
            $gappsUser->save();
        }
    }

    public static function EnsureUserSynced($user)
    {
        $gappsUser = App::gapps()->retrieveUser($user->username);
        $shouldUserExist = App::UserAllowed($user, App::$config->gapps->allowed_groups);

        // User does not exist and should
        if ($gappsUser === null && $shouldUserExist) {
            $gappsUser = App::gapps()->createUser(
                       $user->username,
                           $user->first_name,
                           $user->last_name,
                           hash('md5', time() . rand(0,10000) . $user->username . '!aoehrocehRhoer!')
            );
        }
        else if ($gappsUser->login->suspended && $shouldUserExist) {
            App::gapps()->restoreUser($user->username);
        }
        else if ($gappsUser !== null && !$shouldUserExist) {
            App::gapps()->suspendUser($user->username);
        }

        // Process profile info updates
        if ($shouldUserExist) {
            $hasUpdates = false;

            if ($gappsUser->login->agreedToTerms == false) {
                $gappsUser->login->agreedToTerms = true;
                $hasUpdates = true;
            }

            // Check if the user account should be marked as an admin
            if ($gappsUser->login->admin !== $user->is_admin) {
                $gappsUser->login->admin = $user->is_admin;
                $hasUpdates = true;
            }

            // Check if the user's name has changed
            if ($gappsUser->name->givenName !== $user->first_name) {
                $gappsUser->name->givenName = $user->first_name;
                $hasUpdates = true;
            }
            if ($gappsUser->name->familyName !== $user->last_name) {
                $gappsUser->name->familyName = $user->last_name;
                $hasUpdates = true;
            }


            if ($hasUpdates) {
                $gappsUser->save();
            }
        }
    }
}