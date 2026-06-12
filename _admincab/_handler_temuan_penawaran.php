<?php
/**
 * Handler untuk Temuan & Penawaran Part
 * Include file ini di servis-input-*.php
 */

// Include koneksi database jika belum ada
if(!isset($koneksi)) {
    // Cek apakah dipanggil via AJAX langsung atau via include
    if(isset($_GET['action']) || isset($_POST['action'])) {
        // Dipanggil langsung via AJAX
        include "../config/koneksi.php";
    } else {
        // Di-include dari halaman lain (sama-sama naik 1 level)
        include "../config/koneksi.php";
    }
}

// ============================================
// AJAX HANDLERS - MUST BE FIRST (before any output)
// ============================================

// Get part by kategori (untuk AJAX)
if(isset($_GET['action']) && $_GET['action'] == 'get_parts_by_kategori') {
    try {
        if(!isset($koneksi) || !$koneksi) {
            throw new Exception("Database connection not available");
        }
        
        $kode_kategori = mysqli_real_escape_string($koneksi, $_GET['kategori']);
        
        // Derive motor type context
        $no_service = trim($_GET['no_service'] ?? '');
        $kd_jenis_motor = intval($_GET['kd_jenis_motor'] ?? 0);
        if ($kd_jenis_motor <= 0 && $no_service !== '') {
            $no_service_safe = mysqli_real_escape_string($koneksi, $no_service);
            $res_kd = mysqli_query($koneksi, "SELECT kd_jenis_motor FROM view_service_jenis_motor WHERE no_service='{$no_service_safe}'");
            if ($res_kd && ($row_kd = mysqli_fetch_assoc($res_kd))) {
                $kd_jenis_motor = intval($row_kd['kd_jenis_motor'] ?? 0);
            }
        }

        $caseExpr = ($kd_jenis_motor > 0)
            ? "EXISTS (SELECT 1 FROM tbitem_jenis_motor jm WHERE jm.noitem=item.noitem AND jm.kd_jenis_motor={$kd_jenis_motor})"
            : "1=1";

        $query = "SELECT 
                    mbf.kode_barang,
                    item.namaitem as nama_barang,
                    item.hargajual as harga_jual,
                    item.satuan,
                    0 as stok_tersedia,
                    mbf.is_featured,
                    CASE WHEN $caseExpr THEN 1 ELSE 0 END AS applicable
                FROM tbmaster_barang_fastmoves mbf
                INNER JOIN tblitem item ON mbf.kode_barang = item.noitem
                WHERE mbf.kode_kategori = '$kode_kategori'
                ORDER BY applicable DESC, mbf.urutan, item.namaitem";
        
        $result = mysqli_query($koneksi, $query);
        
        if(!$result) {
            throw new Exception("Query error: " . mysqli_error($koneksi));
        }
        
        $parts = [];
        while($row = mysqli_fetch_assoc($result)) {
            $parts[] = $row;
        }
        
        header('Content-Type: application/json');
        echo json_encode($parts);
        exit;
    } catch(Exception $e) {
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
        exit;
    }
}

// Search part (untuk AJAX)
if(isset($_GET['action']) && $_GET['action'] == 'search_part') {
    $search = mysqli_real_escape_string($koneksi, $_GET['q']);
    
    // Derive motor type context
    $no_service = trim($_GET['no_service'] ?? '');
    $kd_jenis_motor = intval($_GET['kd_jenis_motor'] ?? 0);
    if ($kd_jenis_motor <= 0 && $no_service !== '') {
        $no_service_safe = mysqli_real_escape_string($koneksi, $no_service);
        $res_kd = mysqli_query($koneksi, "SELECT kd_jenis_motor FROM view_service_jenis_motor WHERE no_service='{$no_service_safe}'");
        if ($res_kd && ($row_kd = mysqli_fetch_assoc($res_kd))) {
            $kd_jenis_motor = intval($row_kd['kd_jenis_motor'] ?? 0);
        }
    }

    $caseExpr = ($kd_jenis_motor > 0)
        ? "EXISTS (SELECT 1 FROM tbitem_jenis_motor jm WHERE jm.noitem=tblitem.noitem AND jm.kd_jenis_motor={$kd_jenis_motor})"
        : "1=1";
    
    $query = "SELECT 
                noitem as kode_barang,
                namaitem as nama_barang,
                hargajual as harga_jual,
                satuan,
                CASE WHEN $caseExpr THEN 1 ELSE 0 END AS applicable
            FROM tblitem
            WHERE namaitem LIKE '%$search%'
            ORDER BY applicable DESC, namaitem
            LIMIT 50";
    
    $result = mysqli_query($koneksi, $query);
    $parts = [];
    
    while($row = mysqli_fetch_assoc($result)) {
        $parts[] = $row;
    }
    
    header('Content-Type: application/json');
    echo json_encode($parts);
    exit;
}

// Get temuan info by ID (untuk AJAX - modal Edit Temuan & Tambah Part)
if(isset($_GET['action']) && $_GET['action'] == 'get_temuan_info') {
    try {
        if(!isset($koneksi) || !$koneksi) {
            throw new Exception("Database connection not available");
        }
        
        $temuan_id = mysqli_real_escape_string($koneksi, $_GET['id'] ?? '');
        
        if(empty($temuan_id)) {
            throw new Exception("Temuan ID tidak boleh kosong");
        }
        
        $query = "SELECT 
                    t.*,
                    COALESCE(mt.nama_temuan, t.temuan_custom) as nama_temuan
                  FROM tbservis_temuan t
                  LEFT JOIN tbmaster_temuan mt ON t.kode_temuan = mt.kode_temuan
                  WHERE t.id = '$temuan_id'";
        
        $result = mysqli_query($koneksi, $query);
        
        if(!$result || mysqli_num_rows($result) == 0) {
            throw new Exception("Temuan tidak ditemukan");
        }
        
        $temuan = mysqli_fetch_assoc($result);
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'data' => [
                'id' => $temuan['id'],
                'no_service' => $temuan['no_service'],
                'nama_temuan' => $temuan['nama_temuan'],
                'deskripsi_temuan' => $temuan['deskripsi_temuan'],
                'jenis_perbaikan' => $temuan['jenis_perbaikan'],
                'tingkat_urgensi' => $temuan['tingkat_urgensi'],
                'status_temuan' => $temuan['status_temuan'],
                'estimasi_biaya' => $temuan['estimasi_biaya'],
                'kode_temuan' => $temuan['kode_temuan'],
                'temuan_custom' => $temuan['temuan_custom']
            ]
        ]);
        exit;
    } catch(Exception $e) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
        exit;
    }
}

// Get suggested jasa from mapping table
if(isset($_GET['action']) && $_GET['action'] == 'get_suggested_jasa') {
    try {
        if(!isset($koneksi) || !$koneksi) {
            throw new Exception("Database connection not available");
        }

        $kode_temuan = mysqli_real_escape_string($koneksi, trim($_GET['kode_temuan'] ?? ''));

        if(empty($kode_temuan)) {
            echo json_encode(['success' => false, 'message' => 'Kode temuan kosong', 'data' => []]);
            exit;
        }

        $jasa_list = [];

        // Try to get from mapping table first
        $query = "SELECT
                    m.id as mapping_id,
                    m.noitem,
                    m.is_primary,
                    m.prioritas,
                    m.waktu_estimasi,
                    m.keterangan,
                    i.namaitem,
                    i.hargajual,
                    i.satuan
                  FROM tbmaster_temuan_jasa_mapping m
                  LEFT JOIN tblitem i ON m.noitem = i.noitem
                  WHERE m.kode_temuan = '$kode_temuan'
                  AND m.status_aktif = 1
                  ORDER BY m.is_primary DESC, m.prioritas ASC";

        $result = mysqli_query($koneksi, $query);

        if($result && mysqli_num_rows($result) > 0) {
            while($row = mysqli_fetch_assoc($result)) {
                $jasa_list[] = $row;
            }
        } else {
            // Fallback: search by temuan name keyword in jasa items
            // Get temuan name first
            $q_name = mysqli_query($koneksi, "SELECT nama_temuan FROM tbmaster_temuan WHERE kode_temuan='$kode_temuan'");
            $temuan_name = '';
            if($q_name && mysqli_num_rows($q_name) > 0) {
                $row_name = mysqli_fetch_assoc($q_name);
                $temuan_name = strtolower($row_name['nama_temuan']);
            }

            if(empty($temuan_name)) {
                // If kode_temuan is actually a name (fallback search)
                $temuan_name = strtolower($kode_temuan);
            }

            // Extract keywords
            $keywords = preg_split('/\s+/', $temuan_name);
            $keywords = array_filter($keywords, function($k) {
                return strlen($k) >= 3 && !in_array($k, ['yang', 'dan', 'untuk', 'dari', 'dengan', 'pada', 'ini', 'itu', 'harus', 'perlu']);
            });

            if(count($keywords) > 0) {
                $like_conditions = [];
                foreach($keywords as $kw) {
                    $kw_esc = mysqli_real_escape_string($koneksi, $kw);
                    $like_conditions[] = "LOWER(namaitem) LIKE '%$kw_esc%'";
                }
                $where_like = implode(' OR ', $like_conditions);

                $q_fallback = mysqli_query($koneksi, "SELECT
                                                        noitem,
                                                        namaitem,
                                                        hargajual,
                                                        satuan,
                                                        0 as is_primary,
                                                        99 as prioritas,
                                                        0 as waktu_estimasi,
                                                        '' as keterangan,
                                                        NULL as mapping_id
                                                      FROM tblitem
                                                      WHERE jenis = 'SERVIS'
                                                      AND ($where_like)
                                                      ORDER BY namaitem
                                                      LIMIT 10");

                if($q_fallback) {
                    while($row = mysqli_fetch_assoc($q_fallback)) {
                        $jasa_list[] = $row;
                    }
                }
            }
        }

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'kode_temuan' => $kode_temuan,
            'data' => $jasa_list
        ]);
        exit;
    } catch(Exception $e) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage(),
            'data' => []
        ]);
        exit;
    }
}

if(isset($_GET['action']) && $_GET['action'] == 'get_recommended_parts_for_temuan') {
    try {
        if(!isset($koneksi) || !$koneksi) {
            throw new Exception("Database connection not available");
        }

        $temuan_raw = isset($_GET['temuan']) ? $_GET['temuan'] : '';
        $temuan_text = strtolower(trim(mysqli_real_escape_string($koneksi, $temuan_raw)));
        $response = [
            'strategy' => null,
            'temuan' => $temuan_text,
            'kode_kategori' => null,
            'nama_kategori' => null,
            'parts' => []
        ];

        $matched_kode = null;
        $matched_nama = null;
        $q_kat = mysqli_query($koneksi, "SELECT kode_kategori, nama_kategori FROM tbmaster_kategori_fastmoves WHERE is_active = 1 ORDER BY urutan, nama_kategori");
        if($q_kat) {
            while($kat = mysqli_fetch_assoc($q_kat)) {
                $nama_kat_lc = strtolower($kat['nama_kategori']);
                if($nama_kat_lc !== '' && strpos($temuan_text, $nama_kat_lc) !== false) {
                    $matched_kode = $kat['kode_kategori'];
                    $matched_nama = $kat['nama_kategori'];
                    break;
                }
            }
        }

        if($matched_kode) {
            $q_parts = mysqli_query($koneksi, "SELECT 
                                mbf.kode_barang,
                                item.namaitem AS nama_barang,
                                item.hargajual AS harga_jual,
                                item.satuan,
                                0 AS stok_tersedia,
                                mbf.is_featured
                              FROM tbmaster_barang_fastmoves mbf
                              INNER JOIN tblitem item ON mbf.kode_barang = item.noitem
                              WHERE mbf.kode_kategori = '".$matched_kode."'
                              ORDER BY mbf.urutan, item.namaitem
                              LIMIT 50");
            $parts = [];
            if($q_parts) {
                while($row = mysqli_fetch_assoc($q_parts)) {
                    $parts[] = $row;
                }
            }
            $response['strategy'] = 'kategori';
            $response['kode_kategori'] = $matched_kode;
            $response['nama_kategori'] = $matched_nama;
            // Jika tidak ada part pada kategori, fallback ke pencarian keyword di tblitem
            if(count($parts) === 0) {
                $clean = preg_replace('/[^a-z0-9\s]/', ' ', $temuan_text);
                $tokens = array_values(array_filter(explode(' ', $clean), function($t){ return strlen($t) > 0; }));
                $stopwords = [
                    'kotor','kebocoran','bocor','rusak','perlu','butuh','diganti','ganti','hanya','di','yang','dan','untuk','agar','cek','periksa','servis','service','saja','aja','itu','ini','pergantian','perbaikan','per','penggantian','temuan','masalah','kerusakan','harus','segera'
                ];
                $tokens = array_values(array_filter($tokens, function($t) use ($stopwords) { return !in_array($t, $stopwords); }));
                if(count($tokens) == 0) {
                    $approx = array_values(array_filter(explode(' ', $clean)));
                    $tokens = array_slice($approx, 0, 2);
                }
                $where = "1=1";
                foreach($tokens as $tk) {
                    $tk_esc = mysqli_real_escape_string($koneksi, $tk);
                    $where .= " AND namaitem LIKE '%$tk_esc%'";
                }
                $q_kw = mysqli_query($koneksi, "SELECT noitem AS kode_barang, namaitem AS nama_barang, hargajual AS harga_jual, satuan FROM tblitem WHERE $where ORDER BY namaitem LIMIT 50");
                $parts_kw = [];
                if($q_kw) {
                    while($r = mysqli_fetch_assoc($q_kw)) { $parts_kw[] = $r; }
                }
                $response['strategy'] = 'kategori+keyword_fallback';
                $response['parts'] = $parts_kw;
            } else {
                $response['parts'] = $parts;
            }
        } else {
            $stopwords = [
                'kotor','kebocoran','bocor','rusak','perlu','butuh','diganti','ganti','hanya','di','yang','dan','untuk','agar','cek','periksa','servis','service','saja','aja','itu','ini','pergantian','perbaikan','per','penggantian','temuan','masalah','kerusakan','harus','segera'
            ];
            $clean = preg_replace('/[^a-z0-9\s]/', ' ', $temuan_text);
            $tokens = array_values(array_filter(explode(' ', $clean), function($t){ return strlen($t) > 0; }));
            $tokens = array_values(array_filter($tokens, function($t) use ($stopwords) { return !in_array($t, $stopwords); }));

            if(count($tokens) == 0) {
                $approx = array_values(array_filter(explode(' ', $clean)));
                $tokens = array_slice($approx, 0, 2);
            }

            $where = "1=1";
            foreach($tokens as $tk) {
                $tk_esc = mysqli_real_escape_string($koneksi, $tk);
                $where .= " AND namaitem LIKE '%$tk_esc%'";
            }
            $q = "SELECT noitem AS kode_barang, namaitem AS nama_barang, hargajual AS harga_jual, satuan 
                  FROM tblitem WHERE $where ORDER BY namaitem LIMIT 50";
            $q_items = mysqli_query($koneksi, $q);
            $parts = [];
            if($q_items) {
                while($row = mysqli_fetch_assoc($q_items)) {
                    $parts[] = $row;
                }
            }
            $response['strategy'] = 'keyword';
            $response['parts'] = $parts;
        }

        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    } catch(Exception $e) {
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
        exit;
    }
}

// ============================================
// NEW AJAX ENDPOINTS - MAPPING TEMUAN & AUTO-SAVE
// ============================================

/**
 * ENDPOINT 1: Get Parts by Temuan Kode (dari mapping)
 * URL: ?action=get_parts_by_temuan_kode&kode_temuan=TMN001
 * Returns: List parts yang di-mapping ke temuan tersebut
 */
if(isset($_GET['action']) && $_GET['action'] == 'get_parts_by_temuan_kode') {
    session_start();
    try {
        if(!isset($koneksi) || !$koneksi) {
            throw new Exception("Database connection not available");
        }

        $kode_temuan = mysqli_real_escape_string($koneksi, $_GET['kode_temuan'] ?? '');

        if(empty($kode_temuan)) {
            throw new Exception("Kode temuan tidak boleh kosong");
        }

        // Get kode cabang dari session
        $kd_cabang = $_SESSION['_cabang'] ?? '';

        // Query untuk get parts dari mapping
        // Note: Stok diambil dari view_stok_master jika ada, default 0 jika tidak ada
        $query = "
            SELECT
                m.id as mapping_id,
                m.noitem as kode_barang,
                i.namaitem as nama_barang,
                i.hargajual as harga_jual,
                i.satuan,
                m.is_primary,
                m.prioritas,
                m.qty_default,
                m.keterangan,
                COALESCE(s.saldo, 0) as stok_tersedia
            FROM tbmaster_temuan_barang_mapping m
            INNER JOIN tblitem i ON m.noitem = i.noitem
            LEFT JOIN view_stok_master s ON i.noitem = s.no_item AND s.kd_cabang = '$kd_cabang'
            WHERE m.kode_temuan = '$kode_temuan'
            AND m.status_aktif = 1
            ORDER BY m.prioritas ASC, m.is_primary DESC
        ";

        $result = mysqli_query($koneksi, $query);

        if(!$result) {
            throw new Exception("Query error: " . mysqli_error($koneksi));
        }

        $parts = [];
        while($row = mysqli_fetch_assoc($result)) {
            $parts[] = $row;
        }

        $response = [
            'success' => true,
            'kode_temuan' => $kode_temuan,
            'strategy' => 'mapping',
            'count' => count($parts),
            'parts' => $parts
        ];

        header('Content-Type: application/json');
        echo json_encode($response);
        exit;

    } catch(Exception $e) {
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
        exit;
    }
}

/**
 * ENDPOINT 2: Check Duplikasi Temuan
 * URL: ?action=check_temuan_duplicate&nama_temuan=Filter%20Udara%20Kotor
 * Returns: Data temuan jika ada duplikasi, atau empty jika tidak ada
 */
if(isset($_GET['action']) && $_GET['action'] == 'check_temuan_duplicate') {
    try {
        if(!isset($koneksi) || !$koneksi) {
            throw new Exception("Database connection not available");
        }

        $nama_temuan = mysqli_real_escape_string($koneksi, $_GET['nama_temuan'] ?? '');

        if(empty($nama_temuan)) {
            throw new Exception("Nama temuan tidak boleh kosong");
        }

        // Normalize input untuk comparison yang lebih baik
        $nama_temuan_normalized = strtolower(trim($nama_temuan));

        // Check exact match (case insensitive)
        $query_exact = "
            SELECT
                kode_temuan,
                nama_temuan,
                deskripsi,
                kategori,
                tingkat_urgensi
            FROM tbmaster_temuan
            WHERE LOWER(nama_temuan) = '$nama_temuan_normalized'
            AND is_active = 1
            LIMIT 1
        ";

        $result = mysqli_query($koneksi, $query_exact);

        if(!$result) {
            throw new Exception("Query error: " . mysqli_error($koneksi));
        }

        if(mysqli_num_rows($result) > 0) {
            // Found exact match
            $row = mysqli_fetch_assoc($result);

            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'duplicate_found' => true,
                'match_type' => 'exact',
                'data' => $row
            ]);
            exit;
        }

        // Check similar match (untuk catch typo atau variasi nama)
        // Cek dengan SOUNDEX atau similarity (jika ada extension)
        // Untuk sekarang kita pakai LIKE dengan tokenization

        // Tokenize dan cari yang mirip
        $tokens = explode(' ', $nama_temuan_normalized);
        $tokens = array_filter($tokens, function($t) {
            return strlen($t) > 3; // Hanya ambil token yang panjangnya > 3 char
        });

        if(count($tokens) > 0) {
            $where_conditions = [];
            foreach($tokens as $token) {
                $token_esc = mysqli_real_escape_string($koneksi, $token);
                $where_conditions[] = "LOWER(nama_temuan) LIKE '%$token_esc%'";
            }

            $where_clause = implode(' AND ', $where_conditions);

            $query_similar = "
                SELECT
                    kode_temuan,
                    nama_temuan,
                    deskripsi,
                    kategori,
                    tingkat_urgensi
                FROM tbmaster_temuan
                WHERE $where_clause
                AND is_active = 1
                LIMIT 3
            ";

            $result_similar = mysqli_query($koneksi, $query_similar);

            if($result_similar && mysqli_num_rows($result_similar) > 0) {
                $similar_items = [];
                while($row = mysqli_fetch_assoc($result_similar)) {
                    $similar_items[] = $row;
                }

                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'duplicate_found' => true,
                    'match_type' => 'similar',
                    'data' => $similar_items
                ]);
                exit;
            }
        }

        // No duplicate found
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'duplicate_found' => false,
            'data' => null
        ]);
        exit;

    } catch(Exception $e) {
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
        exit;
    }
}

/**
 * ENDPOINT 3: Save Temuan Custom ke Master
 * URL: POST ?action=save_temuan_to_master
 * Body: nama_temuan, deskripsi, kategori, tingkat_urgensi
 * Returns: Kode temuan yang baru dibuat
 */
if(isset($_POST['action']) && $_POST['action'] == 'save_temuan_to_master') {
    session_start();
    try {
        if(!isset($koneksi) || !$koneksi) {
            throw new Exception("Database connection not available");
        }

        // Get input
        $nama_temuan = mysqli_real_escape_string($koneksi, $_POST['nama_temuan'] ?? '');
        $deskripsi = mysqli_real_escape_string($koneksi, $_POST['deskripsi'] ?? '');
        $kategori = mysqli_real_escape_string($koneksi, $_POST['kategori'] ?? 'Lainnya');
        $tingkat_urgensi = mysqli_real_escape_string($koneksi, $_POST['tingkat_urgensi'] ?? 'sedang');

        // Validation
        if(empty($nama_temuan)) {
            throw new Exception("Nama temuan tidak boleh kosong");
        }

        // Validate tingkat_urgensi enum
        $valid_urgensi = ['rendah', 'sedang', 'tinggi', 'kritis'];
        if(!in_array($tingkat_urgensi, $valid_urgensi)) {
            $tingkat_urgensi = 'sedang'; // default
        }

        // Auto-generate kode temuan
        // Format: TMN + auto-increment number (misal: TMN011, TMN012, dst)
        $query_last_code = "
            SELECT kode_temuan
            FROM tbmaster_temuan
            WHERE kode_temuan LIKE 'TMN%'
            ORDER BY kode_temuan DESC
            LIMIT 1
        ";

        $result = mysqli_query($koneksi, $query_last_code);

        if(!$result) {
            throw new Exception("Error getting last code: " . mysqli_error($koneksi));
        }

        if(mysqli_num_rows($result) > 0) {
            $row = mysqli_fetch_assoc($result);
            $last_code = $row['kode_temuan']; // misal: TMN010

            // Extract number
            $number = intval(substr($last_code, 3)); // 010 -> 10
            $new_number = $number + 1; // 11
            $new_code = 'TMN' . str_pad($new_number, 3, '0', STR_PAD_LEFT); // TMN011
        } else {
            // Tidak ada data, mulai dari TMN001
            $new_code = 'TMN001';
        }

        // Insert ke master
        $created_by = $_SESSION['_nama'] ?? 'System';

        $query_insert = "
            INSERT INTO tbmaster_temuan
            (kode_temuan, nama_temuan, deskripsi, kategori, tingkat_urgensi, is_active, created_at)
            VALUES
            ('$new_code', '$nama_temuan', '$deskripsi', '$kategori', '$tingkat_urgensi', 1, NOW())
        ";

        if(!mysqli_query($koneksi, $query_insert)) {
            throw new Exception("Error inserting data: " . mysqli_error($koneksi));
        }

        // Get inserted ID
        $inserted_id = mysqli_insert_id($koneksi);

        // Get full data untuk return
        $query_get = "
            SELECT
                id,
                kode_temuan,
                nama_temuan,
                deskripsi,
                kategori,
                tingkat_urgensi
            FROM tbmaster_temuan
            WHERE id = $inserted_id
        ";

        $result_get = mysqli_query($koneksi, $query_get);
        $data = mysqli_fetch_assoc($result_get);

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => 'Temuan berhasil disimpan ke master',
            'data' => $data
        ]);
        exit;

    } catch(Exception $e) {
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
        exit;
    }
}

// ============================================
// AJAX: Add Part to Existing Temuan
// ============================================
if(isset($_POST['action']) && $_POST['action'] == 'add_part_to_temuan') {
    session_start();
    try {
        if(!isset($koneksi) || !$koneksi) {
            throw new Exception("Database connection not available");
        }

        $temuan_id = mysqli_real_escape_string($koneksi, $_POST['temuan_id'] ?? '');
        $kode_barang = mysqli_real_escape_string($koneksi, $_POST['kode_barang'] ?? '');
        $nama_barang = mysqli_real_escape_string($koneksi, $_POST['nama_barang'] ?? '');
        $harga_satuan = (float)($_POST['harga_satuan'] ?? 0);
        $quantity = (int)($_POST['quantity'] ?? 1);

        if(empty($temuan_id) || empty($kode_barang)) {
            throw new Exception("Temuan ID dan Kode Barang wajib diisi");
        }

        // Get no_service from temuan
        $q_temuan = mysqli_query($koneksi, "SELECT no_service FROM tbservis_temuan WHERE id='$temuan_id'");
        if(!$q_temuan || mysqli_num_rows($q_temuan) == 0) {
            throw new Exception("Temuan tidak ditemukan");
        }
        $row_temuan = mysqli_fetch_assoc($q_temuan);
        $no_service = $row_temuan['no_service'];

        // STRICT GUARD: reject non-applicable part for the service motor type
        // Derive kd_jenis_motor from service context
        $kd_jenis_motor = 0;
        $no_service_safe = mysqli_real_escape_string($koneksi, $no_service);
        $res_kd = mysqli_query($koneksi, "SELECT kd_jenis_motor FROM view_service_jenis_motor WHERE no_service='{$no_service_safe}'");
        if ($res_kd && ($row_kd = mysqli_fetch_assoc($res_kd))) {
            $kd_jenis_motor = intval($row_kd['kd_jenis_motor'] ?? 0);
        }
        if ($kd_jenis_motor > 0) {
            $kode_barang_safe = mysqli_real_escape_string($koneksi, $kode_barang);
            $q_app = mysqli_query($koneksi, "SELECT 1 FROM tbitem_jenis_motor WHERE noitem='{$kode_barang_safe}' AND kd_jenis_motor={$kd_jenis_motor} LIMIT 1");
            if (!$q_app || mysqli_num_rows($q_app) === 0) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => 'Part tidak applicable untuk jenis motor layanan ini.'
                ]);
                exit;
            }
        }

        // Get harga from master if not provided
        if($harga_satuan <= 0) {
            $qh = mysqli_query($koneksi, "SELECT namaitem, hargajual FROM tblitem WHERE noitem='$kode_barang' LIMIT 1");
            if($qh && mysqli_num_rows($qh) > 0) {
                $rh = mysqli_fetch_assoc($qh);
                $harga_satuan = (float)$rh['hargajual'];
                if(empty($nama_barang)) {
                    $nama_barang = mysqli_real_escape_string($koneksi, $rh['namaitem']);
                }
            }
        }

        $total_harga = $quantity * $harga_satuan;
        $user_penawaran = $_SESSION['_nama'] ?? 'System';

        // Check duplicate
        $dup_check = mysqli_query($koneksi, "SELECT COUNT(*) AS c FROM tbservis_penawaran_part 
            WHERE no_service='$no_service' AND kode_barang='$kode_barang' AND status_penawaran IN ('pending','disetujui')");
        if($dup_check) {
            $dup = mysqli_fetch_array($dup_check);
            if((int)$dup['c'] > 0) {
                throw new Exception("Part ini sudah ada di daftar penawaran");
            }
        }

        // Insert
        $sql_insert = "INSERT INTO tbservis_penawaran_part 
            (no_service, temuan_id, kode_barang, nama_barang, quantity, harga_satuan, total_harga, status_penawaran, user_penawaran, created_at) 
            VALUES 
            ('$no_service', '$temuan_id', '$kode_barang', '$nama_barang', $quantity, $harga_satuan, $total_harga, 'pending', '$user_penawaran', NOW())";

        if(!mysqli_query($koneksi, $sql_insert)) {
            throw new Exception("Gagal menyimpan: " . mysqli_error($koneksi));
        }

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => 'Part berhasil ditambahkan ke temuan'
        ]);
        exit;

    } catch(Exception $e) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
        exit;
    }
}

// ============================================
// HANDLER TEMUAN
// ============================================


// Tambah Temuan
if(isset($_POST['btnaddtemuan'])) {
    $no_service = mysqli_real_escape_string($koneksi, $_POST['txtnosrv']);
    $keluhan_id = !empty($_POST['keluhan_id']) ? mysqli_real_escape_string($koneksi, $_POST['keluhan_id']) : NULL;
    $kode_temuan = !empty($_POST['kode_temuan']) ? mysqli_real_escape_string($koneksi, $_POST['kode_temuan']) : NULL;
    $nama_temuan = mysqli_real_escape_string($koneksi, $_POST['nama_temuan']);
    $jenis_perbaikan = mysqli_real_escape_string($koneksi, $_POST['jenis_perbaikan']);
    $tingkat_urgensi = mysqli_real_escape_string($koneksi, $_POST['tingkat_urgensi']);
    // estimasi_biaya field removed from UI, default to 0
    $estimasi_biaya = isset($_POST['estimasi_biaya']) ? mysqli_real_escape_string($koneksi, $_POST['estimasi_biaya']) : 0;
    $deskripsi_temuan = mysqli_real_escape_string($koneksi, $_POST['deskripsi_temuan']);
    
    // Validate
    if(empty($nama_temuan)) {
        echo "<script>alert('Nama temuan harus diisi!'); window.location.href='?snoserv=$no_service';</script>";
        exit;
    }
    
    // STANDARDISASI TEMUAN: jika tidak ada kode_temuan, dedup dan/atau buat master baru otomatis
    if (empty($kode_temuan)) {
        $nama_norm = strtolower(trim($nama_temuan));
        $q_dup = mysqli_query($koneksi, "SELECT kode_temuan, nama_temuan FROM tbmaster_temuan WHERE LOWER(nama_temuan) = '$nama_norm' AND is_active = 1 LIMIT 1");
        if ($q_dup && mysqli_num_rows($q_dup) > 0) {
            // Pakai master yang sudah ada
            $row_dup = mysqli_fetch_assoc($q_dup);
            $kode_temuan = $row_dup['kode_temuan'];
        } else {
            // Generate kode baru TMNxxx
            $q_last = mysqli_query($koneksi, "SELECT kode_temuan FROM tbmaster_temuan WHERE kode_temuan LIKE 'TMN%' ORDER BY kode_temuan DESC LIMIT 1");
            if ($q_last && mysqli_num_rows($q_last) > 0) {
                $row_last = mysqli_fetch_assoc($q_last);
                $last_code = $row_last['kode_temuan'];
                $num = intval(substr($last_code, 3));
                $new_code = 'TMN' . str_pad($num + 1, 3, '0', STR_PAD_LEFT);
            } else {
                $new_code = 'TMN001';
            }
            $kategori = 'Lainnya';
            $valid_urg = ['rendah','sedang','tinggi','kritis'];
            if (!in_array($tingkat_urgensi, $valid_urg)) { $tingkat_urgensi = 'sedang'; }
            $ins_master = "INSERT INTO tbmaster_temuan (kode_temuan, nama_temuan, deskripsi, kategori, tingkat_urgensi, is_active, created_at) VALUES ('$new_code', '$nama_temuan', '$deskripsi_temuan', '$kategori', '$tingkat_urgensi', 1, NOW())";
            if (mysqli_query($koneksi, $ins_master)) {
                $kode_temuan = $new_code;
            } else {
                // Jika gagal insert master, fallback tetap simpan sebagai custom agar tidak blokir workflow
                $kode_temuan = NULL;
            }
        }
    }
    
    // Insert temuan (gunakan kode_temuan jika tersedia, jika tidak ada tetap simpan sebagai custom)
    $query_insert = "INSERT INTO tbservis_temuan 
                    (no_service, keluhan_id, kode_temuan, temuan_custom, deskripsi_temuan, 
                     jenis_perbaikan, status_temuan, tingkat_urgensi, estimasi_biaya) 
                    VALUES 
                    ('$no_service', " . ($keluhan_id ? "'$keluhan_id'" : "NULL") . ", 
                     " . ($kode_temuan ? "'$kode_temuan'" : "NULL") . ", 
                     " . ($kode_temuan ? "NULL" : "'$nama_temuan'") . ", 
                     '$deskripsi_temuan', '$jenis_perbaikan', 'ditemukan', 
                     '$tingkat_urgensi', '$estimasi_biaya')";
    
    if(mysqli_query($koneksi, $query_insert)) {
        $new_temuan_id = mysqli_insert_id($koneksi);

        // =====================================================
        // PROSES PART YANG DIPILIH
        // Part masuk ke tbservis_penawaran_part dengan link ke temuan
        // =====================================================
        if(isset($_POST['selected_parts_payload']) && !empty($_POST['selected_parts_payload'])) {
            $payload_json = $_POST['selected_parts_payload'];
            $items = json_decode($payload_json, true);
            if(is_array($items)) {
                foreach($items as $it) {
                    $kode_barang = mysqli_real_escape_string($koneksi, $it['kode_barang'] ?? '');
                    if(empty($kode_barang)) continue;
                    $quantity = (int)($it['quantity'] ?? 1);
                    if($quantity < 1) $quantity = 1;
                    // Ambil dari master jika harga 0
                    $harga_satuan = (float)($it['harga_satuan'] ?? 0);
                    $nama_barang = mysqli_real_escape_string($koneksi, $it['nama_barang'] ?? $kode_barang);
                    if($harga_satuan <= 0) {
                        $qh = mysqli_query($koneksi, "SELECT namaitem, hargajual FROM tblitem WHERE noitem='".$kode_barang."' LIMIT 1");
                        if($qh && mysqli_num_rows($qh) > 0) {
                            $rh = mysqli_fetch_assoc($qh);
                            $nama_barang = mysqli_real_escape_string($koneksi, $rh['namaitem']);
                            $harga_satuan = (float)$rh['hargajual'];
                        }
                    }
                    $total_harga = $quantity * $harga_satuan;
                    // Dedup: cek sudah ada di barang servis atau penawaran aktif
                    $dup_barang = mysqli_query($koneksi, "SELECT COUNT(*) AS c FROM tblservis_barang WHERE no_service='$no_service' AND no_item='".$kode_barang."'");
                    $dup_ok = true;
                    if($dup_barang) { $rb = mysqli_fetch_array($dup_barang); if((int)$rb['c'] > 0) $dup_ok = false; }
                    if($dup_ok) {
                        $dup_pen = mysqli_query($koneksi, "SELECT COUNT(*) AS c FROM tbservis_penawaran_part WHERE no_service='$no_service' AND kode_barang='".$kode_barang."' AND status_penawaran IN ('pending','disetujui')");
                        if($dup_pen) { $rp = mysqli_fetch_array($dup_pen); if((int)$rp['c'] > 0) $dup_ok = false; }
                    }
                    if(!$dup_ok) continue;
                    // Flags dari mapping
                    $mapping_id = isset($it['mapping_id']) && $it['mapping_id'] !== '' ? mysqli_real_escape_string($koneksi, $it['mapping_id']) : null;
                    $prioritas = isset($it['prioritas']) && $it['prioritas'] !== '' ? (int)$it['prioritas'] : null;
                    $temuan_id_val = $new_temuan_id ? "'".$new_temuan_id."'" : 'NULL';
                    $prioritas_val = $prioritas !== null ? (int)$prioritas : 'NULL';
                    $catatan_val = $mapping_id ? ("'mapping_id=".mysqli_real_escape_string($koneksi,$mapping_id)."'") : 'NULL';
                    $user_penawaran = $_SESSION['_nama'] ?? 'System';
                    $sql_ins = "INSERT INTO tbservis_penawaran_part (no_service, temuan_id, is_from_suggestion, suggestion_priority, kode_barang, nama_barang, quantity, harga_satuan, total_harga, status_penawaran, user_penawaran, catatan_penawaran) VALUES ('".$no_service."', " . $temuan_id_val . ", 1, " . $prioritas_val . ", '".$kode_barang."', '".$nama_barang."', " . $quantity . ", " . $harga_satuan . ", " . $total_harga . ", 'pending', '".mysqli_real_escape_string($koneksi,$user_penawaran)."', " . $catatan_val . ")";
                    mysqli_query($koneksi, $sql_ins);
                }
            }
        }

        // =====================================================
        // PROSES JASA YANG DIPILIH
        // Jasa masuk ke tbservis_penawaran_jasa untuk approval
        // =====================================================
        $__chk_tbl = mysqli_query($koneksi, "SHOW TABLES LIKE 'tbservis_penawaran_jasa'");
        if(!$__chk_tbl || mysqli_num_rows($__chk_tbl) == 0) {
            $sql_create_tbl = "CREATE TABLE IF NOT EXISTS `tbservis_penawaran_jasa` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `no_service` varchar(50) NOT NULL,
              `temuan_id` int(11) DEFAULT NULL,
              `kode_jasa` varchar(50) NOT NULL,
              `nama_jasa` varchar(255) DEFAULT NULL,
              `harga` double DEFAULT 0,
              `waktu_estimasi` int(11) DEFAULT 0,
              `is_from_suggestion` tinyint(1) DEFAULT 0,
              `mapping_id` int(11) DEFAULT NULL,
              `status_penawaran` enum('pending','disetujui','ditolak') DEFAULT 'pending',
              `alasan_tolak` varchar(100) DEFAULT NULL,
              `keterangan_tolak` text DEFAULT NULL,
              `user_penawaran` varchar(100) DEFAULT NULL,
              `user_respon` varchar(100) DEFAULT NULL,
              `tanggal_respon` datetime DEFAULT NULL,
              `catatan_penawaran` text DEFAULT NULL,
              `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
              `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
              PRIMARY KEY (`id`),
              KEY `idx_no_service` (`no_service`),
              KEY `idx_temuan_id` (`temuan_id`),
              KEY `idx_status` (`status_penawaran`),
              KEY `idx_kode_jasa` (`kode_jasa`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
            mysqli_query($koneksi, $sql_create_tbl);
            mysqli_query($koneksi, "ALTER TABLE `tbservis_penawaran_jasa` ADD INDEX `idx_service_status` (`no_service`, `status_penawaran`)");
        }
        if(isset($_POST['selected_jasa_payload']) && !empty($_POST['selected_jasa_payload'])) {
            $jasa_json = $_POST['selected_jasa_payload'];
            $jasa_items = json_decode($jasa_json, true);
            if(is_array($jasa_items)) {
                foreach($jasa_items as $jasa) {
                    $kode_jasa = mysqli_real_escape_string($koneksi, $jasa['kode_jasa'] ?? '');
                    if(empty($kode_jasa)) continue;

                    // Get harga and nama from master if not provided
                    $harga = (float)($jasa['harga'] ?? 0);
                    $waktu = (int)($jasa['waktu_estimasi'] ?? 0);
                    $nama_jasa = mysqli_real_escape_string($koneksi, $jasa['nama_jasa'] ?? $kode_jasa);

                    if($harga <= 0) {
                        $qj = mysqli_query($koneksi, "SELECT namaitem, hargajual FROM tblitem WHERE noitem='".$kode_jasa."' AND jenis='SERVIS' LIMIT 1");
                        if($qj && mysqli_num_rows($qj) > 0) {
                            $rj = mysqli_fetch_assoc($qj);
                            $harga = (float)$rj['hargajual'];
                            if(empty($nama_jasa) || $nama_jasa == $kode_jasa) {
                                $nama_jasa = mysqli_real_escape_string($koneksi, $rj['namaitem']);
                            }
                        }
                    }

                    // Dedup: cek sudah ada di jasa servis atau penawaran aktif
                    $dup_jasa = mysqli_query($koneksi, "SELECT COUNT(*) AS c FROM tblservis_jasa WHERE no_service='$no_service' AND no_item='".$kode_jasa."'");
                    $dup_ok = true;
                    if($dup_jasa) {
                        $rd = mysqli_fetch_array($dup_jasa);
                        if((int)$rd['c'] > 0) $dup_ok = false;
                    }
                    if($dup_ok) {
                        $dup_pen_jasa = mysqli_query($koneksi, "SELECT COUNT(*) AS c FROM tbservis_penawaran_jasa WHERE no_service='$no_service' AND kode_jasa='".$kode_jasa."' AND status_penawaran IN ('pending','disetujui')");
                        if($dup_pen_jasa) {
                            $rpj = mysqli_fetch_array($dup_pen_jasa);
                            if((int)$rpj['c'] > 0) $dup_ok = false;
                        }
                    }
                    if(!$dup_ok) continue;

                    // Flags dari mapping
                    $mapping_id = isset($jasa['mapping_id']) && $jasa['mapping_id'] !== '' ? mysqli_real_escape_string($koneksi, $jasa['mapping_id']) : null;
                    $temuan_id_val = $new_temuan_id ? "'".$new_temuan_id."'" : 'NULL';
                    $mapping_id_val = $mapping_id ? "'".$mapping_id."'" : 'NULL';
                    $user_penawaran = $_SESSION['_nama'] ?? 'System';

                    // Insert ke tbservis_penawaran_jasa (pending approval)
                    $sql_ins_jasa = "INSERT INTO tbservis_penawaran_jasa
                                    (no_service, temuan_id, kode_jasa, nama_jasa, harga, waktu_estimasi,
                                     is_from_suggestion, mapping_id, status_penawaran, user_penawaran)
                                    VALUES
                                    ('$no_service', $temuan_id_val, '$kode_jasa', '$nama_jasa', '$harga', '$waktu',
                                     1, $mapping_id_val, 'pending', '".mysqli_real_escape_string($koneksi,$user_penawaran)."')";
                    mysqli_query($koneksi, $sql_ins_jasa);
                }
            }
        }

        // Redirect server-side ke halaman pemanggil (reguler/jemput/garansi), fallback ke JS jika header sudah terkirim
        $base_script = basename($_SERVER['PHP_SELF'] ?? '');
        if(empty($base_script)) { $base_script = 'servis-input-reguler.php'; }
        $redir = $base_script . '?snoserv=' . urlencode($no_service) . '&tab=temuan#temuan-penawaran';
        if (!headers_sent()) {
            header('Location: ' . $redir);
        } else {
            echo "<script>window.location.replace('" . $redir . "');</script>";
        }
    } else {
        $base_script = basename($_SERVER['PHP_SELF'] ?? '');
        if(empty($base_script)) { $base_script = 'servis-input-reguler.php'; }
        $redir = $base_script . '?snoserv=' . urlencode($no_service) . '&tab=temuan#temuan-penawaran';
        if (!headers_sent()) {
            header('Location: ' . $redir);
        } else {
            echo "<script>window.location.replace('" . $redir . "');</script>";
        }
    }
    exit;
}

// Update Temuan (Edit detil temuan)
if(isset($_POST['btnupdatetemuan'])) {
    $temuan_id = mysqli_real_escape_string($koneksi, $_POST['temuan_id']);
    $no_service = mysqli_real_escape_string($koneksi, $_POST['txtnosrv']);
    $nama_temuan = mysqli_real_escape_string($koneksi, $_POST['nama_temuan'] ?? '');
    $jenis_perbaikan = mysqli_real_escape_string($koneksi, $_POST['jenis_perbaikan'] ?? 'setting');
    $tingkat_urgensi = mysqli_real_escape_string($koneksi, $_POST['tingkat_urgensi'] ?? 'sedang');
    $deskripsi_temuan = mysqli_real_escape_string($koneksi, $_POST['deskripsi_temuan'] ?? '');
    $kode_temuan = mysqli_real_escape_string($koneksi, $_POST['kode_temuan'] ?? '');
    
    // Validate
    if(empty($temuan_id)) {
        echo "<script>alert('ID temuan tidak valid!'); window.location.href='?snoserv=$no_service&tab=temuan';</script>";
        exit;
    }
    
    // Build update query - only update temuan_custom if not from master
    if(empty($kode_temuan)) {
        // Custom temuan - can update name
        $query_update = "UPDATE tbservis_temuan 
                        SET temuan_custom = '$nama_temuan',
                            jenis_perbaikan = '$jenis_perbaikan',
                            tingkat_urgensi = '$tingkat_urgensi',
                            deskripsi_temuan = '$deskripsi_temuan'
                        WHERE id = '$temuan_id'";
    } else {
        // Master temuan - cannot update name
        $query_update = "UPDATE tbservis_temuan 
                        SET jenis_perbaikan = '$jenis_perbaikan',
                            tingkat_urgensi = '$tingkat_urgensi',
                            deskripsi_temuan = '$deskripsi_temuan'
                        WHERE id = '$temuan_id'";
    }
    
    if(mysqli_query($koneksi, $query_update)) {
        echo "<script>alert('Temuan berhasil diupdate!'); window.location.href='?snoserv=$no_service&tab=temuan#temuan-penawaran';</script>";
    } else {
        echo "<script>alert('Gagal update temuan: " . mysqli_error($koneksi) . "'); window.location.href='?snoserv=$no_service&tab=temuan';</script>";
    }
    exit;
}

// Update Status Temuan
if(isset($_POST['btnupdatestatustemuan'])) {

    $temuan_id = mysqli_real_escape_string($koneksi, $_POST['temuan_id_status']);
    $status_temuan = mysqli_real_escape_string($koneksi, $_POST['status_temuan_update']);
    $keterangan = isset($_POST['keterangan_temuan']) ? mysqli_real_escape_string($koneksi, $_POST['keterangan_temuan']) : '';
    $no_service = mysqli_real_escape_string($koneksi, $_POST['txtnosrv']);
    
    // Validate: keterangan wajib jika status tidak_selesai
    if($status_temuan == 'tidak_selesai' && empty($keterangan)) {
        echo "<script>alert('Keterangan wajib diisi untuk status Tidak Selesai!'); window.location.href='?snoserv=$no_service&tab=temuan';</script>";
        exit;
    }
    
    $query_update = "UPDATE tbservis_temuan 
                    SET status_temuan = '$status_temuan',
                        keterangan_tidak_selesai = " . ($status_temuan == 'tidak_selesai' ? "'$keterangan'" : "NULL") . "
                    WHERE id = '$temuan_id'";
    
    if(mysqli_query($koneksi, $query_update)) {
        // Jika status tidak_selesai, tambahkan ke catatan servis
        if($status_temuan == 'tidak_selesai' && !empty($keterangan)) {
            // Get temuan name
            $q_temuan = mysqli_query($koneksi, "SELECT COALESCE(temuan_custom, (SELECT nama_temuan FROM tbmaster_temuan WHERE kode_temuan = tbservis_temuan.kode_temuan)) as nama_temuan FROM tbservis_temuan WHERE id = '$temuan_id'");
            $temuan_data = mysqli_fetch_array($q_temuan);
            $nama_temuan = $temuan_data['nama_temuan'];
            
            // Get current catatan
            $q_service = mysqli_query($koneksi, "SELECT catatan FROM tblservice WHERE no_service = '$no_service'");
            $service_data = mysqli_fetch_array($q_service);
            $catatan_lama = $service_data['catatan'] ?? '';
            
            // Append new note
            $catatan_baru = $catatan_lama;
            if(!empty($catatan_lama)) {
                $catatan_baru .= "\n\n";
            }
            $catatan_baru .= "[TEMUAN TIDAK SELESAI] $nama_temuan: $keterangan";
            
            // Update catatan
            $catatan_escaped = mysqli_real_escape_string($koneksi, $catatan_baru);
            mysqli_query($koneksi, "UPDATE tblservice SET catatan = '$catatan_escaped' WHERE no_service = '$no_service'");
        }
        
        echo "<script>alert('Status temuan berhasil diupdate!'); window.location.href='?snoserv=$no_service&tab=temuan#temuan-penawaran';</script>";
    } else {
        echo "<script>alert('Gagal update status temuan: " . mysqli_error($koneksi) . "'); window.location.href='?snoserv=$no_service&tab=temuan';</script>";
    }
    exit;
}

// Delete Temuan
if(isset($_POST['btndeletetemuan'])) {
    $temuan_id = mysqli_real_escape_string($koneksi, $_POST['temuan_id']);
    $no_service = mysqli_real_escape_string($koneksi, $_POST['txtnosrv']);
    
    $query_delete = "DELETE FROM tbservis_temuan WHERE id = '$temuan_id'";
    
    if(mysqli_query($koneksi, $query_delete)) {
        echo "<script>alert('Temuan berhasil dihapus!'); window.location.href='?snoserv=$no_service&tab=temuan#temuan-penawaran';</script>";
    } else {
        echo "<script>alert('Gagal menghapus temuan!'); window.location.href='?snoserv=$no_service';</script>";
    }
    exit;
}

// ============================================
// HANDLER PENAWARAN PART
// ============================================

// Tambah Penawaran
if(isset($_POST['btnaddpenawaran'])) {
    $no_service = mysqli_real_escape_string($koneksi, $_POST['txtnosrv']);
    $temuan_id = !empty($_POST['temuan_id']) ? mysqli_real_escape_string($koneksi, $_POST['temuan_id']) : NULL;
    $kode_barang = mysqli_real_escape_string($koneksi, $_POST['kode_barang']);
    $nama_barang = mysqli_real_escape_string($koneksi, $_POST['nama_barang']);
    $quantity = isset($_POST['quantity']) ? (int) $_POST['quantity'] : 0;
    $harga_satuan = isset($_POST['harga_satuan']) ? (float) $_POST['harga_satuan'] : 0;
    
    // Jika harga 0 atau belum diisi, ambil dari master item (tblitem)
    if ($harga_satuan <= 0 && !empty($kode_barang)) {
        $qharga = mysqli_query($koneksi, "SELECT namaitem, hargajual FROM tblitem WHERE noitem='".$kode_barang."' LIMIT 1");
        if ($qharga && mysqli_num_rows($qharga) > 0) {
            $rit = mysqli_fetch_assoc($qharga);
            if (empty($nama_barang)) { $nama_barang = $rit['namaitem']; }
            $harga_satuan = (float)$rit['hargajual'];
        }
    }
    $total_harga = $quantity * $harga_satuan;
    $user_penawaran = $_SESSION['_nama'] ?? 'System';
    
    // Validate minimal
    if(empty($kode_barang) || empty($nama_barang)) {
        echo "<script>alert('Kode dan nama part wajib diisi.'); window.location.href='?snoserv=$no_service&tab=temuan#temuan-penawaran';</script>";
        exit;
    }
    if($quantity < 1) {
        echo "<script>alert('Quantity minimal 1.'); window.location.href='?snoserv=$no_service&tab=temuan#temuan-penawaran';</script>";
        exit;
    }
    
    // Cegah duplikasi dengan part yang sudah ada di servis (misal dari workorder atau penawaran yang disetujui)
    // 1) Cek di tblservis_barang (part yang sudah benar-benar masuk ke servis)
    $cek_barang_servis = mysqli_query($koneksi, "SELECT COUNT(*) AS cnt
                                               FROM tblservis_barang 
                                               WHERE no_service='$no_service' 
                                                 AND no_item='$kode_barang'");
    if ($cek_barang_servis) {
        $row_barang_servis = mysqli_fetch_array($cek_barang_servis);
        if (!empty($row_barang_servis['cnt']) && $row_barang_servis['cnt'] > 0) {
            echo "<script>alert('Part ini sudah ada di daftar barang servis (misalnya dari work order atau penawaran yang sudah disetujui). Penawaran baru untuk part yang sama dibatalkan.'); window.location.href='?snoserv=$no_service&tab=temuan#temuan-penawaran';</script>";
            exit;
        }
    }

    // 2) Cek penawaran aktif (pending/disetujui) untuk part yang sama di service yang sama
    $cek_penawaran = mysqli_query($koneksi, "SELECT COUNT(*) AS cnt
                                            FROM tbservis_penawaran_part
                                            WHERE no_service='$no_service'
                                              AND kode_barang='$kode_barang'
                                              AND status_penawaran IN ('pending','disetujui')");
    if ($cek_penawaran) {
        $row_penawaran = mysqli_fetch_array($cek_penawaran);
        if (!empty($row_penawaran['cnt']) && $row_penawaran['cnt'] > 0) {
            echo "<script>alert('Sudah ada penawaran aktif untuk part ini pada service yang sama. Duplikasi penawaran dibatalkan.'); window.location.href='?snoserv=$no_service&tab=temuan#temuan-penawaran';</script>";
            exit;
        }
    }
    
    // Note: tblitem doesn't have stok column
    // All parts treated as stok = 0 for approval workflow
    $stok_tersedia = 0;
    
    // Semua penawaran default pending untuk approval
    // Akan ditampilkan di tab Temuan & Penawaran untuk admin setujui/tolak
    $status_penawaran = 'pending';
    
    // Insert penawaran
    $temuan_id_value = $temuan_id ? "'$temuan_id'" : 'NULL';
    
    $query_insert = "INSERT INTO tbservis_penawaran_part 
                    (no_service, temuan_id, kode_barang, nama_barang, quantity, 
                     harga_satuan, total_harga, status_penawaran, user_penawaran) 
                    VALUES 
                    ('$no_service', $temuan_id_value, 
                     '$kode_barang', '$nama_barang', '$quantity', 
                     '$harga_satuan', '$total_harga', '$status_penawaran', '$user_penawaran')";
    
    if(mysqli_query($koneksi, $query_insert)) {
        // Catatan: Status temuan TIDAK diubah otomatis
        // Status temuan (diproses/selesai/tidak_selesai) dikelola terpisah dari status penawaran
        
        $msg = 'Penawaran berhasil ditambahkan!';
        if($stok_tersedia == 0) {
            $msg .= '\\n\\nPerhatian: Stok part = 0. Penawaran menunggu approval.';
        } else {
            $msg .= '\\n\\nPenawaran menunggu approval.';
        }
        
        echo "<script>alert('$msg'); window.location.href='?snoserv=$no_service&tab=temuan#temuan-penawaran';</script>";
    } else {
        echo "<script>alert('Gagal menambahkan penawaran: " . mysqli_error($koneksi) . "'); window.location.href='?snoserv=$no_service&tab=temuan#temuan-penawaran';</script>";
    }
    exit;
}

// Quick-Add Penawaran (dari rekomendasi mapping)
if(isset($_POST['btnquickaddpenawaran'])) {
    $no_service = mysqli_real_escape_string($koneksi, $_POST['txtnosrv']);
    $temuan_id = !empty($_POST['temuan_id']) ? mysqli_real_escape_string($koneksi, $_POST['temuan_id']) : NULL;
    $kode_barang = mysqli_real_escape_string($koneksi, $_POST['kode_barang']);
    $mapping_id = !empty($_POST['mapping_id']) ? mysqli_real_escape_string($koneksi, $_POST['mapping_id']) : NULL;
    $suggestion_priority = (isset($_POST['suggestion_priority']) && $_POST['suggestion_priority'] !== '') ? (int)$_POST['suggestion_priority'] : NULL;
    $user_penawaran = $_SESSION['_nama'] ?? 'System';

    if(empty($no_service) || empty($kode_barang)) {
        echo "<script>alert('Data Quick-Add tidak lengkap.'); window.location.href='?snoserv=$no_service&tab=temuan#temuan-penawaran';</script>";
        exit;
    }

    // Default quantity dari mapping
    $quantity = 1;
    if($mapping_id) {
        $q_map = mysqli_query($koneksi, "SELECT prioritas, qty_default FROM tbmaster_temuan_barang_mapping WHERE id='$mapping_id' LIMIT 1");
        if($q_map && mysqli_num_rows($q_map) > 0) {
            $rm = mysqli_fetch_assoc($q_map);
            if(!$suggestion_priority && isset($rm['prioritas'])) { $suggestion_priority = (int)$rm['prioritas']; }
            if(!empty($rm['qty_default']) && (int)$rm['qty_default'] > 0) { $quantity = (int)$rm['qty_default']; }
        }
    } else if ($temuan_id) {
        $q_tem = mysqli_query($koneksi, "SELECT kode_temuan FROM tbservis_temuan WHERE id='$temuan_id' LIMIT 1");
        if($q_tem && mysqli_num_rows($q_tem) > 0) {
            $rt = mysqli_fetch_assoc($q_tem);
            $kode_temuan = mysqli_real_escape_string($koneksi, $rt['kode_temuan']);
            if(!empty($kode_temuan)) {
                $q_map2 = mysqli_query($koneksi, "SELECT prioritas, qty_default FROM tbmaster_temuan_barang_mapping WHERE kode_temuan='$kode_temuan' AND noitem='$kode_barang' LIMIT 1");
                if($q_map2 && mysqli_num_rows($q_map2) > 0) {
                    $rm2 = mysqli_fetch_assoc($q_map2);
                    if(!$suggestion_priority && isset($rm2['prioritas'])) { $suggestion_priority = (int)$rm2['prioritas']; }
                    if(!empty($rm2['qty_default']) && (int)$rm2['qty_default'] > 0) { $quantity = (int)$rm2['qty_default']; }
                }
            }
        }
    }

    // Ambil harga dan nama dari master
    $nama_barang = $kode_barang;
    $harga_satuan = 0;
    $q_item = mysqli_query($koneksi, "SELECT namaitem, hargajual FROM tblitem WHERE noitem='$kode_barang' LIMIT 1");
    if($q_item && mysqli_num_rows($q_item) > 0) {
        $it = mysqli_fetch_assoc($q_item);
        $nama_barang = $it['namaitem'];
        $harga_satuan = (float)$it['hargajual'];
    }
    $total_harga = $quantity * $harga_satuan;

    // Dedup seperti biasa
    $cek_barang_servis = mysqli_query($koneksi, "SELECT COUNT(*) AS cnt FROM tblservis_barang WHERE no_service='$no_service' AND no_item='$kode_barang'");
    if ($cek_barang_servis) {
        $row_barang_servis = mysqli_fetch_array($cek_barang_servis);
        if (!empty($row_barang_servis['cnt']) && $row_barang_servis['cnt'] > 0) {
            echo "<script>alert('Part ini sudah ada di daftar barang servis. Quick-Add dibatalkan.'); window.location.href='?snoserv=$no_service&tab=temuan#temuan-penawaran';</script>";
            exit;
        }
    }
    $cek_penawaran = mysqli_query($koneksi, "SELECT COUNT(*) AS cnt FROM tbservis_penawaran_part WHERE no_service='$no_service' AND kode_barang='$kode_barang' AND status_penawaran IN ('pending','disetujui')");
    if ($cek_penawaran) {
        $row_penawaran = mysqli_fetch_array($cek_penawaran);
        if (!empty($row_penawaran['cnt']) && $row_penawaran['cnt'] > 0) {
            echo "<script>alert('Sudah ada penawaran aktif untuk part ini. Quick-Add dibatalkan.'); window.location.href='?snoserv=$no_service&tab=temuan#temuan-penawaran';</script>";
            exit;
        }
    }

    $temuan_id_value = $temuan_id ? "'$temuan_id'" : 'NULL';
    $priority_value = ($suggestion_priority !== NULL) ? (int)$suggestion_priority : 'NULL';
    $catatan_value = $mapping_id ? ("'mapping_id=".mysqli_real_escape_string($koneksi,$mapping_id)."'") : 'NULL';

    $query_insert = "INSERT INTO tbservis_penawaran_part 
                    (no_service, temuan_id, is_from_suggestion, suggestion_priority, kode_barang, nama_barang, quantity, harga_satuan, total_harga, status_penawaran, user_penawaran, catatan_penawaran)
                    VALUES
                    ('$no_service', $temuan_id_value, 1, $priority_value, '$kode_barang', '".mysqli_real_escape_string($koneksi,$nama_barang)."', $quantity, $harga_satuan, $total_harga, 'pending', '".mysqli_real_escape_string($koneksi,$user_penawaran)."', $catatan_value)";

    if(mysqli_query($koneksi, $query_insert)) {
        echo "<script>alert('Penawaran (Quick-Add) berhasil ditambahkan!'); window.location.href='?snoserv=$no_service&tab=temuan#temuan-penawaran';</script>";
    } else {
        echo "<script>alert('Gagal Quick-Add penawaran: " . mysqli_error($koneksi) . "'); window.location.href='?snoserv=$no_service&tab=temuan#temuan-penawaran';</script>";
    }
    exit;
}

// Setujui Penawaran
if(isset($_POST['btnsetujuipenawaran'])) {
    $penawaran_id = mysqli_real_escape_string($koneksi, $_POST['penawaran_id']);
    $no_service = mysqli_real_escape_string($koneksi, $_POST['txtnosrv']);
    $user_respon = $_SESSION['_nama'] ?? 'System';
    
    // Get penawaran data
    $query_penawaran = mysqli_query($koneksi, "SELECT * FROM tbservis_penawaran_part WHERE id = '$penawaran_id'");
    $penawaran = mysqli_fetch_array($query_penawaran);
    
    if($penawaran) {
        // Update status penawaran
        $query_update = "UPDATE tbservis_penawaran_part 
                        SET status_penawaran = 'disetujui', 
                            tanggal_respon = NOW(), 
                            user_respon = '$user_respon' 
                        WHERE id = '$penawaran_id'";
        
        if(mysqli_query($koneksi, $query_update)) {
            // Auto-add ke tblservis_barang
            $kode_barang = $penawaran['kode_barang'];
            $quantity = $penawaran['quantity'];
            $harga_jual = $penawaran['harga_satuan'];
            $total = $penawaran['total_harga'];

            // Get next nobaris for this service
            $q_nobaris = mysqli_query($koneksi, "SELECT COALESCE(MAX(nobaris), 0) + 1 as next_nobaris
                                                 FROM tblservis_barang WHERE no_service='$no_service'");
            $nobaris_data = mysqli_fetch_array($q_nobaris);
            $nobaris = $nobaris_data['next_nobaris'] ?? 1;

            $query_add_barang = "INSERT INTO tblservis_barang
                                (no_service, nobaris, no_item, quantity, qty_retur, harga_jual, potongan, total)
                                VALUES
                                ('$no_service', '$nobaris', '$kode_barang', '$quantity', 0, '$harga_jual', 0, '$total')";

            mysqli_query($koneksi, $query_add_barang);
            
            // Catatan: Status temuan TIDAK diubah otomatis
            // Status temuan dikelola terpisah dari status penawaran
            
            echo "<script>alert('Penawaran disetujui dan part ditambahkan ke servis!'); window.location.href='?snoserv=$no_service&tab=temuan';</script>";
        } else {
            echo "<script>alert('Gagal menyetujui penawaran!'); window.location.href='?snoserv=$no_service';</script>";
        }
    }
    exit;
}

// Tolak Penawaran
if(isset($_POST['btntolakpenawaran'])) {
    $penawaran_id = mysqli_real_escape_string($koneksi, $_POST['penawaran_id_tolak']);
    $no_service = mysqli_real_escape_string($koneksi, $_POST['txtnosrv']);
    $alasan_tolak = mysqli_real_escape_string($koneksi, $_POST['alasan_tolak']);
    $keterangan_tolak = mysqli_real_escape_string($koneksi, $_POST['keterangan_tolak']);
    $user_respon = $_SESSION['_nama'] ?? 'System';
    
    // Get temuan_id
    $query_penawaran = mysqli_query($koneksi, "SELECT temuan_id FROM tbservis_penawaran_part WHERE id = '$penawaran_id'");
    $penawaran = mysqli_fetch_array($query_penawaran);
    
    // Update status penawaran
    $query_update = "UPDATE tbservis_penawaran_part 
                    SET status_penawaran = 'ditolak', 
                        alasan_tolak = '$alasan_tolak', 
                        keterangan_tolak = '$keterangan_tolak', 
                        tanggal_respon = NOW(), 
                        user_respon = '$user_respon' 
                    WHERE id = '$penawaran_id'";
    
    if(mysqli_query($koneksi, $query_update)) {
        // Catatan: Status temuan TIDAK diubah otomatis
        // Status temuan dikelola terpisah dari status penawaran
        
        echo "<script>alert('Penawaran ditolak dan dicatat!'); window.location.href='?snoserv=$no_service&tab=temuan';</script>";
    } else {
        echo "<script>alert('Gagal menolak penawaran!'); window.location.href='?snoserv=$no_service';</script>";
    }
    exit;
}

// ============================================
// HANDLER PENAWARAN JASA
// ============================================

// Setujui Penawaran Jasa
if(isset($_POST['btnsetujuipenawaran_jasa'])) {
    $penawaran_id = mysqli_real_escape_string($koneksi, $_POST['penawaran_jasa_id']);
    $no_service = mysqli_real_escape_string($koneksi, $_POST['txtnosrv']);
    $user_respon = $_SESSION['_nama'] ?? 'System';

    // Get penawaran data
    $query_penawaran = mysqli_query($koneksi, "SELECT * FROM tbservis_penawaran_jasa WHERE id = '$penawaran_id'");
    $penawaran = mysqli_fetch_array($query_penawaran);

    if($penawaran) {
        // Update status penawaran
        $query_update = "UPDATE tbservis_penawaran_jasa
                        SET status_penawaran = 'disetujui',
                            tanggal_respon = NOW(),
                            user_respon = '$user_respon'
                        WHERE id = '$penawaran_id'";

        if(mysqli_query($koneksi, $query_update)) {
            // Auto-add ke tblservis_jasa
            $kode_jasa = $penawaran['kode_jasa'];
            $harga = $penawaran['harga'];
            $waktu = $penawaran['waktu_estimasi'];

            // Get next nobaris for this service
            $q_nobaris = mysqli_query($koneksi, "SELECT COALESCE(MAX(nobaris), 0) + 1 as next_nobaris
                                                 FROM tblservis_jasa WHERE no_service='$no_service'");
            $nobaris_data = mysqli_fetch_array($q_nobaris);
            $nobaris = $nobaris_data['next_nobaris'] ?? 1;

            // Check waktu column exists
            $check_waktu = mysqli_query($koneksi, "SHOW COLUMNS FROM tblservis_jasa LIKE 'waktu'");
            $has_waktu = ($check_waktu && mysqli_num_rows($check_waktu) > 0);

            if($has_waktu) {
                $query_add_jasa = "INSERT INTO tblservis_jasa
                                    (no_service, nobaris, no_item, harga, waktu, potongan, total)
                                    VALUES
                                    ('$no_service', '$nobaris', '$kode_jasa', '$harga', '$waktu', 0, '$harga')";
            } else {
                $query_add_jasa = "INSERT INTO tblservis_jasa
                                    (no_service, nobaris, no_item, harga, potongan, total)
                                    VALUES
                                    ('$no_service', '$nobaris', '$kode_jasa', '$harga', 0, '$harga')";
            }

            mysqli_query($koneksi, $query_add_jasa);

            echo "<script>alert('Penawaran jasa disetujui dan ditambahkan ke servis!'); window.location.href='?snoserv=$no_service&tab=temuan';</script>";
        } else {
            echo "<script>alert('Gagal menyetujui penawaran jasa!'); window.location.href='?snoserv=$no_service';</script>";
        }
    }
    exit;
}

// Tolak Penawaran Jasa
if(isset($_POST['btntolakpenawaran_jasa'])) {
    $penawaran_id = mysqli_real_escape_string($koneksi, $_POST['penawaran_jasa_id_tolak']);
    $no_service = mysqli_real_escape_string($koneksi, $_POST['txtnosrv']);
    $alasan_tolak = mysqli_real_escape_string($koneksi, $_POST['alasan_tolak_jasa']);
    $keterangan_tolak = mysqli_real_escape_string($koneksi, $_POST['keterangan_tolak_jasa']);
    $user_respon = $_SESSION['_nama'] ?? 'System';

    // Update status penawaran
    $query_update = "UPDATE tbservis_penawaran_jasa
                    SET status_penawaran = 'ditolak',
                        alasan_tolak = '$alasan_tolak',
                        keterangan_tolak = '$keterangan_tolak',
                        tanggal_respon = NOW(),
                        user_respon = '$user_respon'
                    WHERE id = '$penawaran_id'";

    if(mysqli_query($koneksi, $query_update)) {
        echo "<script>alert('Penawaran jasa ditolak dan dicatat!'); window.location.href='?snoserv=$no_service&tab=temuan';</script>";
    } else {
        echo "<script>alert('Gagal menolak penawaran jasa!'); window.location.href='?snoserv=$no_service';</script>";
    }
    exit;
}

// Delete Penawaran Jasa
if(isset($_POST['btndeletepenawaran_jasa'])) {
    $penawaran_id = mysqli_real_escape_string($koneksi, $_POST['penawaran_jasa_id']);
    $no_service = mysqli_real_escape_string($koneksi, $_POST['txtnosrv']);

    $query_delete = "DELETE FROM tbservis_penawaran_jasa WHERE id = '$penawaran_id'";

    if(mysqli_query($koneksi, $query_delete)) {
        echo "<script>alert('Penawaran jasa berhasil dihapus!'); window.location.href='?snoserv=$no_service&tab=temuan';</script>";
    } else {
        echo "<script>alert('Gagal menghapus penawaran jasa!'); window.location.href='?snoserv=$no_service';</script>";
    }
    exit;
}

// Delete Penawaran
if(isset($_POST['btndeletepenawaran'])) {
    $penawaran_id = mysqli_real_escape_string($koneksi, $_POST['penawaran_id']);
    $no_service = mysqli_real_escape_string($koneksi, $_POST['txtnosrv']);
    
    $query_delete = "DELETE FROM tbservis_penawaran_part WHERE id = '$penawaran_id'";
    
    if(mysqli_query($koneksi, $query_delete)) {
        echo "<script>alert('Penawaran berhasil dihapus!'); window.location.href='?snoserv=$no_service&tab=temuan';</script>";
    } else {
        echo "<script>alert('Gagal menghapus penawaran!'); window.location.href='?snoserv=$no_service';</script>";
    }
    exit;
}

// Edit Temuan
if(isset($_POST['btnedittemuan'])) {
    $temuan_id = mysqli_real_escape_string($koneksi, $_POST['temuan_id']);
    $no_service = mysqli_real_escape_string($koneksi, $_POST['txtnosrv']);
    $nama_temuan = isset($_POST['nama_temuan']) ? mysqli_real_escape_string($koneksi, $_POST['nama_temuan']) : '';
    $kode_temuan_post = isset($_POST['kode_temuan']) ? mysqli_real_escape_string($koneksi, $_POST['kode_temuan']) : '';
    $deskripsi_temuan = mysqli_real_escape_string($koneksi, $_POST['deskripsi_temuan'] ?? '');
    $jenis_perbaikan = mysqli_real_escape_string($koneksi, $_POST['jenis_perbaikan']);
    $tingkat_urgensi = mysqli_real_escape_string($koneksi, $_POST['tingkat_urgensi']);
    $status_temuan = mysqli_real_escape_string($koneksi, $_POST['status_temuan']);
    $estimasi_biaya = mysqli_real_escape_string($koneksi, $_POST['estimasi_biaya'] ?? 0);
    
    // Enforce: wajib pilih dari master (kode_temuan tidak boleh kosong)
    if (empty($kode_temuan_post)) {
        echo "<script>alert('Wajib memilih Temuan dari Master. Silakan klik tombol Pilih dan pilih salah satu.'); window.location.href='servis-input-reguler.php?snoserv=$no_service&tab=temuan';</script>";
        exit;
    }

    // Build dynamic update based on selected master kode_temuan
    $set_parts = [];
    $set_parts[] = "kode_temuan = '$kode_temuan_post'";
    $set_parts[] = "temuan_custom = NULL";
    $set_parts[] = "deskripsi_temuan = '$deskripsi_temuan'";
    $set_parts[] = "jenis_perbaikan = '$jenis_perbaikan'";
    $set_parts[] = "tingkat_urgensi = '$tingkat_urgensi'";
    $set_parts[] = "status_temuan = '$status_temuan'";
    $set_parts[] = "estimasi_biaya = '$estimasi_biaya'";
    $set_parts[] = "updated_at = NOW()";

    $query_update = "UPDATE tbservis_temuan SET " . implode(", ", $set_parts) . " WHERE id = '$temuan_id'";
    
    if(mysqli_query($koneksi, $query_update)) {
        echo "<script>alert('Temuan berhasil diupdate!'); window.location.href='servis-input-reguler.php?snoserv=$no_service&tab=temuan';</script>";
    } else {
        echo "<script>alert('Gagal mengupdate temuan: " . mysqli_error($koneksi) . "'); window.history.back();</script>";
    }
    exit;
}
?>
