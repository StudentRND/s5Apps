<?php


class GAppsUtils
{
    public static function ProcessPasswordUpdate($username, $password)
    {
        $id = $username.'@'.App::$config->gapps->domain;
        $gappsUser = App::gapps()->users->get($id);
        $gappsUser->setPassword($password);
        App::gapps()->users->update($id, $gappsUser);
    }

    public static function SuspendUser($username)
    {
        $id = $username.'@'.App::$config->gapps->domain;
        $gappsUser = App::gapps()->users->get($id);
        $gappsUser->setSuspended(true);
        App::gapps()->users->update($id, $gappsUser);
    }

    public function UnsuspendUser($username)
    {
        $id = $username.'@'.App::$config->gapps->domain;
        $gappsUser = App::gapps()->users->get($id);
        $gappsUser->setSuspended(false);
        App::gapps()->users->update($id, $gappsUser);
    }

    public static function RenameUser($old, $new)
    {
        $id = $old.'@'.App::$config->gapps->domain;
        $gappsUser = App::gapps()->users->get($id);
        $gappsUser->setPrimaryEmail($new.'@'.App::$config->gapps->domain);
        App::gapps()->users->update($id, $gappsUser);
    }

    public static function EnsureUserSynced($user)
    {
        $id = $user->username.'@'.App::$config->gapps->domain;
        try {
            $gappsUser = App::gapps()->users->get($id);
        } catch (\Google_Service_Exception $ex) {
            $gappsUser = null;
        }

        $shouldUserExist = App::UserAllowed($user, App::$config->gapps->allowed_groups);

        $isNew = false;
        // User does not exist and should
        if ($gappsUser === null && $shouldUserExist) {
            $gappsUser = new \Google_Service_Directory_User;
            $gappsUser->setPrimaryEmail($id);
            $gappsUser->setPassword(md5(mt_rand(0,mt_getrandmax()).time().$user->username));
            $isNew = true;
        } else if ($gappsUser->suspended && $shouldUserExist) {
            self::UnsuspendUser($user->username);
        } else if ($gappsUser !== null && !$shouldUserExist && !$gappsUser->isAdmin) {
            self::SuspendUser($user->username);
        }

        // Process profile info updates
        if ($shouldUserExist) {
            $hasUpdates = false;

            if ($gappsUser->agreedToTerms == false) {
                $gappsUser->agreedToTerms = true;
                $hasUpdates = true;
            }

            // Check if the user's name has changed
            if ($gappsUser->getName() ||
                $gappsUser->getName()->givenName !== $user->first_name ||
                $gappsUser->getName()->familyName !== $user->last_name) {

                $name = new \Google_Service_Directory_UserName;
                $name->setGivenName($user->first_name);
                $name->setFamilyName($user->last_name);
                $gappsUser->setName($name);

                $hasUpdates = true;
            }

            if ($hasUpdates && !$isNew) {
                App::gapps()->users->update($id, $gappsUser);
            } elseif ($isNew) {
                App::gapps()->users->insert($gappsUser);
            }
        }
    }
}