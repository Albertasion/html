<?php
if(file_exists($_SERVER['DOCUMENT_ROOT'].'/seoshield-client/main.php')) {
    include_once($_SERVER['DOCUMENT_ROOT'].'/seoshield-client/main.php');

    if (function_exists('seo_shield_start_cms')) {
        seo_shield_start_cms();
    }

    if (function_exists('seo_shield_out_buffer')) {
        ob_start('seo_shield_out_buffer');
    }
}

header("Pragma: nocache\n");
header("cache-control: no-cache, must-revalidate, no-store\n\n");
header("Expires: Mon, 01 Jan 1990 01:01:01 GMT");
header("Last-Modified: ".gmdate("D, d M Y H:i:s")."GMT");
header('Content-Type: text/html; charset=UTF-8; dir=RTL');
header('P3P: CP="CAO PSA OUR"');

if (!isset($_GET['errors_all_fd5sf5j'])) {
    error_reporting(E_ERROR | E_PARSE);
} else {
    error_reporting(E_ALL);
}

$_SERVER['REQUEST_URI_SOURCE'] = $_SERVER['REQUEST_URI'];

if(strpos($_SERVER['REQUEST_URI_SOURCE'], "//") !== false || strpos($_SERVER['REQUEST_URI_SOURCE'], "///") !== false){
    $url = str_replace("/////", "/", $_SERVER['REQUEST_URI_SOURCE']);
    $url = str_replace("////", "/", $url);
    $url = str_replace("///", "/", $url);
    $url = str_replace("//", "/", $url);
    $protocol = "http";
    if(isset($_SERVER['HTTPS'])) {
        $protocol = "https";
    }
    $url_final = $protocol . "://" . $_SERVER['HTTP_HOST'] . $url;
    header("HTTP/1.1 301 Moved Permanently");
    header("Location: $url_final");
}

if (strpos($_SERVER['REQUEST_URI_SOURCE'], "/page0/?offset=0") !== false){
    $url = str_replace(array("/page0/?offset=0"), '/', $_SERVER['REQUEST_URI_SOURCE']);
    $url = str_replace(array("/page0/"), '/', $url);
    $url = str_replace('/&', '/?', $url);
    $protocol = "http";
    if(isset($_SERVER['HTTPS'])) {
        $protocol = "https";
    }
    $url_final = $protocol . "://" . $_SERVER['HTTP_HOST'] . $url;

    header("HTTP/1.1 301 Moved Permanently");
    header("Location: $url_final");
    echo ' ';die();
}

include_once($_SERVER['DOCUMENT_ROOT'].'/published/SC/html/scripts/classes/FastConfig.php');

include_once($_SERVER['DOCUMENT_ROOT'].'/published/SC/html/scripts/classes/v2pagecache.php');           		//V2PAGECACHE
$pagecache = new V2PageCache();                                 //V2PAGECACHE
if ($pagecache->ServeFromCache()) {                             //V2PAGECACHE
    // exit here if we served this page from the cache          //V2PAGECACHE
    return;                                                     //V2PAGECACHE
}                                                               //V2PAGECACHE


// -------------------------INITIALIZATION-----------------------------//
ini_set('display_errors', true);
//error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
define('DIR_ROOT', str_replace("\\","/",realpath(dirname(__FILE__))));
$DebugMode = false;
$Warnings = array();

include_once(DIR_ROOT.'/includes/init.php');
include_once(DIR_CFG.'/connect.inc.wa.php');
if ( $_GET['ukey'] == 'checkout' && $_GET['view'] == 'noframe' ) $_GET['view'] = '';

//support for old urls
//hack-like method

// to HTTPS
if (!isset($_SERVER["HTTPS"]) || $_SERVER["HTTPS"] != 'on') {
    header("Vary: ");
    header_remove("Expires");
    header_remove("Cache-Control");
    header_remove("Pragma");
    header_remove("Set-Cookie");
    header_remove("Vary");
    header("HTTP/1.1 301 Moved Permanently");
    header("Location: https://" . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"]);
    exit();
}


if(MOD_REWRITE_SUPPORT
    &&!SystemSettings::is_backend()
    &&!isset($_GET['ukey'])
    &&(
        (isset($_GET['__furl_path'])&&preg_match('/^index.php/i',$_GET['__furl_path']))
        ||!isset($_GET['__furl_path'])
    )
){
    if(!isset($_GET['__furl_path'])){
        $_GET['__furl_path'] = '';
    }
    if(isset($_GET['productID'])){
        $_GET['__furl_path'].='/product/'.intval($_GET['productID']);
    }elseif (isset($_GET['categoryID'])){
        $_GET['__furl_path'].='/category/'.intval($_GET['categoryID']);
    }
}


//fix redirection
if(isset($_GET['__furl_path'])&&strpos($_GET['__furl_path'],'published/SC/html/scripts/')===0){
    $_GET['__furl_path'] = substr($_GET['__furl_path'],strlen('published/SC/html/scripts/'));
}

require_once DIR_FUNC.'/setting_functions.php';

if (!isset($_SESSION['WBS_ACCESS_SC']) || intval($_SESSION['WBS_ACCESS_SC']) == 0) {
    if (isset($_GET['debug'])&& ($_GET['debug']=='time' || $_GET['debug']=='total_time')){
        $T = new Timer();
        $T->timerStart();
    }
}

//file cache
global $waCache, $Cache_Lite;

$Cache_Lite = new Cache_Lite(['cacheDir' => WBS_DIR.'temp/cache_lite/', 'lifeTime' => GLOBAL_CACHETIME, 'automaticSerialization' => true]);

if (class_exists('Redis') || isset($_GET['redd'])) {
	try {
	    $waCache = new Redis();
	    // This connection is for a remote server
	    $waCache->connect('127.0.0.1', 6379); 
	} catch (Exception $e) {
	    die($e->getMessage());
	}
}

global $mysqli, $mysql_error;
$mysql_error = false;
if (!$mysqli = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME)) {
    echo "Ошибка: Невозможно установить соединение с MySQL." . PHP_EOL;
    echo "Код ошибки errno: " . mysqli_connect_errno() . PHP_EOL;
    echo "Текст ошибки error: " . mysqli_connect_error() . PHP_EOL;
    exit;
}

$DB_tree = new DataBase();
$DB_tree->link = $mysqli;

define('VAR_DBHANDLER','DBHandler');


if(isset($_SESSION['__WBS_SC_DATA'])&&isset($_SESSION['__WBS_SC_DATA']["U_ID"])){
    $fileEntry = new FileWBS();
    Functions::register($fileEntry, 'file_move_uploaded', 'move_uploaded');

    Functions::register($fileEntry, 'file_copy', 'copy');
    Functions::register($fileEntry, 'file_move', 'move');
    Functions::register($fileEntry, 'file_remove', 'remove');
}

$Register = &Register::getInstance();

// Document
$document = new Document();
$Register->set('document', $document);

$Register->set(VAR_DBHANDLER, $DB_tree);

settingDefineConstants();

if (!defined('CACHE_LAST_MOD_TS')) {
    $config = FastConfig::getInstance();
    $config->set('CACHE_LAST_MOD_TS', time());
    $config->saveConfig();
}

define('FURL_ENABLED', 1);
$urlEntry = new URL();
$urlEntry->loadFromServerInfo();

define('VAR_URL', 'URL');
$Register->set(VAR_URL, $urlEntry);

$_urlEntry = new URL();
$_urlEntry->loadFromServerInfo();

$furl_path = isset($_GET['__furl_path'])?$_GET['__furl_path']:'';

$Register->set('FURL_PATH', $furl_path);

if($furl_path == 'robots.txt') {
    header("HTTP/1.1 404 Not Found");
    header("Status: 404 Not Found");
    header("Content-type: text/html; charset=ISO-8859-1");
    echo '<!DOCTYPE HTML PUBLIC "-//IETF//DTD HTML 2.0//EN">'.
        '<html><head>'.
        '<title>404 Not Found</title>'.
        '</head><body>'.
        '<h1>Not Found</h1>'.
        '<p>The requested URL was not found on this server.</p>'.
        '</body></html>';
    die();
};

//$_urlEntry->setPath('/');
$_furl_path=$furl_path?substr($_SERVER["REQUEST_URI"],0,strpos($_SERVER["REQUEST_URI"],$furl_path)):$_SERVER["REQUEST_URI"];
$_furl_path=substr($_furl_path,strlen(WBS_INSTALL_PATH));

while (!strpos($_furl_path,'//')===false){
    $_furl_path=str_replace('//','/',$_furl_path);
}
$_furl_path=explode('/',$_furl_path);
if( (isset($_furl_path[0])&&strcmp(strtolower($_furl_path[0]),'shop')===0) ) {
    $_furl_path='/shop/';
} else {
    $_furl_path='/';
}

$_urlEntry->setPath(str_replace('//','/',WBS_INSTALL_PATH.$_furl_path));

$_urlEntry->setQuery('?');
$__url = preg_replace('/\/[^\/]+$/', '', $_urlEntry->getURI());

$CONF_FULL_SHOP_URL = $__url.((SystemSettings::get('FRONTEND')!='SC')?'shop/':'');

$__wa_url = $__url;

$pattern = '|^((http[s]{0,1}://([^/]+)/)'.substr(WBS_INSTALL_PATH,1).')|msi';
if(preg_match($pattern,$__url,$matches)){
    $_base_url = $matches[2];
    $WIDGET_SHOP_URL = $matches[1];
} else {
    $_base_url = $__url;
}
define('BASE_URL', $_base_url);
define('BASE_WA_URL', $WIDGET_SHOP_URL);
define('WIDGET_SHOP_URL',$WIDGET_SHOP_URL.((SystemSettings::get('FRONTEND')!='SC')?'shop/':''));
define('CONF_FULL_SHOP_URL',WIDGET_SHOP_URL);
unset($_base_url);
define('CONF_WAROOT_URL', WBS_INSTALL_PATH);

require_once(DIR_CFG.'/language_list.php');
require_once(DIR_FUNC.'/access_functions.php');
require_once(DIR_FUNC.'/sc_functions.php');
require_once(DIR_FUNC.'/category_functions.php');
require_once(DIR_FUNC.'/product_functions.php');
require_once(DIR_FUNC.'/statistic_functions.php');//*
require_once(DIR_FUNC.'/country_functions.php' );//*
require_once(DIR_FUNC.'/zone_functions.php' );//*
require_once(DIR_FUNC.'/datetime_functions.php' );
require_once(DIR_FUNC.'/picture_functions.php' );//*
require_once(DIR_FUNC.'/configurator_functions.php' );
require_once(DIR_FUNC.'/option_functions.php' );//*
require_once(DIR_FUNC.'/discount_functions.php' );
require_once(DIR_FUNC.'/custgroup_functions.php' );//*
require_once(DIR_FUNC.'/currency_functions.php' );
require_once(DIR_FUNC.'/module_function.php' );
require_once(DIR_FUNC.'/registration_functions.php' );
require_once(DIR_FUNC.'/order_amount_functions.php' );
require_once(DIR_FUNC.'/catalog_import_functions.php');//*
require_once(DIR_FUNC.'/cart_functions.php');
require_once(DIR_FUNC.'/subscribers_functions.php' );
require_once(DIR_FUNC.'/discussion_functions.php' );//*
require_once(DIR_FUNC.'/order_status_functions.php' );//*
require_once(DIR_FUNC.'/order_functions.php' );
require_once(DIR_FUNC.'/shipping_functions.php' );//*
require_once(DIR_FUNC.'/payment_functions.php' );//*
require_once(DIR_FUNC.'/reg_fields_functions.php' );//*
require_once(DIR_FUNC.'/tax_function.php' );//*
require_once(DIR_FUNC.'/onesteporder_functions.php');
require_once(DIR_FUNC.'/banners.php');
require_once(DIR_FUNC.'/translate_functions.php');
require_once(DIR_CLASSES.'/class.virtual.shippingratecalculator.php');
require_once(DIR_CLASSES.'/autoload.php');

require_once(DIR_CLASSES.'/universalparserconfig.php');
require_once(DIR_CLASSES.'/universalparseremailimport.php');
require_once(DIR_CLASSES.'/universalparserproduct.php');
require_once(DIR_CLASSES.'/universalparser.php');

require_once(DIR_CLASSES.'/cachecleaner.php');

require_once(WBS_DIR.'/kernel/includes/smarty3/Smarty.class.php');

require_once(DIR_FUNC.'/search_function.php' );

include_once($_SERVER['DOCUMENT_ROOT'].'/published/SC/html/scripts/includes/redirects.php');    
// indexRedirects(); 

$smarty = new Smarty(); //core smarty object

checkPath(DIR_COMPILEDTEMPLATES);
$smarty->compile_dir = DIR_COMPILEDTEMPLATES;
$smarty->template_dir = DIR_TPLS;
$smarty->cache_dir = DIR_SMARTY_CACHE;
$smarty->debugging = false;
$smarty->caching = false;
$smarty->force_compile = true;

$smarty_mail = new Smarty(); //for e-mails

checkPath(DIR_COMPILEDTEMPLATES);
$smarty_mail->compile_dir = DIR_COMPILEDTEMPLATES;
$smarty_mail->template_dir = DIR_TPLS;
$smarty_mail->cache_dir = DIR_SMARTY_CACHE;
$smarty_mail->caching = false;
$smarty_mail->force_compile = false;

define('VAR_SMARTY','Smarty');
$Register = &Register::getInstance();
$Register->set(VAR_SMARTY, $smarty);

$error404 = false;
ModulesFabric::initGlobalModules();

if(!MOD_REWRITE_SUPPORT and array_key_exists('productID', $_GET) and !array_key_exists('ukey', $_GET) && !array_key_exists('did', $_GET) )
{
    $_GET['ukey'] = 'product';
};
$max_cnt = 200;
$CurrDivision = null;

do {
    $defauldDidID = 0;
    $did = isset($_GET['did'])?$_GET['did']:$defauldDidID;

    if (isset($_GET['ukey'])&&$_GET['ukey']) {
        $did = DivisionModule::getDivisionIDByUnicKey($_GET['ukey']);

        set_query('did='.$did, '', true);
        if(!$did&&($_GET['ukey']!='category')&&(strpos($_GET['ukey'],'index.php')!==0)){
            $error404 = true;
        }
        // 404 для несуществующих категорий
        if($_GET['ukey'] == 'category' && !isset($_GET['categoryID'])) {
            $error404 = true;
        }
    }

    if (!$did) {
        if(!isset($furl1)){
            $furl1 = true;
            fURL::exec();
            continue;
        }
        $did = DivisionModule::getDivisionIDByUnicKey('TitlePage');
    }

    $CurrDivision = &DivisionModule::getDivision($did);
    if(!$CurrDivision->getID()){
        if(!isset($furl1)){
            $furl1 = true;
            fURL::exec();
            continue;
        }
        $CurrDivision->LinkDivisionUKey = 'TitlePage';
    }
} while(--$max_cnt>0 && (!is_object($CurrDivision) || !$CurrDivision->getID()));

if($max_cnt <= 0){
    die('Couldnt load divisions');
}


//select a new language?
if (isset($_POST['lang'])){
    LanguagesManager::setCurrentLanguage($_POST['lang']);
    RedirectSQ();
}
if (isset($_GET['lang'])){
    LanguagesManager::setCurrentLanguage($_GET['lang']);
    RedirectSQ('lang=');
}

$_lang_iso2 = getPostOrGetData('lang_iso2');
$_lang_iso2 = !empty($_lang_iso2)&&strlen($_lang_iso2)==2?$_lang_iso2:'ru';
$lang = LanguagesManager::getLanguageByISO2($_lang_iso2);
if($lang != null) {
    LanguagesManager::setCurrentLanguage($lang->id, true);
}

$smarty->assign('lang_list', $lang_list);
$smarty->assign('lang_iso2', $LanguageEntry->iso2);
$cur_lang = LanguagesManager::getCurrentLanguage();

$smarty->assign('lang_iso2',$cur_lang->iso2);

if($CurrDivision->LinkDivisionUKey !=''){
    $CurrDivision = &DivisionModule::getDivisionByUnicKey($CurrDivision->LinkDivisionUKey);
    set_query('&did='.$CurrDivision->getID().'&did=&ukey='.$CurrDivision->getUnicKey(), '', true);
}

$Register->set(VAR_CURRENTDIVISION, $CurrDivision);
$AdminDivID = DivisionModule::getDivisionIDByUnicKey('admin');
$AdminChild = $CurrDivision->isBranchOf($AdminDivID);
$admin_mode = ($CurrDivision->UnicKey == 'admin' || $AdminChild)&&($CurrDivision->UnicKey!=='test');
$Register->set('admin_mode', $admin_mode);

if(!isset($furl1) && !$admin_mode and MOD_REWRITE_SUPPORT){
    $furl1 = true;
    fURL::exec();
}

if( $admin_mode && ( !wbs_auth() || restrictAcessByIP() == false ) ) {
    if (restrictAcessByIP() == false) {
        Redirect('/');
        die();
    } else {
        adminAuth();
    }
}

$LanguageEntry = &LanguagesManager::getCurrentLanguage();

$smarty->assign('BREADCRUMB_DELIMITER', '&raquo;');

if( ($admin_mode) && sc_getSessionData('LANGUAGE_ID') && sc_getSessionData('LANGUAGE_ID')!=$LanguageEntry->id){
    LanguagesManager::setCurrentLanguage(sc_getSessionData('LANGUAGE_ID'));
}

$locals = $LanguageEntry->getLocals(array(
    $admin_mode?LOCALTYPE_BACKEND:LOCALTYPE_FRONTEND, 
    LOCALTYPE_GENERAL, 
    LOCALTYPE_HIDDEN
), false, false);

$smarty->assign('lang_direction',$LanguageEntry->direction);

$Register->set('CURRLANG_LOCALS', $locals);
$Register->set('CURR_LANGUAGE', $LanguageEntry);

$DefLanguageEntry = ClassManager::getInstance('Language');
$DefLanguageEntry->loadById(CONF_DEFAULT_LANG);
$deflocals = $DefLanguageEntry->getLocals(array($admin_mode?LOCALTYPE_BACKEND:LOCALTYPE_FRONTEND, LOCALTYPE_GENERAL, LOCALTYPE_HIDDEN), false, false);

$Register->set('DEFLANG_LOCALS', $deflocals);
$Register->set('DEF_LANGUAGE', $DefLanguageEntry);

$rMonths = array(
    1=>translate('str_month_january'), 2=>translate('str_month_february'), 3=>translate('str_month_march'), 4=>translate('str_month_april'), 5=>translate('str_month_may'), 6=>translate('str_month_june'), 7=>translate('str_month_july'), 8=>translate('str_month_august'), 9=>translate('str_month_september'), 10=>translate('str_month_october'), 11=>translate('str_month_november'), 12=>translate('str_month_december'),
);
$rWeekDays = array(
    0=>translate('str_week_monday'),
    1=>translate('str_week_tuesday'),
    2=>translate('str_week_wednesday'),
    3=>translate('str_week_thursday'),
    4=>translate('str_week_friday'),
    5=>translate('str_week_saturday'),
    6=>translate('str_week_sunday'),
);
include_once(DIR_INCLUDES.'/handler.message.php');

$CurrDivision->loadCustomSettings();

$smarty->assign('CurrentDivision', array(
    'id' => $CurrDivision->ID,
    'name' => $CurrDivision->Name,
    'parentID' =>$CurrDivision->ParentID,
    'ukey' => $CurrDivision->UnicKey,
));

$smarty_mail->template_dir = DIR_TPLS.'/email';

if ($admin_mode) {
    //admin MODE

    $AdminDeps = [];
    $SubDivs = &DivisionModule::getBranchDivisions($AdminDivID, array('xEnabled'=>1));

    foreach ($SubDivs as $_SubDiv){
        $AdminDeps[] = array(
            'id' => $_SubDiv->ID,
            'name' => $_SubDiv->Name,
        );
    }
    $BreadDivs = $CurrDivision->getBreadsToID($AdminDivID);
    if(count($BreadDivs)>1){
        $CurrDptID = $BreadDivs[1]->ID;
    } else {
        $CurrDptID = $CurrDivision->ID;
    }
    sc_checkLoggedUserAccess2Division($CurrDivision, $BreadDivs);

    //проверям права на удаления
    $wbs_username = $_SESSION['wbs_username'];
    $wbs_delete_rights = 0;
    if (isset($wbs_username) && !empty($wbs_username)) {
        $wbs_delete_rights = db_phquery_fetch(DBRFETCH_FIRST, ' SELECT VALUE FROM USER_SETTINGS WHERE NAME=? AND U_ID=? ', 'DELETE_RIGHTS', $wbs_username);
    }
    $smarty->assign('wbs_delete_rights', $wbs_delete_rights);

    if($CurrDivision->UnicKey!='admin'){
        $smarty->assign('SubDivs', DivisionModule::getBranchDivisions($CurrDptID, array('xEnabled'=>1)));
    }
    $smarty->assign('current_dpt', $CurrDptID);

    $smarty->assign('admin_departments', $AdminDeps);
    $smarty->assign('admin_departments_count', count($AdminDeps));
    $smarty->assign('safemode', 0);

    $U_ID = $_SESSION['wbs_username'];

    $top_menu = array();
    $admin_divisions = sc_getAdminDivs();
    $defauldDidID = isset($_SESSION['admin_latest_did'])&&intval($_SESSION['admin_latest_did'])>0?intval($_SESSION['admin_latest_did']):sc_getDefaultDivisionID();
    $curentDid = isset($_GET['did'])?intval($_GET['did']):$defauldDidID;
    $_SESSION['admin_latest_did'] = $curentDid;

    $is_first = true;
    foreach ($admin_divisions as $_div){

        $i = array(
            'title' => $_div['xName'],
            'id' => $_div['xID'],
            'active' => ($curentDid==$_div['xID']?true:$is_first),
            'url' => '/admin/?did='.$_div['xID'],
            'direct_url' => '/admin/?did='.$_div['xID'],
            'sub_tabs' => array()
        );
        $is_first = false;
        if(is_array($_div['sub_divs'])){
            $is_first_ = true;
            $curentDid_ = sc_getDefaultChildDivisionID($_div['xID']);

            foreach ($_div['sub_divs'] as $__div){
                $exist_ar_id = db_phquery_fetch(DBRFETCH_FIRST, ' SELECT AR_ID FROM U_ACCESSRIGHTS WHERE AR_ID=? AND AR_PATH=? AND AR_OBJECT_ID=? ',
                    $U_ID, '/ROOT/SC/FUNCTIONS', 'SC__'.intval($__div['xID']) );
                $exist_ar_id = isset($exist_ar_id)&&!empty($exist_ar_id)?true:false;
                if($exist_ar_id == false) {
                    continue;
                }
                $i['sub_tabs'][] = array(
                    'title' => $__div['xName'],
                    'id' => $__div['xID'],
                    'active' => $curentDid==$__div['xID'],
                    'url' => '/admin/?did='.$__div['xID'],
                    'direct_url' => '/admin/?did='.$__div['xID'],
                );
            }
        }
        if(!count($i['sub_tabs']))continue;
        $top_menu[] = $i;
    }
    $smarty->assign('top_menu', $top_menu);
    $sub_tab_id = isset($_GET['did'])?$_GET['did']:sc_getDefaultDivisionID();
    $top_tab_disivion = sc_getParentDivision($sub_tab_id);
    if ($top_tab_disivion['xUnicKey'] == 'admin'){
        $top_tab_id = $sub_tab_id;
        $sub_tab_id = sc_getDefaultChildDivisionID($top_tab_id);
    } else {
        $top_tab_id = $top_tab_disivion['xID'];
    }
    $smarty->assign('top_tab_id', $top_tab_id);
    $smarty->assign('sub_tab_id', $sub_tab_id);

    $smarty->assign('admin_main_content_template', 'nav2level.tpl.html');

    $smarty->template_dir = DIR_TPLS;

} else {
    //not admin

    $customerEntry = Customer::getAuthedInstance();
    $smarty->assign('customer_entry', (array)$customerEntry);

    $sale_categories = db_phquery_fetch(DBRFETCH_ASSOC_ALL, ' SELECT c.categoryID, c.slug, c.products_count, c.sale_menu_icon, c.name_ua, c.name_en, c.name_ru
																FROM SC_categories AS c 
																WHERE c.hidden = 0 AND c.parent=1 AND c.sale_category=1 AND c.products_count > 0
																ORDER BY c.name_ru ASC 
														');
    foreach ($sale_categories as $key => $_cat) {
        LanguagesManager::ml_fillFields(CATEGORIES_TABLE, $_cat);
        $sale_categories[$key] = $_cat;
    }
    $smarty->assign('sale_categories', $sale_categories);

    $root_categories = db_phquery_fetch(DBRFETCH_ASSOC_ALL, ' SELECT c.name_ua, c.name_en, c.name_ru, c.expanded, c.categoryID, c.slug, c.redirect 
                                                                FROM SC_categories AS c 
                                                                WHERE c.parent=1 AND c.type=1 AND c.sale_category=0 AND c.hidden=0 
                                                                ORDER BY c.sort_order ');
    foreach ($root_categories as $key => $_cat) {
        LanguagesManager::ml_fillFields(CATEGORIES_TABLE, $_cat);
        $root_categories[$key] = $_cat;
    }
    $smarty->assign('root_categories', $root_categories);

    $root_cats = db_phquery_fetch(DBRFETCH_ASSOC_ALL, 'SELECT c.name_ua, c.name_ua, c.name_ru, c.expanded, c.categoryID, c.slug, c.redirect 
                                                        FROM SC_categories AS c 
                                                        WHERE c.parent=1 AND c.type=1 AND c.sale_category=0 AND c.hidden=0 
                                                        ORDER BY IF(c.sort_order=0,1,0) ASC, c.sort_order ASC ');
    foreach ($root_cats as $key => $_cat) {
        LanguagesManager::ml_fillFields(CATEGORIES_TABLE, $_cat);
        $root_cats[$key] = $_cat;
    }

function get_picture_by_category ($cat_id) {
    'SELECT sc_products.productID, sc_products.categoryID, sc_products.default_picture, sc_products.viewed_times, sc_products.name_ru, sc_product_pictures.photoID, sc_product_pictures.productID, sc_product_pictures.filename, sc_product_pictures.enlarged 
    FROM sc_products 
    INNER JOIN sc_product_pictures 
    ON sc_products.productID=sc_product_pictures.productID 
    WHERE sc_products.categoryID=591 
    AND sc_products.viewed_times>10 
    LIMIT 1';

}






function get_sub_categories_mob ($cat_id) {
    $sql = "SELECT * FROM SC_categories WHERE parent = ('$cat_id')";
    $q = db_query($sql);
    while ($row = db_fetch_assoc($q)) {
        $data[] = $row;
    }
    return $data;
    }



function get_children_cats ($cat_id) {
    $sql = "SELECT * FROM SC_categories INNER JOIN SC_category_links ON SC_categories.categoryID = SC_category_links.categoryID WHERE SC_category_links.rootcatID = ('$cat_id')";
    $q = db_query($sql);
    while ($row = db_fetch_assoc($q)) {
       $sub_category_id = get_sub_categories_mob ($row['categoryID']);
       $row['sub_cat'] =  $sub_category_id;
    $data[] =$row;
}
return $data;
}

$sql_main_cat = "SELECT * FROM SC_categories WHERE type = 1";
$q_main_cat = db_query($sql_main_cat);
$data_main_cat = array();
while ($row = db_fetch_assoc($q_main_cat)) {
    $cat_children = get_children_cats ($row['categoryID']);
    $row['children'] = $cat_children;

$cat_pcture = get_picture_by_category ($row['categoryID']);
$row['cat_picture'] = $cat_pcture;





    $data_main_cat[] =$row;
}
$smarty -> assign('menu_mob', $data_main_cat);





    $smarty->assign('root_cats', $root_cats);

    $smarty->assign('PAGE_VIEW', isset($GetVars['view'])?$GetVars['view']:'');

    $smarty->assign('main_content_template', 'home.html');

    $smarty->assign('isHTTPS', isHTTPS());
    $smarty->assign('isOptimizer', (isset($_SERVER['HTTP_USER_AGENT']) && isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) && $_SERVER['HTTP_ACCEPT_LANGUAGE'] == 'en-US' && strpos($_SERVER['HTTP_USER_AGENT'], 'Mozilla/5.0 (Linux; Android 6.0.1; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/74.0.3694.0 Mobile Safari/537.36 Chrome-Lighthouse') !== false));


    include(DIR_ROOT.'/includes/authorization.php');

    $smarty->assign('categoryID', isset($_GET['categoryID'])?intval($_GET['categoryID']):0);
    $smarty->template_dir = DIR_FTPLS;

}

$InheritableInterfaces = $CurrDivision->getInheritableInterfaces();
$Interfaces = $CurrDivision->getInterfaces();

if(!$error404){
    foreach ($InheritableInterfaces as $_Interface){
        ModulesFabric::callInterface($_Interface);
    }

    foreach ($Interfaces as $_Interface){
        ModulesFabric::callInterface($_Interface);
    }
}


$smarty->assign('Warnings', $Warnings);
$smarty->assign( "https_connection_flag", $urlEntry->getScheme()=='https');

$page = '';
if($error404){
    $smarty->assign('page_title','404 '.translate('err_cant_find_required_page'));
    error404page();
    $smarty->assign('page_not_found404', true);
}

$meta_robots = $Register->get('document')->getMetaRobots();
if (!empty($meta_robots)) {
    $smarty->assign('meta_robots', $meta_robots);
}

$category_messages_popup_json = getPageMessages();

$smarty->assign('category_messages_popup_json', getPageMessageJsonFormat($category_messages_popup_json));

if ($CurrDivision->MainTemplate) {
    if(isset($GetVars['view']) && ($GetVars['view'] == 'nohtmlframe'||$GetVars['view'] == 'noframe'||$GetVars['view'] == 'printable')){
        if ($GetVars['view'] == 'nohtmlframe') {
            $page = $smarty->fetch($smarty->getTemplateVars('main_content_template'));
            echo $page;

            // Output save
            if (isset($pagecache) && $pagecache->OkToCache()) {             //V2PAGECACHE
                $pagecache->CachePage($page);                           	//V2PAGECACHE
            }
            die();
        } else {
            $smarty->assign('main_body_tpl', $smarty->getTemplateVars('main_content_template'));
        }
    }

    ini_set('display_errors', true);

    $page .= $smarty->fetch($CurrDivision->MainTemplate);
}

//DEBUG futures
if (!isset($_SESSION['WBS_ACCESS_SC']) || intval($_SESSION['WBS_ACCESS_SC']) == 0) {
    if(isset($_GET['debug'])&& ($_GET['debug']=='time' || $_GET['debug']=='total_time')){
        $page .= 'time: <strong>'.$T->timerStop().'</strong><br />';
    }
}



// Output save
if (isset($pagecache) && $pagecache->OkToCache()) {             //V2PAGECACHE
    $page = $pagecache->CachePage($page);                           	//V2PAGECACHE
}

print $page;
