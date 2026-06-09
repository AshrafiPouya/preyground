from flask import Blueprint, render_template, request, session, redirect, url_for
from .base import get_db, log_payload, get_payload_log, mark_solved, is_solved, check_flag, reveal_hint, get_hints_revealed

lab10_bp = Blueprint('lab10', __name__, url_prefix='/lab10')

FLAG = 'HUNT{you_are_now_a_hunter}'

HINTS = [
    "Not every input is vulnerable. Map the app first — there are four endpoints. Test each one for *any* anomaly: errors, timing changes, response length differences.",
    "The vulnerable point won't hand you data directly. You'll need to combine at least two techniques you've already learned. Think: what's blocking you, and what have you learned to get around that?",
    "Once you confirm the injection point, fingerprint the DB, bypass what's filtering you, then extract inferentially. Think about the order: confirm → fingerprint → bypass → extract.",
]

# Naive keyword filter — case-sensitive, bypassable via case variation
_BLOCKED = ['select', 'SELECT', 'union', 'UNION', 'drop', 'DROP', 'sleep', 'SLEEP']


def _waf(s: str):
    for t in _BLOCKED:
        if t in s:
            return False
    return True


def _search_articles(q: str):
    conn = get_db(10)
    # NOT VULNERABLE — parameterized
    try:
        rows = conn.execute(
            "SELECT title, author, summary FROM articles WHERE title LIKE ?",
            (f'%{q}%',)
        ).fetchall()
        conn.close()
        return [dict(r) for r in rows], None
    except Exception as e:
        conn.close()
        return None, str(e)


def _get_ticket(ticket_id: str):
    conn = get_db(10)
    # NOT VULNERABLE — parameterized
    try:
        row = conn.execute(
            "SELECT id, subject, status FROM tickets WHERE id = ?", (ticket_id,)
        ).fetchone()
        conn.close()
        return dict(row) if row else None, None
    except Exception as e:
        conn.close()
        return None, str(e)


def _search_users(username: str):
    conn = get_db(10)
    # NOT VULNERABLE — parameterized
    try:
        rows = conn.execute(
            "SELECT id, username, role FROM accounts WHERE username LIKE ?",
            (f'%{username}%',)
        ).fetchall()
        conn.close()
        return [dict(r) for r in rows], None
    except Exception as e:
        conn.close()
        return None, str(e)


def _get_product(product_id: str):
    conn = get_db(10)
    # DELIBERATELY VULNERABLE — filtered but bypassable
    if not _waf(product_id):
        conn.close()
        return None, "Request blocked by security filter.", None
    query = f"SELECT name, description, price FROM products WHERE id = {product_id}"
    try:
        rows = conn.execute(query).fetchall()
        conn.close()
        return [dict(r) for r in rows], None, query
    except Exception as e:
        conn.close()
        return None, str(e), query


@lab10_bp.route('/', methods=['GET'])
def index():
    return render_template(
        'labs/lab10.html',
        solved=is_solved(10),
        hints=HINTS, hints_revealed=get_hints_revealed(10),
        payload_log=get_payload_log(10),
        flag_error=session.pop('lab10_flag_error', False),
        results=session.pop('lab10_results', None),
    )


@lab10_bp.route('/articles', methods=['GET'])
def articles():
    q = request.args.get('q', '')
    log_payload(10, q, 'articles search')
    rows, error = _search_articles(q)
    session['lab10_results'] = {'endpoint': 'articles', 'q': q, 'rows': rows, 'error': error}
    return redirect(url_for('lab10.index'))


@lab10_bp.route('/ticket', methods=['GET'])
def ticket():
    tid = request.args.get('id', '')
    log_payload(10, tid, 'ticket lookup')
    row, error = _get_ticket(tid)
    session['lab10_results'] = {'endpoint': 'ticket', 'id': tid, 'row': row, 'error': error}
    return redirect(url_for('lab10.index'))


@lab10_bp.route('/users', methods=['GET'])
def users():
    username = request.args.get('username', '')
    log_payload(10, username, 'user search')
    rows, error = _search_users(username)
    session['lab10_results'] = {'endpoint': 'users', 'username': username, 'rows': rows, 'error': error}
    return redirect(url_for('lab10.index'))


@lab10_bp.route('/product', methods=['GET'])
def product():
    pid = request.args.get('id', '1')
    log_payload(10, pid, 'product lookup (filtered)')
    result = _get_product(pid)
    if len(result) == 3:
        rows, error, query = result
    else:
        rows, error, query = result[0], result[1], None
    session['lab10_results'] = {
        'endpoint': 'product', 'id': pid,
        'rows': rows, 'error': error, 'query': query
    }
    return redirect(url_for('lab10.index'))


@lab10_bp.route('/hint/<int:n>', methods=['POST'])
def hint(n):
    if 1 <= n <= 3:
        reveal_hint(10, n)
    return redirect(url_for('lab10.index'))


@lab10_bp.route('/flag', methods=['POST'])
def submit_flag():
    submitted = request.form.get('flag', '')
    if check_flag(10, submitted, FLAG):
        mark_solved(10)
    else:
        session['lab10_flag_error'] = True
    return redirect(url_for('lab10.index'))


@lab10_bp.route('/reset', methods=['POST'])
def reset():
    from setup_db import init_lab10
    init_lab10()
    session.pop('payload_log', None)
    session.pop('lab10_results', None)
    return redirect(url_for('lab10.index'))


@lab10_bp.route('/interview')
def interview():
    return render_template('labs/lab10_interview.html', solved=is_solved(10))
