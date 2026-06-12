import xml.etree.ElementTree as ET
import os

base_path = r"C:\xampp\htdocs\web-bengkel\aplikasi\aplikasi\excel_xml"
shared_strings_file = os.path.join(base_path, "xl", "sharedStrings.xml")
sheet1_file = os.path.join(base_path, "xl", "worksheets", "sheet1.xml")

# 1. Parse Shared Strings
strings = []
if os.path.exists(shared_strings_file):
    tree = ET.parse(shared_strings_file)
    root = tree.getroot()
    # Namespace handling
    ns = {'ns': 'http://schemas.openxmlformats.org/spreadsheetml/2006/main'}
    for si in root.findall('ns:si', ns):
        t = si.find('ns:t', ns)
        if t is not None:
            strings.append(t.text)
        else:
            # Handle formatted text (multiple r segments)
            text_parts = []
            for r in si.findall('ns:r', ns):
                rt = r.find('ns:t', ns)
                if rt is not None:
                    text_parts.append(rt.text)
            strings.append("".join(text_parts))

# 2. Parse Sheet1
if os.path.exists(sheet1_file):
    tree = ET.parse(sheet1_file)
    root = tree.getroot()
    ns = {'ns': 'http://schemas.openxmlformats.org/spreadsheetml/2006/main'}
    
    rows = {}
    for row_elem in root.findall('.//ns:row', ns):
        row_idx = int(row_elem.get('r'))
        row_data = {}
        for c in row_elem.findall('ns:c', ns):
            ref = c.get('r') # e.g. A1
            cell_type = c.get('t')
            v_elem = c.find('ns:v', ns)
            f_elem = c.find('ns:f', ns)
            
            value = ""
            formula = ""
            
            if f_elem is not None:
                formula = f_elem.text
                
            if v_elem is not None:
                if cell_type == 's': # Shared String
                    idx = int(v_elem.text)
                    value = strings[idx] if idx < len(strings) else f"ERR_REF_{idx}"
                else:
                    value = v_elem.text
            
            row_data[ref] = {"v": value, "f": formula}
        rows[row_idx] = row_data

    # Output formatted data
    print("RECONSTRUCTED EXCEL CONTENT (First 100 rows)")
    for r in sorted(rows.keys())[:100]:
        row_cells = rows[r]
        formatted_row = []
        for col_name in sorted(row_cells.keys()):
            cell = row_cells[col_name]
            cell_str = cell['v']
            if cell['f']:
                cell_str = f"F:{cell['f']} (={cell_str})"
            formatted_row.append(f"{col_name}: {cell_str}")
        print(" | ".join(formatted_row))

else:
    print("Sheet1 file not found.")
