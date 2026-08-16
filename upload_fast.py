import os
import sys
import ftplib
import time
from concurrent.futures import ThreadPoolExecutor, as_completed

if hasattr(sys.stdout, 'reconfigure'):
    sys.stdout.reconfigure(encoding='utf-8')

# FTP Config
HOST = "ftpupload.net"
USER = "if0_42613531"
PASSWORD = "hqUiibOaHn"
TARGET_DIR = "honhathao.id.vn/htdocs"
MAX_WORKERS = 8

ignored_names = {".git", "node_modules", "website.zip", "website.txt", "upload.py", "upload_fast.py", "upload_honhathao.py", "upload_pixelgear.py", ".gitignore", ".agents", ".gemini", "__pycache__", "Website"}

print("=== FAST UPLOAD SCRIPT (MULTI-THREADED) ===")
print(f"Target: {TARGET_DIR} with {MAX_WORKERS} workers")

def get_ftp():
    for attempt in range(3):
        try:
            ftp = ftplib.FTP(HOST, USER, PASSWORD, timeout=60)
            ftp.set_pasv(True)
            return ftp
        except Exception as e:
            time.sleep(2)
    raise Exception("Failed to connect to FTP after 3 attempts.")

# 1. Create directories synchronously first
def setup_directories():
    print("-> Scanning local files and creating remote directories...")
    ftp = get_ftp()
    
    try:
        ftp.cwd("/")
        for part in TARGET_DIR.split("/"):
            ftp.cwd(part)
    except Exception as e:
        print(f"Error changing to {TARGET_DIR}: {e}")
        return []

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

    files_to_upload = []
    
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
            files_to_upload.append((local_path, remote_path))
            
    ftp.quit()
    return files_to_upload

def upload_file(task):
    local_path, remote_path = task
    for retry in range(3):
        ftp = None
        try:
            ftp = get_ftp()
            ftp.cwd("/")
            for part in TARGET_DIR.split("/"):
                ftp.cwd(part)
                
            with open(local_path, "rb") as f:
                ftp.storbinary(f"STOR {remote_path}", f)
            
            try:
                ftp.quit()
            except:
                pass
            return f"[OK] {remote_path}"
        except Exception as e:
            if ftp:
                try: ftp.quit()
                except: pass
            time.sleep(2)
    return f"[ERROR] {remote_path}"

def main():
    start_time = time.time()
    
    try:
        files_to_upload = setup_directories()
        print(f"-> Found {len(files_to_upload)} files to upload.")
    except Exception as e:
        print(f"Directory setup failed: {e}")
        return

    print("-> Uploading files in parallel...")
    success_count = 0
    error_count = 0
    
    with ThreadPoolExecutor(max_workers=MAX_WORKERS) as executor:
        futures = {executor.submit(upload_file, task): task for task in files_to_upload}
        for future in as_completed(futures):
            res = future.result()
            print(res)
            if "[OK]" in res:
                success_count += 1
            else:
                error_count += 1
                
    elapsed = time.time() - start_time
    print(f"\n🎉 UPLOAD COMPLETE IN {elapsed:.1f}s!")
    print(f"Success: {success_count} | Errors: {error_count}")

if __name__ == "__main__":
    main()
