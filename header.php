<?php
// vim: set et ts=4 sw=4 fdm=marker:
/*
    Copyright (C) 2008 Jesse Hogan <jessehogan0@gmail.com>
    All rights reserverd
*/
/*TODO: Redirect to google doesn't seem to work */
# session_start();
try{

    ob_start();

    require_once("conf/style.php");
    require_once("conf/kvp.php");
    require_once("ExceptionLogs.php");
    require_once("GlobalSingleton.php");
    $gs =& GlobalSingleton::GetInstance();
    $gs->PictureDirectory($PIC_DIR);
    /* The above lines need to be run first before an exception can
        be properly handled */

    define('REPORT_TYPE_SPAM', 0);
    define('REPORT_TYPE_ABUSE', 1);

    define('RESOLVED_STATE_UNRESOLVED', 0);
    define('RESOLVED_STATE_DISABLED', 1);
    define('RESOLVED_STATE_IGNORED', 2);

    define('LOGIN_USER_NOT_FOUND', 0);
    define('LOGIN_AUTH', 1);
    define('LOGIN_ACCOUNT_DISABLED', 2);
    define('LOGIN_FAILED_AUTH', 3);

    if (get_magic_quotes_gpc){
        foreach ($_GET as $key=>$val){
            $_GET[$key] = stripslashes($val);
        }
        foreach ($_POST as $key=>$val){
            if (!is_array($_POST[$key]))
                $_POST[$key] = stripslashes($val);
        }
    }
    $post = (($_POST['blnHeaderPost']) == 1) ? true : false;
    $searchText = $_POST['txtSearch'];
    if ($searchText == ""){
        $searchText = $_GET['txtSearch'];
    }
    $postType = $_POST['txtPostType'];
    $RSS = (($_GET['RSS']) == 1) ? true : false;
    $logout = ($_GET['logout'] == 1) ? true : false;
    $returnURI = rawurlencode($_GET['returnURI']);
    $delete = (($_GET['delete']) == 1) ? true : false;
    $updateDB = (($_GET['updateDB']) == 1) ? true : false;
    $username = $_GET['username'];

    require_once("Business_Objects.php");
    $bom =& Business_Objects_Manager::getInstance();
    $bom->addDSN($DEF_DSN, "default");
    /* Manager dsn can't have a db */
    $bom->addDSN($MAN_DSN, "manager");
    if ($DEBUG && $updateDB){
        $bom->TableChangeType(BOM_CONSTRUCTIVE_AND_DESTUCTIVE_TABLE_CHANGE);
        $bom->ClassFiles('^[A-Z].*\.php$');
        $bom->UpdateDBStructures();
        echo "DB Changes:<br/>" . $bom->DBChangeLogString();
    }else{
        require_once("I18N.php");
        require_once("Captions.php");
        require_once("Users.php");
        require_once("Movies.php");
    }
    $locale = 1033;
    $pathArray = explode('/', $_SERVER['PHP_SELF']);
    $PHP_SELF = $_SERVER['PHP_SELF'];
    $thisURI = 'http://' . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];
    $thisURI = htmlentities($thisURI);

    /* rm logout flag out else a sequential logout->login will result in a logout */
    $thisURI = RemoveKVP($thisURI, 'logout=1');

    $thisURI = AppendKVP($thisURI, 'offset=1');
    $thisURIEncoded = rawurlencode($thisURI);
    $thisScript = $pathArray[count($pathArray)-1];

    $sid = $_COOKIE['sid'];
    //TODO:SEC: Consider regenerating session id
    if ($sid == ''){
        MakeNewSession();
    }else{
        /* Use composite index to quickly discover user if it's logged in */
        $session = new Session("loggedIn = 1 AND sessionID = '$sid'");
        /* If session isn't logged in search on sid */
        if ($session->IsEmpty()){
            $session =new Session("sessionID = '$sid'");
            if ($session->IsEmpty()){ /* This would happen if the session database changed */
                MakeNewSession();
            }
        }else{
            /* TODO:TEST: Need to test transporting cookies to another workstation
            to see if this works */
            if ($session->GetIPAddress() != $_SERVER['REMOTE_ADDR']){
                $user =& $session->User();
                $session->SessionIPChanged('User', $user->ID(), $session->GetIPAddress() . ' -> ' . $_SERVER['REMOTE_ADDR']);
                $user->Logout();
            }
        }
    }
    
    if ($post){
        switch ($postType){
            case 'LOGIN':
                $ret = Login($_POST['txtUsername'], $_POST['txtPassword']);
                switch ($ret){
                    case LOGIN_AUTH:
                        break;
                    case LOGIN_ACCOUNT_DISABLED:
                        $banner = GetCap('capAccountDisabled');
                        break;
                    case LOGIN_USER_NOT_FOUND:
                    case LOGIN_FAILED_AUTH:  
                        $banner = GetCap('capInvalidUsernameOrPassword');
                        break;
                }
        }
    }
    $editable = false;
    if (!$session->IsAnonymous()){
        $user =& $session->User();
        if ($session->LoggedIn()){
            if ($logout){
                $user->Logout();
                $session->LoggedOut("User", $user->ID());
                $user = null;
            }else{
                $editable = ($user->IsDataAdmin() || $user->IsAdmin());
            }
        }else{
            $user = null;
        }
    }
    $linkUSAllGooglePlaytimes = new Link();
    $linkUSAllGooglePlaytimes->LoadLinkID('US_ALL_GOOGLE_PLAYTIMES');
    if ($post){
        switch ($postType){
            case 'CHANGE_PLAYTIMES':
                $location = $_POST['txtLocation'];

                if (isset($user)){
                    $user->PlayTimesLocation($location);
                    UpdateObject($user, false);
                }else{
                    $session->PlayTimesLocation($location);
                    UpdateObject($session, false);
                }
                $session->ChangedPlayTimesLocation('Session', $session->ID(), $location);
                UpdateObject($session, false);
                $linkUSAllGooglePlaytimes = new Link();
                $linkUSAllGooglePlaytimes->LoadLinkID('US_ALL_GOOGLE_PLAYTIMES');
                $playTimesURL = $linkUSAllGooglePlaytimes->URLPlugged($location);
                header("Location: $playTimesURL");
                exit();
                break;
        }
    }

}catch(Exception $ex){
    ProcessException($ex);
}
function Dump($thenExit=true){
    global $pageTitle;
    global $TITLE_PREFIX;
    $out = ob_get_clean();
    $offset = strpos($out, "@TITLE");
    if ($pageTitle == ""){
        $pageTitle = $TITLE_PREFIX;
    }else{
        $pageTitle = "$TITLE_PREFIX :: $pageTitle";
    }
    $out = substr_replace($out, $pageTitle, $offset, 6);
    echo $out;
    if ($thenExit) exit();
}
function MakeNewSession(){
    global $session;
    $sid = md5(uniqid(rand(), true));
    $expire = mktime(12, 38, 20, 1, 12, 2010);
    setcookie('sid', $sid, $expire);
    $session = new Session();
    $session->SessionID($sid); 
    $session->SetIPAddress($_SERVER['REMOTE_ADDR']);
    $session->UserAgent($_SERVER['HTTP_USER_AGENT']);
    UpdateObject($session, false);
}
function BlockIfViolation($accessType='request'){
    global $thisScript; global $editable; global $session;
    global $ACL;
    if (!$editable){
        $session->Violation("$thisScript; $accessType");
        UpdateObject($session, false);
        ob_end_clean();
        header("Location: movies.php");
        exit();
    }else{
        $ips = explode(",", $ACL); 
        $curIPElements = explode('.', $_SERVER['REMOTE_ADDR']);
        foreach($ips as $ip){
            $ip = trim($ip);
            $ipElements = explode('.', $ip);
            for ($i=0; $i<4; $i++){
                $element = $ipElements[$i];
                $curElement = $curIPElements[$i];
                if ($i == 3){
                    if ($element == $curElement || $element == "*"){
                        return;
                    }
                }
                if ($element == '*') {
                    continue;
                }
                if ($element != $curElement) break;
            }
        }
        $session->ACLViolation("$thisScript; $accessType");
        UpdateObject($session, false);
        ob_end_clean();
        header("Location: movies.php");
        exit();
    }
}
function NoteViolation($accessType){
    global $thisScript; global $editable; global $session;
    if (!$editable){
        $session->Violation("$thisScript; $accessType");
        return true;
    }else{
        return false;
    }
}
function ThrowExceptionOnMaliciousInput($text, $for){
    if (preg_match('/<[ |    |\/]*script/i', $text) > 0){
        throw new Exception("Malicious input for $for: $text");
    }
}
function Login($username, $password){
    global $session;
    $user = new User("username = '$username'");
    if ($user->IsEmpty()){
        $user = null;
        return LOGIN_USER_NOT_FOUND;
    }
    if ($user->Password() == $password){
        if ($user->Enabled()){
            if ($user->PlayTimesLocation() == ""){
                $user->PlayTimesLocation($session->PlayTimesLocation());
            }
            if (UpdateObject($user)){
                $session->UserID($user->ID());
                $session->LoggedIn(true);
                UpdateObject($session);
                $session->Authenticated("User", $user->ID());
                UpdateObject($session, false);
                return LOGIN_AUTH;
            }
        }else{
            $session->AuthenticatedButAccountDisabled("Session", $session->ID());
            RETurn LOGIN_ACCOUNT_DISABLED;
        }
    }else{
        $session->FailedAuthentication("Session", $session->ID());
        return LOGIN_FAILED_AUTH;
    }

}

function GetDeleteURI($instanceID, $className, $PHPClassFile=''){
    global $gs;
    global $thisURIEncoded;
    $classID = $gs->ClassName2ID($className);
    $ret = AppendKVP($thisURI, 'delete=1');
    $ret = AppendKVP($ret, "returnURI=$thisURIEncoded");
    $ret = AppendKVP($ret, "instanceID=" . $instanceID);
    $ret = AppendKVP($ret, "classID=$classID");
    if ($PHPClassFile != ''){
        $ret = AppendKVP($ret, "file=$PHPClassFile");
    }
    return $ret;
}
function GetCap($symbol){
    global $locale;
    global $gs;
    return $gs->GetText($locale, $symbol);
}
function ProcessException($ex){
    global $DEBUG;
    global $RSS;
    global $sid;
    global $pageTitle;
    global $body;
    $log =& LogException($ex);
    $pageTitle = GetCap('capError');
    if ($DEBUG){
        print("<br>Exception:<br>".$log->ToString(1));
        Dump();
    }else{
        ob_end_clean();
        $id = $log->ID();
        ?>
        <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
        <html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en" dir="ltr">
            <head>
                <title><?=$pageTitle?></title>
            </head>
            <body <?=$body?>>
                <center>
                    <p>
                        <?=GetCap('capProblemEncountered')?><br/>
                        <?=GetCap('capItWasRecordedAndItWillBeFixed')?><br/>
                        <?=GetCap('capProblemID') . ': ' . $id?><br/>
                    </p>
                </center>
            </body>
        </html>
        <?
    }
    exit();
}
function LogException($ex){
    $log = new ExceptionLog($ex, 'exlog', $sid);
    $log->Save();
    return $log;
}
function UpdateObject(&$bo, $logEvent=true, $updateSession=false){
    global $session;
    $className = get_class($bo);
    if ($bo->IsValid()){
        $isBO = is_subclass_of($bo, "Business_Base");
        if ($isBO){
            $isNew = $bo->IsNew();
            $isMarkedForDeletion = $bo->IsMarkedForDeletion();
            $isDirty = $bo->IsDirty();
        }
        
        $bo->Update();

        if ($isBO){
            if ($logEvent){
                if ($isNew){
                    $session->Created($className, $bo->ID());
                }elseif ($isMarkedForDeletion){
                    $session->Deleted($className, $bo->ID());
                }elseif($isDirty){
                    $session->Updated($className, $bo->ID());
                }
            }
        }
        $ret = true;
    }else{
        $brs = $bo->BrokenRules();

        $rules = $brs->Rules();
        ?>
            <table>
                <tr>
                <td><u><font color="red"><?=strtoupper(GetCap("capUpdateFailed"))?></font></u></td>
                </tr>
        <?
        foreach($rules as $rule){
            $name = key($rule);
            $desc = current($rule);
            $brokenRules .= "|$name - $desc|"
            ?>
                <tr><td><font color="blue"><?='+'.$name . ' - ' . $desc?></font></td></tr>
            <?
        }
        ?>
            </table>
        <?
        $session->UpdateFailed($className, $bo->ID(), $brokenRules);
        $ret = false;
    }
    if ($updateSession){
        UpdateObject($session, false);
    }
    return $ret;
}
function ReturnURI(){
    global $returnURI;
    global $session;
    if ($returnURI != ""){
        UpdateObject($session, false);
        $returnURI = rawurldecode($returnURI);
        ob_end_clean();
        header("Location: $returnURI");
        exit();
    }
}
function RemoveKVP($url, $kvp){
    $tmp = str_replace("&amp;$kvp", '', $url);
    $tmp = str_replace("?$kvp", '?', $tmp);
    if (substr($tmp, strlen($tmp) - 1) == '?'){
        $tmp = substr($tmp, 0, strlen($tmp) -1);
    }
    return $tmp;
}
function AppendKVP($url, $kvp){
    $kvp = explode('=', $kvp);
    if (trim($kvp[1]) == '') return $url;
    if (($offset = strpos($url, '?')) === false){
        return $url . '?' . $kvp[0] . '=' . $kvp[1];
    }else{
        $url = explode('?', $url);
        $schemaAuthPath = $url[0];
        $qryStr = $url[1];
        $kvps = explode('&amp;', $qryStr);
        $beenHere=false;
        $keyExists=false;
        foreach($kvps as $kvp0){
            $kvp0 = explode('=', $kvp0);
            if (strcasecmp($kvp0[0], $kvp[0]) === 0){
                $val = $kvp[1];
                $keyExists=true;
            }else{
                $val = $kvp0[1];
            }
            if ($beenHere) $newQryStr .= '&amp;'; 
            else $beenHere=true;
            $newQryStr .= $kvp0[0] . '=' . $val;
        }
        if (!$keyExists){
            $newQryStr .= '&amp;' . $kvp[0] . '=' . $kvp[1];
        }
        return $schemaAuthPath . '?' . $newQryStr;
    }
}
function NewObject($classID, $instanceID, $PHPClassFile=''){
    global $gs;
    $className = $gs->ClassID2Name($classID);
    if ($PHPClassFile == '')
        $classFile = $className . ".php";
    else
        $classFile = $PHPClassFile . ".php";
    require_once($classFile);
    $obj = $gs->NewObject($classID, $instanceID);
    return $obj;
}
if (!$RSS){
    ?>
    <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
    <html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en" dir="ltr">
    <head> 
        <title>@TITLE</title>
    </head>
    <body <?=$body?>>
    <table align="right">
        <tr align="right">
            <td>
            <?
                if ($user != null) {
                    echo "<a href=\"user.php?username=".urlencode($user->Username())."\">".$user->Username()."</a>";
                        if ($user->IsDataAdmin()){
                            echo(' (' . GetCap('capDA') . ')');
                        }elseif ($user->IsAdmin()){
                            echo(' (<i>' . GetCap('capadmin') . '</i>)');
                        }
                    $tmp = AppendKVP($thisURI, 'logout=1');
                    echo " | <a href=\"$tmp\">" . GetCap('capLogout'). "</a>";
                    echo "<br />";
                    if ($editable){ 
                    ?>
                        <a href="person.php"><?=GetCap('capAddPerson')?></a> |
                        <a href="movie.php"><?=GetCap('capAddMovie')?></a> |
                        <a href="link.php?type=s"><?=GetCap('capAddLink')?></a> |
                        <a href="bbsAbuseManager.php"><?=GetCap('capManageSpam')?></a> |
                        <?
                        if ($user->IsAdmin()){
                            if (!$updateDB) $updateDBLink = AppendKVP($thisURI, 'updateDB=1');
                            ?>
                            <a href="<?=$updateDBLink?>"> <?=GetCap('capUpdateDB')?></a>
                        <?
                        }
                    }
                }elseif ($thisScript != 'login.php') {?>
                    <form name="frmLogin" method="post" action="<?=$thisURI?>">
                        <table>
                            <tr>
                                <td>
                                    <?=GetCap('capUsername') . ":"?>
                                </td>
                                <td>
                                    <input type="text" name="txtUsername" value="<?=$username?>"/>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <?=GetCap('capPassword') . ":"?>
                                </td>
                                <td>
                                    <input type="password" name="txtPassword"/>
                                </td>
                            </tr>
                            <tr>
                                <td align="right">
                                    &nbsp;
                                </td>
                                <td align="right">
                                    <input type="submit" name="btnLogin" value="<?=GetCap('capLogin')?>" />
                                </td>
                            </tr>
                            <tr>
                                <td>&nbsp;</td>
                                <td align="right">
                                    <a href="login.php?create=1&amp;returnURI=<?=$thisURIEncoded?>"><?=GetCap('capRegister')?></a>
                                </td>
                            </tr>
                            <tr>
                                <td>&nbsp;</td>
                                <td align="right">
                                    <a href="login?emailPassword=1&amp;returnURI=<?=$thisURIEncoded?>"><?=GetCap('capForgotPassword')?></a>
                                    <input type="hidden" name="blnHeaderPost" value="1" />
                                    <input type="hidden" name="txtPostType" value="LOGIN" />
                                </td>
                            </tr>
                        </table>
                    </form>
                <?
                }
                ?>
            </td>
        </tr>
        <tr>
            <td>&nbsp;</td>
        </tr>
        <tr align="right">
            <?
            if (isset($user)){
                $location = $user->PlayTimesLocation();
            }else{
                $location = $session->PlayTimesLocation();
            }
            $playTimesURL = htmlentities($linkUSAllGooglePlaytimes->URLPlugged($location));
            if ($location == "" || $_GET['changePlayTimesLocation'] == 1){
            ?>
                <td valign="top">
                    <?=GetCap('capEnterCityStateOrZip')?>:
                    <form name="frmLocation" method="post" action="<?=$PHP_SELF?>?id=<?=$id?>">
                        <input type="text" name="txtLocation" value="<?=$location?>" /> <br />
                        <input type="submit" name="btnLocation" value="<?=GetCap('capShowAllPlayTimes')?>"/>
                        <input type="hidden" name="blnHeaderPost" value="1"/>
                        <input type="hidden" name="txtPostType" value="CHANGE_PLAYTIMES"/>
                    </form>
                </td>
            <?}else{
                ?>
                <td>
                <?
                    print("<a href=\"$playTimesURL\">".GetCap('capShowPlayTimes')." ($location)</a>&nbsp;<br />");
                    print("<a href=\"$PHP_SELF?id=$id&amp;changePlayTimesLocation=1\">[".GetCap('capChange')."]</a>");
                ?>
                </td>
            <?}?>
        </tr>
        <tr>
            <td>&nbsp;</td>
        </tr>
        <tr align="right">
            <td>
                <form name="frmSearch" method="post" action="movies.php">
                    <?=GetCap('capSearch')?>:
                    <input type="hidden" name="blnHeaderPost" value="1" />
                    <input type="hidden" name="txtPostType" value="SEARCH" />
                    <input type="text" name="txtSearch" value="<?=$searchText?>" />
                </form>
            </td>
        </tr>
    </table>
    <h1> <a href="movies.php"><?=$SITE_NAME?></a> </h1>
    <? if ($banner != ""){
        echo "<center><u><b>$banner</b></u></center>";
    }
    ?>
<?
}
try{
    if ($delete){
        BlockIfViolation();
        $confirm = $_GET['confirm'];
        $returnURI = $_GET['returnURI'];
        $returnURI = rawurldecode($returnURI);
        $instanceID = $_GET['instanceID'];
        $classID = $_GET['classID'];
        $PHPClassFile = $_GET['file'];
        $obj =& NewObject($classID, $instanceID, $PHPClassFile);
        $className = $gs->ClassID2Name($classID);
        $id = $obj->ID();
        $toString = $obj->ToString($locale);
        $desc = "$toString";
        if ($confirm == "" ){
            print(GetCap('capConfirmDelete')."<br />");
            print("$className:<br>&nbsp;&nbsp;&nbsp;&nbsp;$desc");
            print("<br /><br />");
            $confirmURI = AppendKVP($thisURI, 'confirm=1');
            echo "<a href=$confirmURI>".GetCap('capYes')."</a>&nbsp;&nbsp;&nbsp;&nbsp;";
            echo "<a href=$returnURI>".GetCap('capNo')."</a>";
            Dump();
        }elseif($confirm==1){
            $obj->MarkForDeletion(); 
            UpdateObject($obj);
            UpdateObject($session, false);
            header("Location: $returnURI");
        }
    }
}catch(Exception $ex){
    ProcessException($ex);
}
?>
 


