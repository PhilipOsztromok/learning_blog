"""
Build vim_commands.pdf from vim_appendix_commands.html
Uses Chrome headless to render the dark-theme HTML to PDF.
"""

import subprocess
import sys
import os
import tempfile

BASE = r"C:\Users\emuba\OneDrive\My Learning\WebSites\website\claude-generated-files"
HTML_FRAGMENT = os.path.join(BASE, "vim_appendix_commands.html")
OUTPUT_PDF    = os.path.join(BASE, "vim_commands.pdf")

# ── Read the HTML fragment ────────────────────────────────────
with open(HTML_FRAGMENT, "r", encoding="utf-8") as f:
    fragment = f.read()

# ── Wrap in a full HTML document ─────────────────────────────
full_html = f"""<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Vim Commands</title>
  <style>
    /* Page setup */
    @page {{
      size: A4;
      margin: 14mm 12mm 14mm 12mm;
    }}
    * {{ box-sizing: border-box; }}
    html, body {{
      background: #12141e;
      color: #e8e8e8;
      font-family: 'Georgia', serif;
      font-size: 9.5pt;
      line-height: 1.5;
      margin: 0; padding: 0;
    }}

    /* Cover / title block */
    .cover {{
      text-align: center;
      padding: 30mm 0 20mm 0;
      page-break-after: always;
    }}
    .cover h1 {{
      font-size: 2.8rem;
      color: #4f8ef7;
      margin: 0 0 0.3em 0;
      letter-spacing: 0.04em;
    }}
    .cover .subtitle {{
      color: #7a8aaa;
      font-style: italic;
      font-size: 1rem;
      margin-bottom: 2em;
    }}
    .cover .legend-cover {{
      display: inline-flex; flex-wrap: wrap; gap: 0.6em 1.4em;
      background: #1a1d27; border: 1px solid #2a2d3e; border-radius: 8px;
      padding: 1em 1.6em; margin-top: 1.5em; font-size: 0.85rem;
      justify-content: center;
    }}
    .cover .legend-cover span {{ display: flex; align-items: center; gap: 0.4em; color: #9aabcc; }}

    /* Avoid breaking command rows across pages */
    .cr {{ page-break-inside: avoid; }}

    /* Section headings start on same page as content where possible */
    h2 {{ page-break-after: avoid; }}
    .cs {{ page-break-after: avoid; }}
  </style>
</head>
<body>

<!-- Cover page -->
<div class="cover">
  <h1>Vim Commands</h1>
  <p class="subtitle">Complete command reference — Learning Vim course</p>
  <div class="legend-cover">
    <span><span class="mb mb-n" style="display:inline-block;font-family:Consolas,monospace;font-size:0.7rem;padding:0.1em 0.45em;border-radius:3px;background:#1a2a4a;color:#79b8ff;border:1px solid #2a4a7a;font-weight:bold;">N</span> Normal mode</span>
    <span><span class="mb mb-i" style="display:inline-block;font-family:Consolas,monospace;font-size:0.7rem;padding:0.1em 0.45em;border-radius:3px;background:#1a3a1a;color:#85e89d;border:1px solid #2a5a2a;font-weight:bold;">I</span> Insert mode</span>
    <span><span class="mb mb-c" style="display:inline-block;font-family:Consolas,monospace;font-size:0.7rem;padding:0.1em 0.45em;border-radius:3px;background:#2a1f10;color:#ffab70;border:1px solid #4a3510;font-weight:bold;">C</span> Command mode</span>
    <span><span class="mb mb-v" style="display:inline-block;font-family:Consolas,monospace;font-size:0.7rem;padding:0.1em 0.45em;border-radius:3px;background:#2a1a3a;color:#d2a8ff;border:1px solid #4a2a6a;font-weight:bold;">V</span> Visual mode</span>
    <span><span class="mb mb-s" style="display:inline-block;font-family:Consolas,monospace;font-size:0.7rem;padding:0.1em 0.45em;border-radius:3px;background:#1a1a1a;color:#aaaaaa;border:1px solid #333;font-weight:bold;">S</span> Shell / terminal</span>
    <span><span class="mb mb-r" style="display:inline-block;font-family:Consolas,monospace;font-size:0.7rem;padding:0.1em 0.45em;border-radius:3px;background:#2a1a1a;color:#ff7b72;border:1px solid #4a2a2a;font-weight:bold;">RC</span> .vimrc setting</span>
    <span><span class="mb mb-any" style="display:inline-block;font-family:Consolas,monospace;font-size:0.7rem;padding:0.1em 0.45em;border-radius:3px;background:#1a2020;color:#7adcdc;border:1px solid #2a4040;font-weight:bold;">ANY</span> Any mode</span>
  </div>
</div>

<!-- Command reference (the CMS fragment) -->
{fragment}

</body>
</html>
"""

# ── Write to a temp file ──────────────────────────────────────
tmp = tempfile.NamedTemporaryFile(suffix=".html", delete=False, mode="w", encoding="utf-8")
tmp.write(full_html)
tmp.close()
tmp_path = tmp.name

print(f"Temporary HTML written to: {tmp_path}")

# ── Locate Chrome ─────────────────────────────────────────────
chrome_candidates = [
    r"C:\Program Files\Google\Chrome\Application\chrome.exe",
    r"C:\Program Files (x86)\Google\Chrome\Application\chrome.exe",
    r"C:\Users\emuba\AppData\Local\Google\Chrome\Application\chrome.exe",
]

chrome = None
for c in chrome_candidates:
    if os.path.exists(c):
        chrome = c
        break

if not chrome:
    print("ERROR: Chrome not found. Please install Google Chrome.")
    sys.exit(1)

print(f"Using Chrome: {chrome}")

# ── Generate PDF via Chrome headless ─────────────────────────
cmd = [
    chrome,
    "--headless=new",
    "--disable-gpu",
    "--no-sandbox",
    "--disable-software-rasterizer",
    f"--print-to-pdf={OUTPUT_PDF}",
    "--print-to-pdf-no-header",
    "--no-pdf-header-footer",
    f"file:///{tmp_path.replace(os.sep, '/')}",
]

print("Running Chrome headless...")
result = subprocess.run(cmd, capture_output=True, text=True, timeout=120)

# ── Clean up temp file ────────────────────────────────────────
os.unlink(tmp_path)

# ── Report result ─────────────────────────────────────────────
if result.returncode == 0 and os.path.exists(OUTPUT_PDF):
    size_kb = os.path.getsize(OUTPUT_PDF) // 1024
    print(f"\nPDF generated successfully!")
    print(f"  Output : {OUTPUT_PDF}")
    print(f"  Size   : {size_kb} KB")
else:
    print(f"\nChrome returned exit code {result.returncode}")
    if result.stderr:
        print("stderr:", result.stderr[:1000])
    sys.exit(1)
