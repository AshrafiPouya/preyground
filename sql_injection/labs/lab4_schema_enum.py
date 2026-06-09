from flask import Blueprint, render_template, request, session, redirect, url_for
from .base import get_db, log_payload, get_payload_log, mark_solved, is_solved, check_flag, reveal_hint, get_hints_revealed

lab4_bp = Blueprint('lab4', __name__, url_prefix='/lab4')

FLAG = 'HUNT{information_schema_is_a_map}'

HINTS = [
    "You don't know the table name — but the database stores all its own table names somewhere. In SQLite, try <code>sqlite_master</code>.",
    "Query <code>SELECT name FROM sqlite_master WHERE type='table'</code> via UNION to list all tables. One will look suspicious. Then enumerate its columns.",
    "Once you find the hidden table, query its columns from <code>sqlite_master</code> or just guess common column names, then UNION-select the flag from it.",
]


def _run_search(search: str):
    conn = get_db(4)
    # DELIBERATELY VULNERABLE
    query = f"SELECT title, author, year FROM products WHERE title LIKE '%{search}%'"
    try:
        rows = conn.execute(query).fetchall()
        conn.close()
        return [dict(r) for r in rows], None, query
    except Exception as e:
        conn.close()
        return None, str(e), query


@lab4_bp.route('/', methods=['GET'])
def index():
    search = request.args.get('q', '')
    rows, error, query = None, None, None
    if search or request.args.get('submitted'):
        log_payload(4, search, 'search query')
        rows, error, query = _run_search(search)
    return render_template(
        'labs/lab4.html',
        rows=rows, error=error, query=query, search=search,
        solved=is_solved(4),
        hints=HINTS, hints_revealed=get_hints_revealed(4),
        payload_log=get_payload_log(4),
        flag_error=session.pop('lab4_flag_error', False),
    )


@lab4_bp.route('/hint/<int:n>', methods=['POST'])
def hint(n):
    if 1 <= n <= 3:
        reveal_hint(4, n)
    return redirect(url_for('lab4.index'))


@lab4_bp.route('/flag', methods=['POST'])
def submit_flag():
    submitted = request.form.get('flag', '')
    if check_flag(4, submitted, FLAG):
        mark_solved(4)
    else:
        session['lab4_flag_error'] = True
    return redirect(url_for('lab4.index'))


@lab4_bp.route('/reset', methods=['POST'])
def reset():
    from setup_db import init_lab4
    init_lab4()
    session.pop('payload_log', None)
    return redirect(url_for('lab4.index'))


@lab4_bp.route('/interview')
def interview():
    return render_template('labs/lab4_interview.html', solved=is_solved(4))
