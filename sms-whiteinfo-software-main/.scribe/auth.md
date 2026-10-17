# Authenticating requests

To authenticate requests, include an **`Authorization`** header with the value **`"Bearer {YOUR_ACCESS_TOKEN}"`**.

All authenticated endpoints are marked with a `requires authentication` badge in the documentation below.

Generate a personal access token under **Developer → Access Tokens** in your customer dashboard.

The token can be passed in any one of three ways — pick whichever your client makes easiest:

| Variant | How |
|---|---|
| **Header** *(recommended)* | `Authorization: Bearer YOUR_TOKEN` |
| **Query string** | `?token=YOUR_TOKEN` — handy for clients that can only configure a URL |
| **Request body** | `token=YOUR_TOKEN` as a form/JSON field |

<aside class="warning">Query-string and body tokens get written to webserver access logs and browser history. Prefer the <code>Authorization</code> header for anything beyond quick tests.</aside>
