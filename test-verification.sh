#!/bin/bash

echo "==================================="
echo "Email Verification Test Script"
echo "==================================="
echo ""

# Check if user ID provided
if [ -z "$1" ]; then
    echo "Usage: ./test-verification.sh <user_id>"
    echo "Example: ./test-verification.sh 2"
    exit 1
fi

USER_ID=$1

echo "Testing verification for User ID: $USER_ID"
echo ""

# Check user in database
echo "1. Checking user in database..."
php artisan tinker --execute="
\$user = App\Models\User::find($USER_ID);
if (\$user) {
    echo 'User: ' . \$user->email . PHP_EOL;
    echo 'Email Verified: ' . (\$user->hasVerifiedEmail() ? 'YES' : 'NO') . PHP_EOL;
    echo 'Verified At: ' . (\$user->email_verified_at ?? 'NULL') . PHP_EOL;
} else {
    echo 'User not found!' . PHP_EOL;
}
"

echo ""
echo "2. Sending verification email..."
php artisan tinker --execute="
\$user = App\Models\User::find($USER_ID);
if (\$user) {
    \$user->sendEmailVerificationNotification();
    echo 'Email sent to: ' . \$user->email . PHP_EOL;
    echo 'Check your email for verification link.' . PHP_EOL;
} else {
    echo 'User not found!' . PHP_EOL;
}
"

echo ""
echo "3. Watching logs (Ctrl+C to stop)..."
echo "   Click the verification link in your email"
echo "   You should see detailed logs below:"
echo ""
tail -f storage/logs/laravel.log | grep -E "(EMAIL VERIFICATION|User ID|Hash|Signature|verified)"
