import zipfile
import os

file_path = r"C:\xampp\htdocs\web-bengkel\aplikasi\aplikasi\order.xlsx"
extract_dir = r"C:\xampp\htdocs\web-bengkel\aplikasi\aplikasi\excel_xml"

try:
    if not os.path.exists(extract_dir):
        os.makedirs(extract_dir)
        
    with zipfile.ZipFile(file_path, 'r') as zip_ref:
        zip_ref.extractall(extract_dir)
    print(f"Extracted to {extract_dir}")
    
    # List important files
    for root, dirs, files in os.walk(extract_dir):
        for file in files:
            print(os.path.join(root, file))
            
except Exception as e:
    print(f"Error: {str(e)}")
