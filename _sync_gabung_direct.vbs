' ============================================================
' _sync_gabung_direct.vbs
' Direct sync: FITMOTOR GABUNG.mdb -> MySQL fitmotor_dbbengkel
' Jalankan dari: cscript //NoLogo _sync_gabung_direct.vbs [dataset]
' Contoh: cscript //NoLogo _sync_gabung_direct.vbs rekap_konsumen
'          cscript //NoLogo _sync_gabung_direct.vbs all
' ============================================================

Option Explicit

Const MDB_PATH  = "E:\BENGKEL 2.0\FITMOTOR GABUNG.mdb"
Const MYSQL_EXE = "C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin\mysql.exe"
Const DB_HOST   = "127.0.0.1"
Const DB_USER   = "fitmotor_LOGIN"
Const DB_PASS   = "Sayalupa12"
Const DB_NAME   = "fitmotor_dbbengkel"
Const BATCH_SZ  = 200
Const TMP_DIR   = "C:\laragon\tmp\"

Dim db, dbe, fso, oShell, runTarget

Set fso    = CreateObject("Scripting.FileSystemObject")
Set oShell = CreateObject("WScript.Shell")
Set dbe    = CreateObject("DAO.DBEngine.120")

If Not fso.FolderExists(TMP_DIR) Then fso.CreateFolder(TMP_DIR)

On Error Resume Next
Set db = dbe.OpenDatabase(MDB_PATH, False, False)
If Err.Number <> 0 Then
    WScript.Echo "ERROR: Tidak bisa buka " & MDB_PATH & " - " & Err.Description
    WScript.Quit 1
End If
On Error GoTo 0

runTarget = "all"
If WScript.Arguments.Count > 0 Then runTarget = LCase(WScript.Arguments(0))

WScript.Echo "=== FITMOTOR GABUNG Direct Sync === " & Now()
WScript.Echo "Target: " & runTarget
WScript.Echo ""

' Pastikan tabel statistik minggu ada
RunSQL "CREATE TABLE IF NOT EXISTS gabung_statistik_item_minggu (" & _
    "noitem VARCHAR(50) NOT NULL, sts VARCHAR(10) NOT NULL, " & _
    "m1 DECIMAL(12,2) DEFAULT 0, m2 DECIMAL(12,2) DEFAULT 0, " & _
    "m3 DECIMAL(12,2) DEFAULT 0, m4 DECIMAL(12,2) DEFAULT 0, " & _
    "m5 DECIMAL(12,2) DEFAULT 0, m6 DECIMAL(12,2) DEFAULT 0, " & _
    "m7 DECIMAL(12,2) DEFAULT 0, m8 DECIMAL(12,2) DEFAULT 0, " & _
    "m9 DECIMAL(12,2) DEFAULT 0, m10 DECIMAL(12,2) DEFAULT 0, " & _
    "m11 DECIMAL(12,2) DEFAULT 0, m12 DECIMAL(12,2) DEFAULT 0, " & _
    "synced_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, " & _
    "PRIMARY KEY (noitem, sts));"

' ── Dataset list ─────────────────────────────────────────────
' Format: key | access_table | mysql_table | mode
' access_table prefix _MULTI_ = multi-tabel per-cabang
Dim dsKeys(17), dsTbls(17), dsMySQL(17), dsMode(17)

dsKeys(0)="rekap_konsumen"       : dsTbls(0)="REKAP_KONSUMEN_DATA"
dsMySQL(0)="gabung_rekap_konsumen"           : dsMode(0)="replace"

dsKeys(1)="rekap_kedatangan"     : dsTbls(1)="REKAP_KONSUMEN_DATANGBERIKUTNYA_DATA"
dsMySQL(1)="gabung_rekap_kedatangan"         : dsMode(1)="replace"

dsKeys(2)="rekap_konsumen_wa"    : dsTbls(2)="REKAP_KONSUMEN_NOMOR_WA_DATA"
dsMySQL(2)="gabung_rekap_konsumen_wa"        : dsMode(2)="replace"

dsKeys(3)="nopolisi_domisili"    : dsTbls(3)="NOPOLISI_DOMISILI_DATA"
dsMySQL(3)="gabung_nopolisi_domisili"        : dsMode(3)="replace"

dsKeys(4)="rekap_peritem_terakhir": dsTbls(4)="REKAP_JUALSERVIS_PERITEM_TERAKHIR_DATA"
dsMySQL(4)="gabung_rekap_peritem_terakhir"   : dsMode(4)="replace"

dsKeys(5)="pernah_jemputantar"   : dsTbls(5)="PERNAH_JEMPUTANTAR_DATA"
dsMySQL(5)="gabung_pernah_jemputantar"       : dsMode(5)="replace"

dsKeys(6)="rekonsil_antarcabang" : dsTbls(6)="REKONSIL_JUAL_BELI_ANTARCABANG_DATA"
dsMySQL(6)="gabung_rekonsil_antarcabang"     : dsMode(6)="replace"

dsKeys(7)="hpp_acuan"            : dsTbls(7)="LABARUGI_ACUAN_HPP_PEMBELIAN_TEMP"
dsMySQL(7)="gabung_hpp_acuan"                : dsMode(7)="replace"

dsKeys(8)="tipemotor"            : dsTbls(8)="TIPEMOTOR"
dsMySQL(8)="tbtipemotor"                     : dsMode(8)="replace"

dsKeys(9)="bagi_hasil"           : dsTbls(9)="BAGI_HASIL"
dsMySQL(9)="tbbagi_hasil"                    : dsMode(9)="replace"

dsKeys(10)="persen_insentif"     : dsTbls(10)="PERSEN_INSENTIF"
dsMySQL(10)="tbpersen_insentif"              : dsMode(10)="replace"

dsKeys(11)="gabung_statistik_minggu" : dsTbls(11)="GABUNG_STATISTIK_ITEM_MINGGU_PIVOT"
dsMySQL(11)="gabung_statistik_item_minggu"   : dsMode(11)="replace"

dsKeys(12)="siklus"              : dsTbls(12)="_MULTI_SIKLUS_"
dsMySQL(12)="tbsiklus"                       : dsMode(12)="replace"

dsKeys(13)="service_jemputantar" : dsTbls(13)="_MULTI_JEMPUTANTAR_"
dsMySQL(13)="tblservice_jemputantar"         : dsMode(13)="replace"

dsKeys(14)="item_mutasi_header"  : dsTbls(14)="_MULTI_ITEMM_HEADER_"
dsMySQL(14)="tblitem_mutasi_header"          : dsMode(14)="replace"

dsKeys(15)="item_mutasi_detail"  : dsTbls(15)="_MULTI_ITEMM_DETAIL_"
dsMySQL(15)="tblitem_mutasi_detail"          : dsMode(15)="replace"

dsKeys(16)="item_koreksi_header" : dsTbls(16)="_MULTI_ITEMK_HEADER_"
dsMySQL(16)="tblitem_koreksi_header"         : dsMode(16)="replace"

dsKeys(17)="item_koreksi_detail" : dsTbls(17)="_MULTI_ITEMK_DETAIL_"
dsMySQL(17)="tblitem_koreksi_detail"         : dsMode(17)="replace"

Dim i, ran
ran = 0
For i = 0 To 17
    If runTarget = "all" Or runTarget = dsKeys(i) Then
        SyncDataset dsKeys(i), dsTbls(i), dsMySQL(i), dsMode(i)
        ran = ran + 1
    End If
Next

If ran = 0 Then
    WScript.Echo "Dataset tidak dikenal: " & runTarget
    WScript.Echo "Gunakan: all, rekap_konsumen, rekap_kedatangan, rekap_konsumen_wa,"
    WScript.Echo "  nopolisi_domisili, rekap_peritem_terakhir, pernah_jemputantar,"
    WScript.Echo "  rekonsil_antarcabang, hpp_acuan, tipemotor, bagi_hasil,"
    WScript.Echo "  persen_insentif, gabung_statistik_minggu, siklus,"
    WScript.Echo "  service_jemputantar, item_mutasi_header, item_mutasi_detail,"
    WScript.Echo "  item_koreksi_header, item_koreksi_detail"
End If

db.Close
WScript.Echo ""
WScript.Echo "=== SELESAI === " & Now()

' ================================================================
Sub SyncDataset(dsKey, accessTable, mysqlTable, syncMode)
    WScript.Echo "[" & dsKey & "] Memproses..."
    Dim t0 : t0 = Timer()
    If Left(accessTable, 7) = "_MULTI_" Then
        SyncMultiTable dsKey, accessTable, mysqlTable, syncMode
    Else
        SyncSingleTable dsKey, accessTable, mysqlTable, syncMode
    End If
    WScript.Echo "[" & dsKey & "] Done (" & Int((Timer()-t0)*10)/10 & "s)" & Chr(10)
End Sub

Sub SyncSingleTable(dsKey, accessTable, mysqlTable, syncMode)
    Dim rs, sqlFile, ts, rowCount, batchRows, sqlLine
    sqlFile = TMP_DIR & "g_" & dsKey & ".sql"

    On Error Resume Next
    Set rs = db.OpenRecordset("SELECT * FROM [" & accessTable & "]", 4)
    If Err.Number <> 0 Then
        WScript.Echo "  ERROR: " & Err.Description : Err.Clear
        On Error GoTo 0 : Exit Sub
    End If
    On Error GoTo 0

    Set ts = fso.CreateTextFile(sqlFile, True)
    ts.WriteLine "SET NAMES utf8mb4; SET foreign_key_checks=0;"
    If syncMode = "replace" Then ts.WriteLine "TRUNCATE TABLE `" & mysqlTable & "`;"

    rowCount = 0 : batchRows = 0 : sqlLine = ""
    Dim rv, writeErr
    ' Pakai batchSize=1 untuk dataset dengan field Memo/WA yang rentan null-char error
    Dim batchSize : batchSize = BATCH_SZ
    If dsKey = "rekap_konsumen_wa" Then batchSize = 1
    Do While Not rs.EOF
        On Error Resume Next
        rv = BuildRowVal(rs, dsKey)
        If Err.Number <> 0 Then rv = "" : Err.Clear
        On Error GoTo 0
        If rv <> "" Then
            If batchSize = 1 Then
                ' Tulis satu INSERT per baris langsung
                On Error Resume Next
                ts.WriteLine "INSERT IGNORE INTO `" & mysqlTable & "` VALUES" & rv & ";"
                If Err.Number <> 0 Then Err.Clear
                On Error GoTo 0
            Else
                If batchRows = 0 Then sqlLine = "INSERT IGNORE INTO `" & mysqlTable & "` VALUES"
                If batchRows > 0 Then sqlLine = sqlLine & ","
                sqlLine = sqlLine & rv
                batchRows = batchRows + 1
                If batchRows >= batchSize Then
                    On Error Resume Next
                    ts.WriteLine sqlLine & ";"
                    writeErr = Err.Number
                    If writeErr <> 0 Then Err.Clear
                    On Error GoTo 0
                    sqlLine = "" : batchRows = 0
                End If
            End If
            rowCount = rowCount + 1
        End If
        rs.MoveNext
    Loop
    If batchRows > 0 Then
        On Error Resume Next
        ts.WriteLine sqlLine & ";"
        If Err.Number <> 0 Then Err.Clear
        On Error GoTo 0
    End If
    ts.WriteLine "SET foreign_key_checks=1;"
    ts.Close : rs.Close

    RunSQLFile sqlFile
    LogSync dsKey, accessTable, mysqlTable, rowCount
    WScript.Echo "  OK: " & rowCount & " baris"
End Sub

Sub SyncMultiTable(dsKey, marker, mysqlTable, syncMode)
    Dim kodes(4), sufxs(4)
    kodes(0)="PSL":sufxs(0)="PESALAKAN"
    kodes(1)="PCL":sufxs(1)="PACUL"
    kodes(2)="CIK":sufxs(2)="CIKDITIRO"
    kodes(3)="TRY":sufxs(3)="TRAYEMAN"
    kodes(4)="PST":sufxs(4)="PUSAT"

    Dim sqlFile, ts
    sqlFile = TMP_DIR & "g_" & dsKey & ".sql"
    Set ts = fso.CreateTextFile(sqlFile, True)
    ts.WriteLine "SET NAMES utf8mb4; SET foreign_key_checks=0;"
    If syncMode = "replace" Then ts.WriteLine "TRUNCATE TABLE `" & mysqlTable & "`;"

    Dim totalRows, c, srcTable, rs, batchRows, sqlLine, rowCount
    totalRows = 0

    For c = 0 To 4
        Select Case marker
            Case "_MULTI_SIKLUS_"       : srcTable = "SIKLUS_" & sufxs(c)
            Case "_MULTI_JEMPUTANTAR_"  : srcTable = "TBLService_JEMPUTANTAR_" & sufxs(c)
            Case "_MULTI_ITEMM_HEADER_" : srcTable = "TBLItemMHeader_" & sufxs(c)
            Case "_MULTI_ITEMM_DETAIL_" : srcTable = "TBLItemMDetail_" & sufxs(c)
            Case "_MULTI_ITEMK_HEADER_" : srcTable = "TBLItemKHeader_" & sufxs(c)
            Case "_MULTI_ITEMK_DETAIL_" : srcTable = "TBLItemKDetail_" & sufxs(c)
        End Select

        On Error Resume Next
        Set rs = db.OpenRecordset("SELECT * FROM [" & srcTable & "]", 4)
        If Err.Number <> 0 Then
            WScript.Echo "  SKIP " & srcTable & ": " & Err.Description
            Err.Clear : On Error GoTo 0
        Else
            On Error GoTo 0
            rowCount = 0 : batchRows = 0 : sqlLine = ""
            Do While Not rs.EOF
                If batchRows = 0 Then sqlLine = "INSERT IGNORE INTO `" & mysqlTable & "` VALUES"
                Dim mrv : mrv = BuildMultiVal(rs, dsKey, kodes(c))
                If batchRows > 0 Then sqlLine = sqlLine & ","
                sqlLine = sqlLine & mrv
                rowCount = rowCount+1 : batchRows = batchRows+1
                If batchRows >= BATCH_SZ Then
                    ts.WriteLine sqlLine & ";" : sqlLine="" : batchRows=0
                End If
                rs.MoveNext
            Loop
            If batchRows > 0 Then ts.WriteLine sqlLine & ";"
            rs.Close
            totalRows = totalRows + rowCount
            WScript.Echo "  " & sufxs(c) & ": " & rowCount & " baris"
        End If
    Next

    ts.WriteLine "SET foreign_key_checks=1;"
    ts.Close
    RunSQLFile sqlFile
    LogSync dsKey, marker, mysqlTable, totalRows
    WScript.Echo "  Total: " & totalRows & " baris"
End Sub

Function BuildRowVal(rs, dsKey)
    Dim v
    Select Case dsKey
      Case "rekap_konsumen"
        v="("&Q(rs("NoPolisi"))&","&N(rs("JUMLAH"))&","&D(rs("AWAL"))&","&D(rs("AKHIR"))&","& _
          N(rs("PELANGGAN_SEJAK_HARI"))&","&N(rs("PELANGGAN_SEJAK_BULAN"))&","& _
          N(rs("LAMA_TDK_DTG_HARI"))&","&N(rs("LAMA_TDK_DTG_BULAN"))&","& _
          N(rs("TOTAL"))&","&N(rs("RATA2"))&","&D(rs("tanggal_data"))&",NOW())"
      Case "rekap_kedatangan"
        v="("&Q(rs("NoPolisi"))&","&N(rs("JUMLAH"))&","&D(rs("AWAL"))&","&D(rs("AKHIR"))&","& _
          N(rs("PELANGGAN_SEJAK_HARI"))&","&N(rs("PELANGGAN_SEJAK_BULAN"))&","& _
          N(rs("LAMA_TDK_DTG_HARI"))&","&N(rs("LAMA_TDK_DTG_BULAN"))&","& _
          N(rs("TOTAL"))&","&N(rs("RATA2"))&","& _
          D(rs("GANTI_OLI_AKHIR"))&","&D(rs("SERVIS_AKHIR"))&","& _
          D(rs("DATANG_BERIKUTNYA"))&","&D(rs("REMIND_SEBELUMNYA"))&","& _
          D(rs("tanggal_data"))&",NOW())"
      Case "rekap_konsumen_wa"
        v="("&Q(rs("NamaPelanggan"))&","&Q(rs("DOMISILI"))&","&N(rs("JUM_MOTOR"))&","& _
          N(rs("TOT_DATANG"))&","&N(rs("TOTAL_NILAI"))&","&N(rs("RATA2_NILAI"))&","& _
          D(rs("AWAL"))&","&D(rs("AKHIR"))&","&D(rs("DATANG_BERIKUTNYA"))&","& _
          N(rs("LAMA_PELANGGAN"))&","&N(rs("LAMA_TDK_DATANG"))&","& _
          D(rs("TIAP_30HARI"))&","&Q(rs("Telephone"))&","&D(rs("tanggal_data"))&",NOW())"
      Case "nopolisi_domisili"
        v="("&Q(rs("NoPolisi"))&","&N(rs("STS_PES"))&","&N(rs("STS_PAC"))&","& _
          N(rs("STS_CIK"))&","&N(rs("STS_TRA"))&","&D(rs("AKHIR"))&","& _
          Q(rs("STS"))&","&Q(rs("DOMISILI"))&","&D(rs("tanggal_data"))&",NOW())"
      Case "rekap_peritem_terakhir"
        v="("&Q(rs("NoItem"))&","&Q(rs("STS"))&","&D(rs("TGGL"))&","&D(rs("tanggal_data"))&",NOW())"
      Case "pernah_jemputantar"
        v="("&Q(rs("NoPolisi"))&","&N(rs("JUMLAH"))&","&D(rs("tanggal_data"))&",NOW())"
      Case "rekonsil_antarcabang"
        v="(DEFAULT,"&Q(rs("CABANG"))&","&D(rs("TANGGAL"))&","& _
          Q(rs("KODE_JUAL"))&","&Q(rs("NAMA_JUAL"))&","&Q(rs("TIPE_JUAL"))&","& _
          Q(rs("KATEGORI_JUAL"))&","&Q(rs("NO_JUAL"))&","&Q(rs("ITEM_JUAL"))&","& _
          N(rs("QTY_JUAL"))&","&N(rs("HPP_JUAL"))&","&N(rs("NILAI_JUAL"))&","& _
          Q(rs("SUMBER"))&","&Q(rs("KODE_BELI"))&","&Q(rs("NAMA_BELI"))&","& _
          Q(rs("TIPE_BELI"))&","&Q(rs("KATEGORI_BELI"))&","&Q(rs("NO_BELI"))&","& _
          Q(rs("ITEM_BELI"))&","&N(rs("QTY_BELI"))&","&N(rs("HPP_BELI"))&","& _
          N(rs("NILAI_BELI"))&","&Q(rs("ID_BELI"))&","&D(rs("tanggal_data"))&",NOW())"
      Case "hpp_acuan"
        v="("&Q(rs("TAHUN"))&","&Q(rs("SIKLUS"))&","&Q(rs("NoItem"))&","& _
          N(rs("FREK"))&","&N(rs("QTY"))&","&N(rs("BELI"))&","&N(rs("ACUAN_HPP"))&","& _
          Q(rs("STS"))&",NOW())"
      Case "tipemotor"
        v="("&Q(rs("TIPE"))&","&Q(rs("PABRIK"))&","&Q(rs("KATEGORI"))&",NOW())"
      Case "bagi_hasil"
        v="("&Q(rs("STTS"))&","&Q(rs("KATEGORI"))&","&N(rs("PERSEN_BAGIHASIL"))&",NOW())"
      Case "persen_insentif"
        v="("&Q(rs("POSISI"))&","&N(rs("PERSEN_BARANG"))&","&N(rs("PERSEN_JASA"))&",NOW())"
      Case "gabung_statistik_minggu"
        v="("&Q(rs("NOITEM"))&","&Q(rs("STS"))&","& _
          N(rs("M1"))&","&N(rs("M2"))&","&N(rs("M3"))&","&N(rs("M4"))&","& _
          N(rs("M5"))&","&N(rs("M6"))&","&N(rs("M7"))&","&N(rs("M8"))&","& _
          N(rs("M9"))&","&N(rs("M10"))&","&N(rs("M11"))&","&N(rs("M12"))&",NOW())"
      Case Else
        v="(NULL)"
    End Select
    BuildRowVal = v
End Function

Function BuildMultiVal(rs, dsKey, kd)
    Dim v
    Select Case dsKey
      Case "siklus"
        v="("&Q(kd)&","&Q(rs("SIKLUS"))&","&D(rs("TGLAWAL"))&","&D(rs("TGLAKHIR"))&",NOW())"
      Case "service_jemputantar"
        Dim jamV
        On Error Resume Next
        If IsNull(rs("JAM")) Or IsEmpty(rs("JAM")) Then
            jamV="NULL"
        Else
            Dim dtJ:dtJ=CDate(rs("JAM"))
            jamV="'"+Right("00"&Hour(dtJ),2)+":"+Right("00"&Minute(dtJ),2)+":00'"
        End If
        On Error GoTo 0
        v="("&Q(rs("NoService"))&","&Q(kd)&","&D(rs("Tanggal"))&","& _
          Q(rs("NoPelanggan"))&","&Q(rs("NoPolisi"))&","&jamV&","& _
          Q(rs("Keterangan"))&",NOW())"
      Case "item_mutasi_header"
        v="("&Q(rs("NoTransaksi"))&","&Q(kd)&","&D(rs("Tanggal"))&","& _
          N(rs("TotalQty"))&","&N(rs("Total"))&","&QM(rs("Note"))&","& _
          Q(rs("User"))&","&Q(rs("IDTabel"))&",NOW())"
      Case "item_mutasi_detail"
        v="("&Q(rs("NoTransaksi"))&","&Q(kd)&","&N(rs("NoBaris"))&","& _
          Q(rs("NoItem"))&","&Q(rs("TypeTr"))&","&N(rs("Qty"))&","& _
          N(rs("Harga"))&","&N(rs("Total"))&","&QM(rs("Note"))&",NOW())"
      Case "item_koreksi_header"
        v="("&Q(rs("NoTransaksi"))&","&Q(kd)&","&D(rs("Tanggal"))&","& _
          N(rs("TotalQty"))&","&N(rs("Total"))&","&QM(rs("Note"))&","& _
          Q(rs("User"))&","&Q(rs("IDTabel"))&",NOW())"
      Case "item_koreksi_detail"
        v="("&Q(rs("NoTransaksi"))&","&Q(kd)&","&N(rs("NoBaris"))&","& _
          Q(rs("NoItem"))&","&Q(rs("TypeTr"))&","&N(rs("Qty"))&","& _
          N(rs("Harga"))&","&N(rs("HargaPokok"))&","&N(rs("Total"))&","& _
          QM(rs("Note"))&",NOW())"
      Case Else
        v="(NULL)"
    End Select
    BuildMultiVal = v
End Function

Function Q(v)
    If IsNull(v) Or IsEmpty(v) Then Q="NULL":Exit Function
    On Error Resume Next
    Dim s:s=CStr(v)
    If Err.Number<>0 Then Q="NULL":Err.Clear:On Error GoTo 0:Exit Function
    On Error GoTo 0
    If Len(s)>500 Then s=Left(s,500)
    ' Hapus null char dan karakter kontrol yang menyebabkan ts.WriteLine gagal
    s=Replace(s,Chr(0),"")
    s=Replace(s,Chr(13)," ")
    s=Replace(s,Chr(10)," ")
    s=Replace(s,Chr(26),"")
    Q="'"&Replace(s,"'","''"&"")&"'"
End Function

Function QM(v)
    If IsNull(v) Or IsEmpty(v) Then QM="NULL":Exit Function
    Dim s:s=CStr(v):If Len(s)>60000 Then s=Left(s,60000)
    QM="'"&Replace(s,"'","''"&"")&"'"
End Function

Function N(v)
    If IsNull(v) Or IsEmpty(v) Then N="0":Exit Function
    On Error Resume Next
    Dim d:d=CDbl(v)
    If Err.Number<>0 Then N="0":Err.Clear:Exit Function
    On Error GoTo 0
    N=Replace(CStr(d),",",".")
End Function

Function D(v)
    If IsNull(v) Or IsEmpty(v) Then D="NULL":Exit Function
    On Error Resume Next
    Dim dt:dt=CDate(v)
    If Err.Number<>0 Then D="NULL":Err.Clear:Exit Function
    On Error GoTo 0
    Dim y:y=Year(dt):If y<1900 Or y>2100 Then D="NULL":Exit Function
    D="'"&y&"-"&Right("0"&Month(dt),2)&"-"&Right("0"&Day(dt),2)&"'"
End Function

Sub RunSQLFile(sqlFile)
    If Not fso.FileExists(sqlFile) Then Exit Sub
    ' Tulis .bat sementara — paling reliable untuk redirect di Windows
    Dim Q1:Q1=Chr(34)
    Dim batFile:batFile=TMP_DIR&"sync_run_"&Int(Timer()*1000)&".bat"
    Dim bts:Set bts=fso.CreateTextFile(batFile,True)
    bts.WriteLine "@echo off"
    bts.WriteLine Q1&MYSQL_EXE&Q1&" -h "&DB_HOST&" -u "&DB_USER& _
        " -p"&DB_PASS&" "&DB_NAME& _
        " --default-character-set=utf8mb4 < "&Q1&sqlFile&Q1
    bts.Close
    Dim ret:ret=oShell.Run(Q1&batFile&Q1,0,True)
    If ret<>0 Then WScript.Echo "  WARN: mysql exit "&ret
    On Error Resume Next
    fso.DeleteFile batFile
    fso.DeleteFile sqlFile
    On Error GoTo 0
End Sub

Sub RunSQL(sqlStr)
    Dim f:f=TMP_DIR&"g_inline_"&Int(Timer()*1000)&".sql"
    Dim ts:Set ts=fso.CreateTextFile(f,True)
    ts.WriteLine sqlStr:ts.Close
    RunSQLFile f
End Sub

Sub LogSync(dsKey, accTbl, myTbl, cnt)
    RunSQL "INSERT INTO sync_gabung_log " & _
        "(dataset_key,source_table,target_table,started_at,finished_at,status,total_rows,success_rows) " & _
        "VALUES ('"&dsKey&"','"&accTbl&"','"&myTbl&"',NOW(),NOW(),'success',"&cnt&","&cnt&");"
End Sub
