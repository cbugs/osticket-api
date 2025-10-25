# osTicket REST API (v1)

**Note:** This is an unofficial, community-made REST API extension for [osTicket](https://osticket.com/). It is not maintained by the osTicket team.

A plug-and-play REST API to manage tickets directly via HTTP requests, including replies and internal notes.

---

## Features

* CRUD support for tickets:

  * `GET` → list tickets with optional filters and pagination, includes all ticket responses
  * `POST` → create new tickets
  * `PUT/PATCH` → update ticket status by ID
  * `DELETE` → remove tickets by ID
* Returns full thread of ticket responses (`responses` field) including internal notes
* Uses existing osTicket API key for authentication
* Returns simple JSON responses
* Optional Postman collection for testing

---

## Installation

1. Locate your osTicket installation directory, e.g.:

   ```
   /var/www/html/osticket/
   ```

2. Copy the API folder into osTicket's `api` folder:

   ```bash
   cd /var/www/html/osticket/api
   cp -r /path/to/osticket-rest-api/v1 ./v1
   ```

   Folder structure after installation:

   ```
   osticket/
   └── api/
       ├── http.php
       └── v1/
           └── tickets.php
   ```

3. Set proper permissions:

   ```bash
   chown -R www-data:www-data /var/www/html/osticket/api/v1
   chmod -R 755 /var/www/html/osticket/api/v1
   ```

4. Generate an API Key in osTicket:

   * Go to Admin Panel → Manage → API → Add New API Key
   * Assign a name and optionally restrict to specific IPs
   * Copy the generated key. This is the same key used for REST API requests.

---

## Authentication

All requests require the API key in the header:

```
X-API-Key: your_generated_api_key
```

This key is the **built-in osTicket API key**, ensuring that only authorized users or applications can access tickets. You can create multiple keys in the osTicket admin panel and restrict them by IP if needed.

---

## Usage

### List Tickets

```
GET /api/v1/tickets.php?page=1&per_page=10
X-API-Key: your_api_key
```

Optional filters:

| Parameter   | Description                  |
| ----------- | ---------------------------- |
| `id`        | Filter by ticket ID          |
| `status_id` | Filter by status             |
| `dept_id`   | Filter by department ID      |
| `email`     | Filter by user email address |
| `topic_id`  | Filter by help topic         |
| `q`         | Search in ticket subject     |

**Response Example:**

```json
{
  "tickets": [
    {
      "id": 3,
      "number": "154897",
      "subject": "Advanced API Ticket",
      "status": "Open",
      "status_id": 1,
      "dept_id": 2,
      "priority_id": 2,
      "topic_id": 1,
      "created": "2025-10-25 06:00:25",
      "updated": "2025-10-25 07:06:00",
      "user_email": "user@example.com",
      "user_name": "John Doe",
      "responses": [
        {
          "id": 3,
          "created": "2025-10-25 06:00:25",
          "body": "This ticket includes all optional fields",
          "is_note": false,
          "user_id": 2,
          "user_name": "John Doe",
          "user_email": "user@example.com"
        },
        {
          "id": 4,
          "created": "2025-10-25 07:05:00",
          "body": "fdfsd",
          "is_note": false,
          "user_id": 1,
          "user_name": "Admin User",
          "user_email": "admin@example.com"
        }
      ]
    }
  ]
}
```

### Create Ticket

```
POST /api/v1/tickets.php
Content-Type: application/json
X-API-Key: your_api_key
```

Body example:

```json
{
  "email": "user@example.com",
  "name": "John Doe",
  "subject": "Printer issue",
  "message": "The office printer is offline.",
  "topic_id": 1
}
```

### Update Ticket (Status Only)

```
PUT /api/v1/tickets.php?id=123
Content-Type: application/json
X-API-Key: your_api_key
```

Body example:

```json
{
  "status_id": 2,
  "topic_id": 2
}
```

### Delete Ticket

```
DELETE /api/v1/tickets.php?id=123
X-API-Key: your_api_key
```

---

## Testing with Postman

* Import the provided Postman collection `osTicket REST API.postman_collection.json`
* Set environment variables:

  * `BASE_URL` → e.g., `http://localhost/osticket/api/v1`
  * `API_KEY` → your osTicket API key
  * `TICKET_ID` → any valid ticket ID for testing update/delete
* Run requests directly from Postman

---

## License

MIT License. Free to use, modify, and distribute with attribution.

---

## Credits

Built for the [osTicket](https://osticket.com/) open-source helpdesk platform.
