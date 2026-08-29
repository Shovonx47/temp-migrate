<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <title>Photo Already Submitted</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 min-h-screen text-white flex items-center justify-center p-6">
    <div class="text-center max-w-sm">
        <div class="w-20 h-20 mx-auto rounded-full bg-teal-500/20 border-2 border-teal-500 flex items-center justify-center mb-5">
            <svg class="w-10 h-10 text-teal-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
            </svg>
        </div>
        <h1 class="text-xl font-bold mb-2">Photo Already Submitted</h1>
        <p class="text-gray-400 text-sm">
            This photo session has already been completed. Please return to your computer to continue with the registration process.
        </p>
    </div>
</body>
</html>
