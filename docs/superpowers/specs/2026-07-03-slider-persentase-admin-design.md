# Slider Persentase Staff Servis + Auto-fill Admin Login

**Tanggal:** 2026-07-03
**Status:** Disetujui

## Latar Belakang

Halaman input servis (reguler, jemput, garansi) pakai template bersama
`app/_template/panel-kiri-kasir.php` untuk assignment staff (Kepala
Mekanik, Admin/Kasir, Mekanik) dan persentase komisi masing-masing.
Input persentase saat ini textbox `<input type="number">`, kurang nyaman
dipakai dan auto-split cuma jalan saat pilih dropdown (event `change`),
bukan saat user edit angka manual.

## Tujuan

1. Admin 1 otomatis terisi nama user yang sedang login saat servis baru
   dibuat (masih bisa diganti manual).
2. Input persentase (8 field: KM1, KM2, Admin1, Admin2, MK1-MK4) diganti
   jadi slider + textbox yang sync dua arah.
3. Saat 1 slider dalam grup digeser/diedit, sisa persen otomatis dibagi
   rata ke slider aktif lain dalam grup yang sama supaya total selalu
   100%.

## Scope

File yang diubah: **hanya** `app/_template/panel-kiri-kasir.php`.
Karena file ini di-include oleh `servis-input-reguler.php`,
`servis-input-reguler-jemput.php`, dan `servis-garansi.php`, perubahan
otomatis berlaku ke ketiga halaman tanpa duplikasi.

Tidak ada perubahan skema DB, tidak ada endpoint AJAX baru — murni
markup + JS client-side di atas struktur data yang sudah ada
(`persen_admin1`, `persen_admin2`, `persen_kepala1/2`,
`persen_mekanik1..4`).

## Desain

### 1. Auto-fill Admin 1 dari user login

Session `$_SESSION['_nama_lengkap']` (diset di `config/auth_session.php`
saat login) dipakai sebagai kandidat default Admin 1.

Logic PHP di awal file (dekat blok "Staff Options"):
- Kalau `$admin1` belum di-set (belum ada value existing — servis baru)
  DAN nama user login ada di `$opsi_admin_service`, jadikan default
  `$admin1 = $_SESSION['_nama_lengkap']`.
- Kalau servis sedang di-edit (`$admin1` sudah ada value dari DB),
  jangan override — pakai data existing.
- User tetap bebas ganti dropdown manual kapan saja.
- Setelah render, JS trigger `autoDistributePersenGroup('admin')` sekali
  di `$(document).ready` supaya persen ikut ke-set (100/0) kalau admin1
  ke-auto-fill dan admin2 kosong.

### 2. Markup slider

Tiap pasangan select+persen (8 field) diubah dari:
```html
<input type="number" name="txtpersen_admin1" id="txtpersen_admin1_v2" ...>
```
jadi:
```html
<div class="ks-persen-row">
  <input type="range" class="ks-persen-slider" id="txtpersen_admin1_v2_slider"
         min="0" max="100" step="1" value="<?= $persen_admin1 ?? 0 ?>">
  <input type="number" class="ks-persen-text" name="txtpersen_admin1"
         id="txtpersen_admin1_v2" value="<?= $persen_admin1 ?? 0 ?>" min="0" max="100">
  <span class="ks-persen-label">%</span>
</div>
```
`id` textbox lama (`txtpersen_*_v2`) **dipertahankan** — semua kode JS
lain (`saveMechanicDataV2`, `validateMechanicPersen`, ajax payload) baca
by id itu, jadi gak perlu ubah apapun di luar file ini.

CSS ringan ditambah di `<style>` blok existing / inline: warna track
sama dengan aksen `ks-btn-mini primary`, `transition: value 150ms` via
JS-driven class toggle saat auto-komplemen (bukan saat drag manual, biar
drag tetap 1:1 responsif).

### 3. Sinkron dua arah + auto-komplemen

Ganti `autoDistributePersenGroup` (dipertahankan untuk event `change`
dropdown) dengan tambahan fungsi generik:

```js
function wireSliderGroup(group, ids) {
  // ids: array of {slider, text} pairs untuk grup ini
  ids.forEach(function(pair, idx){
    var $s = $('#'+pair.slider), $t = $('#'+pair.text);
    $s.on('input', function(){ $t.val(this.value); redistribute(group, ids, idx); });
    $t.on('input', function(){
      var v = clamp(parseFloat(this.value)||0, 0, 100);
      $s.val(v); redistribute(group, ids, idx);
    });
  });
}

function redistribute(group, ids, changedIdx) {
  var active = activeIndexes(group); // slot dgn dropdown terisi
  if (active.indexOf(changedIdx) === -1) return; // slot nonaktif diabaikan
  var changedVal = clamp(parseFloat($('#'+ids[changedIdx].text).val())||0, 0, 100);
  var others = active.filter(function(i){ return i!==changedIdx; });
  if (!others.length) return; // cuma 1 aktif, gak ada yg dibagi
  var remain = 100 - changedVal;
  var per = Math.floor(remain/others.length), rem = remain - per*others.length;
  others.forEach(function(i, k){
    var v = per + (k===0 ? rem : 0);
    $('#'+ids[i].slider).val(v); $('#'+ids[i].text).val(v);
  });
}
```

Dipanggil untuk 3 grup: `km` (2 slot), `admin` (2 slot), `mekanik` (4
slot). Slot yang dropdown-nya kosong: slider `disabled`, value dipaksa 0,
dan tidak ikut dihitung di `redistribute`.

`autoDistributePersenGroup` (dropdown `onchange`) tetap dipakai apa
adanya untuk split rata saat pilih/hapus staff — sudah benar dan
konsisten dengan aturan komplemen 100%.

### 4. Validasi

`validateMechanicPersen()` (submit-time) **tidak diubah** — tetap jadi
pengaman terakhir. Realtime auto-komplemen membuat kondisi total ≠ 100%
jadi jarang terjadi, tapi validasi dipertahankan untuk edge case (mis.
field di-disable manual lewat devtools, atau race condition input cepat).

### 5. Kompatibilitas

- Semua `name`/`id` textbox persen dipertahankan → tidak ada perubahan
  di `servis-input-reguler.php`, `servis-input-reguler-jemput.php`,
  `servis-garansi.php`, maupun endpoint `_ajax/save_mechanic_data.php`.
- Slider pakai native `<input type="range">` → jalan di desktop (mouse)
  dan tablet/touchscreen tanpa library tambahan.

## Test Plan

- Buka `servis-input-reguler.php` (servis baru): Admin 1 ke-auto-fill
  nama user login, persen Admin1/Admin2 auto 100/0.
- Geser slider Admin1 ke 70 → textbox Admin1 jadi 70, Admin2 auto jadi 30.
- Edit textbox Admin2 jadi 40 → slider Admin2 ikut gerak, Admin1 auto
  jadi 60.
- Grup Mekanik 3 aktif (MK1-MK3): geser MK1 ke 50 → MK2 & MK3 masing2
  jadi 25.
- Submit dengan total persen sengaja timpang (lewat devtools) →
  `validateMechanicPersen` tetap block dengan alert.
- Test di halaman jemput & garansi (template sama) — behavior identik.
- Test tablet/touchscreen (drag pakai touch) — slider responsif.
