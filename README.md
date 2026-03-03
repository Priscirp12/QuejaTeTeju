# Quejas Municipales

This repository contains a simple complaint/suggestion portal for a municipal government. It includes:

- An **Angular / Ionic** frontend (targeting both web and mobile) under `src/`
- A **PHP backend** (plain `*.php` scripts) exposing a small API for authentication and complaints.
- A **MySQL database** schema and stored procedures in `quejas_municipales.sql`.

## Getting started (development)

1. **Prerequisites**
   - [WAMP](https://www.wampserver.com/) or similar PHP/MySQL environment. The project is located in `c:\wamp64\www\PETICIONES` so the API is served at `http://localhost/PETICIONES/api/...`.
   - [Node.js](https://nodejs.org/) (14+), `npm` installed.
   - Optional: `ng` / Ionic CLI if running outside package scripts.

2. **Database**
   - Create a MySQL database named `quejas_municipales`.
   - Import `quejas_municipales.sql` with phpMyAdmin or `mysql` CLI.
   - Ensure the database credentials in `src/backend/config/configDatabase.php` match your environment.
   - The SQL file defines tables, views, and stored procedures used by the PHP API.

3. **Backend**
   - The PHP endpoints are in `api/` (and mirrored under `src/backend/api/`). When using WAMP, use the `api/` directory since it's directly accessible from the web root.
   - Start Apache/MySQL via WAMP. Verify you can visit `http://localhost/PETICIONES/api/auth/login.php` (you should get a CORS header or a `400` response).

4. **Frontend**
   - Install dependencies:
     ```bash
     cd c:\wamp64\www\PETICIONES
     npm install
     ```
   - Development server (Angular/Ionic):
     ```bash
     npm run start
     # or ng serve --proxy-config proxy.conf.json
     ```
   - The app will run at `http://localhost:4200`.
   - The frontend reads `environment.apiUrl` to determine the backend URL. By default this points to `http://localhost/PETICIONES/api`.
     a) You can also use the provided proxy (`/api` → `http://localhost/PETICIONES/api`) by leaving the URL as `/api` and running with `--proxy-config`.

5. **Usage**
   - Register a new user via the form, then log in.
   - Normal users can create complaints with optional file attachments (images/pdf).
   - An administrator (set `rol='admin'` manually in the database) can view all complaints and update their status.

## Notes & troubleshooting

- **CORS** is handled by the PHP scripts via `Access-Control-Allow-Origin: *` header.
- **Authentication** uses a simple base64 token; the `Authorization: Bearer ...` header is required for protected endpoints. The frontend sets this automatically after login.
- If you see 404 errors for `api/...`, check that the backend path matches the value of `environment.apiUrl` and that WAMP is running.
- When building the Angular app, style budgets were increased to avoid errors (`angular.json`).

## Suggestions & improvements

- Replace the simple token with JWT and add expiry.
- Implement input sanitization and CSRF protection on the backend.
- Add pagination to complaint listings for scalability.
- Create Angular interceptors to handle auth headers and global error handling.
- Convert PHP endpoints to a framework (Laravel, Symfony) if the project grows.

---

This readme should help you get the project up and running and serve as a reference when debugging common issues.