<?php
//Create a file in the same directory as this on named deadlink.config.local.inc.php and copy the stuff below.
//Activate this to run the bot on a specific page(s) for debugging purposes.
$debug = false;
$limitedRun = false;
$debugPage = ['title' => "", 'pageid' => 0];
$debugStyle = 20;   //Use an int to run through a limited amount of articles.  Use "test" to run the test pages.
// Set to true to disable writing to database and editing wiki (dry run)
// And write what would be edited on the page to stdout
$testMode = false;
// Set to true to disable the edit function of the bot.
$disableEdits = false;
//Set the bot's UA
$userAgent = '';
//Multithread settings.  Use this to speed up the bot's performance.  Do not use more than 50 workers.
//This increases network bandwidth.  The programs speed will be limited by the CPU, or the bandwidth, whichever one is slower.
$multithread = false;
$workers = false;
$workerLimit = 3;
//Set Wiki to run on, define this before this gets called, to run on a different wiki.
if ( !defined( 'WIKIPEDIA' ) ) define( 'WIKIPEDIA', "enwiki" );
//Progress memory file.  This allows the bot to resume where it left off in the event of a shutdown or a crash.
$memoryFile = "";
//Wiki connection setup.  Uses the defined constant WIKIPEDIA.
switch ( WIKIPEDIA ) {
	default:
		$apiURL = "https://en.wikipedia.org/w/api.php";
		$oauthURL = "https://en.wikipedia.org/w/index.php?title=Special:OAuth";
		$consumerKey = "";
		$consumerSecret = "";
		$accessToken = "";
		$accessSecret = "";
		$username = "";
		$wikirunpageURL =
			false; //Optional: Forces the run page to be read from another wiki.  Specify the index.php url of the wiki to be read from.
		$runpage = "";
		$taskname = "";
		$nobots = false;
		break;
}
//Log central API
$enableAPILogging = false;
$apiCall = "";
$expectedValue = true;
$decodeFunction = 'unserialize';        //Either json_decode or unserialize
//IA Error Mailing List
$enableMail = false;
$to = "";
$from = "";
//DB connection setup
$host = "";
$port = "";
$user = "";
$pass = "";
$db = "";
//Wikipedia DB setup
$useWikiDB = false;
$wikihost = "";
$wikiport = "";
$wikiuser = "";
$wikipass = "";
$wikidb = "";
$revisiontable = "";
$texttable = "";
//Webapp variables
$interfaceMaster = [
	'inheritsgroups' => ['root'],
	'inheritsflags' => [],
	'assigngroups' => ['root'],
	'assignflags' => [],
	'removegroups' => ['root'],
	'removeflags' => [],
	'members' => []
];
$userGroups = [
	'basicuser' => [
		'inheritsgroups' => [],
		'inheritsflags' => [ 'reportfp' ],
		'assigngroups' => [],
		'removegroups' => [],
		'assignflags' => [],
		'removeflags' => [],
		'labelclass' => "default",
		'autoacquire' => [
			'registered' => strtotime( "-3 months" ),
			'editcount' => 3000,
			'withwikigroup' => [],
			'withwikiright' => []
		]
	],
	'user' => [
		'inheritsgroups' => ['basicuser'],
		'inheritsflags' => [],
		'assigngroups' => [],
		'removegroups' => [],
		'assignflags' => [],
		'removeflags' => [],
		'labelclass' => "primary",
		'autoacquire' => [
			'registered' => strtotime( "-6 months" ),
			'editcount' => 6000,
			'withwikigroup' => [],
			'withwikiright' => []
		]
	],
	'admin' => [
		'inheritsgroups' => ['user'],
		'inheritsflags' => ['blockuser', 'changepermissions', 'unblockuser'],
		'assigngroups' => ['user', 'basicuser'],
		'removegroups' => ['user', 'basicuser'],
		'assignflags' => ['changepermissions', 'reportfp'],
		'removeflags' => ['changepermissions', 'reportfp'],
		'labelclass' => "success",
		'autoacquire' => [
			'registered' => strtotime( "-6 months" ),
			'editcount' => 6000,
			'withwikigroup' => ['sysop'],
			'withwikiright' => []
		]
	],
	'root' => [
		'inheritsgroups' => ['admin', 'bot'],
		'inheritsflags' => ['unblockme', 'viewfpreviewpage', 'changefpreportstatus', 'fpruncheckifdeadreview', 'changemassbq', 'viewbotqueue', 'changebqjob'],
		'assigngroups' => ['admin', 'bot'],
		'removegroups' => ['admin', 'bot'],
		'assignflags' => ['blockuser', 'unblockuser', 'unblockme', 'viewfpreviewpage', 'changefpreportstatus','fpruncheckifdeadreview', 'changemassbq', 'viewbotqueue', 'changebqjob'],
		'removeflags' => ['blockuser', 'unblockuser', 'unblockme', 'viewfpreviewpage', 'changefpreportstatus','fpruncheckifdeadreview', 'changemassbq', 'viewbotqueue', 'changebqjob'],
		'labelclass' => "danger",
		'autoacquire' => [
			'registered' => 0,
			'editcount' => 0,
			'withwikigroup' => [],
			'withwikiright' => []
		]
	],
	'bot' => [
		'inheritsgroups' => ['user'],
		'inheritsflags' => [],
		'assigngroups' => [],
		'removegroups' => [],
		'assignflags' => [],
		'removeflags' => [],
		'labelclass' => "info",
		'autoacquire' => [
			'registered' => 0,
			'editcount' => 0,
			'withwikigroup' => [],
			'withwikiright' => ['bot']
		]
	]
];
//DO NOT COPY ANYTHING BELOW THIS LINE
if ( file_exists( dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'deadlink.config.local.inc.php'
) ) require_once( 'deadlink.config.local.inc.php' );
require_once( 'API.php' );
if ( $multithread || $workers ) require_once( 'thread.php' );
require_once( 'Parser/parse.php' );
require_once( 'DB.php' );
require_once( __DIR__ . '/../vendor/autoload.php' );
if ( file_exists( dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'Parser/' . WIKIPEDIA . '.php' ) ) {
	require_once( 'Parser/' . WIKIPEDIA . '.php' );
	define( 'PARSERCLASS', WIKIPEDIA . 'Parser' );
} else {
	define( 'PARSERCLASS', 'Parser' );
	echo "ERROR: Unable to load local wiki parsing library.\nTerminating application...";
	exit( 40000 );
}
define( 'USERAGENT', $userAgent );
define( 'COOKIE', $username . WIKIPEDIA . $taskname );
define( 'API', $apiURL );
define( 'OAUTH', $oauthURL );
define( 'NOBOTS', $nobots );
if ( !defined( 'USEWEBINTERFACE' ) ) define( 'USERNAME', $username );
define( 'TASKNAME', $taskname );
define( 'IAPROGRESS', $memoryFile );
define( 'RUNPAGE', $runpage );
define( 'MULTITHREAD', $multithread );
define( 'WORKERS', $workers );
define( 'DEBUG', $debug );
define( 'LIMITEDRUN', $limitedRun );
define( 'TESTMODE', $testMode );
define( 'DISABLEEDITS', $disableEdits );
define( 'USEWIKIDB', $useWikiDB );
define( 'WIKIHOST', $wikihost );
define( 'WIKIPORT', $wikiport );
define( 'WIKIUSER', $wikiuser );
define( 'WIKIPASS', $wikipass );
define( 'WIKIDB', $wikidb );
define( 'REVISIONTABLE', $revisiontable );
define( 'TEXTTABLE', $texttable );
define( 'HOST', $host );
define( 'PORT', $port );
define( 'USER', $user );
define( 'PASS', $pass );
define( 'DB', $db );
define( 'CONSUMERKEY', $consumerKey );
define( 'CONSUMERSECRET', $consumerSecret );
if ( !defined( 'USEWEBINTERFACE' ) ) define( 'ACCESSTOKEN', $accessToken );
if ( !defined( 'USEWEBINTERFACE' ) ) define( 'ACCESSSECRET', $accessSecret );
define( 'ENABLEMAIL', $enableMail );
define( 'TO', $to );
define( 'FROM', $from );
define( 'LOGAPI', $enableAPILogging );
define( 'APICALL', $apiCall );
define( 'EXPECTEDRETURN', $expectedValue );
define( 'DECODEMETHOD', $decodeFunction );
define( 'WIKIRUNPAGEURL', $wikirunpageURL );
define( 'VERSION', "1.3alpha2" );
define( 'INTERFACEVERSION', "1.0alpha3" );
if ( !defined( 'UNIQUEID' ) ) define( 'UNIQUEID', "" );
unset( $wikirunpageURL, $enableAPILogging, $apiCall, $expectedValue, $decodeFunction, $enableMail, $to, $from, $oauthURL, $accessSecret, $accessToken, $consumerSecret, $consumerKey, $db, $user, $pass, $port, $host, $texttable, $revisiontable, $wikidb, $wikiuser, $wikipass, $wikiport, $wikihost, $useWikiDB, $limitedRun, $testMode, $disableEdits, $debug, $workers, $multithread, $runpage, $memoryFile, $taskname, $username, $nobots, $apiURL, $userAgent );
?>
