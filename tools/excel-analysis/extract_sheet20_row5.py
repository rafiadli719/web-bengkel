import xml.etree.ElementTree as ET
import os

base_path = r"C:\xampp\htdocs\web-bengkel\aplikasi\aplikasi\excel_xml"
shared_strings_file = os.path.join(base_path, "xl", "sharedStrings.xml")
sheet_file = os.path.join(base_path, "xl", "worksheets", "sheet20.xml")
out_file = r"C:\xampp\htdocs\web-bengkel\aplikasi\aplikasi\sheet20_row5_cols.txt"

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

if os.path.exists(sheet_file):
    context = ET.iterparse(sheet_file, events=('start', 'end'))
    ns = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main'
    with open(out_file, "w", encoding="utf-8") as f:
        for event, elem in context:
            if event == 'end' and elem.tag == f'{{{ns}}}row':
                r_idx = elem.get('r')
                if r_idx == "5":
                    cols = []
                    for c in elem.findall(f'{{{ns}}}c'):
                        ref = c.get('r')
                        v_elem = c.find(f'{{{ns}}}v')
                        f_elem = c.find(f'{{{ns}}}f')
                        val = ""
                        if v_elem is not None:
                            if c.get('t') == 's': val = strings[int(v_elem.text)]
                            else: val = v_elem.text
                        form = f"F:{f_elem.text}" if f_elem is not None else ""
                        cols.append(f"{ref}: {val} {form}")
                    f.write(" | ".join(cols) + "\n")
                    break
                elem.clear()
    print(f"Row 5 saved to {out_file}")
