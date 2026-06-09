import sqlite3
import os
import time
import threading
from flask import session
from config import DATA_DIR

# Thread-local storage so concurrent requests don't interfere
_tl = threading.local()


def get_db(lab_num: int) -> sqlite3.Connection:
    db_path = os.path.join(DATA_DIR, f'lab{lab_num}.db')
    conn = sqlite3.connect(db_path)
    conn.row_factory = sqlite3.Row
    _register_mysql_compat(conn)
    return conn


def _register_mysql_compat(conn: sqlite3.Connection):
    """Add MySQL-compatible functions so learners can use real-world payloads."""

    def extractvalue(arg1, xpath_str):
        # Capture the value so we can surface it after the exception is caught.
        _tl.last_extracted = f"XPATH syntax error: '{xpath_str}'"
        raise sqlite3.OperationalError(_tl.last_extracted)

    def updatexml(arg1, xpath, new_val):
        _tl.last_extracted = f"XPATH syntax error: '{xpath}'"
        raise sqlite3.OperationalError(_tl.last_extracted)

    def concat(*args):
        return ''.join('' if a is None else str(a) for a in args)

    def sleep_fn(seconds):
        try:
            secs = min(float(seconds), 10)
        except (TypeError, ValueError):
            secs = 0
        time.sleep(secs)
        return 0

    def ifnull(val, default):
        return val if val is not None else default

    conn.create_function('extractvalue', 2, extractvalue)
    conn.create_function('updatexml', 3, updatexml)
    conn.create_function('concat', -1, concat)
    conn.create_function('sleep', 1, sleep_fn)
    conn.create_function('ifnull', 2, ifnull)


def get_last_extracted_error() -> str | None:
    """Return the error message captured by extractvalue/updatexml, if any."""
    val = getattr(_tl, 'last_extracted', None)
    _tl.last_extracted = None
    return val


def log_payload(lab_num: int, payload: str, context: str = ''):
    if 'payload_log' not in session:
        session['payload_log'] = {}
    key = str(lab_num)
    if key not in session['payload_log']:
        session['payload_log'][key] = []
    entry = {'payload': payload, 'context': context, 'ts': int(time.time())}
    session['payload_log'][key].insert(0, entry)
    session['payload_log'][key] = session['payload_log'][key][:50]
    session.modified = True


def get_payload_log(lab_num: int):
    return session.get('payload_log', {}).get(str(lab_num), [])


def mark_solved(lab_num: int):
    solved = session.get('solved', [])
    if lab_num not in solved:
        solved.append(lab_num)
    session['solved'] = solved
    session.modified = True


def is_solved(lab_num: int) -> bool:
    return lab_num in session.get('solved', [])


def get_hints_revealed(lab_num: int) -> list:
    return session.get('hints', {}).get(str(lab_num), [])


def reveal_hint(lab_num: int, hint_num: int):
    hints = session.get('hints', {})
    key = str(lab_num)
    if key not in hints:
        hints[key] = []
    if hint_num not in hints[key]:
        hints[key].append(hint_num)
    session['hints'] = hints
    session.modified = True


def check_flag(lab_num: int, submitted: str, correct: str) -> bool:
    return submitted.strip() == correct
