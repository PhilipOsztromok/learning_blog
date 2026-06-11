"""
build_regex_pdf.py
Combines all 12 regex lesson HTML fragments into a single PDF.
Uses Chrome headless for rendering so the dark theme and syntax
colours are preserved exactly as they appear in the browser.
"""

import os
import subprocess
import sys
import tempfile

# ── Paths ─────────────────────────────────────────────────────────────────
SCRIPT_DIR  = os.path.dirname(os.path.abspath(__file__))
OUTPUT_PDF  = os.path.join(SCRIPT_DIR, "regex_course.pdf")

LESSONS = [
    ("01", "What Are Regular Expressions"),
    ("02", "Literals, the Dot, and Escaping"),
    ("03", "Anchors"),
    ("04", "Character Classes"),
    ("05", "Quantifiers"),
    ("06", "Groups, Alternation, and Back-references"),
    ("07", "grep and egrep in Depth"),
    ("08", "sed and Regex in Depth"),
    ("09", "Regex in awk"),
    ("10", "Bash [[ =~ ]] and BASH_REMATCH"),
    ("11", "PCRE: Lookahead, Lookbehind, and Advanced Features"),
    ("12", "Your Regex Toolkit: Patterns, Testing, and Real Pipelines"),
]

# ── Chrome candidates ─────────────────────────────────────────────────────
chrome_candidates = [
    r"C:\Program Files\Google\Chrome\Application\chrome.exe",
    r"C:\Program Files (x86)\Google\Chrome\Application\chrome.exe",
    r"C:\Users\emuba\AppData\Local\Google\Chrome\Application\chrome.exe",
    "/usr/bin/google-chrome",
    "/usr/bin/chromium-browser",
    "/usr/bin/chromium",
    "/Applications/Google Chrome.app/Contents/MacOS/Google Chrome",
]

def find_chrome():
    for path in chrome_candidates:
        if os.path.isfile(path):
            return path
    raise FileNotFoundError(
        "Chrome not found. Add your Chrome path to chrome_candidates."
    )

# ── CSS shared by the wrapper document ────────────────────────────────────
WRAPPER_CSS = """
  * { box-sizing: border-box; }
  body {
    background: #12141e;
    color: #e8e8e8;
    margin: 0;
    padding: 0;
    font-family: 'Georgia', serif;
  }

  /* ---- Cover page ---- */
  .cover {
    width: 100%;
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    background: linear-gradient(160deg, #0d1021 0%, #12141e 60%, #1a1f35 100%);
    page-break-after: always;
    text-align: center;
    padding: 4em 2em;
  }
  .cover-eyebrow {
    color: #4f8ef7;
    font-size: 0.95rem;
    text-transform: uppercase;
    letter-spacing: 0.18em;
    margin-bottom: 1em;
    font-family: 'Consolas', monospace;
  }
  .cover-title {
    font-size: 3rem;
    font-weight: bold;
    color: #e8e8e8;
    margin: 0 0 0.3em 0;
    line-height: 1.15;
  }
  .cover-title span { color: #4f8ef7; }
  .cover-subtitle {
    font-size: 1.15rem;
    color: #7a8aaa;
    margin: 0.5em 0 2.5em 0;
  }
  .cover-toc {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0.45em 2em;
    margin: 0 auto;
    max-width: 680px;
    text-align: left;
  }
  .cover-toc-item {
    display: flex;
    gap: 0.7em;
    align-items: baseline;
    font-size: 0.88rem;
    color: #c9d8ff;
  }
  .cover-toc-num {
    font-family: 'Consolas', monospace;
    color: #4f8ef7;
    min-width: 28px;
  }
  .cover-footer {
    margin-top: 3.5em;
    font-size: 0.8rem;
    color: #3a4a6a;
    font-family: 'Consolas', monospace;
    letter-spacing: 0.05em;
  }

  /* ---- Chapter divider ---- */
  .chapter-divider {
    width: 100%;
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    background: linear-gradient(160deg, #0d1021 0%, #12141e 70%);
    page-break-before: always;
    page-break-after: always;
    text-align: center;
    padding: 3em 2em;
  }
  .chapter-divider .ch-num {
    font-family: 'Consolas', monospace;
    font-size: 0.9rem;
    color: #4f8ef7;
    text-transform: uppercase;
    letter-spacing: 0.2em;
    margin-bottom: 0.6em;
  }
  .chapter-divider .ch-title {
    font-size: 2rem;
    color: #e8e8e8;
    margin: 0 0 0.5em 0;
    max-width: 640px;
    line-height: 1.25;
  }
  .chapter-divider .ch-sub {
    font-size: 0.9rem;
    color: #5a6a8a;
    font-family: 'Consolas', monospace;
  }
  .chapter-divider .ch-rule {
    width: 80px;
    height: 3px;
    background: #4f8ef7;
    border: none;
    margin: 1.5em auto 0 auto;
    border-radius: 2px;
  }

  /* ---- Lesson content ---- */
  .lesson-wrap {
    padding: 2.5em 3em 3em 3em;
    page-break-after: always;
  }
  /* suppress the h1 inside .lx-lesson — chapter divider provides it */
  .lesson-wrap .lx-lesson h1 { display: none; }
  .lesson-wrap .lx-lesson > p:first-of-type { margin-top: 0; }

  @media print {
    body { background: #12141e !important; }
  }
"""

# ── Build HTML ─────────────────────────────────────────────────────────────
def read_fragment(num):
    path = os.path.join(SCRIPT_DIR, f"regex_lesson_{num}.html")
    if not os.path.isfile(path):
        raise FileNotFoundError(f"Missing: {path}")
    with open(path, encoding="utf-8") as fh:
        return fh.read()

def build_html():
    parts = []

    # Document head
    parts.append(f"""<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Regular Expressions in Bash</title>
<style>{WRAPPER_CSS}</style>
</head>
<body>
""")

    # Cover page
    toc_items = "\n".join(
        f'<div class="cover-toc-item">'
        f'<span class="cover-toc-num">{num}</span>'
        f'<span>{title}</span>'
        f'</div>'
        for num, title in LESSONS
    )
    parts.append(f"""
<div class="cover">
  <div class="cover-eyebrow">A Bash scripting course</div>
  <h1 class="cover-title">Regular Expressions<br><span>in Bash</span></h1>
  <p class="cover-subtitle">12 chapters &mdash; grep &middot; sed &middot; awk &middot; [[ =~ ]] &middot; PCRE</p>
  <div class="cover-toc">
{toc_items}
  </div>
  <div class="cover-footer">BRE &nbsp;&bull;&nbsp; ERE &nbsp;&bull;&nbsp; PCRE &nbsp;&bull;&nbsp; BASH_REMATCH &nbsp;&bull;&nbsp; perl -ne</div>
</div>
""")

    # Chapters
    for num, title in LESSONS:
        fragment = read_fragment(num)

        # Chapter divider page
        parts.append(f"""
<div class="chapter-divider">
  <div class="ch-num">Chapter {int(num)}</div>
  <h2 class="ch-title">{title}</h2>
  <hr class="ch-rule">
</div>
""")

        # Lesson content
        parts.append(f"""
<div class="lesson-wrap">
{fragment}
</div>
""")

    parts.append("</body>\n</html>")
    return "".join(parts)


# ── Main ──────────────────────────────────────────────────────────────────
def main():
    print("Building regex_course.pdf ...")

    chrome = find_chrome()
    print(f"  Chrome: {chrome}")

    html = build_html()
    print(f"  HTML size: {len(html):,} bytes")

    # Write to a temp file
    with tempfile.NamedTemporaryFile(
        mode="w", suffix=".html", delete=False,
        encoding="utf-8", prefix="regex_course_"
    ) as tmp:
        tmp.write(html)
        tmp_path = tmp.name

    print(f"  Temp file: {tmp_path}")

    try:
        cmd = [
            chrome,
            "--headless=new",
            "--disable-gpu",
            "--no-sandbox",
            "--disable-dev-shm-usage",
            "--run-all-compositor-stages-before-draw",
            "--print-to-pdf-no-header",
            f"--print-to-pdf={OUTPUT_PDF}",
            "--no-pdf-header-footer",
            tmp_path,
        ]
        print("  Running Chrome headless ...")
        result = subprocess.run(
            cmd, capture_output=True, text=True, timeout=300
        )
        if result.returncode != 0:
            print(f"  Chrome stderr:\n{result.stderr[:2000]}")
            sys.exit(1)
    finally:
        os.unlink(tmp_path)

    if os.path.isfile(OUTPUT_PDF):
        size_kb = os.path.getsize(OUTPUT_PDF) // 1024
        print(f"\nDone: {OUTPUT_PDF}  ({size_kb:,} KB)")
    else:
        print("ERROR: PDF was not created.")
        sys.exit(1)


if __name__ == "__main__":
    main()
