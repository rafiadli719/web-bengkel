    <table width="100%">
		<tr>
			<td align="right">
                Copyright @2023 Aplikasi Bengkel Version 1.0.0 / 
                <font color="red">
                <b>
                <?php 
                    if($nama_cabang=="") {
                        echo "Semua Cabang"; 
                    } else {
                        echo "Cabang ".$nama_cabang;             
                    }
                ?>
                <b>
                </font>
            </td>				
		</tr>
	</table>