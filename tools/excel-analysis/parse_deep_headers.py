import xml.etree.ElementTree as ET
import os

base_path = r"C:\xampp\htdocs\web-bengkel\aplikasi\aplikasi\excel_xml"
shared_strings_file = os.path.join(base_path, "xl", "sharedStrings.xml")
out_file = r"C:\xampp\htdocs\web-bengkel\aplikasi\aplikasi\excel_deep_headers.txt"

strings = []
if os.path.exists(shared_strings_file):
    tree = ET.parse(shared_strings_file)
    root = tree.getroot()
    ns = {'ns': 'http://schemas.openxmlformats.org/spreadsheetml/2006/main'}
    for si in root.findall('ns:si', ns):
        t = si.find('ns:t', ns)
        if t is not None:
            strings.append(t.text if t.text else "")
        else:
            text_parts = []
            for r in si.findall('ns:r', ns):
                rt = r.find('ns:t', ns)
                if rt is not None and rt.text:
                    text_parts.append(rt.text)
            strings.append("".join(text_parts))

def parse_deep(sheet_name, row_limit=5):
    sheet_file = os.path.join(base_path, "xl", "worksheets", f"{sheet_name}.xml")
    if not os.path.exists(sheet_file): return f"{sheet_name} NOT FOUND"
    context = ET.iterparse(sheet_file, events=('start', 'end'))
    ns = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main'
    result = []
    for event, elem in context:
        if event == 'end' and elem.tag == f'{{{ns}}}row':
            r_idx = elem.get('r')
            if int(r_idx) > row_limit:
                elem.clear()
                break
            row_data = []
            for c in elem.findall(f'{{{ns}}}c'):
                ref = c.get('r')
                v_elem = c.find(f'{{{ns}}}v')
                f_elem = c.find(f'{{{ns}}}f')
                cell_type = c.get('t')
                val = ""
                if v_elem is not None:
                    if cell_type == 's': val = strings[int(v_elem.text)]
                    else: val = v_elem.text
                form = f"F:{f_elem.text}" if f_elem is not None else ""
                row_data.append(f"{ref}:{val} {form}")
            result.append(f"R{r_idx}: " + " | ".join(row_data))
            elem.clear()
    return "\n".join(result)

with open(out_file, "w", encoding="utf-8") as f:
    f.write("=== SHEET20 (PO SUPPLIER) ===\n")
    f.write(parse_deep("sheet20", 10) + "\n\n")
    f.write("=== SHEET18 (ORDER 1) ===\n")
    f.write(parse_deep("sheet18", 5) + "\n\n")
    f.write("=== SHEET19 (ORDER 2) ===\n")
    f.write(parse_deep("sheet19", 5) + "\n\n")
    f.write("=== SHEET24 (RENCANA ORDER) ===\n")
    f.write(parse_deep("sheet24", 10) + "\n\n")
print(f"Deep headers saved to {out_file}")
