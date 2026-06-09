import sqlite3
from flask import Blueprint, render_template, request, session, redirect, url_for
from .base import get_db, log_payload, get_payload_log, mark_solved, is_solved, check_flag, reveal_hint, get_hints_revealed, get_last_extracted_error

lab1_bp = Blueprint('lab1', __name__, url_prefix='/lab1')

FLAG = 'HUNT{error_messages_leak_data}'

HINTS = [
    "What happens when you put something unexpected in the <code>id</code> value — like a single quote, a word, or a math expression? Try it.",
    "The error message is telling you about how your input is used in the SQL query. Notice you're in numeric context — no quotes around the value. You may not need one.",
    "Look up error-based SQL injection using <code>extractvalue()</code> — it leaks a value inside the error text. Try: <code>1 AND extractvalue(1,(SELECT flag FROM secrets))</code>",
]


def _run_query(raw_id: str):
    conn = get_db(1)
    # DELIBERATELY VULNERABLE — input concatenated directly into SQL
    query = f"SELECT title, author, price FROM products WHERE id = {raw_id}"
    try:
        rows = conn.execute(query).fetchall()
        conn.close()
        return rows, None, query
    except Exception:
        conn.close()
        # Prefer the human-readable message captured by extractvalue/updatexml
        err_msg = get_last_extracted_error() or f"SQL error near: {raw_id!r}"
        return None, err_msg, query


@lab1_bp.route('/', methods=['GET'])
def index():
    product_id = request.args.get('id', '1')
    log_payload(1, product_id, 'id parameter')
    rows, error, query = _run_query(product_id)
    return render_template(
        'labs/lab1.html',
        rows=rows,
        error=error,
        query=query,
        product_id=product_id,
        solved=is_solved(1),
        hints=HINTS,
        hints_revealed=get_hints_revealed(1),
        payload_log=get_payload_log(1),
        verbose_errors=session.get('lab1_verbose', True),
    )


@lab1_bp.route('/hint/<int:n>', methods=['POST'])
def hint(n):
    if 1 <= n <= 3:
        reveal_hint(1, n)
    return redirect(url_for('lab1.index'))


@lab1_bp.route('/flag', methods=['POST'])
def submit_flag():
    submitted = request.form.get('flag', '')
    if check_flag(1, submitted, FLAG):
        mark_solved(1)
    else:
        session['lab1_flag_error'] = True
    return redirect(url_for('lab1.index'))


@lab1_bp.route('/toggle-verbose', methods=['POST'])
def toggle_verbose():
    session['lab1_verbose'] = not session.get('lab1_verbose', True)
    return redirect(url_for('lab1.index'))


@lab1_bp.route('/reset', methods=['POST'])
def reset():
    from setup_db import init_lab1
    init_lab1()
    session.pop('payload_log', None)
    return redirect(url_for('lab1.index'))


@lab1_bp.route('/interview')
def interview():
    return render_template('labs/lab1_interview.html', solved=is_solved(1))
