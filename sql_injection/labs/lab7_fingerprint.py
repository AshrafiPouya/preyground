from flask import Blueprint, render_template, request, session, redirect, url_for
from .base import get_db, log_payload, get_payload_log, mark_solved, is_solved, check_flag, reveal_hint, get_hints_revealed

lab7_bp = Blueprint('lab7', __name__, url_prefix='/lab7')

FLAG = 'HUNT{know_your_database}'

HINTS = [
    "Your payloads from earlier labs might not all work here. Different databases speak different SQL dialects. Start by trying to determine which DB engine you're talking to.",
    "Each DBMS has a version function: <code>version()</code> (PostgreSQL/MySQL), <code>@@version</code> (MSSQL), <code>sqlite_version()</code> (SQLite). Try them and see which one works.",
    "Once you identify the engine via version output, adapt: comments (<code>--</code> vs <code>#</code>), concatenation (<code>||</code> vs <code>CONCAT()</code>), and system tables differ per DB.",
]


def _run_query(product_id: str):
    conn = get_db(7)
    # DELIBERATELY VULNERABLE
    query = f"SELECT name, category, price FROM inventory WHERE id = {product_id}"
    try:
        rows = conn.execute(query).fetchall()
        conn.close()
        return [dict(r) for r in rows], None, query
    except Exception as e:
        conn.close()
        return None, str(e), query


@lab7_bp.route('/', methods=['GET'])
def index():
    product_id = request.args.get('id', '1')
    log_payload(7, product_id, 'id parameter')
    rows, error, query = _run_query(product_id)
    return render_template(
        'labs/lab7.html',
        rows=rows, error=error, query=query, product_id=product_id,
        solved=is_solved(7),
        hints=HINTS, hints_revealed=get_hints_revealed(7),
        payload_log=get_payload_log(7),
        flag_error=session.pop('lab7_flag_error', False),
    )


@lab7_bp.route('/hint/<int:n>', methods=['POST'])
def hint(n):
    if 1 <= n <= 3:
        reveal_hint(7, n)
    return redirect(url_for('lab7.index'))


@lab7_bp.route('/flag', methods=['POST'])
def submit_flag():
    submitted = request.form.get('flag', '')
    if check_flag(7, submitted, FLAG):
        mark_solved(7)
    else:
        session['lab7_flag_error'] = True
    return redirect(url_for('lab7.index'))


@lab7_bp.route('/reset', methods=['POST'])
def reset():
    from setup_db import init_lab7
    init_lab7()
    session.pop('payload_log', None)
    return redirect(url_for('lab7.index'))


@lab7_bp.route('/interview')
def interview():
    return render_template('labs/lab7_interview.html', solved=is_solved(7))
