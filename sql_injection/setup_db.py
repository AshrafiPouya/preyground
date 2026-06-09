import sqlite3
import os
import sys

BASE_DIR = os.path.dirname(__file__)
DATA_DIR = os.path.join(BASE_DIR, 'data')


def _conn(lab_num: int) -> sqlite3.Connection:
    os.makedirs(DATA_DIR, exist_ok=True)
    return sqlite3.connect(os.path.join(DATA_DIR, f'lab{lab_num}.db'))


def init_lab1():
    conn = _conn(1)
    conn.executescript("""
        DROP TABLE IF EXISTS products;
        DROP TABLE IF EXISTS secrets;
        CREATE TABLE products (
            id INTEGER PRIMARY KEY,
            title TEXT,
            author TEXT,
            price REAL
        );
        INSERT INTO products VALUES
            (1, 'The Art of Exploitation', 'Jon Erickson', 29.99),
            (2, 'Hacking: The Next Generation', 'Nitesh Dhanjani', 24.99),
            (3, 'The Web Application Hacker''s Handbook', 'Stuttard & Pinto', 39.99),
            (4, 'Black Hat Python', 'Justin Seitz', 22.99),
            (5, 'Penetration Testing', 'Georgia Weidman', 34.99);
        CREATE TABLE secrets (
            id INTEGER PRIMARY KEY,
            flag TEXT
        );
        INSERT INTO secrets VALUES (1, 'HUNT{error_messages_leak_data}');
    """)
    conn.commit()
    conn.close()


def init_lab2():
    conn = _conn(2)
    conn.executescript("""
        DROP TABLE IF EXISTS users;
        CREATE TABLE users (
            id INTEGER PRIMARY KEY,
            username TEXT UNIQUE,
            password TEXT,
            admin_note TEXT
        );
        INSERT INTO users VALUES
            (1, 'admin', 'sup3rs3cr3t!', 'HUNT{or_1_equals_1_classic}'),
            (2, 'alice', 'password123', NULL),
            (3, 'bob', 'hunter2', NULL);
    """)
    conn.commit()
    conn.close()


def init_lab3():
    conn = _conn(3)
    conn.executescript("""
        DROP TABLE IF EXISTS products;
        DROP TABLE IF EXISTS vault;
        CREATE TABLE products (
            id INTEGER PRIMARY KEY,
            title TEXT,
            author TEXT,
            year INTEGER
        );
        INSERT INTO products VALUES
            (1, 'Clean Code', 'Robert Martin', 2008),
            (2, 'The Pragmatic Programmer', 'Hunt & Thomas', 1999),
            (3, 'Design Patterns', 'Gang of Four', 1994),
            (4, 'Refactoring', 'Martin Fowler', 1999),
            (5, 'Code Complete', 'Steve McConnell', 2004);
        CREATE TABLE vault (
            id INTEGER PRIMARY KEY,
            flag TEXT
        );
        INSERT INTO vault VALUES (1, 'HUNT{union_select_all_the_things}');
    """)
    conn.commit()
    conn.close()


def init_lab4():
    conn = _conn(4)
    conn.executescript("""
        DROP TABLE IF EXISTS products;
        DROP TABLE IF EXISTS decoy_flags;
        DROP TABLE IF EXISTS tbl_z9;
        CREATE TABLE products (
            id INTEGER PRIMARY KEY,
            title TEXT,
            author TEXT,
            year INTEGER
        );
        INSERT INTO products VALUES
            (1, 'Structure and Interpretation of Computer Programs', 'Abelson & Sussman', 1984),
            (2, 'Introduction to Algorithms', 'Cormen et al.', 1990),
            (3, 'The C Programming Language', 'Kernighan & Ritchie', 1978);
        CREATE TABLE decoy_flags (
            id INTEGER PRIMARY KEY,
            col_x4 TEXT
        );
        INSERT INTO decoy_flags VALUES
            (1, 'HUNT{nope_keep_looking}'),
            (2, 'HUNT{almost_but_not_quite}');
        CREATE TABLE tbl_z9 (
            id INTEGER PRIMARY KEY,
            col_x4 TEXT
        );
        INSERT INTO tbl_z9 VALUES (1, 'HUNT{information_schema_is_a_map}');
    """)
    conn.commit()
    conn.close()


def init_lab5():
    conn = _conn(5)
    conn.executescript("""
        DROP TABLE IF EXISTS users;
        CREATE TABLE users (
            id INTEGER PRIMARY KEY,
            username TEXT UNIQUE,
            password TEXT,
            flag TEXT
        );
        INSERT INTO users VALUES
            (1, 'admin', 'h4rdp4ssw0rd', 'HUNT{one_bit_at_a_time}'),
            (2, 'alice', 'pass1', NULL),
            (3, 'charlie', 'pass2', NULL);
    """)
    conn.commit()
    conn.close()


def init_lab6():
    conn = _conn(6)
    conn.executescript("""
        DROP TABLE IF EXISTS signups;
        DROP TABLE IF EXISTS secrets;
        CREATE TABLE signups (
            id INTEGER PRIMARY KEY,
            email TEXT UNIQUE
        );
        CREATE TABLE secrets (
            id INTEGER PRIMARY KEY,
            flag TEXT
        );
        INSERT INTO secrets VALUES (1, 'HUNT{time_is_a_side_channel}');
    """)
    conn.commit()
    conn.close()


def init_lab7():
    conn = _conn(7)
    conn.executescript("""
        DROP TABLE IF EXISTS inventory;
        DROP TABLE IF EXISTS secrets;
        CREATE TABLE inventory (
            id INTEGER PRIMARY KEY,
            name TEXT,
            category TEXT,
            price REAL
        );
        INSERT INTO inventory VALUES
            (1, 'Widget A', 'Tools', 9.99),
            (2, 'Gadget B', 'Electronics', 49.99),
            (3, 'Doohickey C', 'Misc', 4.99);
        CREATE TABLE secrets (
            id INTEGER PRIMARY KEY,
            flag TEXT
        );
        INSERT INTO secrets VALUES (1, 'HUNT{know_your_database}');
    """)
    conn.commit()
    conn.close()


def init_lab8():
    conn = _conn(8)
    conn.executescript("""
        DROP TABLE IF EXISTS users;
        DROP TABLE IF EXISTS profiles;
        DROP TABLE IF EXISTS secrets;
        CREATE TABLE users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT UNIQUE,
            password TEXT
        );
        CREATE TABLE profiles (
            id INTEGER PRIMARY KEY,
            username TEXT,
            bio TEXT,
            avatar TEXT
        );
        CREATE TABLE secrets (
            id INTEGER PRIMARY KEY,
            flag TEXT
        );
        INSERT INTO profiles VALUES (1, 'admin', 'Site administrator', 'admin.png');
        INSERT INTO profiles VALUES (2, 'alice', 'Regular user', 'alice.png');
        INSERT INTO secrets VALUES (1, 'HUNT{stored_now_exploited_later}');
    """)
    conn.commit()
    conn.close()


def init_lab9():
    conn = _conn(9)
    conn.executescript("""
        DROP TABLE IF EXISTS movies;
        DROP TABLE IF EXISTS secrets;
        CREATE TABLE movies (
            id INTEGER PRIMARY KEY,
            title TEXT,
            genre TEXT,
            year INTEGER
        );
        INSERT INTO movies VALUES
            (1, 'Hackers', 'Thriller', 1995),
            (2, 'WarGames', 'Drama', 1983),
            (3, 'Sneakers', 'Comedy', 1992),
            (4, 'The Matrix', 'Sci-Fi', 1999),
            (5, 'Mr. Robot', 'Drama', 2015);
        CREATE TABLE secrets (
            id INTEGER PRIMARY KEY,
            flag TEXT
        );
        INSERT INTO secrets VALUES (1, 'HUNT{blacklists_always_lose}');
    """)
    conn.commit()
    conn.close()


def init_lab10():
    conn = _conn(10)
    conn.executescript("""
        DROP TABLE IF EXISTS articles;
        DROP TABLE IF EXISTS tickets;
        DROP TABLE IF EXISTS accounts;
        DROP TABLE IF EXISTS products;
        DROP TABLE IF EXISTS hidden_vault;
        CREATE TABLE articles (
            id INTEGER PRIMARY KEY,
            title TEXT,
            author TEXT,
            summary TEXT
        );
        INSERT INTO articles VALUES
            (1, 'Getting Started with Bug Bounty', 'Alice', 'A beginner''s guide to bug bounty hunting.'),
            (2, 'Web App Security Fundamentals', 'Bob', 'Core concepts every hunter should know.'),
            (3, 'Recon Techniques for 2024', 'Charlie', 'Modern recon methodology.');
        CREATE TABLE tickets (
            id INTEGER PRIMARY KEY,
            subject TEXT,
            status TEXT
        );
        INSERT INTO tickets VALUES
            (1, 'Login issue', 'open'),
            (2, 'Payment error', 'closed'),
            (3, 'Feature request', 'open');
        CREATE TABLE accounts (
            id INTEGER PRIMARY KEY,
            username TEXT,
            role TEXT
        );
        INSERT INTO accounts VALUES
            (1, 'admin', 'administrator'),
            (2, 'alice', 'user'),
            (3, 'bob', 'user');
        CREATE TABLE products (
            id INTEGER PRIMARY KEY,
            name TEXT,
            description TEXT,
            price REAL
        );
        INSERT INTO products VALUES
            (1, 'Basic Plan', 'Access to standard features', 9.99),
            (2, 'Pro Plan', 'Access to all features', 29.99),
            (3, 'Enterprise Plan', 'Custom solutions', 99.99);
        CREATE TABLE hidden_vault (
            id INTEGER PRIMARY KEY,
            flag TEXT
        );
        INSERT INTO hidden_vault VALUES (1, 'HUNT{you_are_now_a_hunter}');
    """)
    conn.commit()
    conn.close()


def init_all():
    print("Initializing all lab databases...")
    for n, fn in enumerate([
        init_lab1, init_lab2, init_lab3, init_lab4, init_lab5,
        init_lab6, init_lab7, init_lab8, init_lab9, init_lab10,
    ], 1):
        fn()
        print(f"  Lab {n} ✓")
    print("All databases initialized.")


if __name__ == '__main__':
    init_all()
