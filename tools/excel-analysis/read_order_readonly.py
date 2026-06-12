import openpyxl

file_path = r"C:\xampp\htdocs\web-bengkel\aplikasi\aplikasi\order.xlsx"

try:
    print(f"Opening {file_path} in read_only mode...")
    wb = openpyxl.load_workbook(file_path, read_only=True, data_only=False)
    sheet = wb.active
    print(f"Active Sheet: {sheet.title}")
    
    # Read the first 50 rows, 20 columns
    for r_idx, row in enumerate(sheet.iter_rows(min_row=1, max_row=50, max_col=20)):
        vals = []
        for cell in row:
            val = cell.value
            if val is None:
                vals.append("")
            elif isinstance(val, str) and val.startswith('='):
                vals.append(f"FORMULA:{val}")
            else:
                vals.append(str(val))
        print(" | ".join(vals))
        
except Exception as e:
    print(f"Error: {str(e)}")
