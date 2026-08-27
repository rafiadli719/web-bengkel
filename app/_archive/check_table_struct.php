<?php
include "../config/koneksi.php";
$query = mysqli_query($koneksi, "DESCRIBE tbservis_pending_items");
echo "<pre>";
while($row = mysqli_fetch_assoc($query)) {
    print_r($row);
}
echo "</pre>";
?>
