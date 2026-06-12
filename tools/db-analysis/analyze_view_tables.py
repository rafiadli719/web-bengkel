import re

# Read SQL file
with open('fitmotor_dbbengkel.sql', 'r', encoding='utf-8', errors='ignore') as f:
    content = f.read()

print('=== VIEW TABLES DETAILED ANALYSIS ===\n')

# Get all view_ tables
view_tables = re.findall(r'CREATE TABLE `(view_[^`]+)`(.*?)(?=CREATE TABLE|CREATE TRIGGER|$)',
                         content, re.DOTALL)

print(f'Total view_ tables: {len(view_tables)}\n')

# Categorize view tables by purpose
categories = {
    'Master Data Views': [],
    'Transaction Views': [],
    'Reporting/Summary Views': [],
    'Search/Lookup Views': [],
    'Status/Tracking Views': [],
    'Other Views': []
}

for table_name, table_def in view_tables:
    if 'master' in table_name or 'item' in table_name:
        categories['Master Data Views'].append(table_name)
    elif any(x in table_name for x in ['pembelian', 'penjualan', 'service', 'servis', 'order', 'po', 'pr', 'do']):
        categories['Transaction Views'].append(table_name)
    elif any(x in table_name for x in ['laporan', 'statistik', 'ringkasan', 'pembayaran', 'top_']):
        categories['Reporting/Summary Views'].append(table_name)
    elif any(x in table_name for x in ['cari', 'search', 'suggested']):
        categories['Search/Lookup Views'].append(table_name)
    elif any(x in table_name for x in ['status', 'tracking', 'follow']):
        categories['Status/Tracking Views'].append(table_name)
    else:
        categories['Other Views'].append(table_name)

for category, tables in categories.items():
    if tables:
        print(f'\n{category}: {len(tables)}')
        for table in sorted(tables):
            print(f'  - {table}')

# Check if these are actually views or tables with data
print('\n\n=== CHECKING IF view_ TABLES CONTAIN DATA ===\n')

# Look for INSERT statements for view_ tables
view_inserts = []
for vt, _ in view_tables:
    insert_pattern = rf'INSERT INTO `{vt}`'
    if re.search(insert_pattern, content, re.IGNORECASE):
        view_inserts.append(vt)

print(f'view_ tables with INSERT statements (contains data): {len(view_inserts)}')
if view_inserts:
    for table in sorted(view_inserts)[:10]:
        print(f'  - {table}')
    if len(view_inserts) > 10:
        print(f'  ... and {len(view_inserts) - 10} more')

print(f'\nview_ tables WITHOUT INSERT statements (likely empty): {len(view_tables) - len(view_inserts)}')

# Sample structure of a view_ table
print('\n\n=== SAMPLE view_ TABLE STRUCTURE ===')
if view_tables:
    sample_table, sample_def = view_tables[0]
    print(f'\nTable: {sample_table}')
    # Extract columns
    col_pattern = r'`([^`]+)` ([^\n,]+)'
    columns = re.findall(col_pattern, sample_def.split('ENGINE=')[0])[:10]
    print('First 10 columns:')
    for col_name, col_type in columns:
        print(f'  - {col_name}: {col_type.strip()}')
