 <?php
// vim: set et ts=4 sw=4 fdm=marker:
/*
    Copyright (C) 2008 Jesse Hogan <jessehogan0@gmail.com>
    All rights reserverd
*/
require_once('header.php');
require_once("Business_Objects.php");
require_once("Users.php");
require_once("Movies.php");
main();
function main(){
    try{
        global $pageTitle;
        global $editable;
        global $session;
        global $user;

        $post = $_POST['blnPost'];            
        $username = $_GET['username'];
        $selUser = new User("username = '$username'");
        $logout = $_GET['logout'];

        if ($selUser->IsEmpty()){
            print(GetCap('capUserNotFound'));
            $session->ViewedNotFound("Movie", $username);
            UpdateObject($session, false);
            Dump();
        }

        if ($logout){
            $selUser->Logout();
            $session->ForcedLogout($selUser->ID());
        }

        if (isset($user)){
            if ($user->ID() == $selUser->ID()){
                $sameUser = true;
            }
        }
        if ( !$editable && !$selUser->Enabled()){
            echo GetCap('capThisAccountHasBeenDisabled');
            Dump();
        }
            
        if ($post){
            if (!$editable && !$sameUser){
                BlockIfViolation('update');
            }
            ThrowExceptionOnMaliciousInput($_POST['txtInfo'], 'USER_INFO');
            if ($editable) $selUser->Enabled(($_POST['chkEnabled'] == '1') ? 1 : 0);
            $selUser->PlayTimesLocation($_POST['txtPlayTimesLocation']);
            $selUser->Email($_POST['txtEmail']);
            $selUser->Info(strip_tags($_POST['txtInfo']));
            UpdateObject($selUser);
        }else{
            $session->Viewed("User", $selUser->ID());
        }
        $username = $selUser->UserName();
        $pageTitle = $username;
        $email = $selUser->Email();
        $playTimesLocation = $selUser->PlayTimesLocation();
        $enabled = $selUser->Enabled();
        $info = $selUser->Info();
        ?>
        <br />
        <form name="frm" method="post" action="<?=$PHP_SELF . "?username=" . urlencode($username)?>">
            <table>
                <tr> 
                    <td>
                        <font size=6><b><?=$username?></b></font>
                        <?
                        if (! $sameUser){
                            echo PublicizedInfo($info);
                        }
                        ?>
                    </td>
                </tr> 
                    <?
                    if ($editable || $sameUser){
                    ?>
                        <tr> 
                            <td>
                                <b><?=GetCap('capEmailAddress')?>: </b>
                            </td>
                            <td>
                                <input type="text" size=50 name="txtEmail" value="<?=$email?>"/>
                                <?="<i>(".GetCap('capPrivate').")</i>"?>
                            </td>
                        </tr> 
                     <?
                     }
                if ($editable || $sameUser){
                ?>
                    <tr> 
                        <td>
                            <b><?=GetCap('capPlayTimesLocation')?>: </b>
                        </td>
                        <td>
                            <input type="text" size=50 name="txtPlayTimesLocation" value="<?=$playTimesLocation?>"/>
                            <?="<i>(".GetCap('capPrivate').")</i>"?>
                        </td>
                    </tr> 
                <?
                }
                if ($editable || $sameUser){
                ?>
                <tr> 
                        <td valign=top>
                            <b><?=GetCap('capTellUsAboutYourself')?>: </b>
                            <br/>
                            <i>(<?=GetCap('capEditAnywayYouWish')?>)</i>
                        </td>
                        <td valign=top>
                            <textarea rows="10" cols="57" name="txtInfo"><?=$info?></textarea>
                            <?="<i>(".GetCap('capPublic').")</i>"?>
                        </td>
                </tr> 
                <tr valign=top> 
                        <td>
                            <b><?=GetCap('capPublicView')?>: </b>
                        </td>
                        <td>
                            <?echo PublicizedInfo($info)?>
                        </td>
                </tr> 
                <?
                }
                if ($editable) { ?>
                    <tr> 
                        <td>
                            <b><?=GetCap('capLoggedIn')?>: </b>
                            <?
                                if ($selUser->LoggedIn()){
                                    print(GetCap("capYes"));
                                    if ($admin){
                                        print ("<a href=\"user.php?username=$username&logout=1\">[" . GetCap('capLogout') . "]</a>");
                                    }
                                }else{
                                    print(GetCap("capNo"));
                                }
                            ?>
                    </tr> 
                    <tr> 
                        <td>
                            <b><?=GetCap('capEnabled')?>: </b>
                            <?
                                print("<input type=\"checkbox\" name=\"chkEnabled\" value=\"1\" " . (($enabled) ? 'checked="checked"' : '') . "/>");
                            ?>
                    </tr> 
                <? } 
                if ($editable || $sameUser){
                ?>
                    <tr> 
                        <td>
                            <input type="submit" name="btnLogin" value="<?=GetCap('capSave')?>"/>
                        </td>
                        <td>
                            <input type="hidden" name="blnPost" value="1"/>
                        </td>
                    </tr> 
                <?
                }
                ?>
        </table>
    </form>
    <?
    }
    catch(Exception $ex){
      ProcessException($ex);
    }
}
function PublicizedInfo($info){
    return "<p>" . str_replace("\n", "<br/>", "$info") . '<p>';
}
require_once('tailer.php');
?>

