<?php
$me = App::$s5->User->me();

if (!App::UserAllowed($me, App::$config->gapps->allowed_groups)) {
    echo "Sorry, you don't have permission to access Google Apps.";
    exit;
}

GAppsUtils::EnsureUserSynced($me);

if (array_key_exists('SAMLRequest', $_REQUEST)) {

    $request = new Saml\Request();
    $assertion = new Saml\Assertion();

    $assertion->Request = $request;
    $assertion->IssuedAt = time();
    $assertion->AuthenticatedAt = time() - 120;
    $assertion->AssertionValidAt = time() - 30;
    $assertion->AssertionExpiresAt = time() + 300;
    $assertion->SessionExpiresAt = time() + (3600*8);
    $assertion->Audience = 'google.com';
    $assertion->Issuer = App::$config->gapps->domain;
    $assertion->PublicKey = App::$config->gapps->saml_public;
    $assertion->PrivateKey = App::$config->gapps->saml_private;

    $assertion->Email = $me->username.'@'.App::$config->gapps->domain;

    $assertion->Respond();
} else {
    header('Location: https://mail.google.com/a/studentrnd.org');
}