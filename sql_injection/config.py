import os

BASE_DIR = os.path.dirname(__file__)
DATA_DIR = os.environ.get('DATA_DIR', os.path.join(BASE_DIR, 'data'))

SECRET_KEY = os.environ.get('SECRET_KEY', 'hunt-labs-dev-key-change-in-prod')
DEBUG = os.environ.get('FLASK_DEBUG', '1') == '1'

LABS = [
    {
        "id": 1, "slug": "lab1",
        "title": "Detection & Error-Based Injection",
        "difficulty": "beginner",
        "interview_chance": 5, "hunt_chance": 3,
        "description": "Trigger database errors and read the flag out of verbose error messages.",
        "concepts": ["Numeric context", "Verbose errors", "extractvalue()", "Detection"],
        "flag": "HUNT{error_messages_leak_data}",
    },
    {
        "id": 2, "slug": "lab2",
        "title": "Authentication Bypass",
        "difficulty": "beginner",
        "interview_chance": 5, "hunt_chance": 2,
        "description": "Break a login form using tautologies and SQL comment injection.",
        "concepts": ["Tautology bypass", "Comment injection", "Boolean logic", "Auth"],
        "flag": "HUNT{or_1_equals_1_classic}",
    },
    {
        "id": 3, "slug": "lab3",
        "title": "UNION-Based Injection",
        "difficulty": "intermediate",
        "interview_chance": 4, "hunt_chance": 3,
        "description": "Append a UNION SELECT to pull data from another table into visible results.",
        "concepts": ["Column counting", "Type matching", "UNION SELECT", "Reflected output"],
        "flag": "HUNT{union_select_all_the_things}",
    },
    {
        "id": 4, "slug": "lab4",
        "title": "Schema Enumeration",
        "difficulty": "intermediate",
        "interview_chance": 3, "hunt_chance": 4,
        "description": "Query information_schema to find hidden tables and extract an obfuscated flag.",
        "concepts": ["information_schema", "Table/column discovery", "group_concat", "Mapping"],
        "flag": "HUNT{information_schema_is_a_map}",
    },
    {
        "id": 5, "slug": "lab5",
        "title": "Boolean-Based Blind Injection",
        "difficulty": "intermediate",
        "interview_chance": 4, "hunt_chance": 5,
        "description": "Extract data character-by-character using only true/false page responses.",
        "concepts": ["Blind injection", "SUBSTRING", "Binary inference", "No output"],
        "flag": "HUNT{one_bit_at_a_time}",
    },
    {
        "id": 6, "slug": "lab6",
        "title": "Time-Based Blind Injection",
        "difficulty": "advanced",
        "interview_chance": 4, "hunt_chance": 5,
        "description": "Use conditional SLEEP() delays to extract data when the response never changes.",
        "concepts": ["Timing side-channel", "SLEEP()", "Conditional delays", "pg_sleep"],
        "flag": "HUNT{time_is_a_side_channel}",
    },
    {
        "id": 7, "slug": "lab7",
        "title": "DB Fingerprinting",
        "difficulty": "advanced",
        "interview_chance": 3, "hunt_chance": 4,
        "description": "Identify the DBMS via version functions and syntax quirks, then adapt your payload.",
        "concepts": ["version()", "@@version", "Dialect differences", "Fingerprinting"],
        "flag": "HUNT{know_your_database}",
    },
    {
        "id": 8, "slug": "lab8",
        "title": "Second-Order SQL Injection",
        "difficulty": "advanced",
        "interview_chance": 4, "hunt_chance": 3,
        "description": "Store a payload that fires later in a different, unparameterized query.",
        "concepts": ["Stored payloads", "Trust boundaries", "Data flow", "Latent injection"],
        "flag": "HUNT{stored_now_exploited_later}",
    },
    {
        "id": 9, "slug": "lab9",
        "title": "WAF / Filter Bypass",
        "difficulty": "advanced",
        "interview_chance": 3, "hunt_chance": 5,
        "description": "Evade a keyword blacklist using case variation, comments, encoding, and hex.",
        "concepts": ["Blacklist bypass", "Case variation", "Inline comments", "Hex encoding"],
        "flag": "HUNT{blacklists_always_lose}",
    },
    {
        "id": 10, "slug": "lab10",
        "title": "Capstone: The Hunt",
        "difficulty": "expert",
        "interview_chance": 4, "hunt_chance": 5,
        "description": "A realistic app with red herrings. Find the one injectable input and chain techniques to extract the final flag.",
        "concepts": ["Recon", "Chaining", "Methodology", "Recognition"],
        "flag": "HUNT{you_are_now_a_hunter}",
    },
]
