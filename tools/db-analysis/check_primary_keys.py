import re

# Read SQL file
with open('fitmotor_dbbengkel.sql', 'r', encoding='utf-8', errors='ignore') as f:
    content = f.read()

# Split into table definitions
tables = re.findall(r'CREATE TABLE `([^`]+)`(.*?)(?=CREATE TABLE|CREATE TRIGGER|CREATE VIEW|$)',
                    content, re.DOTALL)

print('=== PRIMARY KEY ANALYSIS ===\n')

tables_with_pk = []
tables_without_pk = []

for table_name, table_def in tables:
    # Look for PRIMARY KEY in the definition
    if re.search(r'PRIMARY KEY', table_def, re.IGNORECASE):
        tables_with_pk.append(table_name)
    else:
        tables_without_pk.append(table_name)

print(f'Tables WITH Primary Key: {len(tables_with_pk)}')
print(f'Tables WITHOUT Primary Key: {len(tables_without_pk)}\n')

if len(tables_without_pk) > 0:
    print('Tables WITHOUT Primary Key:')
    for i, table in enumerate(sorted(tables_without_pk)[:20], 1):
        print(f'  {i}. {table}')
    if len(tables_without_pk) > 20:
        print(f'  ... and {len(tables_without_pk) - 20} more')

print('\n\nSample tables WITH Primary Key:')
for i, table in enumerate(sorted(tables_with_pk)[:15], 1):
    print(f'  {i}. {table}')

# Check if view_ tables have primary keys
view_tables_with_pk = [t for t in tables_with_pk if t.startswith('view_')]
view_tables_without_pk = [t for t in tables_without_pk if t.startswith('view_')]

print(f'\n\nView_ Tables Analysis:')
print(f'  - WITH Primary Key: {len(view_tables_with_pk)}')
print(f'  - WITHOUT Primary Key: {len(view_tables_without_pk)}')
