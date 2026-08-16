import os
import re
import glob

def update_favicons(file_path, is_admin=False):
    if not os.path.exists(file_path):
        return
    try:
        with open(file_path, 'r', encoding='utf-8', errors='ignore') as f:
            content = f.read()

        prefix = '../' if is_admin else ''
        favicon_tags = f'    <link rel="icon" type="image/png" href="{prefix}favicon.png?v=2">\n    <link rel="shortcut icon" href="{prefix}favicon.ico?v=2">'

        # Remove existing favicon tags
        content_clean = re.sub(r'\s*<link rel=[\'"](?:shortcut )?icon[\'"].*?>', '', content, flags=re.IGNORECASE)

        # Insert after <head>
        if '<head>' in content_clean:
            new_content = content_clean.replace('<head>', '<head>\n' + favicon_tags, 1)
        elif '<HEAD>' in content_clean:
            new_content = content_clean.replace('<HEAD>', '<HEAD>\n' + favicon_tags, 1)
        else:
            return

        with open(file_path, 'w', encoding='utf-8') as f:
            f.write(new_content)
        print(f'Updated {file_path}')
    except Exception as e:
        print(f'Error {file_path}: {e}')

# Root files
root_files = glob.glob('*.php')
for rf in root_files:
    update_favicons(rf, is_admin=False)

# Admin files
admin_files = glob.glob('admin/*.php')
for af in admin_files:
    update_favicons(af, is_admin=True)
