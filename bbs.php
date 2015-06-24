<?php
// vim: set et ts=4 sw=4 fdm=marker:
/*
    Copyright (C) 2008 Jesse Hogan <jessehogan0@gmail.com>
    All rights reserverd
*/
require_once("header.php");
require_once("Person.php");
require_once("Movies.php");

main();
function main(){
    try{
        global $editable;
        global $user;
        global $thisURIEncoded;
        global $session;
        global $bbsRow;
        $PHP_SELF = $_SERVER['PHP_SELF'];
        $post = $_POST['blnPost'];            
        $id = $_GET['id'];
        $topic = new Post($id);

        if (!$topic->IsNew())
            $session->Viewed("Post", $id);

        $enablePost = ($_GET['enablePost'] == '1') ? true : false;
        $disablePost = ($_GET['disablePost'] == '1') ? true : false;
        if ($enablePost || $disablePost){
            if (!NoteViolation("toggle $enablePost:$disablePost ($id)")){
                $postID = $_GET['postID'];
                $posts = $topic->Posts();
                foreach($posts as $post0){
                    if ($post0->ID() == $postID){
                        $post0->Disabled($disablePost);
                        UpdateObject($post0);
                        if ($disabled){
                            $session->Disabled('Post', $postID);
                        }else{
                            $session->Enabled('Post', $postID);
                        }
                        break;
                    }
                }
            }
        }
        if ($id == ""){
            $movID = $_GET['movID'];
            $mov = new Movie($movID);
            $topic->MovieID($movID);
        }else{
            $mov =& $topic->Movie();
            $movID = $mov->ID();
        }

        if ($mov->IsEmpty()){
            BlockIfViolation("non-exisisting movie (id=$movID)");
        }
        if (!$mov->Visible()){
            BlockIfViolation("invisable view (id=$movID)");
        }
	?>
	<a href="movie.php?id=<?=$mov->ID()?>"> <img src="<?=$mov->MainPicture()?>" alt=""/></a>
        <b><font size="6"><a href="movie.php?id=<?=$movID?>"><?=$mov->Title()?></a></font></b>
	<?
        if ($post){
            ThrowExceptionOnMaliciousInput($_POST['txtText'], 'BBS_TEXT');
            ThrowExceptionOnMaliciousInput($_POST['txtSubject'], 'BBS_SUB');
            $username = $user->Username();
            if ($topic->IsNew()){
                $topic->Text($_POST['txtText']);
                $topic->Subject($_POST['txtSubject']);
                $topic->Username($username); 
                $topic->ParentID(0); // Currently non-threaded, may change...
                $topic->MovieID($movID);
                $upd =& $topic;
            }else{
                $objPost = new Post();
                $objPost->Text($_POST['txtText']);
                $objPost->Username($username); 
                $objPost->ParentID($topic->ID()); // Currently non-threaded, may change...
                $objPost->MovieID($movID);
                $upd =& $objPost;
            }
            UpdateObject($upd);
            $id = $topic->ID();
        }
        if (!$topic->IsNew()){
            $posts =& $topic->Posts();
        }
        $subject = $topic->Subject();
        $username = $topic->Username();
        $text = $topic->Text();
        ?>
        <table>
            <? if (!$topic->IsNew()){ ?>
                <tr>
                    <td>
                        <h3><?=GetCap('capTopic') . ": $subject"?></h3>
                    </td>
                </tr>
            <?
            }
            if (isset($posts)){
                foreach($posts as $post){
                    $text = $post->Text();
                    $text = wordwrap(strip_tags($text), 75);
                    $text = str_replace("\n", '<br />', $text);
                    $date = $post->DatePosted();
                    $postID = $post->ID();
                    $username = $post->Username();
                    $pad = str_repeat('&nbsp;', 20 - strlen($username));
                    $disabled = $post->Disabled();
                    if ($disabled){
                        if ($user == null || ($username != $user->Username() && !$user->IsMaintainer())) continue;
                    }
                    ?>
                    <tr <?=$bbsRow?>>
                        <td>
                            <?="<b><a href=\"user.php?username=$username\">$username</a></b>" . '<br />'. $date . ""?>
                            &nbsp;<?="<a href=\"bbsReport.php?postID=$postID&amp;type=" . REPORT_TYPE_SPAM . "\">[" . GetCap('capReport') . "]</a>"?>
                        </td>
                    </tr>
                    <?
                    if ($editable){
                    ?>
                        <tr align="right">
                        <?
                            if ($post->Disabled()){
                                echo "<td><a href=\"bbs.php?id=$id&amp;postID=$postID&amp;enablePost=1\">".GetCap('capEnable')."</a></td>";
                            }else{
                                echo "<td><a href=\"bbs.php?id=$id&amp;postID=$postID&amp;disablePost=1\">".GetCap('capDisable')."</a></td>";
                            }
                        ?>
                        </td>
                        <?
                    }
                    ?>
                    <tr>
                        <td>
                            <?
                            if ($disabled){
                                if ($user->IsMaintainer()){
                                    echo "&nbsp;&nbsp;&nbsp;&nbsp;";
                                    echo "<b>" . strtoupper(GetCap('capDisabled')) . "</b><br />" ;
                                }else{
                                    echo "<b>".GetCap('capThisPostWasDisabledBecauseItViolatedOurPolicy')."</b>";
                                    echo "<br>&nbsp;&nbsp;&nbsp;&nbsp";
                                    echo "<b>".GetCap('capOnlyYouCanSeeThisPost')."</b>";
                                    echo "<br>&nbsp;&nbsp;&nbsp;&nbsp";
                                }
                            }
                            echo "<p>$text</p>";
                            ?>
                        </td>
                    </tr>
                    <?
                }
            }
            ?>
        </table>
        <? if ($user != null) { ?>
            <form name="frm" method="post" action="<?=$PHP_SELF . "?id=$id&amp;movID=$movID"?>">
                <table>
                    <? if ($topic->IsNew()) { ?>
                        <tr>
                            <td>
                                <?="<b>".GetCap('capSubject')."</b>"?>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <input type="text" name="txtSubject" value="<?=$subject?>"/>
                            </td>
                        </tr>
                    <? } ?>
                    <tr <?=$postMessageRow?>>
                        <td>
                            <?="<b>".GetCap('capPostMessage')."</b>"?>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <textarea rows="10" cols="57" name="txtText"></textarea>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <input type="submit" name="btnPost" value="<?=GetCap('capPost')?>"/>
                        </td>
                        <td>
                            <input type="hidden" name="blnPost" value="1"/>
                        </td>
                    </tr>
                </table>
            </form>
        <? }else{ ?>
            <table>
                <tr>
                    <td>
                        <?=GetCap('capYouMustBeLoggedInToPostA Message')?>
                    </td>
                </tr>
            </table>
        <? 
        }
    }
    catch(Exception $ex){
      ProcessException($ex);
    }
}
require_once('tailer.php');
?>

