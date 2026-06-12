import re

# Read SQL file
with open('fitmotor_dbbengkel.sql', 'r', encoding='utf-8', errors='ignore') as f:
    content = f.read()

print('=== DATABASE RELATIONSHIP ANALYSIS ===\n')

# Find all foreign key relationships
fk_pattern = r'FOREIGN KEY \(`([^`]+)`\) REFERENCES `([^`]+)` \(`([^`]+)`\)'
foreign_keys = re.findall(fk_pattern, content, re.IGNORECASE)

if foreign_keys:
    print(f'Foreign Key Relationships Found: {len(foreign_keys)}\n')

    # Group by referenced table
    refs = {}
    for fk_col, ref_table, ref_col in foreign_keys:
        if ref_table not in refs:
            refs[ref_table] = []
        refs[ref_table].append((fk_col, ref_col))

    print('Tables being referenced (parent tables):')
    for table, relations in sorted(refs.items(), key=lambda x: len(x[1]), reverse=True):
        print(f'\n  {table} ({len(relations)} references)')
        for fk_col, ref_col in relations[:3]:
            print(f'    - Referenced by column: {ref_col}')
        if len(relations) > 3:
            print(f'    ... and {len(relations) - 3} more')

# Check for view_ tables and their likely source tables
print('\n\n=== VIEW TABLES ANALYSIS ===\n')

view_tables = re.findall(r'CREATE TABLE `(view_[^`]+)`', content)
print(f'Total view_ tables: {len(view_tables)}\n')

# Sample some view definitions
print('Sample view_ tables:')
for vt in sorted(view_tables)[:15]:
    print(f'  - {vt}')
if len(view_tables) > 15:
    print(f'  ... and {len(view_tables) - 15} more')

# Check for potential issues
print('\n\n=== POTENTIAL ISSUES ===\n')

# 1. Tables without primary keys
table_blocks = re.split(r'CREATE TABLE', content)
tables_without_pk = []

for block in table_blocks[1:]:  # Skip first split
    table_match = re.search(r'`([^`]+)`', block)
    if table_match:
        table_name = table_match.group(1)
        # Check if block contains PRIMARY KEY
        if 'PRIMARY KEY' not in block.split('ENGINE=')[0]:  # Only check table definition, not after
            tables_without_pk.append(table_name)

if tables_without_pk:
    print(f'1. Tables without PRIMARY KEY: {len(tables_without_pk)}')
    for t in sorted(tables_without_pk)[:10]:
        print(f'   - {t}')
    if len(tables_without_pk) > 10:
        print(f'   ... and {len(tables_without_pk) - 10} more')
else:
    print('1. All tables have PRIMARY KEY ✓')

# 2. Check for backup tables
backup_tables = [t for t in re.findall(r'CREATE TABLE `([^`]+)`', content)
                 if 'backup' in t.lower() or t.startswith('_backup')]
if backup_tables:
    print(f'\n2. Backup tables found: {len(backup_tables)}')
    for t in sorted(backup_tables):
        print(f'   - {t}')

# 3. Check for temp tables
temp_tables = [t for t in re.findall(r'CREATE TABLE `([^`]+)`', content)
               if 'temp' in t.lower() or '_rst' in t.lower()]
if temp_tables:
    print(f'\n3. Temporary tables found: {len(temp_tables)}')
    for t in sorted(temp_tables):
        print(f'   - {t}')

# 4. Check engines
engines = re.findall(r'ENGINE=(\w+)', content)
engine_counts = {}
for engine in engines:
    engine_counts[engine] = engine_counts.get(engine, 0) + 1

print(f'\n4. Storage Engines used:')
for engine, count in sorted(engine_counts.items(), key=lambda x: x[1], reverse=True):
    print(f'   - {engine}: {count} tables')

# 5. Check for UTF8 charset
charsets = re.findall(r'CHARSET=(\w+)', content)
charset_counts = {}
for charset in charsets:
    charset_counts[charset] = charset_counts.get(charset, 0) + 1

print(f'\n5. Character Sets used:')
for charset, count in sorted(charset_counts.items(), key=lambda x: x[1], reverse=True):
    print(f'   - {charset}: {count} tables')

print('\n')
