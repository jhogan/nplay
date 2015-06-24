<?php
/*
    Copyright (C) 2008 Jesse Hogan <jessehogan0@gmail.com>
    All rights reserverd
*/
// vim: set et ts=4 sw=4 fdm=marker:
require_once('header.php');
require_once('CaptchasDotNet.php');
main();
function main(){
    try{
        global $sid;
        global $session;
        global $returnURI;
        global $DOMAIN;
        global $FROM_ADDR;
        global $X_MAILER;
        $PHP_SELF = $_SERVER['PHP_SELF'];
        $message = $_REQUEST['message'];
        $captchaCode = $_REQUEST['txtCaptchaCode'];
        $random = $_REQUEST['txtRandom'];
        $post = $_POST['blnPost'];            
        $username = $_POST['txtUsername'];            
        $password = $_POST['txtPassword'];            
        $repassword = $_POST['txtRePassword'];            
        $email = $_POST['txtEmail'];            
        $emailPassword = ($_POST['btnEmailPassword'] != "");
        $create = ($_POST['btnCreateAccount'] != "");

        if (! $create)          $create = ($_GET['create'] != "");
        if (! $emailPassword)   $emailPassword = ($_GET['emailPassword'] != "");

        if ($post){
            if ($emailPassword){
                $user = new User("username='$username'");
                if ($user->Email() != ''){
                    $capLogin = GetCap('capLogin');
                    $sub = GetCap('capPassword');
                    $path = dirname($_SERVER['SCRIPT_NAME']);
                    $msg = GetCap('capBelowAreYourCredentials')."\r\n";
                    $msg .= "Username: ".$user->Username()."\r\n";
                    $msg .= 'Password: '.$user->Password()."\r\n";
                    $msg .= "$capLogin: http://$DOMAIN$path/" . "movies.php?username=".urlencode($user->Username());
                    $user->EmailPassword($msg, $sub, $FROM_ADDR, $X_MAILER);
                    $session->EmailedPassword("User", $user->ID());
                    print ("<b><center>".GetCap('capEmailHasBeenSent').'</center></b>');
                }else{
                    print ("<b><center>".GetCap('capUserAccountDoesn\'tHaveEmailAddress</center></b>'));
                }
                $username = $user->UserName();
                $email = $user->Email();
            }elseif($create){
                $captcha = new CaptchasDotNet('demo', 'secret');
                if (!$captcha->validate($random)){
                    $password = $_POST['txtPassword']; $rePassword = $_POST['txtRePassword'];
                    print("<center>".GetCap('capCaptchaWasReused')."</center>");
                    $session->ReusedCaptcha("session", $session->ID(), $random);
                }elseif(!$captcha->verify ($captchaCode)){
                    $password = $_POST['txtPassword']; $rePassword = $_POST['txtRePassword'];
                    print("<center>".GetCap('capInvalidConfirmationCode')."</center>");
                    $session->InvalidConfirmationCode($captchCode);
                }elseif($password == $repassword){
                    $user = new User();
                    $user->UserName($username);
                    $user->Password($password);
                    $user->Email($email);
                    if (UpdateObject($user)){
                        $session->UserID($user->ID());
                        $session->LoggedIn(true);
                        UpdateObject($session);
                        $username = $user->UserName();
                        $email = $user->Email();
                        ReturnURI();
                        print('<center>' . GetCap("capUpdateSucceded") . '</center>');
                    }
                }else{
                    $password = $_POST['txtPassword']; $rePassword = $_POST['txtRePassword'];
                    print("<center>".GetCap('capPasswordsDoNotMatch')."</center>");
                }
            }
        }
        /* TODO:PREROLL Go to captcha.net and register an actual account (not demo) before rolling
            to production */
        if ($create) $captcha = new CaptchasDotNet('demo', 'secret');
        ?>
        <form name="frm" method="post" action="<?="login.php?returnURI=$returnURI"?>">
            <table>
                <tr> 
                    <td>
                        <b><?=GetCap('capUsername')?>: </b>
                    </td>
                    <td>
                        <input type="text" name="txtUsername" value="<?=$username?>"/>
                    </td>
                </tr> 
                <?
                if ($create){
                ?>
                    <tr> 
                        <td>
                            <b><?=GetCap('capPassword')?>: </b>
                        </td>
                        <td>
                            <input type="password" name="txtPassword" value="<?=$password?>"/>
                        </td>
                    </tr> 
                    <tr> 
                        <td>
                            <b><?=GetCap('capRe-typePassword')?>: </b>
                        </td>
                        <td>
                            <input type="password" name="txtRePassword" value="<?=$_POST['txtRePassword']?>"/>
                        </td>
                    </tr> 
                    <tr> 
                        <td>
                            <b><?=GetCap('capEmail (optional)')?>: </b>
                        </td>
                        <td>
                            <input type="text" name="txtEmail" value="<?=$email?>"/>
                        </td>
                        <td>
                            <i><?=GetCap('capIn case you forget your password we can email you a new one.')?></i>
                        </td>
                    </tr> 
                    <tr valign="top">
                        <td>
                            <b><?=GetCap('capEnterConfirmationCodeFromPicture')?>: </b>
                        </td>
                        <td>
                            <input type="text" name="txtCaptchaCode"/>
                            <input type="hidden" name="txtRandom" value="<?= $captcha->random () ?>" />
                            <br /><br/><br/> 
                            <a href="<?=$captcha->audio_url()?>"><?=GetCap('capPhoenieticSpelling(mp3)')?></a>
                        </td>
                        <td>
                            <?
                                echo $captcha->Image(false, 'captchas.net', GetCap('capLoadingCaptcha...'));
                            ?>
                        </td>
                    </tr> 
                    <tr> 
                        <td>
                            <input type="submit" name="btnCreateAccount" value="<?=GetCap('capCreateAccount')?>"/>
                        </td>
                    </tr> 
                <?
                }
                if ($emailPassword){
                ?>
                    <tr>
                        <td>&nbsp;</td>
                        <td>
                            <input type="submit" name="btnEmailPassword" value="<?=GetCap('capEmailMePassword')?>"/>
                        </td>
                    </tr>
                <?
                }
                ?>
                <tr>
                    <td>
                        <input type="hidden" name="blnPost" value="1"/>
                    </td>
                </tr> 
            </table>
         </form>
        <?
    }
    catch(Exception $ex){
        ProcessException($ex);
    }
}
require_once('tailer.php');
?>

