<?php
session_start();
include "../config/koneksi.php";
require_once "_include_menu_rbac.php";

// Check if user is logged in and has permission
if (empty($_SESSION['_iduser']) || !canAccessPage($koneksi, (int) $_SESSION['_iduser'], 'user_read')) {
    exit('Unauthorized');
}

if(isset($_POST['action']) && $_POST['action'] == 'get_user') {
    $user_id = intval($_POST['user_id']);

    $role_options = [];
    $role_result = mysqli_query(
        $koneksi,
        "SELECT kode_posisi, nama_posisi, departemen, user_akses_level
         FROM tb_master_posisi
         WHERE is_active='active'
         ORDER BY departemen ASC, nama_posisi ASC"
    );
    if ($role_result) {
        while ($role_row = mysqli_fetch_assoc($role_result)) {
            $role_options[] = $role_row;
        }
    }

    $role_lookup = [];
    foreach ($role_options as $role_option) {
        $role_lookup[$role_option['kode_posisi']] = [
            'name' => $role_option['nama_posisi'],
            'dept' => $role_option['departemen'],
            'user_akses' => (int) $role_option['user_akses_level'],
            'is_workshop' => in_array($role_option['kode_posisi'], ['MK', 'KM'], true),
        ];
    }

    $query = "SELECT * FROM tbuser WHERE id = '$user_id'";
    $result = mysqli_query($koneksi, $query);
    $user = mysqli_fetch_assoc($result);

    if($user) {
        // Get linked mechanic if exists
        $mekanik_query = mysqli_query($koneksi, "SELECT mekanik_code FROM tb_user_mekanik_mapping WHERE user_id = '$user_id'");
        $linked_mekanik = mysqli_fetch_assoc($mekanik_query);

        ?>
        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">

        <div class="form-group">
            <label>Username <span class="text-danger">*</span></label>
            <input type="text" class="form-control" name="nama_user" value="<?php echo htmlspecialchars($user['nama_user']); ?>" required>
        </div>

        <div class="form-group">
            <label>Role <span class="text-danger">*</span></label>
            <select class="form-control" name="kode_posisi" id="user_akses_edit" required onchange="updateRoleInfo('edit')">
                <option value="">- Pilih Role -</option>
                <?php foreach ($role_options as $role_option): ?>
                <option value="<?php echo htmlspecialchars($role_option['kode_posisi']); ?>" <?php echo ($user['kode_posisi'] ?? '') === $role_option['kode_posisi'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($role_option['nama_posisi'] . ' [' . $role_option['kode_posisi'] . ']'); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label>Role Name</label>
            <input type="text" class="form-control" name="role_name" id="role_name_edit" value="<?php echo htmlspecialchars($user['role_name'] ?? ''); ?>">
        </div>

        <div class="form-group">
            <label>Department</label>
            <input type="text" class="form-control" name="department" id="department_edit" value="<?php echo htmlspecialchars($user['department'] ?? ''); ?>">
        </div>

        <div class="form-group" id="mekanik_section_edit" style="<?php echo in_array(($user['kode_posisi'] ?? ''), ['MK', 'KM'], true) ? 'display: block;' : 'display: none;'; ?>">
            <label>Link dengan Mekanik</label>
            <select class="form-control" name="mekanik_code">
                <option value="">- Pilih Mekanik (Opsional) -</option>
                <?php
                $mekanik_query = mysqli_query($koneksi, "SELECT nomekanik, nama FROM tblmekanik WHERE status='aktif' ORDER BY nama");
                while($mekanik = mysqli_fetch_assoc($mekanik_query)):
                ?>
                <option value="<?php echo $mekanik['nomekanik']; ?>"
                        <?php echo ($linked_mekanik && $linked_mekanik['mekanik_code'] == $mekanik['nomekanik']) ? 'selected' : ''; ?>>
                    <?php echo $mekanik['nama']; ?> (<?php echo $mekanik['nomekanik']; ?>)
                </option>
                <?php endwhile; ?>
            </select>
        </div>

        <div class="form-group">
            <label>Status</label>
            <select class="form-control" name="is_active">
                <option value="active" <?php echo ($user['is_active'] ?? 'active') == 'active' ? 'selected' : ''; ?>>Active</option>
                <option value="inactive" <?php echo ($user['is_active'] ?? 'active') == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
            </select>
        </div>

        <div class="alert alert-info">
            <i class="fa fa-info-circle"></i>
            <strong>Info:</strong> Untuk mengubah password, gunakan tombol "Change Password" di tabel.
        </div>

        <script>
        // Initialize role info for edit form
        updateRoleInfo('edit');
        </script>
        <?php
    } else {
        echo '<div class="alert alert-danger">User not found!</div>';
    }
}
?>
