import time
import re
from flask import Blueprint, render_template, request, session, redirect, url_for
from .base import get_db, log_payload, get_payload_log, mark_solved, is_solved, check_flag, reveal_hint, get_hints_revealed

lab6_bp = Blueprint('lab6', __name__, url_prefix='/lab6')

FLAG = 'HUNT{time_is_a_side_channel}'

HINTS = [
    "Every response looks identical: 'Thanks, we'll be in touch.' But what if you could make the server *wait* before responding when a condition is true?",
    "The <code>sleep(N)</code> function pauses execution for N seconds. The email lands inside an INSERT VALUES clause — close the string with a quote and add a boolean condition that calls sleep, then close the VALUES expression properly (no <code>--</code> needed).",
    "INSERT context payload: <code>x@x.com' AND sleep(3) AND '1'='1</code> — the VALUES clause evaluates to a boolean, calling sleep as a side-effect. For extraction: <code>x@x.com' AND (SELECT CASE WHEN SUBSTR(flag,1,1)='H' THEN sleep(3) ELSE 0 END FROM secrets) AND '1'='1</code>",
]


def _process_signup(email: str) -> float:
    t0 = time.time()
    conn = get_db(6)
    # DELIBERATELY VULNERABLE — email concatenated directly into INSERT
    query = f"INSERT OR IGNORE INTO signups (email) VALUES ('{email}')"
    try:
        conn.execute(query)
        conn.commit()
    except Exception:
        pass
    conn.close()
    return time.time() - t0


@lab6_bp.route('/', methods=['GET'])
def index():
    return render_template(
        'labs/lab6.html',
        solved=is_solved(6),
        hints=HINTS, hints_revealed=get_hints_revealed(6),
        payload_log=get_payload_log(6),
        signup_result=session.pop('lab6_signup_result', None),
        flag_error=session.pop('lab6_flag_error', False),
    )


@lab6_bp.route('/signup', methods=['POST'])
def signup():
    email = request.form.get('email', '')
    log_payload(6, email, 'newsletter signup')
    elapsed = _process_signup(email)
    session['lab6_signup_result'] = {
        'email': email,
        'elapsed_ms': round(elapsed * 1000),
    }
    return redirect(url_for('lab6.index'))


@lab6_bp.route('/hint/<int:n>', methods=['POST'])
def hint(n):
    if 1 <= n <= 3:
        reveal_hint(6, n)
    return redirect(url_for('lab6.index'))


@lab6_bp.route('/flag', methods=['POST'])
def submit_flag():
    submitted = request.form.get('flag', '')
    if check_flag(6, submitted, FLAG):
        mark_solved(6)
    else:
        session['lab6_flag_error'] = True
    return redirect(url_for('lab6.index'))


@lab6_bp.route('/reset', methods=['POST'])
def reset():
    from setup_db import init_lab6
    init_lab6()
    session.pop('payload_log', None)
    return redirect(url_for('lab6.index'))


@lab6_bp.route('/interview')
def interview():
    return render_template('labs/lab6_interview.html', solved=is_solved(6))
