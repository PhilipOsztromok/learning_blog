#!/usr/bin/env bash
# =============================================================================
# migrate.sh — Import existing HTML content into the learning blog database
# =============================================================================

# NOTE: deliberately NOT using 'set -e' so individual failures don't
# silently abort the whole migration. Errors are reported per-item instead.

# =============================================================================
# CONFIG — edit these before running
# =============================================================================

WEB_ROOT="/var/www/html"
DB_NAME="learning_blog"
DB_USER="philip"
DB_PASS="AsT@1sAd3mon"
DB_HOST="localhost"

# Folders inside WEB_ROOT to skip (not subject folders)
SKIP_DIRS="admin images css js fonts assets uploads .git"

# =============================================================================
# COLOUR HELPERS
# =============================================================================

BOLD='\033[1m'; GREEN='\033[0;32m'; YELLOW='\033[1;33m'
CYAN='\033[0;36m'; RED='\033[0;31m'; RESET='\033[0m'

log()     { echo -e "${CYAN}[migrate]${RESET} $*"; }
ok()      { echo -e "  ${GREEN}✓${RESET} $*"; }
warn()    { echo -e "  ${YELLOW}⚠${RESET}  $*"; }
err()     { echo -e "  ${RED}✗${RESET} $*"; }
dry()     { echo -e "  ${YELLOW}[DRY RUN]${RESET} $*"; }
section() { echo -e "\n${BOLD}$*${RESET}"; }

DRY_RUN=false
[[ "${1:-}" == "--dry-run" ]] && DRY_RUN=true

# =============================================================================
# DATABASE HELPER
# Uses a MySQL options file so the password is never on the command line
# and is not visible in process lists.
# =============================================================================

MYCNF=$(mktemp /tmp/.my_XXXXXX.cnf)
chmod 600 "$MYCNF"
cat > "$MYCNF" <<EOF
[client]
host=$DB_HOST
user=$DB_USER
password=$DB_PASS
database=$DB_NAME
EOF
# Clean up credentials file on exit
trap 'rm -f "$MYCNF"' EXIT

db() {
    # Usage: db "SQL statement"
    mysql --defaults-file="$MYCNF" --skip-column-names --silent -e "$1" 2>&1
}

db_import() {
    # Reads SQL from stdin — used for content that may contain special characters
    mysql --defaults-file="$MYCNF" 2>&1
}

# =============================================================================
# STRING HELPERS
# =============================================================================

# Escape single quotes for MySQL string literals (doubling them is ANSI SQL)
escape() { printf '%s' "$1" | sed "s/'/''/g"; }

# Convert slug to display name: "html-essential-training" → "HTML Essential Training"
to_name() {
    local name
    name=$(echo "$1" | tr '-' ' ' | sed 's/\b\(.\)/\u\1/g')
    name=$(echo "$name" | sed '
        s/\bHtml\b/HTML/g
        s/\bCss\b/CSS/g
        s/\bJs\b/JS/g
        s/\bApi\b/API/g
        s/\bSql\b/SQL/g
        s/\bPhp\b/PHP/g
        s/\bPi\b/Pi/g
    ')
    echo "$name"
}

# Check if a value is in a space-separated list
in_list() {
    local val="$1" list="$2"
    for item in $list; do [[ "$item" == "$val" ]] && return 0; done
    return 1
}

# =============================================================================
# BODY EXTRACTION
# Pulls page content from an HTML file using Python.
# Tries several strategies in order.
# =============================================================================

extract_body() {
    local file="$1"
    python3 - "$file" <<'PYEOF'
import sys, re

try:
    content = open(sys.argv[1], encoding='utf-8', errors='replace').read()
except Exception as e:
    print(f'<p>Could not read file: {e}</p>')
    sys.exit(0)

# Strategy 1: <div class="page-body">
m = re.search(r'<div[^>]*class=["\']page-body["\'][^>]*>(.*?)</div\s*>', content, re.S | re.I)
if m and m.group(1).strip():
    print(m.group(1).strip()); sys.exit(0)

# Strategy 2: <main> tag — strip the h1 since title is stored separately
m = re.search(r'<main[^>]*>(.*?)</main\s*>', content, re.S | re.I)
if m:
    body = re.sub(r'<h1[^>]*>.*?</h1\s*>', '', m.group(1), flags=re.S | re.I).strip()
    if body:
        print(body); sys.exit(0)

# Strategy 3: <article> tag
m = re.search(r'<article[^>]*>(.*?)</article\s*>', content, re.S | re.I)
if m:
    body = re.sub(r'<h1[^>]*>.*?</h1\s*>', '', m.group(1), flags=re.S | re.I).strip()
    if body:
        print(body); sys.exit(0)

# Strategy 4: everything after the first </h1> up to </body>
m = re.search(r'</h1\s*>(.*?)(?:</body\s*>|$)', content, re.S | re.I)
if m and m.group(1).strip():
    print(m.group(1).strip()); sys.exit(0)

print('<p>(No content extracted — please edit this page in the admin.)</p>')
PYEOF
}

# =============================================================================
# PRE-FLIGHT CHECKS
# =============================================================================

log "Running pre-flight checks..."
ERRORS=0

if ! command -v mysql &>/dev/null; then
    err "mysql client not found.  Fix: sudo apt install default-mysql-client"
    ERRORS=$((ERRORS+1))
fi

if ! command -v python3 &>/dev/null; then
    err "python3 not found.  Fix: sudo apt install python3"
    ERRORS=$((ERRORS+1))
fi

if [[ ! -d "$WEB_ROOT" ]]; then
    err "Web root not found: $WEB_ROOT"
    ERRORS=$((ERRORS+1))
fi

[[ $ERRORS -gt 0 ]] && { echo; err "Fix the errors above then re-run."; exit 1; }

# Test DB connection
DB_TEST=$(db "SELECT 'connected'" 2>&1)
if [[ "$DB_TEST" != "connected" ]]; then
    err "Cannot connect to MySQL. Response: $DB_TEST"
    err "Check DB_USER / DB_PASS / DB_NAME / DB_HOST at the top of this script."
    exit 1
fi

# Confirm the schema exists
TABLES=$(db "SHOW TABLES LIKE 'subjects'" 2>&1)
if [[ -z "$TABLES" ]]; then
    err "Table 'subjects' not found in '$DB_NAME'. Run the schema SQL first."
    exit 1
fi

ok "All pre-flight checks passed."
$DRY_RUN && { echo; warn "DRY RUN — nothing will be written to the database."; }

# =============================================================================
# DISCOVERY
# =============================================================================

section "Scanning $WEB_ROOT ..."

SUBJECT_DIRS=()
for dir in "$WEB_ROOT"/*/; do
    [[ ! -d "$dir" ]] && continue
    slug=$(basename "$dir")
    if in_list "$slug" "$SKIP_DIRS"; then
        log "  Skipping: $slug"
        continue
    fi
    SUBJECT_DIRS+=("$dir")
    log "  Found subject folder: $slug"
done

if [[ ${#SUBJECT_DIRS[@]} -eq 0 ]]; then
    err "No subject folders found in $WEB_ROOT — check that WEB_ROOT is correct."
    exit 1
fi

echo
log "Found ${#SUBJECT_DIRS[@]} subject folder(s). Starting import..."

# =============================================================================
# MAIN IMPORT LOOP
# =============================================================================

TOTAL_SUBJECTS=0; TOTAL_SUBTOPICS=0; TOTAL_PAGES=0
SKIPPED_PAGES=0;  FAILED_PAGES=0

for subject_dir in "${SUBJECT_DIRS[@]}"; do
    subject_slug=$(basename "$subject_dir")
    subject_name=$(to_name "$subject_slug")

    section "Subject: $subject_name (/$subject_slug)"

    # ── Insert or find subject ──────────────────────────────────────────────
    if ! $DRY_RUN; then
        db "INSERT INTO subjects (name, slug, description, image_url, sort_order)
            VALUES ('$(escape "$subject_name")', '$(escape "$subject_slug")', '', '', 0)
            ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id);" > /dev/null

        SUBJECT_ID=$(db "SELECT id FROM subjects WHERE slug='$(escape "$subject_slug")' LIMIT 1")

        if [[ -z "$SUBJECT_ID" || ! "$SUBJECT_ID" =~ ^[0-9]+$ ]]; then
            err "Could not get ID for subject '$subject_slug' — skipping."
            continue
        fi
        ok "Subject ID=$SUBJECT_ID"
    else
        dry "Would insert/find subject: $subject_name"
        SUBJECT_ID=999
    fi

    TOTAL_SUBJECTS=$((TOTAL_SUBJECTS+1))
    SUBTOPIC_ORDER=0

    # ── Walk subtopic folders ───────────────────────────────────────────────
    for subtopic_dir in "$subject_dir"*/; do
        [[ ! -d "$subtopic_dir" ]] && continue
        subtopic_slug=$(basename "$subtopic_dir")
        [[ "$subtopic_slug" == .* ]] && continue
        in_list "$subtopic_slug" "$SKIP_DIRS" && continue

        subtopic_name=$(to_name "$subtopic_slug")
        SUBTOPIC_ORDER=$((SUBTOPIC_ORDER+10))

        echo -e "  ${CYAN}Subtopic:${RESET} $subtopic_name"

        # ── Insert or find subtopic ─────────────────────────────────────────
        if ! $DRY_RUN; then
            db "INSERT INTO subtopics (subject_id, name, slug, description, sort_order)
                VALUES ($SUBJECT_ID, '$(escape "$subtopic_name")', '$(escape "$subtopic_slug")', '', $SUBTOPIC_ORDER)
                ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id);" > /dev/null

            SUBTOPIC_ID=$(db "SELECT id FROM subtopics
                              WHERE subject_id=$SUBJECT_ID AND slug='$(escape "$subtopic_slug")' LIMIT 1")

            if [[ -z "$SUBTOPIC_ID" || ! "$SUBTOPIC_ID" =~ ^[0-9]+$ ]]; then
                err "    Could not get ID for subtopic '$subtopic_slug' — skipping."
                continue
            fi
        else
            dry "  Would insert/find subtopic: $subtopic_name"
            SUBTOPIC_ID=999
        fi

        TOTAL_SUBTOPICS=$((TOTAL_SUBTOPICS+1))
        PAGE_ORDER=0

        # ── Walk HTML files in this subtopic folder ─────────────────────────
        while IFS= read -r -d '' html_file; do
            filename=$(basename "$html_file" .html)
            [[ "$filename" == "index" ]]          && continue
            [[ "$filename" == "$subtopic_slug" ]] && continue

            page_slug="$filename"
            page_title=$(to_name "$page_slug")
            PAGE_ORDER=$((PAGE_ORDER+10))

            # Skip if already in DB
            if ! $DRY_RUN; then
                EXISTING=$(db "SELECT COUNT(*) FROM pages
                               WHERE subtopic_id=$SUBTOPIC_ID AND slug='$(escape "$page_slug")'")
                if [[ "$EXISTING" -gt 0 ]]; then
                    warn "    Skipping (already in DB): $page_title"
                    SKIPPED_PAGES=$((SKIPPED_PAGES+1))
                    continue
                fi
            fi

            # Extract body content
            body=$(extract_body "$html_file")
            echo -e "    ${GREEN}Page:${RESET} $page_title [${#body} chars]"

            if ! $DRY_RUN; then
                # Insert page metadata row
                INSERT_RESULT=$(db "INSERT INTO pages (subtopic_id, title, slug, sort_order)
                                    VALUES ($SUBTOPIC_ID, '$(escape "$page_title")',
                                            '$(escape "$page_slug")', $PAGE_ORDER);" 2>&1)

                if echo "$INSERT_RESULT" | grep -qi "error"; then
                    err "    Page insert failed: $INSERT_RESULT"
                    FAILED_PAGES=$((FAILED_PAGES+1)); continue
                fi

                PAGE_ID=$(db "SELECT id FROM pages
                              WHERE subtopic_id=$SUBTOPIC_ID AND slug='$(escape "$page_slug")' LIMIT 1")
                if [[ -z "$PAGE_ID" || ! "$PAGE_ID" =~ ^[0-9]+$ ]]; then
                    err "    Could not get page ID — skipping content."
                    FAILED_PAGES=$((FAILED_PAGES+1)); continue
                fi

                # Insert content via stdin to safely handle quotes and special chars
                CONTENT_RESULT=$(db_import <<SQL
SET NAMES utf8mb4;
INSERT INTO page_content (page_id, body)
VALUES ($PAGE_ID, '$(printf '%s' "$body" | sed "s/'/''/g")');
SQL
                )

                if echo "$CONTENT_RESULT" | grep -qi "error"; then
                    err "    Content insert failed: $CONTENT_RESULT"
                    db "DELETE FROM pages WHERE id=$PAGE_ID" > /dev/null
                    FAILED_PAGES=$((FAILED_PAGES+1)); continue
                fi

                ok "    Imported (page_id=$PAGE_ID)"
            else
                dry "    Would import: $page_title"
            fi

            TOTAL_PAGES=$((TOTAL_PAGES+1))

        done < <(find "$subtopic_dir" -maxdepth 1 -name "*.html" -print0 | sort -z)

    done
done

# =============================================================================
# SUMMARY
# =============================================================================

echo
echo -e "${BOLD}═══════════════════════════════${RESET}"
echo -e "${BOLD} Migration summary${RESET}"
echo -e "${BOLD}═══════════════════════════════${RESET}"
echo -e "  Subjects   : ${GREEN}$TOTAL_SUBJECTS${RESET}"
echo -e "  Subtopics  : ${GREEN}$TOTAL_SUBTOPICS${RESET}"
echo -e "  Pages      : ${GREEN}$TOTAL_PAGES${RESET} imported"
echo -e "  Skipped    : ${YELLOW}$SKIPPED_PAGES${RESET} (already existed)"
echo -e "  Failed     : ${RED}$FAILED_PAGES${RESET}"

if ! $DRY_RUN; then
    echo
    log "Live row counts:"
    echo -e "  subjects     : $(db 'SELECT COUNT(*) FROM subjects') rows"
    echo -e "  subtopics    : $(db 'SELECT COUNT(*) FROM subtopics') rows"
    echo -e "  pages        : $(db 'SELECT COUNT(*) FROM pages') rows"
    echo -e "  page_content : $(db 'SELECT COUNT(*) FROM page_content') rows"
fi

$DRY_RUN && { echo; warn "Dry run complete. Remove --dry-run to import for real."; }
echo
