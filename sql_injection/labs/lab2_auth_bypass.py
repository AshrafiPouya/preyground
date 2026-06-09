import sqlite3
from flask import Blueprint, render_template, request, session, redirect, url_for
from .base import get_db, log_payload, get_payload_log, mark_solved, is_solved, check_flag, reveal_hint, get_hints_revealed

lab2_bp = Blueprint('lab2', __name__, url_prefix='/lab2')

FLAG = 'HUNT{or_1_equals_1_classic}'

HINTS = [
    "The username and password you type land inside single quotes in a SQL query. What if you closed that quote yourself?",
    "You can make the password check irrelevant — either by making a condition always true, or by commenting out the rest of the query with <code>--</code>.",
    "Try a username of <code>admin'--</code> (with any password). Everything after <code>--</code> is ignored by the database.",
]


def _try_login(username: str, password: str):
    conn = get_db(2)
    # DELIBERATELY VULNERABLE — string concatenation in auth query
    query = f"SELECT id, username, admin_note FROM users WHERE username = '{username}' AND password = '{password}'"
    try:
        row = conn.execute(query).fetchone()
        conn.close()
        return row, None, query
    except Exception as e:
        conn.close()
        return None, str(e), query


@lab2_bp.route('/', methods=['GET'])
def index():
    return render_template(
        'labs/lab2.html',
        solved=is_solved(2),
        hints=HINTS,
        hints_revealed=get_hints_revealed(2),
        payload_log=get_payload_log(2),
        login_result=session.pop('lab2_login_result', None),
        flag_error=session.pop('lab2_flag_error', False),
    )


@lab2_bp.route('/login', methods=['POST'])
def login():
    username = request.form.get('username', '')
    password = request.form.get('password', '')
    log_payload(2, f"username={username!r}  password={password!r}", 'login form')
    row, error, query = _try_login(username, password)
    result = {
        'username': username,
        'password': password,
        'query': query,
        'error': error,
        'row': dict(row) if row else None,
        'success': row is not None,
        'is_admin': (row['username'] == 'admin') if row else False,
    }
    session['lab2_login_result'] = result
    return redirect(url_for('lab2.index'))


@lab2_bp.route('/hint/<int:n>', methods=['POST'])
def hint(n):
    if 1 <= n <= 3:
        reveal_hint(2, n)
    return redirect(url_for('lab2.index'))


@lab2_bp.route('/flag', methods=['POST'])
def submit_flag():
    submitted = request.form.get('flag', '')
    if check_flag(2, submitted, FLAG):
        mark_solved(2)
    else:
        session['lab2_flag_error'] = True
    return redirect(url_for('lab2.index'))


@lab2_bp.route('/reset', methods=['POST'])
def reset():
    from setup_db import init_lab2
    init_lab2()
    session.pop('payload_log', None)
    session.pop('lab2_login_result', None)
    return redirect(url_for('lab2.index'))


@lab2_bp.route('/interview')
def interview():
    return render_template('labs/lab2_interview.html', solved=is_solved(2))
