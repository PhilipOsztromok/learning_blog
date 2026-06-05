"""
Generate japanese_lessons.pdf from 5 HTML lesson files.
Uses MS Gothic (system font) for full Japanese Unicode support.
"""

import re
import os
from bs4 import BeautifulSoup
from reportlab.lib.pagesizes import A4
from reportlab.lib.styles import ParagraphStyle
from reportlab.lib.units import mm
from reportlab.lib import colors
from reportlab.platypus import (
    SimpleDocTemplate, Paragraph, Spacer, Table, TableStyle,
    PageBreak, HRFlowable
)
from reportlab.pdfbase import pdfmetrics
from reportlab.pdfbase.ttfonts import TTFont

# ── Register Japanese font ──────────────────────────────────────────────────
FONT_PATH = r"C:\Windows\Fonts\msgothic.ttc"
pdfmetrics.registerFont(TTFont("JPFont", FONT_PATH, subfontIndex=0))
pdfmetrics.registerFont(TTFont("JPFontBold", r"C:\Windows\Fonts\msgothic.ttc", subfontIndex=2))

# ── Colour palette (light/printable) ───────────────────────────────────────
C_HEADING   = colors.HexColor("#1a3a6b")   # dark navy
C_SUBHEAD   = colors.HexColor("#4f8ef7")   # accent blue
C_PURPLE    = colors.HexColor("#7c5cbf")   # accent purple
C_TEXT      = colors.HexColor("#1a1a2e")   # near-black
C_MUTED     = colors.HexColor("#555577")   # muted grey-blue
C_BG_LIGHT  = colors.HexColor("#f0f4ff")   # very light blue tint
C_BG_TIP    = colors.HexColor("#f5f0ff")   # very light purple tint
C_BORDER    = colors.HexColor("#c0c8e8")   # light border
C_TABLE_HDR = colors.HexColor("#1e2235")   # dark table header
C_TABLE_ALT = colors.HexColor("#f7f9ff")   # alternating row

W, H = A4

# ── Styles ──────────────────────────────────────────────────────────────────
def make_styles():
    return {
        "cover_title": ParagraphStyle(
            "cover_title", fontName="JPFontBold", fontSize=28,
            textColor=C_HEADING, leading=36, spaceAfter=10, alignment=1
        ),
        "cover_sub": ParagraphStyle(
            "cover_sub", fontName="JPFont", fontSize=14,
            textColor=C_MUTED, leading=20, spaceAfter=4, alignment=1
        ),
        "lesson_title": ParagraphStyle(
            "lesson_title", fontName="JPFontBold", fontSize=20,
            textColor=C_HEADING, leading=26, spaceBefore=6, spaceAfter=6
        ),
        "section_head": ParagraphStyle(
            "section_head", fontName="JPFontBold", fontSize=13,
            textColor=C_SUBHEAD, leading=18, spaceBefore=14, spaceAfter=4
        ),
        "subsection_head": ParagraphStyle(
            "subsection_head", fontName="JPFontBold", fontSize=11,
            textColor=C_PURPLE, leading=16, spaceBefore=10, spaceAfter=3
        ),
        "body": ParagraphStyle(
            "body", fontName="JPFont", fontSize=9,
            textColor=C_TEXT, leading=14, spaceAfter=3
        ),
        "body_italic": ParagraphStyle(
            "body_italic", fontName="JPFont", fontSize=8.5,
            textColor=C_MUTED, leading=13, spaceAfter=3
        ),
        "jp_large": ParagraphStyle(
            "jp_large", fontName="JPFont", fontSize=11,
            textColor=C_TEXT, leading=16, spaceAfter=1
        ),
        "romaji": ParagraphStyle(
            "romaji", fontName="JPFont", fontSize=8,
            textColor=C_MUTED, leading=12, spaceAfter=1
        ),
        "english": ParagraphStyle(
            "english", fontName="JPFont", fontSize=8.5,
            textColor=colors.HexColor("#334466"), leading=12, spaceAfter=6
        ),
        "tip_title": ParagraphStyle(
            "tip_title", fontName="JPFontBold", fontSize=9,
            textColor=C_PURPLE, leading=13, spaceAfter=2
        ),
        "tip_body": ParagraphStyle(
            "tip_body", fontName="JPFont", fontSize=8.5,
            textColor=C_TEXT, leading=13, spaceAfter=0
        ),
        "toc_entry": ParagraphStyle(
            "toc_entry", fontName="JPFont", fontSize=11,
            textColor=C_TEXT, leading=18, spaceAfter=2
        ),
        "num_label": ParagraphStyle(
            "num_label", fontName="JPFontBold", fontSize=8,
            textColor=C_SUBHEAD, leading=12, spaceAfter=1
        ),
    }

S = make_styles()

# ── HTML parsing helpers ────────────────────────────────────────────────────
def clean(text):
    """Strip excess whitespace from text."""
    return re.sub(r'\s+', ' ', text or "").strip()

def text_of(tag):
    return clean(tag.get_text(" ", strip=True)) if tag else ""

def parse_html(filepath):
    with open(filepath, encoding="utf-8") as f:
        return BeautifulSoup(f.read(), "html.parser")

# ── Flowable builders ───────────────────────────────────────────────────────

def tip_box(title_text, body_text):
    """Render the tip box as a single-cell table with tinted background."""
    content = []
    if title_text:
        content.append(Paragraph(title_text, S["tip_title"]))
    if body_text:
        content.append(Paragraph(body_text, S["tip_body"]))
    t = Table([[content]], colWidths=[W - 60*mm])
    t.setStyle(TableStyle([
        ("BACKGROUND", (0,0), (-1,-1), C_BG_TIP),
        ("BOX",        (0,0), (-1,-1), 0.8, C_PURPLE),
        ("LEFTPADDING",  (0,0), (-1,-1), 8),
        ("RIGHTPADDING", (0,0), (-1,-1), 8),
        ("TOPPADDING",   (0,0), (-1,-1), 7),
        ("BOTTOMPADDING",(0,0), (-1,-1), 7),
    ]))
    return t

def info_box(body_text):
    content = [Paragraph(body_text, S["body"])]
    t = Table([[content]], colWidths=[W - 60*mm])
    t.setStyle(TableStyle([
        ("BACKGROUND", (0,0), (-1,-1), C_BG_LIGHT),
        ("BOX",        (0,0), (-1,-1), 0.8, C_SUBHEAD),
        ("LEFTPADDING",  (0,0), (-1,-1), 8),
        ("RIGHTPADDING", (0,0), (-1,-1), 8),
        ("TOPPADDING",   (0,0), (-1,-1), 7),
        ("BOTTOMPADDING",(0,0), (-1,-1), 7),
    ]))
    return t

def verb_table(headers, rows):
    """Build a styled data table."""
    col_count = len(headers)
    usable = W - 60*mm
    # Rough column widths
    if col_count == 8:   # godan/ichidan full table
        col_w = [18*mm, 28*mm, 26*mm, 30*mm, 24*mm, 20*mm, 20*mm, 30*mm]
    elif col_count == 6:  # irregular suru table
        col_w = [12*mm, 36*mm, 32*mm, 34*mm, 28*mm, 40*mm]
    elif col_count == 5:  # example sentences or pattern
        col_w = [18*mm, 40*mm, 30*mm, 28*mm, 50*mm]
    else:
        each = usable / col_count
        col_w = [each] * col_count

    # Clamp to usable width
    total = sum(col_w)
    if total > usable:
        scale = usable / total
        col_w = [c * scale for c in col_w]

    data = [[Paragraph(str(h), ParagraphStyle("th", fontName="JPFontBold", fontSize=7.5,
                        textColor=colors.white, leading=10)) for h in headers]]
    for i, row in enumerate(rows):
        data.append([Paragraph(str(c), ParagraphStyle(
            "td", fontName="JPFont", fontSize=8, textColor=C_TEXT, leading=11,
            wordWrap='CJK')) for c in row])

    t = Table(data, colWidths=col_w, repeatRows=1)
    style = [
        ("BACKGROUND", (0,0), (-1,0), C_TABLE_HDR),
        ("ROWBACKGROUNDS", (0,1), (-1,-1), [colors.white, C_TABLE_ALT]),
        ("GRID", (0,0), (-1,-1), 0.3, C_BORDER),
        ("TOPPADDING",   (0,0), (-1,-1), 4),
        ("BOTTOMPADDING",(0,0), (-1,-1), 4),
        ("LEFTPADDING",  (0,0), (-1,-1), 4),
        ("RIGHTPADDING", (0,0), (-1,-1), 4),
        ("VALIGN", (0,0), (-1,-1), "TOP"),
    ]
    t.setStyle(TableStyle(style))
    return t

def dialog_pair(cards):
    """Render dialog cards as a 2-column table."""
    if not cards:
        return None
    rows = []
    for i in range(0, len(cards), 2):
        left  = cards[i]
        right = cards[i+1] if i+1 < len(cards) else []
        rows.append([left, right])

    col_w = [(W - 60*mm) / 2 - 3*mm] * 2
    t = Table(rows, colWidths=col_w, hAlign="LEFT")
    t.setStyle(TableStyle([
        ("VALIGN", (0,0), (-1,-1), "TOP"),
        ("LEFTPADDING",  (0,0), (-1,-1), 0),
        ("RIGHTPADDING", (0,0), (-1,-1), 4),
        ("TOPPADDING",   (0,0), (-1,-1), 0),
        ("BOTTOMPADDING",(0,0), (-1,-1), 8),
    ]))
    return t

# ── Per-lesson parsers ──────────────────────────────────────────────────────

def lesson_to_flowables(filepath, lesson_title):
    soup = parse_html(filepath)
    flowables = []

    # Lesson title
    flowables.append(Paragraph(lesson_title, S["lesson_title"]))
    flowables.append(HRFlowable(width="100%", thickness=1.5, color=C_SUBHEAD, spaceAfter=8))

    # Remove style tags
    for tag in soup.find_all("style"):
        tag.decompose()

    root = soup.find(class_=re.compile(r'jp-lesson|hu-lesson|fr-lesson'))
    if not root:
        root = soup

    for el in root.children:
        if not hasattr(el, 'name') or el.name is None:
            continue
        tag = el.name

        # ── h2 section headings ──────────────────────────────────────────
        if tag == "h2":
            txt = clean(el.get_text())
            flowables.append(Spacer(1, 4*mm))
            flowables.append(Paragraph(txt, S["section_head"]))

        # ── h3 sub-headings ──────────────────────────────────────────────
        elif tag == "h3":
            txt = clean(el.get_text())
            flowables.append(Paragraph(txt, S["subsection_head"]))

        # ── Intro paragraph ──────────────────────────────────────────────
        elif tag == "p":
            txt = clean(el.get_text())
            if txt:
                flowables.append(Paragraph(txt, S["body_italic"]))

        # ── Pronunciation key strip ──────────────────────────────────────
        elif "pronun-key" in el.get("class", []):
            parts = [clean(s.get_text()) for s in el.find_all("span")]
            txt = "  ·  ".join(parts)
            flowables.append(info_box(txt))
            flowables.append(Spacer(1, 3*mm))

        # ── Info box ─────────────────────────────────────────────────────
        elif "info-box" in el.get("class", []):
            strong = el.find("strong")
            title = text_of(strong) if strong else ""
            if strong:
                strong.decompose()
            body = clean(el.get_text())
            if title:
                body = f"<b>{title}</b>  {body}"
            flowables.append(info_box(body))
            flowables.append(Spacer(1, 3*mm))

        # ── Rule box ─────────────────────────────────────────────────────
        elif "rule-box" in el.get("class", []):
            txt = clean(el.get_text())
            flowables.append(info_box(txt))
            flowables.append(Spacer(1, 3*mm))

        # ── Clock grid (hours reference) ──────────────────────────────────
        elif "clock-grid" in el.get("class", []):
            headers = ["Time", "Reading", "Romaji", "English"]
            rows = []
            for card in el.find_all(class_="clock-card"):
                jp   = text_of(card.find(class_="jp-time"))
                rom  = text_of(card.find(class_="romaji-time"))
                en   = text_of(card.find(class_="en-time"))
                rows.append([jp, jp, rom, en])
            # Simplified: just show jp + romaji + english
            simple_rows = []
            for card in el.find_all(class_="clock-card"):
                jp  = text_of(card.find(class_="jp-time"))
                rom = text_of(card.find(class_="romaji-time"))
                en  = text_of(card.find(class_="en-time"))
                simple_rows.append([jp, rom, en])
            col_w_c = [(W - 60*mm) / 3] * 3
            data = [[Paragraph(h, ParagraphStyle("th2", fontName="JPFontBold", fontSize=8,
                                textColor=colors.white, leading=10))
                     for h in ["Japanese", "Romaji", "English"]]]
            for r in simple_rows:
                data.append([Paragraph(c, ParagraphStyle("td2", fontName="JPFont",
                             fontSize=9, textColor=C_TEXT, leading=13, wordWrap='CJK'))
                             for c in r])
            t = Table(data, colWidths=col_w_c, repeatRows=1)
            t.setStyle(TableStyle([
                ("BACKGROUND", (0,0), (-1,0), C_TABLE_HDR),
                ("ROWBACKGROUNDS", (0,1), (-1,-1), [colors.white, C_TABLE_ALT]),
                ("GRID", (0,0), (-1,-1), 0.3, C_BORDER),
                ("TOPPADDING",   (0,0), (-1,-1), 4),
                ("BOTTOMPADDING",(0,0), (-1,-1), 4),
                ("LEFTPADDING",  (0,0), (-1,-1), 5),
                ("RIGHTPADDING", (0,0), (-1,-1), 5),
            ]))
            flowables.append(t)
            flowables.append(Spacer(1, 4*mm))

        # ── Forms grid ───────────────────────────────────────────────────
        elif "forms-grid" in el.get("class", []):
            cards_data = []
            for card in el.find_all(class_="form-card"):
                name  = text_of(card.find(class_="form-name"))
                rule  = text_of(card.find(class_="form-rule"))
                desc  = text_of(card.find(class_="form-desc"))
                cell = [
                    Paragraph(name, ParagraphStyle("fn", fontName="JPFontBold",
                              fontSize=8, textColor=C_PURPLE, leading=11)),
                    Paragraph(rule, ParagraphStyle("fr", fontName="JPFont",
                              fontSize=8, textColor=C_TEXT, leading=11)),
                    Paragraph(desc, ParagraphStyle("fd", fontName="JPFont",
                              fontSize=7.5, textColor=C_MUTED, leading=10)),
                ]
                cards_data.append(cell)
            # 2-column grid
            rows = []
            for i in range(0, len(cards_data), 2):
                left  = cards_data[i]
                right = cards_data[i+1] if i+1 < len(cards_data) else ["", "", ""]
                rows.append([left, right])
            cw = [(W - 60*mm) / 2 - 2*mm] * 2
            t = Table(rows, colWidths=cw)
            t.setStyle(TableStyle([
                ("VALIGN", (0,0), (-1,-1), "TOP"),
                ("BACKGROUND", (0,0), (-1,-1), C_BG_LIGHT),
                ("BOX", (0,0), (-1,-1), 0.5, C_BORDER),
                ("INNERGRID", (0,0), (-1,-1), 0.3, C_BORDER),
                ("LEFTPADDING",  (0,0), (-1,-1), 6),
                ("RIGHTPADDING", (0,0), (-1,-1), 6),
                ("TOPPADDING",   (0,0), (-1,-1), 5),
                ("BOTTOMPADDING",(0,0), (-1,-1), 5),
            ]))
            flowables.append(t)
            flowables.append(Spacer(1, 4*mm))

        # ── Pattern table ────────────────────────────────────────────────
        elif "pattern-table" in el.get("class", []):
            headers = [text_of(th) for th in el.find("thead").find_all("th")] if el.find("thead") else []
            rows = []
            tbody = el.find("tbody")
            if tbody:
                for tr in tbody.find_all("tr"):
                    rows.append([text_of(td) for td in tr.find_all("td")])
            if headers and rows:
                col_count = len(headers)
                usable = W - 60*mm
                col_w = [usable / col_count] * col_count
                data = [[Paragraph(h, ParagraphStyle("pth", fontName="JPFontBold",
                         fontSize=7.5, textColor=colors.white, leading=10)) for h in headers]]
                for row in rows:
                    data.append([Paragraph(c, ParagraphStyle("ptd", fontName="JPFont",
                                 fontSize=8, textColor=C_TEXT, leading=11, wordWrap='CJK'))
                                 for c in row])
                t = Table(data, colWidths=col_w, repeatRows=1)
                t.setStyle(TableStyle([
                    ("BACKGROUND", (0,0), (-1,0), C_TABLE_HDR),
                    ("ROWBACKGROUNDS", (0,1), (-1,-1), [colors.white, C_TABLE_ALT]),
                    ("GRID", (0,0), (-1,-1), 0.3, C_BORDER),
                    ("TOPPADDING",   (0,0), (-1,-1), 4),
                    ("BOTTOMPADDING",(0,0), (-1,-1), 4),
                    ("LEFTPADDING",  (0,0), (-1,-1), 4),
                    ("RIGHTPADDING", (0,0), (-1,-1), 4),
                    ("VALIGN", (0,0), (-1,-1), "TOP"),
                ]))
                flowables.append(t)
                flowables.append(Spacer(1, 4*mm))

        # ── Dialog grid ──────────────────────────────────────────────────
        elif "dialog-grid" in el.get("class", []):
            cards = []
            for card in el.find_all(class_="dialog-card"):
                cell_content = []
                h3 = card.find("h3")
                if h3:
                    cell_content.append(Paragraph(clean(h3.get_text()),
                        ParagraphStyle("dh", fontName="JPFontBold", fontSize=8,
                                       textColor=C_SUBHEAD, leading=11, spaceAfter=3)))
                for line in card.find_all(class_="dialog-line"):
                    spk = text_of(line.find(class_="speaker"))
                    jp  = text_of(line.find(class_=re.compile(r'^jp$|^hu$|^fr$')))
                    rom = text_of(line.find(class_="romaji"))
                    en  = text_of(line.find(class_="en"))
                    pron = text_of(line.find(class_="pronunciation"))
                    note = text_of(line.find(class_="note"))
                    if spk or jp:
                        cell_content.append(Paragraph(
                            f"<b>{spk}</b>  {jp}",
                            ParagraphStyle("djp", fontName="JPFont", fontSize=9,
                                           textColor=C_TEXT, leading=13, wordWrap='CJK')))
                    if rom:
                        cell_content.append(Paragraph(rom,
                            ParagraphStyle("drom", fontName="JPFont", fontSize=7.5,
                                           textColor=C_MUTED, leading=11)))
                    if pron:
                        cell_content.append(Paragraph(pron,
                            ParagraphStyle("dpron", fontName="JPFont", fontSize=7.5,
                                           textColor=C_MUTED, leading=11)))
                    if en:
                        cell_content.append(Paragraph(en,
                            ParagraphStyle("den", fontName="JPFont", fontSize=8,
                                           textColor=colors.HexColor("#334466"),
                                           leading=11, spaceAfter=3)))
                    if note:
                        cell_content.append(Paragraph(f"Note: {note}",
                            ParagraphStyle("dnote", fontName="JPFont", fontSize=7,
                                           textColor=C_MUTED, leading=10, spaceAfter=2)))
                cards.append(cell_content)

            # Render as 2-column table
            rows = []
            for i in range(0, len(cards), 2):
                left  = cards[i]
                right = cards[i+1] if i+1 < len(cards) else [Paragraph("", S["body"])]
                rows.append([left, right])
            if rows:
                cw = [(W - 60*mm) / 2 - 2*mm] * 2
                t = Table(rows, colWidths=cw)
                t.setStyle(TableStyle([
                    ("VALIGN", (0,0), (-1,-1), "TOP"),
                    ("BACKGROUND", (0,0), (-1,-1), C_BG_LIGHT),
                    ("BOX", (0,0), (-1,-1), 0.5, C_BORDER),
                    ("INNERGRID", (0,0), (-1,-1), 0.3, C_BORDER),
                    ("LEFTPADDING",  (0,0), (-1,-1), 6),
                    ("RIGHTPADDING", (0,0), (-1,-1), 6),
                    ("TOPPADDING",   (0,0), (-1,-1), 5),
                    ("BOTTOMPADDING",(0,0), (-1,-1), 5),
                ]))
                flowables.append(t)
                flowables.append(Spacer(1, 4*mm))

        # ── Vocabulary tables (vocab-table or verb-table-wrap) ───────────
        elif tag == "table" or (tag == "div" and "verb-table-wrap" in el.get("class", [])):
            tbl = el if tag == "table" else el.find("table")
            if not tbl:
                continue
            thead = tbl.find("thead")
            tbody = tbl.find("tbody")
            if not thead or not tbody:
                continue
            headers = [text_of(th) for th in thead.find_all("th")]
            rows = []
            for tr in tbody.find_all("tr"):
                rows.append([text_of(td) for td in tr.find_all("td")])
            if headers and rows:
                flowables.append(verb_table(headers, rows))
                flowables.append(Spacer(1, 4*mm))

        # ── Examples grid ────────────────────────────────────────────────
        elif "examples-grid" in el.get("class", []):
            ex_cards = el.find_all(class_="example-card")
            if not ex_cards:
                continue
            rows = []
            for i in range(0, len(ex_cards), 2):
                pair = []
                for card in [ex_cards[i], ex_cards[i+1] if i+1 < len(ex_cards) else None]:
                    if card is None:
                        pair.append([Paragraph("", S["body"])])
                        continue
                    num  = text_of(card.find(class_="ex-num"))
                    jp   = text_of(card.find(class_="ex-jp"))
                    rom  = text_of(card.find(class_="ex-romaji"))
                    en   = text_of(card.find(class_="ex-en"))
                    cell = []
                    if num:
                        cell.append(Paragraph(num, ParagraphStyle("enum",
                            fontName="JPFontBold", fontSize=7.5, textColor=C_SUBHEAD, leading=10)))
                    if jp:
                        cell.append(Paragraph(jp, ParagraphStyle("ejp",
                            fontName="JPFont", fontSize=9, textColor=C_TEXT,
                            leading=13, wordWrap='CJK')))
                    if rom:
                        cell.append(Paragraph(rom, ParagraphStyle("erom",
                            fontName="JPFont", fontSize=7.5, textColor=C_MUTED, leading=11)))
                    if en:
                        cell.append(Paragraph(en, ParagraphStyle("een",
                            fontName="JPFont", fontSize=8, textColor=colors.HexColor("#334466"),
                            leading=11, spaceAfter=2)))
                    pair.append(cell)
                rows.append(pair)
            if rows:
                cw = [(W - 60*mm) / 2 - 2*mm] * 2
                t = Table(rows, colWidths=cw)
                t.setStyle(TableStyle([
                    ("VALIGN", (0,0), (-1,-1), "TOP"),
                    ("BACKGROUND", (0,0), (-1,-1), C_BG_LIGHT),
                    ("BOX", (0,0), (-1,-1), 0.5, C_BORDER),
                    ("INNERGRID", (0,0), (-1,-1), 0.3, C_BORDER),
                    ("LEFTPADDING",  (0,0), (-1,-1), 6),
                    ("RIGHTPADDING", (0,0), (-1,-1), 6),
                    ("TOPPADDING",   (0,0), (-1,-1), 5),
                    ("BOTTOMPADDING",(0,0), (-1,-1), 5),
                ]))
                flowables.append(t)
                flowables.append(Spacer(1, 4*mm))

        # ── Verb conjugation cards (irregular verbs) ─────────────────────
        elif "verb-conj-grid" in el.get("class", []):
            conj_cards = el.find_all(class_="verb-conj-card")
            pair = []
            for card in conj_cards[:2]:
                header = card.find(class_="card-header")
                v_dict = text_of(header.find(class_="v-dict")) if header else ""
                v_rom  = text_of(header.find(class_="v-romaji")) if header else ""
                v_en   = text_of(header.find(class_="v-en")) if header else ""
                cell = [
                    Paragraph(f"{v_dict}  ({v_rom})  —  {v_en}",
                        ParagraphStyle("vh", fontName="JPFontBold", fontSize=10,
                                       textColor=C_PURPLE, leading=14, spaceAfter=4)),
                ]
                for row in card.find_all(class_="conj-row"):
                    label = text_of(row.find(class_="conj-label"))
                    jp    = text_of(row.find(class_="conj-jp"))
                    rom   = text_of(row.find(class_="conj-romaji"))
                    cell.append(Paragraph(
                        f"<b>{label}</b>  {jp}  ({rom})",
                        ParagraphStyle("vr", fontName="JPFont", fontSize=8.5,
                                       textColor=C_TEXT, leading=12, wordWrap='CJK')))
                pair.append(cell)
            if len(pair) == 2:
                cw = [(W - 60*mm) / 2 - 2*mm] * 2
                t = Table([pair], colWidths=cw)
                t.setStyle(TableStyle([
                    ("VALIGN", (0,0), (-1,-1), "TOP"),
                    ("BACKGROUND", (0,0), (-1,-1), C_BG_LIGHT),
                    ("BOX", (0,0), (-1,-1), 0.5, C_BORDER),
                    ("INNERGRID", (0,0), (-1,-1), 0.3, C_BORDER),
                    ("LEFTPADDING",  (0,0), (-1,-1), 8),
                    ("RIGHTPADDING", (0,0), (-1,-1), 8),
                    ("TOPPADDING",   (0,0), (-1,-1), 8),
                    ("BOTTOMPADDING",(0,0), (-1,-1), 8),
                ]))
                flowables.append(t)
                flowables.append(Spacer(1, 4*mm))

        # ── Tip box ──────────────────────────────────────────────────────
        elif "tip-box" in el.get("class", []):
            strong = el.find("strong")
            title  = text_of(strong) if strong else ""
            if strong:
                strong.decompose()
            body = clean(el.get_text())
            flowables.append(tip_box(f"💡 {title}" if title else "", body))
            flowables.append(Spacer(1, 4*mm))

    return flowables


# ── Cover page ───────────────────────────────────────────────────────────────
def cover_page():
    return [
        Spacer(1, 40*mm),
        Paragraph("🎌 Japanese Lessons", S["cover_title"]),
        Spacer(1, 6*mm),
        Paragraph("A Personal Study Guide", S["cover_sub"]),
        Spacer(1, 4*mm),
        HRFlowable(width="60%", thickness=1, color=C_SUBHEAD, hAlign="CENTER"),
        Spacer(1, 8*mm),
        Paragraph("Contents", ParagraphStyle("toc_h", fontName="JPFontBold",
                  fontSize=13, textColor=C_HEADING, leading=18, alignment=1)),
        Spacer(1, 4*mm),
        Paragraph("1.  Telling the Time — 時間をいう",        S["toc_entry"]),
        Paragraph("2.  Asking for Directions — 場所をたずねる", S["toc_entry"]),
        Paragraph("3.  Godan Verbs — 五段動詞",               S["toc_entry"]),
        Paragraph("4.  Ichidan Verbs — 一段動詞",             S["toc_entry"]),
        Paragraph("5.  Irregular Verbs — 不規則動詞",         S["toc_entry"]),
        PageBreak(),
    ]


# ── Main ─────────────────────────────────────────────────────────────────────
def main():
    base = r"C:\Users\emuba\OneDrive\My Learning\WebSites\website"
    output = os.path.join(base, "japanese_lessons.pdf")

    lessons = [
        ("japanese_lesson_telling_the_time.html", "Lesson 1 — Telling the Time　時間をいう"),
        ("japanese_lesson_directions.html",        "Lesson 2 — Asking for Directions　場所をたずねる"),
        ("japanese_lesson_godan_verbs.html",       "Lesson 3 — Godan Verbs　五段動詞"),
        ("japanese_lesson_ichidan_verbs.html",     "Lesson 4 — Ichidan Verbs　一段動詞"),
        ("japanese_lesson_irregular_verbs.html",   "Lesson 5 — Irregular Verbs　不規則動詞"),
    ]

    doc = SimpleDocTemplate(
        output,
        pagesize=A4,
        leftMargin=25*mm, rightMargin=25*mm,
        topMargin=20*mm, bottomMargin=20*mm,
        title="Japanese Lessons",
        author="osztromok.com",
    )

    story = cover_page()

    for i, (filename, title) in enumerate(lessons):
        filepath = os.path.join(base, filename)
        print(f"Processing: {filename}")
        story += lesson_to_flowables(filepath, title)
        if i < len(lessons) - 1:
            story.append(PageBreak())

    print(f"Building PDF -> {output}")
    doc.build(story)
    print("Done!")

if __name__ == "__main__":
    main()
