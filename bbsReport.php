<?php
/*
    Copyright (C) 2008 Jesse Hogan <jessehogan0@gmail.com>
    All rights reserverd
*/
// vim: set et ts=4 sw=4 fdm=marker:
require_once("header.php");
require_once("BBSReports.php");

main();
function main(){
    try{
        global $user;
        global $PHP_SELF;

        $id = $_POST['id'];

        $postID = $_POST['txtPostID'];
        if ($postID == "") $postID = $_GET['postID'];

        $type = $_POST['cboType'];
        if ($type == "") $type = $_GET['type'];
        
        $post = new Post($postID);
        $bbsRpt = new BBSReport($id);
        $bbsRpt->Type($type);


        $username = $post->Username();
        $text = $post->Text();
        $parent =& $post->Parent();
        $parID =& $parent->ID();
        $mov =& $post->Movie();
        $movTitle = $mov->Title();
        $movID = $mov->ID();
        $subject = $parent->Subject();

        if ($_POST['blnPost'] != 1 && $post->Username() == $user->Username()){?>
            <table>
                <tr>
                    <td>
                        <?="<h3>".GetCap("capNote: YouAreReportingOnYourOwnPost")."</h3>"?>
                    </td>
                </tr>
                <tr>
                    <td>
                       <?=GetCap('capReturnToTopic').": "?> 
                       <?="<a href=\"bbs.php?id=$parID\">$subject</a>"?> 
                    </td>
                </tr>
            </table>
        <?
        }

        $bbsRpts = new BBSReports("postID = $postID and userID = " . $user->ID());
        if ($bbsRpts->Count() > 0){?>
            <table>
                <tr>
                    <td>
                        <?=GetCap("capYouHaveAlreadyReportedThisPost")?>
                    </td>
                </tr>
                <tr>
                    <td>
                       <?=GetCap('capReturnToTopic').": "?> 
                       <?="<a href=\"bbs.php?id=$parID\">$subject</a>"?> 
                    </td>
                </tr>
            </table>
            <?
            Dump();
        }
        if ($_POST['blnPost']){
            $bbsRpt->PostID($post->ID());
            $bbsRpt->UserID($user->ID());
            $bbsRpt->Comments($_POST['txtComment']);

            if (UpdateObject($bbsRpt, true, true)){
                ?>
                    <table>
                        <tr>
                            <td>
                                <?if ($bbsRpt->Type() == REPORT_TYPE_SPAM){
                                    print(GetCap('capThankYouForFightingSpam'));    
                                }elseif ($bbsRpt->Type() == REPORT_TYPE_ABUSE){
                                    print(GetCap('capThankYouForReportingAbuse'));    
                                }else{
                                    throw new Exception("Report Type: '" . $bbsRpt->Type() . "' not supported");
                                }?>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <?=GetCap("capYourReportWillBeReviewed. TheOffendingPostWillBeRemovedIfItViolatesOurPolicies")?>
                            </td>
                        </tr>
                        <tr>
                            <td>
                               <?=GetCap('capReturnToTopic').": "?> 
                               <?="<a href=\"bbs.php?id=$parID\">$subject</a>"?> 
                            </td>
                        </tr>
                    </table>
                <?
            }
        }else{
            $comments = $bbsRpt->Comments();
            $type = $bbsRpt->Type();
            $id = $bbsRpt->ID();
            ?>
            <form name="frm" method="post" action="<?=$PHP_SELF?>">
                <table>
                        <tr>
                            <td>
                                <?="<h3><a href=\"movie.php?id=$movID\">$movTitle</a></h3>"?>
                            </td>
                        </tr>
                        <tr>
                            <td>
                               <?=GetCap('capTopic').":"?> 
                            </td>
                            <td>
                               <?="<a href=\"bbs.php?id=$parID\">$subject</a>"?> 
                            </td>
                        </tr>
                        <tr>
                            <td>
                               <?=GetCap('capUser').":"?> 
                            </td>
                            <td>
                               <?="<a href=\"user.php?username=$username\">$username</a>"?> 
                            </td>
                        </tr>
                        <tr>
                            <td>
                               <?=GetCap('capPost').":"?> 
                            </td>
                            <td>
                               <?="<b>$text</b>"?> 
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <?=GetCap('capFlagAs').":"?>
                            </td>
                            <td>
                                <select name="cboType">
                                    <option value="<?=REPORT_TYPE_SPAM?>"<?=(($type == REPORT_TYPE_SPAM) ? ' selected="selected"': "")?>><?=GetCap('capSpam') ?></option>
                                    <option value="<?=REPORT_TYPE_ABUSE?>"<?=(($type == REPORT_TYPE_ABUSE) ? ' selected="selected"': "")?>><?=GetCap('capAbuse') ?></option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td>
                               <?=GetCap('capComment/Complaint')?> 
                            </td>
                            <td>
                               <textarea rows="5" cols="75" name="txtComment"><?=$comments?></textarea>
                            </td>
                        </tr>
                        <tr>
                            <td> &nbsp; </td>
                            <td>
                                <input type="submit" name="btnPost" value="<?=GetCap('capReport')?>"/>
                            </td>
                            <td>
                                <input type="hidden" name="blnPost" value="1"/>
                            </td>
                            <td>
                                <input type="hidden" name="txtPostID" value="<?=$postID?>"/>
                            </td>
                        </tr>
                </table>
            </form>
        <?
        }
    }
    catch(Exception $ex){
        ProcessException($ex);
    }
    require_once('tailer.php');
}
?>

