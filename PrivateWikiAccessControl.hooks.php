<?php
/**
 * @author    Spas Z. Spasov <spas.z.spasov@gmail.com>
 * @copyright 2019 Spas Z. Spasov
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU General Public License, version 3 (or later)
 * @home      https://github.com/pa4080/mw-PrivateWikiAccessControl
 *
 * This file is a part of the MediaWiki Extension:PrivateWikiAccessControl.
 *
 * This is the actual MediaWiki Extension:PrivateWikiAccessControl.
 * The extension will generate three files (by default) located in the '$IP/cache' directory - so it must be writable by 'www-data'.
 * 1. The first file is an Configuration Array that will be used by the API from the same package.
 * 2. The second file is an Array that contains the Pages listed in MediaWiki:InternalWhitelist.
 *    This array could be read within LocalSettings.php in a way like this:
 *    $wgWhitelistRead = unserialize(file_get_contents("$IP/cache/PWAC_WhitelistPages.txt"));
 * 3. The third file is an Array that contains the Queries (partially) listed in MediaWiki:InternalWhitelistAPI.
 *    This Array will be used by the API from the same package.
 * In addition the extension loads a JavaScript module that will ad an interface menu element within the dropdown menu 'More'.
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
**/

if (!defined('MEDIAWIKI')) {
    die('This file is an extension to MediaWiki and thus not a valid entry point.');
} else {
    global $IP;
    global $wgServer;
    global $wgPWAC;
    global $wgPWACSettings;

    $wgPWAC['IP']                 = $IP;
    $wgPWAC['CacheDir']           = $wgPWAC['IP'] . '/cache';
    $wgPWAC['WhitelistPages']     = 'InternalWhitelist';
    $wgPWAC['WhitelistApi']       = 'InternalWhitelistAPI';
    $wgPWAC['WhitelistCat']       = 'InternalWhitelistCAT';
    $wgPWAC['WhitelistPagesFile'] = $wgPWAC['CacheDir'] . '/PWAC_WhitelistPages.txt';
    $wgPWAC['WhitelistApiFile']   = $wgPWAC['CacheDir'] . '/PWAC_WhitelistApi.txt';
    $wgPWAC['WhitelistCatFile']   = $wgPWAC['CacheDir'] . '/PWAC_WhitelistCat.txt';
    $wgPWAC['WhitelistApiUser']   = false;
    $wgPWAC['WhitelistApiPass']   = false;
    $wgPWAC['WhitelistApiCookie'] = $wgPWAC['CacheDir'] . '/PWAC_Api.cookie';
    $wgPWAC['WhitelistApiLog']    = $wgPWAC['CacheDir'] . '/PWAC_Api.log';
    $wgPWAC['WhitelistAllApi']    = 'Allow All';
    $wgPWAC['WhitelistApiURI']    = '/wl.api.php';
    $wgPWAC['MediaWikiApiURI']    = '/mw.api.php';
    $wgPWAC['wgServerMediaWiki']  = $wgServer;
    $wgPWAC['WhitelistWalk']      = false;
    $wgPWAC['ConfigurationFile']  = $wgPWAC['CacheDir'] . '/PWAC_Conf.txt';
    $wgPWAC['AutoGenerateWL']     = false;
    $wgPWAC['CronWLPage']         = 'MediaWiki:Pwac-menu-label_disabled_by_default';

    // Merge the user's settings with the default ones
    foreach ($wgPWACSettings as $key => $value) {
        $wgPWAC[$key]  = $value;
    }

    // Only the value 'disable' is acceptable as user's preference
    if ($wgPWAC['WhitelistApiLog'] != 'disable') {
        $wgPWAC['WhitelistApiLog'] = $wgPWAC['CacheDir'] . '/PWAC_Api.log';
    }

    // Compose the end points
    $wgPWAC['WhitelistApiEndPoint'] = $wgPWAC['wgServerMediaWiki'] . $wgPWAC['WhitelistApiURI'];
    $wgPWAC['MediaWikiApiEndPoint'] = $wgPWAC['wgServerMediaWiki'] . $wgPWAC['MediaWikiApiURI'];

    // Prepare the current and the saved configuration for comparison
    $wgPWAC_Serialized = serialize($wgPWAC);
    $wgPWAC_FromFile = file_get_contents($wgPWAC['ConfigurationFile']);

    // Compare the current and the saved configuration and save the new configuration if it is needed
    if ($wgPWAC_Serialized != $wgPWAC_FromFile) {
        file_put_contents($wgPWAC['ConfigurationFile'], $wgPWAC_Serialized, LOCK_EX);
    }
}

class PrivateWikiAccessControlHooks {

    public static function onArticleViewHeader( &$article, &$outputDone, &$pcache ) {

        global $wgPWAC;

        $articleTitle = $article->getTitle();
        $articleTitle = explode(':', $articleTitle);
        $articleTitle = end($articleTitle);

        $CronWLPage = $wgPWAC['CronWLPage'];
        $CronWLPage = explode(':', $CronWLPage);
        $CronWLPage = end($CronWLPage);

        $allowedTitles = array($wgPWAC['WhitelistPages'], $wgPWAC['WhitelistCat'], $wgPWAC['WhitelistApi'], $CronWLPage);

        if (in_array($articleTitle, $allowedTitles) || $wgPWAC['AutoGenerateWL'] === true) {

            /**
             * Create an array of Whitelisted Pages and Categories
            **/

            // Create an array of Whitelisted Pages
            $PWAC_WhitelistText = wfMessage($wgPWAC['WhitelistPages'])->text();
            $PWAC_WhitelistArray = explode("\n", $PWAC_WhitelistText);

            // Process the array of Whitelisted Pages
            foreach ($PWAC_WhitelistArray as $entry) {

                // Find lines starting with one or more `*`, preceded by zero or more whitespaces
                $has_match = preg_match('#^\*+.*$#', $entry, $matches);

                if ($has_match == 1) {
                    // Apply some filters
                    $entry = preg_replace('/(\[\[|\]\])/', '', $entry);
                    $entry = trim(trim($entry, "*"));
                    $entry = preg_replace('/^\:/', '', $entry);
                    $entry = preg_replace("/[\s]/", "_", $entry);
                    $entry = trim($entry);
                    // Add the entry to whitelist
                    $PWAC_WhitelistReadCurrent[] = $entry;
                }
            }

            // Create an array of Whitelisted Categories
            $PWAC_WhitelistCatText = wfMessage($wgPWAC['WhitelistCat'])->text();
            $PWAC_WhitelistCatArray = explode("\n", $PWAC_WhitelistCatText);

            // If the array of Whitelisted Categories is not empty
            if (!empty($PWAC_WhitelistCatArray)) {

                foreach ($PWAC_WhitelistCatArray as $entry) {

                    // Find lines starting with one or more `*`, preceded by zero or more whitespaces
                    $has_match = preg_match('#^\*+.*$#', $entry, $matches);

                    if ($has_match == 1) {
                        // Apply some filters
                        $entry = preg_replace('/(\[\[|\]\])/', '', $entry);
                        $entry = trim(trim($entry, "*"));
                        $entry = preg_replace('/^\:/', '', $entry);
                        $entry = preg_replace("/[\s]/", "_", $entry);
                        $entry = trim($entry);
                        // Whitelist the category itself (probably this must be commentout?)
                        $PWAC_WhitelistReadCurrent[] = $entry;

			// Prepare an array that contains the list of the Whitelist Categories (cat)
			$PWAC_WhitelistCategoryList[] = $entry;

                        // API Request in order to get a list with the category members
                        $params = [
                            "action" => "query",
                            "list" => "categorymembers",
                            "cmtitle" => $entry,
                            "cmlimit" => "5000",
                            "format" => "json"
                        ];

                        // $wgPWAC['WhitelistApiEndPoint'] = $endPoint = "https://wiki.szs.space/wl.api.php";
                        $url = $wgPWAC['WhitelistApiEndPoint'] . "?" . http_build_query( $params );

                        // Do the API Request
                        $ch = curl_init( $url );
                        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
                        $output = curl_exec( $ch );
                        curl_close( $ch );

                        // Decode the list
                        $result = json_decode( $output, true );

                        // Whitelist each category member
                        foreach( $result["query"]["categorymembers"] as $page ){
                            $entry = $page["title"];
                            // Apply some filters
                            $entry = preg_replace("/[\s]/", "_", $entry);
                            // Add the entry to whitelist
                            $PWAC_WhitelistReadCurrent[] = $entry;
                        }
                    }
                }
            }

            // This is a hack for the curl cron job for more details see README.md
            $PWAC_WhitelistReadCurrent[] = $wgPWAC['CronWLPage'];

            // Prepare the current and the saved Whitelist array for comparison
            $PWAC_WhitelistReadSerialized = serialize($PWAC_WhitelistReadCurrent);
            $PWAC_WhitelistReadFromFile = file_get_contents($wgPWAC['WhitelistPagesFile']);

            // Compare the current and the saved Whitelist array and save the new Whitelist array if it is needed
            if ($PWAC_WhitelistReadSerialized != $PWAC_WhitelistReadFromFile) {
                file_put_contents($wgPWAC['WhitelistPagesFile'], $PWAC_WhitelistReadSerialized, LOCK_EX);
            }

            // Prepare the current and the saved list of the Whitelist Categories array for comparison (cat)
            $PWAC_WhitelistCatSerialized = serialize($PWAC_WhitelistCategoryList);
            $PWAC_WhitelistCatFromFile = file_get_contents($wgPWAC['WhitelistCatFile']);

            // Compare the current and the saved list of the Whitelist Categories array if it is needed (cat)
            if ($PWAC_WhitelistCatSerialized != $PWAC_WhitelistCatFromFile) {
                file_put_contents($wgPWAC['WhitelistCatFile'], $PWAC_WhitelistCatSerialized, LOCK_EX);
            }


            /**
             * Create an array of Whitelisted Api Requests
            **/

            // Get a list of the Whitelisted Api Requests
            $PWAC_WhitelistApiText = wfMessage($wgPWAC['WhitelistApi'])->text();
            $PWAC_WhitelistApiArray = explode("\n", $PWAC_WhitelistApiText);

            foreach ($PWAC_WhitelistApiArray as $entry) {
                // Find lines starting with one or more `*`, preceded by zero or more whitespaces
                $has_match = preg_match('#^\*+.*$#', $entry, $matches);
                if ($has_match == 1) {
                    $entry = trim(trim($entry, "*"));
                    $PWAC_WhitelistReadApiCurrent[] = $entry;
                }
            }

            // Allow all API requests when the list is empty
            if (empty($PWAC_WhitelistReadApiCurrent)) {
                $PWAC_WhitelistReadApiCurrent[] = $wgPWAC['WhitelistAllApi'];
            }

            // Prepare the current and the saved Whitelist array for comparison
            $PWAC_WhitelistReadApiSerialized = serialize($PWAC_WhitelistReadApiCurrent);
            $PWAC_WhitelistReadApiFromFile = file_get_contents($wgPWAC['WhitelistApiFile']);

            // Write the new array values if it is needed
            if ($PWAC_WhitelistReadApiSerialized != $PWAC_WhitelistReadApiFromFile) {
                    file_put_contents($wgPWAC['WhitelistApiFile'], $PWAC_WhitelistReadApiSerialized, LOCK_EX);
            }

        } // end if

        return true;
    } // End of the function


    public static function onBeforePageDisplay(OutputPage $out, Skin $skin) {
        $out->addModules('PrivateWikiAccessControlManager');
        return true;
    }

    public static function onResourceLoaderGetConfigVars( array &$vars ) {
        global $wgPWAC;

        // Prepare the list of Whitelist Categories to the JavaScript environment
        $PWAC_WhitelistCatFromFile = unserialize(file_get_contents($wgPWAC['WhitelistCatFile']));
        $PWAC_WhitelistCatJS = '';

        foreach ($PWAC_WhitelistCatFromFile as $entry) {
            $PWAC_WhitelistCatJS = $PWAC_WhitelistCatJS . $entry . ', ';
        }
        //$PWAC_WhitelistCatJS = json_encode($PWAC_WhitelistCatFromFile);


        // Forward some PHP variables to the JavaScript environment
        $vars['wgPWAC'] = [
            'WhitelistPages' => $wgPWAC['WhitelistPages'],
            'WhitelistWalk'  => $wgPWAC['WhitelistWalk'],
            'WhitelistCat'   => $PWAC_WhitelistCatJS
        ];

        return true;
    }
}
