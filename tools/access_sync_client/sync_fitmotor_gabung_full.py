#!/usr/bin/env python
from __future__ import annotations

import argparse
import datetime as dt
import hashlib
import json
import logging
import os
import platform
import sqlite3
import sys
import time
from dataclasses import dataclass
from decimal import Decimal
from pathlib import Path
from typing import Any, Dict, Iterable, Iterator, List, Optional

import pyodbc
import requests


BASE_DIR = Path(__file__).resolve().parent
DEFAULT_ENV_PATH = BASE_DIR / "sync_settings.env"
DEFAULT_LOG_DIR = BASE_DIR / "logs"
DEFAULT_RUNTIME_DIR = BASE_DIR / "runtime"
DEFAULT_STATE_DB = DEFAULT_RUNTIME_DIR / "sync_state.sqlite3"
DEFAULT_LOCK_FILE = DEFAULT_RUNTIME_DIR / "sync.lock"
DEFAULT_API_BASE_URL = "http://localhost/web-bengkel/aplikasi/aplikasi/api/sync"
DEFAULT_TOKEN = "FITMOTOR_SYNC_CHANGE_THIS_TOKEN_2026"
DEFAULT_MDB_PATH = r"E:\BENGKEL 2.0\FITMOTOR GABUNG.mdb"


@dataclass
class DatasetConfig:
    key: str
    label: str
    endpoint: str
    sql: str
    row_key: List[str]
    source_file: str
    source_cabang_field: Optional[str] = None
    columns_by_index: Optional[List[Optional[str]]] = None
    max_lengths: Optional[Dict[str, int]] = None


DATASETS: List[DatasetConfig] = [
    DatasetConfig(
        key="cabang",
        label="Master Cabang",
        endpoint="master-data/cabang.php",
        sql="""
            SELECT [KODE] AS kode_cabang, [CABANG] AS nama_cabang
            FROM [TBLCABANG]
        """,
        row_key=["kode_cabang"],
        source_file="TBLCABANG",
    ),
    DatasetConfig(
        key="pelanggan",
        label="Master Pelanggan",
        endpoint="master-data/customers.php",
        sql="""
            SELECT
                [NoPelanggan] AS no_pelanggan,
                [NamaPelanggan] AS nama_pelanggan,
                [NO_HP] AS telephone,
                [CABANG] AS kd_cabang,
                [MEMBER] AS kgrup
            FROM [GABUNG_PELANGGAN]
        """,
        row_key=["no_pelanggan"],
        source_file="GABUNG_PELANGGAN",
        source_cabang_field="kd_cabang",
        max_lengths={
            "no_pelanggan": 20,
            "nama_pelanggan": 50,
            "telephone": 20,
            "alamat": 100,
            "kota": 20,
            "propinsi": 20,
            "kode_post": 10,
            "negara": 20,
            "fax": 20,
            "kontak_person": 30,
            "note": 100,
            "kgrup": 3,
        },
    ),
    DatasetConfig(
        key="kendaraan_pelanggan",
        label="Pelanggan & Kendaraan",
        endpoint="master-data/vehicles.php",
        sql="""
            SELECT
                [NoPolisi] AS no_polisi,
                [NamaPelanggan] AS nama_pelanggan,
                [Merek],
                [Tipe],
                [Jenis],
                [Warna],
                [TahunBuat] AS tahun_buat,
                [Telephone],
                [Grup],
                [Alamat],
                [Note],
                [KATEGORI],
                [STS] AS kd_cabang,
                [Kota],
                [KontakPerson] AS kontak_person,
                [tanggal_data]
            FROM [KENDARAAN_PELANGGAN_GABUNG_DATA]
        """,
        row_key=["no_polisi"],
        source_file="KENDARAAN_PELANGGAN_GABUNG_DATA",
        source_cabang_field="kd_cabang",
        max_lengths={
            "no_polisi": 20,
            "nama_pelanggan": 50,
            "Merek": 50,
            "Tipe": 50,
            "Jenis": 50,
            "Warna": 50,
            "tahun_buat": 10,
            "Telephone": 20,
            "Grup": 10,
            "Alamat": 50,
            "Note": 100,
            "Kota": 20,
            "kontak_person": 30,
        },
    ),
    DatasetConfig(
        key="item",
        label="Item Per Bengkel",
        endpoint="master-data/items-master.php",
        sql="""
            SELECT
                [NoItem] AS no_item,
                [KodeBarCode] AS kode_barcode,
                [NamaItem] AS nama_item,
                [Jenis],
                [Satuan],
                [HargaPokok] AS harga_pokok,
                [HargaJual] AS harga_jual,
                [HargaJual2] AS harga_jual2,
                [HargaJual3] AS harga_jual3,
                [Quantity],
                [StokMin] AS stok_min,
                [StatusItem] AS status_item,
                [Supplier] AS supplier1,
                [Supplier2],
                [Supplier3],
                [StatusProduk] AS status_produk,
                [RakBarang] AS rak_barang,
                [JasaWaktu] AS jasa_waktu,
                [STS] AS kd_cabang,
                [tanggal_data]
            FROM [GABUNG_TBLITEM_PERBENGKEL_DATA]
        """,
        row_key=["no_item", "kd_cabang"],
        source_file="GABUNG_TBLITEM_PERBENGKEL_DATA",
        source_cabang_field="kd_cabang",
    ),
    DatasetConfig(
        key="supplier",
        label="Supplier",
        endpoint="master-data/suppliers.php",
        sql="""
            SELECT
                [NoSupplier] AS no_supplier,
                [NamaSupplier] AS nama_supplier,
                [Alamat],
                [Kota],
                [Propinsi],
                [KodePost] AS kode_post,
                [Negara],
                [Telephone],
                [Fax],
                [NamaBank] AS nama_bank,
                [NoAccount] AS no_account,
                [AtasNama] AS atas_nama,
                [KontakPerson] AS kontak_person,
                [Email],
                [Note],
                [SaldoAwal] AS saldo_awal,
                [PerTanggal] AS per_tanggal,
                [JmlBayar] AS jml_bayar,
                [Sisa],
                [STS] AS kd_cabang,
                [tanggal_data]
            FROM [GABUNG_TBLSUPPLIER_DATA]
        """,
        row_key=["no_supplier"],
        source_file="GABUNG_TBLSUPPLIER_DATA",
        source_cabang_field="kd_cabang",
        max_lengths={
            "no_supplier": 20,
            "nama_supplier": 100,
            "Alamat": 100,
            "Kota": 20,
            "Propinsi": 20,
            "kode_post": 20,
            "Negara": 20,
            "Telephone": 20,
            "Fax": 20,
            "nama_bank": 30,
            "no_account": 30,
            "atas_nama": 50,
            "kontak_person": 50,
            "Email": 50,
            "Note": 100,
            "kd_cabang": 10,
        },
    ),
    DatasetConfig(
        key="mekanik",
        label="Mekanik",
        endpoint="master-data/mechanics.php",
        sql="""
            SELECT
                [NOMEKANIK] AS no_mekanik,
                [NAMA],
                [STS] AS kd_cabang,
                [tanggal_data]
            FROM [GABUNG_TBLMEKANIK_DATA]
        """,
        row_key=["no_mekanik", "kd_cabang"],
        source_file="GABUNG_TBLMEKANIK_DATA",
        source_cabang_field="kd_cabang",
        max_lengths={
            "no_mekanik": 20,
            "NAMA": 100,
            "kd_cabang": 20,
        },
    ),
    DatasetConfig(
        key="member_pelanggan",
        label="Member Pelanggan",
        endpoint="master-data/customer-members.php",
        sql="""
            SELECT
                [NoPelanggan] AS no_pelanggan,
                [KGrup] AS tipe_member
            FROM [UPDATE_TIPE_MEMBER]
        """,
        row_key=["no_pelanggan"],
        source_file="UPDATE_TIPE_MEMBER",
    ),
    DatasetConfig(
        key="pembelian",
        label="Pembelian",
        endpoint="transactions/pembelian.php",
        sql="""
            SELECT
                [NoTransaksi] AS no_transaksi,
                [Status],
                [CaraBayar] AS cara_bayar,
                [Tanggal],
                [NoOrder] AS no_order,
                [TanggalOrder] AS tanggal_order,
                [NoSupplier] AS no_supplier,
                [Note],
                [TotalQtyOrder] AS total_qty_order,
                [TotalQty] AS total_qty,
                [TotalBeli] AS total_beli,
                [Diskon],
                [TotalDiskon] AS total_diskon,
                [Pajak],
                [TotalPajak] AS total_pajak,
                [TotalAkhir] AS total_akhir,
                [TotalRetur] AS total_retur,
                [Pembayaran],
                [TanggalJT] AS tanggal_jt,
                [TanggalLunas] AS tanggal_lunas,
                [JumlahBayar] AS jumlah_bayar,
                [User] AS user_input,
                [IDTabel] AS id_tabel,
                [NoBaris] AS no_baris,
                [NoItem] AS no_item,
                [QtyOrder] AS qty_order,
                [Quantity],
                [QtyRetur] AS qty_retur,
                [HargaPokok] AS harga_pokok,
                [Potongan],
                [HargaSP] AS harga_sp,
                [Total],
                [StsOrder] AS sts_order,
                [IDInv] AS id_inv,
                [STS] AS kd_cabang,
                [tanggal_data]
            FROM [GABUNG_PEMBELIAN_DATA]
        """,
        row_key=["no_transaksi", "no_baris", "kd_cabang"],
        source_file="GABUNG_PEMBELIAN_DATA",
        source_cabang_field="kd_cabang",
    ),
    DatasetConfig(
        key="penjualan",
        label="Penjualan",
        endpoint="transactions/penjualan.php",
        sql="""
            SELECT * FROM [GABUNG_PENJUALAN]
        """,
        row_key=["no_transaksi", "no_baris", "kd_cabang"],
        source_file="GABUNG_PENJUALAN",
        source_cabang_field="kd_cabang",
        columns_by_index=[
            "no_transaksi",
            "Status",
            "cara_bayar",
            "Tanggal",
            "no_order",
            "tanggal_order",
            "no_sales",
            "no_pelanggan",
            "Note",
            "total_qty",
            "total_qty_order",
            "total_jual",
            "Diskon",
            "total_diskon",
            "Pajak",
            "total_pajak",
            "total_akhir",
            "total_retur",
            "Pembayaran",
            "tanggal_jt",
            "tanggal_lunas",
            "jumlah_bayar",
            "user_input",
            "id_tabel",
            None,
            "no_baris",
            "no_item",
            "qty_order",
            "Quantity",
            "qty_retur",
            "harga_jual",
            "Potongan",
            "harga_sp",
            "harga_pokok",
            "Total",
            "sts_order",
            "kd_cabang",
        ],
    ),
    DatasetConfig(
        key="service",
        label="Service",
        endpoint="transactions/service.php",
        sql="""
            SELECT * FROM [GABUNG_SERVICE_HEADER_DATA]
        """,
        row_key=["no_service", "kd_cabang"],
        source_file="GABUNG_SERVICE_HEADER_DATA",
        source_cabang_field="kd_cabang",
        columns_by_index=[
            "no_service",
            "Tanggal",
            "no_pelanggan",
            "no_polisi",
            "Mekanik1",
            "Mekanik2",
            "Mekanik3",
            "Mekanik4",
            "BiayaM1",
            "BiayaM2",
            "BiayaM3",
            "BiayaM4",
            "km_sekarang",
            "km_berikut",
            "total_waktu",
            "Status",
            "keterangan",
            "subtotal_jasa",
            "subtotal_item",
            "subtotal",
            "Diskon",
            "total_diskon",
            "Pajak",
            "total_pajak",
            "total_akhir",
            "Pembayaran",
            "user_input",
            "id_tabel",
            "kd_cabang",
            "tanggal_data",
        ],
    ),
    DatasetConfig(
        key="service_barang",
        label="Service Histori Barang",
        endpoint="transactions/service-barang.php",
        sql="""
            SELECT * FROM [GABUNG_SERVICE_ITEM]
        """,
        row_key=["no_service", "no_baris", "no_item", "kd_cabang"],
        source_file="GABUNG_SERVICE_ITEM",
        source_cabang_field="kd_cabang",
        columns_by_index=[
            "no_service",
            "Tanggal",
            "no_pelanggan",
            "no_polisi",
            "Mekanik1",
            "Mekanik2",
            "Mekanik3",
            "Mekanik4",
            "BiayaM1",
            "BiayaM2",
            "BiayaM3",
            "BiayaM4",
            "km_sekarang",
            "km_berikut",
            "total_waktu",
            "Status",
            "keterangan",
            "subtotal_jasa",
            "subtotal_item",
            "subtotal",
            "Diskon",
            "total_diskon",
            "Pajak",
            "total_pajak",
            "total_akhir",
            "Pembayaran",
            "user_input",
            "id_tabel",
            None,
            "no_baris",
            "no_item",
            "Quantity",
            "qty_retur",
            "harga",
            "Potongan",
            "Total",
            "kd_cabang",
        ],
    ),
    DatasetConfig(
        key="service_jasa",
        label="Service Histori Jasa",
        endpoint="transactions/service-jasa.php",
        sql="""
            SELECT * FROM [GABUNG_SERVICE_JASA]
        """,
        row_key=["no_service", "no_baris", "no_item", "kd_cabang"],
        source_file="GABUNG_SERVICE_JASA",
        source_cabang_field="kd_cabang",
        columns_by_index=[
            "no_service",
            "Tanggal",
            "no_pelanggan",
            "no_polisi",
            "Mekanik1",
            "Mekanik2",
            "Mekanik3",
            "Mekanik4",
            "BiayaM1",
            "BiayaM2",
            "BiayaM3",
            "BiayaM4",
            "km_sekarang",
            "km_berikut",
            "total_waktu",
            "Status",
            "keterangan",
            "subtotal_jasa",
            "subtotal_item",
            "subtotal",
            "Diskon",
            "total_diskon",
            "Pajak",
            "total_pajak",
            "total_akhir",
            "Pembayaran",
            "user_input",
            "id_tabel",
            None,
            "no_baris",
            "no_item",
            "waktu",
            "harga",
            "Potongan",
            "Total",
            "kd_cabang",
        ],
    ),
    DatasetConfig(
        key="service_advisor",
        label="Service Advisor",
        endpoint="transactions/service-advisor.php",
        sql="""
            SELECT * FROM [GABUNG_TBLSERVICE_ADVISOR_DATA]
        """,
        row_key=["no_service", "advisor", "kd_cabang"],
        source_file="GABUNG_TBLSERVICE_ADVISOR_DATA",
        source_cabang_field="kd_cabang",
        columns_by_index=[
            "no_service",
            "advisor",
            "kd_cabang",
            "tanggal_data",
        ],
        max_lengths={
            "no_service": 50,
            "advisor": 20,
            "kd_cabang": 30,
        },
    ),
]


def load_env_file(path: Path) -> Dict[str, str]:
    env: Dict[str, str] = {}
    if not path.is_file():
        return env

    for raw_line in path.read_text(encoding="utf-8").splitlines():
        line = raw_line.strip()
        if not line or line.startswith("#") or "=" not in line:
            continue
        key, value = line.split("=", 1)
        env[key.strip()] = value.strip().strip('"').strip("'")
    return env


def env_or_default(values: Dict[str, str], key: str, default: str) -> str:
    return os.getenv(key) or values.get(key) or default


def normalize_value(value: Any) -> Any:
    if isinstance(value, Decimal):
        return float(value)
    if isinstance(value, dt.datetime):
        return value.strftime("%Y-%m-%d %H:%M:%S")
    if isinstance(value, dt.date):
        return value.strftime("%Y-%m-%d")
    if isinstance(value, str):
        cleaned = value.strip()
        try:
            cleaned = cleaned.encode("latin-1", "ignore").decode("latin-1")
        except Exception:
            pass
        return cleaned
    return value


def normalize_row(row: Dict[str, Any]) -> Dict[str, Any]:
    cleaned: Dict[str, Any] = {}
    for key, value in row.items():
        cleaned[key] = normalize_value(value)
    return cleaned


def build_row_key(dataset: DatasetConfig, row: Dict[str, Any]) -> str:
    parts: List[str] = []
    for field in dataset.row_key:
        parts.append(str(row.get(field, "")).strip())
    return "|".join(parts)


def hash_row(row: Dict[str, Any]) -> str:
    payload = json.dumps(row, ensure_ascii=False, sort_keys=True, separators=(",", ":"))
    return hashlib.sha256(payload.encode("utf-8")).hexdigest()


class SyncStateStore:
    def __init__(self, db_path: Path) -> None:
        self.db_path = db_path
        self.db_path.parent.mkdir(parents=True, exist_ok=True)
        self.conn = sqlite3.connect(str(self.db_path))
        self.conn.execute(
            """
            CREATE TABLE IF NOT EXISTS row_state (
                dataset_key TEXT NOT NULL,
                row_key TEXT NOT NULL,
                row_hash TEXT NOT NULL,
                updated_at TEXT NOT NULL,
                PRIMARY KEY (dataset_key, row_key)
            )
            """
        )
        self.conn.commit()

    def get_hash(self, dataset_key: str, row_key: str) -> Optional[str]:
        cursor = self.conn.execute(
            "SELECT row_hash FROM row_state WHERE dataset_key = ? AND row_key = ?",
            (dataset_key, row_key),
        )
        row = cursor.fetchone()
        return row[0] if row else None

    def set_hash(self, dataset_key: str, row_key: str, row_hash: str) -> None:
        self.conn.execute(
            """
            INSERT INTO row_state (dataset_key, row_key, row_hash, updated_at)
            VALUES (?, ?, ?, ?)
            ON CONFLICT(dataset_key, row_key) DO UPDATE SET
                row_hash = excluded.row_hash,
                updated_at = excluded.updated_at
            """,
            (dataset_key, row_key, row_hash, dt.datetime.now().strftime("%Y-%m-%d %H:%M:%S")),
        )

    def commit(self) -> None:
        self.conn.commit()

    def close(self) -> None:
        self.conn.commit()
        self.conn.close()


class SimpleRunLock:
    def __init__(self, lock_path: Path, stale_after_seconds: int = 60 * 60 * 12) -> None:
        self.lock_path = lock_path
        self.stale_after_seconds = stale_after_seconds
        self.acquired = False

    def acquire(self) -> bool:
        self.lock_path.parent.mkdir(parents=True, exist_ok=True)
        if self.lock_path.exists():
            age = time.time() - self.lock_path.stat().st_mtime
            if age > self.stale_after_seconds:
                self.lock_path.unlink(missing_ok=True)
            else:
                return False

        flags = os.O_CREAT | os.O_EXCL | os.O_WRONLY
        fd = os.open(str(self.lock_path), flags)
        with os.fdopen(fd, "w", encoding="utf-8") as handle:
            handle.write(f"pid={os.getpid()}\n")
            handle.write(f"started_at={dt.datetime.now().strftime('%Y-%m-%d %H:%M:%S')}\n")
        self.acquired = True
        return True

    def release(self) -> None:
        if self.acquired and self.lock_path.exists():
            self.lock_path.unlink(missing_ok=True)
        self.acquired = False


class AccessSyncClient:
    def __init__(self, config: Dict[str, Any], logger: logging.Logger) -> None:
        self.config = config
        self.logger = logger
        self.session = requests.Session()
        self.session.headers.update(
            {
                "Content-Type": "application/json",
                "X-Sync-Token": config["api_token"],
                "X-Machine-Name": config["machine_name"],
            }
        )
        self.state_store = SyncStateStore(Path(config["state_db_path"]))

    def close(self) -> None:
        self.state_store.close()
        self.session.close()

    def access_connection(self) -> pyodbc.Connection:
        conn_str = (
            f"Driver={{Microsoft Access Driver (*.mdb, *.accdb)}};"
            f"DBQ={self.config['mdb_path']};"
        )
        self.logger.info("Opening Access MDB: %s", self.config["mdb_path"])
        return pyodbc.connect(conn_str, timeout=30)

    def api_post(self, endpoint: str, payload: Dict[str, Any]) -> Dict[str, Any]:
        url = self.config["api_base_url"].rstrip("/") + "/" + endpoint.lstrip("/")
        response = self.session.post(url, json=payload, timeout=self.config["http_timeout"])
        try:
            body = response.json()
        except Exception:
            body = {"success": False, "error": response.text}
        if response.status_code >= 400:
            raise RuntimeError(f"HTTP {response.status_code}: {body}")
        return body

    def preflight(self) -> None:
        body = self.api_post(
            "preflight.php",
            {
                "source_name": "FITMOTOR GABUNG Scheduler",
                "machine_name": self.config["machine_name"],
                "trigger_source": "scheduler",
            },
        )
        if not body.get("success"):
            raise RuntimeError(f"Preflight gagal: {body}")
        self.logger.info("Preflight OK: %s", body.get("server_time"))

    def heartbeat(self, status: str, notes: str, extra: Optional[Dict[str, Any]] = None) -> None:
        payload = {
            "source_name": "FITMOTOR GABUNG Full Sync",
            "source_file": "scheduler-heartbeat",
            "dataset_key": "heartbeat",
            "source_cabang": self.config.get("default_source_cabang", ""),
            "trigger_source": "scheduler",
            "machine_name": self.config["machine_name"],
            "sync_mode": self.config["sync_mode"],
            "status": status,
            "notes": notes,
            "total_rows": 0,
            "success_rows": 0,
            "failed_rows": 0,
        }
        if extra:
            payload.update(extra)
        try:
            self.api_post("report-run.php", payload)
        except Exception as exc:
            self.logger.warning("Heartbeat gagal dikirim: %s", exc)

    def iter_access_rows(self, conn: pyodbc.Connection, dataset: DatasetConfig) -> Iterator[Dict[str, Any]]:
        cursor = conn.cursor()
        cursor.execute(dataset.sql)
        columns = [column[0] for column in cursor.description]
        while True:
            batch = cursor.fetchmany(self.config["cursor_fetch_size"])
            if not batch:
                break
            for row in batch:
                if dataset.columns_by_index:
                    mapped: Dict[str, Any] = {}
                    for index, field_name in enumerate(dataset.columns_by_index):
                        if field_name is None:
                            continue
                        if index >= len(row):
                            continue
                        mapped[field_name] = row[index]
                else:
                    mapped = {columns[index]: row[index] for index in range(len(columns))}
                yield self._apply_dataset_limits(dataset, normalize_row(mapped))

    def _apply_dataset_limits(self, dataset: DatasetConfig, row: Dict[str, Any]) -> Dict[str, Any]:
        if not dataset.max_lengths:
            return row

        limited = dict(row)
        for field_name, max_length in dataset.max_lengths.items():
            if field_name not in limited or limited[field_name] is None:
                continue
            if isinstance(limited[field_name], str) and len(limited[field_name]) > max_length:
                limited[field_name] = limited[field_name][:max_length].strip()
        return limited

    def sync_dataset(self, conn: pyodbc.Connection, dataset: DatasetConfig, sample_limit: int = 0) -> Dict[str, Any]:
        self.logger.info("Sync dataset mulai: %s", dataset.label)
        changed_rows: List[Dict[str, Any]] = []
        state_updates: List[tuple[str, str]] = []
        scanned = 0
        skipped = 0
        batches = 0
        sent_rows = 0

        for row in self.iter_access_rows(conn, dataset):
            scanned += 1
            row_key = build_row_key(dataset, row)
            if not row_key.strip("|"):
                skipped += 1
                continue

            row_hash = hash_row(row)
            if self.config["sync_mode"] != "full":
                previous_hash = self.state_store.get_hash(dataset.key, row_key)
                if previous_hash == row_hash:
                    skipped += 1
                    if sample_limit and scanned >= sample_limit:
                        break
                    continue

            changed_rows.append(row)
            state_updates.append((row_key, row_hash))

            if sample_limit and scanned >= sample_limit:
                break

            if len(changed_rows) >= self.config["batch_size"]:
                sent_rows += self._flush_dataset_batch(dataset, changed_rows)
                self._apply_state_updates(dataset, state_updates)
                changed_rows = []
                state_updates = []
                batches += 1

        if changed_rows:
            sent_rows += self._flush_dataset_batch(dataset, changed_rows)
            self._apply_state_updates(dataset, state_updates)
            batches += 1

        self.state_store.commit()
        summary = {
            "dataset": dataset.key,
            "scanned": scanned,
            "sent": sent_rows,
            "skipped": skipped,
            "batches": batches,
        }
        self.logger.info(
            "Sync dataset selesai: %s | scanned=%s sent=%s skipped=%s batches=%s",
            dataset.label,
            scanned,
            sent_rows,
            skipped,
            batches,
        )
        return summary

    def _apply_state_updates(self, dataset: DatasetConfig, updates: List[tuple[str, str]]) -> None:
        for row_key, row_hash in updates:
            self.state_store.set_hash(dataset.key, row_key, row_hash)

    def _flush_dataset_batch(self, dataset: DatasetConfig, rows: List[Dict[str, Any]]) -> int:
        if not rows:
            return 0

        first_source_cabang = ""
        if dataset.source_cabang_field:
            for row in rows:
                value = str(row.get(dataset.source_cabang_field, "")).strip()
                if value:
                    first_source_cabang = value
                    break

        payload = {
            "rows": rows,
            "sync_mode": self.config["sync_mode"],
            "mode": "append",
            "source_name": dataset.label,
            "source_file": dataset.source_file,
            "source_cabang": first_source_cabang or self.config.get("default_source_cabang", ""),
            "trigger_source": "scheduler",
            "machine_name": self.config["machine_name"],
            "batch_key": f"{dataset.key}-{int(time.time())}",
            "auto_merge": True,
        }
        response = self.api_post(dataset.endpoint, payload)
        if not response.get("success"):
            raise RuntimeError(f"Sync {dataset.key} gagal: {response}")
        self.logger.info(
            "Batch terkirim: %s | rows=%s | run_id=%s",
            dataset.key,
            len(rows),
            response.get("run_id"),
        )
        return len(rows)


def build_logger(log_dir: Path) -> logging.Logger:
    log_dir.mkdir(parents=True, exist_ok=True)
    logger = logging.getLogger("fitmotor_gabung_sync")
    logger.setLevel(logging.INFO)
    logger.handlers.clear()

    log_file = log_dir / f"sync_{dt.datetime.now().strftime('%Y%m%d')}.log"
    formatter = logging.Formatter("%(asctime)s [%(levelname)s] %(message)s")

    file_handler = logging.FileHandler(log_file, encoding="utf-8")
    file_handler.setFormatter(formatter)
    logger.addHandler(file_handler)

    stream_handler = logging.StreamHandler(sys.stdout)
    stream_handler.setFormatter(formatter)
    logger.addHandler(stream_handler)

    return logger


def parse_args() -> argparse.Namespace:
    parser = argparse.ArgumentParser(description="Full sync FITMOTOR GABUNG.mdb ke API web-bengkel")
    parser.add_argument("--env", default=str(DEFAULT_ENV_PATH), help="Path file env sync")
    parser.add_argument("--dataset", action="append", default=[], help="Filter dataset tertentu")
    parser.add_argument("--sample-limit", type=int, default=0, help="Batasi jumlah row yang discan per dataset untuk testing")
    parser.add_argument("--mode", choices=["incremental", "full"], default="", help="Override mode sync")
    parser.add_argument("--heartbeat-only", action="store_true", help="Kirim heartbeat saja tanpa proses dataset")
    return parser.parse_args()


def load_runtime_config(args: argparse.Namespace) -> Dict[str, Any]:
    env_values = load_env_file(Path(args.env))
    machine_name = platform.node() or os.getenv("COMPUTERNAME") or "UNKNOWN-MACHINE"
    log_dir = Path(env_or_default(env_values, "SYNC_LOG_DIR", str(DEFAULT_LOG_DIR)))
    runtime_dir = Path(env_or_default(env_values, "SYNC_RUNTIME_DIR", str(DEFAULT_RUNTIME_DIR)))
    runtime_dir.mkdir(parents=True, exist_ok=True)

    config: Dict[str, Any] = {
        "api_base_url": env_or_default(env_values, "SYNC_API_BASE_URL", DEFAULT_API_BASE_URL),
        "api_token": env_or_default(env_values, "SYNC_API_TOKEN", DEFAULT_TOKEN),
        "mdb_path": env_or_default(env_values, "SYNC_MDB_PATH", DEFAULT_MDB_PATH),
        "sync_mode": args.mode or env_or_default(env_values, "SYNC_MODE", "incremental"),
        "batch_size": int(env_or_default(env_values, "SYNC_BATCH_SIZE", "200")),
        "cursor_fetch_size": int(env_or_default(env_values, "SYNC_CURSOR_FETCH_SIZE", "200")),
        "http_timeout": int(env_or_default(env_values, "SYNC_HTTP_TIMEOUT", "120")),
        "state_db_path": env_or_default(env_values, "SYNC_STATE_DB_PATH", str(DEFAULT_STATE_DB)),
        "lock_file_path": env_or_default(env_values, "SYNC_LOCK_FILE_PATH", str(DEFAULT_LOCK_FILE)),
        "log_dir": str(log_dir),
        "machine_name": env_or_default(env_values, "SYNC_MACHINE_NAME", machine_name),
        "default_source_cabang": env_or_default(env_values, "SYNC_DEFAULT_SOURCE_CABANG", ""),
    }
    return config


def select_datasets(filters: List[str]) -> List[DatasetConfig]:
    if not filters:
        return DATASETS

    wanted = {item.strip().lower() for item in filters if item.strip()}
    selected = [dataset for dataset in DATASETS if dataset.key.lower() in wanted]
    if not selected:
        raise SystemExit(f"Dataset tidak ditemukan: {', '.join(filters)}")
    return selected


def main() -> int:
    args = parse_args()
    config = load_runtime_config(args)
    logger = build_logger(Path(config["log_dir"]))
    client = AccessSyncClient(config, logger)
    lock = SimpleRunLock(Path(config["lock_file_path"]))

    try:
        if not lock.acquire():
            logger.warning("Run dilewati karena proses sync lain masih berjalan.")
            return 0
        logger.info("Starting FITMOTOR GABUNG full sync | mode=%s | machine=%s", config["sync_mode"], config["machine_name"])
        client.preflight()

        if args.heartbeat_only:
            client.heartbeat("success", "Heartbeat only run")
            logger.info("Heartbeat only selesai")
            return 0

        connection = client.access_connection()
        try:
            dataset_list = select_datasets(args.dataset)
            summaries: List[Dict[str, Any]] = []
            total_sent = 0
            total_scanned = 0
            for dataset in dataset_list:
                summary = client.sync_dataset(connection, dataset, sample_limit=args.sample_limit)
                summaries.append(summary)
                total_sent += summary["sent"]
                total_scanned += summary["scanned"]
        finally:
            connection.close()

        notes = " ; ".join(
            f"{row['dataset']}:scan={row['scanned']},sent={row['sent']},skip={row['skipped']}"
            for row in summaries
        )
        client.heartbeat(
            "success",
            notes,
            extra={
                "total_rows": total_scanned,
                "success_rows": total_sent,
                "failed_rows": 0,
            },
        )
        logger.info("Semua dataset selesai | total_scanned=%s | total_sent=%s", total_scanned, total_sent)
        return 0
    except Exception as exc:
        logger.exception("Sync gagal: %s", exc)
        client.heartbeat("failed", f"Sync gagal: {exc}", extra={"failed_rows": 1})
        return 1
    finally:
        lock.release()
        client.close()


if __name__ == "__main__":
    raise SystemExit(main())
