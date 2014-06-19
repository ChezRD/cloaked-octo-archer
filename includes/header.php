<?php session_start(); ?>
<?php //ini_set('display_errors', 'On'); ?>
<?php if (!$_SESSION['logged_in']) { header("Location: login.php"); } ?>
<?php require_once "$INC_DIR/xcrud/xcrud/xcrud.php"; ?>

<!DOCTYPE html>
<!--
To change this license header, choose License Headers in Project Properties.
To change this template file, choose Tools | Templates
and open the template in the editor.
-->
<html>
<head>
    <title>UC Admin Tools</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Bootstrap -->
    <link href="/css/bootstrap-hover-menu.css" rel="stylesheet" media="screen">
    <link href="/css/bootstrap.min.css" rel="stylesheet" media="screen">
    <link href="/custom/css/sticky-footer-navbar.css" rel="stylesheet" media="screen">
    <link href="/custom/css/custom.css" rel="stylesheet" media="screen">
    <?php echo Xcrud::load_css(); ?>
</head>
<body>
<div id="wrap">
    <nav class="navbar-wrapper navbar-default navbar-left navbar-fixed-top " role="navigation">
        <div class="container-fluid">
            <div class="navbar-header">
                <l1><a class="navbar-brand" href="/index.php"><span class="glyphicon glyphicon-home right-margin-glyp"></span> <b class="text-danger">Home</b></a></l1>
            </div>
            <div class="collapse navbar-collapse">
                <ul class="nav navbar-nav">
                    <?php if ($_SESSION['role'] == '1'): ?>
                    <li class="dropdown">
                        <a href="javascript:void(0)" class="dropdown-toggle <?php echo preg_match('/check/i', basename($_SERVER['SCRIPT_FILENAME'])) ? 'active': '';?>"><span class="glyphicon right-margin-glyph"></span>Phone Web Check</a>
                        <ul class="dropdown-menu">
                            <li><a href="checkWebSingle.php"><span class="glyphicon"></span>Single Phone</a></li>
                            <li><a href="checkWebBulk.php"><span class="glyphicon"></span>Load Bulk Phones</a></li>
                            <li><a href="checkWebReport.php"><span class="glyphicon"></span>Report Phones</a></li>
                        </ul>
                    </li>
                    <li class="dropdown">
                        <a href="javascript:void(0)" class="dropdown-toggle <?php echo preg_match('/ctl/i', basename($_SERVER['SCRIPT_FILENAME'])) ? 'active': '';?>"><span class="glyphicon right-margin-glyph"></span>CTL Remover</a>
                        <ul class="dropdown-menu">
                            <li><a href="ctlSingle.php"><span class="glyphicon"></span>Single Phone</a></li>
                            <li><a href="ctlBulk.php"><span class="glyphicon"></span>Load Bulk Phones</a></li>
                            <li><a href="ctlReport.php"><span class="glyphicon"></span>Report Phones</a></li>
                        </ul>
                    </li>
                    <li class="dropdown">
                        <a href="javascript:void(0)" class="dropdown-toggle <?php echo preg_match('/reset/i', basename($_SERVER['SCRIPT_FILENAME'])) ? 'active': '';?>"><span class="glyphicon right-margin-glyph"></span>Reset Phones</a>
                        <ul class="dropdown-menu">
                            <li><a href="resetSingle.php"><span class="glyphicon"></span>Single Phone</a></li>
                            <li><a href="resetBulk.php"><span class="glyphicon"></span>Load Bulk Phones</a></li>
                            <li><a href="resetReport.php"><span class="glyphicon"></span>Report Phones</a></li>
                        </ul>
                    </li>
                    <li class="dropdown">
                        <a href="javascript:void(0)" class="dropdown-toggle <?php echo preg_match('/dialer/i', basename($_SERVER['SCRIPT_FILENAME'])) ? 'active': '';?>"><span class="glyphicon right-margin-glyph"></span>Dial Plan Tester</a>
                        <ul class="dropdown-menu">
                            <li><a href="dialerSingle.php"><span class="glyphicon"></span>Single Phone</a></li>
                            <li><a href="dialerBulk.php"><span class="glyphicon"></span>Load Bulk Phones</a></li>
                            <li><a href="dialerReport.php"><span class="glyphicon"></span>Report Phones</a></li>
                        </ul>
                    </li>
                    <?php endif ?>
                </ul>
                <ul class="nav navbar-nav navbar-right">
                    <li><a href="logout.php"><span class="glyphicon glyphicon-asterisk text-danger"></span> Log Out</a></li>
                </ul>
                <ul class="nav navbar-nav navbar-right">
                    <li><a href="adminOptions.php?username=<?php echo $_SESSION['username']; ?>" ><span class="text-info"></span> Hello <b class="text-info"><?php echo $_SESSION['username']; ?></b></a></li>
                </ul>
            </div>
        </div>
    </nav>
    <div class="container">
