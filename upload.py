import os
import sys
import ftplib
import time

if hasattr(sys.stdout, 'reconfigure'):
    sys.stdout.reconfigure(encoding='utf-8')

# Cấu hình FTP InfinityFree
HOST = "ftpupload.net"
USER = "if0_42613531"
PASSWORD = "hqUiibOaHn"

print("=== CÔNG CỤ UPLOAD LÊN INFINITYFREE ===")
print("Đang chuẩn bị upload lên honhathao.id.vn/htdocs ...")

# Thư mục gốc trên InfinityFree của tên miền
valid_target_dirs = ["honhathao.id.vn/htdocs"]

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

ignored_names = {".git", "node_modules", "website.zip", "website.txt", "upload.py", "upload_honhathao.py", "upload_pixelgear.py", ".gitignore", ".agents", ".gemini"}

for target_folder in valid_target_dirs:
    print(f"\n==========================================")
    print(f"-> ĐANG UPLOAD MÃ NGUỒN VÀO: {target_folder}")
    print(f"==========================================")
    
    try:
        ftp.cwd("/")
        for part in target_folder.split("/"):
            ftp.cwd(part)
    except Exception as e:
        print(f"Lỗi truy cập thư mục {target_folder}: {e}")
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
                        for part in target_folder.split("/"):
                            ftp.cwd(part)
                        if rel_dir != ".":
                            ensure_dir(rel_dir)
                    except Exception:
                        pass

            if uploaded:
                print(f"Up thành công: {remote_path}")
            else:
                print(f"Bỏ qua (Lỗi): {remote_path}")

try:
    ftp.quit()
except Exception:
    pass

print("\n🎉 HOÀN TẤT UPLOAD LÊN INFINITYFREE!")
