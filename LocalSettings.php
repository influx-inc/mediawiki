<?php

# This file was automatically generated by the MediaWiki 1.37.1
# installer. If you make manual changes, please keep track in case you
# need to recreate them later.
#
# See includes/DefaultSettings.php for all configurable settings
# and their default values, but don't forget to make changes in _this_
# file, not there.
#
# Further documentation for configuration settings may be found at:
# https://www.mediawiki.org/wiki/Manual:Configuration_settings

# Protect against web entry
if ( !defined( 'MEDIAWIKI' ) ) {
	exit;
}

require_once('vendor/autoload.php');

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

$wgShowSQLErrors = 1;

$wgSitename = "Influx Wiki";
$wgMetaNamespace = "Influx_Wiki";

$wgScriptPath = "";
$wgArticlePath = "/wiki/$1";

## The protocol and server name to use in fully-qualified URLs
$wgServer = $_ENV["SITE_URL"];

#
# Caching
#
## Set $wgCacheDirectory to a writable directory on the web server
## to make your wiki go slightly faster. The directory should not
## be publicly accessible from the web.
$wgCacheDirectory = "$IP/cache";

# See https://www.mediawiki.org/wiki/Manual:Memcached

$wgMemCachedServers = [ "127.0.0.1:11211" ];
$wgMainCacheType    = CACHE_MEMCACHED;
$wgParserCacheType  = CACHE_MEMCACHED;
$wgMessageCacheType = CACHE_MEMCACHED;
$wgSessionCacheType = CACHE_MEMCACHED;

# Via: https://www.mediawiki.org/wiki/User:Aaron_Schulz/How_to_make_MediaWiki_fast
$wgJobRunRate = 0;
$wgUseGzip = true;
$wgEnableSidebarCache = true;
$wgDisableCounters = true;
$wgMiserMode = true;


## The URL path to static resources (images, scripts, etc.)
$wgResourceBasePath = $wgScriptPath;

$wgEnableEmail = true;
$wgEnableUserEmail = true; # UPO

$wgEmergencyContact = "apache@🌻.invalid";
$wgPasswordSender = "apache@🌻.invalid";

$wgEnotifUserTalk = false; # UPO
$wgEnotifWatchlist = false; # UPO
$wgEmailAuthentication = true;

## Database settings
$parts = parse_url($_ENV["DATABASE_URL"]);

$wgDBtype     = "mysql";
$wgDBserver   = $parts['host'];
$wgDBname     = substr($parts['path'], 1);
$wgDBuser     = $parts['user'];
$wgDBpassword = $parts['pass'];

# MySQL specific settings
$wgDBprefix = "";

# MySQL table options to use during installation or update
$wgDBTableOptions = "ENGINE=InnoDB, DEFAULT CHARSET=binary";

# Shared database table
# This has no effect unless $wgSharedDB is also set.
$wgSharedTables[] = "actor";

## To enable image uploads, make sure the 'images' directory
## is writable, then set this to true:
$wgEnableUploads = true;
$wgUseImageMagick = true;
$wgImageMagickConvertCommand = "/usr/bin/convert";

# InstantCommons allows wiki to use images from https://commons.wikimedia.org
$wgUseInstantCommons = false;

# Periodically send a pingback to https://www.mediawiki.org/ with basic data
# about this MediaWiki instance. The Wikimedia Foundation shares this data
# with MediaWiki developers to help guide future development efforts.
$wgPingback = false;

## If you use ImageMagick (or any other shell command) on a
## Linux server, this will need to be set to the name of an
## available UTF-8 locale. This should ideally be set to an English
## language locale so that the behaviour of C library functions will
## be consistent with typical installations. Use $wgLanguageCode to
## localise the wiki.
$wgShellLocale = "en_US.UTF-8";

# Site language code, should be one of the list in ./languages/data/Names.php
$wgLanguageCode = "en";

# Time zone
$wgLocaltimezone = "UTC";

$wgMaxCredits = 1;

$wgSecretKey = $_ENV['MEDIAWIKI_SECRET_KEY'];

# Changing this will log out all existing sessions.
$wgAuthenticationTokenVersion = "1";

# Site upgrade key. Must be set to a string (default provided) to turn on the
# web installer while LocalSettings.php is in place
$wgUpgradeKey = "b448eab0f45bed26";

## For attaching licensing metadata to pages, and displaying an
## appropriate copyright notice / icon. GNU Free Documentation
## License and Creative Commons licenses are supported so far.
$wgRightsPage = ""; # Set to the title of a wiki page that describes your license/copyright
$wgRightsUrl = "";
$wgRightsText = "";
$wgRightsIcon = "";

# Path to the GNU diff3 utility. Used for conflict resolution.
$wgDiff3 = "/usr/bin/diff3";

#
# Theme
#
wfLoadSkin( 'Vector' );
$wgDefaultSkin = 'vector-2022';

$wgLogos = [
	'svg' => 'https://influx.com/images/brand/logo-influx-dark.svg',
	'wordmark' => [
		'src' => "https://influx.com/images/brand/logo-influx-dark.svg",
		'width' => 135,
		'height' => 24,
	],
];

//
//  Add custom CSS + JS under /customizations
//
$wgResourceModules['zzz.customizations'] = array(
	'styles'         => "skin.css",
	'scripts'        => "skin.js",
	'localBasePath'  => "$IP/customizations/",
	'remoteBasePath' => "$wgScriptPath/customizations/"
);

function onCustomBeforePageDisplay( &$out, &$skin ) {
	$out->addModules( array( 'zzz.customizations' ) );

	// Add Google Sign-in JS + metatag
/*
	$out->addHeadItem('GoogleJS', '<script src="https://apis.google.com/js/platform.js" async defer></script>');
	$out->addHeadItem('GoogleMeta', '<meta name="google-signin-client_id" content="161144458162-u7cvk85nv7ai0fj0jpgqhqg1l06tu9bg.apps.googleusercontent.com" />');
*/
	$js = <<<END
	<script>
	function onSignIn(user) {
		const profile = user.getBasicProfile();
		const email   = profile.getEmail()
		const token   = user.getAuthResponse().id_token

		console.log('Google sign in as', email)

		fetch("/google_auth", {
			method: "POST",
			headers: { 'Content-type':  'application/x-www-form-urlencoded' },
			body: "token=" + token
		})
		.then(response => {
			localStorage.setItem("UserEmail", email)

			if ( window.location.pathname == "/" ) {
				window.location.pathname = "/wiki"
			} else {
				window.location.reload()
			}
		})
	}
	</script>
END;

	$out->addHeadItem("onSignInFunc", $js);

	// Add the sign in button
	/*
	if ( !isset($_COOKIE["google_auth_token"])) {
		$out->prependHTML('<div class="g-signin2" data-onsuccess="onSignIn"></div>');
	}
	*/

	// Add Sentry JS
	$out->addHeadItem("Sentry", '<script src="https://js.sentry-cdn.com/d5310fdaa0fb42ab828a5119867ce92b.min.js" crossorigin="anonymous"></script>');

	return true;

}

$wgHooks['BeforePageDisplay'][] = 'onCustomBeforePageDisplay';


# ---------

$wgShowDebug = false;
$wgDevelopmentWarnings = false;
$wgShowExceptionDetails = true;
$wgDeprecationReleaseLimit = '1.0';
$wgFooterIcons = [];

#
# Elasticsearch
#
wfLoadExtension( 'Elastica' );
wfLoadExtension( 'CirrusSearch' );

$wgDisableSearchUpdate = false;

$wgSearchType = 'CirrusSearch';

$wgCirrusSearchUseCompletionSuggester = 'yes';
$wgCirrusSearchCompletionSettings = 'fuzzy-subphrases';
$wgCirrusSearchPhraseSuggestProfiles = 'default';
$wgCirrusSearchCompletionSuggesterSubphrases = [

   'build' => true,
   'use' => true,
   'type' => 'anywords',
   'limit' => 10,
];
$wgCirrusSearchCompletionSuggesterUseDefaultSort = true;

#
# Google Login (https://www.mediawiki.org/wiki/Extension:GoogleLogin)
#
wfLoadExtension( 'GoogleLogin' );

$wgGroupPermissions['*']['read'] = false;
$wgGroupPermissions['*']['autocreateaccount'] = true;
$wgWhitelistRead = array( 'Special:GoogleLogin', 'Special:GoogleLoginReturn' );

$wgGLSecret = $_ENV['GOOGLE_LOGIN_SECRET'];
$wgGLAppId  = $_ENV['GOOGLE_LOGIN_APP_ID'];

$wgGLForceKeepLogin = true;
$wgGLReplaceMWLogin = false;
$wgGLAllowedDomains = array('influx.com', 'influx.support', 'influxapprentice.com');

$wgGroupPermissions['*']['autocreateaccount'] = true;
$wgGLAuthoritativeMode = true;
$wgInvalidUsernameCharacters = ':~';
$wgUserrightsInterwikiDelimiter = '~';

$wgAuthManagerConfig = [
	'primaryauth' => [
			GoogleLogin\Auth\GooglePrimaryAuthenticationProvider::class => [
					'class' => GoogleLogin\Auth\GooglePrimaryAuthenticationProvider::class,
					'sort' => 0
			]
	],
	'preauth' => [],
	'secondaryauth' => []
];

// The extension lets us bypass Mediawiki authentication system and
// just tell it who the signed in user is ($wgAuthRemoteuserUserName)
/*
wfLoadExtension( 'Auth_remoteuser' );
//
// Validate the google_auth_token_cookie set by google_auth.php
//
if ( isset($_COOKIE["google_auth_token"]) ) {

	list($email, $time, $signature) = explode(':', $_COOKIE["google_auth_token"]);

	$expected = sha1($email . $time . $_ENV["AUTH_TOKEN_SALT"]);

	if ( $signature == $expected ) {
		//
		// Derive the username from the email "fred.bloggs@influx.com" -> "Fred Bloggs"
		//
		$username = explode('@', $email)[0];
		$username = str_replace(".", " ", $username);
		$username = ucwords($username);


		// This signs the user in:
		//
		$wgAuthRemoteuserUserName = $username;
	} else {
		// Unset the cookie if invalid
		setcookie("google_auth_token", "", time() - 3600);
	}
}

# Users must sign in to read the wiki:
$wgGroupPermissions['*']['read'] = false;
$wgGroupPermissions['*']['createaccount'] = false;
$wgGroupPermissions['*']['autocreateaccount'] = true;
*/
#
#  TinyMCE
#
wfLoadExtension( 'TinyMCE' );
$wgTinyMCEEnabled = true;

# Default toolbar:
# | undo redo | cut copy paste insert selectall | fontselect fontsizeselect bold italic underline strikethrough subscript superscript forecolor backcolor | alignleft aligncenter alignright alignjustify | bullist numlist advlist outdent indent | wikilink wikiunlink table image media | formatselect removeformat| visualchars visualblocks| searchreplace | wikimagic wikisourcecode wikitext wikiupload | wikitoggle nonbreaking singlelinebreak reference comment template',

$wgTinyMCESettings = array(
	"#wpTextbox1" => array(
		"toolbar" => "| wikilink wikiunlink | h1 h2 h3 | bold italic underline strikethrough forecolor backcolor | alignleft aligncenter alignright alignjustify | bullist numlist advlist outdent indent |table image media | wikimagic wikisourcecode wikitext wikiupload | wikitoggle reference template"
	)
);
