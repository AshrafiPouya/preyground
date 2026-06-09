from flask import Blueprint, render_template, request, session, redirect, url_for
from .base import get_db, log_payload, get_payload_log, mark_solved, is_solved, check_flag, reveal_hint, get_hints_revealed

lab5_bp = Blueprint('lab5', __name__, url_prefix='/lab5')

FLAG = 'HUNT{one_bit_at_a_time}'

HINTS = [
    "The page only tells you 'Account found' or 'No account'. That's one bit of information per request — enough to ask true/false questions about the data.",
    "Try injecting a condition after the username: <code>admin' AND 1=1--</code> (should say 'found'), then <code>admin' AND 1=2--</code> (should say 'not found'). You control the truth value.",
    "Extract the flag char-by-char: <code>admin' AND SUBSTR((SELECT flag FROM users WHERE username='admin'),1,1)='H'--</code>. Step through position and character until the page says 'found' for each.",
]


def _check_user(username_input: str) -> bool:
    conn = get_db(5)
    # DELIBERATELY VULNERABLE
    query = f"SELECT 1 FROM users WHERE username = '{username_input}'"
    try:
        row = conn.execute(query).fetchone()
        conn.close()
        return row is not None
    except Exception:
        conn.close()
        return False


@lab5_bp.route('/', methods=['GET'])
def index():
    username = request.args.get('username', '')
    exists = None
    if username or request.args.get('submitted'):
        log_payload(5, username, 'membership check')
        exists = _check_user(username)
    return render_template(
        'labs/lab5.html',
        username=username, exists=exists,
        solved=is_solved(5),
        hints=HINTS, hints_revealed=get_hints_revealed(5),
        payload_log=get_payload_log(5),
        flag_error=session.pop('lab5_flag_error', False),
    )


@lab5_bp.route('/hint/<int:n>', methods=['POST'])
def hint(n):
    if 1 <= n <= 3:
        reveal_hint(5, n)
    return redirect(url_for('lab5.index'))


@lab5_bp.route('/flag', methods=['POST'])
def submit_flag():
    submitted = request.form.get('flag', '')
    if check_flag(5, submitted, FLAG):
        mark_solved(5)
    else:
        session['lab5_flag_error'] = True
    return redirect(url_for('lab5.index'))


@lab5_bp.route('/reset', methods=['POST'])
def reset():
    from setup_db import init_lab5
    init_lab5()
    session.pop('payload_log', None)
    return redirect(url_for('lab5.index'))


@lab5_bp.route('/interview')
def interview():
    return render_template('labs/lab5_interview.html', solved=is_solved(5))
