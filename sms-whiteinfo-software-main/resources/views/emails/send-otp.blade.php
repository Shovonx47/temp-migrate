<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verification Code</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background-color: #f4f7f6;
            margin: 0;
            padding: 0;
            color: #333333;
        }
        .container {
            max-width: 520px;
            margin: 40px auto;
            background: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            border: 1px solid #e5e7eb;
        }
        .header {
            background-color: #0f766e;
            padding: 30px 20px;
            text-align: center;
            color: #ffffff;
        }
        .header h1 {
            margin: 0;
            font-size: 22px;
            font-weight: 700;
            letter-spacing: 0.5px;
        }
        .content {
            padding: 35px 30px;
            text-align: center;
        }
        .content p {
            font-size: 15px;
            line-height: 1.6;
            color: #4b5563;
            margin: 0 0 20px;
        }
        .otp-box {
            background: #f0fdfa;
            border: 2px dashed #0d9488;
            border-radius: 10px;
            padding: 16px 24px;
            display: inline-block;
            margin: 15px 0 25px;
        }
        .otp-code {
            font-family: 'Courier New', Courier, monospace;
            font-size: 32px;
            font-weight: 800;
            letter-spacing: 8px;
            color: #0f766e;
        }
        .footer {
            background-color: #f9fafb;
            padding: 20px;
            text-align: center;
            font-size: 12px;
            color: #9ca3af;
            border-top: 1px solid #f3f4f6;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Email Verification</h1>
        </div>
        <div class="content">
            <p>Hello,</p>
            <p>Thank you for signing up with <strong>SMS WhiteInfo</strong>. Please use the verification code below to verify your email address:</p>
            
            <div class="otp-box">
                <span class="otp-code">{{ $otp }}</span>
            </div>

            <p style="font-size: 13px; color: #6b7280;">This code is valid for <strong>{{ $expiryMinutes }} minutes</strong>. If you did not request this verification, please ignore this email.</p>
        </div>
        <div class="footer">
            &copy; {{ date('Y') }} SMS WhiteInfo. All rights reserved.
        </div>
    </div>
</body>
</html>
