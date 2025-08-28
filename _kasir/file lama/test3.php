<!DOCTYPE html>
<html>
<head>
	<title>Menghilangkan Disabled Menggunakan Event Change Pada Jquery</title>
	<style type="text/css">
	.container{width: 500px !important; background: salmon;padding: 20px;}
	</style>
</head>
<body>
	<div class="container">
		<div class="row">
			<div class="col-md-12 text-center">
				<h2>Menghilangkan Disabled Menggunakan Event Change Pada Jquery</h2>
				<select id="merk" class="form-control">
					<option value="">-</option>
					<option value="palomino">Palomino</option>
					<option value="fladeo">Fladeo</option>
				</select>
				<br>
				<select id="model" class="form-control" disabled="disabled">
					<option value="">-</option>
					<option value="488">10256</option>
					<option value="560">56560</option>
				</select>
				<br>
				<select id="tipe" class="form-control" disabled="disabled">
					<option value="">-</option>
					<option value="handbag">Handbag</option>
					<option value="totebag">Totebag</option>
				</select>
				<br>
				<button class="btn btn-primary form-control" disabled="disabled">Beli</button>
			</div>
		</div>
	</div>

<script type="text/javascript" src="https://code.jquery.com/jquery-3.3.1.js"></script>

    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js"></script>

	<script type="text/javascript">
		$(document).ready(function(){
			// Manual tanpa funsi
			$("#merk").change(function(){
				var merk = $(this).val();
				$("#model").removeAttr("disabled");
			});
			$("#model").change(function(){
				var model = $(this).val();
				$("#tipe").removeAttr("disabled");
			});
			$("#tipe").change(function(){
				var tipe = $(this).val();
				$(".btn").removeAttr("disabled");
			});
		});

	</script>    
</body>
</html>