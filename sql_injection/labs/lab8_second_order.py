from flask import Blueprint, render_template, request, session, redirect, url_for
from .base import get_db, log_payload, get_payload_log, mark_solved, is_solved, check_flag, reveal_hint, get_hints_revealed

lab8_bp = Blueprint('lab8', __name__, url_prefix='/lab8')

FLAG = 'HUNT{stored_now_exploited_later}'

HINTS = [
    "Registration looks safe — your quotes get stored, not executed. So when does your stored username get used *again*? Think about every feature that reads your username back.",
    "The 'view profile' page re-reads your username from the database and plugs it into another query — one that isn't as careful. That's where the injection fires.",
    "Register with a username like <code>x' UNION SELECT flag,2,3 FROM secrets--</code> (mind the column count), then visit your profile page to trigger the second-order injection.",
]


def _register(username: str, password: str):
    conn = get_db(8)
    # Registration is safe — parameterized
    try:
        conn.execute(
            "INSERT INTO users (username, password) VALUES (?, ?)",
            (username, password)
        )
        conn.commit()
        row = conn.execute("SELECT id FROM users WHERE username = ?", (username,)).fetchone()
        conn.close()
        return row['id'] if row else None, None
    except Exception as e:
        conn.close()
        return None, str(e)


def _view_profile(user_id: int):
    conn = get_db(8)
    # Fetch the stored username (safe)
    user_row = conn.execute("SELECT username FROM users WHERE id = ?", (user_id,)).fetchone()
    if not user_row:
        conn.close()
        return None, "User not found", None
    stored_username = user_row['username']
    # DELIBERATELY VULNERABLE — stored username re-used unsafely
    query = f"SELECT username, bio, avatar FROM profiles WHERE username = '{stored_username}'"
    try:
        profile = conn.execute(query).fetchone()
        conn.close()
        return dict(profile) if profile else None, None, query
    except Exception as e:
        conn.close()
        return None, str(e), query


@lab8_bp.route('/', methods=['GET'])
def index():
    return render_template(
        'labs/lab8.html',
        solved=is_solved(8),
        hints=HINTS, hints_revealed=get_hints_revealed(8),
        payload_log=get_payload_log(8),
        register_result=session.pop('lab8_register_result', None),
        profile_result=session.pop('lab8_profile_result', None),
        flag_error=session.pop('lab8_flag_error', False),
    )


@lab8_bp.route('/register', methods=['POST'])
def register():
    username = request.form.get('username', '')
    password = request.form.get('password', '')
    log_payload(8, username, 'registration username (stored)')
    user_id, error = _register(username, password)
    session['lab8_register_result'] = {
        'username': username, 'user_id': user_id, 'error': error,
        'note': 'Username stored safely via parameterized INSERT.'
    }
    return redirect(url_for('lab8.index'))


@lab8_bp.route('/profile/<int:user_id>', methods=['GET'])
def profile(user_id):
    log_payload(8, str(user_id), 'profile view (triggers second query)')
    profile_data, error, query = _view_profile(user_id)
    session['lab8_profile_result'] = {
        'user_id': user_id, 'profile': profile_data,
        'error': error, 'query': query,
        'note': 'Stored username re-used in unparameterized SELECT.'
    }
    return redirect(url_for('lab8.index'))


@lab8_bp.route('/hint/<int:n>', methods=['POST'])
def hint(n):
    if 1 <= n <= 3:
        reveal_hint(8, n)
    return redirect(url_for('lab8.index'))


@lab8_bp.route('/flag', methods=['POST'])
def submit_flag():
    submitted = request.form.get('flag', '')
    if check_flag(8, submitted, FLAG):
        mark_solved(8)
    else:
        session['lab8_flag_error'] = True
    return redirect(url_for('lab8.index'))


@lab8_bp.route('/reset', methods=['POST'])
def reset():
    from setup_db import init_lab8
    init_lab8()
    session.pop('payload_log', None)
    session.pop('lab8_register_result', None)
    session.pop('lab8_profile_result', None)
    return redirect(url_for('lab8.index'))


@lab8_bp.route('/interview')
def interview():
    return render_template('labs/lab8_interview.html', solved=is_solved(8))
