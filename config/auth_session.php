<?php
/**
 * Auth session helper
 * Menyatukan context user, role, dan permission berbasis tbuser + tb_master_posisi.
 */

if (!function_exists('auth_permission_aliases')) {
    function auth_permission_aliases() {
        return [
            'users.view' => ['user_read', 'master_karyawan_read', 'master_posisi_read'],
            'users.manage_roles' => ['master_posisi_read', 'user_read'],
            'users.add' => ['user_read'],
            'users.edit' => ['user_read'],
            'users.delete' => ['user_read'],
            'master_karyawan.view' => ['master_karyawan_read'],
            'master_posisi.view' => ['master_posisi_read'],
        ];
    }
}

if (!function_exists('auth_build_permission_matrix')) {
    function auth_build_permission_matrix(array $permissionCodes) {
        $matrix = [];

        foreach ($permissionCodes as $code) {
            if (!is_string($code) || $code === '') {
                continue;
            }

            if ($code === 'all' || $code === '*') {
                $matrix['*']['*'] = true;
                continue;
            }

            $parts = explode('_', $code);
            $action = array_pop($parts);
            $module = implode('_', $parts);

            if ($module !== '' && $action !== '') {
                if (!isset($matrix[$module])) {
                    $matrix[$module] = [];
                }
                $matrix[$module][$action] = true;
            }
        }

        foreach (auth_permission_aliases() as $target => $aliases) {
            [$module, $action] = explode('.', $target, 2);
            foreach ($aliases as $aliasCode) {
                $parts = explode('_', $aliasCode);
                $aliasAction = array_pop($parts);
                $aliasModule = implode('_', $parts);

                if (isset($matrix[$aliasModule][$aliasAction]) && $matrix[$aliasModule][$aliasAction] === true) {
                    if (!isset($matrix[$module])) {
                        $matrix[$module] = [];
                    }
                    $matrix[$module][$action] = true;
                    break;
                }
            }
        }

        return $matrix;
    }
}

if (!function_exists('auth_get_user_context')) {
    function auth_get_user_context($koneksi, $userId) {
        $userId = (int) $userId;
        $query = "
            SELECT
                u.id,
                u.kode_karyawan,
                u.nama_user,
                u.nama_lengkap,
                u.foto_user,
                u.foto,
                u.user_akses,
                u.kode_posisi,
                u.kode_level,
                u.kode_cabang,
                u.role_name,
                u.department,
                u.is_active,
                p.nama_posisi,
                p.departemen,
                p.user_akses_level,
                p.permissions
            FROM tbuser u
            LEFT JOIN tb_master_posisi p
                ON p.kode_posisi = u.kode_posisi
            WHERE u.id = {$userId}
            LIMIT 1
        ";

        $result = mysqli_query($koneksi, $query);
        if (!$result) {
            return null;
        }

        $row = mysqli_fetch_assoc($result);
        if (!$row) {
            return null;
        }

        $permissionCodes = [];
        if (!empty($row['permissions'])) {
            $decoded = json_decode($row['permissions'], true);
            if (is_array($decoded)) {
                $permissionCodes = array_values(array_unique(array_filter(array_map('strval', $decoded))));
            }
        }

        return [
            'id' => (int) $row['id'],
            'kode_karyawan' => $row['kode_karyawan'] ?? '',
            'nama_user' => $row['nama_user'] ?? '',
            'nama_lengkap' => $row['nama_lengkap'] ?: ($row['nama_user'] ?? ''),
            'foto' => $row['foto_user'] ?: ($row['foto'] ?: 'file_upload/avatar.png'),
            'user_akses' => (int) ($row['user_akses_level'] ?: $row['user_akses']),
            'kode_posisi' => $row['kode_posisi'] ?? '',
            'kode_level' => $row['kode_level'] ?? '',
            'kode_cabang' => $row['kode_cabang'] ?? '',
            'role_name' => $row['nama_posisi'] ?: ($row['role_name'] ?: ''),
            'department' => $row['departemen'] ?: ($row['department'] ?: ''),
            'permission_codes' => $permissionCodes,
            'permissions' => auth_build_permission_matrix($permissionCodes),
            'is_active' => $row['is_active'] ?? 'active',
        ];
    }
}

if (!function_exists('auth_store_user_context')) {
    function auth_store_user_context(array $context) {
        $_SESSION['_iduser'] = $context['id'];
        $_SESSION['_username'] = $context['nama_user'];
        $_SESSION['_nama_lengkap'] = $context['nama_lengkap'];
        $_SESSION['_foto'] = $context['foto'];
        $_SESSION['_role_name'] = $context['role_name'];
        $_SESSION['_department'] = $context['department'];
        $_SESSION['_kode_posisi'] = $context['kode_posisi'];
        $_SESSION['_kode_level'] = $context['kode_level'];
        $_SESSION['_permission_codes'] = $context['permission_codes'];
        $_SESSION['_permissions'] = $context['permissions'];
        $_SESSION['user_akses'] = $context['user_akses'];
        $_SESSION['_cabang'] = $context['kode_cabang'];
        $_SESSION['login_time'] = time();
        $_SESSION['last_activity'] = time();
    }
}
