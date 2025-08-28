<!DOCTYPE html>
<html>
<head>
	<title>
	Cara menampilkan value select menggunakan onchange
	</title>
</head>
<body>

	<select id="barang" onchange="changeMovie()">
		<option value="0">Silahkan Pilih</option>
		<option value="100">Kulkas</option>
		<option value="200">tv</option>
		<option value="300">jam</option>
	</select>

	<input type="text" id="harga">
<script type="text/javascript">
function changeMovie() {
     $('#harga').removeAttr('disabled');
 } 
 <script>
</body>
</html>

