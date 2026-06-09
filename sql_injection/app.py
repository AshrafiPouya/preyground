import os
from flask import Flask, render_template, session, redirect, url_for
import config
from setup_db import init_all

app = Flask(__name__)
app.secret_key = config.SECRET_KEY
app.config['DEBUG'] = config.DEBUG

# Ensure data directory and databases exist on startup
os.makedirs(config.DATA_DIR, exist_ok=True)
if not os.path.exists(os.path.join(config.DATA_DIR, 'lab1.db')):
    init_all()

from labs import ALL_BLUEPRINTS
for bp in ALL_BLUEPRINTS:
    app.register_blueprint(bp)


@app.route('/')
def index():
    solved = session.get('solved', [])
    return render_template('index.html', labs=config.LABS, solved=solved)


@app.route('/reset-progress', methods=['POST'])
def reset_progress():
    session.clear()
    return redirect(url_for('index'))


@app.context_processor
def inject_globals():
    return {
        'solved_labs': session.get('solved', []),
        'total_labs': len(config.LABS),
    }


if __name__ == '__main__':
    app.run(host='0.0.0.0', port=5000, debug=config.DEBUG)
