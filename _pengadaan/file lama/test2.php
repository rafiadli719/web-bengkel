<!DOCTYPE html>
<html>
<head>
	<title> Event onChange </title>
</head>
<body>
	<form>
		<select name="provinsi" onChange="tampil(this.value)">
			<option value=""></option>
			<option value="Jakarta">Jakarta</option>
			<option value="Jateng">Jawa Tengah</option>
		</select>
	</form>

	<div id="kota"></div>
	<script type="text/javascript">
		function tampil(provinsi)
		{
			var kota="";
			switch(provinsi)
			{
				case "Jakarta" : {
					kota = "<ul><li>Jakarta Timur</li><li>Jakarta Barat</li></ul>";
				}
				break;
				case "Jateng" : {
					kota = "<ul><li> Kebumen </li><li> purworejo </li></ul>";
				}
				break;
				default :kota ="";
			}
			document.getElementById('kota').innerHTML =kota;
		}
	</script>
</body>
</html>