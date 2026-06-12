import xml.etree.ElementTree as ET
import os

p = r"C:\xampp\htdocs\web-bengkel\aplikasi\aplikasi\excel_xml\xl\_rels\workbook.xml.rels"
tree = ET.parse(p)
root = tree.getroot()
ns = {'ns': 'http://schemas.openxmlformats.org/package/2006/relationships'}

for r in root.findall('ns:Relationship', ns):
    rid = r.get('Id')
    target = r.get('Target')
    print(f"{rid} -> {target}")
