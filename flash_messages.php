<?php
// flash_messages.php - Helper function to display flash messages
function display_flash_message() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        $type = htmlspecialchars($message['type']);
        $text = htmlspecialchars($message['message']);
        
        // Define CSS classes for each message type
        $classes = [
            'success' => 'flash-success',
            'error' => 'flash-error',
            'warning' => 'flash-warning',
            'info' => 'flash-info'
        ];
        
        $class = $classes[$type] ?? 'flash-info';
        
        echo <<<HTML
        <div class="flash-message {$class}">
            <div class="flash-content">
                <i class="fas fa-{$type}-circle"></i>
                <span>{$text}</span>
            </div>
            <button class="flash-close" onclick="this.parentElement.remove()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        HTML;
        
        // Clear the message after displaying
        unset($_SESSION['flash_message']);
    }
}

// CSS for flash messages (add to your main CSS file)
function flash_messages_css() {
    return <<<CSS
    .flash-message {
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 16px 20px;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        min-width: 300px;
        max-width: 500px;
        z-index: 1000;
        animation: slideIn 0.3s ease;
        transition: all 0.3s ease;
    }
    
    .flash-success {
        background: #10b981;
        color: white;
        border-left: 4px solid #059669;
    }
    
    .flash-error {
        background: #ef4444;
        color: white;
        border-left: 4px solid #dc2626;
    }
    
    .flash-warning {
        background: #f59e0b;
        color: white;
        border-left: 4px solid #d97706;
    }
    
    .flash-info {
        background: #3b82f6;
        color: white;
        border-left: 4px solid #2563eb;
    }
    
    .flash-content {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-grow: 1;
    }
    
    .flash-close {
        background: none;
        border: none;
        color: white;
        cursor: pointer;
        padding: 4px;
        opacity: 0.7;
        transition: opacity 0.2s;
    }
    
    .flash-close:hover {
        opacity: 1;
    }
    
    @keyframes slideIn {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @media (max-width: 768px) {
        .flash-message {
            left: 20px;
            right: 20px;
            min-width: auto;
            max-width: none;
        }
    }
    CSS;
}
?>