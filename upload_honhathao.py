import os
import sys
import ftplib
import time

if hasattr(sys.stdout, 'reconfigure'):
    sys.stdout.reconfigure(encoding='utf-8')

HOST = "pixelgear.getenjoyment.net"
USER = "4776587"
PASSWORD = "thapvi123"
TARGET_DOMAIN = "honhathao.id.vn"

print(f"=== UPLOAD NHANH SANG TEN MIEN MAIN: {TARGET_DOMAIN} ===")

def get_ftp():
    for attempt in range(5):
        try:
            ftp = ftplib.FTP(HOST, USER, PASSWORD, timeout=30)
            ftp.set_pasv(True)
            return ftp
        except Exception as e:
            print(f"Thử lại kết nối FTP ({attempt + 1}/5)...")
            time.sleep(2)
    print("Lỗi: Không thể kết nối FTP.")
    sys.exit(1)

ftp = get_ftp()
print("-> Kết nối FTP thành công!")

try:
    ftp.cwd("/")
    ftp.cwd(TARGET_DOMAIN)
    print(f"-> Đã vào thư mục {TARGET_DOMAIN}")
except Exception:
    try:
        ftp.cwd("/")
        ftp.cwd("www.honhathao.id.vn")
        TARGET_DOMAIN = "www.honhathao.id.vn"
        print(f"-> Đã vào thư mục {TARGET_DOMAIN}")
    except Exception as e:
        print(f"Lỗi truy cập thư mục: {e}")
        sys.exit(1)

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

ignored_names = {".git", "node_modules", "website.zip", "website.txt", "upload.py", "upload_honhathao.py", "upload_pixelgear.py", ".gitignore"}

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
                    ftp.cwd(TARGET_DOMAIN)
                    if rel_dir != ".":
                        ensure_dir(rel_dir)
                except Exception:
                    pass

        if uploaded:
            print(f"[{TARGET_DOMAIN}] Up thành công: {remote_path}")
        else:
            print(f"[{TARGET_DOMAIN}] Bỏ qua: {remote_path}")

try:
    ftp.quit()
except Exception:
    pass

print(f"\n🎉 HOÀN TẤT UPLOAD SANG TÊN MIỀN {TARGET_DOMAIN}!")
