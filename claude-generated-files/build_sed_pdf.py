"""
Build sed_course.pdf from all 12 sed_lesson_NN.html fragments.
Uses Chrome headless to render the dark-theme HTML to PDF.
"""

import subprocess
import sys
import os
import tempfile

BASE       = r"C:\Users\emuba\OneDrive\My Learning\WebSites\website\claude-generated-files"
OUTPUT_PDF = os.path.join(BASE, "sed_course.pdf")

CHAPTERS = [
    (1,  "Introduction to SED"),
    (2,  "The Substitute Command"),
    (3,  "Addresses and Line Selection"),
    (4,  "Delete, Print and Quit"),
    (5,  "Append, Insert and Change"),
    (6,  "The Hold Space"),
    (7,  "Multi-line Commands"),
    (8,  "Branching and Labels"),
    (9,  "The y Command and Other Commands"),
    (10, "Regular Expressions in SED"),
    (11, "SED Scripts and Real-World Recipes"),
    (12, "SED in Shell Scripts and Pipelines"),
]

# ── Read all lesson fragments ─────────────────────────────────
fragments = []
for num, title in CHAPTERS:
    path = os.path.join(BASE, f"sed_lesson_{num:02d}.html")
    if not os.path.exists(path):
        print(f"ERROR: Missing file: {path}")
        sys.exit(1)
    with open(path, "r", encoding="utf-8") as f:
        fragments.append((num, title, f.read()))
    print(f"  Read chapter {num:02d}: {title}")

print(f"\nAll {len(fragments)} chapters loaded.")

# ── Build the combined body HTML ─────────────────────────────
body_parts = []

for num, title, html in fragments:
    divider = f"""
<div class="chapter-divider">
  <div class="ch-num">Chapter {num:02d}</div>
  <div class="ch-title">{title}</div>
</div>
"""
    body_parts.append(divider + html)

combined_body = "\n".join(body_parts)

# ── Wrap in full HTML document ───────────────────────────────
full_html = f"""<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Learning SED — Complete Course</title>
  <style>
    @page {{
      size: A4;
      margin: 14mm 13mm 14mm 13mm;
    }}
    * {{ box-sizing: border-box; }}
    html, body {{
      background: #12141e;
      color: #e8e8e8;
      font-family: 'Georgia', serif;
      font-size: 9.5pt;
      line-height: 1.55;
      margin: 0; padding: 0;
    }}

    /* ── Cover page ── */
    .cover {{
      text-align: center;
      padding: 35mm 20mm 25mm 20mm;
      page-break-after: always;
    }}
    .cover .cover-label {{
      color: #4f8ef7;
      font-family: 'Consolas', monospace;
      font-size: 0.75rem;
      letter-spacing: 0.18em;
      text-transform: uppercase;
      margin-bottom: 0.8em;
    }}
    .cover h1 {{
      font-size: 3rem;
      color: #e8e8e8;
      margin: 0 0 0.15em 0;
      letter-spacing: 0.02em;
    }}
    .cover h1 span {{ color: #4f8ef7; }}
    .cover .subtitle {{
      color: #7a8aaa;
      font-style: italic;
      font-size: 1rem;
      margin-bottom: 2.5em;
    }}
    .cover .cover-toc {{
      display: inline-block;
      text-align: left;
      background: #1a1d27;
      border: 1px solid #2a2d3e;
      border-radius: 8px;
      padding: 1.2em 2em;
      margin: 0 auto;
    }}
    .cover .cover-toc h3 {{
      color: #4f8ef7;
      font-size: 0.78rem;
      text-transform: uppercase;
      letter-spacing: 0.1em;
      margin: 0 0 0.8em 0;
      font-family: 'Consolas', monospace;
    }}
    .cover .cover-toc .toc-row {{
      display: flex;
      gap: 1em;
      padding: 0.18em 0;
      font-size: 0.85rem;
      border-bottom: 1px solid #1e2030;
      align-items: baseline;
    }}
    .cover .cover-toc .toc-row:last-child {{ border-bottom: none; }}
    .cover .cover-toc .toc-num  {{ color: #4f8ef7; font-family: 'Consolas', monospace; min-width: 36px; }}
    .cover .cover-toc .toc-title {{ color: #c9d8ff; }}
    .cover .cover-toc .toc-tag  {{ color: #5a6a8a; font-size: 0.78rem; font-style: italic; margin-left: auto; }}
    .cover .cover-footer {{
      margin-top: 3em;
      color: #5a6a8a;
      font-size: 0.78rem;
      font-style: italic;
    }}

    /* ── Chapter divider ── */
    .chapter-divider {{
      page-break-before: always;
      padding: 18mm 0 8mm 0;
      border-bottom: 3px solid #4f8ef7;
      margin-bottom: 1.5em;
    }}
    .chapter-divider:first-of-type {{
      page-break-before: auto;
    }}
    .chapter-divider .ch-num {{
      font-family: 'Consolas', monospace;
      font-size: 0.75rem;
      color: #4f8ef7;
      text-transform: uppercase;
      letter-spacing: 0.15em;
      margin-bottom: 0.3em;
    }}
    .chapter-divider .ch-title {{
      font-size: 1.8rem;
      color: #e8e8e8;
      font-family: 'Georgia', serif;
    }}

    /* ── Lesson content sizing ── */
    .lx-lesson {{
      max-width: 100% !important;
      font-size: 9.5pt;
    }}
    .lx-lesson h1 {{ display: none; }}   /* chapter title shown in divider */
    .lx-lesson > p:first-of-type {{ margin-top: 0; }}

    /* ── Page-break helpers ── */
    .lx-lesson h2 {{ page-break-after: avoid; }}
    .lx-lesson h3 {{ page-break-after: avoid; }}
    .lx-lesson table {{ page-break-inside: avoid; }}
    .lx-lesson pre  {{ page-break-inside: avoid; }}
    .lx-lesson .note,
    .lx-lesson .warn,
    .lx-lesson .tip  {{ page-break-inside: avoid; }}
    .lx-lesson .cmd-ref {{ page-break-inside: avoid; }}

    /* ── Print: force dark background ── */
    @media print {{
      html, body {{ -webkit-print-color-adjust: exact; print-color-adjust: exact; }}
    }}
  </style>
</head>
<body>

<!-- ══ Cover page ══════════════════════════════════════ -->
<div class="cover">
  <div class="cover-label">Linux Text Processing</div>
  <h1>Learning <span>SED</span></h1>
  <p class="subtitle">A complete 12-chapter course — from first commands to real-world scripting</p>

  <div class="cover-toc">
    <h3>Contents</h3>
    <div class="toc-row"><span class="toc-num">01</span><span class="toc-title">Introduction to SED</span><span class="toc-tag">foundations</span></div>
    <div class="toc-row"><span class="toc-num">02</span><span class="toc-title">The Substitute Command</span><span class="toc-tag">core</span></div>
    <div class="toc-row"><span class="toc-num">03</span><span class="toc-title">Addresses and Line Selection</span><span class="toc-tag">core</span></div>
    <div class="toc-row"><span class="toc-num">04</span><span class="toc-title">Delete, Print and Quit</span><span class="toc-tag">core</span></div>
    <div class="toc-row"><span class="toc-num">05</span><span class="toc-title">Append, Insert and Change</span><span class="toc-tag">core</span></div>
    <div class="toc-row"><span class="toc-num">06</span><span class="toc-title">The Hold Space</span><span class="toc-tag">intermediate</span></div>
    <div class="toc-row"><span class="toc-num">07</span><span class="toc-title">Multi-line Commands</span><span class="toc-tag">intermediate</span></div>
    <div class="toc-row"><span class="toc-num">08</span><span class="toc-title">Branching and Labels</span><span class="toc-tag">intermediate</span></div>
    <div class="toc-row"><span class="toc-num">09</span><span class="toc-title">The y Command and Other Commands</span><span class="toc-tag">intermediate</span></div>
    <div class="toc-row"><span class="toc-num">10</span><span class="toc-title">Regular Expressions in SED</span><span class="toc-tag">regex</span></div>
    <div class="toc-row"><span class="toc-num">11</span><span class="toc-title">SED Scripts and Real-World Recipes</span><span class="toc-tag">applied</span></div>
    <div class="toc-row"><span class="toc-num">12</span><span class="toc-title">SED in Shell Scripts and Pipelines</span><span class="toc-tag">applied</span></div>
  </div>

  <div class="cover-footer">Generated for osztromok.com</div>
</div>

<!-- ══ Chapter content ═════════════════════════════════ -->
{combined_body}

</body>
</html>
"""

# ── Write to temp file ────────────────────────────────────────
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

print("Running Chrome headless (this may take a moment for 12 chapters)...")
result = subprocess.run(cmd, capture_output=True, text=True, timeout=180)

# ── Clean up temp file ────────────────────────────────────────
os.unlink(tmp_path)

# ── Report result ─────────────────────────────────────────────
if result.returncode == 0 and os.path.exists(OUTPUT_PDF):
    size_kb = os.path.getsize(OUTPUT_PDF) // 1024
    print(f"\nPDF generated successfully!")
    print(f"  Output : {OUTPUT_PDF}")
    print(f"  Size   : {size_kb} KB")
    print(f"  Chapters: {len(CHAPTERS)}")
else:
    print(f"\nChrome returned exit code {result.returncode}")
    if result.stderr:
        print("stderr:", result.stderr[:1000])
    sys.exit(1)
