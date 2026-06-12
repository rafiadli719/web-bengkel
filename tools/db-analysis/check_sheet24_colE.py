import xml.etree.ElementTree as ET
import os

base_path = r"C:\xampp\htdocs\web-bengkel\aplikasi\aplikasi\excel_xml"
sheet_file = os.path.join(base_path, "xl", "worksheets", "sheet24.xml")

if os.path.exists(sheet_file):
    context = ET.iterparse(sheet_file, events=('start', 'end'))
    ns = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main'
    
    print("Checking Sheet24 for Column E data...")
    count = 0
    for event, elem in context:
        if event == 'end' and elem.tag == f'{{{ns}}}row':
            for c in elem.findall(f'{{{ns}}}c'):
                ref = c.get('r')
                if ref and ref.startswith("E"):
                    v_elem = c.find(f'{{{ns}}}v')
                    f_elem = c.find(f'{{{ns}}}f')
                    val = v_elem.text if v_elem is not None else ""
                    form = f_elem.text if f_elem is not None else ""
                    if val or form:
                        print(f"{ref}: Val={val}, Formula={form}")
                        count += 1
            if count > 10: break
            elem.clear()
else: print("Sheet24 missing")
