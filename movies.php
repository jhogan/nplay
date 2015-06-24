<?php
// vim: set et ts=4 sw=4 fdm=marker:
/*
    Copyright (C) 2008 Jesse Hogan <jessehogan0@gmail.com>
    All rights reserverd
*/
require_once("header.php");
require_once("Movies.php");
require_once("lib/RSSDoc.php");

main();
function NavHTML(&$bos, $curOffset, $pageMax, $extraQryKVPs){
    $cnt = $bos->Count();
    if ($cnt <= $pageMax) return;
    $pageCnt = ceil($cnt / $pageMax);
    $offset=1;

    if ($curOffset > 1){
        $url = AppendKVP($thisURL, "offset=1");
        foreach($extraQryKVPs as $extraQryKVP) $url = AppendKVP($url, $extraQryKVP);
        $ret .= "<a href=\"$url\"><<</a>&nbsp;&nbsp;&nbsp;&nbsp";
        $url = AppendKVP($thisURL, "offset=" . ($curOffset - $pageMax));
        foreach($extraQryKVPs as $extraQryKVP) $url = AppendKVP($url, $extraQryKVP);
        $ret .= "<a href=\"$url\"><</a>&nbsp;&nbsp";
    }
    for($i=0; $i<$pageCnt; $i++){
        $txt = $i + 1;
        $url = AppendKVP($thisURL, "offset=$offset");
        if ($offset == $curOffset){
            $ret .= $txt . ' ';
        }else{
            foreach($extraQryKVPs as $extraQryKVP) $url = AppendKVP($url, $extraQryKVP);
            $ret .= "<a href=\"$url\">$txt</a>&nbsp;";
        }
        $offset = $offset + $pageMax;
    }
    $maxOffset = ($pageCnt * $pageMax) - ($pageMax-1);
    if ($curOffset < $maxOffset){
        $url = AppendKVP($thisURL, "offset=" . ($curOffset + $pageMax));
        foreach($extraQryKVPs as $extraQryKVP) $url = AppendKVP($url, $extraQryKVP);
        $ret .= "&nbsp;&nbsp;<a href=\"$url\">></a> ";
        $url = AppendKVP($thisURL, "offset=$maxOffset");
        foreach($extraQryKVPs as $extraQryKVP) $url = AppendKVP($url, $extraQryKVP);
        $ret .= "&nbsp;&nbsp;&nbsp;&nbsp;<a href=\"$url\">>></a> ";
    }
    
    return $ret;
}
function main(){
    try{
        global $locale;
        global $RSS;
        global $session;
        global $gs;
        global $searchTitle;
        global $thisURI;
        global $PAGE_MAX;
        global $curOffset;
        global $editable;
        $curOffset = trim($_GET['offset']);
        $curOffset = ($curOffset == "") ? 1 : $curOffset;
        $removes = array();
        $movs = new Movies();
        $catID = $_GET['catID'];
        $searchTitle = $_POST['txtSearch'];
        if ($searchTitle == ''){
            $searchTitle = $_GET['txtSearch'];
        }
        if ($searchTitle == ''){
            if ($editable){
                $movs->LoadWhere('nowPlaying=1');
            }else{
                $movs->LoadWhere('nowPlaying=1 AND visible=1');
            }
        }else{
            if ($editable){
                $movs->Search($searchTitle);
            }else{
                $movs->SearchWhereVisiable($searchTitle);
            }
            $session->Searched('Movies', $searchTitle);
        }
        $movs->Sort('Title');

        if ($catID != ''){
            foreach($movs as $mov){
                $cats = $mov->Categories();
                if (! $cats->Contains($catID)){
                    $removes[] = $mov;
                }
            }
            foreach ($removes as $remove){
                $movs->Remove($remove);
            }
        }
        if($RSS){
            RSS($movs);
        }else{
            $session->Viewed("Movies", 0, $gs->ActionMsg2ID("ViewedNowPlaying"));
        }

        $rssLink = AppendKVP($thisURI, "RSS=1");
        ?>
        <a type="application/rss+xml" href="<?=$rssLink?>">RSS</a>
        <br/>
        <?
        $i=$printedCnt=0;
        $extraQryKVPs = array();
        if ($catID != "") $extraQryKVPs[] = "catID=$catID";
        if ($searchTitle != "") $extraQryKVPs[] = "txtSearch=$searchTitle";
        $nav = NavHTML($movs, $curOffset, $PAGE_MAX, $extraQryKVPs);
        ?>
<table>
    <tr>
        <td align="center">
            <?=$nav?>
        </td>
    </tr>
    <tr>
        <td>
        <?
        foreach($movs as $mov){
	        if (++$i < $curOffset) continue; if (++$printedCnt > $PAGE_MAX) break;	
            $otherSiteLinks =& $mov->OtherSiteLinks();
            $reviewLinks =& $mov->ReviewLinks();
            $trailerLinks =& $mov->TrailerLinks();
            $stars =& $mov->Stars();
            $writers =& $mov->Writers();
            $directors =& $mov->Directors();
            $stars->Sort('Name'); $writers->Sort('Name'); $directors->Sort('Name');
            $movID =& $mov->ID();

            ?>
            <br/>
            <table bgcolor="#dddddd" frame="border" width="800">
                <tr valign="top"> 
                    <? if (file_exists($mov->MainPicture())) {?>
                        <td>
                            <a href="movie.php?id=<?=$mov->ID()?>"> <img src="<?=$mov->MainPicture()?>" alt=""/></a>
                        </td>
                    <?}?>
                        <td>
                            <table cellspacing="1" width="700">
                                <tr>
                                    <td colspan="2" bgcolor="#cccccc">
                                        <b><a href="movie.php?id=<?=$mov->ID()?>"> <?=$mov->Title()?></a></b> 
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <?  
                                            if ($stars->Count() > 1) print("<b>".GetCap('capStars') . ": </b>");
                                            else print("<b>".GetCap('capStar') . ": </b>");
                                        ?>
                                    </td>
                                    <td>
                                        <?
                                            foreach($stars as $per){
                                                $id = $per->ID(); $name = $per->Name(); 
                                                print("<a href=\"person.php?id=$id\">$name</a> ");
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
                                            foreach($directors as $per){
                                                $id = $per->ID(); $name = $per->Name(); 
                                                print("<a href=\"person.php?id=$id\">$name</a> ");
                                            }
                                        ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                    <?  
                                        if ($writers->Count() > 1) print("<b>".GetCap('capWriters') . ": </b>");
                                        else print("<b>".GetCap('capWriter') . ": </b>");
                                    ?>
                                    </td>
                                    <td>
                                    <?
                                        foreach($writers as $per){
                                            $id = $per->ID(); $name = $per->Name(); 
                                            print("<a href=\"person.php?id=$id\">$name</a> ");
                                        }
                                    ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td valign="top">
                                        <b><?=GetCap('capPlot')?>: </b>
                                    </td>
                                    <td>
                                        <?=wordwrap($mov->PlotOutline($locale), 75, '<br />')?> 
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <b><?=GetCap('capCategories')?>:</b>
                                    </td>
                                    <td>
                                        <?
                                            $cats =& $mov->Categories();
                                            $writers =& $mov->Categories();
                                            $directors =& $mov->Categories();
                                            foreach($cats as $cat){
                                                $catID0 = $cat->ID();
                                                $name = $cat->Name($locale);
                                                print("<a href=\"movies.php?catID=$catID0\">$name</a>&nbsp;");
                                            }
                                        ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <b><?=GetCap('capTrailers')?>: </b>
                                    </td>
                                    <td>
                                        <?
                                            foreach($trailerLinks as $link){
                                            ?>
                                                <a href="<?=$link->URL()?>"><?=GetCap($link->Source())?></a>
                                            <?
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
                                            <?
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
                                            <?
                                            }
                                        ?>
                                    </td>
                                </tr>
                            </table>
                        </td>
                </tr>
            </table>
             <?
        }
        ?>
        </td>
    </tr>
    <tr>
        <td align="center">
            <?=$nav?>
        </td>
    </tr>
</table>
    <?
    }
    catch(Exception $ex){
        ProcessException($ex);
    }
}
function RSS(&$movs){
   try{
        global $PHP_SELF;
        global $locale;
        global $session;
        global $gs;
        $session->Viewed("Movies", 0, $gs->ActionMsg2ID('ViewedNowPlayingRSS'));
        $appPath = preg_replace('/\/[^\/]+$/', '', $_SERVER['SCRIPT_NAME']);
        $movie_phpURL = 'http://' . $_SERVER['SERVER_NAME'] . "$appPath/movie.php";
        $movies_phpURL = 'http://' . $_SERVER['SERVER_NAME'] . "$appPath/movies.php";
        $rss = new RSS2Doc(GetCap('capNowPlaying'), 
                            $movies_phpURL, 
                            GetCap('capListOfMoviesNowPlaying'));
                  


        foreach($movs as $mov){
            $id = $mov->ID();
            $rss->AddItem($mov->Title(), "$movie_phpURL?id=$id",
                                $mov->Plot($locale));
        }
        ob_end_clean();
        header("Content-Type: text/xml");
        echo $rss->ToString();
        exit();
    }
    catch(Exception $ex){
        ob_end_clean();
        ProcessException($ex);
    }
}
require_once('tailer.php');

?>

