"""
Anime Vault – Web Development Guide Generator
Produces a book-style PDF tutorial for building the Anime Vault project.
"""

from reportlab.lib.pagesizes import A4
from reportlab.lib import colors
from reportlab.lib.units import cm
from reportlab.lib.styles import getSampleStyleSheet, ParagraphStyle
from reportlab.lib.enums import TA_LEFT, TA_CENTER, TA_JUSTIFY
from reportlab.platypus import (
    SimpleDocTemplate, Paragraph, Spacer, PageBreak, Table, TableStyle,
    HRFlowable, KeepTogether
)
from reportlab.platypus.tableofcontents import TableOfContents
from reportlab.lib.colors import HexColor
from reportlab.platypus import BaseDocTemplate, Frame, PageTemplate
from reportlab.platypus import Flowable
import os

# ── Colours ──────────────────────────────────────────────────────────────────
PINK      = HexColor('#e91e8c')
DARK_BG   = HexColor('#1a1a2e')
DARK_MID  = HexColor('#16213e')
ACCENT    = HexColor('#0f3460')
LIGHT_BG  = HexColor('#f8f9fa')
CODE_BG   = HexColor('#1e1e2e')
CODE_FG   = HexColor('#cdd6f4')
TIP_BG    = HexColor('#e8f5e9')
TIP_BORDER= HexColor('#4caf50')
WARN_BG   = HexColor('#fff3e0')
WARN_BORDER=HexColor('#ff9800')
MID_GREY  = HexColor('#555555')
LIGHT_GREY= HexColor('#dddddd')

OUTPUT_PATH = r"C:\Users\emuba\OneDrive\My Learning\WebSites\Anime_Vault_Guide.pdf"

# ── Document with custom page templates ──────────────────────────────────────
class BookDocTemplate(BaseDocTemplate):
    def __init__(self, filename, **kwargs):
        super().__init__(filename, **kwargs)
        self.chapter_num  = 0
        self.chapter_title = ''
        frame_cover = Frame(0, 0, A4[0], A4[1], leftPadding=0, rightPadding=0,
                            topPadding=0, bottomPadding=0)
        frame_body  = Frame(2*cm, 2.2*cm, A4[0]-4*cm, A4[1]-4*cm,
                            leftPadding=0, rightPadding=0,
                            topPadding=0, bottomPadding=0)
        self.addPageTemplates([
            PageTemplate(id='Cover', frames=frame_cover, onPage=self._cover_page),
            PageTemplate(id='TOC',   frames=frame_body,  onPage=self._toc_page),
            PageTemplate(id='Body',  frames=frame_body,  onPage=self._body_page),
        ])

    def _cover_page(self, canvas, doc):
        pass  # cover draws itself via flowables

    def _toc_page(self, canvas, doc):
        self._draw_header_footer(canvas, doc, 'Contents')

    def _body_page(self, canvas, doc):
        self._draw_header_footer(canvas, doc, self.chapter_title)

    def _draw_header_footer(self, canvas, doc, section):
        w, h = A4
        canvas.saveState()
        # Header bar
        canvas.setFillColor(DARK_BG)
        canvas.rect(0, h-1.5*cm, w, 1.5*cm, fill=1, stroke=0)
        canvas.setFillColor(PINK)
        canvas.setFont('Helvetica-Bold', 9)
        canvas.drawString(2*cm, h-1*cm, 'Anime')
        canvas.setFillColor(colors.white)
        canvas.setFont('Helvetica', 9)
        canvas.drawString(2*cm + 32, h-1*cm, 'Vault – Web Development Guide')
        canvas.setFillColor(colors.white)
        canvas.setFont('Helvetica', 8)
        canvas.drawRightString(w-2*cm, h-1*cm, section)
        # Footer
        canvas.setFillColor(DARK_BG)
        canvas.rect(0, 0, w, 1.8*cm, fill=1, stroke=0)
        canvas.setFillColor(colors.white)
        canvas.setFont('Helvetica', 8)
        canvas.drawCentredString(w/2, 0.7*cm, str(doc.page))
        canvas.restoreState()

    def afterFlowable(self, flowable):
        if hasattr(flowable, 'toc_level'):
            self.notify('TOCEntry', (flowable.toc_level, flowable.getPlainText(), self.page))
        if hasattr(flowable, 'chapter_title'):
            self.chapter_title = flowable.chapter_title


# ── Styles ────────────────────────────────────────────────────────────────────
def make_styles():
    base = getSampleStyleSheet()
    s = {}

    s['normal'] = ParagraphStyle('normal', fontName='Helvetica', fontSize=10,
                                 leading=16, textColor=HexColor('#333333'),
                                 alignment=TA_JUSTIFY, spaceAfter=8)

    s['h1'] = ParagraphStyle('h1', fontName='Helvetica-Bold', fontSize=22,
                              textColor=PINK, spaceBefore=18, spaceAfter=10,
                              leading=28)

    s['h2'] = ParagraphStyle('h2', fontName='Helvetica-Bold', fontSize=15,
                              textColor=DARK_BG, spaceBefore=14, spaceAfter=6,
                              leading=20, borderPad=0)

    s['h3'] = ParagraphStyle('h3', fontName='Helvetica-Bold', fontSize=12,
                              textColor=ACCENT, spaceBefore=10, spaceAfter=4,
                              leading=16)

    s['code'] = ParagraphStyle('code', fontName='Courier', fontSize=8.5,
                                leading=13, textColor=CODE_FG,
                                backColor=CODE_BG, leftIndent=8, rightIndent=8,
                                spaceBefore=4, spaceAfter=4,
                                borderPad=6)

    s['code_label'] = ParagraphStyle('code_label', fontName='Courier-Bold',
                                      fontSize=7.5, textColor=HexColor('#888888'),
                                      backColor=CODE_BG, leftIndent=8,
                                      spaceBefore=6, spaceAfter=0)

    s['bullet'] = ParagraphStyle('bullet', fontName='Helvetica', fontSize=10,
                                  leading=15, leftIndent=16, spaceAfter=4,
                                  textColor=HexColor('#333333'),
                                  bulletIndent=4)

    s['toc1'] = ParagraphStyle('toc1', fontName='Helvetica-Bold', fontSize=11,
                                textColor=DARK_BG, leading=16, spaceAfter=2)

    s['toc2'] = ParagraphStyle('toc2', fontName='Helvetica', fontSize=10,
                                textColor=MID_GREY, leading=15, leftIndent=16,
                                spaceAfter=1)

    s['caption'] = ParagraphStyle('caption', fontName='Helvetica-Oblique',
                                   fontSize=8.5, textColor=MID_GREY,
                                   alignment=TA_CENTER, spaceAfter=8)

    s['tip_title'] = ParagraphStyle('tip_title', fontName='Helvetica-Bold',
                                     fontSize=9.5, textColor=TIP_BORDER,
                                     spaceAfter=2)

    s['tip_body'] = ParagraphStyle('tip_body', fontName='Helvetica', fontSize=9.5,
                                    leading=14, textColor=HexColor('#333333'),
                                    alignment=TA_JUSTIFY)

    s['warn_title'] = ParagraphStyle('warn_title', fontName='Helvetica-Bold',
                                      fontSize=9.5, textColor=WARN_BORDER,
                                      spaceAfter=2)

    s['warn_body'] = ParagraphStyle('warn_body', fontName='Helvetica', fontSize=9.5,
                                     leading=14, textColor=HexColor('#333333'),
                                     alignment=TA_JUSTIFY)

    return s


# ── Helper flowables ──────────────────────────────────────────────────────────
def tip(s, title, body):
    inner = [Paragraph(f'&#x2714; {title}', s['tip_title']),
             Paragraph(body, s['tip_body'])]
    t = Table([[inner]], colWidths=[A4[0]-4*cm-1])
    t.setStyle(TableStyle([
        ('BACKGROUND', (0,0), (-1,-1), TIP_BG),
        ('LINEAFTER',  (0,0), (0,-1), 3, TIP_BORDER),
        ('LINEBEFORE', (0,0), (0,-1), 3, TIP_BORDER),
        ('TOPPADDING', (0,0), (-1,-1), 8),
        ('BOTTOMPADDING', (0,0), (-1,-1), 8),
        ('LEFTPADDING', (0,0), (-1,-1), 10),
        ('RIGHTPADDING', (0,0), (-1,-1), 10),
    ]))
    return [Spacer(1, 4), t, Spacer(1, 8)]


def warn(s, title, body):
    inner = [Paragraph(f'&#x26A0; {title}', s['warn_title']),
             Paragraph(body, s['warn_body'])]
    t = Table([[inner]], colWidths=[A4[0]-4*cm-1])
    t.setStyle(TableStyle([
        ('BACKGROUND', (0,0), (-1,-1), WARN_BG),
        ('LINEBEFORE', (0,0), (0,-1), 3, WARN_BORDER),
        ('TOPPADDING', (0,0), (-1,-1), 8),
        ('BOTTOMPADDING', (0,0), (-1,-1), 8),
        ('LEFTPADDING', (0,0), (-1,-1), 10),
        ('RIGHTPADDING', (0,0), (-1,-1), 10),
    ]))
    return [Spacer(1, 4), t, Spacer(1, 8)]


def code_block(s, label, code_text):
    lines = code_text.strip('\n').split('\n')
    flowables = []
    if label:
        flowables.append(Paragraph(label, s['code_label']))
    for line in lines:
        safe = (line.replace('&', '&amp;').replace('<', '&lt;')
                    .replace('>', '&gt;').replace(' ', '&nbsp;'))
        if not safe:
            safe = '&nbsp;'
        flowables.append(Paragraph(safe, s['code']))
    flowables.append(Spacer(1, 4))
    return flowables


def h1(s, text, chapter_num=None):
    label = f'Chapter {chapter_num}: {text}' if chapter_num else text
    p = Paragraph(label, s['h1'])
    p.toc_level = 0
    p.chapter_title = label
    return [HRFlowable(width='100%', thickness=2, color=PINK,
                       spaceAfter=4, spaceBefore=8), p]


def h2(s, text):
    p = Paragraph(text, s['h2'])
    p.toc_level = 1
    return [p]


def h3(s, text):
    return [Paragraph(text, s['h3'])]


def p(s, text):
    return [Paragraph(text, s['normal'])]


def ul(s, items):
    return [Paragraph(f'&#x2022;&nbsp;&nbsp;{item}', s['bullet'])
            for item in items]


def sp(n=1):
    return [Spacer(1, n * 6)]


# ── Cover page ────────────────────────────────────────────────────────────────
def cover_page(s):
    w, h = A4
    story = []

    # Full-page dark background table
    cover_content = [
        Spacer(1, 3.5*cm),
        Paragraph('&#x26E9;', ParagraphStyle('icon', fontName='Helvetica-Bold',
                  fontSize=60, textColor=PINK, alignment=TA_CENTER)),
        Spacer(1, 0.5*cm),
        Paragraph('Anime<font color="#e91e8c">Vault</font>',
                  ParagraphStyle('ctitle', fontName='Helvetica-Bold', fontSize=42,
                                 textColor=colors.white, alignment=TA_CENTER)),
        Spacer(1, 0.3*cm),
        Paragraph('Building a Full-Stack Web Application',
                  ParagraphStyle('csub', fontName='Helvetica', fontSize=18,
                                 textColor=HexColor('#aaaacc'), alignment=TA_CENTER)),
        Spacer(1, 0.2*cm),
        Paragraph('A Step-by-Step Guide for Web Development Students',
                  ParagraphStyle('csub2', fontName='Helvetica-Oblique', fontSize=13,
                                 textColor=HexColor('#888899'), alignment=TA_CENTER)),
        Spacer(1, 2.5*cm),
        HRFlowable(width='60%', thickness=1, color=PINK,
                   hAlign='CENTER', spaceAfter=20),
        Paragraph('Covers: PHP &bull; MySQL &bull; Apache &bull; HTML/CSS/JavaScript',
                  ParagraphStyle('ctech', fontName='Helvetica', fontSize=11,
                                 textColor=HexColor('#aaaacc'), alignment=TA_CENTER)),
        Spacer(1, 0.4*cm),
        Paragraph('Authentication &bull; Admin Panels &bull; REST APIs &bull; Security',
                  ParagraphStyle('ctech2', fontName='Helvetica', fontSize=11,
                                 textColor=HexColor('#aaaacc'), alignment=TA_CENTER)),
        Spacer(1, 3*cm),
        Paragraph('osztromok.com &nbsp;|&nbsp; 2026',
                  ParagraphStyle('cfooter', fontName='Helvetica', fontSize=10,
                                 textColor=HexColor('#666677'), alignment=TA_CENTER)),
    ]

    bg_table = Table([[cover_content]], colWidths=[w], rowHeights=[h])
    bg_table.setStyle(TableStyle([
        ('BACKGROUND', (0,0), (-1,-1), DARK_BG),
        ('TOPPADDING',    (0,0), (-1,-1), 0),
        ('BOTTOMPADDING', (0,0), (-1,-1), 0),
        ('LEFTPADDING',   (0,0), (-1,-1), 0),
        ('RIGHTPADDING',  (0,0), (-1,-1), 0),
        ('VALIGN', (0,0), (-1,-1), 'TOP'),
    ]))
    story.append(bg_table)
    story.append(PageBreak())
    return story


# ── Table of Contents ─────────────────────────────────────────────────────────
def toc_page(s):
    toc = TableOfContents()
    toc.levelStyles = [s['toc1'], s['toc2']]
    story = []
    story.append(Paragraph('Contents', ParagraphStyle(
        'toc_heading', fontName='Helvetica-Bold', fontSize=20,
        textColor=DARK_BG, spaceAfter=16)))
    story.append(HRFlowable(width='100%', thickness=1, color=PINK, spaceAfter=12))
    story.append(toc)
    story.append(PageBreak())
    return story, toc


# ═══════════════════════════════════════════════════════════════════════════════
# CHAPTER CONTENT
# ═══════════════════════════════════════════════════════════════════════════════

def chapter_intro(s):
    story = []
    story += h1(s, 'Introduction & Project Overview', 1)
    story += p(s, 'Welcome to this step-by-step guide to building <b>Anime Vault</b> — a '
                   'full-stack web application for browsing, reviewing, and managing anime '
                   'shows. This project is intentionally comprehensive: it covers a realistic '
                   'set of technologies that professional web developers use every day.')
    story += p(s, 'By the time you have completed this guide you will have built a working '
                   'application with user registration and login, a public-facing browsing '
                   'interface, a secure admin panel, and integration with a third-party API. '
                   'More importantly, you will understand <i>why</i> each decision was made.')

    story += h2(s, '1.1  What We Are Building')
    story += p(s, 'Anime Vault has two distinct parts:')
    story += ul(s, [
        '<b>The public site</b> — visitors can browse anime, read reviews, search by genre '
        'or rating, and (once registered) maintain a personal watchlist.',
        '<b>The admin panel</b> — administrators can add, edit, and delete anime entries, '
        'manage studios and genres, and review user accounts.',
    ])
    story += p(s, 'The technology stack is deliberately straightforward — no frameworks, '
                   'no build tools — so that every line of code is visible and understandable.')

    story += h2(s, '1.2  Technology Stack')
    tech_data = [
        ['Layer', 'Technology', 'Why We Use It'],
        ['Web Server', 'Apache 2.4', 'Industry-standard server with .htaccess support'],
        ['Back End', 'PHP 8+', 'Widely deployed, readable syntax, runs on most hosting'],
        ['Database', 'MySQL / MariaDB', 'Relational DB ideal for structured media data'],
        ['Front End', 'HTML5 + CSS3 + JS', 'Core web skills that underpin every framework'],
        ['API', 'Jikan (MyAnimeList)', 'Free REST API — no key required for basic use'],
        ['Version Control', 'Git + GitHub', 'Industry-standard code management'],
    ]
    t = Table(tech_data, colWidths=[3.5*cm, 4*cm, None])
    t.setStyle(TableStyle([
        ('BACKGROUND', (0,0), (-1,0), DARK_BG),
        ('TEXTCOLOR',  (0,0), (-1,0), colors.white),
        ('FONTNAME',   (0,0), (-1,0), 'Helvetica-Bold'),
        ('FONTSIZE',   (0,0), (-1,-1), 9),
        ('ROWBACKGROUNDS', (0,1), (-1,-1), [LIGHT_BG, colors.white]),
        ('GRID', (0,0), (-1,-1), 0.5, LIGHT_GREY),
        ('TOPPADDING',    (0,0), (-1,-1), 5),
        ('BOTTOMPADDING', (0,0), (-1,-1), 5),
        ('LEFTPADDING',   (0,0), (-1,-1), 6),
    ]))
    story.append(t)
    story += sp(2)

    story += h2(s, '1.3  How to Use This Guide')
    story += p(s, 'Each chapter builds on the last. Code is shown in full where it matters '
                   'and abbreviated where the pattern is already established. Every section '
                   'ends with a brief explanation of the <i>mistake to avoid</i> — common '
                   'pitfalls that trip up beginners.')
    story += tip(s, 'Read the "Why" sections',
                    'It is tempting to copy code and move on. Resist that urge. The '
                    'explanations of <i>why</i> things are done a certain way are the most '
                    'valuable part of this guide.')
    story.append(PageBreak())
    return story


def chapter_environment(s):
    story = []
    story += h1(s, 'Setting Up Your Environment', 2)
    story += p(s, 'Before writing a single line of PHP, we need a working local development '
                   'environment. This chapter walks through everything you need.')

    story += h2(s, '2.1  What You Need')
    story += ul(s, [
        '<b>A local web server</b> running Apache and PHP — XAMPP (Windows/Mac) or LAMP (Linux)',
        '<b>MySQL</b> (included with XAMPP) or MariaDB',
        '<b>A code editor</b> — Visual Studio Code is recommended',
        '<b>Git</b> — for version control',
        '<b>A browser</b> with developer tools — Firefox or Chrome',
    ])

    story += h2(s, '2.2  Installing XAMPP (Windows)')
    story += p(s, 'XAMPP bundles Apache, PHP, and MySQL into a single installer, making it '
                   'the easiest way to get started on Windows.')
    story += ul(s, [
        'Download XAMPP from <b>apachefriends.org</b> — choose the version that includes PHP 8.1 or later.',
        'Run the installer and accept the defaults. Install to <b>C:\\xampp</b>.',
        'Open the XAMPP Control Panel and click <b>Start</b> next to Apache and MySQL.',
        'Open your browser and visit <b>http://localhost</b> — you should see the XAMPP welcome page.',
    ])
    story += tip(s, 'PHP Version Matters',
                    'This project uses PHP 8.0+ features including the <b>match</b> expression '
                    '(Chapter 5). If you install an older PHP version the code will fail with '
                    'a parse error. Always check your PHP version with: '
                    '<font face="Courier">php -v</font>')

    story += h2(s, '2.3  Project Folder Structure')
    story += p(s, 'Create your project inside Apache\'s document root. On XAMPP this is '
                   '<b>C:\\xampp\\htdocs\\</b>. Create a folder called <b>anime</b>:')
    story += code_block(s, 'Expected folder structure', '''
anime/
├── admin/              ← admin-only PHP pages
│   ├── index.php
│   ├── anime.php
│   ├── edit_anime.php
│   ├── delete_anime.php
│   ├── sidebar.php
│   ├── genres.php
│   ├── people.php
│   ├── studios.php
│   ├── users.php
│   └── reviews.php
├── includes/           ← shared PHP helpers (not web-accessible directly)
│   ├── auth.php
│   ├── db.php
│   ├── header.php
│   └── footer.php
├── styles/
│   └── main.css
├── js/
│   └── main.js
├── index.php
├── browse.php
├── show.php
├── login.php
├── register.php
├── logout.php
├── watchlist.php
├── .htaccess
└── schema.sql
''')
    story += warn(s, 'Common Beginner Mistake — Wrong Directories',
                     'A very common mistake when receiving AI-generated or downloaded code is '
                     'placing all files in the same folder regardless of where they belong. '
                     'Always read the file path comment at the top of each PHP file '
                     '(e.g. <font face="Courier">// File: /anime/admin/edit_anime.php</font>) '
                     'and place it in the correct subdirectory.')

    story += h2(s, '2.4  Setting Up the Database')
    story += p(s, 'Open your browser and go to <b>http://localhost/phpmyadmin</b>. '
                   'Create a new database called <b>anime_vault</b> with the '
                   '<b>utf8mb4_unicode_ci</b> collation. Then import your '
                   '<b>schema.sql</b> file to create all the tables.')
    story += p(s, 'Alternatively, use the command line:')
    story += code_block(s, 'Create the database from the command line', '''
mysql -u root -p < schema.sql
''')
    story.append(PageBreak())
    return story


def chapter_database(s):
    story = []
    story += h1(s, 'Designing the Database', 3)
    story += p(s, 'A well-designed database is the foundation of any data-driven web '
                   'application. Getting the structure right at the start saves enormous '
                   'amounts of refactoring later.')

    story += h2(s, '3.1  Relational Database Concepts')
    story += p(s, 'MySQL is a <b>relational database</b>, which means data is stored in '
                   '<b>tables</b> (like spreadsheet tabs) and tables can be <b>linked</b> '
                   'to each other via keys. The three core concepts are:')
    story += ul(s, [
        '<b>Primary Key</b> — a unique identifier for each row, usually an auto-incrementing integer.',
        '<b>Foreign Key</b> — a column in one table that references the primary key of another table, '
        'creating a relationship between them.',
        '<b>Normalisation</b> — the process of organising data to reduce duplication. Each piece of '
        'information should live in exactly one place.',
    ])

    story += h2(s, '3.2  The Anime Vault Schema')
    story += p(s, 'Our database has eight tables. Here is a summary of each and why it exists:')
    table_data = [
        ['Table', 'Purpose', 'Key Relationships'],
        ['users', 'Stores registered accounts', 'Referenced by anime, reviews, watchlist'],
        ['anime', 'Core anime data (title, synopsis, etc.)', 'Belongs to studios; has genres via anime_genres'],
        ['studios', 'Animation studios', 'One studio produces many anime'],
        ['genres', 'Genre tags (Action, Romance, etc.)', 'Many-to-many with anime'],
        ['anime_genres', 'Links anime to genres', 'Junction table for the M:N relationship'],
        ['people', 'Voice actors, directors, etc.', 'Linked to anime via cast_members and staff'],
        ['reviews', 'User reviews with ratings', 'Belongs to both a user and an anime'],
        ['watchlist', 'Per-user anime tracking', 'Belongs to user and anime'],
    ]
    t = Table(table_data, colWidths=[3*cm, 6*cm, None])
    t.setStyle(TableStyle([
        ('BACKGROUND', (0,0), (-1,0), DARK_BG),
        ('TEXTCOLOR',  (0,0), (-1,0), colors.white),
        ('FONTNAME',   (0,0), (-1,0), 'Helvetica-Bold'),
        ('FONTSIZE',   (0,0), (-1,-1), 9),
        ('ROWBACKGROUNDS', (0,1), (-1,-1), [LIGHT_BG, colors.white]),
        ('GRID', (0,0), (-1,-1), 0.5, LIGHT_GREY),
        ('TOPPADDING',    (0,0), (-1,-1), 5),
        ('BOTTOMPADDING', (0,0), (-1,-1), 5),
        ('LEFTPADDING',   (0,0), (-1,-1), 6),
        ('VALIGN', (0,0), (-1,-1), 'TOP'),
    ]))
    story.append(t)
    story += sp(2)

    story += h2(s, '3.3  Many-to-Many Relationships')
    story += p(s, 'An anime can belong to multiple genres (Action AND Adventure), and a '
                   'genre applies to multiple anime. This is a <b>many-to-many</b> relationship '
                   'and cannot be stored in either table directly. We solve it with a '
                   '<b>junction table</b> called <b>anime_genres</b>:')
    story += code_block(s, 'schema.sql — junction table', '''
CREATE TABLE anime_genres (
    anime_id  INT NOT NULL,
    genre_id  INT NOT NULL,
    PRIMARY KEY (anime_id, genre_id),
    FOREIGN KEY (anime_id) REFERENCES anime(id) ON DELETE CASCADE,
    FOREIGN KEY (genre_id) REFERENCES genres(id) ON DELETE CASCADE
) ENGINE=InnoDB;
''')
    story += p(s, 'The composite <b>PRIMARY KEY</b> on both columns ensures the same '
                   'genre cannot be assigned to the same anime twice. The '
                   '<b>ON DELETE CASCADE</b> means if an anime is deleted, its genre '
                   'links are automatically cleaned up too.')

    story += h2(s, '3.4  Choosing Data Types Carefully')
    story += p(s, 'Choosing the right data type matters for both correctness and performance. '
                   'Some examples from our schema:')
    story += ul(s, [
        "<b>VARCHAR(n)</b> — for text with a known maximum length. Always set n generously "
        "enough for real-world data. For example, age_rating was initially VARCHAR(20) but "
        "the Jikan API returns 'R - 17+ (violence &amp; profanity)' which is 30 characters — "
        "it was increased to VARCHAR(50).",
        '<b>TINYINT(1)</b> — used for boolean flags like <b>is_active</b> and '
        '<b>contains_spoilers</b>.',
        "<b>DECIMAL(3,1)</b> — for the anime rating, allowing values like 8.5. Using FLOAT "
        "would introduce rounding errors.",
        '<b>YEAR</b> — MySQL\'s special type for year values; only accepts 1901–2155.',
        "<b>ENUM</b> — constrains a column to a fixed set of values. Our anime status is "
        "ENUM('Airing','Completed','Upcoming','Hiatus') — MySQL rejects any other value.",
    ])
    story += warn(s, 'Always check what external APIs actually return',
                     'Column sizes should be based on the real data you will store, not '
                     'just what you expect to enter manually. Before defining a VARCHAR length '
                     'for a field populated from an API, make a test call to the API and '
                     'inspect the actual response values.')
    story.append(PageBreak())
    return story


def chapter_foundation(s):
    story = []
    story += h1(s, 'Building the Foundation', 4)
    story += p(s, 'Before building any visible pages, we lay the groundwork: the database '
                   'connection, session management, authentication helpers, and shared '
                   'header/footer templates.')

    story += h2(s, '4.1  The Database Connection (includes/db.php)')
    story += p(s, 'All database access goes through a single file that creates one '
                   '<b>PDO</b> (PHP Data Objects) connection and reuses it throughout the '
                   'request. PDO is preferred over the older mysqli extension because it '
                   'supports multiple database engines and has a cleaner API.')
    story += code_block(s, 'includes/db.php', '''
<?php
define('DB_HOST', 'localhost');
define('DB_NAME', 'anime_vault');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = "mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    }
    return $pdo;
}
''')
    story += p(s, 'Key decisions here:')
    story += ul(s, [
        '<b>ERRMODE_EXCEPTION</b> — any SQL error throws a PHP exception rather than '
        'silently failing. This makes bugs visible during development.',
        '<b>FETCH_ASSOC</b> — rows are returned as associative arrays '
        '(e.g. <font face="Courier">$row[\'title\']</font>) rather than indexed arrays.',
        '<b>static $pdo</b> — the connection is created once per request and reused. '
        'Creating a new connection for every query would be slow.',
        '<b>EMULATE_PREPARES = false</b> — uses true prepared statements rather than '
        'emulated ones, giving stronger protection against SQL injection.',
    ])
    story += warn(s, 'Never commit credentials to Git',
                     'Your database password is in db.php. Add this file to .gitignore, '
                     'or at minimum never push to a public repository with real credentials. '
                     'A better pattern is to store credentials in environment variables or '
                     'a config file that is excluded from version control.')

    story += h2(s, '4.2  Authentication (includes/auth.php)')
    story += p(s, 'The auth file handles sessions, login, logout, registration, and CSRF '
                   'protection. It is included at the top of every page.')

    story += h3(s, 'Sessions')
    story += p(s, 'PHP sessions work by assigning each visitor a unique ID stored in a '
                   'cookie. On each request, PHP loads the session data for that ID from '
                   'the server. We configure sessions with security options:')
    story += code_block(s, 'Session start with security settings', '''
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_httponly' => true,   // JS cannot read the cookie
        'cookie_samesite' => 'Lax',  // Protection against CSRF
        'cookie_secure'   => isset($_SERVER['HTTPS']), // HTTPS only in production
    ]);
}
''')
    story += tip(s, 'Use SameSite=Lax, not Strict',
                    'SameSite=Strict prevents the session cookie being sent when a user '
                    'navigates to your site from an external link or redirect — this causes '
                    'CSRF tokens to mismatch on the login form. Lax provides strong '
                    'protection while allowing normal navigation.')

    story += h3(s, 'CSRF Protection')
    story += p(s, '<b>Cross-Site Request Forgery (CSRF)</b> is an attack where a malicious '
                   'website tricks a logged-in user\'s browser into submitting a form to your '
                   'site without their knowledge. We prevent it with a random token:')
    story += code_block(s, 'CSRF token generation and verification', '''
function csrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrf(): void {
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals(csrfToken(), $token)) {
        http_response_code(403);
        die(json_encode(['error' => 'Invalid CSRF token']));
    }
}
''')
    story += p(s, 'Every form includes a hidden field with the token. When the form is '
                   'submitted, the server checks the submitted token matches the one in the '
                   'session. A cross-site attacker cannot read the token, so their forged '
                   'request will always fail.')

    story += h3(s, 'Password Hashing')
    story += p(s, 'Passwords are <b>never stored in plain text</b>. We use PHP\'s '
                   '<b>password_hash()</b> with the bcrypt algorithm:')
    story += code_block(s, 'Secure password storage and verification', '''
// When registering — hash before storing:
$hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

// When logging in — verify against the stored hash:
if (!password_verify($submittedPassword, $storedHash)) {
    return ['ok' => false, 'error' => 'Incorrect password'];
}
''')
    story += p(s, 'Bcrypt is a deliberately slow algorithm — the \'cost\' parameter '
                   'controls how slow. This makes brute-force attacks impractical even if '
                   'someone steals the database.')
    story.append(PageBreak())
    return story


def chapter_public(s):
    story = []
    story += h1(s, 'The Public-Facing Pages', 5)
    story += p(s, 'The public site is what visitors see before (and after) logging in. '
                   'It consists of the home page, browse/search, individual show pages, '
                   'login/register, and the watchlist.')

    story += h2(s, '5.1  Shared Header and Footer')
    story += p(s, 'Rather than repeating the navigation bar and footer HTML on every page, '
                   'we extract them into <b>includes/header.php</b> and '
                   '<b>includes/footer.php</b>. Each page then simply includes them:')
    story += code_block(s, 'Pattern used at the top of every page', '''
<?php
require_once __DIR__ . '/includes/auth.php';
// ... page logic here ...
$pageTitle = 'Browse Anime';
include __DIR__ . '/includes/header.php';
?>
<!-- page HTML here -->
<?php include __DIR__ . '/includes/footer.php'; ?>
''')
    story += p(s, 'The <b>require_once</b> vs <b>include</b> distinction is important: '
                   '<b>require_once</b> causes a fatal error if the file is missing '
                   '(appropriate for critical dependencies like auth.php), while '
                   '<b>include</b> only produces a warning (acceptable for templates).')

    story += h2(s, '5.2  The Browse Page and SQL Queries')
    story += p(s, 'The browse page demonstrates a key pattern: building a dynamic SQL '
                   'query based on user input (filters, search, sorting) while protecting '
                   'against SQL injection.')
    story += code_block(s, 'browse.php — safe dynamic query building', '''
$conditions = ['1=1'];  // always true — makes appending easier
$params = [];

if (!empty($_GET['q'])) {
    $conditions[] = 'MATCH(title, synopsis) AGAINST(? IN BOOLEAN MODE)';
    $params[] = $_GET['q'] . '*';
}

if (!empty($_GET['genre'])) {
    $conditions[] = 'EXISTS (SELECT 1 FROM anime_genres ag
                    JOIN genres g ON ag.genre_id = g.id
                    WHERE ag.anime_id = a.id AND g.slug = ?)';
    $params[] = $_GET['genre'];
}

$sql = 'SELECT * FROM anime WHERE ' . implode(' AND ', $conditions);
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
''')
    story += warn(s, 'Never interpolate user input directly into SQL',
                     'Writing <font face="Courier">$sql = "SELECT * FROM anime WHERE title = \'".$_GET[\'q\']."\'"</font> '
                     'is a <b>SQL injection vulnerability</b>. An attacker can input '
                     '<font face="Courier">\' OR 1=1; DROP TABLE anime; --</font> and destroy '
                     'your database. Always use <b>prepared statements</b> with <b>?</b> placeholders '
                     'and pass user input as bound parameters.')

    story += h2(s, '5.3  Login and Registration')
    story += p(s, 'The login flow demonstrates the <b>Post/Redirect/Get</b> pattern — '
                   'a web development best practice that prevents forms from being '
                   'resubmitted when the user refreshes the page:')
    story += ul(s, [
        'User visits <b>GET</b> /login.php — form is shown with a fresh CSRF token.',
        'User submits — <b>POST</b> /login.php — credentials are verified.',
        'On success: <b>header(\'Location: /anime/\')</b> — redirect to home page.',
        'On failure: re-render the form with an error message.',
    ])
    story += p(s, 'After a successful login, we call <b>session_regenerate_id(true)</b> '
                   'to assign a new session ID. This prevents <b>session fixation attacks</b> '
                   'where an attacker pre-sets a victim\'s session ID before they log in.')

    story += h2(s, '5.4  Integrating the Jikan API')
    story += p(s, 'The admin edit form uses the free <b>Jikan API</b> (jikan.moe) to '
                   'auto-populate anime details from MyAnimeList. The API call is made '
                   'server-side from PHP using <b>file_get_contents()</b> with a stream '
                   'context, and the result is returned to the browser as JSON via AJAX:')
    story += code_block(s, 'PHP — fetching from Jikan API', '''
$apiUrl = "https://api.jikan.moe/v4/anime?q=" . urlencode($title) . "&limit=1";
$ctx = stream_context_create(['http' => [
    'timeout' => 10,
    'header'  => "User-Agent: AnimeVault/1.0\r\n",
]]);
$raw = @file_get_contents($apiUrl, false, $ctx);
$data = json_decode($raw, true);
''')
    story += tip(s, 'Rate Limiting',
                    'Free APIs like Jikan have rate limits. Jikan allows approximately '
                    '3 requests per second. If you make too many requests too quickly, '
                    'you will receive a 429 (Too Many Requests) response. Always check '
                    'the API documentation for limits before building against it.')
    story.append(PageBreak())
    return story


def chapter_admin(s):
    story = []
    story += h1(s, 'The Admin Panel', 6)
    story += p(s, 'The admin panel is accessible only to users with the role <b>admin</b>. '
                   'It provides a dashboard and full CRUD (Create, Read, Update, Delete) '
                   'management for all content.')

    story += h2(s, '6.1  Protecting Admin Pages')
    story += p(s, 'Every admin page begins with these two lines:')
    story += code_block(s, 'admin/index.php — top of every admin file', '''
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();
''')
    story += p(s, 'The <b>requireAdmin()</b> function checks that the user is both logged '
                   'in AND has the admin role. If not, they are redirected to the login page '
                   '(if not logged in) or shown a 403 Forbidden page (if logged in but not '
                   'an admin). This is called <b>authorisation</b> — checking what a '
                   'confirmed user is <i>allowed</i> to do, as distinct from '
                   '<b>authentication</b> which just confirms who they are.')

    story += h2(s, '6.2  The Admin Folder Structure')
    story += p(s, 'All admin pages live inside the <b>admin/</b> subdirectory. This is '
                   'important for two reasons:')
    story += ul(s, [
        'It allows you to apply .htaccess rules to the whole admin area at once '
        '(e.g. restricting access by IP address on a production server).',
        'It keeps admin code cleanly separated from public code, making the project '
        'easier to navigate and reason about.',
    ])
    story += p(s, 'The shared <b>admin/sidebar.php</b> is included on every admin page '
                   'to provide consistent navigation, just as header.php does for the '
                   'public site.')

    story += h2(s, '6.3  Add/Edit Anime (edit_anime.php)')
    story += p(s, 'The edit form handles both <b>adding</b> and <b>editing</b> anime with '
                   'a single file. The logic branches based on whether an <b>id</b> parameter '
                   'is present in the URL:')
    story += code_block(s, 'admin/edit_anime.php — add vs edit logic', '''
$animeId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$isEdit  = $animeId > 0;

if ($isEdit) {
    // Load existing record for pre-filling the form
    $stmt = $pdo->prepare("SELECT * FROM anime WHERE id = ?");
    $stmt->execute([$animeId]);
    $show = $stmt->fetch();
}
''')
    story += p(s, 'Notice the <b>(int)</b> cast on <font face="Courier">$_GET[\'id\']</font>. '
                   'This is a simple but effective security measure: even if an attacker '
                   'passes a malicious string in the URL, casting to integer reduces it to '
                   'a safe number (or 0).')

    story += h2(s, '6.4  Slug Generation')
    story += p(s, 'Rather than using numeric IDs in public URLs '
                   '(e.g. <font face="Courier">/show.php?id=42</font>), we generate '
                   '<b>slugs</b> — human-readable URL segments like '
                   '<font face="Courier">/show.php?slug=fullmetal-alchemist-brotherhood</font>. '
                   'This is better for users and for search engines:')
    story += code_block(s, 'Slug generation with uniqueness check', '''
$baseSlug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $title));
$baseSlug = trim($baseSlug, '-');
$slug = $baseSlug;
$n = 1;

while (true) {
    $check = $pdo->prepare("SELECT id FROM anime WHERE slug = ? AND id != ?");
    $check->execute([$slug, $animeId]);
    if (!$check->fetch()) break;  // slug is unique, we can use it
    $slug = $baseSlug . '-' . (++$n);  // append -2, -3, etc.
}
''')

    story += h2(s, '6.5  Error Handling in Database Operations')
    story += p(s, 'Database operations should always be wrapped in a <b>try/catch</b> block. '
                   'With <b>PDO::ERRMODE_EXCEPTION</b> set, any SQL error throws a '
                   '<b>PDOException</b>. Without a catch block, this becomes a PHP Fatal '
                   'Error resulting in a blank 500 response — confusing for admins and '
                   'giving away no useful information about what went wrong:')
    story += code_block(s, 'Proper error handling pattern', '''
try {
    $pdo->prepare("INSERT INTO anime (...) VALUES (...)")->execute([...]);
    $msg = 'success:Anime saved successfully!';

} catch (PDOException $e) {
    error_log('edit_anime.php error: ' . $e->getMessage());
    $msg = 'error:Database error — ' . htmlspecialchars($e->getMessage());
}
''')
    story += p(s, 'The <b>error_log()</b> call writes the full technical error to the '
                   'server\'s Apache error log (safe — not visible to users), while '
                   'the user sees a sanitised version of the message via '
                   '<b>htmlspecialchars()</b> which prevents XSS attacks.')
    story.append(PageBreak())
    return story


def chapter_security(s):
    story = []
    story += h1(s, 'Security Best Practices', 7)
    story += p(s, 'Security is not a feature you add at the end — it must be considered '
                   'at every step. This chapter summarises the key security measures in '
                   'Anime Vault and the threats they address.')

    story += h2(s, '7.1  The OWASP Top 10')
    story += p(s, 'The <b>Open Web Application Security Project (OWASP)</b> publishes a '
                   'list of the ten most critical web application security risks. Our project '
                   'addresses several of them directly:')
    sec_data = [
        ['Risk', 'Our Defence'],
        ['SQL Injection', 'All queries use PDO prepared statements with bound parameters'],
        ['Broken Authentication', 'bcrypt hashing, session regeneration on login, httpOnly cookies'],
        ['XSS (Cross-Site Scripting)', 'All output uses htmlspecialchars() before rendering'],
        ['CSRF', 'Every form includes a random CSRF token verified on submission'],
        ['Sensitive Data Exposure', 'Passwords hashed, credentials not committed to Git'],
        ['Security Misconfiguration', '.htaccess blocks directory listing and direct access to includes/'],
    ]
    t = Table(sec_data, colWidths=[5.5*cm, None])
    t.setStyle(TableStyle([
        ('BACKGROUND', (0,0), (-1,0), DARK_BG),
        ('TEXTCOLOR',  (0,0), (-1,0), colors.white),
        ('FONTNAME',   (0,0), (-1,0), 'Helvetica-Bold'),
        ('FONTSIZE',   (0,0), (-1,-1), 9),
        ('ROWBACKGROUNDS', (0,1), (-1,-1), [LIGHT_BG, colors.white]),
        ('GRID', (0,0), (-1,-1), 0.5, LIGHT_GREY),
        ('TOPPADDING',    (0,0), (-1,-1), 5),
        ('BOTTOMPADDING', (0,0), (-1,-1), 5),
        ('LEFTPADDING',   (0,0), (-1,-1), 6),
        ('VALIGN', (0,0), (-1,-1), 'TOP'),
    ]))
    story.append(t)
    story += sp(2)

    story += h2(s, '7.2  The .htaccess File')
    story += p(s, 'Apache\'s <b>.htaccess</b> file controls server behaviour for the '
                   'directory it lives in. Our .htaccess does several important things:')
    story += code_block(s, 'anime/.htaccess — key security directives', '''
Options -Indexes          # Disables directory listing
Options -MultiViews       # Prevents content negotiation attacks

# Block direct browser access to the includes/ folder
RewriteRule ^includes/ - [F,L]

# Security headers
Header always set X-Frame-Options "SAMEORIGIN"
Header always set X-Content-Type-Options "nosniff"
Header always set X-XSS-Protection "1; mode=block"
''')
    story += p(s, '<b>Options -Indexes</b> is particularly important: without it, if a '
                   'directory has no index.php, Apache shows a listing of all files in that '
                   'directory — revealing your project structure to attackers.')

    story += h2(s, '7.3  Output Escaping')
    story += p(s, 'Any time you output data that originated from a user (or a database that '
                   'stores user data), you must escape it with <b>htmlspecialchars()</b>. '
                   'Failing to do so allows <b>Cross-Site Scripting (XSS)</b> attacks:')
    story += code_block(s, 'XSS prevention — always escape output', '''
<!-- WRONG — vulnerable to XSS: -->
<h1><?= $anime['title'] ?></h1>

<!-- CORRECT — always use htmlspecialchars(): -->
<h1><?= htmlspecialchars($anime['title']) ?></h1>
''')
    story += warn(s, 'XSS is one of the most common vulnerabilities',
                     'If a user can store text in your database that is later rendered '
                     'without escaping, they can inject JavaScript that runs in other '
                     'users\' browsers — stealing session cookies, redirecting users, or '
                     'defacing your site. htmlspecialchars() converts < to &amp;lt; so '
                     'the browser displays it as text rather than executing it.')

    story += h2(s, '7.4  HTTP Security Headers')
    story += p(s, 'Our .htaccess sets several HTTP security headers that instruct browsers '
                   'on how to handle the page:')
    story += ul(s, [
        '<b>X-Frame-Options: SAMEORIGIN</b> — prevents your pages being loaded inside '
        'an iframe on another site (clickjacking protection).',
        '<b>X-Content-Type-Options: nosniff</b> — prevents browsers from guessing the '
        'content type of a response, which can be exploited.',
        '<b>Content-Security-Policy</b> — specifies exactly which sources of scripts, '
        'styles, and images are allowed. Anything else is blocked.',
    ])
    story.append(PageBreak())
    return story


def chapter_deployment(s):
    story = []
    story += h1(s, 'Deployment & Version Control', 8)
    story += p(s, 'Once your application works locally, you need to deploy it to a live '
                   'server. This chapter covers the essentials of version control with Git '
                   'and deployment to a shared hosting environment.')

    story += h2(s, '8.1  Version Control with Git')
    story += p(s, '<b>Git</b> is a version control system that tracks every change you '
                   'make to your code. It is an essential professional skill. The basic '
                   'workflow is:')
    story += code_block(s, 'Basic Git workflow', '''
# 1. Initialise a new repository
git init

# 2. Stage files you want to commit
git add anime/

# 3. Create a commit (snapshot)
git commit -m "Add anime browse page"

# 4. Connect to GitHub
git remote add origin https://github.com/yourusername/your-repo.git

# 5. Push to GitHub
git push -u origin master
''')
    story += tip(s, 'Write meaningful commit messages',
                    'A commit message should explain <i>why</i> the change was made, not '
                    'just what files changed. "Fix CSRF token mismatch on login caused by '
                    'SameSite=Strict blocking session cookie on redirect" is far more useful '
                    'than "fix login bug".')

    story += h2(s, '8.2  What NOT to Commit')
    story += p(s, 'Some files should never be pushed to a version control repository, '
                   'especially a public one. Create a <b>.gitignore</b> file to exclude them:')
    story += code_block(s, '.gitignore', '''
# Database credentials
includes/db.php
config.php

# Editor and OS files
.vscode/
.DS_Store
Thumbs.db

# Uploaded files (often large and user-specific)
uploads/

# Log files
*.log
''')
    story += warn(s, 'Leaked credentials are a serious security incident',
                     'Once a password is pushed to a public GitHub repository, it should be '
                     'considered compromised — even if you delete it afterwards. Search engines '
                     'and security scanners index public repositories in real time. Change '
                     'any credential that was ever committed publicly.')

    story += h2(s, '8.3  The Pull Request Workflow')
    story += p(s, 'When working on new features or bug fixes, create a separate '
                   '<b>branch</b> rather than committing directly to master:')
    story += code_block(s, 'Feature branch workflow', '''
# Create and switch to a new branch
git checkout -b fix/csrf-login

# Make your changes and commit
git add anime/login.php
git commit -m "Fix CSRF token mismatch on login page"

# Push the branch to GitHub
git push -u origin fix/csrf-login

# Then open a Pull Request on GitHub to merge into master
''')
    story += p(s, 'A <b>Pull Request (PR)</b> is a proposal to merge changes. On a team, '
                   'another developer reviews the PR before it is merged. Even when working '
                   'alone, PRs create a useful history of <i>why</i> changes were made.')

    story += h2(s, '8.4  Deploying to a Live Server')
    story += p(s, 'The simplest deployment method for a PHP project is to connect your '
                   'server to your GitHub repository and pull changes:')
    story += code_block(s, 'SSH deployment (run on your server)', '''
# First time — clone the repo
cd /var/www/html
git clone https://github.com/yourusername/anime-vault.git anime

# For subsequent updates
cd /var/www/html/anime
git pull origin master
''')
    story += p(s, 'Remember to create your <b>includes/db.php</b> file directly on the '
                   'server with the production credentials — since it is in .gitignore, '
                   'it will not come down with the pull.')
    story.append(PageBreak())
    return story


def appendix(s):
    story = []
    story += h1(s, 'Appendix: Further Reading & Resources')
    story += p(s, 'The following resources are recommended for deepening your understanding '
                   'of the topics covered in this guide. All URLs were active at time of writing.')

    story += h2(s, 'A.1  Official Documentation')
    res_data = [
        ['Resource', 'URL', 'What it Covers'],
        ['PHP Manual', 'php.net/manual', 'Comprehensive reference for every PHP function and feature'],
        ['MySQL Docs', 'dev.mysql.com/doc', 'SQL syntax, data types, optimisation'],
        ['Apache Docs', 'httpd.apache.org/docs', '.htaccess directives, mod_rewrite, virtual hosts'],
        ['PDO Reference', 'php.net/manual/book.pdo.php', 'Prepared statements, transactions, error modes'],
        ['MDN Web Docs', 'developer.mozilla.org', 'HTML, CSS, JavaScript — the definitive reference'],
    ]
    t = Table(res_data, colWidths=[3.5*cm, 5.5*cm, None])
    t.setStyle(TableStyle([
        ('BACKGROUND', (0,0), (-1,0), DARK_BG),
        ('TEXTCOLOR',  (0,0), (-1,0), colors.white),
        ('FONTNAME',   (0,0), (-1,0), 'Helvetica-Bold'),
        ('FONTSIZE',   (0,0), (-1,-1), 9),
        ('ROWBACKGROUNDS', (0,1), (-1,-1), [LIGHT_BG, colors.white]),
        ('GRID', (0,0), (-1,-1), 0.5, LIGHT_GREY),
        ('TOPPADDING',    (0,0), (-1,-1), 5),
        ('BOTTOMPADDING', (0,0), (-1,-1), 5),
        ('LEFTPADDING',   (0,0), (-1,-1), 6),
        ('VALIGN', (0,0), (-1,-1), 'TOP'),
    ]))
    story.append(t)
    story += sp(2)

    story += h2(s, 'A.2  Security')
    sec_res = [
        ['OWASP Top 10', 'owasp.org/www-project-top-ten', 'The ten most critical web security risks — essential reading'],
        ['OWASP Cheat Sheets', 'cheatsheetseries.owasp.org', 'Practical guides for SQL injection, XSS, CSRF, authentication'],
        ['Have I Been Pwned', 'haveibeenpwned.com', 'Check if an email/password has appeared in known data breaches'],
        ['Security Headers', 'securityheaders.com', 'Scan your live site and get a report on missing HTTP security headers'],
    ]
    t2 = Table([['Resource','URL','What it Covers']] + sec_res, colWidths=[3.5*cm, 5.5*cm, None])
    t2.setStyle(TableStyle([
        ('BACKGROUND', (0,0), (-1,0), DARK_BG),
        ('TEXTCOLOR',  (0,0), (-1,0), colors.white),
        ('FONTNAME',   (0,0), (-1,0), 'Helvetica-Bold'),
        ('FONTSIZE',   (0,0), (-1,-1), 9),
        ('ROWBACKGROUNDS', (0,1), (-1,-1), [LIGHT_BG, colors.white]),
        ('GRID', (0,0), (-1,-1), 0.5, LIGHT_GREY),
        ('TOPPADDING',    (0,0), (-1,-1), 5),
        ('BOTTOMPADDING', (0,0), (-1,-1), 5),
        ('LEFTPADDING',   (0,0), (-1,-1), 6),
        ('VALIGN', (0,0), (-1,-1), 'TOP'),
    ]))
    story.append(t2)
    story += sp(2)

    story += h2(s, 'A.3  Learning Platforms')
    learn_res = [
        ['The Odin Project', 'theodinproject.com', 'Free, project-based full-stack web development curriculum'],
        ['freeCodeCamp', 'freecodecamp.org', 'Free coding challenges and certifications including PHP and SQL'],
        ['W3Schools', 'w3schools.com', 'Quick reference and interactive examples for HTML, CSS, PHP, SQL'],
        ['Laracasts', 'laracasts.com', 'High-quality PHP and Laravel screencasts (some free)'],
        ['CSS-Tricks', 'css-tricks.com', 'In-depth CSS articles, Flexbox and Grid guides'],
    ]
    t3 = Table([['Resource','URL','What it Covers']] + learn_res, colWidths=[3.5*cm, 5.5*cm, None])
    t3.setStyle(TableStyle([
        ('BACKGROUND', (0,0), (-1,0), DARK_BG),
        ('TEXTCOLOR',  (0,0), (-1,0), colors.white),
        ('FONTNAME',   (0,0), (-1,0), 'Helvetica-Bold'),
        ('FONTSIZE',   (0,0), (-1,-1), 9),
        ('ROWBACKGROUNDS', (0,1), (-1,-1), [LIGHT_BG, colors.white]),
        ('GRID', (0,0), (-1,-1), 0.5, LIGHT_GREY),
        ('TOPPADDING',    (0,0), (-1,-1), 5),
        ('BOTTOMPADDING', (0,0), (-1,-1), 5),
        ('LEFTPADDING',   (0,0), (-1,-1), 6),
        ('VALIGN', (0,0), (-1,-1), 'TOP'),
    ]))
    story.append(t3)
    story += sp(2)

    story += h2(s, 'A.4  Tools Used in This Project')
    tools_res = [
        ['Jikan API', 'jikan.moe', 'Free MyAnimeList REST API — no API key required for basic use'],
        ['phpMyAdmin', 'phpmyadmin.net', 'Web-based MySQL administration tool (included with XAMPP)'],
        ['XAMPP', 'apachefriends.org', 'Local development environment bundling Apache, PHP, MySQL'],
        ['Visual Studio Code', 'code.visualstudio.com', 'Free, powerful code editor with excellent PHP extensions'],
        ['GitHub', 'github.com', 'Code hosting, version control, and collaboration platform'],
        ['Git SCM', 'git-scm.com', 'Official Git documentation and downloads'],
        ['Postman', 'postman.com', 'Test and explore REST APIs interactively'],
    ]
    t4 = Table([['Tool','URL','Purpose']] + tools_res, colWidths=[3.5*cm, 5.5*cm, None])
    t4.setStyle(TableStyle([
        ('BACKGROUND', (0,0), (-1,0), DARK_BG),
        ('TEXTCOLOR',  (0,0), (-1,0), colors.white),
        ('FONTNAME',   (0,0), (-1,0), 'Helvetica-Bold'),
        ('FONTSIZE',   (0,0), (-1,-1), 9),
        ('ROWBACKGROUNDS', (0,1), (-1,-1), [LIGHT_BG, colors.white]),
        ('GRID', (0,0), (-1,-1), 0.5, LIGHT_GREY),
        ('TOPPADDING',    (0,0), (-1,-1), 5),
        ('BOTTOMPADDING', (0,0), (-1,-1), 5),
        ('LEFTPADDING',   (0,0), (-1,-1), 6),
        ('VALIGN', (0,0), (-1,-1), 'TOP'),
    ]))
    story.append(t4)
    story += sp(2)

    story += h2(s, 'A.5  Next Steps')
    story += p(s, 'Once comfortable with the patterns in this project, consider exploring:')
    story += ul(s, [
        '<b>A PHP framework</b> — Laravel (laravel.com) or Symfony (symfony.com) build on '
        'exactly the concepts here and add routing, ORM, templating, and more.',
        '<b>Front-end frameworks</b> — React (react.dev) or Vue (vuejs.org) for richer '
        'interactive interfaces.',
        '<b>Automated testing</b> — PHPUnit (phpunit.de) for unit testing your PHP functions.',
        '<b>Containerisation</b> — Docker (docker.com) to replace XAMPP with a reproducible '
        'development environment that matches your production server exactly.',
        '<b>CI/CD</b> — GitHub Actions to automatically run tests and deploy on every push.',
    ])
    return story


# ── Main build ────────────────────────────────────────────────────────────────
def build():
    s = make_styles()
    doc = BookDocTemplate(OUTPUT_PATH,
                          pagesize=A4,
                          leftMargin=2*cm, rightMargin=2*cm,
                          topMargin=2*cm,  bottomMargin=2.2*cm)

    story = []

    # Cover
    story += cover_page(s)
    doc.addPageTemplates  # already added in __init__

    # TOC (will be filled in by multi-pass build)
    toc_story, toc = toc_page(s)
    story += toc_story

    # Chapters
    story += chapter_intro(s)
    story += chapter_environment(s)
    story += chapter_database(s)
    story += chapter_foundation(s)
    story += chapter_public(s)
    story += chapter_admin(s)
    story += chapter_security(s)
    story += chapter_deployment(s)
    story += appendix(s)

    # Apply page templates
    from reportlab.platypus import NextPageTemplate
    # Insert template switches
    final_story = []
    final_story.append(NextPageTemplate('TOC'))
    for item in story:
        final_story.append(item)

    doc.multiBuild(final_story)
    print(f"PDF written to: {OUTPUT_PATH}")


if __name__ == '__main__':
    build()
