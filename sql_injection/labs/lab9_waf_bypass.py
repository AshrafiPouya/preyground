from flask import Blueprint, render_template, request, session, redirect, url_for
from .base import get_db, log_payload, get_payload_log, mark_solved, is_solved, check_flag, reveal_hint, get_hints_revealed

lab9_bp = Blueprint('lab9', __name__, url_prefix='/lab9')

FLAG = 'HUNT{blacklists_always_lose}'

HINTS = [
    "The filter blocks certain keywords and characters — but blacklists are brittle. Does it catch every *form* of those words? Try mixed case: <code>SeLeCt</code>, <code>UnIoN</code>.",
    "If case variation doesn't work alone, break up keywords with inline comments: <code>UN/**/ION</code>, <code>SEL/**/ECT</code>. The DB engine ignores comments; the WAF doesn't see the keyword.",
    "Quotes blocked? Use hex literals instead: <code>0x48554e54</code> decodes to a string. Spaces blocked? Use <code>/**/</code> or <code>%09</code>. Combine techniques until it slips through.",
]

# Naive keyword blacklist — case-sensitive, intentionally bypassable.
# Blocks exact-casing only. Bypass: case variation (SeLeCt, UniOn),
# inline comments (UN/**/ION SE/**/LECT), or double-encoding.
BLOCKED_TERMS = [
    'select', 'SELECT',
    'union', 'UNION',
    'insert', 'INSERT',
    'delete', 'DELETE',
    'drop', 'DROP',
    'sleep', 'SLEEP',
]


def _waf_check(payload: str):
    for term in BLOCKED_TERMS:
        if term in payload:
            return False, f"Blocked: detected '{term}'"
    return True, None


def _run_query(search: str):
    conn = get_db(9)
    # DELIBERATELY VULNERABLE
    query = f"SELECT title, genre, year FROM movies WHERE title LIKE '%{search}%'"
    try:
        rows = conn.execute(query).fetchall()
        conn.close()
        return [dict(r) for r in rows], None, query
    except Exception as e:
        conn.close()
        return None, str(e), query


@lab9_bp.route('/', methods=['GET'])
def index():
    search = request.args.get('q', '')
    rows, error, query, blocked_msg = None, None, None, None
    if search or request.args.get('submitted'):
        log_payload(9, search, 'search (WAF protected)')
        passed, blocked_msg = _waf_check(search)
        if passed:
            rows, error, query = _run_query(search)
    return render_template(
        'labs/lab9.html',
        search=search, rows=rows, error=error, query=query,
        blocked_msg=blocked_msg,
        blocked_terms=BLOCKED_TERMS,
        solved=is_solved(9),
        hints=HINTS, hints_revealed=get_hints_revealed(9),
        payload_log=get_payload_log(9),
        flag_error=session.pop('lab9_flag_error', False),
    )


@lab9_bp.route('/hint/<int:n>', methods=['POST'])
def hint(n):
    if 1 <= n <= 3:
        reveal_hint(9, n)
    return redirect(url_for('lab9.index'))


@lab9_bp.route('/flag', methods=['POST'])
def submit_flag():
    submitted = request.form.get('flag', '')
    if check_flag(9, submitted, FLAG):
        mark_solved(9)
    else:
        session['lab9_flag_error'] = True
    return redirect(url_for('lab9.index'))


@lab9_bp.route('/reset', methods=['POST'])
def reset():
    from setup_db import init_lab9
    init_lab9()
    session.pop('payload_log', None)
    return redirect(url_for('lab9.index'))


@lab9_bp.route('/interview')
def interview():
    return render_template('labs/lab9_interview.html', solved=is_solved(9))
