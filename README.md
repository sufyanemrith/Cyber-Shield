# Cyber-Shield — iPHARMATIC

A University of Mauritius **ICT2213Y(3): Web Technologies and Security** group project. Cyber-Shield is the team's repository; **iPHARMATIC** is the pharmacy web application described in our requirements.

## Project purpose

The proposed application extends a community pharmacy's services online: customers will browse medicines, place orders, upload prescriptions and track deliveries. Pharmacists will review prescriptions before relevant orders can progress. Administrators and delivery riders will receive distinct permissions appropriate to their roles.

This is an academic demonstration using fictional data. A production pharmacy service is outside the current deliverable.

## Current scope and evidence

| Deliverable | Evidence / implementation | Status |
| --- | --- | --- |
| Week 3: functional requirements and design | [Original requirements](docs/week-03/functional-requirements-original.md), [design reference](docs/week-03/design-links.md) | Provided; export readable UML, ERD, flowchart and Figma frames |
| Week 5: database design and implementation | [Original SQL export](database/week-05-schema.sql) | Provided; contains eight tables and foreign keys |
| Week 10: website demo, slides, code explanation, contributions and repository | [Reference scope and checks](docs/week-10/demo-checklist.md) | Authentication reference prepared; PHP/MySQL execution and presentation pending |
| Week 20: changes since Week 10, final demo, contributions and repository | [Final deliverable plan](docs/week-20/README.md) | Planned |

Update these statuses only when the team has tested and demonstrated the corresponding work. The reference code was prepared separately from the live repository; existing remote contents and branch names were not verified.

## Week 10 reference

Included: customer signup, login, logout, a session-protected confirmation page, responsive forms and a jQuery Show password enhancement. A small home page supplies working navigation anchors for About us and FAQs.

The two security techniques selected for this demonstration are:

1. **Password hashing and verification** using PHP `password_hash()` and `password_verify()`. Only a salted hash is inserted into `customer.password_hash`.
2. **Prepared SQL statements** using PDO `prepare()` and bound values in `execute()`. Submitted values are not concatenated into SQL.

Ordinary input validation, HTML encoding and session handling support the example. They do not complete the security requirements. CSRF tokens, rate limiting, idle expiry, email verification, complete role/ownership checks and safe private prescription uploads remain planned. Do not expose this limited reference as a finished public service.

## Technology

- PHP 8.2+ with `pdo_mysql` and `mbstring` enabled.
- MySQL / MariaDB. The supplied export was generated with MariaDB 10.4.32.
- HTML5, CSS and jQuery 3.7.1, pinned for this example. jQuery comes from its official CDN; Internet access is needed for the enhancement only.
- Git and GitHub for version history, collaboration and individual contribution evidence.

Laravel, AJAX, JSON and JSON Schema are planned for the later assessed demonstration; they are not implemented here.

## Repository layout

| Path | Purpose |
| --- | --- |
| `public/` | Browser-facing PHP pages and CSS/JS assets; use this as the server document root |
| `app/` | Shared PHP setup and page templates |
| `config/database.example.php` | Safe settings template; copy to the ignored `database.php` locally |
| `database/week-05-schema.sql` | Original historical database deliverable |
| `database/migrations/` | Later schema changes, applied in numbered order |
| `docs/assignment/` | Lecturer's brief |
| `docs/week-03/` | Functional requirements and design evidence |
| `docs/week-10/` | Original frontend, demo checklist and line-by-line explanation |
| `docs/week-20/` | Final changes and presentation evidence |
| `.github/` | Pull request template |

Maintain one active application rather than copying all code into a new folder every week. Use documentation folders and Git tags for the assessed milestones.

## Run locally on Windows with XAMPP

1. Start Apache (for phpMyAdmin) and MySQL in XAMPP.
2. In phpMyAdmin create `ipharmatic_database` using `utf8mb4_general_ci`. Select it and import `database/week-05-schema.sql`. Do not reimport into an existing populated database without a backup and review.
3. Review duplicate emails with `SELECT email, COUNT(*) FROM customer GROUP BY email HAVING COUNT(*) > 1;`. Resolve any duplicates intentionally; then import `database/migrations/001_unique_customer_email.sql` **once**.
4. Create a local application database account with only `SELECT` and `INSERT` on `ipharmatic_database.customer`. Instructions are in the [setup guide](docs/setup-and-github-guide.md).
5. From the project root, use PowerShell:

```powershell
Copy-Item config/database.example.php config/database.php
```

6. Edit `config/database.php` with that local account and password. This file is ignored by Git.
7. From the same project root, start the development server:

```powershell
& 'C:\xampp\php\php.exe' -S localhost:8000 -t public
```

8. Open http://localhost:8000. Use fake accounts to run the [demo checks](docs/week-10/demo-checklist.md). Stop the server with Ctrl+C.

On macOS/Linux with PHP installed: `cp config/database.example.php config/database.php`, edit it, then `php -S localhost:8000 -t public`.

VS Code Live Server and GitHub Pages cannot execute this PHP/MySQL application. GitHub stores the code; a PHP server executes it. This built-in PHP server is for local development.

## Team contribution

| Member | Responsibility | Evidence |
| --- | --- | --- |
| @sufyanemrith — team leader | Integration, repository organization and own assigned code | Link actual commits, Issues and PRs |
| Add actual teammate username | Add agreed feature and files | Link actual commits, Issues and PRs |

Replace the placeholder row with one row for each team member; do not invent contributions. Each member commits using their own GitHub-linked identity and explains their own code in the demo.

## Workflow

See [CONTRIBUTING.md](CONTRIBUTING.md) for member branches, leader pushes, pull requests, merging and conflict resolution. See [setup-and-github-guide.md](docs/setup-and-github-guide.md) for the ordered repository cleanup steps.

Use a stable `main` branch, member/feature branches for work in progress, meaningful commits, Issues for assigned requirements, and milestone tags after verified demonstrations.

## Tests and current limits

Static package consistency checks and JavaScript syntax checks passed during preparation. A browser runtime was unavailable, so visual layout was not verified. PHP/MySQL were also unavailable; database import, migration, PHP execution and end-to-end authentication remain **untested** until the team completes the supplied local checklist.

There is no implemented catalogue search, shopping cart, checkout, payment integration, inventory workflow, prescription review, staff/admin/rider dashboard, AJAX endpoint, JSON Schema validation or Laravel application in this reference. These must be built against the approved Week 3 requirements.
