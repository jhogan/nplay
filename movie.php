<?php

// vim: set et ts=4 sw=4 fdm=marker:
/*
    Copyright (C) 2008 Jesse Hogan <jessehogan0@gmail.com>
    All rights reserverd
*/
require_once('header.php');
require_once("Business_Objects.php");
require_once("Movies.php");
require_once("I18N.php");
require_once("Captions.php");
require_once("Categories.php");
main();
function main(){
    try{
        global $pageTitle;
        global $locale;
        global $editable;
        global $session;
        global $user;
        global $gs;
        global $thisURI;
        global $thisURIEncoded;
        global $topicRowAlt1;
        global $topicRowAlt2;
        global $messaageBoardHeaderRow;
        $enableThread = ($_GET['enableThread'] == '1') ? true : false;
        $disableThread = ($_GET['disableThread'] == '1') ? true : false;
        $nowPlaying = ($_POST['chkNowPlaying'] == '1') ? true : false;
        $visible = ($_POST['chkVisible'] == '1') ? true : false;
        $id = $_GET['id'];
        if ($id == ""){
            $id = $_POST['id'];
        }
        $mov = new Movie($id);
        if ($mov->IsEmpty()){
            BlockIfViolation("non-exisisting movie (id=$id)");
        }
        if (!$mov->Visible()){
            BlockIfViolation("invisable view (id=$id)");
        }

        /*TODO:PERF:  This is very redundant. Keep in memory when resource
                        becomes available */
        $link = new Link();
        $link->LoadLinkID('US_GOOGLE_PLAYTIMES');
        if ($enableThread){
            if (!NoteViolation('enableThread')){
                $postID = $_GET['postID'];
                $post = new Post($postID);
                $post->EnableRecursivly();
                UpdateObject($post);
                $session->Enabled("Post", $postID);
            }
        }
        if ($disableThread){
            if (!NoteViolation('disableThread')){
                $postID = $_GET['postID'];
                $post = new Post($postID);
                $post->DisableRecursivly();
                UpdateObject($post);
                $session->Disabled("Post", $postID);
            }
        }
        if( ($_POST['btnLocation'] != '')){
            $location = $_POST['txtLocation'];

            if (isset($user)){
                $user->PlayTimesLocation($location);
                UpdateObject($user, false);
            }else{
                $session->PlayTimesLocation($location);
                UpdateObject($session, false);
            }
            $session->ChangedPlayTimesLocation('Movie', $mov->ID(), $location);
            UpdateObject($session, false);
            $playTimesURL = $link->URLPlugged($location, $mov->GoogleID());
            header("Location: $playTimesURL");
            exit();
        }
        $classID = $gs->ClassName2ID('Movie');
        
        if ( ! $editable && $id==""){
            throw new Exception(GetCap("capNoMovieID ParameterInURL"));
        }
        $PHP_SELF = $_SERVER['PHP_SELF'];
        if ($_POST['blnPost']){
            if ($_POST['btnSnarf'] != ''){
                BlockIfViolation('snarf');
                $url = $_POST['txtURL'];
                $mov->SnarfMainPicture($url);
                $session->Snarfed("Movie", $mov->ID(), $url);
            }else{
                BlockIfViolation('update');
                $mov->Title($_POST['txtTitle']);
                $mov->ReleaseDate($_POST['txtReleaseDate']);
                $mov->Plot($locale, $_POST['txtPlot']);
                $mov->PlotOutline($locale, $_POST['txtPlotOutline']);
                $mov->GoogleID($_POST['txtGoogleID']);
                $mov->NowPlaying($nowPlaying);
                $mov->Visible($visible);
                UpdateObject($mov);
            }
        }else{
           if ($id != ""){
               $session->Viewed('Movie', $id);
           }
        }
        $title = $mov->Title();
        $pageTitle = $title;
        $gid = $mov->GoogleID();
        $releaseDate = $mov->ReleaseDate();
        $plot = $mov->Plot($locale);
        $plotOutline = $mov->PlotOutline($locale);
        $stars =& $mov->MovieToPerson_Stars();
        $writers =& $mov->MovieToPerson_Writers();
        $directors =& $mov->MovieToPerson_Directors();
        $otherSiteLinks =& $mov->OtherSiteLinks();
        $reviewLinks =& $mov->ReviewLinks();
        $trailerLinks =& $mov->TrailerLinks();
        $topics =& $mov->Topics();
        $nowPlaying = $mov->NowPlaying();
        $visible = $mov->Visible();
        $id = $mov->ID();

        if (isset($user)){
            $location = $user->PlayTimesLocation();
        }else{
            $location = $session->PlayTimesLocation();
        }

        $playTimesURL = htmlentities($link->URLPlugged($location, $mov->GoogleID()));
        print("<br/>");
        if ($editable){
        ?>
            <? if (!$mov->IsNew()){?>
                <form name="frmSnarf" method="post" action="<?=$thisURI?>">
                    <b><?=GetCap('capURL')?>: </b>
                    <input type="text" size="52" name="txtURL" value="<?=$url?>"/>
                    <input type="submit" name="btnSnarf" value="<?=GetCap('capSnarfPictureURL')?>"/>
                    <input type="hidden" name="blnPost" value="1"/>
                </form>
            <?}?>
            <form name="frm" method="post" action="<?=$PHP_SELF . "?id=$id"?>">
                <table>
                    <tr>
                        <td>
                            <b><?=GetCap('capTitle')?>: </b>
                            <input type="text" name="txtTitle" value="<?=$title?>"/>
                            &nbsp;&nbsp;
                            <b><?=GetCap('capDate')?>: </b>
                            <input type="text" name="txtReleaseDate" value="<?=$releaseDate?>"/>
                            &nbsp;&nbsp;
                            <b><?=GetCap('capNowPlaying')?>: </b>
                            <input type="checkbox" name="chkNowPlaying" value="1" <?=($nowPlaying) ? 'checked="checked"' : '' ?>/>
                            &nbsp;&nbsp;
                            <b><?=GetCap('capVisible')?>: </b>
                            <input type="checkbox" name="chkVisible" value="1" <?=($visible) ? 'checked="checked"' : '' ?>/>
                        </td>
                    </tr>
                </table>

        <?
        }
        ?>
            <table bgcolor=#dddddd frame="border" width=800>
                <? if (!$editable){ ?>
                    <tr>
                        <td colspan="2">
                            <font size="12"><?=$title?></font>
                        </td>
                    </tr>
                <tr> 
                <?
                }
                ?>
                    <td valign="top">
                        <?if (file_exists($mov->MainPicture())) {?>
                            <img src="<?=$mov->MainPicture()?>" alt=""/>
                        <?}?>
                    </td>
                    <td valign="top">
                        <table>
                            <?
                            /* If the below form were printed while $editable==true
                               if would be nested in another form and cause lagic problems */
                            if (!$editable){ 
                            ?>
                                <tr>
                                    <td>
                                        <b><?=GetCap('capPlayTimes')?></b>:
                                    </td>
                                    <?
                                    if ($location == "" || $_GET['changePlayTimesLocation'] == 1){
                                    ?>
                                        <td valign="top">
                                            <b><?=GetCap('capEnterCityStateOrZip')?><br/></b>
                                                <form name="frmLocation0" method="post" action="<?=$PHP_SELF?>?id=<?=$id?>">
                                                    <input type="text" name="txtLocation" value="<?=$location?>" />
                                                    <input type="submit" name="btnLocation" value="<?=GetCap('capShowPlayTimes')?>"/>
                                                </form>
                                        </td>
                                    <?
                                    }else{
                                        print("<td><a href=\"$playTimesURL\">".GetCap('capShowPlayTimes')." ($location)</a>&nbsp;&nbsp;"); 
                                        print("<a href=\"$PHP_SELF?id=$id&amp;changePlayTimesLocation=1\">[".GetCap('capChangeLocation')."]</a></td>");
                                    }?>
                                </tr>
                            <?}?>
                            <tr>
                                <td>
                                    <b><?=GetCap('capCategories')?></b>:
                                </td>
                                <td>
                                    <?
                                        $m2cs =& $mov->MovieToCategories();
                                        foreach($m2cs as $m2c){
                                            $cat =& $m2c->Category();
                                            $catID = $cat->ID();
                                            $name = $cat->Name($locale);
                                            print("<a href=\"movies.php?catID=$catID\">$name</a> ");
                                        }
                                    if($editable && !$mov->IsNew()){
                                    ?>
                                        <sup><a href="<?='movieToCategories.php?movID='.$mov->ID()?>"><?='['.GetCap('capE').']'?></a></sup>
                                    <?
                                    }
                                    ?>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <?  if ($stars->Count() > 1) print("<b>".GetCap('capStars') . ": </b>");
                                        else print("<b>".GetCap('capStar') . ": </b>");
                                    ?>
                                </td>
                                <td>
                                    <?
                                        foreach($stars as $m2p){
                                            $m2pID = $m2p->ID();
                                            $per =& $m2p->Person();
                                            $perID = $per->ID(); $name = $per->Name(); 
                                            print("<a href=\"person.php?id=$perID&amp;movID=$id\">$name</a> ");
                                            if ($editable){
                                                print("<sup><a href=\"movieToPerson.php?id=$m2pID&amp;returnURI=$thisURIEncoded\">[".GetCap('capE')."]</a></sup>");
                                                $deleteURI = GetDeleteURI($m2pID, 'MovieToPerson', 'MoviesToPerson');
                                                print("<sup><a href=\"$deleteURI\">[" . GetCap('capD') ."]</a></sup>");
                                            }
                                        }
                                        if ($editable && !$mov->IsNew()){
                                            if (!$mov->IsNew()){
                                                print("&nbsp;<a href=\"movieToPerson.php?movID=$id&amp;relationship=s&amp;returnURI=$thisURIEncoded\">[".GetCap('capAdd')."]</a>");
                                            }
                                        }

                                    ?>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <?  
                                        if ($directors->Count() > 1) print("<b>".GetCap('capDirectors') . ": </b>");
                                        else print("<b>".GetCap('capDirector') . ": </b>");
                                    ?>
                                </td>
                                <td>
                                    <?
                                        foreach($directors as $m2p){
                                            $m2pID = $m2p->ID();
                                            $per =& $m2p->Person();
                                            $perID = $per->ID(); $name = $per->Name(); 
                                            print("<a href=\"person.php?id=$perID&amp;movID=$id\">$name</a> ");
                                            if ($editable){
                                                print("<sup><a href=\"movieToPerson.php?id=$m2pID&amp;returnURI=$thisURIEncoded\">[".GetCap('capE')."]</a></sup>");
                                                $deleteURI = GetDeleteURI($m2pID, 'MovieToPerson', 'MoviesToPerson');
                                                print("<sup><a href=\"$deleteURI\">[" . GetCap('capD') ."]</a></sup>");
                                            }
                                        }
                                        if ($editable && !$mov->IsNew()){
                                            print("&nbsp;<a href=\"movieToPerson.php?movID=$id&amp;relationship=d&amp;returnURI=$thisURIEncoded\">[".GetCap('capAdd')."]</a>");
                                        }
                                    ?>
                                </td>
                           </tr>
                           <tr>
                                <td>
                                <?  if ($writers->Count() > 1) print("<b>".GetCap('capWriters') . ": </b>");
                                    else print("<b>".GetCap('capWriter') . ": </b>");
                                ?>
                                </td>
                                <td>
                                    <?
                                        foreach($writers as $m2p){
                                            $m2pID = $m2p->ID();
                                            $per =& $m2p->Person();
                                            $perID = $per->ID(); $name = $per->Name(); 
                                            print("<a href=\"person.php?id=$perID&amp;movID=$id\">$name</a> ");
                                            if ($editable){
                                                print("<sup><a href=\"movieToPerson.php?id=$m2pID&amp;returnURI=$thisURIEncoded\">[".GetCap('capE')."]</a></sup>");
                                                $deleteURI = GetDeleteURI($m2pID, 'MovieToPerson', 'MoviesToPerson');
                                                print("<sup><a href=\"$deleteURI\">[" . GetCap('capD') ."]</a></sup>");
                                            }
                                        }
                                        if ($editable && !$mov->IsNew()){
                                            print("&nbsp;<a href=\"movieToPerson.php?movID=$id&amp;relationship=w&amp;returnURI=$thisURIEncoded\">[".GetCap('capAdd')."]</a>");
                                        }
                                    ?>
                                </td>
                            </tr>
                            <tr>
                                <td valign="top">
                                    <?
                                        $capPlot = GetCap('capPlot');
                                        print ("<b>$capPlot: </b>");
                                    ?>
                                </td>
                                <td>
                                    <?
                                        $plotWrapped = wordwrap($plotOutline, 75, '<br />');
                                        if ($editable){
                                            print("<textarea rows=\"5\" cols=\"75\" name=\"txtPlotOutline\">$plotOutline</textarea>");
                                        }else{
                                            echo $plotWrapped;
                                        }
                                        ?>
                                </td>
                            </tr>
                            <?
                            if ($editable){
                            ?>
                            <tr>
                                <td valign="top">
                                    <b><?=GetCap('capPreview')?>:</b><br />
                                    <?='<b>(' . strlen($plotOutline) . ')</b>'?>
                                </td>
                                <td>
                                    <?=$plotWrapped?>
                                </td>
                            </tr>
                            <?}?>
                            <tr>
                                <td>
                                    <b><?=GetCap('capTrailers')?>: </b>
                                </td>
                                <td>
                                    <?
                                        foreach($trailerLinks as $link){
                                        ?>
                                            <a href="<?=$link->URL()?>"><?=GetCap($link->Source())?></a>
                                            <? if ($editable){
                                                $deleteURI = GetDeleteURI($link->ID(), 'Link');
                                            ?>
                                                <sup><a href="<?="link.php?id=".$link->ID()."&amp;instanceID=$id&amp;classID=$classID&amp;returnURI=$thisURIEncoded"?>"> <?='['.GetCap('capE').']'?> </a></sup>
                                                <sup><a href="<?=$deleteURI?>"><?='['.GetCap('capD').']'?></a></sup>
                                            <?}?>
                                        <?
                                        }
                                        if ($editable && !$mov->IsNew()){
                                            print("&nbsp;<a href=\"link.php?type=t&amp;instanceID=$id&amp;classID=$classID&amp;returnURI=$thisURIEncoded\">[".GetCap('capAdd')."]</a>");
                                        }
                                    ?>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <b><?=GetCap('capReviews')?>: </b>
                                </td>
                                <td>
                                    <?
                                        foreach($reviewLinks as $link){
                                        ?>
                                            <a href="<?=$link->URL()?>"><?=GetCap($link->Source()).' ('.$link->Author().')'?></a>
                                            <? if ($editable){
                                                $deleteURI = GetDeleteURI($link->ID(), 'Link');
                                            ?>
                                                <sup><a href="<?="link.php?id=".$link->ID()."&amp;instanceID=$id&amp;classID=$classID&amp;returnURI=$thisURIEncoded"?>"> <?='['.GetCap('capE').']'?> </a></sup>
                                                <sup><a href="<?=$deleteURI?>"><?='['.GetCap('capD').']'?></a></sup>
                                            <?}?>
                                        <?
                                        }
                                        if ($editable && !$mov->IsNew()){
                                            print("&nbsp;<a href=\"link.php?type=r&amp;instanceID=$id&amp;classID=$classID&amp;returnURI=$thisURIEncoded\">[".GetCap('capAdd')."]</a>");
                                        }
                                    ?>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <b><?=GetCap('capOtherSites')?>: </b>
                                </td>
                                <td>
                                    <?
                                        foreach($otherSiteLinks as $link){
                                        ?>
                                            <a href="<?=$link->URL()?>"><?=GetCap($link->Source())?></a>
                                            <? if ($editable){
                                                $deleteURI = GetDeleteURI($link->ID(), 'Link');
                                            ?>
                                                <sup><a href="<?="link.php?id=".$link->ID()."&amp;instanceID=$id&amp;classID=$classID&amp;returnURI=$thisURIEncoded"?>"> <?='['.GetCap('capE').']'?> </a></sup>
                                                <sup><a href="<?=$deleteURI?>"><?='['.GetCap('capD').']'?></a></sup>
                                            <?}?>
                                        <?
                                        }
                                        if ($editable && !$mov->IsNew()){
                                            print("&nbsp;<a href=\"link.php?type=o&amp;instanceID=$id&amp;classID=$classID&amp;returnURI=$thisURIEncoded\">[".GetCap('capAdd')."]</a>");
                                        }
                                    ?>
                                    <input type="hidden" name="blnPost" value="1" />
                                </td>
                            </tr>
                            <? if ($editable){
                            ?>
                                <tr>
                                    <?echo '<td><b>'.GetCap('capGoogleID') . ":</b></td><td><input type=\"text\" name=\"txtGoogleID\" value=\"$gid\"/></td>";?>
                                </tr>
                            <?
                            }
                            ?>
                        </table>
                    </td>
                </tr>
                <? if ($editable){ ?>
                    <tr>
                        <td>&nbsp;</td>
                        <td> <input type="submit" name="btnSubmit" value="<?=GetCap('capSubmit')?>"/> </td>
                    </tr>
                <?}?>
            </table>
         <?
         if ($editable) echo "</form>";
         ?>

         <br />
         <form name="frmBB" method="post" action="<?=$PHP_SELF?>">
            <table>
                <tr>
                    <td>
                        <?='<b>'.GetCap('capMessageBoard').'</b>'?> &nbsp; &nbsp;
                        [<?="<a href=\"bbs.php?movID=$id\">".GetCap('capNewTopic')."</a>"?>]
                    </td>
                </tr>
            </table>
            <table border="1">
                <tr>
                    <td>
                        <table>
                            <tr>
                                <td>
                                    <b><?=GetCap('capTopics') . str_repeat('&nbsp;', 60)?></b>
                                </td>
                                <td>
                                    <b><?=GetCap('capUsers')?></b>
                                </td>
                            <?
                                if ($user != null && $user->IsMaintainer()){
                                    ?>
                                        <td>
                                            <b><?=GetCap('capBowdlerisation')?></b>
                                        </td>
                                    <?
                                }
                            ?>
                            </tr>
                        <?
                            $alt=false;
                            foreach($topics as $topic){
                                $alt = !$alt;
                                if ($alt) $rowAttr = $topicRowAlt1;
                                else $rowAttr = $topicRowAlt2;
                                $topID = $topic->ID();
                                $sub = $topic->Subject();
                                $username = $topic->Username();
                                $viewCount = $topic->ViewCount();
                                if (!$topic->RecursiveDisabled() || ($user != null && $user->IsMaintainer())){
                                ?>
                                    <tr <?=$rowAttr?>>
                                        <td>
                                            <?="<a href=\"bbs.php?id=$topID\">$sub</a>"?>
                                        </td>
                                        <td>
                                            <?="<a href=\"user.php?username=$username\">$username</a>"?>
                                        </td>
                                        <?
                                        if ($editable){
                                            if ($topic->RecursiveDisabled()){
                                                echo "<td><a href=\"movie.php?id=$id&amp;postID=$topID&amp;enableThread=1\">".GetCap('capEnable')."</a></td>";
                                            }else{
                                                echo "<td><a href=\"movie.php?id=$id&amp;postID=$topID&amp;disableThread=1\">".GetCap('capDisable')."</a></td>";
                                            }
                                        }
                                        ?>
                                    </tr>
                                <?
                                }
                            }
                        ?>
                        </table>
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

