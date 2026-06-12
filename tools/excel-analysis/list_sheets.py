import xml.etree.ElementTree as ET
import os

p = r"C:\xampp\htdocs\web-bengkel\aplikasi\aplikasi\excel_xml\xl\workbook.xml"
tree = ET.parse(p)
root = tree.getroot()
ns = {'ns': 'http://schemas.openxmlformats.org/spreadsheetml/2006/main'}

sheets = []
for s in root.findall('.//ns:sheet', ns):
    name = s.get('name')
    rid = s.get('{http://schemas.openxmlformats.org/officeDocument/2006/relationships}id')
    sheets.append(f"{name} ({rid})")

print("SHEETS IN WORKBOOK:")
for s in sheets:
    print(s)
