import xml.etree.ElementTree as ET
import os

base_path = r"C:\xampp\htdocs\web-bengkel\aplikasi\aplikasi\excel_xml"
shared_strings_file = os.path.join(base_path, "xl", "sharedStrings.xml")
out_file = r"C:\xampp\htdocs\web-bengkel\aplikasi\aplikasi\excel_all_headers.txt"

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

def get_headers(sheet_name, row_num):
    sheet_file = os.path.join(base_path, "xl", "worksheets", f"{sheet_name}.xml")
    if not os.path.exists(sheet_file): return f"{sheet_name} NOT FOUND"
    context = ET.iterparse(sheet_file, events=('start', 'end'))
    ns = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main'
    headers = []
    for event, elem in context:
        if event == 'end' and elem.tag == f'{{{ns}}}row':
            if elem.get('r') == str(row_num):
                for c in elem.findall(f'{{{ns}}}c'):
                    ref = c.get('r')
                    cell_type = c.get('t')
                    v_elem = c.find(f'{{{ns}}}v')
                    value = ""
                    if v_elem is not None:
                        if cell_type == 's':
                            idx = int(v_elem.text)
                            value = strings[idx] if idx < len(strings) else f"ERR_{idx}"
                        else: value = v_elem.text
                    headers.append(f"{ref}:{value}")
                elem.clear()
                return " | ".join(headers)
            elem.clear()
    return f"Row {row_num} not found"

with open(out_file, "w", encoding="utf-8") as f:
    f.write("SHEET20 Headers (Row 4):\n" + get_headers("sheet20", 4) + "\n\n")
    f.write("SHEET18 Headers (Row 1):\n" + get_headers("sheet18", 1) + "\n\n")
    f.write("SHEET19 Headers (Row 1):\n" + get_headers("sheet19", 1) + "\n\n")
    f.write("SHEET24 Headers (Row 3):\n" + get_headers("sheet24", 3) + "\n\n")
print(f"All headers saved to {out_file}")
