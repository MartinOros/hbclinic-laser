// Chat Bubble Script
document.addEventListener('DOMContentLoaded', function() {
    const chatButton = document.getElementById('chatButton');
    const chatWindow = document.getElementById('chatWindow');
    const chatClose = document.getElementById('chatClose');
    const chatForm = document.getElementById('chatForm');
    const chatMessageDisplay = document.getElementById('chatMessageDisplay');
    const chatFormTimestamp = document.getElementById('chatFormTimestamp');
    
    if (!chatButton || !chatWindow || !chatForm || !chatMessageDisplay) {
        console.error('Chat bubble elements not found');
        return;
    }
    
    // Set form timestamp
    chatFormTimestamp.value = Date.now();
    
    // Open chat
    chatButton.addEventListener('click', function() {
        chatWindow.classList.add('active');
        chatButton.classList.add('active');
    });
    
    // Close chat
    chatClose.addEventListener('click', function() {
        chatWindow.classList.remove('active');
        chatButton.classList.remove('active');
    });
    
    // Close on outside click
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.chat-bubble-container')) {
            chatWindow.classList.remove('active');
            chatButton.classList.remove('active');
        }
    });
    
    // Also close when form is successfully submitted (after auto-close delay)
    // This is handled in the form submission success handler
    
    // Form submission
    chatForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Validate privacy consent checkbox
        const privacyConsent = document.getElementById('chatPrivacyConsent');
        if (!privacyConsent || !privacyConsent.checked) {
            chatMessageDisplay.innerHTML = '<i class="fa-solid fa-exclamation-circle"></i> Prosím, súhlaste s ochranou osobných údajov.';
            chatMessageDisplay.className = 'chat-message error';
            return false;
        }
        
        const submitBtn = chatForm.querySelector('.chat-submit-btn');
        const btnText = submitBtn.querySelector('.btn-text');
        const btnLoader = submitBtn.querySelector('.btn-loader');
        
        // Show loading
        submitBtn.disabled = true;
        btnText.style.display = 'none';
        btnLoader.style.display = 'inline-block';
        chatMessageDisplay.innerHTML = '';
        chatMessageDisplay.className = 'chat-message';
        
        // Get form data
        const formData = new FormData(chatForm);
        
        console.log('Submitting form...');
        // Send AJAX request - use absolute path to ensure it works from any page
        const formAction = '/chat-form.php';
        
        fetch(formAction, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(async response => {
            // Get response text first to check if it's valid JSON
            const text = await response.text();
            console.log('Response text:', text);
            
            // Try to parse as JSON
            let data;
            try {
                data = JSON.parse(text);
            } catch (e) {
                console.error('JSON parse error:', e);
                console.error('Response was:', text);
                throw new Error('Server returned invalid response. Please check server logs.');
            }
            
            // Check if response is ok
            if (!response.ok) {
                throw new Error(data.message || 'Server error');
            }
            
            return data;
        })
        .then(data => {
            if (data.success) {
                chatMessageDisplay.innerHTML = '<i class="fa-solid fa-check-circle"></i> ' + data.message;
                chatMessageDisplay.className = 'chat-message success';
                chatForm.reset();
                chatFormTimestamp.value = Date.now();
                
                // Auto close after 3 seconds
                setTimeout(function() {
                    chatWindow.classList.remove('active');
                    chatButton.classList.remove('active');
                }, 3000);
            } else {
                chatMessageDisplay.innerHTML = '<i class="fa-solid fa-exclamation-circle"></i> ' + (data.message || 'Chyba pri odosielaní');
                chatMessageDisplay.className = 'chat-message error';
            }
        })
        .catch(error => {
            console.error('Chat form error:', error);
            chatMessageDisplay.innerHTML = '<i class="fa-solid fa-exclamation-circle"></i> ' + (error.message || 'Chyba pri odosielaní. Skúste to prosím znova.');
            chatMessageDisplay.className = 'chat-message error';
        })
        .finally(() => {
            // Hide loading
            submitBtn.disabled = false;
            btnText.style.display = 'inline-block';
            btnLoader.style.display = 'none';
        });
    });
});

