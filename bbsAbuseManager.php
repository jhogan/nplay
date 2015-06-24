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
        BlockIfViolation();
        global $user;
        global $PHP_SELF;

        $disable = ($_GET['disable'] == '1') ? true : false;
        $disableThread = ($_GET['disableThread'] == '1') ? true : false;
        $ignore = ($_GET['ignore'] == '1') ? true : false;
        $postID = $_GET['postID'];

        $postCounts = array();
        $bbsRpts = new BBSReports('resolvedState=' . RESOLVED_STATE_UNRESOLVED);
        foreach($bbsRpts as $rpt){
            $postCounts[$rpt->PostID()]++;
        }

        arsort($postCounts, SORT_NUMERIC);

        $remove = array();
        if ($disableThread){
            $post = new Post($postID);
            $post->DisableRecursivly();
            foreach($bbsRpts as $rpt){
                if ($rpt->PostID() == $postID){
                    $rpt->ResolvedState(RESOLVED_STATE_DISABLED);
                    unset($postCounts[$postID]);
                }
            }
            UpdateObject($bbsRpts);
            UpdateObject($post);
        }
        if ($disable){
            $post = new Post($postID);
            $post->Disabled(true);
            foreach($bbsRpts as $rpt){
                if ($rpt->PostID() == $postID){
                    $rpt->ResolvedState(RESOLVED_STATE_DISABLED);
                    unset($postCounts[$postID]);
                }
            }
            UpdateObject($bbsRpts);
            UpdateObject($post);
        }
        if ($ignore){
            foreach($bbsRpts as $rpt){
                if ($rpt->PostID() == $postID){
                    $rpt->ResolvedState(RESOLVED_STATE_IGNORED);
                    unset($postCounts[$postID]);
                }
            }
            UpdateObject($bbsRpts);
        }
        if (count($postCounts) == 0){
           print(GetCap('capNoUnresolvedAbuses'));
        }else{
        ?>
            <table>
                <?
                foreach($postCounts as $postID=>$postCount){
                    $post = new Post($postID);
                    $username = $post->Username(); $text = $post->Text();
                    $parent =& $post->Parent(); $parID =& $parent->ID();
                    $mov =& $post->Movie(); $movTitle = $mov->Title();
                    $movID = $mov->ID(); $subject = $parent->Subject();

                    $rpts =& $bbsRpts->GetBy('postID', $postID);
                    $spamCnt = $rpts->SpamCount();
                    $abuseCnt = $rpts->AbuseCount();
                    ?>
                    <tr>
                        <td>
                            &nbsp;
                        </td>
                        <td>
                            <?
                            echo "<a href=\"$PHP_SELF?postID=$postID&amp;disable=1\">".GetCap('capDisable')."</a>&nbsp;|&nbsp;";
                            if ($post->IsTopic()){
                                echo "<a href=\"$PHP_SELF?postID=$postID&amp;disableThread=1\">".GetCap('capDisableThread')."</a>&nbsp;|&nbsp;";
                            }
                            echo "<a href=\"$PHP_SELF?postID=$postID&amp;ignore=1\">".GetCap('capIgnore')."</a>";
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <?=GetCap('capID')?>
                        </td>
                        <td>
                            <?=$postID?>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <?=GetCap('capMovie')?>
                        </td>
                        <td>
                            <a href="movie.php?id=<?=$movID?>"><?=$movTitle?></a>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <?=GetCap('capPost')?>
                        </td>
                        <td>
                            <a href="bbs.php?id=<?=$parID?>"><?=$subject?></a>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <?=GetCap('capText')?>
                        </td>
                        <td>
                            <?=$text?>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <?=GetCap('capSpam')?>
                        </td>
                        <td>
                            <?=$spamCnt?>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <?=GetCap('capAbuse')?>
                        </td>
                        <td>
                            <?=$abuseCnt?>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <?=GetCap('capTotal')?>
                        </td>
                        <td>
                            <?=$postCount?>
                        </td>
                    </tr>
                    <?
                    foreach($rpts as $rpt){
                        $comments = $rpt->Comments(); $type = $rpt->Type();
                        $rptUser =& $rpt->User();
                        $username = $rptUser->Username();
                        ?>
                        <tr>
                            <td>
                                <?
                                print("&nbsp;&nbsp;&nbsp;&nbsp;");
                                print("<a href=\"user.php?username=$username\">$username</a>");
                                ?>
                            </td>
                            <td>
                                <?
                                if ($type == REPORT_TYPE_SPAM){
                                    print (" [" . strtoupper(GetCap('capSpam')) . "]");
                                }elseif ($type == REPORT_TYPE_ABUSE){
                                    print (" [" . strtoupper(GetCap('capAbuse')) . "]");
                                }
                                if ($comments != "")
                                    print(" \"$comments\"");
                                ?>
                            </td>
                        </tr>
                        <?
                    }
                }
                ?>
            </table>
        <?
        }
    }
    catch(Exception $ex){
        ProcessException($ex);
    }
    require_once('tailer.php');
}
?>

