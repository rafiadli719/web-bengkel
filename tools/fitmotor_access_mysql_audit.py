from __future__ import annotations

import argparse
import json
import re
import subprocess
from dataclasses import dataclass, asdict
from datetime import datetime
from pathlib import Path
from typing import Any, Iterable

import win32com.client  # type: ignore


ACCESS_MDB_DEFAULT = r"E:\BENGKEL 2.0\FITMOTOR GABUNG.MDB"
MYSQL_EXE_DEFAULT = r"C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin\mysql.exe"
MYSQL_HOST_DEFAULT = "localhost"
MYSQL_USER_DEFAULT = "fitmotor_LOGIN"
MYSQL_PASS_DEFAULT = "Sayalupa12"
MYSQL_DB_DEFAULT = "fitmotor_gabung"


def ensure_dir(path: Path) -> None:
    path.mkdir(parents=True, exist_ok=True)


def sql_ident(name: str) -> str:
    return "`" + name.replace("`", "``") + "`"


def md_escape(value: Any) -> str:
    if value is None:
        return ""
    return str(value).replace("|", r"\|").replace("\r\n", "<br>").replace("\n", "<br>")


def access_type_name(dao_type: int) -> str:
    return {
        1: "Boolean",
        2: "Byte",
        3: "Integer",
        4: "Long",
        5: "Currency",
        6: "Single",
        7: "Double",
        8: "DateTime",
        9: "Binary",
        10: "Text",
        11: "LongBinary",
        12: "Memo",
        15: "GUID",
        16: "BigInt",
        17: "VarBinary",
        18: "Char",
        19: "Numeric",
        20: "Decimal",
        21: "Float",
        22: "Time",
        23: "TimeStamp",
        101: "Attachment",
        102: "ComplexByte",
        103: "ComplexInteger",
        104: "ComplexLong",
        105: "ComplexSingle",
        106: "ComplexDouble",
        107: "ComplexGUID",
        108: "ComplexDecimal",
        109: "ComplexText",
    }.get(dao_type, f"DAOType{dao_type}")


def access_to_mysql_type(field: Any) -> str:
    dao_type = int(field.Type)
    size = 0
    try:
        size = int(field.Size)
    except Exception:
        pass

    if dao_type == 1:
        return "TINYINT(1)"
    if dao_type == 2:
        return "TINYINT UNSIGNED"
    if dao_type == 3:
        return "SMALLINT"
    if dao_type == 4:
        return "INT"
    if dao_type == 5:
        return "DECIMAL(19,4)"
    if dao_type == 6:
        return "FLOAT"
    if dao_type == 7:
        return "DOUBLE"
    if dao_type == 8:
        return "DATETIME"
    if dao_type == 10:
        if size <= 0:
            size = 255
        if size > 16383:
            return "TEXT"
        return f"VARCHAR({size})"
    if dao_type == 11:
        return "LONGBLOB"
    if dao_type == 12:
        return "LONGTEXT"
    if dao_type == 15:
        return "CHAR(36)"
    if dao_type == 16:
        return "BIGINT"
    if dao_type == 17:
        return "VARBINARY(255)"
    if dao_type == 18:
        return "CHAR(1)"
    if dao_type in (19, 20):
        return "DECIMAL(19,4)"
    if dao_type == 21:
        return "DOUBLE"
    if dao_type == 22:
        return "TIME"
    if dao_type == 23:
        return "DATETIME"
    return "TEXT"


def normalize_name(name: str) -> str:
    n = name.lower()
    n = re.sub(r"[^a-z0-9]", "", n)
    n = re.sub(r"^kd", "kode", n)
    n = re.sub(r"tgl", "tanggal", n)
    n = re.sub(r"^nm", "nama", n)
    n = re.sub(r"no", "nomor", n)
    return n


def levenshtein(a: str, b: str) -> int:
    if a == b:
        return 0
    if not a:
        return len(b)
    if not b:
        return len(a)
    prev = list(range(len(b) + 1))
    for i, ca in enumerate(a, start=1):
        curr = [i]
        for j, cb in enumerate(b, start=1):
            cost = 0 if ca == cb else 1
            curr.append(min(prev[j] + 1, curr[-1] + 1, prev[j - 1] + cost))
        prev = curr
    return prev[-1]


def run_mysql(mysql_exe: str, host: str, user: str, password: str, db: str, sql: str) -> list[str]:
    args = [
        mysql_exe,
        "-h",
        host,
        "-u",
        user,
        f"-p{password}",
        "--default-character-set=latin1",
        "--batch",
        "--raw",
        "--skip-column-names",
        "-e",
        sql,
        db,
    ]
    proc = subprocess.run(args, capture_output=True, text=True, encoding="utf-8")
    if proc.returncode != 0:
        raise RuntimeError(f"mysql exited with code {proc.returncode} while running: {sql}\n{proc.stderr}")
    lines = [line for line in proc.stdout.splitlines() if line.strip()]
    warning = "Using a password on the command line interface can be insecure."
    return [line for line in lines if warning not in line]


def split_top_level_comma(text: str) -> list[str]:
    parts: list[str] = []
    buf: list[str] = []
    depth = 0
    quote: str | None = None
    for ch in text:
        if quote:
            buf.append(ch)
            if ch == quote:
                quote = None
            continue
        if ch in {"'", '"'}:
            quote = ch
            buf.append(ch)
            continue
        if ch == "(":
            depth += 1
        elif ch == ")" and depth > 0:
            depth -= 1
        elif ch == "," and depth == 0:
            parts.append("".join(buf).strip())
            buf = []
            continue
        buf.append(ch)
    if buf:
        parts.append("".join(buf).strip())
    return parts


def parse_access_select_fields(sql: str) -> list[dict[str, Any]]:
    if not sql.strip():
        return []
    clean = re.sub(r"\r?\n", " ", sql)
    m = re.search(r"(?is)\bSELECT\s+(?P<select>.*?)\s+\bFROM\b", clean)
    if not m:
        m = re.search(r"(?is)\bTRANSFORM\s+(?P<select>.*?)\s+\bSELECT\b", clean)
    if not m:
        return []

    select_list = m.group("select").strip()
    select_list = re.sub(r"(?i)^DISTINCT\s+", "", select_list)
    select_list = re.sub(r"(?i)^DISTINCTROW\s+", "", select_list)

    fields: list[dict[str, Any]] = []
    ordinal = 1
    for part in split_top_level_comma(select_list):
        name: str | None = None
        alias = re.search(r'(?is)\s+AS\s+(\[[^\]]+\]|`[^`]+`|"[^"]+"|[A-Za-z0-9_ ]+)\s*$', part)
        if alias:
            name = alias.group(1).strip()
        else:
            simple = re.search(r'^\s*(\[[^\]]+\]|`[^`]+`|[A-Za-z0-9_]+)\.(\[[^\]]+\]|`[^`]+`|[A-Za-z0-9_]+)\s*$', part)
            if simple:
                name = simple.group(2)
            else:
                naked = re.search(r'^\s*(\[[^\]]+\]|`[^`]+`|[A-Za-z0-9_]+)\s*$', part)
                if naked:
                    name = naked.group(1)
                else:
                    tail = re.search(r'(?is)\s+(\[[^\]]+\]|`[^`]+`|"[^"]+"|[A-Za-z0-9_]+)\s*$', part)
                    if tail and not re.search(r"(?i)\)$", part):
                        name = tail.group(1)

        if name:
            name = name.strip("[]`\"")
            fields.append({"name": name, "ordinal": ordinal, "accessType": "PARSED", "size": None})
            ordinal += 1
    return fields


def convert_access_sql_to_mysql(sql: str) -> str:
    if not sql.strip():
        return ""
    s = sql.strip()
    s = re.sub(r";+\s*$", "", s)
    s = re.sub(r"\[([^\]]+)\]", r"`\1`", s)
    s = re.sub(r"\bNz\s*\(", "IFNULL(", s, flags=re.I)
    s = re.sub(r"\bInt\s*\(", "FLOOR(", s, flags=re.I)
    s = re.sub(r"\bDate\s*\(\s*\)", "CURDATE()", s, flags=re.I)
    s = re.sub(r"\bNow\s*\(\s*\)", "NOW()", s, flags=re.I)
    s = re.sub(r"\bDateValue\s*\(", "DATE(", s, flags=re.I)
    s = re.sub(r"\bYes\b", "1", s, flags=re.I)
    s = re.sub(r"\bNo\b", "0", s, flags=re.I)
    s = re.sub(r"\bTrue\b", "1", s, flags=re.I)
    s = re.sub(r"\bFalse\b", "0", s, flags=re.I)
    s = re.sub(r"\bIIf\s*\(", "IF(", s, flags=re.I)
    s = re.sub(r"\bUCase\s*\(", "UPPER(", s, flags=re.I)
    s = re.sub(r"\bLCase\s*\(", "LOWER(", s, flags=re.I)
    s = re.sub(r"\bLen\s*\(", "CHAR_LENGTH(", s, flags=re.I)
    s = re.sub(r"\bIsNull\s*\(", "ISNULL(", s, flags=re.I)
    s = re.sub(r"\bMAXIMUM\s*\(", "GREATEST(", s, flags=re.I)
    s = re.sub(
        r"(?is)\bFormat\s*\(\s*(?P<expr>[^,]+?)\s*,\s*['\"]#\,##0['\"]\s*\)",
        r"FORMAT(\g<expr>, 0)",
        s,
    )
    s = re.sub(
        r"(?is)\bFormat\s*\(\s*(?P<expr>[^,]+?)\s*,\s*['\"]dd-mmm-yy['\"]\s*\)",
        r"DATE_FORMAT(\g<expr>, '%d-%b-%y')",
        s,
    )
    s = re.sub(
        r"(?is)\bFormat\s*\(\s*(?P<expr>[^,]+?)\s*,\s*['\"]yyyy['\"]\s*\)",
        r"DATE_FORMAT(\g<expr>, '%Y')",
        s,
    )
    s = re.sub(
        r"(?is)\bFormat\s*\(\s*(?P<expr>[^,]+?)\s*,\s*['\"]mm['\"]\s*\)",
        r"DATE_FORMAT(\g<expr>, '%m')",
        s,
    )
    s = re.sub(
        r"(?is)\bFormat\s*\(\s*(?P<expr>[^,]+?)\s*,\s*['\"]MM/DD/YYYY['\"]\s*\)",
        r"DATE_FORMAT(\g<expr>, '%m/%d/%Y')",
        s,
    )
    s = re.sub(
        r'(?is)\bDateAdd\s*\(\s*"d"\s*,\s*(?P<expr>[^,]+?)\s*,\s*(?P<date>[^)]+?)\s*\)',
        r"DATE_ADD(\g<date>, INTERVAL \g<expr> DAY)",
        s,
    )
    s = re.sub(
        r"(?is)\bSafeDivision\s*\(\s*(?P<num>[^,]+?)\s*,\s*(?P<den>[^)]+?)\s*\)",
        r"IFNULL((\g<num>) / NULLIF(\g<den>, 0), 0)",
        s,
    )
    return s


@dataclass
class AccessField:
    name: str
    ordinal: int
    dao_type: int
    access_type: str
    size: int | None
    required: bool | None
    allow_zero_length: bool | None
    default_value: str | None
    mysql_type_suggestion: str


@dataclass
class AccessTable:
    name: str
    is_system: bool
    attributes: int | None
    fields: list[AccessField]
    primary_key: list[str]
    indexes: list[dict[str, Any]]


@dataclass
class AccessQuery:
    name: str
    is_internal: bool
    kind: str
    sql: str
    output_fields: list[dict[str, Any]]
    output_error: str | None


def get_query_output_fields(query_def: Any, resolve_with_dao: bool) -> tuple[list[dict[str, Any]], str | None]:
    if resolve_with_dao:
        try:
            fields = []
            for i in range(query_def.Fields.Count):
                f = query_def.Fields.Item(i)
                size = None
                try:
                    size = int(f.Size)
                except Exception:
                    pass
                fields.append(
                    {
                        "name": str(f.Name),
                        "ordinal": i + 1,
                        "accessType": access_type_name(int(f.Type)),
                        "size": size,
                    }
                )
            return fields, None
        except Exception as exc:
            return [], str(exc)
    fields = parse_access_select_fields(str(query_def.SQL))
    if fields:
        return fields, "Output fields parsed from SELECT list."
    return [], "Output fields parsed/unresolved; DAO resolution disabled."


def extract_tabledef_metadata(table_def: Any) -> tuple[list[AccessField], list[str], list[dict[str, Any]]]:
    fields: list[AccessField] = []
    for j in range(table_def.Fields.Count):
        f = table_def.Fields.Item(j)
        size = None
        required = None
        allow_zero = None
        default_value = None
        try:
            size = int(f.Size)
        except Exception:
            pass
        try:
            required = bool(f.Required)
        except Exception:
            pass
        try:
            allow_zero = bool(f.AllowZeroLength)
        except Exception:
            pass
        try:
            default_value = str(f.DefaultValue)
        except Exception:
            pass
        fields.append(
            AccessField(
                name=str(f.Name),
                ordinal=j + 1,
                dao_type=int(f.Type),
                access_type=access_type_name(int(f.Type)),
                size=size,
                required=required,
                allow_zero_length=allow_zero,
                default_value=default_value,
                mysql_type_suggestion=access_to_mysql_type(f),
            )
        )

    primary_fields: list[str] = []
    indexes: list[dict[str, Any]] = []
    try:
        for ix in range(table_def.Indexes.Count):
            idx = table_def.Indexes.Item(ix)
            idx_fields = [str(idx.Fields.Item(k).Name) for k in range(idx.Fields.Count)]
            indexes.append(
                {
                    "name": str(idx.Name),
                    "primary": bool(idx.Primary),
                    "unique": bool(idx.Unique),
                    "fields": idx_fields,
                }
            )
            if idx.Primary:
                primary_fields = idx_fields
    except Exception:
        pass

    return fields, primary_fields, indexes


def extract_mdb_path(connect: str) -> str | None:
    m = re.search(r"(?i)\bDATABASE=([^;]+)", connect or "")
    if not m:
        return None
    return m.group(1).strip()


def read_access_metadata(mdb_path: str, resolve_with_dao: bool) -> tuple[list[AccessTable], list[AccessQuery], list[dict[str, Any]]]:
    dao = None
    for prog_id in ("DAO.DBEngine.120", "DAO.DBEngine.36"):
        try:
            dao = win32com.client.Dispatch(prog_id)
            break
        except Exception:
            continue
    if dao is None:
        raise RuntimeError("DAO DBEngine is unavailable.")

    db = dao.OpenDatabase(mdb_path)
    tables: list[AccessTable] = []
    linked_db_cache: dict[str, Any] = {}
    for i in range(db.TableDefs.Count):
        t = db.TableDefs.Item(i)
        fields, primary_fields, indexes = extract_tabledef_metadata(t)
        if not fields:
            connect = ""
            source_name = ""
            try:
                connect = str(t.Connect)
            except Exception:
                connect = ""
            try:
                source_name = str(t.SourceTableName)
            except Exception:
                source_name = ""
            linked_path = extract_mdb_path(connect)
            if linked_path:
                try:
                    linked_db = linked_db_cache.get(linked_path)
                    if linked_db is None:
                        linked_db = dao.OpenDatabase(linked_path)
                        linked_db_cache[linked_path] = linked_db
                    lookup_names = [name for name in (source_name, str(t.Name)) if name]
                    linked_table = None
                    for candidate in lookup_names:
                        try:
                            linked_table = linked_db.TableDefs.Item(candidate)
                            break
                        except Exception:
                            continue
                    if linked_table is not None:
                        linked_fields, linked_primary, linked_indexes = extract_tabledef_metadata(linked_table)
                        if linked_fields:
                            fields = linked_fields
                            primary_fields = linked_primary
                            indexes = linked_indexes
                except Exception:
                    pass
        is_system = str(t.Name).startswith("MSys") or str(t.Name).startswith("~")
        attrs = None
        try:
            attrs = int(t.Attributes)
        except Exception:
            pass
        tables.append(
            AccessTable(
                name=str(t.Name),
                is_system=is_system,
                attributes=attrs,
                fields=fields,
                primary_key=primary_fields,
                indexes=indexes,
            )
        )

    relations: list[dict[str, Any]] = []
    try:
        for i in range(db.Relations.Count):
            r = db.Relations.Item(i)
            rel_fields = []
            for j in range(r.Fields.Count):
                rf = r.Fields.Item(j)
                rel_fields.append({"field": str(rf.Name), "foreignField": str(rf.ForeignName)})
            relations.append(
                {
                    "name": str(r.Name),
                    "table": str(r.Table),
                    "foreignTable": str(r.ForeignTable),
                    "fields": rel_fields,
                }
            )
    except Exception:
        pass

    queries: list[AccessQuery] = []
    for i in range(db.QueryDefs.Count):
        q = db.QueryDefs.Item(i)
        sql = str(q.SQL)
        trim = sql.lstrip()
        if re.match(r"(?is)^SELECT\b", trim):
            kind = "SELECT"
        elif re.match(r"(?is)^TRANSFORM\b", trim):
            kind = "CROSSTAB"
        elif re.match(r"(?is)^PARAMETERS\b", trim):
            kind = "PARAMETERS"
        elif re.match(r"(?is)^UNION\b", trim):
            kind = "UNION"
        else:
            kind = "OTHER"
        output_fields, output_error = get_query_output_fields(q, resolve_with_dao)
        queries.append(
            AccessQuery(
                name=str(q.Name),
                is_internal=str(q.Name).startswith("~sq_"),
                kind=kind,
                sql=sql,
                output_fields=output_fields,
                output_error=output_error,
            )
        )

    db.Close()
    return tables, queries, relations


def read_mysql_schema(mysql_exe: str, host: str, user: str, password: str, db_name: str) -> tuple[dict[str, Any], dict[str, list[dict[str, Any]]], dict[str, Any]]:
    tables_rows = run_mysql(
        mysql_exe,
        host,
        user,
        password,
        db_name,
        "SELECT TABLE_NAME, TABLE_TYPE, ENGINE FROM information_schema.TABLES "
        f"WHERE TABLE_SCHEMA = DATABASE() ORDER BY TABLE_NAME",
    )
    columns_rows = run_mysql(
        mysql_exe,
        host,
        user,
        password,
        db_name,
        "SELECT TABLE_NAME, COLUMN_NAME, ORDINAL_POSITION, COLUMN_TYPE, DATA_TYPE, IS_NULLABLE, "
        "COLUMN_DEFAULT, COLUMN_KEY, EXTRA FROM information_schema.COLUMNS "
        "WHERE TABLE_SCHEMA = DATABASE() ORDER BY TABLE_NAME, ORDINAL_POSITION",
    )
    views_rows = run_mysql(
        mysql_exe,
        host,
        user,
        password,
        db_name,
        "SELECT TABLE_NAME, VIEW_DEFINITION FROM information_schema.VIEWS "
        "WHERE TABLE_SCHEMA = DATABASE() ORDER BY TABLE_NAME",
    )

    mysql_tables: dict[str, Any] = {}
    for line in tables_rows:
        table_name, table_type, engine = (line.split("\t") + ["", ""])[:3]
        mysql_tables[table_name] = {"name": table_name, "type": table_type, "engine": engine, "columns": []}
    for line in columns_rows:
        parts = (line.split("\t") + [""] * 9)[:9]
        t_name, c_name, ordinal, ctype, dtype, nullable, default, key, extra = parts
        mysql_tables.setdefault(t_name, {"name": t_name, "type": "UNKNOWN", "engine": None, "columns": []})["columns"].append(
            {
                "name": c_name,
                "ordinal": int(ordinal),
                "columnType": ctype,
                "dataType": dtype,
                "nullable": nullable,
                "default": default,
                "key": key,
                "extra": extra,
            }
        )
    mysql_views: dict[str, Any] = {}
    for line in views_rows:
        parts = line.split("\t", 1)
        view_name = parts[0]
        definition = parts[1] if len(parts) > 1 else ""
        mysql_views[view_name] = {"name": view_name, "definition": definition, "columns": mysql_tables.get(view_name, {}).get("columns", [])}
    return mysql_tables, mysql_views, {"tables_rows": tables_rows, "columns_rows": columns_rows, "views_rows": views_rows}


def find_named_dependencies(sql: str, names: list[str]) -> list[str]:
    deps: list[str] = []
    for name in sorted(names, key=len, reverse=True):
        if re.search(rf"(?i)(^|[^A-Za-z0-9_]){re.escape(name)}([^A-Za-z0-9_]|$)", sql):
            deps.append(name)
    return deps


def build_manual_reasons(query: AccessQuery) -> list[str]:
    reasons: list[str] = []
    if query.is_internal:
        reasons.append("internal Access form/report query")
    if query.kind not in {"SELECT", "UNION"}:
        reasons.append(f"query kind {query.kind}")
    patterns = [
        r"(?is)\bPARAMETERS\b",
        r"(?is)\bTRANSFORM\b",
        r"(?is)\bPIVOT\b",
        r"(?is)\bCROSSTAB\b",
        r"(?is)\bROWNUMBER\s*\(",
        r"(?is)\bRESETROWNUMBER\s*\(",
        r"(?is)\bFORM[S]?\s*!",
        r"(?is)\bREPORT[S]?\s*!",
        r"(?is)!\w+",
        r"(?is)\bCStr\s*\(",
        r"(?is)\bFormat\s*\(",
        r"(?is)\bDateAdd\s*\(",
        r"(?is)\bSwitch\s*\(",
        r"(?is)\bMAXIMUM\s*\(",
        r"(?is)\bSafeDivision\s*\(",
        r"(?is)\bTOP\s+\d+\b",
        r"(?is)#\d{1,2}/\d{1,2}/\d{2,4}#",
        r"(?is)&",
    ]
    if any(re.search(pattern, query.sql) for pattern in patterns):
        reasons.append("Access-specific syntax/reference detected")
    return reasons


def build_migration(
    access_tables: list[AccessTable],
    access_queries: list[AccessQuery],
    mysql_tables: dict[str, Any],
    mysql_views: dict[str, Any],
) -> dict[str, Any]:
    user_tables = [t for t in access_tables if not t.is_system]
    access_table_by_name = {t.name: t for t in user_tables}
    known_objects = sorted({*(t.name for t in user_tables), *(q.name for q in access_queries)}, key=len, reverse=True)

    table_map: list[dict[str, Any]] = []
    field_map: list[dict[str, Any]] = []
    priority_mismatches: list[dict[str, Any]] = []
    table_sql: list[str] = [
        "-- FITMOTOR Access -> MySQL table reconciliation script",
        f"-- Generated: {datetime.now().strftime('%Y-%m-%d %H:%M:%S %z')}",
        "-- Review before execution. Access MDB is source of truth.",
        "SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS;",
        "SET FOREIGN_KEY_CHECKS=0;",
        "",
    ]

    for at in sorted(user_tables, key=lambda t: t.name):
        mt = mysql_tables.get(at.name)
        status = "OK" if mt else "MISSING_TABLE"
        note = "" if mt else "Create table required in MySQL."
        if not at.fields:
            status = "EMPTY_ACCESS_TABLE"
            note = "Access TableDef exposes no fields through DAO; manual verification needed before MySQL creation."
        table_map.append({"accessTable": at.name, "mysqlTable": at.name if mt else "", "status": status, "note": note})
        if not at.fields:
            priority_mismatches.append(
                {
                    "object": "TABLE",
                    "name": at.name,
                    "issue": "Access table has no accessible fields via DAO",
                    "impact": "Cannot generate faithful MySQL table structure automatically",
                    "action": "Manually inspect linked/empty table in Access before creating MySQL table",
                }
            )
            continue
        if not mt:
            cols = []
            for f in at.fields:
                null_sql = "NOT NULL" if f.required else "NULL"
                cols.append(f"  {sql_ident(f.name)} {f.mysql_type_suggestion} {null_sql}")
            if at.primary_key:
                cols.append("  PRIMARY KEY (" + ", ".join(sql_ident(x) for x in at.primary_key) + ")")
            table_sql.append(f"CREATE TABLE IF NOT EXISTS {sql_ident(at.name)} (")
            table_sql.append(",\n".join(cols))
            table_sql.append(") ENGINE=InnoDB DEFAULT CHARSET=latin1;")
            table_sql.append("")
            priority_mismatches.append(
                {
                    "object": "TABLE",
                    "name": at.name,
                    "issue": "Missing MySQL table",
                    "impact": "Crystal/query cannot bind this source",
                    "action": "Create table with Access field names",
                }
            )
            continue

        mysql_cols_by_name = {c["name"]: c for c in mt["columns"]}
        unused_extras = list(mt["columns"])
        for af in at.fields:
            mc = mysql_cols_by_name.get(af.name)
            if mc:
                field_map.append({"table": at.name, "accessField": af.name, "mysqlField": mc["name"], "status": "OK", "action": ""})
                unused_extras = [x for x in unused_extras if x["name"] != mc["name"]]
                continue

            best = None
            best_score = 999
            afn = normalize_name(af.name)
            for extra in unused_extras:
                score = levenshtein(afn, normalize_name(extra["name"]))
                if score < best_score:
                    best_score = score
                    best = extra
            if best and best_score <= 2:
                field_map.append(
                    {
                        "table": at.name,
                        "accessField": af.name,
                        "mysqlField": best["name"],
                        "status": "LIKELY_RENAME",
                        "action": f"Rename {best['name']} -> {af.name}",
                    }
                )
                table_sql.append(f"-- Likely rename in {at.name}: {best['name']} -> {af.name}")
                table_sql.append(f"ALTER TABLE {sql_ident(at.name)} RENAME COLUMN {sql_ident(best['name'])} TO {sql_ident(af.name)};")
                table_sql.append("")
                priority_mismatches.append(
                    {
                        "object": "FIELD",
                        "name": f"{at.name}.{af.name}",
                        "issue": f"Likely renamed as {best['name']}",
                        "impact": "Crystal field binding may fail",
                        "action": "Rename MySQL column to Access field name",
                    }
                )
                unused_extras = [x for x in unused_extras if x["name"] != best["name"]]
            else:
                field_map.append(
                    {
                        "table": at.name,
                        "accessField": af.name,
                        "mysqlField": "",
                        "status": "MISSING_FIELD",
                        "action": "Add field",
                    }
                )
                table_sql.append(f"ALTER TABLE {sql_ident(at.name)} ADD COLUMN {sql_ident(af.name)} {af.mysql_type_suggestion} NULL;")
                table_sql.append("")
                priority_mismatches.append(
                    {
                        "object": "FIELD",
                        "name": f"{at.name}.{af.name}",
                        "issue": "Missing MySQL field",
                        "impact": "Crystal/query cannot bind this field",
                        "action": "Add MySQL column using Access name",
                    }
                )

        for extra in unused_extras:
            field_map.append(
                {
                    "table": at.name,
                    "accessField": "",
                    "mysqlField": extra["name"],
                    "status": "EXTRA_MYSQL_FIELD",
                    "action": "Review before drop; leave if web-only",
                }
            )

    table_sql.append("SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;")

    view_sql: list[str] = [
        "-- FITMOTOR Access QueryDefs -> MySQL VIEW compatibility script",
        f"-- Generated: {datetime.now().strftime('%Y-%m-%d %H:%M:%S %z')}",
        "-- Best-effort syntax conversion. Review MANUAL_REVIEW blocks before execution.",
        "",
    ]
    query_map: list[dict[str, Any]] = []
    query_deps: list[dict[str, Any]] = []

    query_names = [q.name for q in access_queries]
    query_name_set = set(query_names)
    query_dep_map: dict[str, set[str]] = {}
    table_dep_map: dict[str, set[str]] = {}
    query_meta: dict[str, dict[str, Any]] = {}
    for q in access_queries:
        deps = find_named_dependencies(q.sql, known_objects)
        query_deps.extend(
            {
                "query": q.name,
                "dependsOn": obj,
                "dependencyType": "TABLE" if obj in access_table_by_name else "QUERY",
            }
            for obj in deps
            if obj != q.name
        )
        query_dep_map[q.name] = {obj for obj in deps if obj in query_name_set and obj != q.name}
        table_dep_map[q.name] = {obj for obj in deps if obj in access_table_by_name}
        manual_reasons = build_manual_reasons(q)
        query_meta[q.name] = {
            "query": q,
            "deps": query_dep_map[q.name],
            "tableDeps": table_dep_map[q.name],
            "manualReasons": manual_reasons,
        }

    emitted: set[str] = set()
    pending = [q.name for q in sorted(access_queries, key=lambda x: x.name)]
    ordered_auto: list[AccessQuery] = []
    manual_queue: list[tuple[AccessQuery, list[str]]] = []
    while pending:
        progressed = False
        next_pending: list[str] = []
        for name in pending:
            meta = query_meta[name]
            q = meta["query"]
            deps = meta["deps"]
            if meta["manualReasons"]:
                next_pending.append(name)
                continue
            if deps <= emitted:
                ordered_auto.append(q)
                emitted.add(name)
                progressed = True
            else:
                next_pending.append(name)
        if not progressed:
            for name in next_pending:
                meta = query_meta[name]
                q = meta["query"]
                unresolved = sorted((meta["deps"] - emitted))
                reasons = list(meta["manualReasons"])
                if unresolved:
                    reasons.append("unresolved query dependency: " + ", ".join(unresolved))
                manual_queue.append((q, reasons))
                emitted.add(name)
            break
        pending = next_pending

    for q in ordered_auto:
        view = mysql_views.get(q.name)
        output_access = [f["name"] for f in q.output_fields]
        output_view = [c["name"] for c in sorted(view["columns"], key=lambda x: x["ordinal"])] if view else []
        if not view:
            output_status = "MISSING_VIEW"
        elif output_access == output_view:
            output_status = "OK"
        else:
            output_status = "OUTPUT_MISMATCH"

        query_map.append(
            {
                "accessQuery": q.name,
                "mysqlView": q.name if view else "",
                "status": output_status,
                "kind": q.kind,
                "internal": q.is_internal,
                "accessColumns": ", ".join(output_access),
                "mysqlColumns": ", ".join(output_view),
                "note": f"Access output error: {q.output_error}" if q.output_error else "",
            }
        )
        if output_status != "OK" and not q.is_internal:
            priority_mismatches.append(
                {
                    "object": "QUERY_VIEW",
                    "name": q.name,
                    "issue": output_status,
                    "impact": "Crystal/query compatibility risk",
                    "action": "Create/replace view with exact Access output columns",
                }
            )

        view_sql.append(f"DROP VIEW IF EXISTS {sql_ident(q.name)};")
        view_sql.append(f"CREATE OR REPLACE VIEW {sql_ident(q.name)} AS")
        view_sql.append(convert_access_sql_to_mysql(q.sql))
        view_sql.append(";")
        view_sql.append("")

    for q, manual in manual_queue:
        reason_text = "; ".join(dict.fromkeys(manual)) if manual else "manual review required"
        query_map.append(
            {
                "accessQuery": q.name,
                "mysqlView": "",
                "status": "MANUAL_REVIEW",
                "kind": q.kind,
                "internal": q.is_internal,
                "accessColumns": ", ".join(f["name"] for f in q.output_fields),
                "mysqlColumns": "",
                "note": reason_text,
            }
        )
        if not q.is_internal:
            priority_mismatches.append(
                {
                    "object": "QUERY_VIEW",
                    "name": q.name,
                    "issue": "MANUAL_REVIEW",
                    "impact": "Crystal/query compatibility risk",
                    "action": "Review query and create matching MySQL VIEW manually if needed",
                }
            )
        view_sql.append(f"-- MANUAL_REVIEW: {q.name} ({reason_text})")
        view_sql.append("-- ACCESS_SQL: " + re.sub(r"\s+", " ", q.sql.replace("\r", " ").replace("\n", " ")))
        view_sql.append("")

    # Queries that were marked manual from the start but not captured above.
    for q in sorted(access_queries, key=lambda x: x.name):
        if q.name in emitted:
            continue
        view_sql.append(f"-- MANUAL_REVIEW: {q.name} (not emitted due to unresolved state)")
        view_sql.append("-- ACCESS_SQL: " + re.sub(r"\s+", " ", q.sql.replace("\r", " ").replace("\n", " ")))
        view_sql.append("")

    return {
        "table_map": table_map,
        "field_map": field_map,
        "query_map": query_map,
        "query_dependencies": query_deps,
        "priority_mismatches": priority_mismatches,
        "table_sql": table_sql,
        "view_sql": view_sql,
    }


def main() -> int:
    parser = argparse.ArgumentParser(description="Audit and generate FITMOTOR GABUNG Access -> MySQL migration outputs.")
    parser.add_argument("--mdb-path", default=ACCESS_MDB_DEFAULT)
    parser.add_argument("--mysql-exe", default=MYSQL_EXE_DEFAULT)
    parser.add_argument("--mysql-host", default=MYSQL_HOST_DEFAULT)
    parser.add_argument("--mysql-user", default=MYSQL_USER_DEFAULT)
    parser.add_argument("--mysql-password", default=MYSQL_PASS_DEFAULT)
    parser.add_argument("--mysql-db", default=MYSQL_DB_DEFAULT)
    parser.add_argument("--project-root", default=str(Path.cwd()))
    parser.add_argument("--resolve-access-query-fields", action="store_true")
    args = parser.parse_args()

    project_root = Path(args.project_root)
    audit_dir = project_root / "docs" / "audit"
    migration_dir = project_root / "db" / "migrations"
    ensure_dir(audit_dir)
    ensure_dir(migration_dir)

    access_tables, access_queries, access_relations = read_access_metadata(args.mdb_path, args.resolve_access_query_fields)
    mysql_tables, mysql_views, mysql_raw = read_mysql_schema(
        args.mysql_exe, args.mysql_host, args.mysql_user, args.mysql_password, args.mysql_db
    )
    migration = build_migration(access_tables, access_queries, mysql_tables, mysql_views)

    summary = {
        "generatedAt": datetime.now().isoformat(timespec="seconds"),
        "mdbPath": args.mdb_path,
        "mysqlDatabase": args.mysql_db,
        "counts": {
            "accessTablesAll": len(access_tables),
            "accessTablesUser": len([t for t in access_tables if not t.is_system]),
            "accessQueriesAll": len(access_queries),
            "mysqlObjects": len(mysql_tables),
            "mysqlViews": len(mysql_views),
            "crystalReports": 0,
            "priorityMismatches": len(migration["priority_mismatches"]),
        },
        "note": "Detail lengkap ada di Markdown audit dan SQL migration output.",
    }

    audit_json = audit_dir / "FITMOTOR_ACCESS_MYSQL_AUDIT_DATA.json"
    audit_md = audit_dir / "FITMOTOR_CRYSTAL_ACCESS_MYSQL_AUDIT.md"
    table_sql_path = migration_dir / "2026-06-24_fitmotor_access_table_fix.sql"
    view_sql_path = migration_dir / "2026-06-24_fitmotor_access_views.sql"

    audit_json.write_text(json.dumps(summary, ensure_ascii=False, indent=2), encoding="utf-8")
    table_sql_path.write_text("\n".join(migration["table_sql"]), encoding="utf-8")
    view_sql_path.write_text("\n".join(migration["view_sql"]), encoding="utf-8")

    lines: list[str] = []
    lines.append("# Audit Migrasi FITMOTOR GABUNG: Access vs MySQL")
    lines.append("")
    lines.append(f"Generated: {datetime.now().strftime('%Y-%m-%d %H:%M:%S %z')}")
    lines.append("")
    lines.append(f"Source of truth: `{args.mdb_path}`")
    lines.append(f"Target MySQL: `{args.mysql_db}`")
    lines.append("Fokus audit: integrasi migrasi database secara keseluruhan.")
    lines.append("")
    lines.append("## Ringkasan Temuan")
    lines.append("")
    lines.append("| Item | Jumlah |")
    lines.append("|---|---:|")
    lines.append(f"| TableDefs Access (semua) | {summary['counts']['accessTablesAll']} |")
    lines.append(f"| Tabel user Access | {summary['counts']['accessTablesUser']} |")
    lines.append(f"| QueryDefs Access | {summary['counts']['accessQueriesAll']} |")
    lines.append(f"| Objek MySQL | {summary['counts']['mysqlObjects']} |")
    lines.append(f"| View MySQL | {summary['counts']['mysqlViews']} |")
    lines.append(f"| File Crystal `.rpt` | 0 |")
    lines.append(f"| Mismatch prioritas | {summary['counts']['priorityMismatches']} |")
    lines.append("")
    lines.append("Catatan: query Access internal bernama `~sq_...` tetap masuk data audit, tetapi view SQL otomatis hanya dibuat untuk query SELECT/UNION non-internal yang tidak mengandung sintaks Access berisiko.")
    lines.append("")
    lines.append("## A. Mapping Tabel")
    lines.append("")
    lines.append("| Tabel Access | Tabel MySQL | Status | Catatan |")
    lines.append("|---|---|---|---|")
    for row in sorted(migration["table_map"], key=lambda r: r["accessTable"]):
        lines.append(f"| {md_escape(row['accessTable'])} | {md_escape(row['mysqlTable'])} | {row['status']} | {md_escape(row['note'])} |")

    lines.append("")
    lines.append("## B. Mapping Field Bermasalah")
    lines.append("")
    lines.append("| Tabel | Field Access | Field MySQL Saat Ini | Status | Tindakan |")
    lines.append("|---|---|---|---|---|")
    for row in sorted([r for r in migration["field_map"] if r["status"] != "OK"], key=lambda r: (r["table"], r["accessField"], r["mysqlField"])):
        lines.append(f"| {md_escape(row['table'])} | {md_escape(row['accessField'])} | {md_escape(row['mysqlField'])} | {row['status']} | {md_escape(row['action'])} |")

    lines.append("")
    lines.append("## C. Mapping Query/View")
    lines.append("")
    lines.append("| Query Access | View MySQL | Status | Jenis | Internal | Catatan |")
    lines.append("|---|---|---|---|---|---|")
    for row in sorted(migration["query_map"], key=lambda r: r["accessQuery"]):
        lines.append(
            f"| {md_escape(row['accessQuery'])} | {md_escape(row['mysqlView'])} | {row['status']} | {row['kind']} | {row['internal']} | {md_escape(row['note'])} |"
        )

    lines.append("")
    lines.append("## D. Daftar Mismatch Prioritas")
    lines.append("")
    lines.append("| Objek | Nama | Masalah | Dampak | Tindakan |")
    lines.append("|---|---|---|---|---|")
    for row in sorted(migration["priority_mismatches"], key=lambda r: (r["object"], r["name"])):
        lines.append(f"| {row['object']} | {md_escape(row['name'])} | {md_escape(row['issue'])} | {md_escape(row['impact'])} | {md_escape(row['action'])} |")

    lines.append("")
    lines.append("## E. Dependency Query Access")
    lines.append("")
    lines.append("| Query Access | Bergantung Pada | Jenis Dependency |")
    lines.append("|---|---|---|")
    for row in sorted(migration["query_dependencies"], key=lambda r: (r["query"], r["dependsOn"])):
        lines.append(f"| {md_escape(row['query'])} | {md_escape(row['dependsOn'])} | {row['dependencyType']} |")

    lines.append("")
    lines.append("## F. SQL / Perubahan yang Disiapkan")
    lines.append("")
    lines.append(f"- SQL fix struktur tabel: `{table_sql_path.relative_to(project_root).as_posix()}`")
    lines.append(f"- SQL create/replace view: `{view_sql_path.relative_to(project_root).as_posix()}`")
    lines.append(f"- Data audit lengkap JSON: `{audit_json.relative_to(project_root).as_posix()}`")
    lines.append("")
    lines.append("## G. Progress Log")
    lines.append("")
    lines.append("- Sudah diaudit: metadata tabel/field/index Access, query SQL Access, schema tabel/view MySQL.")
    lines.append("- Sudah disiapkan: mapping tabel, mapping field, mapping query/view, dependency query, SQL rekonsiliasi tabel, SQL view best-effort.")
    lines.append("- Masih terbuka: review manual query Access dengan sintaks `PARAMETERS`, `TRANSFORM`, referensi form/report, operator concat `&`, dan query internal `~sq_...`.")
    lines.append("- Validasi berikutnya: jalankan SQL di database staging, lalu ulangi audit untuk memastikan status query/view berubah menjadi `OK` dan output kolom view sama persis.")
    lines.append("")
    lines.append("## H. Open Questions / Risks")
    lines.append("")
    lines.append("- Script rename field memakai heuristik nama mirip; review setiap `LIKELY_RENAME` sebelum eksekusi.")
    lines.append("- Field ekstra MySQL tidak otomatis di-drop karena bisa dipakai modul web baru; tandai dulu sebelum diputuskan.")
    lines.append("- Query Access yang sangat kompleks tetap ditandai `MANUAL_REVIEW` agar tidak salah terjemah.")

    audit_md.write_text("\n".join(lines), encoding="utf-8")

    print(f"AUDIT_MD={audit_md}")
    print(f"AUDIT_JSON={audit_json}")
    print(f"TABLE_SQL={table_sql_path}")
    print(f"VIEW_SQL={view_sql_path}")
    print(
        "COUNTS access_user_tables={0} access_queries={1} mysql_objects={2} mysql_views={3} priority_mismatches={4}".format(
            summary["counts"]["accessTablesUser"],
            summary["counts"]["accessQueriesAll"],
            summary["counts"]["mysqlObjects"],
            summary["counts"]["mysqlViews"],
            summary["counts"]["priorityMismatches"],
        )
    )
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
