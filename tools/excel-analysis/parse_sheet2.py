import xml.etree.ElementTree as ET
import os

base_path = r"C:\xampp\htdocs\web-bengkel\aplikasi\aplikasi\excel_xml"
shared_strings_file = os.path.join(base_path, "xl", "sharedStrings.xml")
sheet_file = os.path.join(base_path, "xl", "worksheets", "sheet2.xml")
out_file = r"C:\xampp\htdocs\web-bengkel\aplikasi\aplikasi\excel_reconstructed_sheet2.txt"

# 1. Parse Shared Strings
strings = []
if os.path.exists(shared_strings_file):
    print("Parsing shared strings...")
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
    print(f"Total shared strings: {len(strings)}")

# 2. Parse Sheet
if os.path.exists(sheet_file):
    print(f"Parsing {sheet_file}...")
    # Using iterparse for memory efficiency if it's large
    context = ET.iterparse(sheet_file, events=('start', 'end'))
    ns = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main'
    
    rows = {}
    current_row_idx = 0
    
    with open(out_file, "w", encoding="utf-8") as f:
        f.write(f"RECONSTRUCTED EXCEL CONTENT FROM SHEET2\n")
        
        for event, elem in context:
            if event == 'end' and elem.tag == f'{{{ns}}}row':
                row_idx = int(elem.get('r'))
                row_data = []
                for c in elem.findall(f'{{{ns}}}c'):
                    ref = c.get('r')
                    cell_type = c.get('t')
                    v_elem = c.find(f'{{{ns}}}v')
                    f_elem = c.find(f'{{{ns}}}f')
                    
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
                    
                    cell_info = f"{ref}: "
                    if formula:
                        cell_info += f"F:{formula} (={value})"
                    else:
                        cell_info += value
                    row_data.append(cell_info)
                
                if row_data:
                    f.write(f"R{row_idx}: " + " | ".join(row_data) + "\n")
                
                elem.clear() # Clear element from memory
                if row_idx >= 200: # Limit to 200 rows for analysis
                    break

    print(f"Reconstructed data saved to {out_file}")
else:
    print(f"{sheet_file} not found.")
