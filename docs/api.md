[← Back to Documentation Index](./README.md)

# API Documentation

This document provides details on the API endpoints available for the frontend application.

## Base URL

`/api`

## Authentication & Headers

All requests must include the following header:

```
Accept: application/json
```

Authenticated requests must also include the Authorization header type Bearer:

```
Authorization: Bearer <your-token>
```

---

## Auth

### Login

Authenticate a user and retrieve an API token.

**Endpoint:** `POST /login`
**Auth Required:** No

**Request Body:**

```json
{
    "email": "user@example.com",
    "password": "password123",
    "device_name": "My iPhone"
}
```

**Response (200 OK):**

```json
{
    "user": {
        "id": 1,
        "name": "John Doe",
        "email": "user@example.com",
        "email_verified_at": "2023-01-01T12:00:00.000000Z",
        "default_workplace_id": null,
        "employee_id": null,
        "department": null,
        "role": null,
        "created_at": "2023-01-01T12:00:00.000000Z",
        "updated_at": "2023-01-01T12:00:00.000000Z"
    },
    "token": "4|... (sanctum token)"
}
```

### Logout

Revoke the current user's API token.

**Endpoint:** `POST /logout`
**Auth Required:** Yes

**Response (200 OK):**

```json
{
    "message": "Successfully logged out."
}
```

### Get User

Get the currently authenticated user's information.

**Endpoint:** `GET /user`
**Auth Required:** Yes

**Response (200 OK):**

```json
{
    "id": 1,
    "name": "John Doe",
    "email": "user@example.com",
    "email_verified_at": "2023-01-01T12:00:00.000000Z",
    "default_workplace_id": null,
    "employee_id": null,
    "department": null,
    "role": null,
    "created_at": "2023-01-01T12:00:00.000000Z",
    "updated_at": "2023-01-01T12:00:00.000000Z"
}
```

---

## Presence

### Check In

Record a user checking into a workplace.

**Endpoint:** `POST /presence/check-in`
**Auth Required:** Yes

**Request Body:**

```json
{
    "workplace_id": 1, // Required, Integer, Must exist
    "latitude": 44.4268, // Required, Numeric, -90 to 90
    "longitude": 26.1025, // Required, Numeric, -180 to 180
    "accuracy": 15, // Optional, Integer (meters)
    "method": "auto", // Required, 'auto' or 'manual'
    "notes": "Arrived early" // Optional, String, max 500 chars
}
```

**Response (201 Created):**

```json
{
    "message": "Checked in successfully.",
    "event": {
        "id": 101,
        "user_id": 1,
        "workplace_id": 1,
        "workplace": {
            "id": 1,
            "name": "Headquarters",
            "location": {
                "latitude": 44.42,
                "longitude": 26.1
            },
            "radius": 100,
            "timezone": "Europe/Bucharest",
            "wifi_ssid": "Office_WiFi",
            "is_active": true
        },
        "event_type": "check_in",
        "event_time": "2023-10-27T08:30:00+00:00",
        "method": "auto",
        "location": {
            "latitude": 44.4268,
            "longitude": 26.1025,
            "accuracy": 15
        },
        "notes": "Arrived early",
        "pair_event_id": null,
        "duration_minutes": null,
        "created_at": "2023-10-27T08:30:00+00:00"
    }
}
```

### Check Out

Record a user checking out.

**Endpoint:** `POST /presence/check-out`
**Auth Required:** Yes

**Request Body:**

```json
{
    "latitude": 44.4268, // Required
    "longitude": 26.1025, // Required
    "accuracy": 15, // Optional
    "method": "manual", // Required
    "notes": "Leaving for lunch" // Optional
}
```

**Response (201 Created):**

```json
{
  "message": "Checked out successfully.",
  "event": {
    "id": 102,
    "user_id": 1,
    "workplace_id": 1,
    "workplace": { ... },
    "event_type": "check_out",
    "event_time": "2023-10-27T12:30:00+00:00",
    "method": "manual",
    "location": { ... },
    "notes": "Leaving for lunch",
    "pair_event_id": 101,
    "duration_minutes": 240,
    "created_at": "2023-10-27T12:30:00+00:00"
  }
}
```

### Current Status

Get the user's current presence status.

**Endpoint:** `GET /presence/current`
**Auth Required:** Yes

**Response (200 OK) - When Present:**

```json
{
  "is_present": true,
  "latest_event": {
    "id": 101,
    "event_type": "check_in",
    ... // Standard PresenceEventResource fields
  },
  "current_workplace": "Headquarters",
  "duration_minutes": 125
}
```

**Response (200 OK) - When Not Present:**

```json
{
    "is_present": false,
    "latest_event": null,
    "current_workplace": null,
    "duration_minutes": null
}
```

_Note: `latest_event` will be null if no events exist, or the last event object if history exists._

### History

Get paginated history of presence events.

**Endpoint:** `GET /presence/history`
**Auth Required:** Yes
**Query Parameters:** `page` (optional)

**Response (200 OK):**

```json
{
  "data": [
    {
       "id": 102,
       "event_type": "check_out",
       ...
    },
    ...
  ],
  "links": { ... },
  "meta": { ... }
}
```

### Today's Summary

Get a summary of today's activity, including total worked minutes and session breakdown.

**Endpoint:** `GET /presence/today`
**Auth Required:** Yes

**Response (200 OK):**

```json
{
  "date": "2023-10-27",
  "total_minutes": 480,
  "sessions": [
    {
      "check_in": { ... }, // PresenceEventResource
      "check_out": { ... }, // PresenceEventResource
      "duration_minutes": 240
    },
    {
      "check_in": { ... },
      "check_out": null, // Currently active session
      "duration_minutes": 60
    }
  ],
  "this_week": {
    "total_minutes": 2400,
    "on_track": "on_track" // 'on_track', 'behind_schedule', or 'over_time'
  }
}
```

---

## Workplaces

### List Workplaces

Get a list of all active workplaces. Can calculate distance from user.

**Endpoint:** `GET /workplaces`
**Auth Required:** Yes

**Query Parameters:**

-   `latitude` (Optional): User's current latitude.
-   `longitude` (Optional): User's current longitude.

**Response (200 OK):**

```json
{
  "data": [
    {
      "id": 1,
      "name": "Headquarters",
      "city": "Cluj-Napoca",
      "county": "Cluj",
      "street_address": "Str. Memorandumului 1",
      "country": "Romania",
      "location": {
        "latitude": 44.42,
        "longitude": 26.10
      },
      "radius": 100,
      "timezone": "Europe/Bucharest",
      "wifi_ssid": "Office_WiFi",
      "is_active": true,
      "distance": 0.5 // Available only if lat/long provided in request. Rounded to 2 decimals.
    },
    ...
  ]
}
```

---

## Devices

### Register Device

Register a device for push notifications.

**Endpoint:** `POST /devices/register`
**Auth Required:** Yes

**Request Body:**

```json
{
    "device_token": "fcm_token_or_apns_token", // Required
    "device_name": "My iPhone 14", // Optional
    "platform": "ios", // Required: 'ios' or 'android'
    "app_version": "1.0.0", // Optional
    "os_version": "17.0" // Optional
}
```

**Response (201 Created):**

```json
{
    "message": "Device registered successfully.",
    "device": {
        "id": 5,
        "device_token": "fcm_token_...",
        "device_name": "My iPhone 14",
        "platform": "ios"
    }
}
```

---

## Kiosk & Config

### Get Configuration

Get public tenant configuration for the frontend/kiosk.

**Endpoint:** `GET /config`
**Auth Required:** No

**Response (200 OK):**

```json
{
    "company_name": "Acme Corp",
    "logo_url": "https://example.com/logo.png",
    "code_length": 3
}
```

### Kiosk Code Entry

Submit a kiosk access code to check in, check out, or verify identity.

**Endpoint:** `POST /kiosk/submit-code`
**Auth Required:** No (Kiosk Mode)

**Request Body:**

```json
{
    "code": "123", // Required, length matches configured code_length
    "flow": "regular", // Optional, 'regular' (default) or 'delegation'
    "workplace_id": 1, // Optional, Workplace ID for check-in
    "device_info": {} // Optional
}
```

**Response (200 OK) - Check In/Out (Regular Flow):**

```json
{
    "message": "Checked in successfully.",
    "type": "checkin", // 'checkin' or 'checkout'
    "user": {
        "name": "John Doe"
    },
    "time": "9:00 AM",
    "event": { ... } // PresenceEvent object
}
```

**Response (200 OK) - Verification (Delegation Flow):**

```json
{
    "message": "User verified.",
    "user": {
        "id": 1,
        "name": "John Doe",
        "email": "user@example.com",
        "default_workplace_id": 1
    }
}
```
