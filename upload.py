import os
import sys
import ftplib
import time

if hasattr(sys.stdout, 'reconfigure'):
    sys.stdout.reconfigure(encoding='utf-8')

HOST = "pixelgear.getenjoyment.net"
USER = "4776587"
PASSWORD = "thapvi123"

print("=== CONG CU UPLOAD PIXELGEAR STORE ===")
print("1. Upload riêng tên miền chính: honhathao.id.vn (Nhanh)")
print("2. Upload riêng tên miền phụ: pixelgear.getenjoyment.net (Nhanh)")
print("3. Upload đồng bộ cả 2 tên miền")

choice = "1"
if len(sys.argv) > 1:
    choice = sys.argv[1]
else:
    try:
        user_input = input("\nChon so (1, 2, 3) [Mac dinh: 1]: ").strip()
        if user_input:
            choice = user_input
    except Exception:
        choice = "1"

if choice == "1":
    valid_target_dirs = ["honhathao.id.vn"]
elif choice == "2":
    valid_target_dirs = ["pixelgear.getenjoyment.net"]
else:
    valid_target_dirs = ["honhathao.id.vn", "pixelgear.getenjoyment.net"]

def get_ftp():
    for attempt in range(5):
        try:
            ftp = ftplib.FTP(HOST, USER, PASSWORD, timeout=30)
            ftp.set_pasv(True)
            return ftp
        except Exception as e:
            time.sleep(1)
    print("Lỗi: Không thể kết nối FTP.")
    sys.exit(1)

ftp = get_ftp()
print("-> Kết nối FTP thành công!")

ignored_names = {".git", "node_modules", "website.zip", "website.txt", "upload.py", "upload_honhathao.py", "upload_pixelgear.py", ".gitignore"}

for target_folder in valid_target_dirs:
    print(f"\n==========================================")
    print(f"-> ĐANG UPLOAD MÃ NGUỒN CHO TÊN MIỀN: {target_folder}")
    print(f"==========================================")
    
    try:
        ftp.cwd("/")
        ftp.cwd(target_folder)
    except Exception:
        try:
            ftp.cwd("/")
            ftp.cwd(f"www.{target_folder}")
            target_folder = f"www.{target_folder}"
        except Exception as e:
            print(f"Bỏ qua thư mục {target_folder}: {e}")
            continue

    def ensure_dir(path):
        parts = path.replace("\\", "/").strip("/").split("/")
        curr = ""
        for p in parts:
            if not p: continue
            curr = f"{curr}/{p}" if curr else p
            try:
                ftp.mkd(curr)
            except Exception:
                pass

    for root, dirs, files in os.walk("."):
        dirs[:] = [d for d in dirs if d not in ignored_names]
        rel_dir = os.path.relpath(root, ".").replace("\\", "/")
        if rel_dir != ".":
            ensure_dir(rel_dir)
            
        for file in files:
            if file in ignored_names:
                continue
                
            local_path = os.path.join(root, file)
            remote_path = f"{rel_dir}/{file}" if rel_dir != "." else file
            
            uploaded = False
            for retry in range(3):
                try:
                    with open(local_path, "rb") as f:
                        ftp.storbinary(f"STOR {remote_path}", f)
                    uploaded = True
                    break
                except Exception:
                    time.sleep(1)
                    try:
                        ftp = get_ftp()
                        ftp.cwd("/")
                        ftp.cwd(target_folder)
                        if rel_dir != ".":
                            ensure_dir(rel_dir)
                    except Exception:
                        pass

            if uploaded:
                print(f"[{target_folder}] Up thành công: {remote_path}")
            else:
                print(f"[{target_folder}] Bỏ qua: {remote_path}")

try:
    ftp.quit()
except Exception:
    pass

print("\n🎉 HOÀN TẤT UPLOAD!")
