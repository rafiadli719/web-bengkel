import xml.etree.ElementTree as ET
import os

p = r"C:\xampp\htdocs\web-bengkel\aplikasi\aplikasi\excel_xml\xl\_rels\workbook.xml.rels"
tree = ET.parse(p)
root = tree.getroot()
ns = {'ns': 'http://schemas.openxmlformats.org/package/2006/relationships'}

ids = ['rId6', 'rId18', 'rId19', 'rId20']
for r in root.findall('ns:Relationship', ns):
    rid = r.get('Id')
    target = r.get('Target')
    if rid in ids:
        print(f"{rid} -> {target}")
