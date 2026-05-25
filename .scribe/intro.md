# Introduction

HTTP API for sending SMS, checking account balance and inspecting message batches.

<aside>
    <strong>Base URL</strong>: <code>https://sendora.test</code>
</aside>

    Authenticate every request with a personal access token issued from **Developer → Access Tokens** in your customer dashboard.

    Pass it as a bearer token in the `Authorization` header:

    ```
    Authorization: Bearer YOUR_TOKEN
    ```

    All endpoints return JSON. Error responses use standard HTTP status codes and include a `message` field.

