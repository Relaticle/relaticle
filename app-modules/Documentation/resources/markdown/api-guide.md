# API Reference

Relaticle provides a REST API for integrating with external applications and building custom workflows.

---

## Interactive Documentation

Full interactive API documentation with request/response examples, authentication details, and a built-in API client is available at:

**[View API Documentation](/docs/api)**

---

## Quick Start

### Authentication

All API requests require a Bearer token. Generate one from **Settings > Access Tokens** in the Relaticle app.

```bash
curl https://your-domain.com/api/v1/companies \
  --header 'Authorization: Bearer YOUR_ACCESS_TOKEN'
```

### Available Endpoints

| Resource | Endpoints |
|----------|-----------|
| **Companies** | `GET` `POST` `PUT` `DELETE` `/api/v1/companies` |
| **People** | `GET` `POST` `PUT` `DELETE` `/api/v1/people` |
| **Opportunities** | `GET` `POST` `PUT` `DELETE` `/api/v1/opportunities` |
| **Tasks** | `GET` `POST` `PUT` `DELETE` `/api/v1/tasks` |
| **Notes** | `GET` `POST` `PUT` `DELETE` `/api/v1/notes` |
| **Custom Fields** | `GET` `/api/v1/custom-fields` |

### Key Features

- **JSON:API format** for consistent response structure
- **Cursor and offset pagination** for large datasets
- **Filtering and sorting** via query parameters
- **Sparse fieldsets** to request only the fields you need
- **Custom fields** support for reading team-specific fields

---

## Have Suggestions?

Open an issue on GitHub to share your API requirements and use cases.

[github.com/Relaticle/relaticle](https://github.com/Relaticle/relaticle)
