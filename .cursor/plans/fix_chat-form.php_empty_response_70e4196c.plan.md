---
name: Fix chat-form.php empty response
overview: Diagnose and fix the issue where chat-form.php returns empty response when called from chat bubble, even though test-chat-form.html works correctly with the same PHP file.
todos: []
---

# Fix chat-form.php Empty Response Issue

## Problem Analysis

- `test-chat-form.html` works correctly and receives JSON response from `chat-form.php`
- Chat bubble from `chat-bubble.js` returns empty response from `chat-form.php`
- Both use same fetch call: `fetch('chat-form.php', ...)`
- JavaScript error: "Unexpected end of JSON input" with empty response text

## Possible Causes

1. **Path issue**: Chat bubble might be called from different directory context
2. **Timing issue**: Output buffering might be cleared before JSON is sent
3. **PHP fatal error**: Error occurs after output buffering starts but before JSON is sent
4. **PHPMailer output**: PHPMailer might output something that breaks JSON

## Solution

### 1. Add absolute path handling in [chat-bubble.js](js/chat-bubble.js)

- Use absolute path `/chat-form.php` instead of relative `chat-form.php`
- This ensures same path regardless of which page calls it

### 2. Add response validation in [chat-form.php](chat-form.php)

- Add check at end to ensure JSON was actually sent
- Add logging to track when/why empty response occurs
- Ensure all code paths return JSON

### 3. Simplify output buffering

- Remove complex nested output buffering
- Use single, simple output buffer approach
- Ensure headers are sent before any output

### 4. Add diagnostic endpoint

- Create simple test that just returns JSON without any processing
- This will help isolate if issue is in email sending or general PHP execution

## Files to modify

- [js/chat-bubble.js](js/chat-bubble.js) - Change to absolute path
- [chat-form.php](chat-form.php) - Simplify output buffering, add validation

## Testing

- Test chat bubble from homepage (index.html)
- Test chat bubble from other pages (kontakt.html, o-nas.html, etc.)
- Compare with test-chat-form.html behavior