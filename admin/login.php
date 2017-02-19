<?php

require_once 'config.php';
if ($_CONFIG['debug_mode'] === true) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(E_ALL ^ E_NOTICE);
}
require_once 'users/users.php';
require_once 'class.php';

$timeconfig = SetUp::getConfig('default_timezone');
$timezone = (strlen($timeconfig) > 0) ? $timeconfig : "UTC";
date_default_timezone_set($timezone);

$_ERROR = false;
$_WARNING = false;

$firstrun = SetUp::getConfig('firstrun');
$script_url = SetUp::getConfig('script_url');

$resetconfig = false;
$resetusr = false;

if ($firstrun || !$script_url) {
    $actual_link = SetUp::getAppUrl();
    $_CONFIG['script_url'] = $actual_link;
    $_CONFIG['firstrun'] = false;
    $resetconfig = true;
}

if (strlen($_CONFIG['session_name']) < 5) {
    $session = "fm_".strval(mt_rand());
    $_CONFIG['session_name'] = $session;
    $resetconfig = true;
}

if (strlen($_CONFIG['salt']) < 5) {
    $_CONFIG['salt'] = md5(mt_rand());
    $resetusr = true;
}
session_name($_CONFIG["session_name"]);
session_start();

if (isset($_GET['logout'])) {
    setcookie("rm", "", time() -(60*60*24*365));
    $_SESSION['fm_user_name'] = null;
    $_SESSION['fm_logged_in'] = null;
    $_SESSION['fm_user_space'] = null;
    $_SESSION['fm_user_used'] = null;
    $_SESSION['fm_dlist'] = null;
    header('Location:login.php');
    exit;
}


if (strlen($_USERS[0]['pass']) < 1 || $resetusr === true) {
    $reset = crypt($_CONFIG['salt'].urlencode('password'), Utils::randomString());
    $_USERS[0]['pass'] = $reset;
    $usr = '$_USERS = ';
    if (false == (file_put_contents(
        'users/users.php', "<?php\n\n $usr".var_export($_USERS, true).";\n"
    ))
    ) {
        $_ERROR = "Error writing on <strong>/users/users.php</strong>, check CHMOD settings";
    }
}

if ($resetusr === true || $resetconfig === true) {
    $con = '$_CONFIG = ';
    if (false == (file_put_contents(
        'config.php', "<?php\n\n $con".var_export($_CONFIG, true).";\n"
    ))
    ) {
        $_ERROR = "Error writing on <strong>/config.php</strong>, check CHMOD settings";
    }
}

if (isset($_GET['lang'])) {
    $lang = $_GET['lang'];
    $_SESSION['lang'] = $_GET['lang'];
}
if (isset($_SESSION['lang'])) {
    $lang = $_SESSION['lang'];
} else {
    $lang = $_CONFIG["lang"];
}
require_once 'translations/'.$lang.'.php';

$encodeExplorer = new EncodeExplorer();
$setUp = new SetUp();

$template = new Template();
$gateKeeper = new GateKeeper();

$postusername = filter_input(
    INPUT_POST, "fm_admin_name", FILTER_SANITIZE_STRING
);
$postuserpass = filter_input(
    INPUT_POST, "fm_admin_pass", FILTER_SANITIZE_STRING
);

if ($postusername && $postuserpass) {
        
    $postcaptcha = filter_input(
        INPUT_POST, "captcha", FILTER_SANITIZE_STRING
    );

    if (Utils::checkCaptcha($postcaptcha) == true) {
        if ($gateKeeper->isUser($postusername, $postuserpass)) {
            $_SESSION['fm_user_name'] = $postusername;
            $_SESSION['fm_logged_in'] = 1;

            $usedspace = $gateKeeper->getUserSpace();

            if ($usedspace !== false) {
                $userspace = $gateKeeper->getUserInfo('quota')*1024*1024;
                $_SESSION['fm_user_used'] = $usedspace;
                $_SESSION['fm_user_space'] = $userspace;
            } else {
                $_SESSION['fm_user_used'] = null;
                $_SESSION['fm_user_space'] = null;
            }
            
        } else {
            $_ERROR = $encodeExplorer->getString("wrong_pass");
        }
    } else {
        $_WARNING = $encodeExplorer->getString("wrong_captcha");
    }
}
if (isset($_SESSION['fm_logged_in']) && $_SESSION['fm_logged_in'] === 1 && !$gateKeeper->isSuperAdmin()) {
    $_ERROR = $encodeExplorer->getString("access_denied");
}

if ($gateKeeper->isSuperAdmin()) {
    header('Location:index.php');
    exit;
}

if (isset($_SESSION['error'])) {
    $_ERROR = $_SESSION['error'];
    $alertclass = "danger";
    unset($_SESSION['error']);
}
if (isset($_SESSION['warning'])) {
    $_WARNING = $_SESSION['warning'];
    $alertclass = "warning";
    unset($_SESSION['warning']);
} ?>
<!DOCTYPE HTML>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<meta http-equiv="Content-Language" content="<?php echo $lang; ?>" />
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<link rel="shortcut icon" href="images/favicon.ico">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1">
<title>Login | <?php print $setUp->getConfig('appname'); ?></title>
	<link href="https://fonts.googleapis.com/css?family=Roboto:300,400,500,700" rel="stylesheet">
	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
	<link rel="stylesheet" href="css/mdb.min.css">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="skins/<?php print $setUp->getConfig('skin'); ?>">
    <script src="js/jquery.min.js"></script>
</head>
<body>
<?php
    $template->getPart('navbar', '');
    $template->getPart('header', '');
?>
<div class="container">
    <section class="fmblock">
        <div class="login">
        <?php 
        if ($_ERROR) { ?>
            <div class="alert alert-danger" role="alert"><?php echo $_ERROR; ?></div>
        <?php
        } ?>
        <?php 
        if ($_WARNING) { ?>
            <div class="alert alert-warning" role="alert"><?php echo $_WARNING; ?></div>
        <?php
        } ?>
            <div class="panel panel-default">
                <div class="panel-heading">
                    <i class="fa fa-cogs"></i> 
                    <?php print $encodeExplorer->getString('administration'); ?>
                </div>
                <div class="panel-body">
                    <form enctype="multipart/form-data" 
                    method="post" role="form" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                        <div class="form-group">
                            <label class="sr-only" for="fm_user_name">
                                <?php print $encodeExplorer->getString('username'); ?>
                            </label>
                            <div class="input-group">
                                <span class="input-group-addon"><i class="fa fa-user fa-fw"></i></span>
                                <input type="text" name="fm_admin_name" 
                                value="" class="form-control ricevi1" 
                                placeholder="<?php echo $encodeExplorer->getString('username'); ?>" />
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="sr-only" for="fm_user_pass">
                                <?php print $encodeExplorer->getString('password'); ?>
                            </label>

                            <div class="input-group">
                                <span class="input-group-addon"><i class="fa fa-lock fa-fw"></i></span>
                                <input type="password" name="fm_admin_pass" 
                                class="form-control ricevi2" 
                                placeholder="<?php print $encodeExplorer->getString('password'); ?>" />
                            </div>
                        </div>
                        <?php 
                        /* CAPTCHA*/
                        if ($setUp->getConfig('show_captcha') == true ) { 
                            $capath = '';
                            include 'include/captcha.php'; 
                        }   ?>
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary btn-block" />
                                <i class="fa fa-sign-in"></i> 
                                <?php print $encodeExplorer->getString('log_in'); ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            <p><a href="../"><i class="fa fa-home"></i> 
                <?php print $setUp->getConfig('appname'); ?></a>
            </p>
        </div>
    </section>
</div>
    <?php $template->getPart('footer', ''); ?>
    <script type="text/javascript" src="js/bootstrap.min.js"></script>
</body>
</html>