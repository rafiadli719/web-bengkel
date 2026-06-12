import re

# Read SQL file
with open('fitmotor_dbbengkel.sql', 'r', encoding='utf-8', errors='ignore') as f:
    content = f.read()

# Extract all table names
tables = re.findall(r'CREATE TABLE `([^`]+)`', content)

print(f'=== DATABASE FITMOTOR_DBBENGKEL ANALYSIS ===\n')
print(f'Total tables: {len(tables)}\n')

# Categorize tables
categories = {
    'Master Data': [t for t in tables if 'master' in t.lower() and not t.startswith('view_')],
    'Transaction - Service': [t for t in tables if 'serv' in t.lower() and not t.startswith('view_')],
    'Transaction - Sales/Purchase': [t for t in tables if any(x in t.lower() for x in ['penjualan', 'pembelian', 'jual', 'beli']) and not t.startswith('view_')],
    'Inventory/Items': [t for t in tables if any(x in t.lower() for x in ['item', 'barang', 'stok']) and not t.startswith('view_')],
    'Customer/Vehicle': [t for t in tables if any(x in t.lower() for x in ['pelanggan', 'kendaraan', 'motor']) and not t.startswith('view_')],
    'HR/Employee': [t for t in tables if any(x in t.lower() for x in ['pegawai', 'emp', 'mekanik', 'karyawan', 'user']) and not t.startswith('view_')],
    'Finance': [t for t in tables if any(x in t.lower() for x in ['hutang', 'piutang', 'kas', 'bayar', 'akun']) and not t.startswith('view_')],
    'View Tables': [t for t in tables if t.startswith('view_') or t.startswith('v_')],
    'Logs/Tracking': [t for t in tables if any(x in t.lower() for x in ['log', 'tracking', 'history', 'backup']) and not t.startswith('view_')],
    'Configuration': [t for t in tables if any(x in t.lower() for x in ['setting', 'config', 'tb_master_level', 'tb_master_posisi', 'tb_permissions', 'tb_user_roles'])],
}

for category, table_list in categories.items():
    if table_list:
        print(f'\n{category}: {len(table_list)} tables')
        for table in sorted(table_list)[:10]:  # Show first 10
            print(f'  - {table}')
        if len(table_list) > 10:
            print(f'  ... and {len(table_list) - 10} more')

# Find uncategorized tables
categorized = set()
for table_list in categories.values():
    categorized.update(table_list)

uncategorized = [t for t in tables if t not in categorized]
if uncategorized:
    print(f'\n\nUncategorized: {len(uncategorized)} tables')
    for table in sorted(uncategorized)[:15]:
        print(f'  - {table}')

print('\n\n=== KEY FINDINGS ===')

# Check for foreign keys
fk_count = len(re.findall(r'FOREIGN KEY', content, re.IGNORECASE))
print(f'- Foreign Key constraints: {fk_count}')

# Check for indexes
idx_count = len(re.findall(r'KEY `[^`]+`', content))
print(f'- Indexes defined: {idx_count}')

# Check for triggers
triggers = re.findall(r'CREATE.*TRIGGER `([^`]+)`', content, re.IGNORECASE)
print(f'- Triggers: {len(triggers)}')

# Check for stored procedures
procedures = re.findall(r'CREATE.*PROCEDURE `([^`]+)`', content, re.IGNORECASE)
print(f'- Stored Procedures: {len(procedures)}')

# Check for functions
functions = re.findall(r'CREATE.*FUNCTION `([^`]+)`', content, re.IGNORECASE)
print(f'- Functions: {len(functions)}')

print(f'\n- Total database objects: {len(tables) + len(triggers) + len(procedures) + len(functions)}')
