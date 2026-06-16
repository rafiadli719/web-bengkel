<?php
http_response_code(403);
?><!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0" />
  <title>403 - Akses Ditolak</title>
  <link rel="stylesheet" href="assets/css/bootstrap.min.css" />
  <link rel="stylesheet" href="assets/font-awesome/4.5.0/css/font-awesome.min.css" />
  <link rel="stylesheet" href="assets/css/ace.min.css" />
  <style>
    body { background: #f5f5f5; }
    .error-container { max-width: 600px; margin: 80px auto; text-align: center; }
    .error-actions { margin-top: 20px; }
  </style>
</head>
<body class="no-skin">
  <div class="error-container">
    <h1 class="grey lighter smaller">
      <span class="blue bolder">403</span>
      Akses Ditolak
    </h1>
    <hr />
    <h3 class="lighter">Anda tidak memiliki hak akses untuk halaman ini.</h3>
    <div class="space"></div>
    <div class="error-actions">
      <a href="index.php" class="btn btn-primary"><i class="fa fa-home"></i> Kembali ke Dashboard</a>
      <a href="role_permissions.php" class="btn btn-default"><i class="fa fa-lock"></i> Kelola Hak Akses</a>
    </div>
  </div>
  <script src="assets/js/jquery-2.1.4.min.js"></script>
  <script src="assets/js/bootstrap.min.js"></script>
</body>
</html>
