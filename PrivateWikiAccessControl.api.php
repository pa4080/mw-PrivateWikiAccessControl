<?php
/**
 * @author    Spas Z. Spasov <spas.z.spasov@gmail.com>
 * @copyright 2019 Spas Z. Spasov
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU General Public License, version 3 (or later)
 * @home      https://github.com/pa4080/mw-PrivateWikiAccessControl
 *
 * This file is a part of the MediaWiki Extension:PrivateWikiAccessControl.
 * 
 * This API is not loaded by MediaWiki as part of the extension. 
 * Instead of that it is called by rewrite rules within Apache's VH configuration.
 * The API has two parts. The Part 1 will display the images (files) which articles belongs to MediaWiki:InternalWhitelist.
 * The Part 2 will Whitelist the API queries that are partially mentioned in MediaWiki:InternalWhitelistAPI.
 * 
 * PrivateWikiAccessControl project is free software: you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 *
 * PrivateWikiAccessControl project is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
 * without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 * 
 * Example request:   https://wiki.org//api.php?action=query&titles=File:Image_name.png&prop=imageinfo&iiprop=size&format=json
 * Example end point: $endPoint = "https://wiki.org/api.php";
 *
**/

// Hide this API when the query is empty
if (empty($_GET)) {
    header("Location: /");
    return true;
}

// Get the Configuration Settings
$wgPWAC = unserialize(file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/cache/PWAC_Conf.txt'));


/**
 * PART 1: Image Whitelist Option
**/
if ($_GET['imgIWL']) {

    //global $wgPWAC;

    $imgIWL_Img  = $_GET['imgIWL'];
        $imgIWL_File = $wgPWAC['IP'] . $imgIWL_Img;
        $imgIWL_Name = end(explode('/', $imgIWL_Img));
        $imgIWL_Ext  = end(explode('.', $imgIWL_Img));
        $imgIWL_Type = $imgIWL_ContentTypes["$imgIWL_Ext"];

    $wgWhitelistRead = unserialize(file_get_contents($wgPWAC['WhitelistPagesFile']));

    if (preg_grep("/$imgIWL_Name/", $wgWhitelistRead)) {

        $imgIWL_ContentTypes = [
            'ogg' => 'application/ogg', 'pdf' => 'application/pdf', 'zip' => 'application/zip',
            'mpeg' => 'audio/mpeg', 'wav' => 'audio/x-wav', 'gif' => 'image/gif', 'jpeg' => 'image/jpeg',
            'jpg' => 'image/jpeg', 'png' => 'image/png', 'tiff' => 'image/tiff', 'djvu' => 'image/vnd.djvu',
            'djv' => 'image/vnd.djvu', 'svg' => 'image/svg+xml', 'css' => 'text/css', 'cvs' => 'text/csv',
            'html' => 'text/html', 'txt' => 'text/plain', 'xml' => 'text/xml', 'mpg' => 'video/mpeg',
            'mpeg' => 'video/mpeg', 'mp4' => 'video/mp4', 'mkv' => 'video/mkv', 'avi' => 'video/avi',
            'qt' => 'video/quicktime', 'wmv' => 'video/x-ms-wmv', 'webm' => 'video/webm',
        ];

        header('Content-type: ' . $imgIWL_Type);
        readfile($imgIWL_File);
    }
    return true;
}


/**
 * PART 2: API Requests Whitelist Option - Circumstances
**/
// Write queries log, clear the log when the file is bigger than 100K
if ($wgPWAC['WhitelistApiLog'] && $wgPWAC['WhitelistApiLog'] != 'disable') {

    //global $wgPWAC;

    $logLine = $_SERVER['REMOTE_ADDR'] . ': ' . urldecode(http_build_query($_GET)) . PHP_EOL;
    if (filesize($wgPWAC['WhitelistApiLog']) > 100000) {
        file_put_contents($wgPWAC['WhitelistApiLog'], $logLine, LOCK_EX);
    } else {
        file_put_contents($wgPWAC['WhitelistApiLog'], $logLine, FILE_APPEND | LOCK_EX);
    }
}

// Interrupt when the authentication data is not provided
if ($wgPWAC['WhitelistApiUser'] === false || $wgPWAC['WhitelistApiPass'] === false) {
    return true;
}

// Test whether the current API query is whitelisted
$PWAC_WhitelistReadApiFromFile = unserialize(file_get_contents($wgPWAC['WhitelistApiFile']));

foreach ($PWAC_WhitelistReadApiFromFile as $element) {
    if (strpos(http_build_query($_GET), $element) !== false || $element == $wgPWAC['WhitelistAllApi']) {
        $PWAC_ApiQueryTest = 'pass';
    }
}

if ($PWAC_ApiQueryTest != 'pass') {
    return true;
}


/**
 * PART 2: API Requests Whitelist Option - Action Part
**/
$wgPWAC['MediaWikiApiLoginToken'] = Get_mwApiLoginToken($wgPWAC); // Step 1
//echo $wgPWAC['MediaWikiApiLoginToken'];
Do_theLoginRequest($wgPWAC);      // Step 2
Do_theApiCall($wgPWAC, $_GET);    // Step 3

/**
 * Step 0: GET the API end point of the current server :: Deprecated

   $wgPWAC['MediaWikiApiEndPoint']   = Get_mwApiEndPoint($wgPWAC);   // Step 0

function Get_mwApiEndPoint(array $wgPWAC) {
    $protocol = (!empty($_SERVER['HTTPS']) && (strtolower($_SERVER['HTTPS']) == 'on' || $_SERVER['HTTPS'] == '1')) ? 'https://' : 'http://';
    $server = $_SERVER['SERVER_NAME'];
    $port = $_SERVER['SERVER_PORT'] ? ':'.$_SERVER['SERVER_PORT'] : '';

    return $protocol.$server.$port.$wgPWAC['MediaWikiApiURI'];
}
**/

/**
 *Step 1: GET Request to fetch login token--
 * !!! Currently this step uses the deprecated authentication method (action=login) !!!
 * !!! This should be fixed in the further versions !!!
 * !!! https://www.mediawiki.org/wiki/Topic:Vdrava0likcnvy6a !!!
**/
function Get_mwApiLoginToken( array $wgPWAC )
{
    $params = [
        "action"     => "login",
        "format"     => "json"
    ];

    $url = $wgPWAC['MediaWikiApiEndPoint'] . "?" . http_build_query($params);

    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $wgPWAC['MediaWikiApiEndPoint']);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $wgPWAC['WhitelistApiCookie']);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $wgPWAC['WhitelistApiCookie']);

    $output = curl_exec($ch);

    curl_close($ch);

    $result = json_decode($output, true);

    return $result["login"]["token"];
}

/**
 * Step 1: GET Request to fetch login token
 *
function Get_mwApiLoginToken( array $wgPWAC )
{
    $params1 = [
        "action" => "query",
        "meta"   => "tokens",
        "type"   => "login",
        "format" => "json"
    ];
    $url = $wgPWAC['MediaWikiApiEndPoint'] . "?" . http_build_query($params1);
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $wgPWAC['WhitelistApiCookie']);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $wgPWAC['WhitelistApiCookie']);
    $output = curl_exec($ch);
    curl_close($ch);
    $result = json_decode($output, true);
    return $result["query"]["tokens"]["logintoken"];
}
**/

/**
 * Step 2: POST Request to log in. Obtain credentials via Special:BotPasswords
 * (https://www.mediawiki.org/wiki/Special:BotPasswords) for lgname & lgpassword
 * !!! Currently this step uses the deprecated authentication method (action=login) !!!
 * !!! This should be fixed in the further versions !!!
**/
function Do_theLoginRequest( array $wgPWAC )
{
    $params = [
        "action"     => "login",
        "lgname"     => $wgPWAC['WhitelistApiUser'],
        "lgpassword" => $wgPWAC['WhitelistApiPass'],
        "lgtoken"    => $wgPWAC['MediaWikiApiLoginToken'],
        "format"     => "json"
    ];

    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $wgPWAC['MediaWikiApiEndPoint']);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $wgPWAC['WhitelistApiCookie']);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $wgPWAC['WhitelistApiCookie']);

    $output = curl_exec($ch);  // Comment out this line ...
    curl_close($ch);

    return $output;              // .. and this line to get the message about the deprecated method.
}

/**
 * Step 3: GET Request for a image info
**/
function Do_theApiCall( array $wgPWAC, $mwApiQuery_GET )
{
    $url = $wgPWAC['MediaWikiApiEndPoint'] . '?' . http_build_query($mwApiQuery_GET);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $wgPWAC['WhitelistApiCookie']);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $wgPWAC['WhitelistApiCookie']);

    $output = curl_exec($ch);
    curl_close($ch);

    echo $output;
}
?>
