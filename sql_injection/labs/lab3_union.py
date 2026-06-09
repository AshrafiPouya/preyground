from flask import Blueprint, render_template, request, session, redirect, url_for
from .base import get_db, log_payload, get_payload_log, mark_solved, is_solved, check_flag, reveal_hint, get_hints_revealed

lab3_bp = Blueprint('lab3', __name__, url_prefix='/lab3')

FLAG = 'HUNT{union_select_all_the_things}'

HINTS = [
    "Search results come back in a fixed set of columns. To add your own data via UNION, your injected query must return the same number of columns.",
    "Use <code>ORDER BY 1--</code>, <code>ORDER BY 2--</code>, etc. to find the column count. When it errors, you've gone one too many. Alternatively try <code>UNION SELECT NULL,NULL,NULL--</code>.",
    "Once you know the column count, replace one NULL with <code>(SELECT flag FROM vault)</code>. Note: the third column is numeric — put your string in column 1 or 2.",
]


def _run_search(search: str):
    conn = get_db(3)
    # DELIBERATELY VULNERABLE
    query = f"SELECT title, author, year FROM products WHERE title LIKE '%{search}%'"
    try:
        rows = conn.execute(query).fetchall()
        conn.close()
        return [dict(r) for r in rows], None, query
    except Exception as e:
        conn.close()
        return None, str(e), query


@lab3_bp.route('/', methods=['GET'])
def index():
    search = request.args.get('q', '')
    rows, error, query = None, None, None
    if search or request.args.get('submitted'):
        log_payload(3, search, 'search query')
        rows, error, query = _run_search(search)
    return render_template(
        'labs/lab3.html',
        rows=rows, error=error, query=query, search=search,
        solved=is_solved(3),
        hints=HINTS, hints_revealed=get_hints_revealed(3),
        payload_log=get_payload_log(3),
        flag_error=session.pop('lab3_flag_error', False),
    )


@lab3_bp.route('/hint/<int:n>', methods=['POST'])
def hint(n):
    if 1 <= n <= 3:
        reveal_hint(3, n)
    return redirect(url_for('lab3.index'))


@lab3_bp.route('/flag', methods=['POST'])
def submit_flag():
    submitted = request.form.get('flag', '')
    if check_flag(3, submitted, FLAG):
        mark_solved(3)
    else:
        session['lab3_flag_error'] = True
    return redirect(url_for('lab3.index'))


@lab3_bp.route('/reset', methods=['POST'])
def reset():
    from setup_db import init_lab3
    init_lab3()
    session.pop('payload_log', None)
    return redirect(url_for('lab3.index'))


@lab3_bp.route('/interview')
def interview():
    return render_template('labs/lab3_interview.html', solved=is_solved(3))
