<?php
/**
 * Shared ACE-theme header buat modul komplain — samain sama servis-reguler.php
 * dkk (navbar + sidebar RBAC + breadcrumb). Include SETELAH koneksi_komplain.php.
 * Set $pageTitle sebelum include ini.
 */
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php include "../../lib/titel.php"; ?> — <?= htmlspecialchars($pageTitle ?? 'Modul Komplain') ?></title>

    <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/font-awesome/4.5.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="../assets/css/jquery-ui.custom.min.css">
    <link rel="stylesheet" href="../assets/css/fonts.googleapis.com.css">
    <link rel="stylesheet" href="../assets/css/ace.min.css" id="main-ace-style">
    <link rel="stylesheet" href="../assets/css/ace-skins.min.css">
    <link rel="stylesheet" href="../assets/css/ace-rtl.min.css">
</head>

<body class="no-skin">
    <!-- Navbar -->
    <div id="navbar" class="navbar navbar-default ace-save-state">
        <div class="navbar-container ace-save-state" id="navbar-container">
            <button type="button" class="navbar-toggle menu-toggler pull-left" id="menu-toggler" data-target="#sidebar">
                <span class="sr-only">Toggle sidebar</span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
            </button>

            <div class="navbar-header pull-left">
                <a href="../index.php" class="navbar-brand">
                    <small><?php include "../../lib/logo.php"; ?> <?php include "../../lib/subtitel.php"; ?></small>
                </a>
            </div>

            <div class="navbar-buttons navbar-header pull-right">
                <ul class="nav ace-nav">
                    <li class="light-blue dropdown-modal">
                        <a data-toggle="dropdown" href="#" class="dropdown-toggle">
                            <img class="nav-user-photo" src="../../<?= htmlspecialchars($foto_user ?? '') ?>" alt="User Profile">
                            <span class="user-info"><small>Welcome,</small> <?= htmlspecialchars($_nama ?? '') ?></span>
                            <i class="ace-icon fa fa-caret-down"></i>
                        </a>
                        <ul class="user-menu dropdown-menu-right dropdown-menu dropdown-yellow dropdown-caret dropdown-close">
                            <li><a href="../change_pwd.php"><i class="ace-icon fa fa-cog"></i> Change Password</a></li>
                            <li><a href="../profile.php"><i class="ace-icon fa fa-user"></i> Profile</a></li>
                            <li class="divider"></li>
                            <li><a href="../logout.php"><i class="ace-icon fa fa-power-off"></i> Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Main Container -->
    <div class="main-container ace-save-state" id="main-container">
        <div id="sidebar" class="sidebar responsive ace-save-state">
            <?php include "../menu_dashboard.php"; ?>
            <div class="sidebar-toggle sidebar-collapse" id="sidebar-collapse">
                <i id="sidebar-toggle-icon" class="ace-icon fa fa-angle-double-left ace-save-state"></i>
            </div>
        </div>

        <div class="main-content">
            <div class="main-content-inner">
                <div class="breadcrumbs ace-save-state" id="breadcrumbs">
                    <ul class="breadcrumb">
                        <li><i class="ace-icon fa fa-home home-icon"></i> <a href="../index.php">Home</a></li>
                        <li><a href="input.php">Penanganan Komplain</a></li>
                        <li class="active"><?= htmlspecialchars($pageTitle ?? '') ?></li>
                    </ul>
                </div>

                <div class="page-content">
                    <div class="page-header">
                        <h1><?= htmlspecialchars($pageTitle ?? '') ?></h1>
                    </div>
