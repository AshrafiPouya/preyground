FROM python:3.12-slim

WORKDIR /app

COPY requirements.txt .
RUN pip install --no-cache-dir -r requirements.txt

COPY . .

# Create the data directory for SQLite databases
RUN mkdir -p /app/data

# Initialize all lab databases at build time
RUN python setup_db.py

EXPOSE 5000

ENV FLASK_APP=app.py
ENV FLASK_DEBUG=0
ENV SECRET_KEY=change-me-in-production

CMD ["python", "-m", "flask", "run", "--host=0.0.0.0", "--port=5000"]
