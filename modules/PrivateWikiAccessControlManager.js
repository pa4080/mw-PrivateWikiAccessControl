/**
 * @author    Spas Z. Spasov <spas.z.spasov@gmail.com>
 * @copyright 2019 Spas Z. Spasov
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GNU General Public License, version 3 (or later)
 * @home      https://github.com/pa4080/mw-PrivateWikiAccessControl
 *
 * This file is a part of the MediaWiki Extension:PrivateWikiAccessControl.
 * 
 * This script adds a page to MediaWiki:InternalWhitelist or remove it via a button in the dropdown toolbar 'More'.
 * The messages are currently available languages: En, Bg, Ru.
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


(function (mw, $) {

	// Definition of the necessary variables.
	var whitelistMenuItem;
	var label;
	var captionPublic;
	var captionPrivate;
	var currentPageNameInWhitelistEntry;
	var currentPageNameInWhitelistCat;

	// Get the list of the variables forwarded by PrivateWikiAccessControlHooks::onResourceLoaderGetConfigVars
	var wgPWAC = mw.config.get('wgPWAC');

        // Get the list oft the Whitelist Categories as String (replace _ with spaces); for Array add: .split(',');
        if (wgPWAC.WhitelistCat) {
		var whitelisCatList = wgPWAC.WhitelistCat.replace(/_/g, ' ');
	}

        var internalWhitelistArticleName = wgPWAC.WhitelistPages;

	var currentPageName = mw.config.get('wgPageName');
	var currentUserLanguage = mw.config.get('wgUserLanguage');
        var nameOfCategoryNS = mw.config.get('wgFormattedNamespaces')[14]; // Category in the wiki's language
        var nameOfMediaWikiNS = mw.config.get('wgFormattedNamespaces')[8]; // MediaWiki in the wiki's language
	var whitelisPageURI = mw.config.get('wgArticlePath').replace('$1', nameOfMediaWikiNS + ':' + internalWhitelistArticleName + '?action=raw'); //.replace('$1', 'MediaWiki:InternalWhitelist?action=raw');

	// If the action is 'view' and the user is logged in, then run the MAIN FUNCTION.
	if (mw.config.get('wgAction') === 'view' && mw.config.get( 'wgUserId' ) !== null) {
		generateLabelsCaptionsListEntryEtc();
		isWhitelisted();

		if (wgPWAC.WhitelistWalk === 'Add' || wgPWAC.WhitelistWalk === 'add') {
			// Add Pages to the InternalWhitelist while browsing the Wiki
			addToWhitelist();
		} else if (wgPWAC.WhitelistWalk === 'Remove' || wgPWAC.WhitelistWalk === 'remove') {
			// Remove Pages from the InternalWhitelist while browsing the Wiki
			removeFromWhitelist();
		}
	}

	// This is the main function.
	function isWhitelisted() {
		$.ajaxSetup({ cache: false });

		// Test whether the article belongs to a Whitelist Category,based on the list exported by the extension.
		// This is an alternative of the $.get(mw.Api()) request used by the other condition, which updates the values more dynamically.
		wgCategories.forEach( function(category) {
			if (whitelisCatList.indexOf(nameOfCategoryNS + ':' + category + ',') !== -1) currentPageNameInWhitelistCat = true;
		});

		if (currentPageNameInWhitelistCat === true) {
			publicPageMenuItemCat();
			// while the article belongs to a Whitelist Category
                        // and it is automatically whitelisted, we do not need click function here
		} else {
			$.get(whitelisPageURI, function(data){
				if (data.includes(currentPageNameInWhitelistEntry) === true) {
					publicPageMenuItem();

					$(whitelistMenuItem).click(function () {
						removeFromWhitelist();
						privatePageMenuItem();
						window.location.reload(true); // Avoid some confusions
					});
				} else {
					privatePageMenuItem();

					$(whitelistMenuItem).click(function () {
						addToWhitelist();
						publicPageMenuItem();
						window.location.reload(true); // Avoid some confusions
					});
				}
			});
		}
	}

	// Generate menu item if the current page belongs to MediaWiki:InternalWhitelist
	function publicPageMenuItem() {
		if (whitelistMenuItem) { whitelistMenuItem.parentNode.removeChild(whitelistMenuItem); }

		whitelistMenuItem = mw.util.addPortletLink('p-cactions', '#', label + ' ', 'ca-pwac-whitelist-manager-public', captionPublic, 'g', '#ca-delete');
	}

	// Generate menu item if the current page doesn't belong to MediaWiki:InternalWhitelist
	function privatePageMenuItem() {
		if (whitelistMenuItem) { whitelistMenuItem.parentNode.removeChild(whitelistMenuItem); }

		whitelistMenuItem = mw.util.addPortletLink('p-cactions', '#', label + ' ', 'ca-pwac-whitelist-manager-private', captionPrivate, 'g', '#ca-delete');
	}

	// Generate menu item if the current page belongs to Whitelist Catehory
	function publicPageMenuItemCat() {
		if (whitelistMenuItem) { whitelistMenuItem.parentNode.removeChild(whitelistMenuItem); }

		whitelistMenuItem = mw.util.addPortletLink('p-cactions', '#', label + ' ', 'ca-pwac-whitelist-manager-public-cat', captionCatPublic, '', '#ca-delete');
	}

	// Generate the menu item label, depending on the user's language.
	function generateLabelsCaptionsListEntryEtc() {
		// Read the interface labels from MediaWiki:messages-pages
		label = mw.message( 'pwac-menu-label' ).text();
		captionPublic  = mw.message( 'pwac-menu-alt-public' ).text();
		captionPrivate = mw.message( 'pwac-menu-alt-private' ).text();
		captionCatPublic = mw.message( 'pwac-menu-alt-public-cat' ).text();

		// Contrukt Whitelist entry based on the current page name.
		currentPageNameInWhitelistEntry = '* [[:' + currentPageName + ']]';
	}

	function addToWhitelist() {
		$.get(whitelisPageURI, function(data){
			if (data.includes(currentPageNameInWhitelistEntry) === false) {
				var params = {
					action: 'edit',
					title: nameOfMediaWikiNS + ':' + internalWhitelistArticleName, //'MediaWiki:InternalWhitelist',
					section: 'new',
					appendtext: currentPageNameInWhitelistEntry,
					format: 'json'
				}

				var api = new mw.Api();

				api.postWithToken('csrf', params).done(function (data) {
					console.log(data);
				});
			}
		});
	}

	function removeFromWhitelist() {
		$.get(whitelisPageURI, function(data){
			// Catch all cases: if the entry is found one or many times,
			// at the beginning, at the end or at the middle of the list.
			// Also when the syntaxis of the blocks is correct or not.

			if (data.includes(currentPageNameInWhitelistEntry + '\n\n') === true) {
				while (data.includes(currentPageNameInWhitelistEntry + '\n\n') === true) {
					data = data.replace(currentPageNameInWhitelistEntry + '\n\n', '');
				}
			}
			if (data.includes(currentPageNameInWhitelistEntry + '\n') === true) {
				while (data.includes(currentPageNameInWhitelistEntry + '\n') === true) {
					data = data.replace(currentPageNameInWhitelistEntry + '\n', '');
				}
			}
			if (data.includes('\n\n' + currentPageNameInWhitelistEntry) === true) {
				while (data.includes('\n\n' + currentPageNameInWhitelistEntry) === true) {
					data = data.replace('\n\n' + currentPageNameInWhitelistEntry, '');
				}
			}
			if (data.includes('\n' + currentPageNameInWhitelistEntry) === true) {
				while (data.includes('\n' + currentPageNameInWhitelistEntry) === true) {
					data = data.replace('\n' + currentPageNameInWhitelistEntry, '');
				}
			}
			if (data.includes('' + currentPageNameInWhitelistEntry) === true) {
				while (data.includes('' + currentPageNameInWhitelistEntry) === true) {
					data = data.replace('' + currentPageNameInWhitelistEntry, '');
				}
			}

			var params = {
				action: 'edit',
				title: nameOfMediaWikiNS + ':' + internalWhitelistArticleName, //'MediaWiki:InternalWhitelist',
				text: data,
				format: 'json'
			}

			var api = new mw.Api();

			api.postWithToken('csrf', params).done(function (data) {
				console.log(data);
			});
		});
	}

}(mediaWiki, jQuery));
