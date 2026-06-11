"""
crunchyroll_history.py
======================
Exports your Crunchyroll watch history to a CSV file.

Usage
-----
    python crunchyroll_history.py --token YOUR_BEARER_TOKEN

How to get your token
---------------------
1. Log in to https://www.crunchyroll.com/history in your browser.
2. Open DevTools (F12) → Network tab.
3. Refresh the page.
4. Find the request to:
       /content/v2/.../watch-history?page_size=100&locale=en-US
5. In the request headers, copy the value after "Authorization: Bearer "
   (it is a long string starting with "eyJ...")
6. Paste it as the --token argument below.

Note: The token expires after roughly 5 minutes. If you get a 401 error,
refresh the /history page in your browser and grab a fresh token.

Output
------
Creates crunchyroll_watch_history.csv in the current directory with columns:
    date_watched, show_title, season_title, episode_number, episode_title, fully_watched
"""

import argparse
import csv
import sys
import time
from datetime import datetime, timezone

try:
    import requests
except ImportError:
    print("ERROR: 'requests' is not installed.")
    print("Run:  pip install requests")
    sys.exit(1)

# ── Configuration ─────────────────────────────────────────────────────────────

BASE_URL    = "https://www.crunchyroll.com"
PAGE_SIZE   = 100          # max allowed by the API
OUTPUT_FILE = "crunchyroll_watch_history.csv"
DELAY_SEC   = 0.3          # polite pause between pages (seconds)

# ── Helpers ───────────────────────────────────────────────────────────────────

def validate_token(token: str) -> None:
    """Catch common copy-paste corruption before the request is sent."""
    try:
        token.encode("latin-1")
    except UnicodeEncodeError as e:
        bad_char = token[e.start]
        print(f"\nERROR: Your token contains an invalid character at position {e.start}: "
              f"'{bad_char}' (U+{ord(bad_char):04X})")
        if bad_char == "…":
            print("       This is an ellipsis '…' — the token was truncated during copy-paste.")
        print("\nThe token must be copied in full from DevTools.")
        print("Right-click the Authorization header value and choose 'Copy Value'.")
        sys.exit(1)
    if not token.startswith("eyJ"):
        print("\nERROR: Token does not look like a JWT (should start with 'eyJ').")
        print("Make sure you copied only the part AFTER 'Bearer ' in the Authorization header.")
        sys.exit(1)


def build_session(token: str) -> requests.Session:
    session = requests.Session()
    session.headers.update({
        "Authorization": f"Bearer {token}",
        "Accept":        "application/json, text/plain, */*",
        "Accept-Language": "en-US,en;q=0.9",
        "User-Agent":    (
            "Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:153.0) "
            "Gecko/20100101 Firefox/153.0"
        ),
        "Referer":       "https://www.crunchyroll.com/history",
    })
    return session


def get_account_id(session: requests.Session) -> str:
    """
    Fetch the numeric account_id from /accounts/v1/me.
    This is the ID used in all content API URLs — it is different from
    the profile UUID that appears in the JWT token.
    """
    url = f"{BASE_URL}/accounts/v1/me"
    try:
        resp = session.get(url, timeout=30)
        if resp.status_code == 401:
            print("\nERROR 401: Token has expired or is invalid.")
            print("Refresh https://www.crunchyroll.com/history in your browser,")
            print("grab a fresh Bearer token from the Network tab, and try again.")
            sys.exit(1)
        resp.raise_for_status()
        data = resp.json()
        account_id = data.get("account_id")
        if not account_id:
            raise ValueError(f"account_id not found in response: {data}")
        return account_id
    except requests.exceptions.HTTPError as e:
        print(f"\nERROR fetching account ID: {e}")
        sys.exit(1)
    except Exception as e:
        print(f"\nERROR: Could not retrieve account_id from /accounts/v1/me: {e}")
        sys.exit(1)


def fetch_page(session: requests.Session, url: str) -> dict:
    resp = session.get(url, timeout=30)
    if resp.status_code == 401:
        print("\nERROR 401: Token has expired or is invalid.")
        print("Refresh https://www.crunchyroll.com/history in your browser,")
        print("grab a fresh Bearer token from the Network tab, and try again.")
        sys.exit(1)
    resp.raise_for_status()
    return resp.json()


def format_date(iso_str: str) -> str:
    """Convert ISO 8601 UTC string to a readable local date string."""
    try:
        dt = datetime.fromisoformat(iso_str.replace("Z", "+00:00"))
        # Convert to local time for display
        dt_local = dt.astimezone()
        return dt_local.strftime("%Y-%m-%d %H:%M:%S")
    except Exception:
        return iso_str


def extract_row(item: dict) -> dict:
    """Pull the fields we care about from one watch-history entry."""
    panel    = item.get("panel", {})
    meta     = panel.get("episode_metadata", {})

    return {
        "date_watched":   format_date(item.get("date_played", "")),
        "show_title":     meta.get("series_title", "").strip(),
        "season_title":   meta.get("season_title", "").strip(),
        "season_number":  meta.get("season_number", ""),
        "episode_number": meta.get("episode_number", meta.get("episode", "")),
        "episode_title":  panel.get("title", "").strip(),
        "fully_watched":  str(item.get("fully_watched", "")).lower(),
    }

# ── Main ──────────────────────────────────────────────────────────────────────

def main():
    parser = argparse.ArgumentParser(
        description="Export Crunchyroll watch history to CSV.",
        formatter_class=argparse.RawDescriptionHelpFormatter,
        epilog=__doc__,
    )
    parser.add_argument(
        "--token", "-t",
        default=None,
        help="Bearer JWT token from the Authorization header (see instructions above)",
    )
    parser.add_argument(
        "--token-file", "-f",
        default=None,
        help="Path to a plain text file containing just the Bearer token (avoids shell truncation)",
    )
    parser.add_argument(
        "--output", "-o",
        default=OUTPUT_FILE,
        help=f"Output CSV filename (default: {OUTPUT_FILE})",
    )
    parser.add_argument(
        "--max-pages",
        type=int,
        default=None,
        help="Stop after this many pages (useful for testing; omit for full export)",
    )
    args = parser.parse_args()

    # ── Resolve token (inline or from file) ──────────────────────────────────
    if args.token_file:
        try:
            with open(args.token_file, "r", encoding="utf-8") as tf:
                args.token = tf.read().strip()
        except FileNotFoundError:
            print(f"ERROR: Token file not found: {args.token_file}")
            sys.exit(1)
    elif not args.token:
        print("ERROR: Provide a token via --token or --token-file.")
        sys.exit(1)

    # ── Validate token before making any requests ─────────────────────────────
    validate_token(args.token)

    # ── Set up HTTP session ───────────────────────────────────────────────────
    session = build_session(args.token)

    # ── Resolve account ID from the API (not from the JWT) ───────────────────
    # Crunchyroll's content URLs require the account_id returned by
    # /accounts/v1/me — this is different from the profile UUID in the token.
    account_id = get_account_id(session)
    print(f"Account ID : {account_id}")

    # ── Fetch all pages ───────────────────────────────────────────────────────
    first_url = (
        f"{BASE_URL}/content/v2/{account_id}/watch-history"
        f"?page_size={PAGE_SIZE}&locale=en-US"
    )

    rows      = []
    page_num  = 0
    next_path = first_url   # first iteration uses a full URL; subsequent ones a path

    while next_path:
        page_num += 1

        # The API returns a relative path in next_page, e.g.
        # /content/v2/.../watch-history?locale=en-US&page=2&page_size=100
        url = (
            next_path if next_path.startswith("http")
            else BASE_URL + next_path
        )

        print(f"  Fetching page {page_num}...", end=" ", flush=True)
        data = fetch_page(session, url)

        items    = data.get("data", [])
        total    = data.get("total", "?")
        next_path = data.get("meta", {}).get("next_page", "")

        for item in items:
            rows.append(extract_row(item))

        print(f"got {len(items)} items  (total fetched: {len(rows)} / {total})")

        if args.max_pages and page_num >= args.max_pages:
            print(f"  Stopped after {args.max_pages} page(s) (--max-pages limit).")
            break

        if next_path:
            time.sleep(DELAY_SEC)

    # ── Write CSV ─────────────────────────────────────────────────────────────
    if not rows:
        print("\nNo watch history found — nothing to write.")
        return

    fieldnames = [
        "date_watched",
        "show_title",
        "season_title",
        "season_number",
        "episode_number",
        "episode_title",
        "fully_watched",
    ]

    with open(args.output, "w", newline="", encoding="utf-8") as f:
        writer = csv.DictWriter(f, fieldnames=fieldnames)
        writer.writeheader()
        writer.writerows(rows)

    print(f"\nDone! {len(rows)} rows written to: {args.output}")


if __name__ == "__main__":
    main()
