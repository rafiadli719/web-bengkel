<?php
    include "../config/koneksi.php";
?>

<!DOCTYPE html>
<html>

<head>
  <meta charset="utf-8" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <title>FIT MOTOR</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
 <link href="https://fonts.googleapis.com/css?family=Raleway" rel="stylesheet">
  <style>
a {
  text-decoration: none;
}
body {
	background-image: url('img/sales-dashboard.jpg');
    background-repeat: no-repeat;
background-size: cover;
}
label {
  font-family: "Raleway", sans-serif;
  font-size: 11pt;
}
#forgot-pass {
  color: #2dbd6e;
  font-family: "Raleway", sans-serif;
  font-size: 10pt;
  margin-top: 3px;
  text-align: right;
}
#card {
  background: #fbfbfb;
  border-radius: 8px;
  box-shadow: 1px 2px 8px rgba(0, 0, 0, 0.65);
  height: 410px;
  margin: 6rem auto 8.1rem auto;
  width: 329px;
}
#card-content {
  padding: 12px 44px;
}
#card-title {
  font-family: "Raleway Thin", sans-serif;
  letter-spacing: 4px;
  padding-bottom: 23px;
  padding-top: 13px;
  text-align: center;
}
#signup {
  color: #2dbd6e;
  font-family: "Raleway", sans-serif;
  font-size: 10pt;
  margin-top: 16px;
  text-align: center;
}
#submit-btn {
  background: -webkit-linear-gradient(right, #a6f77b, #2dbd6e);
  border: none;
  border-radius: 21px;
  box-shadow: 0px 1px 8px #24c64f;
  cursor: pointer;
  color: white;
  font-family: "Raleway SemiBold", sans-serif;
  height: 42.3px;
  margin: 0 auto;
  margin-top: 50px;
  transition: 0.25s;
  width: 153px;
}
#submit-btn:hover {
  box-shadow: 0px 1px 18px #24c64f;
}
.form {
  align-items: left;
  display: flex;
  flex-direction: column;
}
.form-border {
  background: -webkit-linear-gradient(right, #a6f77b, #2ec06f);
  height: 1px;
  width: 100%;
}
.form-content {
  background: #fbfbfb;
  border: none;
  outline: none;
  padding-top: 14px;
}
.underline-title {
  background: -webkit-linear-gradient(right, #a6f77b, #2ec06f);
  height: 2px;
  margin: -1.1rem auto 0 auto;
  width: 89px;
}  
  </style>

</head>

<body>
  <div id="card">
    <div id="card-content">
      <div id="card-title">
        <h2>LOGIN</h2>
        <div class="underline-title"></div>
      </div>
      <form method="post" class="form" action="cek_login.php">
        <label for="txtnama" style="padding-top:13px">
            &nbsp;User Name
          </label>
        <input name="txtnama" class="form-content" type="text" name="email" autocomplete="off" required />
        <div class="form-border"></div>
        <label for="user-password" style="padding-top:22px">&nbsp;Password
          </label>
        <input id="user-password" class="form-content" type="password" name="txtpass" required autocomplete="off" />
        <div class="form-border"></div>
        <label for="txtnama" style="padding-top:13px">
            &nbsp;Cabang
          </label>
											<select class="form-content" name="cbocabang" id="cbocabang" >
											<option value="">- Pilih -</option>
											<?php
												$sql="select kode_cabang, nama_cabang FROM tbcabang";
												$sql_row=mysqli_query($koneksi,$sql);
												while($sql_res=mysqli_fetch_assoc($sql_row))	
												{
											?>
											<option value="<?php echo $sql_res["kode_cabang"]; ?>"><?php echo $sql_res["nama_cabang"]; ?></option>
											<?php
												}
											?>
											</select>
        <div class="form-border"></div>
        
        <input id="submit-btn" type="submit" name="submit" value="LOGIN" />

      </form>
    </div>
  </div>
</body>

</html>