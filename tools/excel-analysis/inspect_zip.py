import zipfile
import os

file_path = r"C:\xampp\htdocs\web-bengkel\aplikasi\aplikasi\order.xlsx"

try:
    print(f"Inspecting ZIP structure of {file_path}")
    with zipfile.ZipFile(file_path, 'r') as zip_ref:
        print("Files in archive:")
        for name in zip_ref.namelist():
            print(f" - {name}")
            
except Exception as e:
    print(f"Error: {str(e)}")
