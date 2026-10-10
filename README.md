# HostelCare

Hostel Complaint Tracking System built with PHP 8, MySQL, and Bootstrap 5.

## Roles
- **Admin** — manage complaints, categories, users, reports
- **Staff** — view assigned complaints and update status
- **Student** — submit and track complaints

## Deploy on Render

1. Fork/push this repo to GitHub.
2. Create a new **Web Service** on [Render](https://render.com), connect this repo, and select **Docker** as the runtime.
3. Add the following environment variables in the Render dashboard:

| Variable   | Value                          |
|------------|-------------------------------|
| `DB_HOST`  | your MySQL host               |
| `DB_NAME`  | `hostelcare`                  |
| `DB_USER`  | your MySQL username           |
| `DB_PASS`  | your MySQL password           |
| `BASE_URL` | *(leave empty for root URL)*  |

4. Use an external MySQL provider (e.g. [PlanetScale](https://planetscale.com) free tier or [FreeSQLDatabase](https://www.freesqldatabase.com)).
5. After first deploy, visit `/setup.php` once to initialise the database schema.

## Local Development (XAMPP)

Set `BASE_URL=/HostelCare` in your environment or leave the default in `config/db.php`.
