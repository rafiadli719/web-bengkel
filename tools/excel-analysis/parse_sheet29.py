import xml.etree.ElementTree as ET
import os

base_path = r"C:\xampp\htdocs\web-bengkel\aplikasi\aplikasi\excel_xml"
shared_strings_file = os.path.join(base_path, "xl", "sharedStrings.xml")
sheet_file = os.path.join(base_path, "xl", "worksheets", "sheet29.xml")
out_file = r"C:\xampp\htdocs\web-bengkel\aplikasi\aplikasi\excel_reconstructed_v2.txt"

# 1. Parse Shared Strings
strings = []
if os.path.exists(shared_strings_file):
    tree = ET.parse(shared_strings_file)
    root = tree.getroot()
    ns = {'ns': 'http://schemas.openxmlformats.org/spreadsheetml/2006/main'}
    for si in root.findall('ns:si', ns):
        t = si.find('ns:t', ns)
        if t is not None:
            strings.append(t.text)
        else:
            text_parts = []
            for r in si.findall('ns:r', ns):
                rt = r.find('ns:t', ns)
                if rt is not None:
                    text_parts.append(rt.text)
            strings.append("".join(text_parts))

# 2. Parse Sheet
if os.path.exists(sheet_file):
    tree = ET.parse(sheet_file)
    root = tree.getroot()
    ns = {'ns': 'http://schemas.openxmlformats.org/spreadsheetml/2006/main'}
    
    rows = {}
    for row_elem in root.findall('.//ns:row', ns):
        row_idx_str = row_elem.get('r')
        if not row_idx_str: continue
        row_idx = int(row_idx_str)
        row_data = {}
        for c in row_elem.findall('ns:c', ns):
            ref = c.get('r')
            cell_type = c.get('t')
            v_elem = c.find('ns:v', ns)
            f_elem = c.find('ns:f', ns)
            
            value = ""
            formula = ""
            if f_elem is not None:
                formula = f_elem.text
            if v_elem is not None:
                if cell_type == 's':
                    try:
                        idx = int(v_elem.text)
                        value = strings[idx] if idx < len(strings) else f"ERR_REF_{idx}"
                    except:
                        value = v_elem.text
                else:
                    value = v_elem.text
            row_data[ref] = {"v": value, "f": formula}
        rows[row_idx] = row_data

    with open(out_file, "w", encoding="utf-8") as f:
        f.write(f"RECONSTRUCTED EXCEL CONTENT FROM SHEET29\n")
        for r in sorted(rows.keys()):
            row_cells = rows[r]
            formatted_row = []
            sorted_cols = sorted(row_cells.keys(), key=lambda x: (len(x), x))
            for col_name in sorted_cols:
                cell = row_cells[col_name]
                cell_str = cell['v']
                if cell['f']:
                    cell_str = f"F:{cell['f']} (={cell_str})"
                formatted_row.append(f"{col_name}: {cell_str}")
            f.write(f"R{r}: " + " | ".join(formatted_row) + "\n")
    print(f"Reconstructed data saved to {out_file}")
else:
    print(f"{sheet_file} not found.")
