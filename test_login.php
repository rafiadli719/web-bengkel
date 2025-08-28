<?php
session_start();

// Simple test login - bypass database for testing mechanic fields
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['txtnama'] ?? '';
    $password = $_POST['txtpass'] ?? '';
    $cabang = $_POST['cbocabang'] ?? 'CAB001';
    
    // Simple test credentials
    if ($username === 'admin' && $password === 'admin') {
        $_SESSION['_login'] = 'OK';
        $_SESSION['_nama'] = 'Administrator Test';
        $_SESSION['_username'] = 'admin';
        $_SESSION['_level'] = 'admin';
        $_SESSION['_kode_cabang'] = $cabang;
        $_SESSION['nama_cabang'] = 'Test Cabang';
        
        // Redirect to admin cabinet
        header('Location: _admincab/index.php');
        exit;
    } else {
        $error = "Username atau password salah! Gunakan: admin/admin";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>FIT MOTOR - Test Login</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link href="https://fonts.googleapis.com/css?family=Raleway" rel="stylesheet">
    <style>
        a { text-decoration: none; }
        body { background-image: url('img/logo.jpg'); background-repeat: no-repeat; background-size: cover; }
        label { font-family: "Raleway", sans-serif; font-size: 11pt; }
        #card { background: #fbfbfb; border-radius: 8px; box-shadow: 1px 2px 8px rgba(0, 0, 0, 0.65); height: 450px; margin: 6rem auto 8.1rem auto; width: 329px; }
        #card-content { padding: 12px 44px; }
        #card-title { font-family: "Raleway Thin", sans-serif; letter-spacing: 4px; padding-bottom: 23px; padding-top: 13px; text-align: center; }
        #submit-btn { background: -webkit-linear-gradient(right, #a6f77b, #2dbd6e); border: none; border-radius: 21px; box-shadow: 0px 1px 8px #24c64f; cursor: pointer; color: white; font-family: "Raleway SemiBold", sans-serif; height: 42.3px; margin: 0 auto; margin-top: 30px; transition: 0.25s; width: 153px; }
        #submit-btn:hover { box-shadow: 0px 1px 18px #24c64f; }
        .form { align-items: left; display: flex; flex-direction: column; }
        .form-border { background: -webkit-linear-gradient(right, #a6f77b, #2ec06f); height: 1px; width: 100%; }
        .form-content { background: #fbfbfb; border: none; outline: none; padding-top: 14px; }
        .underline-title { background: -webkit-linear-gradient(right, #a6f77b, #2ec06f); height: 2px; margin: -1.1rem auto 0 auto; width: 89px; }
        .error { color: red; font-family: "Raleway", sans-serif; font-size: 10pt; text-align: center; margin: 10px 0; }
        .info { color: blue; font-family: "Raleway", sans-serif; font-size: 9pt; text-align: center; margin: 10px 0; }
    </style>
</head>
<body>
    <div id="card">
        <div id="card-content">
            <div id="card-title">
                <h2>TEST LOGIN</h2>
                <div class="underline-title"></div>
            </div>
            <?php if (isset($error)): ?>
                <p class="error"><?php echo $error; ?></p>
            <?php endif; ?>
            <p class="info">Test Credentials:<br>Username: admin<br>Password: admin</p>
            <form method="post" class="form">
                <label for="txtnama" style="padding-top:13px">User Name</label>
                <input name="txtnama" class="form-content" type="text" autocomplete="off" required />
                <div class="form-border"></div>
                <label for="user-password" style="padding-top:22px">Password</label>
                <input id="user-password" class="form-content" type="password" name="txtpass" required autocomplete="off" />
                <div class="form-border"></div>
                <label for="cbocabang" style="padding-top:13px">Cabang</label>
                <select class="form-content" name="cbocabang" id="cbocabang">
                    <option value="CAB001">Test Cabang</option>
                </select>
                <div class="form-border"></div>
                <input id="submit-btn" type="submit" name="submit" value="LOGIN" />
            </form>
        </div>
    </div>
</body>
</html>
