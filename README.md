# Hunt Labs

A collection of hands-on web security labs (OWASP + extra) covering SQL Injection, Command Injection/RCE/SSTI, Client-Side/Cross-Origin attacks, XSS and its growing ...

## Requirements

- [Docker](https://docs.docker.com/get-docker/) & [Docker Compose](https://docs.docker.com/compose/install/)


## Quick Start

Each lab lives in its own directory. From any lab folder:

```bash
docker compose up --build
```

| Lab | Directory |
|-----|-----------|
| SQL Injection | `sql_injection/` |
| CMDi / RCE / SSTI | `cmdi_rce_ssti/` |
| Client-Side / Cross-Origin | `client_side_xorigin/` |
