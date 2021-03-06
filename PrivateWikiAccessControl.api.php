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

// Get the Configuration Settings, https://www.php.net/manual/en/language.constants.predefined.php
// Define custom algorithm for cache dir determination
if ( file_exists(__DIR__ . '/PrivateWikiAccessControl.api.conf.php') ) {
    require_once(__DIR__ . '/PrivateWikiAccessControl.api.conf.php');
} else {
    $wgPWAC = unserialize(file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/cache/PWAC_Conf.txt'));
}

/**
 * PART 1: Image Whitelist Option
**/
if (isset($_GET['imgIWL'])) {

    //global $wgPWAC;

    // https://developer.mozilla.org/en-US/docs/Web/HTTP/Basics_of_HTTP/MIME_types/Common_types
    $imgIWL_ContentTypes = [
        '3g2' => 'video/3gpp2',
        '3gp' => 'video/3gpp',
        '7z' => 'application/x-7z-compressed',
        'aac' => 'audio/aac',
        'abw' => 'application/x-abiword',
        'arc' => 'application/x-freearc',
        'avi' => 'video/avi',
        'avi' => 'video/x-msvideo',
        'azw' => 'application/vnd.amazon.ebook',
        'bin' => 'application/octet-stream',
        'bmp' => 'image/bmp',
        'bz' => 'application/x-bzip',
        'bz2' => 'application/x-bzip2',
        'csh' => 'application/x-csh',
        'css' => 'text/css',
        'csv' => 'text/csv',
        'cvs' => 'text/csv',
        'djv' => 'image/vnd.djvu',
        'djvu' => 'image/vnd.djvu',
        'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'eot' => 'application/vnd.ms-fontobject',
        'epub' => 'application/epub+zip',
        'gif' => 'image/gif',
        'gz' => 'application/gzip',
        'htm' => 'text/html',
        'html' => 'text/html',
        'ico' => 'image/vnd.microsoft.icon',
        'ics' => 'text/calendar',
        'jar' => 'application/java-archive',
        'jpeg' => 'image/jpeg',
        'jpg' => 'image/jpeg',
        'js' => 'text/javascript',
        'json' => 'application/json',
        'jsonld' => 'application/ld+json',
        'mid' => 'audio/midi',
        'midi' => 'audio/x-midi',
        'mjs' => 'text/javascript',
        'mkv' => 'video/mkv',
        'mp3' => 'audio/mpeg',
        'mp4' => 'video/mp4',
        'mpeg' => 'audio/mpeg',
        'mpeg' => 'video/mpeg',
        'mpg' => 'video/mpeg',
        'mpkg' => 'application/vnd.apple.installer+xml',
        'odp' => 'application/vnd.oasis.opendocument.presentation',
        'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
        'odt' => 'application/vnd.oasis.opendocument.text',
        'oga' => 'audio/ogg',
        'ogg' => 'application/ogg',
        'ogv' => 'video/ogg',
        'ogx' => 'application/ogg',
        'opus' => 'audio/opus',
        'otf' => 'font/otf',
        'pdf' => 'application/pdf',
        'php' => 'application/x-httpd-php',
        'png' => 'image/png',
        'ppt' => 'application/vnd.ms-powerpoint',
        'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'qt' => 'video/quicktime',
        'rar' => 'application/vnd.rar',
        'rtf' => 'application/rtf',
        'sh' => 'application/x-sh',
        'svg' => 'image/svg+xml',
        'swf' => 'application/x-shockwave-flash',
        'tar' => 'application/x-tar',
        'tif' => 'image/tiff',
        'tiff' => 'image/tiff',
        'ts' => 'video/mp2t',
        'ttf' => 'font/ttf',
        'txt' => 'text/plain',
        'vsd' => 'application/vnd.visio',
        'wav' => 'audio/wav',
        'wav' => 'audio/x-wav',
        'weba' => 'audio/webm',
        'webm' => 'video/webm',
        'webp' => 'image/webp',
        'wmv' => 'video/x-ms-wmv',
        'woff' => 'font/woff',
        'woff2' => 'font/woff2',
        'xhtml' => 'application/xhtml+xml',
        'xls' => 'application/vnd.ms-excel',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'xml' => 'application/xml',
        'xml' => 'text/xml',
        'xul' => 'application/vnd.mozilla.xul+xml',
        'zip' => 'application/zip'
    ];

    $imgIWL_Img  = $_GET['imgIWL'];
    $imgIWL_File = $wgPWAC['IP'] . $imgIWL_Img;
    $imgIWL_Name = explode('/', $imgIWL_Img);
    $imgIWL_Name = end($imgIWL_Name);
    $imgIWL_Ext  = explode('.', $imgIWL_Img);
    $imgIWL_Ext  = end($imgIWL_Ext);
    $imgIWL_Type = $imgIWL_ContentTypes["$imgIWL_Ext"];
    $imgIWL_Name_OriginalFile = '';

    $wgWhitelistRead = unserialize(file_get_contents($wgPWAC['WhitelistPagesFile']));

    // Test whether the requested Image is a Resized Version of any Whitelisted Image
    foreach ($wgWhitelistRead as $entry) {
        $entry = explode(':', $entry);
        $entry = end($entry);
        $entry_Ext  = explode('.', $entry);
        $entry_Ext  = end($entry_Ext);

        // Handle the cases when the request image is rendered version of any file, for example PDF > JPG
        if (strpos($imgIWL_Name, $entry) && in_array($entry_Ext, array_keys($imgIWL_ContentTypes))) {
            // this is an alternative trigger of the next condition
            $imgIWL_Name_OriginalFile = $entry;
        }

	/**
     * В горното условие има малък бъг. Например:
	 * 	"Файл:ЕФ Структура на Технологичната среда 1.png" се показва когато
	 * 	"Файл:Структура на Технологичната среда 1.png" е разрешен.
	 * Условията (preg_grep("/^$entry/", $imgIWL_Name)" или ($entry === $imgIWL_Name) не вършат работа в случаи като този,
	 * когато трябва да се изведе "36px-FolderTreeGreenIcon.svg.png", когато "Файл:FolderTreeGreenIcon.svg" е разрешен.
	 */
	//file_put_contents('/tmp/pwac.entry.log', $imgIWL_Name_OriginalFile . ' : ' . $entry . ' : ' . $imgIWL_Name, LOCK_EX);
    }

    // Provide the requested image or its resized version
    if (preg_grep("/$imgIWL_Name/", $wgWhitelistRead) || ($imgIWL_Name_OriginalFile)) {
        header('Content-type: ' . $imgIWL_Type);
        header('Content-Disposition: filename="' . $imgIWL_Name . '"');
        readfile($imgIWL_File);
    } else {
        header('Content-type: ' . 'image/jpeg');
        readfile('./images/access-denied.jpg');
    }

    //$logLine = $_SERVER['REMOTE_ADDR'] .' : '. $_SERVER["HTTP_NAME"] . ' : ' . $_SERVER["HTTP_HOST"] .' : '. $_SERVER["HTTP_REFERER"] .' : '. gethostbyaddr ( $_SERVER['REMOTE_ADDR'] ) .' : '. $imgIWL_Name;
	//file_put_contents('/tmp/pwac.image.request.log', $logLine  . "\n", FILE_APPEND | LOCK_EX);
    return true;
}


/**
 * PART 2: API Requests Whitelist Option - Circumstances
**/
// Write queries log, clear the log when the file is bigger than 100K
if ($wgPWAC['WhitelistApiLog'] && $wgPWAC['WhitelistApiLog'] != 'disable') {

    //global $wgPWAC;

    $logLine = $_SERVER['REMOTE_ADDR'] . ': ' . urldecode(http_build_query($_GET)) . PHP_EOL;
    $logFile = $wgPWAC['WhitelistApiLog'];
    if (filesize($wgPWAC['WhitelistApiLog']) > 100000) {
        file_put_contents($logFile, $logLine, LOCK_EX);
    } else {
        file_put_contents($logFile, $logLine, FILE_APPEND | LOCK_EX);
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

    if (isset($result["login"]["token"])) {
	return $result["login"]["token"];
    } else {
        return true;
    }
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
